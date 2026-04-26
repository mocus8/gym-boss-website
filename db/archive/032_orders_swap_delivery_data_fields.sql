-- Меняет местами поля с датами доставки/самовывоза

ALTER TABLE orders
    MODIFY COLUMN courier_delivery_from DATETIME AFTER status_id,
    MODIFY COLUMN courier_delivery_to DATETIME AFTER courier_delivery_from,
    MODIFY COLUMN ready_for_pickup_from DATETIME AFTER courier_delivery_to,
    MODIFY COLUMN ready_for_pickup_to DATETIME AFTER ready_for_pickup_from;