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
    email_verified BOOLEAN DEFAULT FALSE,
    credits INT DEFAULT 0, -- User's available credits (resets monthly)
    extra_credits INT DEFAULT 0, -- Total sum of active extra credit bundles
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Email Verifications Table
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    verification_token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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

-- Registered Payment Methods Table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wompi_payment_source_id VARCHAR(255) NOT NULL,
    brand VARCHAR(50),
    last4 VARCHAR(4),
    exp_month INT,
    exp_year INT,
    is_default BOOLEAN DEFAULT FALSE,
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

-- Password Resets Table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reset_token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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

-- Email Change Requests Table
CREATE TABLE IF NOT EXISTS email_change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    new_email VARCHAR(255) NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
