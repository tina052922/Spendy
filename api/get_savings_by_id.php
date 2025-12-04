<?php
// get_savings_by_id.php - Fetch a single savings plan by ID
// Used by: edit-savings.html, drawsavemoney.html

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$savings_id = isset($_GET['savingsId']) ? $_GET['savingsId'] : '';

if (empty($savings_id)) {
    echo json_encode(['success' => false, 'error' => 'Savings ID is required']);
    exit;
}

// Fetch the savings plan
$query = "SELECT * FROM savings WHERE savings_id = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database query preparation failed']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $savings_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$plan = mysqli_fetch_assoc($result);

if (!$plan) {
    echo json_encode(['success' => false, 'error' => 'Plan not found']);
    mysqli_stmt_close($stmt);
    exit;
}

// Format the plan data
$planData = [
    'savings_id' => $plan['savings_id'],
    'user_id' => $plan['user_id'],
    'plan_name' => $plan['plan_name'],
    'goal_amount' => floatval($plan['goal_amount']),
    'saved_amount' => floatval($plan['saved_amount']),
    'start_date' => $plan['start_date'],
    'end_date' => $plan['end_date'],
    'duration' => $plan['duration'],
    'status' => $plan['status'],
    'is_locked' => (bool)$plan['is_locked'],
    'monthly_budget' => $plan['monthly_budget'] ? floatval($plan['monthly_budget']) : null
];

mysqli_stmt_close($stmt);
echo json_encode(['success' => true, 'plan' => $planData]);
?>

