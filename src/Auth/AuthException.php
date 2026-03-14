<?php
// Класс-исключение
// Обертка над базовым Exception с дополнительным полем errorCode

namespace App\Auth;

// Наследование от базового Exception
class AuthException extends \Exception {
    private string $errorCode;
    private ?int $retryAfter = null;

    public function __construct(string $errorCode, string $message = '', ?int $retryAfter = null, \Throwable $previous = null) {
        $this->errorCode = $errorCode;
        $this->retryAfter = $retryAfter;
        // Вызываем конструктор базового класса
        parent::__construct($message ?: $errorCode, 0, $previous);
    }

    // Геттер для получения кода ошибки
    public function getErrorCode(): string {
        return $this->errorCode;
    }

    // Геттер для получения время кулдауна
    public function getRetryAfter(): ?int {
        return $this->retryAfter;
    }
}
