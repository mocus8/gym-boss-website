-- Добавляет CURRENT_TIMESTAMP как дефлот для поля attempted_at в таблице login_attempts

ALTER TABLE `login_attempts` MODIFY `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;