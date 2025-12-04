<?php
// Start output buffering FIRST to prevent any HTML/errors from being output before JSON
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Use the same database connection as other files (db.php in root)
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data (can be POST or JSON)
$token = '';
$newPassword = '';
$confirmPassword = '';

if (isset($_POST['token'])) {
    $token = trim($_POST['token'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = trim($input['token'] ?? '');
    $newPassword = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
}

// Validation
if (empty($token)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid reset token']);
    exit;
}

if (empty($newPassword) || strlen($newPassword) < 6) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// Check database connection
if (!$conn) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Verify token
$stmt = mysqli_prepare($conn, "SELECT user_id, expires_at, used FROM password_reset_tokens WHERE token = ?");
if (!$stmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    mysqli_stmt_close($stmt);
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
    exit;
}

$tokenData = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Check if token is expired
if (strtotime($tokenData['expires_at']) < time()) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Reset token has expired']);
    exit;
}

// Check if token is already used
if ($tokenData['used'] == 1) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'This reset link has already been used']);
    exit;
}

$userId = $tokenData['user_id'];

// Hash new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update user password (using user_id column, not id)
$updateStmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
if (!$updateStmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

mysqli_stmt_bind_param($updateStmt, "ss", $hashedPassword, $userId);

if (!mysqli_stmt_execute($updateStmt)) {
    $errorMsg = mysqli_error($conn);
    mysqli_stmt_close($updateStmt);
    ob_clean();
    error_log("Reset password error: " . $errorMsg);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to reset password. Please try again.']);
    exit;
}

mysqli_stmt_close($updateStmt);

// Mark token as used
$markStmt = mysqli_prepare($conn, "UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
if ($markStmt) {
    mysqli_stmt_bind_param($markStmt, "s", $token);
    mysqli_stmt_execute($markStmt);
    mysqli_stmt_close($markStmt);
}

// Include activity logger to log password reset
require_once __DIR__ . '/../includes/activity_logger.php';
if (function_exists('logPasswordReset')) {
    logPasswordReset($conn, $userId);
}

ob_clean();
echo json_encode([
    'success' => true,
    'message' => 'Password reset successfully! You can now login with your new password.',
    'redirect' => 'login.html'
]);
?>
