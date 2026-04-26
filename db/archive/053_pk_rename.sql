-- Переименовывает первичные ключи и соответствующие связи для некоторых таблиц

ALTER TABLE `products` DROP FOREIGN KEY `products_ibfk_1`;

ALTER TABLE `categories` CHANGE `category_id` `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `products` ADD CONSTRAINT `fk_products_categories`
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
  ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `cart_items` DROP FOREIGN KEY `cart_items_product_fk`;
ALTER TABLE `order_items` DROP FOREIGN KEY `order_items_product_fk`;
ALTER TABLE `product_images` DROP FOREIGN KEY `product_images_ibfk_1`;

ALTER TABLE `products` CHANGE `product_id` `id` int NOT NULL AUTO_INCREMENT;

-- При удалении товаров удаляются эти позиции в корзинах пользователей

ALTER TABLE `cart_items` ADD CONSTRAINT `fk_cart_items_products`
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `order_items` ADD CONSTRAINT `fk_order_items_products`
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
  ON DELETE RESTRICT ON UPDATE RESTRICT;

-- Добавляет каскадное удаление фотографий товаров при удалении самих товаров 

ALTER TABLE `product_images` ADD CONSTRAINT `fk_product_images_products`
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `order_items` DROP FOREIGN KEY `order_items_order_fk`;
ALTER TABLE `payments` DROP FOREIGN KEY `payments_order_fk`;

ALTER TABLE `orders` CHANGE `order_id` `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `order_items` ADD CONSTRAINT `fk_order_items_orders`
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

-- При удалении заказов платеди сохраняются как информация для отчетности

ALTER TABLE `payments` ADD CONSTRAINT `fk_payments_orders`
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
  ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `product_images` CHANGE `image_id` `id` int NOT NULL AUTO_INCREMENT;