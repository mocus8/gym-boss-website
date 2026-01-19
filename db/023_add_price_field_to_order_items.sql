-- Добавляет поле price в таблицу order_items - цена на момент оформления заказа

ALTER TABLE order_items
    ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00;

UPDATE order_items oi
JOIN products p ON p.product_id = oi.product_id
SET oi.price = p.price;

ALTER TABLE order_items
    MODIFY COLUMN price DECIMAL(10,2) NOT NULL;