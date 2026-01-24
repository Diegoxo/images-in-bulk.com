-- Delete Database
DROP DATABASE IF EXISTS images_in_bulk;

-- Create Database
CREATE DATABASE IF NOT EXISTS images_in_bulk DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE images_in_bulk;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    full_name VARCHAR(255),
    auth_provider VARCHAR(50) DEFAULT 'local',
    provider_id VARCHAR(255) NULL,
    avatar_url TEXT,
    credits INT DEFAULT 0, -- User's available credits (resets monthly)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Credit Bundles Table (Add-ons with expiry)
CREATE TABLE IF NOT EXISTS credit_bundles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount_original INT NOT NULL,
    amount_remaining INT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Subscriptions Table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_type VARCHAR(20) DEFAULT 'free',
    status VARCHAR(50) DEFAULT 'inactive',
    billing_cycle ENUM('monthly', 'yearly') DEFAULT 'monthly',
    last_credits_reset DATETIME DEFAULT CURRENT_TIMESTAMP,
    wompi_payment_source_id VARCHAR(255),
    wompi_customer_email VARCHAR(255),
    current_period_start DATETIME,
    current_period_end DATETIME,
    images_in_period INT DEFAULT 0, -- Counter for the current billing cycle
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Usage Log Table (to control the $5 plan limits)
CREATE TABLE IF NOT EXISTS usage_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    images_count INT DEFAULT 0,
    month_year VARCHAR(7), -- Format: YYYY-MM
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Generations History Table
CREATE TABLE IF NOT EXISTS generations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    prompt TEXT NOT NULL,
    image_url TEXT, -- Temp URL from OpenAI
    file_name VARCHAR(255),
    model VARCHAR(50),
    resolution VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
