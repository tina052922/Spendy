<?php
header('Content-Type: application/json');

// Start session for user_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';

// Get connection - use throwException=true to avoid die()
try {
    $conn = getDBConnection(true);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get user_id from session instead of GET
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($user_id)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    $conn->close();
    exit;
}

// Get date parameter
$date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');

// Build query - use created_at instead of time (time column doesn't exist)
$sql = "SELECT expense_id, category, amount, note, date, created_at, 
        TIME(created_at) as time
        FROM expenses 
        WHERE user_id = '" . $conn->real_escape_string($user_id) . "' AND date = '$date' 
        ORDER BY created_at DESC";

$result = $conn->query($sql);

$expenses = [];
$total = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $expenses[] = [
            'expense_id' => $row['expense_id'],
            'category' => $row['category'],
            'amount' => floatval($row['amount']),
            'note' => $row['note'],
            'date' => $row['date'],
            'time' => $row['time'] ? $row['time'] : '00:00:00'
        ];
        $total += floatval($row['amount']);
    }
}

echo json_encode([
    'success' => true,
    'expenses' => $expenses,
    'total' => $total,
    'date' => $date
]);

$conn->close();
?>
