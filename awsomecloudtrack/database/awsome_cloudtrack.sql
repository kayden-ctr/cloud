
DROP DATABASE IF EXISTS awsome_cloudtrack;

CREATE DATABASE awsome_cloudtrack;

USE awsome_cloudtrack;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Members table
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(50) NOT NULL,
    join_date DATE NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory items table
CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    unit_price DECIMAL(10, 2) NOT NULL,
    reorder_level INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transactions table
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(50) NOT NULL,
    transaction_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_id INT,
    reference_type VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sales table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    sale_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    item_count INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Sale items table
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
);

-- Procurement orders table
CREATE TABLE procurement_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier VARCHAR(100) NOT NULL,
    order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    item_count INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'received', 'cancelled') NOT NULL DEFAULT 'pending',
    received_date TIMESTAMP NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Procurement items table
CREATE TABLE procurement_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES procurement_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$8zUlxQxkbIk7T3xUeFIRwOQgU9qZUwCYjmUXxSo1cdQQy4jUv.Iva', 'admin@example.com', 'admin');

-- Insert sample members
INSERT INTO members (name, email, phone, role, join_date, status, notes) VALUES
('Jasher Mendoza', 'jasher@example.com', '09123456789', 'President', '2023-01-01', 'active', 'Founding member'),
('Jeremy Dimasacat', 'jeremy@example.com', '09234567890', 'Vice President', '2023-01-01', 'active', 'Founding member'),
('Joshua Osorio', 'joshua@example.com', '09345678901', 'Secretary', '2023-01-15', 'active', 'Joined during first recruitment'),
('Khaela Lee', 'khaela@example.com', '09456789012', 'Treasurer', '2023-01-15', 'active', 'Handles financial matters'),
('John Smith', 'john@example.com', '09567890123', 'Member', '2023-02-01', 'active', 'Regular member');

-- Insert sample inventory items
INSERT INTO inventory_items (name, category, description, quantity, unit_price, reorder_level) VALUES
('Organization T-Shirt (S)', 'Apparel', 'Small size organization t-shirt with logo', 25, 350.00, 10),
('Organization T-Shirt (M)', 'Apparel', 'Medium size organization t-shirt with logo', 30, 350.00, 10),
('Organization T-Shirt (L)', 'Apparel', 'Large size organization t-shirt with logo', 20, 350.00, 10),
('Organization T-Shirt (XL)', 'Apparel', 'Extra large size organization t-shirt with logo', 15, 350.00, 5),
('Lanyard', 'Accessories', 'Organization lanyard with ID holder', 50, 120.00, 20),
('Sticker Pack', 'Merchandise', 'Pack of 5 organization stickers', 40, 80.00, 15),
('Notebook', 'Stationery', 'Organization branded notebook', 30, 150.00, 10),
('Tumbler', 'Merchandise', 'Organization branded tumbler', 20, 250.00, 8),
('Cap', 'Apparel', 'Organization branded cap', 15, 200.00, 5),
('Tote Bag', 'Accessories', 'Organization branded tote bag', 25, 180.00, 10);

-- Insert sample transactions
INSERT INTO transactions (description, amount, type, category, transaction_date) VALUES
('Initial funding', 10000.00, 'income', 'Funding', '2023-01-01 10:00:00'),
('T-shirt procurement', 5000.00, 'expense', 'Procurement', '2023-01-15 14:30:00'),
('Membership fees collection', 2500.00, 'income', 'Membership', '2023-02-01 09:15:00'),
('Event sponsorship', 3000.00, 'income', 'Sponsorship', '2023-02-15 11:00:00'),
('Venue rental for orientation', 1500.00, 'expense', 'Events', '2023-03-01 13:45:00'),
('Sale of merchandise', 1200.00, 'income', 'Sales', '2023-03-15 16:30:00'),
('Printing expenses', 800.00, 'expense', 'Operations', '2023-04-01 10:30:00'),
('Donation from alumni', 2000.00, 'income', 'Donation', '2023-04-15 09:00:00');

-- Insert sample sales
INSERT INTO sales (member_id, sale_date, item_count, total_amount, payment_method, notes, created_by) VALUES
(1, '2023-03-01 10:15:00', 2, 470.00, 'Cash', 'Purchased lanyard and stickers', 1),
(2, '2023-03-05 14:30:00', 1, 350.00, 'GCash', 'Purchased t-shirt', 1),
(3, '2023-03-10 11:45:00', 3, 580.00, 'Cash', 'Purchased stickers, notebook, and lanyard', 1),
(4, '2023-03-15 16:00:00', 1, 250.00, 'Bank Transfer', 'Purchased tumbler', 1),
(NULL, '2023-03-20 13:30:00', 2, 530.00, 'Cash', 'Guest purchase of cap and stickers', 1);

-- Insert sample sale items
INSERT INTO sale_items (sale_id, item_id, quantity, unit_price, total_price) VALUES
(1, 5, 1, 120.00, 120.00),  -- Lanyard
(1, 6, 1, 80.00, 80.00),    -- Sticker Pack
(2, 2, 1, 350.00, 350.00),  -- T-Shirt (M)
(3, 6, 1, 80.00, 80.00),    -- Sticker Pack
(3, 7, 1, 150.00, 150.00),  -- Notebook
(3, 5, 1, 120.00, 120.00),  -- Lanyard
(4, 8, 1, 250.00, 250.00),  -- Tumbler
(5, 9, 1, 200.00, 200.00),  -- Cap
(5, 6, 1, 80.00, 80.00);    -- Sticker Pack

-- Insert sample procurement orders
INSERT INTO procurement_orders (supplier, order_date, item_count, total_amount, status, received_date, notes, created_by) VALUES
('ABC Printing', '2023-01-10 09:00:00', 4, 28000.00, 'received', '2023-01-15 14:00:00', 'Initial t-shirt order', 1),
('XYZ Merchandise', '2023-01-20 10:30:00', 3, 12500.00, 'received', '2023-01-25 11:15:00', 'Accessories order', 1),
('Sticker Company', '2023-02-05 13:45:00', 1, 3200.00, 'received', '2023-02-10 15:30:00', 'Sticker packs order', 1),
('Premium Goods', '2023-02-15 11:00:00', 2, 9500.00, 'received', '2023-02-20 14:00:00', 'Tumblers and notebooks', 1),
('ABC Printing', '2023-04-20 10:00:00', 2, 10000.00, 'pending', NULL, 'Restock of t-shirts', 1);

-- Insert sample procurement items
INSERT INTO procurement_items (order_id, item_id, quantity, unit_price, total_price) VALUES
(1, 1, 25, 280.00, 7000.00),   -- T-Shirt (S)
(1, 2, 30, 280.00, 8400.00),   -- T-Shirt (M)
(1, 3, 20, 280.00, 5600.00),   -- T-Shirt (L)
(1, 4, 20, 280.00, 5600.00),   -- T-Shirt (XL)
(2, 5, 50, 90.00, 4500.00),    -- Lanyard
(2, 9, 15, 150.00, 2250.00),   -- Cap
(2, 10, 30, 140.00, 4200.00),  -- Tote Bag
(3, 6, 40, 60.00, 2400.00),    -- Sticker Pack
(4, 8, 20, 200.00, 4000.00),   -- Tumbler
(4, 7, 30, 120.00, 3600.00),   -- Notebook
(5, 1, 20, 280.00, 5600.00),   -- T-Shirt (S)
(5, 2, 20, 280.00, 5600.00);   -- T-Shirt (M)
