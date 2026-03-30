<?php
require_once __DIR__ . '/bootstrap.php';

// Чистим сессию
$_SESSION = [];
session_destroy();

header("Location: /");