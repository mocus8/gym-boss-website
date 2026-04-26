-- Добавляет и заполняет поле total_qty в таблицу orders

ALTER TABLE orders
ADD COLUMN total_qty INT NOT NULL DEFAULT 0
AFTER session_id;

UPDATE orders o
LEFT JOIN (
    SELECT po.order_id, SUM(po.amount) AS total_qty
    FROM product_order po
    GROUP BY po.order_id
) t ON t.order_id = o.order_id
SET o.total_qty = COALESCE(t.total_qty, 0);