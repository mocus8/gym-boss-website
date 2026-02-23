-- Меняет внешний ключ с платежа на заказ: добавляет каскадное удаление платежей

ALTER TABLE payments
  DROP FOREIGN KEY payments_order_fk;

ALTER TABLE payments
  ADD CONSTRAINT payments_order_fk
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
    ON UPDATE RESTRICT
    ON DELETE CASCADE;