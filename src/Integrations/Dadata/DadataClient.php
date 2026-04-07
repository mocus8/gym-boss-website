<?php
// Класс-клиент для формирования запроса, его отправки во внешний сервис DaData
// Запрос формируется из базовой ссылки, секретного ключ из конфига, query строки и count-кол-во подсказок из внешних параметров

namespace App\Integrations\Dadata;

class DadataClient {
    // Сыллка на внешний сервис подсказок
    private const BASE_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs';
    // Приватная переменная класса - ключ для DaData API из конфига
    private string $apiKey;

    // Конструктор (магический метод), присваиваем ключ из конфига в переменную объекта класса
    public function __construct(string $apiKey) {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            throw new \InvalidArgumentException('Dadata apiKey is empty');
        }

        $this->apiKey = trim($apiKey);
    }

    // Приватный вспомагательный низкоуровненый метод для отправки запроса и получения ответа 
    private function requestJson(string $path, array $payload): array {
        // Формируем полную ссылку для отправки запроса
        $url = self::BASE_URL . $path;

        // Инициализируем и проверяем локальную cURL‑сессию в PHP и привязываем к ней URL, проверяем что это удалось
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('cURL init failed');
        }

        // Кодируем тело (payload) будущего запроса и проверяем 
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('JSON encode failed');
        }

        // Настраиваем запрос
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Token ' . $this->apiKey,
            ],
            // Ставим таймауты (чтобы запросы не висели)
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
        ]);

        // Отправляем запрос, проверяем что ответ пришел
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Dadata request failed: ' . $err);
        }

        // Получаем код ответа и закрываем локальную cURL‑сессию в PHP 
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Проверяем код ответа
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Dadata bad status: ' . $httpCode);
        }

        // Пытаемся декодировать ответ
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Dadata invalid JSON response');
        }

        return $data;
    }

    // Метод отправки http запроса и возврата ответа
    public function suggestAddress(string $query, int $count = 5): array {
        $query = trim($query);

        if ($query === '') {
            throw new \InvalidArgumentException('Empty query');
        }

        if ($count < 1 || $count > 20) {
            throw new \InvalidArgumentException('Invalid count');
        }

        // Используем приватный вспомагательный метод для отправки запроса и получения ответа
        return $this->requestJson('/suggest/address', [
            'query' => $query,
            'count' => $count,
        ]);
    }
}