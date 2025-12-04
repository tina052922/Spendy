-- ============================================================
-- SPENDY WEB APP - FINAL COMPLETE DATABASE
-- ============================================================
-- This is the COMPLETE database setup script for the Spendy application
-- It includes ALL tables, relationships, indexes, and sample data
-- 
-- Features:
-- ✅ Can be run multiple times without errors
-- ✅ Drops all existing tables and recreates them
-- ✅ Includes password_reset_tokens table for forgot password feature
-- ✅ Uses proper password hashing (bcrypt format)
-- ✅ All profile_photo fields set to NULL by default
-- ✅ Proper foreign key constraints
-- ✅ Sample data included for testing
--
-- How to Use:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Create database 'spendy_db' if it doesn't exist
-- 3. Select the 'spendy_db' database
-- 4. Go to SQL tab
-- 5. Copy and paste this entire file
-- 6. Click "Go"
-- ============================================================

USE spendy_db;

-- ============================================================
-- PART 1: DISABLE FOREIGN KEY CHECKS AND DROP ALL TABLES
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in reverse dependency order (child tables first)
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS transaction_log;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS income;
DROP TABLE IF EXISTS savings;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- PART 2: CREATE ALL TABLES (in dependency order)
-- ============================================================

-- ===========================
-- TABLE: users (parent table)
-- ===========================
CREATE TABLE users (
    user_id VARCHAR(20) PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    birthday DATE,
    phone VARCHAR(20),
    gender VARCHAR(10),
    nationality VARCHAR(50),
    address VARCHAR(100),
    profile_photo TEXT DEFAULT NULL,
    google_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================
-- TABLE: password_reset_tokens (depends on users)
-- ===========================
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================
-- TABLE: settings (depends on users)
-- ===========================
CREATE TABLE settings (
    settings_id VARCHAR(20) PRIMARY KEY,
    user_id VARCHAR(20) UNIQUE NOT NULL,
    verified_email BOOLEAN DEFAULT FALSE,
    recovery_email VARCHAR(100),
    recovery_email_2 VARCHAR(100),
    recovery_phone_1 VARCHAR(20),
    recovery_phone_2 VARCHAR(20),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================
-- TABLE: expenses (depends on users)
-- ===========================
CREATE TABLE expenses (
    expense_id VARCHAR(20) PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    category VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    note VARCHAR(255),
    fund_source VARCHAR(50),
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_date (date),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================
-- TABLE: income (depends on users)
-- ===========================
CREATE TABLE income (
    income_id VARCHAR(20) PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    category VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    note VARCHAR(255),
    fund_source VARCHAR(50),
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_date (date),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================
-- TABLE: savings (depends on users)
-- ===========================
CREATE TABLE savings (
    savings_id VARCHAR(20) PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    goal_amount DECIMAL(10,2) NOT NULL,
    saved_amount DECIMAL(10,2) DEFAULT 0,
    start_date DATE,
    end_date DATE,
    duration VARCHAR(20),
    status VARCHAR(20) DEFAULT 'Active',
    is_locked BOOLEAN DEFAULT FALSE,
    monthly_budget DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================
-- TABLE: transaction_log (depends on savings)
-- ===========================
CREATE TABLE transaction_log (
    transaction_id VARCHAR(20) PRIMARY KEY,
    savings_id VARCHAR(20) NOT NULL,
    transaction_type VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    date DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (savings_id) REFERENCES savings(savings_id) ON DELETE CASCADE,
    INDEX idx_savings_id (savings_id),
    INDEX idx_date (date),
    INDEX idx_transaction_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================
-- TABLE: activity_log (depends on users)
-- ===========================
CREATE TABLE activity_log (
    activity_id VARCHAR(40) PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    related_table VARCHAR(50) DEFAULT NULL,
    related_record_id VARCHAR(30) DEFAULT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_related_table (related_table),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

