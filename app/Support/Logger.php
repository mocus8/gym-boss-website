<?php
declare(strict_types=1);

namespace App\Support;

class Logger {
    // Уровни в порядке возрастания серьёзности
    private const LEVELS = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
    private string $logFile;    // файл для логирования
    private int $minLevel;    // уровень, начиная с которого логи будут создаваться

    public function __construct(string $logFile, string $minLevel = 'debug') {
        $this->logFile = $logFile;
        $this->minLevel = self::LEVELS[$minLevel] ?? 0;

        // Создаем директорию для файла логов если ее еще нет
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    // Обертка на log, задает уровень "debug"
    public function debug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }

    // Обертка на log, задает уровень "info"
    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }

    // Обертка на log, задает уровень "warning"
    public function warning(string $message, array $context = []): void {
        $this->log('warning', $message, $context);
    }

    // Обертка на log, задает уровень "error"
    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }

    // Метод для записи лога
    public function log(string $level, string $message, array $context): void {
        // Если уровень ниже минимально установленного - не логируем
        if (self::LEVELS[$level] <  $this->minLevel) return;

        // Подставляем контекст в сообщение
        $message = $this->interpolateMessage($message, $context);

        // Нормализуем контекст
        $context = $this->normalizeContext($context);

        // Собираем строку лога
        // sprintf собирает строку по шаблону из первого параметра
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),    // Верхний регистр
            $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );

        // Записывает в файл
        error_log($line, 3, $this->logFile);
    }

    // Метод для подстановки контекста в сообщение
    private function interpolateMessage(string $message, array $context): string {
        $replace = [];

        foreach ($context as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $replace['{' . $key . '}'] = $value;
            }
        }

        // strtr заменяет подстроки replace в строке message
        return strtr($message, $replace);
    }

    // Метод для нормализации контекста, если там \Throwable то он раскладывается
    private function normalizeContext(array $context): array {
        foreach ($context as $key => $value) {
            if ($value instanceof \Throwable) {
                $context[$key] = $this->normalizeThrowable($value);
            }
        }
    
        return $context;
    }

    // Функция для раскладования Throwable исключения на составляющие (в том числе previous ошибки)
    private function normalizeThrowable(\Throwable $e): array {
        $result = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'previous' => [],
        ];

        // Берем предыдущие исключение
        $previous = $e->getPrevious();

        // Проходимся по всем предыдущим исключениям
        while ($previous !== null) {
            $result['previous'][] = [
                'class' => $previous::class,
                'message' => $previous->getMessage(),
                'code' => $previous->getCode(),
                'file' => $previous->getFile(),
                'line' => $previous->getLine(),
                'trace' => $previous->getTraceAsString(),
            ];

            // Берем еще одно предыдущие исключение у предыдущего
            $previous = $previous->getPrevious();
        }

        return $result;
    }
}
