-- добавляет таблицу password_reset_tokens

CREATE TABLE `password_reset_tokens` (
  `email` varchar(254) NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`),
  UNIQUE KEY `password_reset_tokens_token_unique` (`token`),
  CONSTRAINT `password_reset_tokens_user_fk` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;