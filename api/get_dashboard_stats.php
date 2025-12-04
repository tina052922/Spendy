<?php
// Start session for user_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use unified database connection
require_once __DIR__ . '/../includes/db.php';

// Set JSON header
header('Content-Type: application/json');

// Get user_id from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($user_id)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Get month parameter
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get current month expenses
$stmt = mysqli_prepare($conn, "SELECT SUM(amount) as total FROM expenses 
                                WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $user_id, $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_expenses = floatval($row['total'] ?? 0);
    mysqli_stmt_close($stmt);
} else {
    $total_expenses = 0;
}

// Get current month income
$stmt = mysqli_prepare($conn, "SELECT SUM(amount) as total FROM income 
                                WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $user_id, $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_income = floatval($row['total'] ?? 0);
    mysqli_stmt_close($stmt);
} else {
    $total_income = 0;
}

// Get savings from "Remaining Budget" for this month
// We track this via activity_log with action_type = 'remaining_budget_savings'
// Amount is stored in format: "AMOUNT:1234.56|..."
$stmt = mysqli_prepare($conn, "SELECT action_description
                                FROM activity_log
                                WHERE user_id = ? 
                                AND action_type = 'remaining_budget_savings'
                                AND DATE_FORMAT(created_at, '%Y-%m') = ?");
$savings_from_remaining_budget = 0;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $user_id, $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Extract amounts from descriptions
    while ($row = mysqli_fetch_assoc($result)) {
        $description = $row['action_description'];
        // Extract amount from format "AMOUNT:1234.56|..." or "AMOUNT:1234.00|..."
        // Improved regex to handle both integer and decimal amounts
        if (preg_match('/AMOUNT:([0-9]+(?:\.[0-9]+)?)\|/', $description, $matches)) {
            $extracted_amount = floatval($matches[1]);
            $savings_from_remaining_budget += $extracted_amount;
        } else {
            // Fallback: try to extract from "Saved ₱X,XXX.XX" format
            if (preg_match('/Saved ₱([0-9,]+(?:\.[0-9]+)?)/', $description, $matches)) {
                $extracted_amount = floatval(str_replace(',', '', $matches[1]));
                $savings_from_remaining_budget += $extracted_amount;
            }
        }
    }
    mysqli_stmt_close($stmt);
}

// Get withdrawals to "Main Wallet Account" for this month
// These should increase remaining budget (reduce savings_from_remaining_budget)
// We track this via activity_log with action_type = 'main_wallet_withdrawal'
$stmt = mysqli_prepare($conn, "SELECT action_description
                                FROM activity_log
                                WHERE user_id = ? 
                                AND action_type = 'main_wallet_withdrawal'
                                AND DATE_FORMAT(created_at, '%Y-%m') = ?");
$main_wallet_withdrawals = 0;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $user_id, $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Extract amounts from descriptions
    while ($row = mysqli_fetch_assoc($result)) {
        $description = $row['action_description'];
        // Extract amount from format "AMOUNT:1234.56|..." or "AMOUNT:1234.00|..."
        if (preg_match('/AMOUNT:([0-9]+(?:\.[0-9]+)?)\|/', $description, $matches)) {
            $extracted_amount = floatval($matches[1]);
            $main_wallet_withdrawals += $extracted_amount;
        } else {
            // Fallback: try to extract from "Withdrew ₱X,XXX.XX" format
            if (preg_match('/Withdrew ₱([0-9,]+(?:\.[0-9]+)?)/', $description, $matches)) {
                $extracted_amount = floatval(str_replace(',', '', $matches[1]));
                $main_wallet_withdrawals += $extracted_amount;
            }
        }
    }
    mysqli_stmt_close($stmt);
}

// Subtract main wallet withdrawals from savings_from_remaining_budget
// This effectively increases remaining budget
$savings_from_remaining_budget = max(0, $savings_from_remaining_budget - $main_wallet_withdrawals);

// Get all savings deposits for this month (for reference)
$stmt = mysqli_prepare($conn, "SELECT SUM(tl.amount) as total 
                                FROM transaction_log tl
                                INNER JOIN savings s ON tl.savings_id = s.savings_id
                                WHERE s.user_id = ? 
                                AND tl.transaction_type = 'deposit'
                                AND DATE_FORMAT(tl.date, '%Y-%m') = ?");
$total_savings_deposits = 0;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $user_id, $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_savings_deposits = floatval($row['total'] ?? 0);
    mysqli_stmt_close($stmt);
}

// Calculate remaining budget
// Remaining budget = income - expenses - savings_from_remaining_budget
$total_balance = $total_income - $total_expenses;
$remaining_budget = $total_balance - $savings_from_remaining_budget;
$savings = max(0, $total_balance * 0.15); // 15% savings rate

// Ensure all values are properly formatted as numbers (not null/undefined)
$response_data = [
    'success' => true,
    'stats' => [
        'total_balance' => (float)$total_balance,
        'expenses' => (float)$total_expenses,
        'income' => (float)$total_income,
        'savings' => (float)$savings,
        'savings_deposits' => (float)$total_savings_deposits,
        'savings_from_remaining_budget' => (float)$savings_from_remaining_budget,
        'remaining_budget' => (float)$remaining_budget
    ],
    'month' => $month
];

echo json_encode($response_data);
?>

