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

// Get user_id from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($user_id)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    $conn->close();
    exit;
}

// Fetch ended savings plans for the user (status != 'Active')
$sql = "SELECT savings_id, plan_name, goal_amount, saved_amount, status, end_date
        FROM savings 
        WHERE user_id = '" . $conn->real_escape_string($user_id) . "' AND status != 'Active' 
        ORDER BY end_date DESC, plan_name ASC";

$result = $conn->query($sql);

$plans = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $plans[] = [
            'savings_id' => $row['savings_id'],
            'plan_name' => $row['plan_name'],
            'goal_amount' => floatval($row['goal_amount']),
            'saved_amount' => floatval($row['saved_amount']),
            'status' => $row['status'],
            'end_date' => $row['end_date']
        ];
    }
}

echo json_encode([
    'success' => true,
    'plans' => $plans
]);

$conn->close();
?>

