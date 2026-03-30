-- Переименовывает названия зависимостей в единый формат

ALTER TABLE `cart_items` DROP FOREIGN KEY `cart_items_cart_fk`;
ALTER TABLE `cart_items` ADD CONSTRAINT `fk_cart_items_carts`
  FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `carts` DROP FOREIGN KEY `carts_user_fk`;
ALTER TABLE `carts` ADD CONSTRAINT `fk_carts_users`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `email_verification_tokens` DROP FOREIGN KEY `email_verification_tokens_user_fk`;
ALTER TABLE `email_verification_tokens` ADD CONSTRAINT `fk_email_verification_tokens_users`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `orders` DROP FOREIGN KEY `orders_ibfk_1`;
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_stores`
  FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
  ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `orders` DROP FOREIGN KEY `fk_orders_delivery_type`;
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_delivery_types`
  FOREIGN KEY (`delivery_type_id`) REFERENCES `delivery_types` (`id`)
  ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `orders` DROP FOREIGN KEY `fk_orders_status`;
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_order_statuses`
  FOREIGN KEY (`status_id`) REFERENCES `order_statuses` (`id`)
  ON DELETE RESTRICT ON UPDATE RESTRICT;

-- Убирает каскадное удаление заказов при удалении пользователя, они сохраняются для отчетности

ALTER TABLE `orders` DROP FOREIGN KEY `orders_user_fk`;
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_users`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `password_reset_tokens` DROP FOREIGN KEY `password_reset_tokens_user_fk`;
ALTER TABLE `password_reset_tokens` ADD CONSTRAINT `fk_password_reset_tokens_users`
  FOREIGN KEY (`email`) REFERENCES `users` (`email`)
  ON DELETE CASCADE ON UPDATE CASCADE;