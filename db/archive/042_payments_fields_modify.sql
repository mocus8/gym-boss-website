-- Делаем поля external_payment_id confirmation_url допускающими null, чтобы можно было создать crating платеж

ALTER TABLE payments 
  MODIFY COLUMN external_payment_id varchar(128) NULL,
  MODIFY COLUMN confirmation_url TEXT NULL;