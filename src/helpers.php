<?php
require_once __DIR__ . '/envLoader.php';


define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER')); 
define('DB_PASS', getenv('DB_PASS'));

function getDB() {
    return mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
}

function getCartSessionId() {
    if (!empty($_COOKIE['cart_session'])) {
        return $_COOKIE['cart_session'];
    }
    
    $sessionId = uniqid('cart_', true);
    setcookie('cart_session', $sessionId, time() + 86400 * 30, '/');
    return $sessionId;
}