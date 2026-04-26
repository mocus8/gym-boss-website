-- Переименовываем поля 

UPDATE order_statuses
SET code = 'pending_payment'
WHERE id = 1;