-- Добавлено каскадное удаление адресов пользователей (удален пользователь - удален адрес)

ALTER TABLE delivery_addresses
  DROP FOREIGN KEY `delivery_addresses_ibfk_1`;

ALTER TABLE delivery_addresses
  ADD CONSTRAINT `delivery_addresses_ibfk_1`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE RESTRICT;
