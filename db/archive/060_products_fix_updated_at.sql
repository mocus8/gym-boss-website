-- Для поля updated_at добавлено автоматическое обновление, тип изменен на timestamp

ALTER TABLE `products` MODIFY `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `products` MODIFY `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;