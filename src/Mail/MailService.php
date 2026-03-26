<?php
// Класс для отправки писем, для этого он использует методы gateway-я
// Не имеет своего контроллера, вызывается из разных сервисов

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Mail;
use App\Integrations\Resend\EmailMessageDto;
use App\Integrations\Resend\ResendGateway;

class MailService {
    private ResendGateway $resendGateway;    // экземпляр ResendGateway для взаимодействия с sdk resend-а
    private const SUPPORT_EMAIL = 'mocus8@gmail.com';

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(ResendGateway $resendGateway) {
        $this->resendGateway = $resendGateway;
    }

    // Метод для отправки письма с ссылкой для верификации почты
    public function sendEmailVerificationLink(string $userEmail, string $userName, string $verifyUrl): void {
        if (trim($userEmail) === '' || trim($userName) === '' || trim($verifyUrl) === '') {
            throw new \InvalidArgumentException('Empty userEmail, userName or verifyUrl');
        }

        // Задаем тему письма
        $subject = 'Подтвердите ваш email для GymBoss';

        // Задаем html-версию письма
        $html = $this->renderTemplate(
            __DIR__ . '/../templates/email/verification.html.php', 
            [
                'userName' => $userName,
                'verifyUrl' => $verifyUrl,
            ]
        );

        // Задаем text-версию письма
        $text = $this->renderTemplate(
            __DIR__ . '/../templates/email/verification.text.php', 
            ['userName' => $userName, 'verifyUrl' => $verifyUrl]
        );

        // Оформляем инфу о письме в DTO
        $message = new EmailMessageDto($userEmail, $subject, $html, $text);

        // Через метод gateway-я пробуем отправить письмо
        try {
            $this->resendGateway->send($message);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Cannot send verification email', 0, $e);
        }
    }

    // Метод для отправки письма с ссылкой для сброса пароля
    public function sendPasswordResetLink(string $userEmail, string $userName, string $resetUrl): void {
        if (trim($userEmail) === '' || trim($userName) === '' || trim($resetUrl) === '') {
            throw new \InvalidArgumentException('Empty userEmail, userName or resetUrl');
        }

        // Задаем тему письма
        $subject = 'Сброс пароля в GymBoss';

        // Задаем html-версию письма
        $html = $this->renderTemplate(
            __DIR__ . '/../templates/email/reset-password.html.php', 
            [
                'userName' => $userName,
                'resetUrl' => $resetUrl,
            ]
        );

        // Задаем text-версию письма
        $text = $this->renderTemplate(
            __DIR__ . '/../templates/email/reset-password.text.php', 
            ['userName' => $userName, 'resetUrl' => $resetUrl]
        );

        // Оформляем инфу о письме в DTO
        $message = new EmailMessageDto($userEmail, $subject, $html, $text);

        // Через метод gateway-я пробуем отправить письмо
        try {
            $this->resendGateway->send($message);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Cannot send password reset email', 0, $e);
        }
    }

    // Метод для отправки письма с подтверждением об оплате
    public function sendOrderConfirmation(array $orderInfo, array $orderItems, string $orderUrl): void {
        if (empty($orderInfo) || empty($orderItems) || empty($orderUrl)) {
            throw new \InvalidArgumentException('Missing required data for order confirmation email');
        }

        // Задаем тему письма
        $subject = "Ваш заказ №{$orderInfo['order_id']} оплачен";

        // Собираем массив инфы для рендера шаблона письма
        $templateData = [
            'userName' => $orderInfo['user_name'],
            'orderId' => $orderInfo['order_id'],
            'items' => $orderItems,
            'itemsPrice' => $orderInfo['total_price'],
            'deliveryTypeCode' => $orderInfo['delivery_type_code'],
            'deliveryCost' => $orderInfo['delivery_cost'],
            'totalPrice' => (float)$orderInfo['total_price'] + (float)$orderInfo['delivery_cost'],
            'deliveryTypeName'  => $orderInfo['delivery_type_name'],

            'deliveryAddressText'  => $orderInfo['delivery_address_text'],
            'courierDeliveryFrom'  => formatDateForEmail($orderInfo['courier_delivery_from']),
            'courierDeliveryTo'  => formatDateForEmail($orderInfo['courier_delivery_to']),

            'storeAddress'  => $orderInfo['store_address'],
            'readyForPickupFrom'  => formatDateForEmail($orderInfo['ready_for_pickup_from']),
            'readyForPickupTo'  => formatDateForEmail($orderInfo['ready_for_pickup_to']),

            'orderUrl' => $orderUrl
        ];

        // Задаем html-версию письма
        $html = $this->renderTemplate(
            __DIR__ . '/../templates/email/order-confirmation.html.php',
            $templateData
        );

        // Задаем text-версию письма
        $text = $this->renderTemplate(
            __DIR__ . '/../templates/email/order-confirmation.text.php',
            $templateData
        );

        // Оформляем инфу о письме в DTO
        $message = new EmailMessageDto($orderInfo['user_email'], $subject, $html, $text);

        // Через метод gateway-я пробуем отправить письмо
        try {
            $this->resendGateway->send($message);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Cannot send order confirmation email', 0, $e);
        }
    }

    // Метод для отправки письма об отмене заказа
    public function sendOrderCanceled(array $orderInfo, array $orderItems, string $canceledBy, string $orderUrl): void {
        if (empty($orderInfo) || empty($orderItems) || empty($canceledBy) || empty($orderUrl)) {
            throw new \InvalidArgumentException('Missing required data for order canceled email');
        }

        // Задаем тему письма
        $subject = "Ваш заказ №{$orderInfo['order_id']} отменен";

        // Собираем массив инфы для рендера шаблона письма
        $templateData = [
            'userName' => $orderInfo['user_name'],
            'orderId' => $orderInfo['order_id'],
            'items' => $orderItems,
            'itemsPrice' => $orderInfo['total_price'],
            'canceledBy' => $canceledBy,
            'orderUrl' => $orderUrl
        ];

        // Задаем html-версию письма
        $html = $this->renderTemplate(
            __DIR__ . '/../templates/email/order-canceled.html.php',
            $templateData
        );

        // Задаем text-версию письма
        $text = $this->renderTemplate(
            __DIR__ . '/../templates/email/order-canceled.text.php',
            $templateData
        );

        // Оформляем инфу о письме в DTO
        $message = new EmailMessageDto($orderInfo['user_email'], $subject, $html, $text);

        // Через метод gateway-я пробуем отправить письмо
        try {
            $this->resendGateway->send($message);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Cannot send order canceled email', 0, $e);
        }
    }

    // Метод для пометки заказа как отправленного курьером
    public function sendOrderShipped(array $orderInfo, array $orderItems, string $orderUrl): void {
        if (empty($orderInfo) || empty($orderItems) || empty($orderUrl)) {
            throw new \InvalidArgumentException('Missing required data for order shipped email');
        }

        // Задаем тему письма
        $subject = "Заказ №{$orderInfo['order_id']} передан в доставку";

        // Собираем массив инфы для рендера шаблона письма
        $templateData = [
            'userName' => $orderInfo['user_name'],
            'orderId' => $orderInfo['order_id'],
            'items' => $orderItems,
            'deliveryAddressText'  => $orderInfo['delivery_address_text'],
            'courierDeliveryFrom'  => formatDateForEmail($orderInfo['courier_delivery_from']),
            'courierDeliveryTo'  => formatDateForEmail($orderInfo['courier_delivery_to']),
            'orderUrl' => $orderUrl,
            'supportEmail' => self::SUPPORT_EMAIL
        ];

        // Задаем html-версию письма
        $html = $this->renderTemplate(
            __DIR__ . '/../templates/email/order-shipped.html.php',
            $templateData
        );

        // Задаем text-версию письма
        $text = $this->renderTemplate(
            __DIR__ . '/../templates/email/order-shipped.text.php',
            $templateData
        );

        // Оформляем инфу о письме в DTO
        $message = new EmailMessageDto($orderInfo['user_email'], $subject, $html, $text);

        // Через метод gateway-я пробуем отправить письмо
        try {
            $this->resendGateway->send($message);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Cannot send order shipped email', 0, $e);
        }
    }

    // Метод для пометки заказа как готового к получению
    public function sendOrderReadyForPickup(array $orderInfo, array $orderItems, string $orderUrl): void {
        if (empty($orderInfo) || empty($orderItems) || empty($orderUrl)) {
            throw new \InvalidArgumentException('Missing required data for order ready for pickup email');
        }

        // Задаем тему письма
        $subject = "Заказ №{$orderInfo['order_id']} готов к получению";

        // Собираем массив инфы для рендера шаблона письма
        $templateData = [
            'userName' => $orderInfo['user_name'],
            'orderId' => $orderInfo['order_id'],
            'items' => $orderItems,
            'storeAddress'  => $orderInfo['store_address'],
            'storeWorkHours' => $orderInfo['store_work_hours'],
            'orderUrl' => $orderUrl,
            'supportEmail' => self::SUPPORT_EMAIL
        ];

        // Задаем html-версию письма
        $html = $this->renderTemplate(
            __DIR__ . '/../templates/email/order-ready-for-pickup.html.php',
            $templateData
        );

        // Задаем text-версию письма
        $text = $this->renderTemplate(
            __DIR__ . '/../templates/email/order-ready-for-pickup.text.php',
            $templateData
        );

        // Оформляем инфу о письме в DTO
        $message = new EmailMessageDto($orderInfo['user_email'], $subject, $html, $text);

        // Через метод gateway-я пробуем отправить письмо
        try {
            $this->resendGateway->send($message);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Cannot send order ready for pickup email', 0, $e);
        }
    }

    // Вспомогательный приватный метод для рендера шалона 
    // Подставляет переменные из data в шаблон и возвращает его в виде строки
    private function renderTemplate(string $templatePath, array $data = []): string {
        // Проверяем что файл шаблона существует
        if (!is_file($templatePath)) {
            throw new \RuntimeException('Template file not found: ' . $templatePath);
        }

        // Через буфер начинаем записывать контент шаблона
        ob_start();

        // Пробуем передать инфу из data в шаблон записать его в переменную
        try {
            // Достаем из ассоц. массива удобные локальные переменные
            // EXTR_SKIP - не перезаписываем уже заданные переменные
            extract($data, EXTR_SKIP);

            // Подключаем шаблон письма
            require $templatePath;

            // Записываем все из буфера в переменную и проверяем что удалось
            $output = ob_get_clean();
            if ($output === false) {
                throw new \RuntimeException('Failed to render template: ' . $templatePath);
            }

            return $output;
        } catch (\Throwable $e) {
            // Если какая-либо ошибка - очищаем буфер и пробрасываем дальше
            ob_end_clean();
            throw $e;
        }
    }
}