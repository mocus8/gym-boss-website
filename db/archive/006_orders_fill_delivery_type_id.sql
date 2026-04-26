-- Заполнение поля delivery_type_id в таблице orders на основе поля delivery_type

-- Для delivery
UPDATE orders o
JOIN delivery_types dt ON dt.code = 'delivery'
SET o.delivery_type_id = dt.id
WHERE o.delivery_type = 'delivery';

-- Для pickup
UPDATE orders o
JOIN delivery_types dt ON dt.code = 'pickup'
SET o.delivery_type_id = dt.id
WHERE o.delivery_type = 'pickup';