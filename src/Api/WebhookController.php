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

    // Константа с разрешенными CIDR-диапазонами для приема уведомлений
    private const ALLOWED_CIDRS = [
        '185.71.76.0/27',
        '185.71.77.0/27',
        '77.75.153.0/25',
        '77.75.154.128/25',
        '77.75.156.11',
        '77.75.156.35',
        '2a02:5180::/32',
    ];

    // Конструктор (магический метод), присваиваем внеший экземпляр StoreService в переменные создоваемого объекта
    public function __construct(WebhookService $webhookService) {
        $this->webhookService = $webhookService;
    }

    // Будущий конструктор (с логером)
    // public function __construct(WebhookService $webhookService, Logger $logger) {
    //     $this->webhookService = $webhookService;
    //     $this->logger = $logger;
    // }

    // Приватный метод для проверки принадлежности ip к CIDR-диапозону
    private function ipInCidr(string $ip, string $cidr): bool {
        // Если это IPv6 (есть двоеточие) - пропускаем
        if (str_contains($cidr, ':') || str_contains($ip, ':')) {
            return false;
        }

        // Одиночный IP без "/"
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $maskBits] = explode('/', $cidr, 2);

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskBits = (int) $maskBits;
        $mask     = -1 << (32 - $maskBits); // битовая маска для IPv4

        $ipNet     = $ipLong & $mask;
        $subnetNet = $subnetLong & $mask;

        return $ipNet === $subnetNet;
    }

    // Приватный метод для проверки адреса поступающего уведомления
    private function isIpAllowed(string $ip): bool {
        foreach (self::ALLOWED_CIDRS as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) return true;
        }

        return false;
    }

    // Приватный метод для получения, декодирования и проверки json входных данных
    private function getJsonBody(): ?array {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
    
        if (!is_array($data)) {
            return null;
        }
    
        return $data;
    }

    // Метод для обработки уведомления от юкассы 
    // Обработчик запроса POST /webhook/yookassa от юкассы
    public function handleNotification(): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->isIpAllowed($ip)) {
            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Notification from uknown ip', [
            //     'ip' => $ip,
            // ]);

            http_response_code(403);
            return;
        }

        // Получаем json тело запроса и декодируем его через приватный метод
        $payload = $this->getJsonBody();
        if ($payload === null) {
            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Store getAll  failed', [
            //     'exception' => $e,
            // ]);

            http_response_code(200);
            return;
        }

        try {
            // Синхронизируем статус заказа через метод контроллера
            $this->webhookService->handleNotification($payload);
        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Store getAll  failed', [
            //     'exception' => $e,
            // ]);
        } finally {
            // В любом случае возвращаем успех (статус 200)
            http_response_code(200);
        }
    }
}