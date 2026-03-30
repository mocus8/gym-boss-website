-- Создает таблицу payments

CREATE TABLE payments (
  id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  status varchar(50) NOT NULL DEFAULT 'pending',
  amount decimal(10,2) NOT NULL,
  provider varchar(50) NOT NULL DEFAULT 'yookassa',
  external_payment_id varchar(128) NOT NULL,
  idempotency_key varchar(128) NOT NULL, 
  confirmation_url TEXT NOT NULL, 
  expires_at datetime DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY order_id (order_id),
  UNIQUE KEY uq_payments_provider_external_payment_id (provider, external_payment_id),
  UNIQUE KEY uq_payments_idempotency (idempotency_key),
  CONSTRAINT payments_order_fk FOREIGN KEY (order_id)
    REFERENCES orders (order_id)
    ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;