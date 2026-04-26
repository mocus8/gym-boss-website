-- Меняет местами поля в order_items

ALTER TABLE order_items
  MODIFY COLUMN product_id int NOT NULL AFTER order_id;