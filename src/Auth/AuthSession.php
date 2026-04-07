<?php
declare(strict_types=1);

// Адаптер над $_SESSION, взаимодействие с user id в сессии

namespace App\Auth;

class AuthSession {
    // Метод для входа в аккаунт (записи userId в аккаунт)
    public function login(int $userId): void {
        // Регенерируем id сессии (защита от фиксации - подмены session_id)
        session_regenerate_id(true);

        // Записываем userId в сессию
        $_SESSION['userId'] = $userId;
    }

    // Метод для выхода из аккаунта (удаление userId из сессии)
    public function logout(): void {
        // Очищаем сессию
        $_SESSION = [];

        // Смотрим, используется ли куки в сессии
        if (ini_get('session.use_cookies')) {
            // Если есть - получаем параметры
            $params = session_get_cookie_params();
        
            // "Просрачиваем" (удаляем) куки сессии
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }  

        // Уничтожаем сессию
        session_destroy();
    }

    // Метод для регенерирования id сессии (защита от фиксации - подмены session_id)
    public function regenerateId(): void {
        session_regenerate_id(true);
    }

    // Метод для получение текущего user id из сессии
    public function getUserId(): ?int {
        // Если в сессии есть userId и он валидный - возвращаем
        if (isset($_SESSION['userId']) && (int)$_SESSION['userId'] > 0) {
            return (int)$_SESSION['userId'];
        }
        
        // Иначе возвращаем null
        return null;
    }

    // Метод для проверки авторизации пользователя 
    public function isAuthenticated(): bool {
        return $this->getUserId() !== null;
    }
}
