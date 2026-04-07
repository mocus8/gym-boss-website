<?php
// DTO - data transfer object
// Это "контейнер данных" для передачи между слоями (контроллер -> сервис, сервис -> интеграция, сервис -> фронт)
// Каждый DTO создается под конкретный контракт передаваемых данных (под конкретный use-case)
// Этот DTO-класс создан для передачи объекта с некоторой информацией о платеже при его создании в юкассе

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Integrations\Yookassa;

// Final - нельзя наследовать класс, нужно для сохранения правильного контракта
// Readonly - класс становится неизменяемым, чтобы сохранить исходный правильный контракт
final readonly class CreatedPaymentDto { 
    // Публичные поля DTO-класса - набор данных этого DTO
    public string $paymentId;
    public string $confirmationUrl;
    public ?string $expiresAt;    // Поле expiresAt может быть null

    public function __construct(string $paymentId, string $confirmationUrl, ?string $expiresAt = null) {
        $this->paymentId = $paymentId;
        $this->confirmationUrl = $confirmationUrl;
        $this->expiresAt = $expiresAt;
    }
}