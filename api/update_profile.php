<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error logging but suppress display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Start session FIRST before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Files are now in root directory - db.php and activity_logger.php are both in root
// Suppress any output from db.php
ob_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';
ob_end_clean();

// Verify database connection exists and is valid
if (!isset($conn) || !$conn || !mysqli_ping($conn)) {
    // Try to establish connection manually if db.php failed silently
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "spendy_db";
    $conn = @mysqli_connect($host, $user, $pass, $dbname);
    
    if (!$conn || !mysqli_ping($conn)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
        exit;
    }
}

// Prevent any output before JSON
ob_start();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input: ' . json_last_error_msg()]);
    exit;
}

// Clear any output that might have been generated
ob_clean();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($input['userId']) ? $input['userId'] : 'user002');

if (empty($user_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

// Parse fullName into first_name and last_name if provided
$first_name = null;
$last_name = null;
if (isset($input['fullName']) && !empty($input['fullName'])) {
    $nameParts = explode(' ', trim($input['fullName']), 2);
    $first_name = $nameParts[0];
    $last_name = isset($nameParts[1]) ? $nameParts[1] : '';
} else if (isset($input['firstName']) || isset($input['lastName'])) {
    $first_name = $input['firstName'] ?? null;
    $last_name = $input['lastName'] ?? null;
}

// Build update query dynamically based on provided fields
$updates = [];
$params = [];
$types = '';

if ($first_name !== null) {
    $updates[] = "first_name = ?";
    $params[] = $first_name;
    $types .= 's';
}

if ($last_name !== null) {
    $updates[] = "last_name = ?";
    $params[] = $last_name;
    $types .= 's';
}

if (isset($input['email'])) {
    $updates[] = "email = ?";
    $params[] = $input['email'];
    $types .= 's';
}

if (isset($input['phone'])) {
    $updates[] = "phone = ?";
    $params[] = $input['phone'];
    $types .= 's';
}

if (isset($input['birthday'])) {
    $updates[] = "birthday = ?";
    $params[] = $input['birthday'];
    $types .= 's';
}

if (isset($input['gender'])) {
    $updates[] = "gender = ?";
    $params[] = $input['gender'];
    $types .= 's';
}

if (isset($input['nationality'])) {
    $updates[] = "nationality = ?";
    $params[] = $input['nationality'];
    $types .= 's';
}

if (isset($input['address'])) {
    $updates[] = "address = ?";
    $params[] = $input['address'];
    $types .= 's';
}

if (isset($input['profileImage'])) {
    // Handle base64 images - storing the full data URL
    $profile_photo = trim($input['profileImage']);
    
    // Only update profile_photo if it's a base64 data URI (new upload)
    // Don't update if it's just the default image path - that means no change
    if (!empty($profile_photo) && strpos($profile_photo, 'data:image/') === 0) {
        // This is a new base64 upload - update it
    $updates[] = "profile_photo = ?";
    $params[] = $profile_photo;
    $types .= 's';
    } else if (empty($profile_photo) || $profile_photo === 'null' || $profile_photo === '' || $profile_photo === 'images/blankprofile.png') {
        // Skip updating - if it's empty/null/default, don't change it
        // This avoids NULL binding issues and unnecessary updates
    }
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to update']);
    exit;
}

// Add user_id to params
$params[] = $user_id;
$types .= 's';

// Build and execute query
$sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $error_msg = mysqli_error($conn);
    error_log("Profile update - Failed to prepare statement. SQL: $sql, Error: $error_msg, Params count: " . count($params) . ", Types: $types");
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare statement: ' . $error_msg, 'debug' => 'Check PHP error log for details']);
    exit;
}

// Verify parameter count matches
if (strlen($types) !== count($params)) {
    $error_msg = "Parameter count mismatch: types length (" . strlen($types) . ") != params count (" . count($params) . ")";
    error_log("Profile update - $error_msg. SQL: $sql, Types: $types");
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Internal error: Parameter mismatch', 'debug' => 'Check PHP error log for details']);
    mysqli_stmt_close($stmt);
    exit;
}

$bind_result = mysqli_stmt_bind_param($stmt, $types, ...$params);
if (!$bind_result) {
    $error_msg = mysqli_error($conn);
    error_log("Profile update - Failed to bind parameters. SQL: $sql, Error: $error_msg");
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to bind parameters: ' . $error_msg, 'debug' => 'Check PHP error log for details']);
    mysqli_stmt_close($stmt);
    exit;
}

if (mysqli_stmt_execute($stmt)) {
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    
    // Get updated user data
    $selectStmt = mysqli_prepare($conn, "SELECT user_id, first_name, last_name, email, phone, birthday, gender, nationality, address, profile_photo FROM users WHERE user_id = ?");
    if (!$selectStmt) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare select statement: ' . mysqli_error($conn)]);
        mysqli_stmt_close($stmt);
        exit;
    }
    
    mysqli_stmt_bind_param($selectStmt, "s", $user_id);
    mysqli_stmt_execute($selectStmt);
    $result = mysqli_stmt_get_result($selectStmt);
    $user = mysqli_fetch_assoc($result);
    
    if (!$user) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['error' => 'User not found after update']);
        mysqli_stmt_close($selectStmt);
        mysqli_stmt_close($stmt);
        exit;
    }
    
    // Log activity for profile updates
    $updated_fields = [];
    if ($first_name !== null || $last_name !== null) {
        $updated_fields[] = 'name';
    }
    if (isset($input['email'])) {
        $updated_fields[] = 'email';
    }
    if (isset($input['phone'])) {
        $updated_fields[] = 'phone';
    }
    if (isset($input['birthday'])) {
        $updated_fields[] = 'birthday';
    }
    if (isset($input['gender'])) {
        $updated_fields[] = 'gender';
    }
    if (isset($input['nationality'])) {
        $updated_fields[] = 'nationality';
    }
    if (isset($input['address'])) {
        $updated_fields[] = 'address';
    }
    
    // Log profile photo update separately
    if (isset($input['profileImage']) && !empty(trim($input['profileImage']))) {
        logProfilePhotoUpdate($conn, $user_id);
    }
    
    // Log personal info updates (only if there are fields to log)
    if (!empty($updated_fields)) {
        $fields_str = implode(', ', $updated_fields);
        logPersonalInfoUpdate($conn, $user_id, $fields_str);
    }
    
    $response = [
        'success' => true,
        'message' => 'Profile updated successfully',
        'affected_rows' => $affected_rows,
        'user' => [
            'userId' => $user['user_id'],
            'fullName' => trim($user['first_name'] . ' ' . $user['last_name']),
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
            'birthday' => $user['birthday'] ?? '',
            'gender' => $user['gender'] ?? '',
            'nationality' => $user['nationality'] ?? '',
            'address' => $user['address'] ?? '',
            'profileImage' => $user['profile_photo'] ?? ''
        ]
    ];
    
    ob_clean();
    echo json_encode($response);
    mysqli_stmt_close($selectStmt);
} else {
    $error_msg = mysqli_error($conn);
    error_log("Profile update - Failed to execute statement. SQL: $sql, Error: $error_msg, User ID: $user_id");
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update profile: ' . $error_msg, 'debug' => 'Check PHP error log for details']);
}

mysqli_stmt_close($stmt);
// Don't close connection here - let it close naturally or reuse it
?>

