-- Меняет всю длинну email на 254 символа по RFC 5321, для этого временное снятие fk_password_reset_tokens_users

ALTER TABLE `password_reset_tokens` DROP FOREIGN KEY `fk_password_reset_tokens_users`;

ALTER TABLE `users` MODIFY `email` varchar(254) COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `password_reset_tokens` MODIFY `email` varchar(254) COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `login_attempts` MODIFY `email` varchar(254) NOT NULL;

ALTER TABLE `password_reset_tokens` ADD CONSTRAINT `fk_password_reset_tokens_users`
  FOREIGN KEY (`email`) REFERENCES `users` (`email`)
  ON DELETE CASCADE ON UPDATE CASCADE;