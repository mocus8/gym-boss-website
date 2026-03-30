-- Добавление индексов для ускорения типичных и частых запросов

-- Заказы пользователя по статусу
CREATE INDEX `idx_orders_user_id_status_id` ON `orders` (`user_id`, `status_id`);

-- Заказы по дате создания
CREATE INDEX `idx_orders_created_at` ON `orders` (`created_at`);

-- Заказы по статусу и дате
CREATE INDEX `idx_orders_status_id_created_at` ON `orders` (`status_id`, `created_at`);

-- Платежи по статусу
CREATE INDEX `idx_payments_status` ON `payments` (`status`);

-- Товары по цене
CREATE INDEX `idx_products_price` ON `products` (`price`);

-- Товары по категории и цене
CREATE INDEX `idx_products_category_id_price` ON `products` (`category_id`, `price`);