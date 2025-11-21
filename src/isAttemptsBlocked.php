<?php
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['sms_blocked_until']) && $_SESSION['sms_blocked_until'] > time()) {
    echo json_encode([
        'success' => false,
        'error' => 'blocked',
        'blocked_until' => $_SESSION['sms_blocked_until']
    ]);
} else {
    if (isset($_SESSION['sms_blocked_until'])) {
        unset($_SESSION['sms_blocked_until']);
    }
    
    echo json_encode([
        'success' => true
    ]);
}
?>