CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

INSERT INTO users (name, email) VALUES
    ('João Silva', 'joao@example.com'),
    ('Maria Souza', 'maria@example.com'),
    ('Carlos Oliveira', 'carlos@example.com');

CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL
);

INSERT INTO products (name, price) VALUES
    ('Notebook', 3500.00),
    ('Smartphone', 1999.90),
    ('Tablet', 1200.50);