<?php
require_once __DIR__ . '/../bootstrap.php';

function verifyRecaptcha($token) {
    $secret = $servicesConfig['recaptcha']['secret_key'] ?? "";
    if ($secret === "") return;

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = ['secret' => $secret, 'response' => $token];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);
    
    return $response->success && $response->score >= 0.5;
}

function requireCaptcha() {
    if (!verifyRecaptcha($_POST['recaptcha_response'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'recaptcha_false'
        ]);
        exit;
    }
}
?>