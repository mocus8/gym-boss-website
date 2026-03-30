-- Заполнение полей delivery_address_text и delivery_postal_code в таблице orders на основе отдельной старой таблицы

UPDATE orders o
JOIN delivery_addresses d ON o.delivery_address_id = d.id
SET 
  o.delivery_address_text = d.address_line,
  o.delivery_postal_code  = d.postal_code
WHERE o.delivery_address_id IS NOT NULL;