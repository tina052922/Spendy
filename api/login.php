<?php
// Start session FIRST, before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';

// Validation
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit;
}

if (empty($password)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

// Check database connection
if (!$conn) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check that XAMPP MySQL is running.']);
    exit;
}

// Get user by email - use user_id field to match database schema
$stmt = mysqli_prepare($conn, "SELECT user_id, email, password, first_name, last_name FROM users WHERE email = ?");
if (!$stmt) {
    ob_clean();
    http_response_code(500);
    error_log("Login prepare failed: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    mysqli_stmt_close($stmt);
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

$user = mysqli_fetch_assoc($result);

// Verify password - handle both hashed and plaintext passwords for existing users
$passwordValid = false;
$storedPassword = $user['password']; // Don't trim - might be intentional spaces
$inputPassword = $password; // Don't trim initially - check both trimmed and untrimmed

// Check if password is hashed (bcrypt starts with $2y$, $2a$, or $2b$)
$isHashed = (substr($storedPassword, 0, 4) === '$2y$' || 
             substr($storedPassword, 0, 4) === '$2a$' || 
             substr($storedPassword, 0, 4) === '$2b$');

if ($isHashed) {
    // Password is hashed, use password_verify
    if (password_verify($inputPassword, $storedPassword)) {
        $passwordValid = true;
    } elseif (password_verify(trim($inputPassword), $storedPassword)) {
        // Try trimmed version too
        $passwordValid = true;
        $inputPassword = trim($inputPassword);
    }
} else {
    // Password is plaintext, try exact match first, then trimmed
    if ($storedPassword === $inputPassword) {
        $passwordValid = true;
    } elseif (trim($storedPassword) === trim($inputPassword)) {
        $passwordValid = true;
    }
}

if (!$passwordValid) {
    mysqli_stmt_close($stmt);
    ob_clean();
    http_response_code(401);
    
    // Log the failure for debugging (check PHP error log)
    error_log("Login failed for email: $email");
    error_log("Input password: '" . substr($inputPassword, 0, 20) . "' (length: " . strlen($inputPassword) . ")");
    error_log("Stored password: '" . substr($storedPassword, 0, 20) . "' (length: " . strlen($storedPassword) . ")");
    error_log("Is hashed: " . ($isHashed ? 'yes' : 'no'));
    
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// Session already started at the top - just set session variables
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');

// Set remember me cookie if checked
if ($rememberMe) {
    $cookieValue = base64_encode($user['user_id'] . ':' . hash('sha256', $user['password']));
    setcookie('remember_me', $cookieValue, time() + (30 * 24 * 60 * 60), '/'); // 30 days
}

mysqli_stmt_close($stmt);

// Clear buffer and return success
ob_clean();

// Get the base path dynamically to handle case-insensitive folder names
$scriptPath = $_SERVER['SCRIPT_NAME']; // e.g., /Spendy/api/login.php or /SPENDY/api/login.php
$basePath = dirname(dirname($scriptPath)); // Get base folder path (e.g., /Spendy or /SPENDY)
$redirectPath = $basePath . '/views/DashboardExpenses.html';

echo json_encode([
    'success' => true,
    'message' => 'Login successful! Redirecting...',
    'redirect' => $redirectPath // Redirect to Dashboard Expenses (default landing page)
]);
exit;
?>
