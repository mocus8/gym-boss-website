<?php
// Use-case/координатор класс, для использования методов из двух других сервисов, для обхода цикличейской DI зависимости

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Orders;
use App\Orders\OrderService;
use App\Mail\MailService;

// Класс для пометки заказов как готовых к самовывозу или отправленых
class FulfillOrderUseCase {
    // Приватное свойство (переменная класса), привязанная к объекту
    private OrderService $orderService;
    private MailService $mailService;

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(OrderService $orderService, MailService $mailService) {
        $this->orderService = $orderService;
        $this->mailService = $mailService;
    }

    // Метод для пометки заказа как отправленого курьером, объеденяет (координирует) методы двух других сервисов
    public function markShipped(int $orderId): void {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Помечаем сам заказ как отправленый
        $justMarked = $this->orderService->markShipped($orderId);

        // Если статус реально поменялся - отправляем письмо 
        if ($justMarked) {
            // TODO: сделать метод, добавить параметры, сделать нужный DI
            $this->mailService->sendOrderShipped();
        }
    }
    // Метод для пометки заказа как готового к получению, объеденяет (координирует) методы двух других сервисов
    public function markReadyForPickup(int $orderId): void {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Помечаем сам заказ как готовый к получению
        $justMarked = $this->orderService->markReadyForPickup($orderId);

        // Если статус реально поменялся - отправляем письмо 
        if ($justMarked) {
            // TODO: сделать метод, добавить параметры, сделать нужный DI
            $this->mailService->sendOrderReadyForPickup();
        }
    }
}