-- Изменяет все расширение для фоток товаров с .png на .webp

UPDATE product_images
SET image_path = REPLACE(image_path, '.png', '.webp')
WHERE image_path LIKE '%.png';