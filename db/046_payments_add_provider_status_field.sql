-- Вносит колонку provider_status в таблицу payments

ALTER TABLE payments
    ADD COLUMN provider_status VARCHAR(50) NULL AFTER provider;