<?php
// Use-case/координатор класс, для использования методов из двух других сервисов, для обхода цикличейской DI зависимости

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Orders;
use App\Orders\OrderService;
use App\Mail\MailService;
use App\Support\Logger;

// Класс для пометки заказов как готовых к самовывозу или отправленых
class FulfillOrderUseCase {
    // Приватное свойство (переменная класса), привязанная к объекту
    private string $baseUrl;
    private OrderService $orderService;
    private MailService $mailService;
    private Logger $logger;

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(string $baseUrl, OrderService $orderService, MailService $mailService, Logger $logger) {
        $this->baseUrl = $baseUrl;
        $this->orderService = $orderService;
        $this->mailService = $mailService;
        $this->logger = $logger;
    }

    // Метод для пометки заказа как отправленого курьером, объеденяет (координирует) методы двух других сервисов
    public function markShipped(int $orderId): void {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        $this->logger->info('Order {order_id} shipped initiated', [
            'order_id' => $orderId,
        ]);

        // Помечаем сам заказ как отправленый
        $justMarked = $this->orderService->markShipped($orderId);

        // Если статус реально поменялся - отправляем письмо 
        if ($justMarked) {
            // Получаем данные о заказе
            $orderData = $this->orderService->getById($orderId);

            // Собираем ссылку на страницу заказа
            $orderUrl = $this->baseUrl . '/orders/' . $orderId;

            // Отправляем письмо
            $this->mailService->sendOrderShipped(
                $orderData['order'],
                $orderData['items'],
                $orderUrl
            );
        }
    }
    // Метод для пометки заказа как готового к получению, объеденяет (координирует) методы двух других сервисов
    public function markReadyForPickup(int $orderId): void {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        $this->logger->info('Order {order_id} ready for pickup initiated', [
            'order_id' => $orderId,
        ]);

        // Помечаем сам заказ как готовый к получению
        $justMarked = $this->orderService->markReadyForPickup($orderId);

        // Если статус реально поменялся - отправляем письмо 
        if ($justMarked) {
            // Получаем данные о заказе
            $orderData = $this->orderService->getById($orderId);

            // Собираем ссылку на страницу заказа
            $orderUrl = $this->baseUrl . '/orders/' . $orderId;

            // Отправляем письмо
            $this->mailService->sendOrderReadyForPickup(
                $orderData['order'],
                $orderData['items'],
                $orderUrl
            );
        }
    }
}