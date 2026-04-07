<?php
declare(strict_types=1);

// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo, будет использовать в контроллерах и других файлах
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах
// Вызывается из OrderController, без отдельного контроллера 

namespace App\Payments;

use App\Orders\OrderService;
use App\Users\UserRepository;
use App\Integrations\Yookassa\YookassaGateway;
use App\Support\Logger;

// Класс для управления платежами
class PaymentService {
    private \mysqli $db;
    private string $baseUrl;
    private OrderService $orderService;
    private PaymentRepository $paymentRepository;
    private UserRepository $userRepository;
    private YookassaGateway $yookassaGateway;
    private int $deliveryVatCode;    // код НДС для доставки
    private Logger $logger;

    // Константы для типов доставки
    private const DELIVERY_TYPE_COURIER = 1;
    private const DELIVERY_TYPE_PICKUP = 2;

    public function __construct(
        \mysqli $db,
        string $baseUrl,
        OrderService $orderService,
        PaymentRepository $paymentRepository,
        UserRepository $userRepository,
        YookassaGateway $yookassaGateway,
        int $deliveryVatCode,
        Logger $logger
    ) {
        $this->db = $db;
        $this->baseUrl = $baseUrl;
        $this->orderService = $orderService;
        $this->paymentRepository = $paymentRepository;
        $this->userRepository = $userRepository;
        $this->yookassaGateway = $yookassaGateway;
        $this->deliveryVatCode = $deliveryVatCode;
        $this->logger = $logger;
    }

    // Метод для получения или создания платежа, возвращает существующею/новую ссылку на платеж
    public function getOrCreatePayment(int $orderId, int $userId): string {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        $this->logger->info('Payment creation started for order {order_id} by user {user_id}', [
            'order_id' => $orderId,
            'user_id' => $userId,
        ]);

        // Cтавим ожидание блокировок как 5 секунд, потом ошибка от sql
        $this->db->query("SET SESSION innodb_lock_wait_timeout = 5");

        // Массив со всей инфой о заказе
        // Нужен для синхронизации переменных которые появляются во время транзакции
        // Эти переменные могут не объявится если во время транзакции что-то упадет
        $draftPaymentInfo = null;

        // Начинаем транзакцию (либо все либо ничего для sql)
        $this->db->begin_transaction();

        try {
            // Получаем базовую инфу о заказе из orders с блокировкой строки через for update
            $order = $this->orderService->lockForPayment($orderId, $userId);
            $orderTotal = (float)$order['total_price'] + (float)$order['delivery_cost'];
            // Получаем id статуса pending_payment через таблицу-справочник
            $pendingPaymentStatusId = $this->orderService->getStatusIdByCode('pending_payment');

            // Проверяем статус заказа
            if ((int)$order['status_id'] !== $pendingPaymentStatusId) {
                throw new \RuntimeException('Order status is not pending_payment');
            }

            // Смотрим, есть ли активная ссылка на платеж
            $paymentConfirmationUrl = $this->paymentRepository->findActivePaymentUrl($orderId);

            // Если ссылка есть то просто возвращаем ее
            if ($paymentConfirmationUrl !== null) {
                $this->db->commit();
                
                $this->logger->info('Payment {payment_url} for order {order_id} already exists', [
                    'payment_url' => $paymentConfirmationUrl,
                    'order_id' => $orderId,
                ]);
                
                return $paymentConfirmationUrl;
            }

            // Если платежа нет - создаем новый в три этапа:
            // 1. собираем инфу для платежа в массив draftPaymentInfo
            // 2. через gateway юкассы создаем платеж в самой юкассе 
            // 3. если платеж в юкассе создан - дополняем запись платежа в бд, если нет - помечаем как failed

            // Получаем логин пользователя для формирования чека
            $email = $this->userRepository->findEmailById($userId);
            if ($email === null) {
                throw new \RuntimeException('User email not found');
            }

            // Получаем залоченые позиции заказа из order_items
            $items = $this->orderService->getItemsForReceipt($orderId);
            // Cоздаём массив товаров в нужном для чека формате
            $itemsForReceipt = $this->buildReceiptItems($order, $items);
            
            // Проверяем итоговую стоимость (должна сходиться с чеком)
            $this->checkReceiptTotalMatch($itemsForReceipt, $orderTotal);
        
            // Фиксированный idempotency key для защиты от дублей
            $idempotencyKey = $this->generateUuidV4();

            // Создаем в бд платеж со статусом creating 
            $paymentId  = $this->paymentRepository->create($orderId, $orderTotal, $idempotencyKey);

            $this->db->commit();

            // Все нужные далее переменные оборачиваем в массив
            // Так если без ошибок дошло до commit то будет доступен массив со всеми переменными, иначе он null
            $draftPaymentInfo = [
                'paymentId' => $paymentId,
                'orderId' => $orderId,
                'orderTotal' => $orderTotal,
                'email' => $email,
                'itemsForReceipt' => $itemsForReceipt,
                'idempotencyKey' => $idempotencyKey,
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        // Проверяем инфу для создания платежа
        if ($draftPaymentInfo === null) {
            throw new \RuntimeException('Draft payment info is missing');
        }

        // Формируем payload (тело) для создания платежа
        $payload = [
            'amount' => [
                'value' => number_format($draftPaymentInfo['orderTotal'], 2, '.', ''),
                'currency' => 'RUB'
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $this->baseUrl . '/orders/' . $draftPaymentInfo['orderId']
            ],
            'capture' => true,
            'description' => 'Заказ №' . $draftPaymentInfo['orderId'],
            'metadata' => ['orderId' => $draftPaymentInfo['orderId']],

            'receipt' => [
                'customer' => [
                    'email' => $draftPaymentInfo['email']
                ],
                'items' => $draftPaymentInfo['itemsForReceipt']
            ]
        ];
        
        // Пытаемся создать платеж в юкассе через gateway-интеграционную оболочку над sdk юкассы
        try {
            // Создается платеж, возвращается массив с инфой о платеже из юкассы
            $createdPayment = $this->yookassaGateway->createPayment($payload, $draftPaymentInfo['idempotencyKey']);
        } catch (\Throwable $e) {
            // При ошибке ставим на "черновик" платежа ошибку и прокидываем ошибку
            $this->paymentRepository->setFailed($draftPaymentInfo['paymentId'], $this->mapToErrorCode($e), $e->getMessage());
            throw $e;
        }

        // Переносим значения в локальные переменные для использовании в sql запросах
        // Если попытаться передать просто как поле DTO то может вылететь ошибка о попытке модификации приватного поля
        $externalPaymentId = $createdPayment->paymentId;
        $confirmationUrl = $createdPayment->confirmationUrl;
        $expiresAt = $createdPayment->expiresAt;

        // Меняем статус платежа на pending и заполняем информацию по платежу
        $this->paymentRepository->setPending($externalPaymentId, $confirmationUrl, $expiresAt, $draftPaymentInfo['paymentId']);

        $this->logger->info('Payment {external_payment_id} for order {order_id} created in Yookassa', [
            'order_id' => $orderId,
            'external_payment_id' => $externalPaymentId,
        ]);

        return $confirmationUrl;
    }

    // Метод для получения активного платежа
    public function getActivePayment(int $orderId): ?array {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Получаем информацию о последнем активном платеже на заказ
        return $this->paymentRepository->findActivePayment($orderId);
    }

    // Метод для получения id заказа по id платежа из провайдера (юкассы)
    public function getOrderIdByExternalId(string $externalPaymentId): int {
        return $this->paymentRepository->findOrderIdByExternalId($externalPaymentId);
    }

    // Метод для обновления статуса платежа по id из провайдера (юкассы)
    public function updateStatusByExternalId(
        string $externalPaymentId,
        string $newStatus,
        string $newProviderStatus,
        ?string $errorCode = null,
        ?string $errorMessage = null
    ): void {
        $this->paymentRepository->updateStatusByExternalId(
            $externalPaymentId,
            $newStatus,
            $newProviderStatus,
            $errorCode,
            $errorMessage
        );
    }

    // Метод для пометки в бд всех платежей одного заказа как отмененнных
    public function cancelAllByOrderId(int $orderId, ?string $errorCode = null, ?string $errorMessage = null): void {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        $this->paymentRepository->cancelAllByOrderId($orderId, $errorCode, $errorMessage);
    }

    // Вспомогательный приватный метод для генерации уникального кода UUID v4
    private function generateUuidV4(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Вспомогательный приватный метод для формирования массива товаров для чека
    private function buildReceiptItems(array $order, array $items): array {
        // Cоздаём массив товаров в нужном для чека формате
        $itemsForReceipt = [];
        foreach ($items as $item) {
            $price = number_format((float)$item['price'], 2, '.', '');
    
            $itemsForReceipt[] = [
                'description' => $item['product_name'],
                'quantity' => $item['quantity'],
                'amount' => [
                    'value' => $price,
                    'currency' => 'RUB'
                ],
                'vat_code' => $item['vat_code'],
                'payment_mode' => 'full_payment',
                'payment_subject' => 'commodity'
            ];
        }
    
        // Добавляем стоимость доставки если она есть
        $deliveryCost = number_format((float)$order['delivery_cost'], 2, '.', '');
        $deliveryTypeId = (int)$order['delivery_type_id'];
        if ((float)$order['delivery_cost'] > 0 && $deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
            $itemsForReceipt[] = [
                'description' => 'Доставка',
                'quantity' => 1,
                'amount' => [
                    'value' => $deliveryCost,
                    'currency' => 'RUB'
                ],
                'vat_code' => $this->deliveryVatCode,
                'payment_mode' => 'full_payment',
                'payment_subject' => 'service'    // доставка это услуга, не товар
            ];
        }

        return $itemsForReceipt;
    }

    // Вспомогательный приватный метод для проверки схождения сумм заказа из объекта order и массива itemsForReceipt
    private function checkReceiptTotalMatch(array $itemsForReceipt, float $orderTotal): void {
        // Проверяем итоговую стоимость (должна сходиться с чеком)
        $receiptTotal = 0;
        foreach ($itemsForReceipt as $item) {
            $receiptTotal += (float)$item['amount']['value'] * $item['quantity'];
        }
    
        if (abs($receiptTotal - $orderTotal) > 0.01) {
            throw new \RuntimeException('Receipt total mismatch');
        }
    }

    // Вспомогательный приватный метод для маппинга ошибки создания платежа в юкассе
    private function mapToErrorCode(\Throwable $e): string {
        // Ошибки запроса/данных
        if ($e instanceof \YooKassa\Common\Exceptions\BadApiRequestException) {
            return 'BAD_REQUEST';
        }

        // Проблемы соединения/таймауты
        if ($e instanceof \YooKassa\Common\Exceptions\ApiConnectionException) {
            return 'PAYMENT_SERVICE_UNAVAILABLE';
        }

        // Общая ошибка API
        if ($e instanceof \YooKassa\Common\Exceptions\ApiException) {
            return 'PAYMENT_SYSTEM_ERROR';
        }

        // Всё остальное это внутренняя ошибка
        return 'INTERNAL_ERROR';
    }
}