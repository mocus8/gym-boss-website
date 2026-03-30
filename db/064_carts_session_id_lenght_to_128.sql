-- Меняет длинну session_id на 128 вместо избыточных 255

ALTER TABLE `carts` MODIFY `session_id` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL;