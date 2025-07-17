-- Create database
CREATE DATABASE airtel_ecommerce;
USE airtel_ecommerce;

-- Admin table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    category_id INT,
    image VARCHAR(255),
    features TEXT,
    specifications TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    shipping_address TEXT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert default admin
INSERT INTO admins (username, email, password, full_name) 
VALUES ('admin', 'admin@airtel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

-- Insert categories
INSERT INTO categories (name, description) VALUES 
('Routers', 'High-speed wireless routers for home and office'),
('MiFi Devices', 'Portable wireless hotspot devices'),
('Data Lines', 'Airtel data line packages and SIM cards'),
('Accessories', 'Network accessories and cables');

-- Insert sample products
INSERT INTO products (name, description, price, stock_quantity, category_id, features, specifications) VALUES 
('Airtel 4G Router Pro', 'High-speed 4G wireless router with advanced features', 8500.00, 15, 1, 'Dual-band WiFi, 4G LTE support, 8 device connections', 'Speed: Up to 150Mbps, Range: 50m, Battery: 2000mAh'),
('Airtel MiFi Hotspot', 'Portable 4G MiFi device for on-the-go connectivity', 4500.00, 25, 2, 'Portable design, 8-hour battery, 10 device support', 'Speed: Up to 100Mbps, Battery: 2400mAh, Weight: 120g'),
('Airtel Unlimited Data Line', 'Monthly unlimited data package', 2500.00, 100, 3, 'Unlimited data, High-speed 4G, No throttling', 'Speed: Up to 50Mbps, Validity: 30 days'),
('Airtel Home Router', 'Basic home router for small families', 3500.00, 20, 1, 'Easy setup, Parental controls, Guest network', 'Speed: Up to 50Mbps, Range: 30m, Connections: 15'),
('Airtel Travel MiFi', 'Compact MiFi for travelers', 3200.00, 30, 2, 'Ultra-portable, Long battery life, Global roaming', 'Speed: Up to 75Mbps, Battery: 1800mAh, Weight: 95g');