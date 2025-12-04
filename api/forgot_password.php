<?php
header('Content-Type: application/json');

// Start session (needed for some operations)
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

// Get email from request (can be POST or JSON)
$email = '';
if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
}

// Validation
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    $conn->close();
    exit;
}

// Check if user exists by main email first
$email_escaped = $conn->real_escape_string($email);
$sql = "SELECT user_id, first_name, email FROM users WHERE email = '$email_escaped'";
$result = $conn->query($sql);

$user = null;
$targetEmail = $email; // Default to the email entered

if ($result && $result->num_rows > 0) {
    // User found by main email
    $user = $result->fetch_assoc();
    $targetEmail = $user['email']; // Use main email
} else {
    // User not found by main email, check recovery email
    $recoverySql = "SELECT s.user_id, s.recovery_email, u.first_name, u.email 
                    FROM settings s 
                    INNER JOIN users u ON s.user_id = u.user_id 
                    WHERE s.recovery_email = '$email_escaped'";
    $recoveryResult = $conn->query($recoverySql);
    
    if ($recoveryResult && $recoveryResult->num_rows > 0) {
        // User found by recovery email
        $recoveryData = $recoveryResult->fetch_assoc();
        $user = [
            'user_id' => $recoveryData['user_id'],
            'first_name' => $recoveryData['first_name'],
            'email' => $recoveryData['email'] // Main email from users table
        ];
        $targetEmail = $recoveryData['recovery_email']; // Use recovery email for sending reset link
    }
}

// Always return success message for security (don't reveal if email exists)
$resetLink = null; // Initialize resetLink variable
if ($user) {
    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
    
    // Store reset token in database
    // Use INSERT ... ON DUPLICATE KEY UPDATE to handle existing tokens
    $user_id_escaped = $conn->real_escape_string($user['user_id']);
    $token_escaped = $conn->real_escape_string($resetToken);
    $expiry_escaped = $conn->real_escape_string($tokenExpiry);
    
    $tokenSql = "INSERT INTO password_reset_tokens (user_id, token, expires_at, used) 
                 VALUES ('$user_id_escaped', '$token_escaped', '$expiry_escaped', 0) 
                 ON DUPLICATE KEY UPDATE token = '$token_escaped', expires_at = '$expiry_escaped', used = 0";
    
    if ($conn->query($tokenSql)) {
        // In a real application, you would send an email here to $targetEmail
        // For development: log the reset link (check PHP error log)
        // Get the base path dynamically to handle case-insensitive folder names
        $scriptPath = $_SERVER['SCRIPT_NAME']; // e.g., /Spendy/api/forgot_password.php or /SPENDY/api/forgot_password.php
        $basePath = dirname(dirname($scriptPath)); // Get base folder path (e.g., /Spendy or /SPENDY)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $resetLink = $protocol . "://" . $host . $basePath . "/views/reset_password.html?token=" . $resetToken;
        
        // Log which email was used (main or recovery)
        $emailType = ($targetEmail === $user['email']) ? 'main email' : 'recovery email';
        error_log("Password reset link for user {$user['user_id']} ({$user['first_name']}) sent to {$emailType}: {$targetEmail}");
        error_log("Reset link: " . $resetLink);
        
        // For development, you can also return the link (REMOVE IN PRODUCTION!)
        // In production, send email to $targetEmail instead
    }
}

// Always return success message (security best practice)
echo json_encode([
    'success' => true,
    'message' => 'If an account with that email exists, a password reset link has been sent.',
    // FOR DEVELOPMENT ONLY - Remove in production!
    'dev_reset_link' => isset($resetLink) ? $resetLink : null
]);

$conn->close();
?>
