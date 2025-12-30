<?php

function getCartSessionId() {
    if (!empty($_COOKIE['cart_session'])) {
        return $_COOKIE['cart_session'];
    }
    
    $sessionId = uniqid('cart_', true);
    setcookie('cart_session', $sessionId, time() + 86400 * 30, '/');
    return $sessionId;
}