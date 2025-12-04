<?php
// get_savings.php - Fetch all savings plans for a user
// Used by: savings.html

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Get user_id from session or request
// TODO: Replace with session-based authentication
session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : 'user002');

// Fetch all savings plans for the user
$query = "SELECT * FROM savings WHERE user_id = ? ORDER BY status DESC, end_date ASC";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['error' => 'Database query preparation failed']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$plans = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format dates for display
    $plans[] = [
        'savings_id' => $row['savings_id'],
        'user_id' => $row['user_id'],
        'plan_name' => $row['plan_name'],
        'goal_amount' => floatval($row['goal_amount']),
        'saved_amount' => floatval($row['saved_amount']),
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'duration' => $row['duration'],
        'status' => $row['status'],
        'is_locked' => (bool)$row['is_locked'],
        'monthly_budget' => $row['monthly_budget'] ? floatval($row['monthly_budget']) : null
    ];
}

mysqli_stmt_close($stmt);
echo json_encode(['success' => true, 'plans' => $plans]);
?>

