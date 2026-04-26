-- Меняет тип поля is_converted на boolean для читаемости

ALTER TABLE `carts` MODIFY `is_converted` boolean NOT NULL DEFAULT FALSE;