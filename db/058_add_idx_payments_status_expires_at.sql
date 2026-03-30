-- Добавляет индекс для поиска платежей по expires at (для крон задачи, отменяющей просроченные платежи)

CREATE INDEX `idx_payments_status_expires_at` ON `payments` (`status`, `expires_at`);