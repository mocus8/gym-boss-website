-- Переименовывает столбец login в email

ALTER TABLE users
    CHANGE COLUMN login email VARCHAR(255) NOT NULL;
  
ALTER TABLE users
    DROP INDEX `login (phone number)`,
    ADD UNIQUE KEY `email_unique` (`email`);