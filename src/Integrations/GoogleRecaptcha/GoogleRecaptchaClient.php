<?php
// Класс для взаимодействия с GoogleRecaptcha

namespace App\Integrations\GoogleRecaptcha;

use App\Support\Logger;

class GoogleRecaptchaClient  {
    private string $secretKey;
    private Logger $logger;
    private const GOOGLE_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';
    private const MIN_SCORE = 0.5;

    public function __construct(string $secretKey, Logger $logger) {
        $this->secretKey = $secretKey;
        $this->logger = $logger;
    }

    public function validate(string $token, ?string $expectedAction = null): bool {
        $token = trim($token);

        if ($token === '') {
            $this->logger->warning('Empty recaptcha token while {action}', [
                'action' => $expectedAction
            ]);

            return false;
        }

        $postData = [
            'secret'   => $this->secretKey,
            'response' => $token,
        ];

        // Запрос к Google
        $ch = curl_init(self::GOOGLE_ENDPOINT);
        if ($ch === false) {
            $this->logger->warning('Failed to initialized cURL while recaptcha token validation for {action}', [
                'action' => $expectedAction
            ]);

            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_TIMEOUT => 5,
        ]);

        $responseBody = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlErrNo !== 0 || $responseBody === false) {
            $this->logger->warning('Request to Google reCAPTCHA failed {curl_error} while recaptcha token validation for {action}', [
                'curl_error' => $curlError ?: 'unknown error',
                'action' => $expectedAction
            ]);

            return false;
        }

        $data = json_decode($responseBody, true);
        if (!is_array($data)) {
            $this->logger->warning('Invalid response from google while recaptcha token validation for {action}', [
                'action' => $expectedAction
            ]);

            return false;
        }

        $success = (bool)($data['success'] ?? false);
        $score = isset($data['score']) ? (float)$data['score'] : null;
        $action = isset($data['action']) ? (string)$data['action'] : null;
        $hostname = isset($data['hostname']) ? (string)$data['hostname'] : null;

        $errorCodes = [];
        if (isset($data['error-codes']) && is_array($data['error-codes'])) {
            $errorCodes = array_map('strval', $data['error-codes']);
        }

        // Базовая проверка success
        if (!$success) {
            $this->logger->warning('reCAPTCHA validation failed for action {action}', [
                'action' => $expectedAction,
                'score' => $score,
                'hostname' => $hostname,
                'errorCodes'  => $errorCodes,
            ]);

            return false;
        }

        // Проверяем score относительно заданного минимально проходного
        if ($score === null || $score < self::MIN_SCORE) {
            $this->logger->warning('Score {score} not enough while recaptcha token validation for {action}', [
                'score' => $score,
                'action' => $expectedAction,
                'hostname' => $hostname,
                'errorCodes' => $errorCodes,
            ]);

            return false;
        }

        // Проверка ожидаемого action, если передан
        if ($expectedAction !== null && $action !== $expectedAction) {
            $this->logger->warning('Unexpected action {action} while recaptcha token validation for {expected_action}', [
                'action' => (string)$action,
                'expected_action' => $expectedAction,
                'score' => $score,
                'hostname' => $hostname,
                'errorCodes' => $errorCodes,
            ]);

            return false;
        }

        $this->logger->info('reCAPTCHA success validation for action {action}', [
            'action' => (string)$action,
            'expected_action' => $expectedAction,
            'score' => $score,
            'hostname' => $hostname
        ]);

        return true;
    }
}