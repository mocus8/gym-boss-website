-- Создаёт таблицу (типы доставки) и заполняет базовые значения

CREATE TABLE IF NOT EXISTS delivery_types (
    id INT NOT NULL AUTO_INCREMENT,
    code VARCHAR(32)  NOT NULL,
    name VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_delivery_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO delivery_types (code, name) VALUES
    ('delivery', 'Доставка'),
    ('pickup', 'Самовывоз');