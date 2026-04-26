-- Добавлено каскадное удаление корзин пользователей (удален пользователь - удалена корзина)

ALTER TABLE carts
  DROP FOREIGN KEY `carts_user_fk`;

ALTER TABLE carts
  ADD CONSTRAINT `carts_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT;