<?php
// update_savings.php - Update an existing savings plan
// Used by: edit-savings.html

// Suppress all errors and warnings to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');

// Get form data
$savings_id = isset($_POST['savings_id']) ? trim($_POST['savings_id']) : '';
$plan_name = isset($_POST['planName']) ? trim($_POST['planName']) : '';
$goal_amount = isset($_POST['targetAmount']) ? floatval($_POST['targetAmount']) : 0;
$start_date = isset($_POST['startDate']) ? $_POST['startDate'] : '';
$end_date = isset($_POST['endDate']) ? $_POST['endDate'] : '';
$is_locked = isset($_POST['isLocked']) ? 1 : 0;

// TODO: Get user_id from session (for now, we'll get it from the savings record)
$user_id = null;

// Clear any output
ob_clean();

// Validation
if (empty($savings_id)) {
    ob_clean();
    echo json_encode(['error' => 'Savings ID is required']);
    exit;
}

if (empty($plan_name) || $goal_amount <= 0 || empty($start_date) || empty($end_date)) {
    ob_clean();
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

// Calculate duration
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$diff = $start->diff($end);
$duration_days = $diff->days;
$duration = $duration_days > 0 ? $duration_days . ' Days' : '0 Days';

// Get user_id from savings record for logging
$get_user_query = "SELECT user_id FROM savings WHERE savings_id = ?";
$get_user_stmt = mysqli_prepare($conn, $get_user_query);
if ($get_user_stmt) {
    mysqli_stmt_bind_param($get_user_stmt, "s", $savings_id);
    mysqli_stmt_execute($get_user_stmt);
    $user_result = mysqli_stmt_get_result($get_user_stmt);
    $user_row = mysqli_fetch_assoc($user_result);
    if ($user_row) {
        $user_id = $user_row['user_id'];
    }
    mysqli_stmt_close($get_user_stmt);
}

// Update the savings plan
$query = "UPDATE savings SET plan_name = ?, goal_amount = ?, start_date = ?, end_date = ?, duration = ?, is_locked = ? 
          WHERE savings_id = ?";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['error' => 'Database query preparation failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "sdsssis", $plan_name, $goal_amount, $start_date, $end_date, $duration, $is_locked, $savings_id);

// Get old values before update to detect changes
$old_values = null;
$get_old_query = "SELECT is_locked, plan_name FROM savings WHERE savings_id = ?";
$get_old_stmt = mysqli_prepare($conn, $get_old_query);
if ($get_old_stmt) {
    mysqli_stmt_bind_param($get_old_stmt, "s", $savings_id);
    mysqli_stmt_execute($get_old_stmt);
    $old_result = mysqli_stmt_get_result($get_old_stmt);
    $old_values = mysqli_fetch_assoc($old_result);
    mysqli_stmt_close($get_old_stmt);
}

if (mysqli_stmt_execute($stmt)) {
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    
    // Check if there were actual changes (even if affected_rows is 0, values might have changed)
    $has_changes = false;
    $lock_changed = false;
    
    if ($old_values) {
        $old_is_locked = (bool)$old_values['is_locked'];
        $new_is_locked = (bool)$is_locked;
        $lock_changed = ($old_is_locked != $new_is_locked);
        
        // Check if any field changed
        if ($lock_changed || 
            $old_values['plan_name'] != $plan_name ||
            $affected_rows > 0) {
            $has_changes = true;
        }
    } else {
        $has_changes = ($affected_rows > 0);
    }
    
    if ($has_changes) {
        // Log activity
        if ($user_id) {
            // Always log update if there are changes
            logSavingsUpdate($conn, $user_id, $savings_id, $plan_name);
            
            // Log lock status change if it changed
            if ($lock_changed) {
                logSavingsLockStatus($conn, $user_id, $savings_id, $plan_name, (bool)$is_locked);
            }
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Savings plan updated successfully'
        ]);
    } else {
        ob_clean();
        echo json_encode(['error' => 'No changes made or plan not found']);
    }
} else {
    ob_clean();
    echo json_encode(['error' => 'Failed to update savings plan: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
?>

