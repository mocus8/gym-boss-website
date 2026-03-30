-- Добавлено каскадное удаление товаров в заказах пользователей для старой таблицы (удален пользователь - удалены товары в заказе)

ALTER TABLE product_order
  DROP FOREIGN KEY `product_order_ibfk_1`;

ALTER TABLE product_order
  ADD CONSTRAINT `product_order_ibfk_1`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT;