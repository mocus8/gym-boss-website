-- Добавлено каскадное удаление товаров в заказах пользователей (удален пользователь - удалены товары в заказе)

ALTER TABLE order_items
  DROP FOREIGN KEY `order_items_order_fk`;

ALTER TABLE order_items
  ADD CONSTRAINT `order_items_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT;