-- Меняет код статуса с cancelled на canceled в справочнике order_statuses

UPDATE order_statuses
    SET code = 'canceled'
    WHERE id = 6;