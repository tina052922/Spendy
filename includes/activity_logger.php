<?php
/**
 * Activity Logger Helper Library
 * Provides functions to log user activities in the Spendy Web App
 * 
 * Usage:
 *   require_once 'activity_logger.php';
 *   logActivity($conn, 'user001', 'savings', 'sav001', 'create', 'Created new savings plan: Vacation Fund');
 */

/**
 * Generate a unique activity ID
 * Format: act{actiontype}_{random8chars}
 * Examples: actlogin_8921abx1, actsavingscreate_9912ccaa
 * 
 * @param string $action_type The action type (e.g., 'login', 'savingscreate')
 * @return string Generated activity ID
 */
function generateActivityId($action_type) {
    // Clean action type: remove spaces, convert to lowercase, limit to 20 chars
    $clean_action = strtolower(preg_replace('/[^a-z0-9]/', '', substr($action_type, 0, 20)));
    
    // Generate random 8-character hex string
    $random = bin2hex(random_bytes(4));
    
    return 'act' . $clean_action . '_' . $random;
}

/**
 * Get client IP address
 * Handles proxy/load balancer scenarios
 * 
 * @return string IP address
 */
function getClientIp() {
    $ip_keys = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_REAL_IP',        // Nginx proxy
        'HTTP_X_FORWARDED_FOR',  // Proxy/Load balancer
        'REMOTE_ADDR'            // Standard
    ];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Handle comma-separated IPs (X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR (may be private IP)
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Get user agent string
 * 
 * @return string User agent or 'unknown'
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

/**
 * Log an activity to the activity_log table
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID performing the action
 * @param string|null $related_table Related table name (e.g., 'savings', 'expenses', 'users')
 * @param string|null $related_record_id Related record ID (e.g., 'sav001', 'exp001')
 * @param string $action_type Action type (e.g., 'login', 'create', 'update', 'delete', 'withdraw')
 * @param string $action_description Full description of the action
 * @return bool|string Returns activity_id on success, false on failure
 */
function logActivity($conn, $user_id, $related_table = null, $related_record_id = null, $action_type = '', $action_description = '') {
    // Validate required parameters
    if (empty($user_id) || empty($action_type) || empty($action_description)) {
        error_log('[activity_logger] Missing required parameters: user_id, action_type, or action_description');
        return false;
    }
    
    // Generate activity ID
    $activity_id = generateActivityId($action_type);
    
    // Get client information
    $ip_address = getClientIp();
    $user_agent = getUserAgent();
    
    // Prepare SQL query
    $query = "INSERT INTO activity_log 
              (activity_id, user_id, related_table, related_record_id, action_type, action_description, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log('[activity_logger] Failed to prepare statement: ' . mysqli_error($conn));
        return false;
    }
    
    // Bind parameters
    mysqli_stmt_bind_param($stmt, "ssssssss", 
        $activity_id,
        $user_id,
        $related_table,
        $related_record_id,
        $action_type,
        $action_description,
        $ip_address,
        $user_agent
    );
    
    // Execute
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return $activity_id;
    } else {
        $error = mysqli_error($conn);
        error_log('[activity_logger] Failed to log activity: ' . $error);
        mysqli_stmt_close($stmt);
        return false;
    }
}

/**
 * Convenience function: Log user login
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @return bool|string Activity ID or false
 */
function logLogin($conn, $user_id) {
    return logActivity(
        $conn,
        $user_id,
        'users',
        $user_id,
        'login',
        "User logged into the system"
    );
}

/**
 * Convenience function: Log user signup
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @return bool|string Activity ID or false
 */
function logSignup($conn, $user_id) {
    return logActivity(
        $conn,
        $user_id,
        'users',
        $user_id,
        'signup',
        "New user registered in the system"
    );
}

/**
 * Convenience function: Log savings plan creation
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $savings_id Savings plan ID
 * @param string $plan_name Plan name
 * @return bool|string Activity ID or false
 */
function logSavingsCreate($conn, $user_id, $savings_id, $plan_name) {
    return logActivity(
        $conn,
        $user_id,
        'savings',
        $savings_id,
        'savingscreate',
        "Created new savings plan: {$plan_name}"
    );
}

/**
 * Convenience function: Log savings plan update
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $savings_id Savings plan ID
 * @param string $plan_name Plan name
 * @return bool|string Activity ID or false
 */
function logSavingsUpdate($conn, $user_id, $savings_id, $plan_name) {
    return logActivity(
        $conn,
        $user_id,
        'savings',
        $savings_id,
        'savingsupdate',
        "Updated savings plan: {$plan_name}"
    );
}

/**
 * Convenience function: Log deposit transaction
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $savings_id Savings plan ID
 * @param float $amount Deposit amount
 * @return bool|string Activity ID or false
 */
function logDeposit($conn, $user_id, $savings_id, $amount) {
    return logActivity(
        $conn,
        $user_id,
        'savings',
        $savings_id,
        'deposit',
        "Deposited ₱" . number_format($amount, 2) . " to savings plan"
    );
}

/**
 * Convenience function: Log withdrawal transaction
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $savings_id Savings plan ID
 * @param float $amount Withdrawal amount
 * @return bool|string Activity ID or false
 */
function logWithdraw($conn, $user_id, $savings_id, $amount) {
    return logActivity(
        $conn,
        $user_id,
        'savings',
        $savings_id,
        'withdraw',
        "Withdrew ₱" . number_format($amount, 2) . " from savings plan"
    );
}

/**
 * Convenience function: Log profile update
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $field Updated field name
 * @return bool|string Activity ID or false
 */
function logProfileUpdate($conn, $user_id, $field) {
    return logActivity(
        $conn,
        $user_id,
        'users',
        $user_id,
        'update_profile',
        "Updated profile field: {$field}"
    );
}

/**
 * Convenience function: Log settings update
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $setting_name Setting name
 * @return bool|string Activity ID or false
 */
function logSettingsUpdate($conn, $user_id, $setting_name) {
    return logActivity(
        $conn,
        $user_id,
        'settings',
        $user_id,
        'settingsupdate',
        "Updated setting: {$setting_name}"
    );
}

/**
 * Convenience function: Log expense creation
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $expense_id Expense ID
 * @param float $amount Expense amount
 * @param string $category Expense category
 * @return bool|string Activity ID or false
 */
function logExpenseCreate($conn, $user_id, $expense_id, $amount, $category) {
    return logActivity(
        $conn,
        $user_id,
        'expenses',
        $expense_id,
        'expensecreate',
        "Recorded expense: ₱" . number_format($amount, 2) . " for {$category}"
    );
}

/**
 * Convenience function: Log income creation
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $income_id Income ID
 * @param float $amount Income amount
 * @param string $category Income category
 * @return bool|string Activity ID or false
 */
function logIncomeCreate($conn, $user_id, $income_id, $amount, $category) {
    return logActivity(
        $conn,
        $user_id,
        'income',
        $income_id,
        'incomecreate',
        "Recorded income: ₱" . number_format($amount, 2) . " from {$category}"
    );
}

/**
 * Convenience function: Log income update
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $income_id Income ID
 * @param float $amount Income amount
 * @param string $category Income category
 * @return bool|string Activity ID or false
 */
function logIncomeUpdate($conn, $user_id, $income_id, $amount, $category) {
    return logActivity(
        $conn,
        $user_id,
        'income',
        $income_id,
        'incomeupdate',
        "Updated income: ₱" . number_format($amount, 2) . " from {$category}"
    );
}

/**
 * Convenience function: Log income deletion
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $income_id Income ID
 * @param float $amount Income amount
 * @return bool|string Activity ID or false
 */
function logIncomeDelete($conn, $user_id, $income_id, $amount) {
    return logActivity(
        $conn,
        $user_id,
        'income',
        $income_id,
        'incomedelete',
        "Deleted income record: ₱" . number_format($amount, 2)
    );
}

/**
 * Convenience function: Log expense update
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $expense_id Expense ID
 * @param float $amount Expense amount
 * @param string $category Expense category
 * @return bool|string Activity ID or false
 */
function logExpenseUpdate($conn, $user_id, $expense_id, $amount, $category) {
    return logActivity(
        $conn,
        $user_id,
        'expenses',
        $expense_id,
        'expenseupdate',
        "Updated expense: ₱" . number_format($amount, 2) . " for {$category}"
    );
}

/**
 * Convenience function: Log expense deletion
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $expense_id Expense ID
 * @param float $amount Expense amount
 * @return bool|string Activity ID or false
 */
function logExpenseDelete($conn, $user_id, $expense_id, $amount) {
    return logActivity(
        $conn,
        $user_id,
        'expenses',
        $expense_id,
        'expensedelete',
        "Deleted expense record: ₱" . number_format($amount, 2)
    );
}

/**
 * Convenience function: Log savings plan deletion
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $savings_id Savings plan ID
 * @param string $plan_name Plan name
 * @return bool|string Activity ID or false
 */
function logSavingsDelete($conn, $user_id, $savings_id, $plan_name) {
    return logActivity(
        $conn,
        $user_id,
        'savings',
        $savings_id,
        'savingsdelete',
        "Deleted savings plan: {$plan_name}"
    );
}

/**
 * Convenience function: Log user logout
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @return bool|string Activity ID or false
 */
function logLogout($conn, $user_id) {
    return logActivity(
        $conn,
        $user_id,
        'users',
        $user_id,
        'logout',
        "User logged out of the system"
    );
}

/**
 * Convenience function: Log password change
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @return bool|string Activity ID or false
 */
function logPasswordChange($conn, $user_id) {
    return logActivity(
        $conn,
        $user_id,
        'users',
        $user_id,
        'passwordchange',
        "User changed password"
    );
}

/**
 * Convenience function: Log password reset
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @return bool|string Activity ID or false
 */
function logPasswordReset($conn, $user_id) {
    return logActivity(
        $conn,
        $user_id,
        'users',
        $user_id,
        'passwordreset',
        "User reset password"
    );
}

/**
 * Convenience function: Log profile photo update
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @return bool|string Activity ID or false
 */
function logProfilePhotoUpdate($conn, $user_id) {
    return logActivity(
        $conn,
        $user_id,
        'users',
        $user_id,
        'profilephotoupdate',
        "User updated profile photo"
    );
}

/**
 * Convenience function: Log personal info update
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $field_name Field that was updated
 * @return bool|string Activity ID or false
 */
function logPersonalInfoUpdate($conn, $user_id, $field_name) {
    return logActivity(
        $conn,
        $user_id,
        'users',
        $user_id,
        'profileupdate',
        "Updated personal information: {$field_name}"
    );
}

/**
 * Convenience function: Log savings plan lock/unlock
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @param string $savings_id Savings plan ID
 * @param string $plan_name Plan name
 * @param bool $is_locked Whether plan is locked
 * @return bool|string Activity ID or false
 */
function logSavingsLockStatus($conn, $user_id, $savings_id, $plan_name, $is_locked) {
    $status = $is_locked ? 'locked' : 'unlocked';
    return logActivity(
        $conn,
        $user_id,
        'savings',
        $savings_id,
        'savingslock',
        "Savings plan {$status}: {$plan_name}"
    );
}

/**
 * Convenience function: Log email verification
 * 
 * @param mysqli $conn Database connection
 * @param string $user_id User ID
 * @return bool|string Activity ID or false
 */
function logEmailVerification($conn, $user_id) {
    return logActivity(
        $conn,
        $user_id,
        'settings',
        $user_id,
        'emailverify',
        "User verified email address"
    );
}

?>

