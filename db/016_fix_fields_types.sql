-- Небольшие правки типов полей

ALTER TABLE products
    MODIFY COLUMN price DECIMAL(10,2) NOT NULL;

ALTER TABLE users
    MODIFY COLUMN name VARCHAR(255) NOT NULL;

ALTER TABLE cart_items
    MODIFY COLUMN amount INT UNSIGNED NOT NULL;