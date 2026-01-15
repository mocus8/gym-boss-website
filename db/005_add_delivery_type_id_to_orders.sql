-- Вносит колонку delivery_type_id в таблицу orders и создает связи

ALTER TABLE orders
    ADD COLUMN delivery_type_id INT NULL AFTER delivery_type;

ALTER TABLE orders
    ADD CONSTRAINT fk_orders_delivery_type
        FOREIGN KEY (delivery_type_id)
        REFERENCES delivery_types(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT;
