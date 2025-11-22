<?php
session_start();

require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $connect = getDB();

    if (!$connect) {
        throw new Exception('db connection error');
    }

    if (!isset($data["login"]) || empty(trim($data["login"]))) {
        echo json_encode([
            'success' => false,
            'message' => 'login_required'
        ]);
        exit;
    }

    $login = trim($data["login"]);

    $check = $connect->prepare("SELECT id FROM users WHERE login = ?");

    if (!$check) {
        throw new Exception('prepare statement failed');
    }

    $check->bind_param("s", $login);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => true
        ]);
    } else {
        echo json_encode([
            'success' => false
        ]);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'server_error'
    ]);
} finally {
    if (isset($check)) {
        $check->close();
    }
    
    if (isset($connect)) {
        $connect->close();
    }
}
?>


