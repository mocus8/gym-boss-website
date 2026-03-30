-- Добавление в таблицу stores полей latitude и longitude и их заполнение по старому полю coordinates

ALTER TABLE stores
    ADD COLUMN latitude DECIMAL(9,6) NULL AFTER work_hours,
    ADD COLUMN longitude DECIMAL(9,6) NULL AFTER latitude;

UPDATE stores
SET 
  latitude  = CAST(SUBSTRING_INDEX(coordinates, ',', 1) AS DECIMAL(9,6)),
  longitude = CAST(TRIM(SUBSTRING_INDEX(coordinates, ',', -1)) AS DECIMAL(9,6))
WHERE coordinates IS NOT NULL AND coordinates <> '';