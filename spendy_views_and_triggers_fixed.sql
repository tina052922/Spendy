-- ============================================================
-- SPENDY WEB APP - VIEWS AND TRIGGERS (FIXED)
-- ============================================================
-- This script creates:
-- 1. Database Views for easy data access
-- 2. Triggers to automatically track user activities
--
-- Run this AFTER spendy_final_database.sql
-- ============================================================

USE spendy_db;

-- ============================================================
-- PART 1: CREATE VIEWS
-- ============================================================

-- View: User Profile Summary
-- Combines users with their settings for complete profile view
DROP VIEW IF EXISTS v_user_profiles;
CREATE VIEW v_user_profiles AS
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.birthday,
    u.phone,
    u.gender,
    u.nationality,
    u.address,
    u.profile_photo,
    u.google_id,
    u.created_at as user_created_at,
    s.settings_id,
    s.verified_email,
    s.recovery_email,
    s.recovery_phone_1,
    s.updated_at as settings_updated_at
FROM users u
LEFT JOIN settings s ON u.user_id = s.user_id;

-- View: User Financial Summary
-- Aggregates income, expenses, and savings for each user
DROP VIEW IF EXISTS v_user_financial_summary;
CREATE VIEW v_user_financial_summary AS
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    COALESCE(SUM(DISTINCT i.amount), 0) as total_income,
    COALESCE(SUM(DISTINCT e.amount), 0) as total_expenses,
    COALESCE(SUM(DISTINCT s.saved_amount), 0) as total_savings,
    COALESCE(SUM(DISTINCT s.goal_amount), 0) as total_savings_goals,
    COUNT(DISTINCT i.income_id) as income_count,
    COUNT(DISTINCT e.expense_id) as expense_count,
    COUNT(DISTINCT s.savings_id) as savings_plan_count,
    (COALESCE(SUM(DISTINCT i.amount), 0) - COALESCE(SUM(DISTINCT e.amount), 0)) as net_balance
FROM users u
LEFT JOIN income i ON u.user_id = i.user_id
LEFT JOIN expenses e ON u.user_id = e.user_id
LEFT JOIN savings s ON u.user_id = s.user_id
GROUP BY u.user_id, u.first_name, u.last_name, u.email;

-- View: Monthly Income Summary
-- Groups income by user and month
DROP VIEW IF EXISTS v_monthly_income;
CREATE VIEW v_monthly_income AS
SELECT 
    user_id,
    DATE_FORMAT(date, '%Y-%m') as month,
    SUM(amount) as total_income,
    COUNT(*) as transaction_count,
    AVG(amount) as avg_income,
    MIN(amount) as min_income,
    MAX(amount) as max_income
FROM income
GROUP BY user_id, DATE_FORMAT(date, '%Y-%m');

-- View: Monthly Expenses Summary
-- Groups expenses by user and month
DROP VIEW IF EXISTS v_monthly_expenses;
CREATE VIEW v_monthly_expenses AS
SELECT 
    user_id,
    DATE_FORMAT(date, '%Y-%m') as month,
    SUM(amount) as total_expenses,
    COUNT(*) as transaction_count,
    AVG(amount) as avg_expense,
    MIN(amount) as min_expense,
    MAX(amount) as max_expense
FROM expenses
GROUP BY user_id, DATE_FORMAT(date, '%Y-%m');

-- View: Savings Plans Detail
-- Complete savings plan information with transaction counts
DROP VIEW IF EXISTS v_savings_plans_detail;
CREATE VIEW v_savings_plans_detail AS
SELECT 
    s.savings_id,
    s.user_id,
    u.first_name,
    u.last_name,
    s.plan_name,
    s.goal_amount,
    s.saved_amount,
    s.goal_amount - s.saved_amount as remaining_amount,
    ROUND((s.saved_amount / s.goal_amount) * 100, 2) as progress_percentage,
    s.start_date,
    s.end_date,
    s.duration,
    s.status,
    s.is_locked,
    s.monthly_budget,
    s.created_at,
    s.updated_at,
    COUNT(DISTINCT tl.transaction_id) as transaction_count,
    COALESCE(SUM(CASE WHEN tl.transaction_type = 'deposit' THEN tl.amount ELSE 0 END), 0) as total_deposits,
    COALESCE(SUM(CASE WHEN tl.transaction_type = 'withdraw' THEN tl.amount ELSE 0 END), 0) as total_withdrawals
FROM savings s
LEFT JOIN users u ON s.user_id = u.user_id
LEFT JOIN transaction_log tl ON s.savings_id = tl.savings_id
GROUP BY s.savings_id, s.user_id, u.first_name, u.last_name, s.plan_name, 
         s.goal_amount, s.saved_amount, s.start_date, s.end_date, s.duration, 
         s.status, s.is_locked, s.monthly_budget, s.created_at, s.updated_at;

-- View: Transaction History with Details
-- Complete transaction information with savings plan details
DROP VIEW IF EXISTS v_transaction_history;
CREATE VIEW v_transaction_history AS
SELECT 
    tl.transaction_id,
    tl.savings_id,
    s.plan_name,
    s.user_id,
    u.first_name,
    u.last_name,
    tl.transaction_type,
    tl.amount,
    tl.date,
    tl.created_at,
    s.status as plan_status,
    s.is_locked as plan_locked
FROM transaction_log tl
JOIN savings s ON tl.savings_id = s.savings_id
JOIN users u ON s.user_id = u.user_id;

-- View: User Activity Timeline
-- All user activities with user information
DROP VIEW IF EXISTS v_user_activity_timeline;
CREATE VIEW v_user_activity_timeline AS
SELECT 
    al.activity_id,
    al.user_id,
    u.first_name,
    u.last_name,
    u.email,
    al.related_table,
    al.related_record_id,
    al.action_type,
    al.action_description,
    al.ip_address,
    al.user_agent,
    al.created_at
FROM activity_log al
JOIN users u ON al.user_id = u.user_id
ORDER BY al.created_at DESC;

-- View: Category Spending Summary
-- Groups expenses by category for each user
DROP VIEW IF EXISTS v_category_spending;
CREATE VIEW v_category_spending AS
SELECT 
    user_id,
    category,
    SUM(amount) as total_amount,
    COUNT(*) as transaction_count,
    AVG(amount) as avg_amount,
    MIN(date) as first_transaction,
    MAX(date) as last_transaction
FROM expenses
WHERE category IS NOT NULL
GROUP BY user_id, category;

-- View: Category Income Summary
-- Groups income by category for each user
DROP VIEW IF EXISTS v_category_income;
CREATE VIEW v_category_income AS
SELECT 
    user_id,
    category,
    SUM(amount) as total_amount,
    COUNT(*) as transaction_count,
    AVG(amount) as avg_amount,
    MIN(date) as first_transaction,
    MAX(date) as last_transaction
FROM income
WHERE category IS NOT NULL
GROUP BY user_id, category;

-- View: Active Savings Plans
-- Only active savings plans
DROP VIEW IF EXISTS v_active_savings_plans;
CREATE VIEW v_active_savings_plans AS
SELECT 
    savings_id,
    user_id,
    plan_name,
    goal_amount,
    saved_amount,
    ROUND((saved_amount / goal_amount) * 100, 2) as progress_percentage,
    start_date,
    end_date,
    duration,
    is_locked,
    monthly_budget,
    created_at,
    updated_at
FROM savings
WHERE status = 'Active'
ORDER BY created_at DESC;

-- View: Ended Savings Plans
-- Only ended/finished savings plans
DROP VIEW IF EXISTS v_ended_savings_plans;
CREATE VIEW v_ended_savings_plans AS
SELECT 
    savings_id,
    user_id,
    plan_name,
    goal_amount,
    saved_amount,
    ROUND((saved_amount / goal_amount) * 100, 2) as progress_percentage,
    start_date,
    end_date,
    duration,
    status,
    created_at,
    updated_at
FROM savings
WHERE status != 'Active'
ORDER BY end_date DESC;

-- ============================================================
-- PART 2: CREATE TRIGGERS FOR ACTIVITY TRACKING
-- ============================================================

-- Trigger: Track User Registration
DROP TRIGGER IF EXISTS trg_user_after_insert;
DELIMITER //
CREATE TRIGGER trg_user_after_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actuserreg_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        NEW.user_id,
        'users',
        NEW.user_id,
        'user_registration',
        CONCAT('New user registered: ', NEW.first_name, ' ', NEW.last_name, ' (', NEW.email, ')'),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track User Profile Updates
DROP TRIGGER IF EXISTS trg_user_after_update;
DELIMITER //
CREATE TRIGGER trg_user_after_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    DECLARE changes TEXT DEFAULT '';
    
    -- Track what fields changed
    IF OLD.first_name != NEW.first_name THEN
        SET changes = CONCAT(changes, 'First name, ');
    END IF;
    IF OLD.last_name != NEW.last_name THEN
        SET changes = CONCAT(changes, 'Last name, ');
    END IF;
    IF OLD.email != NEW.email THEN
        SET changes = CONCAT(changes, 'Email, ');
    END IF;
    IF OLD.phone != NEW.phone THEN
        SET changes = CONCAT(changes, 'Phone, ');
    END IF;
    IF OLD.birthday != NEW.birthday THEN
        SET changes = CONCAT(changes, 'Birthday, ');
    END IF;
    IF OLD.gender != NEW.gender THEN
        SET changes = CONCAT(changes, 'Gender, ');
    END IF;
    IF OLD.nationality != NEW.nationality THEN
        SET changes = CONCAT(changes, 'Nationality, ');
    END IF;
    IF OLD.address != NEW.address THEN
        SET changes = CONCAT(changes, 'Address, ');
    END IF;
    IF (OLD.profile_photo IS NULL AND NEW.profile_photo IS NOT NULL) OR 
       (OLD.profile_photo IS NOT NULL AND NEW.profile_photo IS NULL) OR
       (OLD.profile_photo != NEW.profile_photo) THEN
        SET changes = CONCAT(changes, 'Profile photo, ');
    END IF;
    
    -- Remove trailing comma and space
    IF LENGTH(changes) > 0 THEN
        SET changes = LEFT(changes, LENGTH(changes) - 2);
        
        INSERT INTO activity_log (
            activity_id,
            user_id,
            related_table,
            related_record_id,
            action_type,
            action_description,
            created_at
        ) VALUES (
            CONCAT('actprofileupd_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
            NEW.user_id,
            'users',
            NEW.user_id,
            'profile_update',
            CONCAT('Updated profile fields: ', changes),
            NOW()
        );
    END IF;
END//
DELIMITER ;

-- Trigger: Track Income Creation
DROP TRIGGER IF EXISTS trg_income_after_insert;
DELIMITER //
CREATE TRIGGER trg_income_after_insert
AFTER INSERT ON income
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actincomeadd_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        NEW.user_id,
        'income',
        NEW.income_id,
        'income_create',
        CONCAT('Added income: ', COALESCE(NEW.category, 'Uncategorized'), ' - ₱', FORMAT(NEW.amount, 2), 
               CASE WHEN NEW.note IS NOT NULL AND NEW.note != '' THEN CONCAT(' (', NEW.note, ')') ELSE '' END),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Income Updates
DROP TRIGGER IF EXISTS trg_income_after_update;
DELIMITER //
CREATE TRIGGER trg_income_after_update
AFTER UPDATE ON income
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actincomeupd_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        NEW.user_id,
        'income',
        NEW.income_id,
        'income_update',
        CONCAT('Updated income record: ', COALESCE(NEW.category, 'Uncategorized'), ' - ₱', FORMAT(NEW.amount, 2)),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Income Deletion
DROP TRIGGER IF EXISTS trg_income_after_delete;
DELIMITER //
CREATE TRIGGER trg_income_after_delete
AFTER DELETE ON income
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actincomedel_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        OLD.user_id,
        'income',
        OLD.income_id,
        'income_delete',
        CONCAT('Deleted income record: ', COALESCE(OLD.category, 'Uncategorized'), ' - ₱', FORMAT(OLD.amount, 2)),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Expense Creation
DROP TRIGGER IF EXISTS trg_expense_after_insert;
DELIMITER //
CREATE TRIGGER trg_expense_after_insert
AFTER INSERT ON expenses
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actexpenseadd_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        NEW.user_id,
        'expenses',
        NEW.expense_id,
        'expense_create',
        CONCAT('Added expense: ', COALESCE(NEW.category, 'Uncategorized'), ' - ₱', FORMAT(NEW.amount, 2),
               CASE WHEN NEW.note IS NOT NULL AND NEW.note != '' THEN CONCAT(' (', NEW.note, ')') ELSE '' END),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Expense Updates
DROP TRIGGER IF EXISTS trg_expense_after_update;
DELIMITER //
CREATE TRIGGER trg_expense_after_update
AFTER UPDATE ON expenses
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actexpenseupd_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        NEW.user_id,
        'expenses',
        NEW.expense_id,
        'expense_update',
        CONCAT('Updated expense record: ', COALESCE(NEW.category, 'Uncategorized'), ' - ₱', FORMAT(NEW.amount, 2)),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Expense Deletion
DROP TRIGGER IF EXISTS trg_expense_after_delete;
DELIMITER //
CREATE TRIGGER trg_expense_after_delete
AFTER DELETE ON expenses
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actexpensedel_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        OLD.user_id,
        'expenses',
        OLD.expense_id,
        'expense_delete',
        CONCAT('Deleted expense record: ', COALESCE(OLD.category, 'Uncategorized'), ' - ₱', FORMAT(OLD.amount, 2)),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Savings Plan Creation
DROP TRIGGER IF EXISTS trg_savings_after_insert;
DELIMITER //
CREATE TRIGGER trg_savings_after_insert
AFTER INSERT ON savings
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actsavingscreate_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        NEW.user_id,
        'savings',
        NEW.savings_id,
        'savings_create',
        CONCAT('Created new savings plan: ', NEW.plan_name, ' (Goal: ₱', FORMAT(NEW.goal_amount, 2), ')'),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Savings Plan Updates
DROP TRIGGER IF EXISTS trg_savings_after_update;
DELIMITER //
CREATE TRIGGER trg_savings_after_update
AFTER UPDATE ON savings
FOR EACH ROW
BEGIN
    DECLARE changes TEXT DEFAULT '';
    
    -- Track what fields changed
    IF OLD.plan_name != NEW.plan_name THEN
        SET changes = CONCAT(changes, 'Plan name, ');
    END IF;
    IF OLD.goal_amount != NEW.goal_amount THEN
        SET changes = CONCAT(changes, 'Goal amount, ');
    END IF;
    IF OLD.start_date != NEW.start_date THEN
        SET changes = CONCAT(changes, 'Start date, ');
    END IF;
    IF OLD.end_date != NEW.end_date THEN
        SET changes = CONCAT(changes, 'End date, ');
    END IF;
    IF OLD.duration != NEW.duration THEN
        SET changes = CONCAT(changes, 'Duration, ');
    END IF;
    IF OLD.status != NEW.status THEN
        SET changes = CONCAT(changes, 'Status (', OLD.status, ' → ', NEW.status, '), ');
    END IF;
    IF OLD.is_locked != NEW.is_locked THEN
        SET changes = CONCAT(changes, 'Lock status (', IF(OLD.is_locked, 'Locked', 'Unlocked'), ' → ', IF(NEW.is_locked, 'Locked', 'Unlocked'), '), ');
    END IF;
    IF OLD.monthly_budget != NEW.monthly_budget THEN
        SET changes = CONCAT(changes, 'Monthly budget, ');
    END IF;
    
    -- Remove trailing comma and space
    IF LENGTH(changes) > 0 THEN
        SET changes = LEFT(changes, LENGTH(changes) - 2);
        
        INSERT INTO activity_log (
            activity_id,
            user_id,
            related_table,
            related_record_id,
            action_type,
            action_description,
            created_at
        ) VALUES (
            CONCAT('actsavingsupd_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
            NEW.user_id,
            'savings',
            NEW.savings_id,
            'savings_update',
            CONCAT('Updated savings plan: ', NEW.plan_name, ' - Changed: ', changes),
            NOW()
        );
    END IF;
END//
DELIMITER ;

-- Trigger: Track Savings Plan Deletion
DROP TRIGGER IF EXISTS trg_savings_after_delete;
DELIMITER //
CREATE TRIGGER trg_savings_after_delete
AFTER DELETE ON savings
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actsavingsdel_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        OLD.user_id,
        'savings',
        OLD.savings_id,
        'savings_delete',
        CONCAT('Deleted savings plan: ', OLD.plan_name, ' (Goal: ₱', FORMAT(OLD.goal_amount, 2), ')'),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Transaction Log (Deposits/Withdrawals)
DROP TRIGGER IF EXISTS trg_transaction_log_after_insert;
DELIMITER //
CREATE TRIGGER trg_transaction_log_after_insert
AFTER INSERT ON transaction_log
FOR EACH ROW
BEGIN
    DECLARE v_user_id VARCHAR(20);
    DECLARE v_plan_name VARCHAR(100);
    
    -- Get user_id and plan_name from savings table
    SELECT user_id, plan_name INTO v_user_id, v_plan_name
    FROM savings
    WHERE savings_id = NEW.savings_id;
    
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('acttrans', NEW.transaction_type, '_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        v_user_id,
        'transaction_log',
        NEW.transaction_id,
        NEW.transaction_type,
        CONCAT(UCASE(LEFT(NEW.transaction_type, 1)), SUBSTRING(NEW.transaction_type, 2), 
               ' ₱', FORMAT(NEW.amount, 2), ' from/to savings plan: ', v_plan_name),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Settings Updates
DROP TRIGGER IF EXISTS trg_settings_after_update;
DELIMITER //
CREATE TRIGGER trg_settings_after_update
AFTER UPDATE ON settings
FOR EACH ROW
BEGIN
    DECLARE changes TEXT DEFAULT '';
    
    -- Track what fields changed
    IF OLD.verified_email != NEW.verified_email THEN
        SET changes = CONCAT(changes, 'Email verification status, ');
    END IF;
    IF OLD.recovery_email != NEW.recovery_email THEN
        SET changes = CONCAT(changes, 'Recovery email, ');
    END IF;
    IF OLD.recovery_phone_1 != NEW.recovery_phone_1 THEN
        SET changes = CONCAT(changes, 'Recovery phone 1, ');
    END IF;
    
    -- Remove trailing comma and space
    IF LENGTH(changes) > 0 THEN
        SET changes = LEFT(changes, LENGTH(changes) - 2);
        
        INSERT INTO activity_log (
            activity_id,
            user_id,
            related_table,
            related_record_id,
            action_type,
            action_description,
            created_at
        ) VALUES (
            CONCAT('actsettingsupd_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
            NEW.user_id,
            'settings',
            NEW.settings_id,
            'settings_update',
            CONCAT('Updated settings: ', changes),
            NOW()
        );
    END IF;
END//
DELIMITER ;

-- Trigger: Track Password Reset Token Creation
DROP TRIGGER IF EXISTS trg_password_reset_after_insert;
DELIMITER //
CREATE TRIGGER trg_password_reset_after_insert
AFTER INSERT ON password_reset_tokens
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (
        activity_id,
        user_id,
        related_table,
        related_record_id,
        action_type,
        action_description,
        created_at
    ) VALUES (
        CONCAT('actpassreset_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
        NEW.user_id,
        'password_reset_tokens',
        CAST(NEW.id AS CHAR),
        'password_reset_request',
        CONCAT('Password reset token requested (expires: ', DATE_FORMAT(NEW.expires_at, '%Y-%m-%d %H:%i:%s'), ')'),
        NOW()
    );
END//
DELIMITER ;

-- Trigger: Track Password Reset Token Usage
DROP TRIGGER IF EXISTS trg_password_reset_after_update;
DELIMITER //
CREATE TRIGGER trg_password_reset_after_update
AFTER UPDATE ON password_reset_tokens
FOR EACH ROW
BEGIN
    -- Only log when token is marked as used
    IF OLD.used = 0 AND NEW.used = 1 THEN
        INSERT INTO activity_log (
            activity_id,
            user_id,
            related_table,
            related_record_id,
            action_type,
            action_description,
            created_at
        ) VALUES (
            CONCAT('actpassresetused_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)),
            NEW.user_id,
            'password_reset_tokens',
            CAST(NEW.id AS CHAR),
            'password_reset_completed',
            'Password reset token used successfully',
            NOW()
        );
    END IF;
END//
DELIMITER ;

-- ============================================================
-- PART 3: VERIFICATION
-- ============================================================

-- View all created views
-- SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- View all created triggers
-- SHOW TRIGGERS;

-- Test trigger by inserting a test record (uncomment to test)
-- INSERT INTO income (income_id, user_id, category, amount, note, fund_source, date)
-- VALUES ('test001', 'user001', 'Test', 100.00, 'Test trigger', 'Cash', CURDATE());
-- 
-- Check if activity was logged
-- SELECT * FROM activity_log WHERE related_record_id = 'test001' ORDER BY created_at DESC LIMIT 1;
-- 
-- Clean up test record
-- DELETE FROM income WHERE income_id = 'test001';
-- DELETE FROM activity_log WHERE related_record_id = 'test001';

-- ============================================================
-- SUMMARY
-- ============================================================
-- 
-- ✅ Created 12 Views:
--    1. v_user_profiles - Complete user profile with settings (FIXED: removed non-existent columns)
--    2. v_user_financial_summary - Financial overview per user
--    3. v_monthly_income - Monthly income aggregation
--    4. v_monthly_expenses - Monthly expenses aggregation
--    5. v_savings_plans_detail - Detailed savings plan information
--    6. v_transaction_history - Complete transaction history
--    7. v_user_activity_timeline - All user activities
--    8. v_category_spending - Spending by category
--    9. v_category_income - Income by category
--    10. v_active_savings_plans - Only active plans
--    11. v_ended_savings_plans - Only ended plans
--
-- ✅ Created 15 Triggers:
--    1. trg_user_after_insert - Track user registration
--    2. trg_user_after_update - Track profile updates
--    3. trg_income_after_insert - Track income creation
--    4. trg_income_after_update - Track income updates
--    5. trg_income_after_delete - Track income deletion
--    6. trg_expense_after_insert - Track expense creation
--    7. trg_expense_after_update - Track expense updates
--    8. trg_expense_after_delete - Track expense deletion
--    9. trg_savings_after_insert - Track savings plan creation
--    10. trg_savings_after_update - Track savings plan updates
--    11. trg_savings_after_delete - Track savings plan deletion
--    12. trg_transaction_log_after_insert - Track deposits/withdrawals
--    13. trg_settings_after_update - Track settings changes (FIXED: removed non-existent columns)
--    14. trg_password_reset_after_insert - Track password reset requests
--    15. trg_password_reset_after_update - Track password reset completion
--
-- All triggers automatically log activities to the activity_log table
-- with detailed descriptions of what changed.
-- ============================================================

