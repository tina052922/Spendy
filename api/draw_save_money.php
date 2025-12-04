<?php
// draw_save_money.php - Handle save/withdraw money transactions
// Used by: drawsavemoney.html

// Suppress all errors and warnings to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');

// Get transaction data
$savings_id = isset($_POST['savings_id']) ? trim($_POST['savings_id']) : '';
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$transaction_type = isset($_POST['transaction_type']) ? $_POST['transaction_type'] : ''; // 'deposit' or 'withdraw'
$fund_source = isset($_POST['fund_source']) ? trim($_POST['fund_source']) : '';

// TODO: Get user_id from session (for now, we'll get it from the savings record)
$user_id = null;

// Clear any output
ob_clean();

// Validation
if (empty($savings_id) || $amount <= 0) {
    ob_clean();
    echo json_encode(['error' => 'Savings ID and amount are required']);
    exit;
}

if (!in_array($transaction_type, ['deposit', 'withdraw'])) {
    ob_clean();
    echo json_encode(['error' => 'Invalid transaction type']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get current saved_amount and user_id
    $query = "SELECT saved_amount, is_locked, user_id FROM savings WHERE savings_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $savings_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $plan = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$plan) {
        throw new Exception('Savings plan not found');
    }
    
    $user_id = $plan['user_id'];

    // Check if plan is locked and user is trying to withdraw
    if ($transaction_type === 'withdraw' && $plan['is_locked']) {
        throw new Exception('This plan is locked. Withdrawals are not allowed.');
    }

    $current_amount = floatval($plan['saved_amount']);
    
    // Calculate new saved_amount
    if ($transaction_type === 'deposit') {
        $new_amount = $current_amount + $amount;
    } else { // withdraw
        if ($amount > $current_amount) {
            throw new Exception('Insufficient funds. Available: ₱' . number_format($current_amount, 2));
        }
        $new_amount = $current_amount - $amount;
    }

    // Update savings table
    $query = "UPDATE savings SET saved_amount = ? WHERE savings_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ds", $new_amount, $savings_id);
    
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
    // Note: We'll store fund_source information by creating a note in a separate tracking mechanism
    // Since transaction_log doesn't have fund_source, we'll use a workaround
    $query = "INSERT INTO transaction_log (transaction_id, savings_id, transaction_type, amount, date) 
              VALUES (?, ?, ?, ?, CURDATE())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssd", $transaction_id, $savings_id, $transaction_type, $amount);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to log transaction: ' . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    // If deposit and fund_source is "Remaining Budget", track it for budget calculation
    // Store amount in a parseable format: "AMOUNT:1234.56|Saved from Remaining Budget..."
    if ($transaction_type === 'deposit' && $fund_source === 'Remaining Budget') {
        $activity_id = 'ACT' . time() . rand(1000, 9999);
        // Store amount at the start in a parseable format for reliable extraction
        // Use sprintf to ensure consistent decimal format
        $amount_str = sprintf('%.2f', $amount);
        $action_description = "AMOUNT:" . $amount_str . "|Saved ₱" . number_format($amount, 2) . " from Remaining Budget to savings plan " . $savings_id . " (Transaction: " . $transaction_id . ")";
        
        $query = "INSERT INTO activity_log (activity_id, user_id, related_table, related_record_id, action_type, action_description) 
                  VALUES (?, ?, 'savings', ?, 'remaining_budget_savings', ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $activity_id, $user_id, $savings_id, $action_description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // If withdraw and destination is "Main Wallet Account", track it to increase remaining budget
    // This effectively returns money to the remaining budget
    if ($transaction_type === 'withdraw' && $fund_source === 'Main Wallet Account') {
        $activity_id = 'ACT' . time() . rand(1000, 9999);
        // Store amount at the start in a parseable format for reliable extraction
        // Use sprintf to ensure consistent decimal format
        $amount_str = sprintf('%.2f', $amount);
        $action_description = "AMOUNT:" . $amount_str . "|Withdrew ₱" . number_format($amount, 2) . " from savings plan " . $savings_id . " to Main Wallet (Transaction: " . $transaction_id . ")";
        
        $query = "INSERT INTO activity_log (activity_id, user_id, related_table, related_record_id, action_type, action_description) 
                  VALUES (?, ?, 'savings', ?, 'main_wallet_withdrawal', ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $activity_id, $user_id, $savings_id, $action_description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Log activity
    if ($transaction_type === 'deposit') {
        logDeposit($conn, $user_id, $savings_id, $amount);
    } else {
        logWithdraw($conn, $user_id, $savings_id, $amount);
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => ucfirst($transaction_type) . ' successful',
        'new_amount' => $new_amount,
        'transaction_id' => $transaction_id,
        'fund_source' => $fund_source
    ]);

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    ob_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
?>

