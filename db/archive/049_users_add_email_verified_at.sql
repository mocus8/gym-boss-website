-- Добавляет в таблицу users поле email_verified_at

ALTER TABLE users
  ADD email_verified_at TIMESTAMP DEFAULT NULL NULL AFTER email;
