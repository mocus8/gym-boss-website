-- Замена двух индексов idx_login_attempts_email и idx_login_attempts_attempted_at на составной
-- Ускорит типичный для проверки брут форса запрос 

DROP INDEX `idx_login_attempts_email` ON `login_attempts`;
DROP INDEX `idx_login_attempts_attempted_at` ON `login_attempts`;
CREATE INDEX `idx_login_attempts_email_attempted_at` ON `login_attempts` (`email`, `attempted_at`);