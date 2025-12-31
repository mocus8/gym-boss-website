<?php
// Храним корзину неавторизированного пользователя в его куках

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Cart;

class CartSession {
    public function getId(): string { // :string - тип возвращаемого значения
        // Если уже есть - просто возвращаем
        if (!empty($_COOKIE['cart_session_id '])) {
            return $_COOKIE['cart_session_id '];
        }
        
        // Генерируем надёжный случайный идентификатор
        $id = bin2hex(random_bytes(16));

        //Устанавливаем куку по новому, расширенному синтаксису (PHP 7.3+)
        setcookie('cart_session', $id, [
            'expires'  => time() + 86400 * 30,
            'path'     => '/',
            'domain'   => '',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Возвращаем
        return $id;
    }
}
