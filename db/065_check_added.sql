-- Добавляет check ограничения для значения полей таблиц

ALTER TABLE `cart_items` ADD CONSTRAINT `chk_cart_items_quantity_positive`
  CHECK (`quantity` > 0);

ALTER TABLE `order_items` ADD CONSTRAINT `chk_order_items_quantity_positive`
  CHECK (`quantity` > 0);

ALTER TABLE `order_items` ADD CONSTRAINT `chk_order_items_price_non_negative`
  CHECK (`price` >= 0);

ALTER TABLE `products` ADD CONSTRAINT `chk_products_price_positive`
  CHECK (`price` > 0);

ALTER TABLE `orders` ADD CONSTRAINT `chk_orders_total_price_non_negative`
  CHECK (`total_price` >= 0);

ALTER TABLE `orders` ADD CONSTRAINT `chk_orders_total_quantity_non_negative`
  CHECK (`total_quantity` >= 0);

ALTER TABLE `orders` ADD CONSTRAINT `chk_orders_delivery_cost_non_negative`
  CHECK (`delivery_cost` >= 0);

ALTER TABLE `payments` ADD CONSTRAINT `chk_payments_amount_positive`
  CHECK (`amount` > 0);

ALTER TABLE `stores` ADD CONSTRAINT `chk_stores_latitude`
  CHECK (`latitude` BETWEEN -90 AND 90);

ALTER TABLE `stores` ADD CONSTRAINT `chk_stores_longitude`
  CHECK (`longitude` BETWEEN -180 AND 180);