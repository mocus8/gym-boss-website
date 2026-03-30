-- Добавляет поля product_name и vat_code в таблице order_items

ALTER TABLE order_items
    ADD COLUMN product_name VARCHAR(255) NOT NULL AFTER product_id,
    ADD COLUMN vat_code TINYINT NOT NULL AFTER price;