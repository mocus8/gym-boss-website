<?php
// Контроллер для управления подсказками (API)
// Принимает запросы, взаимодействует с сервисом dadata через методы внутреннего клиента и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/order/cancel и т.д.).

namespace App\Api;

use App\Integrations\Dadata\DadataClient;
use App\Support\Logger;

// Класс для получения подсказок (через методы клиента)
class DadataController extends BaseController {
    private DadataClient $dadataClient;

    // Конструктор (магический метод), присваиваем внеший экземпляр DadataClient в переменные создоваемого объекта
    public function __construct(DadataClient $dadataClient, Logger $logger) {
        $this->dadataClient = $dadataClient;
        parent::__construct($logger);
    }

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
                $this->error(
                    422,
                    'VALIDATION_ERROR',
                    'Empty query'
                );

                return;
            }

            if ($count < 1 || $count > 20) {
                $this->error(
                    422,
                    'VALIDATION_ERROR',
                    'Invalid count'
                );

                return;
            }

            $response = $this->dadataClient->suggestAddress($query, $count);
            $suggestions = $response['suggestions'] ?? [];

            // Возвращаем успех через приватную функцию
            $this->success(200, ['suggestions' => $suggestions,]);

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(
                422,
                'VALIDATION_ERROR',
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

        } catch (\RuntimeException $e) {
            // Ошибка DaData
            $this->error(
                502,
                'DADATA_ERROR',
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to suggest address',
                context: [
                    'exception' => $e,
                ]
            );
        }
    }
}