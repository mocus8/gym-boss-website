-- Добавляет поля error_code и error_message

ALTER TABLE payments
    ADD COLUMN error_code VARCHAR(64) NULL DEFAULT NULL,
    ADD COLUMN error_message VARCHAR(1024) NULL DEFAULT NULL;