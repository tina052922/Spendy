<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$conn = getDBConnection();

// Get parameters
$user_id = isset($_GET['user_id']) ? $conn->real_escape_string($_GET['user_id']) : '';
$month = isset($_GET['month']) ? $conn->real_escape_string($_GET['month']) : date('Y-m');

// Get current month income
$sql_income = "SELECT SUM(amount) as total FROM income 
               WHERE user_id = '$user_id' AND DATE_FORMAT(date, '%Y-%m') = '$month'";
$result_income = $conn->query($sql_income);
$total_income = $result_income->fetch_assoc()['total'] ?? 0;

// Get current month expenses
$sql_expenses = "SELECT SUM(amount) as total FROM expenses 
                 WHERE user_id = '$user_id' AND DATE_FORMAT(date, '%Y-%m') = '$month'";
$result_expenses = $conn->query($sql_expenses);
$total_expenses = $result_expenses->fetch_assoc()['total'] ?? 0;

// Calculate balance and savings
$total_balance = $total_income - $total_expenses;
$savings = max(0, $total_balance * 0.15); // Assuming 15% savings rate

// Calculate percentage changes (simplified - you can enhance this)
$previous_month = date('Y-m', strtotime($month . '-01 -1 month'));
$sql_prev_income = "SELECT SUM(amount) as total FROM income 
                     WHERE user_id = '$user_id' AND DATE_FORMAT(date, '%Y-%m') = '$previous_month'";
$result_prev_income = $conn->query($sql_prev_income);
$prev_income = $result_prev_income->fetch_assoc()['total'] ?? 0;

$income_change = $prev_income > 0 ? (($total_income - $prev_income) / $prev_income) * 100 : 0;

echo json_encode([
    'success' => true,
    'stats' => [
        'total_balance' => floatval($total_balance),
        'expenses' => floatval($total_expenses),
        'income' => floatval($total_income),
        'savings' => floatval($savings),
        'income_change' => floatval($income_change)
    ],
    'month' => $month
]);

$conn->close();
?>

