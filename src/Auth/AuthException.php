<?php
// Класс-исключение
// Обертка над базовым Exception с дополнительным полем errorCode

namespace App\Auth;

// Наследование от базового Exception
class AuthException extends \Exception {
    private string $errorCode;

    public function __construct(string $errorCode, string $message = '', int $code = 0, \Throwable $previous = null) {
        $this->errorCode = $errorCode;
        // Вызывается конструктор базового класса
        parent::__construct($message ?: $errorCode, $code, $previous);
    }

    // Геттер для получения кода ошибки
    public function getErrorCode(): string {
        return $this->errorCode;
    }
}
