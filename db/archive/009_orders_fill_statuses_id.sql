-- Заполнение поля status_id в таблице orders на основе поля status

-- Для pending
UPDATE orders o
JOIN order_statuses os ON os.code = 'pending'
SET o.status_id = os.id
WHERE o.status = 'pending';

-- Для paid
UPDATE orders o
JOIN order_statuses os ON os.code = 'paid'
SET o.status_id = os.id
WHERE o.status = 'paid';

-- Для shipped
UPDATE orders o
JOIN order_statuses os ON os.code = 'shipped'
SET o.status_id = os.id
WHERE o.status = 'shipped';

-- Для ready_for_pickup
UPDATE orders o
JOIN order_statuses os ON os.code = 'ready_for_pickup'
SET o.status_id = os.id
WHERE o.status = 'ready_for_pickup';

-- Для completed
UPDATE orders o
JOIN order_statuses os ON os.code = 'completed'
SET o.status_id = os.id
WHERE o.status = 'completed';

-- Для cancelled
UPDATE orders o
JOIN order_statuses os ON os.code = 'cancelled'
SET o.status_id = os.id
WHERE o.status = 'cancelled';