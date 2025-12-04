<?php
// Start session FIRST, before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering to prevent any HTML/errors from being output before JSON
ob_start();

// Use the same database connection as other pages (db.php in root)
require_once __DIR__ . '/../includes/db.php';

// Set JSON header early
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get user_id from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($user_id)) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Get form data
$current_password = trim($_POST['current_password'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');

// Validation
if (empty($current_password) || empty($new_password)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Current password and new password are required']);
    exit;
}

if (strlen($new_password) < 8) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters']);
    exit;
}

// Check database connection
if (!$conn) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get current user's password from database
$userStmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
if (!$userStmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);

if (mysqli_num_rows($userResult) === 0) {
    mysqli_stmt_close($userStmt);
    ob_clean();
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = mysqli_fetch_assoc($userResult);
$stored_password = $user['password'];
mysqli_stmt_close($userStmt);

// Verify current password
$password_valid = false;

// Check if stored password is hashed (bcrypt/argon2) or plaintext
if (password_verify($current_password, $stored_password)) {
    // Password is hashed and matches
    $password_valid = true;
} elseif ($stored_password === $current_password) {
    // Password is plaintext and matches (legacy support)
    $password_valid = true;
}

if (!$password_valid) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

// Hash the new password
$hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password in database
$updateStmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
if (!$updateStmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

mysqli_stmt_bind_param($updateStmt, "ss", $hashed_new_password, $user_id);

if (mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);
    
    // Log password change activity
    require_once __DIR__ . '/../includes/activity_logger.php';
    logPasswordChange($conn, $user_id);
    
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    exit;
} else {
    $errorMsg = mysqli_error($conn);
    mysqli_stmt_close($updateStmt);
    ob_clean();
    error_log("Password update error: " . $errorMsg);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    exit;
}
?>

