-- Удаление устаревших полей в таблице orders

ALTER TABLE `orders`
    DROP INDEX `delivery_address_id`,
    DROP COLUMN `session_id`,
    DROP COLUMN `delivery_type`,
    DROP COLUMN `delivery_address_id`,
    DROP COLUMN `status`;