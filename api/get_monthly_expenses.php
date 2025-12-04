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

// Validate month format
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid month format']);
    exit;
}

// Get expenses grouped by category for the month
$stmt = mysqli_prepare($conn, "SELECT 
                                    category,
                                    SUM(amount) as total_amount,
                                    COUNT(*) as transaction_count
                                FROM expenses 
                                WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
                                GROUP BY category
                                ORDER BY total_amount DESC");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ss", $user_id, $month);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$categories = [];
$total_month_expenses = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = [
        'category' => $row['category'],
        'amount' => floatval($row['total_amount']),
        'count' => intval($row['transaction_count'])
    ];
    $total_month_expenses += floatval($row['total_amount']);
}

mysqli_stmt_close($stmt);

// Calculate percentages
foreach ($categories as &$cat) {
    $cat['percentage'] = $total_month_expenses > 0 ? ($cat['amount'] / $total_month_expenses) * 100 : 0;
}

// Get daily expenses for the bar chart
$stmt_daily = mysqli_prepare($conn, "SELECT 
                                        DAY(date) as day,
                                        SUM(amount) as daily_total
                                      FROM expenses 
                                      WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
                                      GROUP BY DAY(date)
                                      ORDER BY day");

$daily_expenses = [];
$max_daily = 0;

if ($stmt_daily) {
    mysqli_stmt_bind_param($stmt_daily, "ss", $user_id, $month);
    mysqli_stmt_execute($stmt_daily);
    $result_daily = mysqli_stmt_get_result($stmt_daily);
    
    while ($row = mysqli_fetch_assoc($result_daily)) {
        $daily_expenses[intval($row['day'])] = floatval($row['daily_total']);
        if (floatval($row['daily_total']) > $max_daily) {
            $max_daily = floatval($row['daily_total']);
        }
    }
    mysqli_stmt_close($stmt_daily);
}

echo json_encode([
    'success' => true,
    'month' => $month,
    'categories' => $categories,
    'total_expenses' => floatval($total_month_expenses),
    'daily_expenses' => $daily_expenses,
    'max_daily' => floatval($max_daily)
]);
?>
