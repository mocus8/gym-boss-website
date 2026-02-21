-- Добавляет поле last_sync_at

ALTER TABLE payments
    ADD COLUMN last_sync_at DATETIME AFTER updated_at;