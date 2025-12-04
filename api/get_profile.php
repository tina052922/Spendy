<?php
// Start output buffering FIRST to prevent any HTML/errors from being output before JSON
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Files are now in root directory - db.php is in same directory
require_once __DIR__ . '/../includes/db.php';

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : 'user002');

if (empty($user_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

// Output buffering already started above

// Prepare and execute query for user data
$stmt = mysqli_prepare($conn, "SELECT user_id, first_name, last_name, email, phone, birthday, gender, nationality, address, profile_photo FROM users WHERE user_id = ?");
if (!$stmt) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database query preparation failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    
    // Handle profile_photo - ensure it's properly returned
    $profile_photo = $user['profile_photo'] ?? null;
    
    // If profile_photo is NULL, empty, or just whitespace, return empty string
    if ($profile_photo === null || trim($profile_photo) === '' || $profile_photo === 'null') {
        $profile_photo = '';
    }
    
    // Get settings data (recovery email and phone)
    $recovery_email = '';
    $recovery_phone = '';
    $verified_email = false;
    
    $settingsStmt = mysqli_prepare($conn, "SELECT recovery_email, recovery_phone_1, verified_email FROM settings WHERE user_id = ?");
    if ($settingsStmt) {
        mysqli_stmt_bind_param($settingsStmt, "s", $user_id);
        mysqli_stmt_execute($settingsStmt);
        $settingsResult = mysqli_stmt_get_result($settingsStmt);
        if (mysqli_num_rows($settingsResult) > 0) {
            $settings = mysqli_fetch_assoc($settingsResult);
            $recovery_email = $settings['recovery_email'] ?? '';
            $recovery_phone = $settings['recovery_phone_1'] ?? '';
            $verified_email = (bool)($settings['verified_email'] ?? false);
        }
        mysqli_stmt_close($settingsStmt);
    }
    
    // Format the response to match what the frontend expects
    $response = [
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
        'profileImage' => $profile_photo,
        'recovery_email' => $recovery_email,
        'recovery_phone' => $recovery_phone,
        'verified_email' => $verified_email
    ];
    
    ob_clean();
    echo json_encode($response);
} else {
    ob_clean();
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
}

mysqli_stmt_close($stmt);
// Don't close connection - let it close naturally
?>

