-- Создаёт таблицу carts (корзины)

CREATE TABLE IF NOT EXISTS carts (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NULL,
  session_id VARCHAR(255) NULL,
  is_converted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_id (user_id),
  KEY session_id (session_id),
  CONSTRAINT carts_user_fk FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
