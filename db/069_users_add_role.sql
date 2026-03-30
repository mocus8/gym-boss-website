-- Добавляет поле role в таблицу users

ALTER TABLE `users` ADD COLUMN `role` varchar(32) NOT NULL DEFAULT 'customer' AFTER `name`;