-- Меняем обратные слеши на обычные в таблице картинок для заказов

UPDATE product_images
SET image_path = REPLACE(image_path, CHAR(92), '/');