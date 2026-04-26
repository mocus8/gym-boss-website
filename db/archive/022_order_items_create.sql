-- Создает таблицу product_items и заполняет по product_order (название поменял просто, но старое сохранил)

CREATE TABLE order_items (
  product_id int NOT NULL,
  order_id   int NOT NULL,
  amount    int NOT NULL,
  PRIMARY KEY (product_id, order_id),
  KEY order_id (order_id),
  CONSTRAINT order_items_product_fk FOREIGN KEY (product_id)
    REFERENCES products (product_id)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT order_items_order_fk FOREIGN KEY (order_id)
    REFERENCES orders (order_id)
    ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

INSERT INTO order_items (product_id, order_id, amount)
SELECT product_id, order_id, amount
FROM product_order;
