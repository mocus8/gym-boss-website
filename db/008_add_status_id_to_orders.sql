-- Вносит колонку status_id в таблицу orders и создает связи

ALTER TABLE orders
    ADD COLUMN status_id INT NOT NULL DEFAULT 1 AFTER status;

ALTER TABLE orders
    ADD CONSTRAINT fk_orders_status
        FOREIGN KEY (status_id)
        REFERENCES order_statuses(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT;
