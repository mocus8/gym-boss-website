<?php
// Контроллер для обработки уведолений от юкассы
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты

// Тут добавить логирование и документацию для этого api

// Настраиваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Payment\WebhookService;    // используем класс StoreService из пространства имен App\Store
// use App\Support\Logger;    // пространство имен для логгера, на будующее

class WebhookController {
    private WebhookService $webhookService;    // приватное свойство (переменная класса), привязанная к объекту
    // private Logger $logger;    // Логгер для передачи в зависимость в конструкторе, потом подключить

    // Конструктор (магический метод), присваиваем внеший экземпляр StoreService в переменные создоваемого объекта
    public function __construct(WebhookService $webhookService) {
        $this->webhookService = $webhookService;
    }

    // Будущий конструктор (с логером)
    // public function __construct(WebhookService $webhookService, Logger $logger) {
    //     $this->webhookService = $webhookService;
    //     $this->logger = $logger;
    // }

    // Приватный метод для получения, декодирования и проверки json входных данных
    private function getJsonBody(): ?array {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
    
        if (!is_array($data)) {
            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Store getAll  failed', [
            //     'exception' => $e,
            // ]);

            // Все равно возвращаем 200
            http_response_code(200);
        }
    
        return $data;
    }

    // Метод для обработки уведомления от юкассы 
    // Обработчик запроса POST /webhook/yookassa от юкассы
    public function handleNotification(): void {
        try {
            // Получаем json тело запроса и декодируем его через приватный метод
            $payload = $this->getJsonBody();
            if ($payload === null) {
                return;
            }

            $this->webhookService->handleNotification($payload);

            // Возвращаем успех (статус 200)
            http_response_code(200);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Store getAll  failed', [
            //     'exception' => $e,
            // ]);

            // Все равно возвращаем 200
            http_response_code(200);
        }
    }
}