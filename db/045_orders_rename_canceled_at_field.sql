-- Переименовывает столбец cancelled_at в canceled_at

ALTER TABLE orders
  CHANGE COLUMN cancelled_at canceled_at TIMESTAMP NULL;