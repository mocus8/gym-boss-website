-- Перемещаем в конец поля created_at и updated_at

ALTER TABLE orders
    MODIFY COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER payment_expires_at,
    MODIFY COLUMN updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
