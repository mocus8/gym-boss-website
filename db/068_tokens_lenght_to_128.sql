-- Увеличивает длинну полей с токенами до 128

ALTER TABLE `email_verification_tokens` MODIFY `token` varchar(128) COLLATE utf8mb4_0900_ai_ci NOT NULL;
ALTER TABLE `password_reset_tokens` MODIFY `token` varchar(128) COLLATE utf8mb4_0900_ai_ci NOT NULL;