-- Добавляет поля ready_for_pickup_from, ready_for_pickup_to, courier_delivery_from, courier_delivery_to в orders

ALTER TABLE orders
    ADD COLUMN ready_for_pickup_from DATETIME NULL AFTER store_id,
    ADD COLUMN ready_for_pickup_to DATETIME NULL AFTER ready_for_pickup_from,
    ADD COLUMN courier_delivery_from DATETIME NULL AFTER ready_for_pickup_to,
    ADD COLUMN courier_delivery_to DATETIME NULL AFTER courier_delivery_from;