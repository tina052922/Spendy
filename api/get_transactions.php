<?php
// get_transactions.php - Fetch transaction history for a savings plan
// Used by: drawsavemoney.html

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$savings_id = isset($_GET['savingsId']) ? $_GET['savingsId'] : '';

if (empty($savings_id)) {
    echo json_encode(['error' => 'Savings ID is required']);
    exit;
}

// Fetch transactions
$query = "SELECT * FROM transaction_log WHERE savings_id = ? ORDER BY date DESC, transaction_id DESC";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['error' => 'Database query preparation failed']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $savings_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$transactions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $transactions[] = [
        'transaction_id' => $row['transaction_id'],
        'savings_id' => $row['savings_id'],
        'transaction_type' => $row['transaction_type'],
        'amount' => floatval($row['amount']),
        'date' => $row['date']
    ];
}

mysqli_stmt_close($stmt);
echo json_encode(['success' => true, 'transactions' => $transactions]);
?>

