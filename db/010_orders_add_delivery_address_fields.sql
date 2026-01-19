-- Добавление в таблицу orders полей delivery_address_text и delivery_postal_code

ALTER TABLE orders
    ADD COLUMN delivery_address_text VARCHAR(255) NULL AFTER delivery_cost,
    ADD COLUMN delivery_postal_code VARCHAR(20)  NULL AFTER delivery_address_text;