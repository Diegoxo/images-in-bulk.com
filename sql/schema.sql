-- Delete Database
DROP DATABASE IF EXISTS images_in_bulk;

-- Create Database
CREATE DATABASE IF NOT EXISTS images_in_bulk DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE images_in_bulk;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL, -- Null for social login users
    full_name VARCHAR(255),
    auth_provider VARCHAR(50) DEFAULT 'local', -- 'local', 'google', 'microsoft'
    provider_id VARCHAR(255) NULL, -- Null for local users
    avatar_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subscriptions Table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_type VARCHAR(20) DEFAULT 'free', -- 'free', 'pro'
    status VARCHAR(50) DEFAULT 'inactive', -- 'active', 'inactive'
    stripe_customer_id VARCHAR(255), -- Optional (kept for future proofing)
    stripe_subscription_id VARCHAR(255), -- Optional
    current_period_start DATETIME,
    current_period_end DATETIME,
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
