<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/smscFunctions.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
// тут нужно обрабатывать входящий телефон, сейчас этого нет
$phone = $input['phone'] ?? '';

if (empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Введите номер телефона']);
    exit;
}

$result = send_sms_verification($phone);
echo json_encode($result);
?>