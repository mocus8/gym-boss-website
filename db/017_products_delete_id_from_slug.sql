-- Удаляем конечный "-1" (удаляем id) из slug товаров (если он есть)

UPDATE products
    SET slug = REGEXP_REPLACE(slug, '-[0-9]+$', '')
    WHERE slug REGEXP '-[0-9]+$';