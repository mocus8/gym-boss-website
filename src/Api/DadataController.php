<?php
// Контроллер для управления подсказками (API)
// Принимает запросы, взаимодействует с сервисом dadata через методы внутреннего клиента и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/order/cancel и т.д.).

// Тут добавить логирование и документацию для этого api

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Integrations\Dadata\DadataClient;   // используем класс DadataClient из пространства имен App\Integrations\Dadata
// use App\Support\Logger;    // пространство имен для логгера, на будующее

// Класс для получения подсказок (через методы клиента)
class DadataController {
    // Приватные свойства (переменные класса), привязанные к объекту
    private DadataClient $dadataClient;    
    // private Logger $logger;    // Логгер для передачи в зависимость в конструкторе, потом подключить

    // Конструктор (магический метод), присваиваем внеший экземпляр DadataClient в переменные создоваемого объекта
    public function __construct(DadataClient $dadataClient) {
        $this->dadataClient = $dadataClient;
    }

    // Будующий конструктор (с логером)
    // public function __construct(DadataClient $dadataClient, Logger $logger) {
    //     $this->dadataClient = $dadataClient;
    //     $this->logger = $logger;
    // }

    // Метод для получения DaData подсказок по адресу
    // Обработчик запроса POST /api/dadata/suggest/address
    public function suggestAddress(): void {
        try {
            // Получаем json тело запроса и декодируем его через приватный метод
            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            // Разбираем поля из массива полученных данных data
            $query = isset($data['query']) ? trim((string) $data['query']) : '';
            $count = isset($data['count']) ? (int) $data['count'] : 5;

            if ($query === '') {
                $this->error(422, 'VALIDATION_ERROR', 'Empty query');
                return;
            }

            if ($count < 1 || $count > 20) {
                $this->error(422, 'VALIDATION_ERROR', 'Invalid count');
                return;
            }

            $response = $this->dadataClient->suggestAddress($query, $count);
            $suggestions = $response['suggestions'] ?? [];

            // Возвращаем успех через приватную функцию
            $this->success(200, ['suggestions' => $suggestions,]);

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (\RuntimeException $e) {
            // Ошибка DaData
            $this->error(502, 'DADATA_ERROR', $e->getMessage());

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart getCart failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }

    // Приватный метод для получения, декодирования и проверки json входных данных
    private function getJsonBody(): ?array {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
    
        if (!is_array($data)) {
            $this->error(400, 'INVALID_REQUEST', 'Invalid JSON body');
            return null;
        }
    
        return $data;
    }

    // Приватная функция для отправки успеха
    private function success(int $status = 200, array $data = []): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
    }

    // Приватная функция для отправки ошибки
    // Возможно логгер сюда переместить
    private function error(
        int $status = 500,
        string $code = 'INTERNAL_SERVER_ERROR',
        string $message = 'Internal server error'
    ): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }
}