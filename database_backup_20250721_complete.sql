-- Полный бэкап базы данных Хром-KZ Логистика
-- Создан: 2025-07-21 09:47:00
-- PostgreSQL Database Backup

-- Удаление существующих таблиц
DROP TABLE IF EXISTS shipment_orders CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Создание таблицы users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Создание таблицы shipment_orders с правильными полями
CREATE TABLE shipment_orders (
    id SERIAL PRIMARY KEY,
    order_type VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'new',
    pickup_address TEXT NOT NULL,
    ready_time TIME,
    cargo_type VARCHAR(100),
    weight NUMERIC,
    dimensions VARCHAR(100),
    contact_name VARCHAR(100),
    contact_phone VARCHAR(20),
    pickup_city VARCHAR(100),
    destination_city VARCHAR(100),
    delivery_address TEXT,
    delivery_method VARCHAR(50),
    desired_arrival_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    recipient_contact VARCHAR(100),
    recipient_phone VARCHAR(20),
    comment TEXT,
    shipping_cost NUMERIC
);

-- Вставка данных пользователей
INSERT INTO users (id, username, password, created_at, updated_at) VALUES
(1, 'admin', '$2y$10$WMfHFgagrt1yMLVT6GdrHejr29JQoBW9Q27nfYDxJe5b9rXTy/9JC', '2025-07-21 07:59:22.531757', '2025-07-21 07:59:22.531757');

-- Вставка данных заказов
INSERT INTO shipment_orders (id, order_type, status, pickup_address, ready_time, cargo_type, weight, dimensions, contact_name, contact_phone, pickup_city, destination_city, delivery_address, delivery_method, desired_arrival_date, created_at, updated_at, notes, recipient_contact, recipient_phone, comment, shipping_cost) VALUES
(3, 'astana', 'new', 'Тестовый адрес', '14:00:00', 'Документы', 1.50, 'A4', 'Тест', ' 77771234567', NULL, NULL, '', NULL, NULL, '2025-07-21 08:00:56.972615', '2025-07-21 08:00:56.972615', '', '', '', '', 6793.00),
(4, 'regional', 'new', 'Тестовый адрес', '15:00:00', 'Электроника', 2.50, '50x40x30', 'Тест Региональный', ' 77771234567', '', 'Алматы', 'ул. Абая 100', 'Курьер', '2025-07-25', '2025-07-21 08:01:00.464806', '2025-07-21 08:01:00.464806', '', '', '', '', 11770.00);

-- Обновление последовательностей (sequences)
SELECT setval('users_id_seq', (SELECT COALESCE(MAX(id), 1) FROM users));
SELECT setval('shipment_orders_id_seq', (SELECT COALESCE(MAX(id), 1) FROM shipment_orders));

-- Конец бэкапа базы данных Хром-KZ Логистика
-- Дата создания: 2025-07-21 09:47:00
-- Всего пользователей: 1
-- Всего заказов: 2 (1 астана, 1 региональный)
-- База данных: PostgreSQL