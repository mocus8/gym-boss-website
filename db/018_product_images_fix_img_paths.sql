-- Убираем id из пути картинок

UPDATE product_images
SET image_path = REPLACE(
    REPLACE(image_path, '-1-', '-'),
    '-2-',
    '-'
)
WHERE image_path LIKE '%.png';
