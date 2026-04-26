-- Переименовывает все индексы в единый формат

ALTER TABLE `cart_items` DROP INDEX `product_id`;
CREATE INDEX `idx_cart_items_product_id` ON `cart_items` (`product_id`);

ALTER TABLE `carts` DROP INDEX `user_id`;
CREATE INDEX `idx_carts_user_id` ON `carts` (`user_id`);

ALTER TABLE `carts` DROP INDEX `session_id`;
CREATE INDEX `idx_carts_session_id` ON `carts` (`session_id`);

ALTER TABLE `login_attempts` DROP INDEX `login_attempts_email_IDX`;
CREATE INDEX `idx_login_attempts_email` ON `login_attempts` (`email`);
ALTER TABLE `login_attempts` DROP INDEX `login_attempts_attempted_at_IDX`;
CREATE INDEX `idx_login_attempts_attempted_at` ON `login_attempts` (`attempted_at`);

ALTER TABLE `order_items` DROP INDEX `order_id`;
CREATE INDEX `idx_order_items_order_id` ON `order_items` (`order_id`);

ALTER TABLE `orders` DROP INDEX `user_id`;
CREATE INDEX `idx_orders_user_id` ON `orders` (`user_id`);

ALTER TABLE `orders` DROP INDEX `orders_ibfk_1`;
CREATE INDEX `idx_orders_store_id` ON `orders` (`store_id`);

ALTER TABLE `orders` DROP INDEX `fk_orders_delivery_type`;
CREATE INDEX `idx_orders_delivery_type_id` ON `orders` (`delivery_type_id`);

ALTER TABLE `orders` DROP INDEX `fk_orders_status`;
CREATE INDEX `idx_orders_status_id` ON `orders` (`status_id`);

ALTER TABLE `products` DROP INDEX `category_id`;
CREATE INDEX `idx_products_category_id` ON `products` (`category_id`);

ALTER TABLE `products` DROP INDEX `name`;
CREATE FULLTEXT INDEX `ft_products_name_description` ON `products` (`name`, `description`);

ALTER TABLE `products` DROP INDEX `slug`;
CREATE UNIQUE INDEX `uq_products_slug` ON `products` (`slug`);

ALTER TABLE `product_images` DROP INDEX `product_id`;
CREATE INDEX `idx_product_images_product_id` ON `product_images` (`product_id`);

ALTER TABLE `payments` DROP INDEX `order_id`;
CREATE INDEX `idx_payments_order_id` ON `payments` (`order_id`);

ALTER TABLE `users` DROP INDEX `email_unique`;
CREATE UNIQUE INDEX `uq_users_email` ON `users` (`email`);

ALTER TABLE `email_verification_tokens` DROP INDEX `email_verification_token`;
CREATE UNIQUE INDEX `uq_email_verification_tokens_token` ON `email_verification_tokens` (`token`);

ALTER TABLE `password_reset_tokens` DROP INDEX `password_reset_tokens_token_unique`;
CREATE UNIQUE INDEX `uq_password_reset_tokens_token` ON `password_reset_tokens` (`token`);

-- Удаляет старые дубликаты, которые были созданы автоматически 

ALTER TABLE `cart_items` DROP INDEX `product_id`;
ALTER TABLE `carts` DROP INDEX `user_id`;
ALTER TABLE `order_items` DROP INDEX `order_id`;
ALTER TABLE `orders` DROP INDEX `user_id`;
ALTER TABLE `orders` DROP INDEX `orders_ibfk_1`;
ALTER TABLE `orders` DROP INDEX `fk_orders_delivery_type`;
ALTER TABLE `orders` DROP INDEX `fk_orders_status`;
ALTER TABLE `products` DROP INDEX `category_id`;
ALTER TABLE `product_images` DROP INDEX `product_id`;
ALTER TABLE `payments` DROP INDEX `order_id`;
ALTER TABLE `users` DROP INDEX `email_unique`;