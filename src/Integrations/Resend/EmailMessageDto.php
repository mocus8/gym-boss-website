<?php
// DTO - data transfer object
// Это "контейнер данных" для передачи между слоями (контроллер -> сервис, сервис -> интеграция, сервис -> фронт)
// Каждый DTO создается под конкретный контракт передаваемых данных (под конкретный use-case)
// Этот DTO-класс создан для передачи объекта с информацией о email для его отправки через sdk resend-а

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Integrations\Resend;

// Final - нельзя наследовать класс, нужно для сохранения правильного контракта
// Readonly - класс становится неизменяемым, чтобы сохранить исходный правильный контракт
final readonly class EmailMessageDto { 
    // Публичные поля DTO-класса - набор данных этого DTO
    public string|array $to;    // получатель/получатели
    public string $subject;    // тема письма
    public ?string $html;    // html-версия письма
    public ?string $text;    // text-версия письма
    public ?string $replyTo;    // адрес для ответа

    public function __construct(
        string|array $to,
        string $subject,
        ?string $html = null,
        ?string $text = null,
        ?string $replyTo = null
    ) {
        $this->to = $to;
        $this->subject = $subject;
        $this->html = $html;
        $this->text = $text;
        $this->replyTo = $replyTo;
    }
}