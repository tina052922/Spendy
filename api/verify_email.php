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

// Check database connection
if (!$conn) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if settings record exists for this user
$checkStmt = mysqli_prepare($conn, "SELECT settings_id FROM settings WHERE user_id = ?");
if (!$checkStmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

mysqli_stmt_bind_param($checkStmt, "s", $user_id);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
$settingsExists = mysqli_num_rows($checkResult) > 0;
mysqli_stmt_close($checkStmt);

if ($settingsExists) {
    // Update existing settings - set verified_email to true
    $updateStmt = mysqli_prepare($conn, "UPDATE settings SET verified_email = 1, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
    if (!$updateStmt) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        exit;
    }
    
    mysqli_stmt_bind_param($updateStmt, "s", $user_id);
    
    if (mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        
        // Log email verification activity
        require_once __DIR__ . '/../includes/activity_logger.php';
        logEmailVerification($conn, $user_id);
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
        exit;
    } else {
        $errorMsg = mysqli_error($conn);
        mysqli_stmt_close($updateStmt);
        ob_clean();
        error_log("Email verification error: " . $errorMsg);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to verify email']);
        exit;
    }
} else {
    // Create new settings record with verified_email = true
    $settings_id = 'settings' . str_pad(substr($user_id, 4), 3, '0', STR_PAD_LEFT);
    
    $insertStmt = mysqli_prepare($conn, "INSERT INTO settings (settings_id, user_id, verified_email) VALUES (?, ?, 1)");
    if (!$insertStmt) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        exit;
    }
    
    mysqli_stmt_bind_param($insertStmt, "ss", $settings_id, $user_id);
    
    if (mysqli_stmt_execute($insertStmt)) {
        mysqli_stmt_close($insertStmt);
        
        // Log email verification activity
        require_once __DIR__ . '/../includes/activity_logger.php';
        logEmailVerification($conn, $user_id);
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
        exit;
    } else {
        $errorMsg = mysqli_error($conn);
        mysqli_stmt_close($insertStmt);
        ob_clean();
        error_log("Email verification insert error: " . $errorMsg);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to verify email']);
        exit;
    }
}
?>

