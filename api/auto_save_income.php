<?php
// Start session for user_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use unified database connection
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

// Set JSON header
header('Content-Type: application/json');

ob_start();

// Get user_id from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($user_id)) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$savings_id = isset($_POST['savings_id']) ? trim($_POST['savings_id']) : '';
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$fund_source = isset($_POST['fund_source']) ? trim($_POST['fund_source']) : 'Auto-Save from Income';
// Support both parameter names for backward compatibility
$reactivate = (isset($_POST['reactivate_plan']) && $_POST['reactivate_plan'] === 'true') || 
             (isset($_POST['reactivate']) && $_POST['reactivate'] === '1');

// Validation
if (empty($savings_id) || $amount <= 0) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid savings ID or amount']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get current saved_amount and verify user owns the plan
    $query = "SELECT saved_amount, user_id FROM savings WHERE savings_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $savings_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $plan = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$plan) {
        throw new Exception('Savings plan not found');
    }

    if ($plan['user_id'] !== $user_id) {
        throw new Exception('Unauthorized: You do not own this savings plan');
    }

    $current_amount = floatval($plan['saved_amount']);
    $new_amount = $current_amount + $amount;

    // If reactivating an ended plan, set status to Active
    $reactivated = false;
    if ($reactivate) {
        // Update savings table with status change
        $query = "UPDATE savings SET saved_amount = ?, status = 'Active' WHERE savings_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ds", $new_amount, $savings_id);
        $reactivated = true;
    } else {
        // Update savings table without status change
        $query = "UPDATE savings SET saved_amount = ? WHERE savings_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ds", $new_amount, $savings_id);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update savings amount: ' . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    // Generate transaction_id
    $query = "SELECT MAX(CAST(SUBSTRING(transaction_id, 4) AS UNSIGNED)) as max_id FROM transaction_log WHERE transaction_id LIKE 'log%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $next_id = ($row['max_id'] ?? 0) + 1;
    $transaction_id = 'log' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

    // Insert into transaction_log
    $query = "INSERT INTO transaction_log (transaction_id, savings_id, transaction_type, amount, date) 
              VALUES (?, ?, 'deposit', ?, CURDATE())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssd", $transaction_id, $savings_id, $amount);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to log transaction: ' . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    // If fund_source is "Remaining Budget", track it for budget calculation
    // This ensures the budget remaining decreases by the allocated amount
    if ($fund_source === 'Remaining Budget') {
        $activity_id = 'ACT' . time() . rand(1000, 9999);
        // Store amount at the start in a parseable format for reliable extraction
        $amount_str = sprintf('%.2f', $amount);
        $action_description = "AMOUNT:" . $amount_str . "|Saved ₱" . number_format($amount, 2) . " from Remaining Budget to savings plan " . $savings_id . " (Transaction: " . $transaction_id . ")";
        
        $query = "INSERT INTO activity_log (activity_id, user_id, related_table, related_record_id, action_type, action_description) 
                  VALUES (?, ?, 'savings', ?, 'remaining_budget_savings', ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $activity_id, $user_id, $savings_id, $action_description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Commit transaction
    mysqli_commit($conn);
    
    // Log activity
    logDeposit($conn, $user_id, $savings_id, $amount);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Auto-save successful',
        'new_amount' => $new_amount,
        'transaction_id' => $transaction_id,
        'reactivated' => $reactivated
    ]);

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>


