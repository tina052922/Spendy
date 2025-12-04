<?php
// Start output buffering to prevent any HTML/errors from being output before JSON
ob_start();

// Use the same database connection as savings.html (db.php in root)
require_once __DIR__ . '/../includes/db.php';

// Set JSON header early
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$birthdayMonth = $_POST['birthday_month'] ?? '';
$birthdayDay = $_POST['birthday_day'] ?? '';
$birthdayYear = $_POST['birthday_year'] ?? '';
$gender = $_POST['gender'] ?? '';
$nationality = $_POST['nationality'] ?? '';

// Validation
$errors = [];

if (empty($firstName)) {
    $errors[] = 'First name is required';
}

if (empty($lastName)) {
    $errors[] = 'Last name is required';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (empty($password) || strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters';
}

if (!empty($errors)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Check database connection
if (!$conn) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check that XAMPP MySQL is running.']);
    exit;
}

// Check if email already exists - use user_id field to match database schema
$stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
if (!$stmt) {
    ob_clean();
    http_response_code(500);
    error_log("Signup prepare failed: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    mysqli_stmt_close($stmt);
    ob_clean();
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

mysqli_stmt_close($stmt);

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Generate unique user ID in format: userXXX
$stmt = mysqli_prepare($conn, "SELECT MAX(CAST(SUBSTRING(user_id, 5) AS UNSIGNED)) as max_num FROM users WHERE user_id LIKE 'user%'");
if (!$stmt) {
    ob_clean();
    http_response_code(500);
    error_log("Signup max user_id query failed: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$nextNum = ($row['max_num'] ?? 0) + 1;
$userId = 'user' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
mysqli_stmt_close($stmt);

// Prepare birthday as DATE format (YYYY-MM-DD) if all parts are provided
$birthdayDate = null;
if (!empty($birthdayYear) && !empty($birthdayMonth) && !empty($birthdayDay)) {
    // Convert month abbreviation (Jan, Feb, etc.) to number
    $monthMap = [
        'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6,
        'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
    ];
    
    $monthNum = is_numeric($birthdayMonth) ? (int)$birthdayMonth : ($monthMap[$birthdayMonth] ?? null);
    $dayInt = (int)$birthdayDay;
    $yearInt = (int)$birthdayYear;
    
    if ($monthNum && checkdate($monthNum, $dayInt, $yearInt)) {
        $birthdayDate = sprintf('%04d-%02d-%02d', $yearInt, $monthNum, $dayInt);
    }
}

// Insert user - match the schema from spendy_complete_database.sql
// Columns: user_id, first_name, last_name, email, password, birthday (DATE), phone, gender, nationality, address, profile_photo, google_id, created_at
$stmt = mysqli_prepare($conn, "INSERT INTO users (user_id, first_name, last_name, email, password, birthday, gender, nationality) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    ob_clean();
    http_response_code(500);
    error_log("Signup insert prepare failed: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

// Handle NULL birthday properly
$genderVal = !empty($gender) ? $gender : null;
$nationalityVal = !empty($nationality) ? $nationality : null;
$birthdayVal = !empty($birthdayDate) ? $birthdayDate : null;

mysqli_stmt_bind_param($stmt, "ssssssss", 
    $userId,
    $firstName, 
    $lastName, 
    $email, 
    $hashedPassword, 
    $birthdayVal, 
    $genderVal, 
    $nationalityVal
);

if (mysqli_stmt_execute($stmt)) {
    // Don't create session - user will log in separately
    mysqli_stmt_close($stmt);
    ob_clean();
    
    // Get the base path dynamically to handle case-insensitive folder names
    $scriptPath = $_SERVER['SCRIPT_NAME']; // e.g., /Spendy/api/signup.php or /SPENDY/api/signup.php
    $basePath = dirname(dirname($scriptPath)); // Get base folder path (e.g., /Spendy or /SPENDY)
    $redirectPath = $basePath . '/views/login.html';
    
    echo json_encode([
        'success' => true, 
        'message' => 'Account created successfully! Redirecting to login...',
        'redirect' => $redirectPath // Redirect to login page after signup
    ]);
    exit;
} else {
    $errorMsg = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    ob_clean();
    error_log("Signup execute error: " . $errorMsg);
    
    $errorMessage = 'Unable to create account. Please try again.';
    if (strpos($errorMsg, 'Duplicate entry') !== false) {
        $errorMessage = 'Email already registered. Please use a different email address.';
    } elseif (strpos($errorMsg, 'Connection') !== false) {
        $errorMessage = 'Database connection failed. Please check that XAMPP MySQL is running.';
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}
?>
