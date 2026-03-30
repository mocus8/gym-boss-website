-- Переименовывает некоторые поля в таблицах для ясности, для кол-ва товаров тип сделан unsigned (неотрицательные числа)

ALTER TABLE `cart_items` CHANGE `amount` `quantity` int unsigned NOT NULL;
ALTER TABLE `order_items` CHANGE `amount` `quantity` int unsigned NOT NULL;
ALTER TABLE `orders` CHANGE `total_qty` `total_quantity` int NOT NULL DEFAULT 0;