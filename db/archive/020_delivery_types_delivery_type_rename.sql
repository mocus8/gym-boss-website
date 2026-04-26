-- Переименовываем поля 

UPDATE delivery_types
SET code = 'courier',
    name = 'Курьерская доставка'
WHERE id = 1;