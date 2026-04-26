-- Создает таблицу email_verification_tokens (токены для подтверждения почты пользователями)

CREATE TABLE email_verification_tokens (
  id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  token varchar(255) NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email_verification_user_unique (user_id),
  UNIQUE KEY email_verification_token(token),
  CONSTRAINT email_verification_tokens_user_fk
    FOREIGN KEY (user_id)
    REFERENCES users (id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;