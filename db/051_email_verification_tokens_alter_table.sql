-- Для таблицы email_verification_tokens удаляет ненужную колонку id и pk, pk устанавливается по user_id, меняет тип токена на varchar(64)

ALTER TABLE gymboss_db.email_verification_tokens
    DROP INDEX `PRIMARY`,
    DROP COLUMN id,
    ADD CONSTRAINT email_verification_tokens_pk PRIMARY KEY (user_id),
    DROP KEY email_verification_user_unique,
    MODIFY COLUMN token varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
