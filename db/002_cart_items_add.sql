-- Создаёт таблицу cart_items (товары в корзинах)

CREATE TABLE IF NOT EXISTS cart_items (
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  amount INT NOT NULL,
  PRIMARY KEY (cart_id, product_id),
  KEY product_id (product_id),
  CONSTRAINT cart_items_cart_fk FOREIGN KEY (cart_id)
    REFERENCES carts (id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT cart_items_product_fk FOREIGN KEY (product_id)
    REFERENCES products (product_id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
