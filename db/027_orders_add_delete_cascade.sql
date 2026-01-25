-- Добавлено каскадное удаление заказов пользователей (удален пользователь - удален заказов)

ALTER TABLE orders
  ADD CONSTRAINT `orders_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT;