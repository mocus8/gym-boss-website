<?php
declare(strict_types=1);

// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo, будет использовать в контроллерах и других файлах
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах

namespace App\Orders;

use App\Products\ProductService;
use App\Cart\CartService;

class OrderService {
    private \mysqli $db;
    private OrderRepository $orderRepository;
    private OrderItemRepository $orderItemRepository;
    private ProductService $productService;
    private CartService $cartService;

    // Константы для типов доставки
    private const DELIVERY_TYPE_COURIER = 1;
    private const DELIVERY_TYPE_PICKUP = 2;

    // Константы для стоимости доставки и порога бесплатной доставки
    private float $deliveryCourierPrice;
    private float $deliveryFreeThreshold;

    // Константы для времени доставки/сборки заказа
    private int $pickupReadyFromHours;
    private int $pickupReadyToHours;
    private int $courierDeliveryFromHours;
    private int $courierDeliveryToHours;

    // Актуальные id для статусов заказа (ассоциативный массив code => id), живет в течении одного запроса
    private array $statusIdCache = [];

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(
        \mysqli $db,
        ProductService $productService,
        CartService $cartService,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        float $deliveryCourierPrice,
        float $deliveryFreeThreshold,
        int $pickupReadyFromHours,
        int $pickupReadyToHours,
        int $courierDeliveryFromHours,
        int $courierDeliveryToHours
    ) {
        $this->db = $db;
        $this->productService = $productService;
        $this->cartService = $cartService;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->deliveryCourierPrice = $deliveryCourierPrice;
        $this->deliveryFreeThreshold = $deliveryFreeThreshold;
        $this->pickupReadyFromHours = $pickupReadyFromHours;
        $this->pickupReadyToHours = $pickupReadyToHours;
        $this->courierDeliveryFromHours = $courierDeliveryFromHours;
        $this->courierDeliveryToHours = $courierDeliveryToHours;
    }

    // Вспомогательный приватный метод для получения id статуса заказа по code (с кэшем)
    public function getStatusIdByCode(string $code): int {
        if (isset($this->statusIdCache[$code])) {
            return $this->statusIdCache[$code];
        }

        $statusId = $this->orderRepository->findOrderStatusIdByCode($code);
        $this->statusIdCache[$code] = $statusId;

        return $statusId;
    }

    // Метод создания order на основе cart (возвращает id созданного заказа)
    public function createFromCart(
        int $cartId,
        int $userId,
        int $deliveryTypeId,
        ?string $deliveryAddressText = null,
        ?string $deliveryPostalCode = null,
        ?int $storeId = null
    ): int {
        // Базовые проверки id
        if ($cartId <= 0) {
            throw new \InvalidArgumentException('Invalid cartId');
        }
    
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }
    
        if ($deliveryTypeId <= 0) {
            throw new \InvalidArgumentException('Invalid deliveryTypeId');
        }

        // Проверка, что deliveryTypeId это строго одно из двух валидных значений
        if (!in_array($deliveryTypeId, [self::DELIVERY_TYPE_COURIER, self::DELIVERY_TYPE_PICKUP], true)) {
            throw new \InvalidArgumentException('Unknown deliveryTypeId');
        }

        // Проверка на полноту данных при выбранном типе доставки
        if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
            if ($deliveryAddressText === null || trim($deliveryAddressText) === '') {
                throw new \InvalidArgumentException(
                    'Empty deliveryAddressText while courier delivery'
                );
            }
            if ($deliveryPostalCode === null || trim($deliveryPostalCode) === '') {
                throw new \InvalidArgumentException(
                    'Empty deliveryPostalCode while courier delivery'
                );
            }
        } elseif ($deliveryTypeId === self::DELIVERY_TYPE_PICKUP) {
            if ($storeId === null || $storeId <= 0) {
                throw new \InvalidArgumentException('Empty or invalid storeId while pickup');
            }
        }

        // Получаем недостающие поля для создания заказа
        $totalQty = $this->cartService->getItemsCount($cartId);
        $totalPrice = $this->cartService->getItemsTotal($cartId);
        $deliveryCost = $this->getDeliveryPrice($deliveryTypeId, $totalPrice);
        $statusId = $this->getStatusIdByCode('pending_payment');

        // Перед созданием заказа проверяем что корзина не пустая
        $items = $this->cartService->getItems($cartId);
        if ($items === []) {
            throw new \InvalidArgumentException('Empty cart while creating order');
        }
    
        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            // Создаем заказ
            $orderId = $this->orderRepository->createFromCart(
                $userId,
                $totalQty,
                $totalPrice,
                $deliveryTypeId,
                $deliveryCost,
                $deliveryAddressText,
                $deliveryPostalCode,
                $storeId,
                $statusId
            );

            // Заполняем товары
            $this->orderItemRepository->fill($orderId, $items);

            // Помечаем корзину как конвертированную
            $this->cartService->convert($cartId);

            // Комитим транзакцию
            $this->db->commit();

            return $orderId;

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }
    }

    // Метод для получения инфы о заказе и всех товаров в нем (с первой фотографией) по его id
    public function getById(int $orderId): array {
        // Базовые проверки id
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        return $this->getOrderWithItems($orderId, null);
    }

    // Метод для получения инфы о заказе и всех товаров в нем (с первой фотографией) по его id и по user id
    // Для запросов со стороны пользователей, дополнительно проверяется принадлежность по user id
    public function getByIdForUser(int $orderId, int $userId): array {
        // Базовые проверки id
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        return $this->getOrderWithItems($orderId, $userId);
    }

    // Метод для получения инфы о всех заказах пользователя по userId
    public function getUserOrders(int $userId): array {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        return $this->orderRepository->findUserOrders($userId);
    }

    // Метод для пометки заказа как отменненого с привязкой к user id
    // Должен вызываться только внутри транзакции
    public function markCancelByUserInTx(int $orderId, int $userId): bool {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Получаем статус заказа с блокировкой строки
        $orderStatusId = $this->orderRepository->findForStatusUpdate($orderId, $userId);

        $canceledStatusId = $this->getStatusIdByCode('canceled');
        $pendingStatusId = $this->getStatusIdByCode('pending_payment');

        // Уже отменен (тихо выходим из метода)
        if ($orderStatusId === $canceledStatusId) {
            return false;
        }

        // Статус не pending_payment
        if ($orderStatusId !== $pendingStatusId) {
            throw new \RuntimeException('Order cannot be canceled from current status');
        }

        // Обновляем статус заказа на canceled
        $this->orderRepository->markAsCanceled($canceledStatusId, $orderId, $userId);

        return true;
    }

    
    // Метод для пометки заказа как отменненого, отмены от провайдера/юкассы или из вебхука
    // Должен вызываться только внутри транзакции
    public function markCancelFromPaymentProviderInTx(int $orderId): bool {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Получаем статус заказа с блокировкой строки
        $orderStatusId = $this->orderRepository->findForStatusUpdate($orderId, null);

        $canceledStatusId = $this->getStatusIdByCode('canceled');
        $pendingStatusId = $this->getStatusIdByCode('pending_payment');

        // Уже отменен (тихо выходим из метода)
        if ($orderStatusId === $canceledStatusId) {
            return false;
        }

        // Статус не pending_payment
        if ($orderStatusId !== $pendingStatusId) {
            throw new \RuntimeException('Order cannot be canceled from current status');
        }

        // Обновляем статус заказа на canceled
        $this->orderRepository->markAsCanceled($canceledStatusId, $orderId, null);

        return true;
    }

    // Метод пометки заказа как оплаченного: только логика
    // Должен вызываться только внутри транзакции
    public function markPaidInTx(int $orderId): bool {
        // Получаем статус, тип доставки и время оплаты заказа с блокировкой строки (FOR UPDATE)
        $paymentInfo = $this->orderRepository->findForPaymentUpdate($orderId);

        $orderStatusId = (int)$paymentInfo["status_id"];
        $deliveryTypeId = (int)$paymentInfo["delivery_type_id"];
        $paidAtRow = $paymentInfo["paid_at"];

        $pendingStatusId = $this->getStatusIdByCode('pending_payment');
        $paidStatusId = $this->getStatusIdByCode('paid');

        // Уже оплачен
        if ($orderStatusId === $paidStatusId) {
            return false;
        }

        // Статус не pending_payment
        if ($orderStatusId !== $pendingStatusId) {
            throw new \RuntimeException('Order status is not pending_payment');
        }

        // Проверяем поле paid_at
        if ($paidAtRow !== null) {
            // Повторный вызов или особый кейс: используем уже сохранённое время
            $paidAt = new \DateTimeImmutable($paidAtRow);
        } else {
            // Первый раз помечаем как оплаченный: берем текущее время
            $paidAt = new \DateTimeImmutable('now');
        }
        $paidAtFormatted = $paidAt->format('Y-m-d H:i:s');

        // Считаем время доставки/готовности
        $courierFrom = null;
        $courierTo = null;
        $readyForPickupFrom = null;
        $readyForPickupTo = null;

        if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
            $courierFrom = $paidAt->modify('+' . $this->courierDeliveryFromHours . ' hours')->format('Y-m-d H:i:s');
            $courierTo = $paidAt->modify('+' . $this->courierDeliveryToHours . ' hours')->format('Y-m-d H:i:s');
        } elseif ($deliveryTypeId === self::DELIVERY_TYPE_PICKUP) {
            $readyForPickupFrom = $paidAt->modify('+' . $this->pickupReadyFromHours . ' hours')->format('Y-m-d H:i:s');
            $readyForPickupTo = $paidAt->modify('+' . $this->pickupReadyToHours . ' hours')->format('Y-m-d H:i:s');
        }

        // Обновляем статус заказа на paid
        $this->orderRepository->markAsPaid(
            $paidStatusId,
            $courierFrom,
            $courierTo,
            $readyForPickupFrom,
            $readyForPickupTo,
            $paidAtFormatted,
            $orderId
        );

        return true;
    }

    // Метод пометки заказа как оплаченного 
    // Оболочка метода markPaidInTx с транзакцией
    public function markPaid(int $orderId): bool {

        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();
        
        try {
            $justMarked = $this->markPaidInTx($orderId);

            // Комитим транзакцию
            $this->db->commit();

            return $justMarked;
        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }
    }

    // Метод для пометки заказа как отправленого 
    public function markShipped(int $orderId): bool {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();
        
        try {
            // Получаем статус заказа с блокировкой строки
            $orderStatusId = $this->orderRepository->findForStatusUpdate($orderId, null);

            $shippedStatusId = $this->getStatusIdByCode('shipped');
            $paidStatusId = $this->getStatusIdByCode('paid');

            // Уже отправлен 
            if ($orderStatusId === $shippedStatusId) {
                $this->db->commit();
                return false;
            }

            // Еще не оплачен
            if ($orderStatusId !== $paidStatusId) {
                throw new \RuntimeException('Order status is not paid');
            }

            // Обновляем статус заказа на shipped
            $this->orderRepository->setStatus($shippedStatusId, $orderId);

            // Комитим транзакцию
            $this->db->commit();

            return true;
        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }
    }

    // Метод для пометки заказа как готового к самовывозу 
    public function markReadyForPickup(int $orderId): bool {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();
        
        try {
            // Получаем статус заказа с блокировкой строки
            $orderStatusId = $this->orderRepository->findForStatusUpdate($orderId, null);

            $readyForPickupStatusId = $this->getStatusIdByCode('ready_for_pickup');
            $paidStatusId = $this->getStatusIdByCode('paid');

            // Уже готов для получения 
            if ($orderStatusId === $readyForPickupStatusId) {
                $this->db->commit();
                return false;
            }

            // Еще не оплачен
            if ($orderStatusId !== $paidStatusId) {
                throw new \RuntimeException('Order status is not paid');
            }

            // Обновляем статус заказа на ready_for_pickup
            $this->orderRepository->setStatus($readyForPickupStatusId, $orderId);

            // Комитим транзакцию
            $this->db->commit();

            return true;
        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }
    }

    // Метод для получения базовой инфы о заказе и блокировки строки заказа на момент создания оплаты
    public function lockForPayment(int $orderId, int $userId): array {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Получаем базовую инфу о заказе с блокировкой строки
        $order = $this->orderRepository->lockForPayment($orderId, $userId);

        return $order;
    }

    // Метод для получения позиций заказа 
    public function getItemsForReceipt(int $orderId): array {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Получаем инфу о составе заказа
        $items = $this->orderItemRepository->findItemsForReceipt($orderId);

        return $items;
    }

    // Приватный вспомагательный метод для нахождения заказа
    // В зависимости от параметров ищет заказ либо просто по id либо с привязкой к user id
    private function getOrderWithItems(int $orderId, ?int $userId): array {
        $order = $this->orderRepository->findInfo($orderId, $userId);

        // Объявляем примерные/фактические сроки доставки/самовывоза
        $deliveryFrom = null;
        $deliveryTo = null;

        $statusCode = $order["status_code"];
        $deliveryTypeId = (int)$order["delivery_type_id"];

        if ($statusCode === 'pending_payment') {
            // Заказ не оплачен, считаем от now
            $now = new \DateTimeImmutable('now');

            if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
                $deliveryFrom = $now->modify('+' . $this->courierDeliveryFromHours . ' hours')->format('Y-m-d H:i:s');
                $deliveryTo = $now->modify('+' . $this->courierDeliveryToHours . ' hours')->format('Y-m-d H:i:s');
            } elseif ($deliveryTypeId === self::DELIVERY_TYPE_PICKUP) {
                $deliveryFrom = $now->modify('+' . $this->pickupReadyFromHours . ' hours')->format('Y-m-d H:i:s');
                $deliveryTo = $now->modify('+' . $this->pickupReadyToHours . ' hours')->format('Y-m-d H:i:s');
            }

        } elseif ($statusCode === 'paid' || $statusCode === 'shipped' || $statusCode === 'ready_for_pickup') {
            // Заказ оплачен, используем значения из бд
            if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
                $deliveryFrom = $order["courier_delivery_from"];
                $deliveryTo = $order["courier_delivery_to"];
            } elseif ($deliveryTypeId === self::DELIVERY_TYPE_PICKUP) {
                $deliveryFrom = $order["ready_for_pickup_from"];
                $deliveryTo = $order["ready_for_pickup_to"];
            }
        }

        // Удаляем сырые значения из бд из массива order
        unset(
            $order['courier_delivery_from'],
            $order['courier_delivery_to'],
            $order['ready_for_pickup_from'],
            $order['ready_for_pickup_to']
        );

        // Добавляем новые поля в массив order
        $order['delivery_from'] = $deliveryFrom;
        $order['delivery_to'] = $deliveryTo;

        $itemsQuantity = $this->orderItemRepository->findItems($order["id"]);

        if ($itemsQuantity === []) {
            return [
                'order' => $order,
                'items' => []
            ];
        }

        // Получаем массив товаров через productService
        $itemsIds = array_keys($itemsQuantity);
        $products = $this->productService->getByIds($itemsIds);

        // Собираем итоговый массив позиций корзины: товар + quantity + price на момент заказа
        $items = [];
        foreach ($itemsQuantity as $productId => $item) {
            if (!isset($products[$productId])) continue;

            $items[] = array_merge($products[$productId], [
                'quantity' => $item['quantity'],
                'price'    => $item['price']
            ]);
        }

        return [
            'order' => $order,
            'items' => $items
        ];
    }

    // Вспомогательный приватный метод для получения цены доставки
    private function getDeliveryPrice(int $deliveryTypeId, float $totalPrice): float {

        // Если курьерский тип доставки и стоимость товаров меньше порога - возвращаем стоимость доставки
        if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER && $totalPrice < $this->deliveryFreeThreshold) {
            return $this->deliveryCourierPrice;
        }

        // Иначе стоимость ноль
        return 0.00;
    }
}