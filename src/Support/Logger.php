<?php
// Логгер

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

    // Приватный метод для записи лога
    private function log(string $level, string $message, array $context): void {
        // Если уровень ниже минимально установленного - не логируем
        if (self::LEVELS[$level] <  $this->minLevel) return;

        // Подставляем контекст в сообщение
        $message = $this->interpolate($message, $context);

        // Собираем строку лога
        // sprintf собирает строку по шаблону из первого параметра
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),    // Верхний регистр
            $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );

        // Записывает в файл
        error_log($line, 3, $this->logFile);
    }

    // Функция для подстановки контекста в сообщение
    private function interpolate(string $message, array $context): string {
        $replace = [];

        foreach ($context as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $replace['{' . $key . '}'] = $value;
            }
        }

        // strtr заменяет подстроки replace в строке message
        return strtr($message, $replace);
    }
}
