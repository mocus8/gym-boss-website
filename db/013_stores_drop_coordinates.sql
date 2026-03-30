-- Удаляем старый столбец с едиными координатами

ALTER TABLE stores
    DROP COLUMN coordinates;