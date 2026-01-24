<?php
require_once __DIR__ . '/bootstrap.php';
unset($_SESSION['user']);
header("Location: /");