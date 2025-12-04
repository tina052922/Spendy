<?php
// add_savings.php - Create a new savings plan
// Used by: savings.html (Add Savings Plan form)

// Turn off error display for production, but log errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unwanted output
ob_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

error_log('[add_savings] request received at ' . date('Y-m-d H:i:s') . ' from IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Get form data
$plan_name = isset($_POST['planName']) ? trim($_POST['planName']) : '';
$goal_amount = isset($_POST['goalAmount']) ? floatval($_POST['goalAmount']) : 0;
$start_date = isset($_POST['startDate']) ? $_POST['startDate'] : '';
$end_date = isset($_POST['endDate']) ? $_POST['endDate'] : '';
// Checkbox: if checked, value is "1", if unchecked, it's not sent at all
$is_locked = (isset($_POST['isLocked']) && $_POST['isLocked'] == '1') ? 1 : 0;

// TODO: Get user_id from session (for now, using default)
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : 'user001';

// Validation
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

// Generate savings_id (format: sav001, sav002, etc.)
$query = "SELECT MAX(CAST(SUBSTRING(savings_id, 4) AS UNSIGNED)) as max_id FROM savings WHERE savings_id LIKE 'sav%'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$next_id = ($row['max_id'] ?? 0) + 1;
$savings_id = 'sav' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

// Insert into database
$query = "INSERT INTO savings (savings_id, user_id, plan_name, goal_amount, saved_amount, start_date, end_date, duration, status, is_locked) 
          VALUES (?, ?, ?, ?, 0, ?, ?, ?, 'Active', ?)";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['error' => 'Database query preparation failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "sssdsssi", $savings_id, $user_id, $plan_name, $goal_amount, $start_date, $end_date, $duration, $is_locked);

if (mysqli_stmt_execute($stmt)) {
    // Log activity
    logSavingsCreate($conn, $user_id, $savings_id, $plan_name);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Savings plan created successfully',
        'savings_id' => $savings_id
    ]);
} else {
    ob_clean();
    echo json_encode(['error' => 'Failed to create savings plan: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);

// End output buffering
ob_end_flush();
?>

