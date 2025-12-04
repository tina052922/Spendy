
-- How to Use:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Select the 'spendy_db' database
-- 3. Go to SQL tab
-- 4. Copy and paste this entire file
-- 5. Click "Go"
-- ============================================================

USE spendy_db;

-- ============================================================
-- PART 1: DELETE ALL EXISTING DATA
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Delete all data from tables (in reverse dependency order)
DELETE FROM activity_log;
DELETE FROM transaction_log;
DELETE FROM password_reset_tokens;
DELETE FROM expenses;
DELETE FROM income;
DELETE FROM savings;
DELETE FROM settings;
DELETE FROM users;

SET FOREIGN_KEY_CHECKS = 1;

