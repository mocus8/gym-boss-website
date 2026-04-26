-- Создает таблицу login_attempts для хранения попыток входов в аккаунт и блокировок при брутфорсинге

CREATE TABLE gymboss_db.login_attempts (
	id INT auto_increment NOT NULL,
	email varchar(255) NOT NULL,
	attempted_at TIMESTAMP NOT NULL,
	CONSTRAINT login_attempts_pk PRIMARY KEY (id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_0900_ai_ci;
CREATE INDEX login_attempts_email_IDX USING BTREE ON gymboss_db.login_attempts (email);
CREATE INDEX login_attempts_attempted_at_IDX USING BTREE ON gymboss_db.login_attempts (attempted_at);
