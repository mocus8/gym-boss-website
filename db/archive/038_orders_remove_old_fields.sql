-- Удаление устаревших полей в таблице orders (перенесены в payments)

ALTER TABLE `orders`
    DROP COLUMN `yookassa_payment_id`,
    DROP COLUMN `payment_expires_at`;