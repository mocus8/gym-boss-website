<?php
// Use-case/координатор класс, для использования методов из двух других сервисов, для обхода цикличейской DI зависимости

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Orders;
use App\Orders\OrderService;
use App\Payments\PaymentService;
use App\Mail\MailService;

// Класс для управления отменами заказов
class CancelOrderUseCase {
    // Приватное свойство (переменная класса), привязанная к объекту
    private \mysqli $db;
    private string $baseUrl;
    private OrderService $orderService;
    private PaymentService $paymentService;
    private MailService $mailService;

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(
        \mysqli $db,
        string $baseUrl,
        OrderService $orderService,
        PaymentService $paymentService,
        MailService $mailService
    ) {
        $this->db = $db;
        $this->baseUrl = $baseUrl;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->mailService = $mailService;
    }

    // Метод для отмены заказа пользователем, объеденяет (координирует) методы двух других сервисов внутри транзакции
    public function markCancelByUser(int $orderId, int $userId): void {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            // Помечаем сам заказ как отмененный
            $justMarked = $this->orderService->markCancelByUserInTx($orderId, $userId);
            // Помечаем в бд все его платежи как отмененные
            $this->paymentService->cancelAllByOrderId($orderId, 'CANCELED_BY_USER', 'Canceled by user');

            // Комитим транзакцию
            $this->db->commit();
        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }

        // Если статус реально поменялся - отправляем письмо 
        if ($justMarked) {
            // Получаем данные о заказе
            $orderData = $this->orderService->getById($orderId);

            // Собираем ссылку на страницу заказа
            $orderUrl = $this->baseUrl . '/orders/' . $orderId;

            // Помечаем того, кто отменил
            $canceledBy = 'user';

            // Отправляем письмо
            $this->mailService->sendOrderCanceled(
                $orderData['order'],
                $orderData['items'],
                $canceledBy,
                $orderUrl
            );
        }

        return;
    }

    // Метод для отмены заказа провайдером, объеденяет (координирует) методы двух других сервисов внутри транзакции
    public function markCancelFromPaymentProvider(int $orderId): void {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            // Тут вызываем два метода: markCancelFromPaymentProviderInTx и метод для отмены платежей из PaymentService 
            // Помечаем сам заказ как отмененный
            $justMarked = $this->orderService->markCancelFromPaymentProviderInTx($orderId);
            // Помечаем в бд все его платежи как отмененные
            $this->paymentService->cancelAllByOrderId($orderId, 'CANCELED_BY_PROVIDER', 'Canceled by provider');

            // Комитим транзакцию
            $this->db->commit();
        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }

        // Если статус реально поменялся - отправляем письмо 
        if ($justMarked) {
            // Получаем данные о заказе
            $orderData = $this->orderService->getById($orderId);

            // Собираем ссылку на страницу заказа
            $orderUrl = $this->baseUrl . '/orders/' . $orderId;

            // Помечаем того, кто отменил
            $canceledBy = 'provider';

            // Отправляем письмо
            $this->mailService->sendOrderCanceled(
                $orderData['order'],
                $orderData['items'],
                $canceledBy,
                $orderUrl
            );
        }

        return;
    }
}