<?php
header('Content-Type: application/json');


// Start session for user_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get connection - use throwException=true to avoid die()
try {
    $conn = getDBConnection(true);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get user_id from session instead of request
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($user_id)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    $conn->close();
    exit;
}

// Get FormData (not JSON)
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$category = isset($_POST['category']) ? $conn->real_escape_string($_POST['category']) : '';
$note = isset($_POST['note']) ? $conn->real_escape_string($_POST['note']) : '';
$date = isset($_POST['date']) ? $conn->real_escape_string($_POST['date']) : date('Y-m-d');

// Validate required fields
if (empty($amount) || empty($category) || empty($date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: amount, category, or date']);
    $conn->close();
    exit;
}

// Prepare data
$expense_id = 'EXP' . time() . rand(1000, 9999); // Generate unique ID
$user_id_escaped = $conn->real_escape_string($user_id);

// Ensure user exists before inserting expense (prevents foreign key constraint error)
if (!ensureUserExists($conn, $user_id)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: Could not create or verify user account'
    ]);
    $conn->close();
    exit;
}

// Insert expense - removed 'time' column (doesn't exist, created_at handles it)
$sql = "INSERT INTO expenses (expense_id, user_id, category, amount, note, date) 
        VALUES ('$expense_id', '$user_id_escaped', '$category', $amount, '$note', '$date')";

if ($conn->query($sql)) {
    // Get the created_at timestamp for the time field
    $time_result = $conn->query("SELECT TIME(created_at) as time FROM expenses WHERE expense_id = '$expense_id'");
    $time_row = $time_result ? $time_result->fetch_assoc() : null;
    $time = $time_row ? $time_row['time'] : date('H:i:s');
    
    echo json_encode([
        'success' => true,
        'message' => 'Expense added successfully',
        'expense_id' => $expense_id,
        'time' => $time
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $conn->error
    ]);
}

$conn->close();
?>
