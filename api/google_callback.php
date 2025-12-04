<?php
/**
 * Google OAuth Callback Handler
 * This file handles the callback from Google after user authorizes the app
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/google_oauth.php';

/**
 * Helper function to get redirect path dynamically
 */
function getRedirectPath($relativePath) {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $basePath = dirname(dirname($scriptPath));
    return $basePath . $relativePath;
}

// Check if we have an authorization code
if (!isset($_GET['code'])) {
    // Error occurred or user denied access
    $error = $_GET['error'] ?? 'Unknown error';
    header('Location: ' . getRedirectPath('/views/login.html?error=' . urlencode('Google sign-in failed: ' . $error)));
    exit;
}

$code = $_GET['code'];

// Exchange authorization code for access token
$tokenData = array(
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Google token exchange failed: " . $tokenResponse);
    header('Location: ' . getRedirectPath('/views/login.html?error=' . urlencode('Failed to authenticate with Google')));
    exit;
}

$tokenData = json_decode($tokenResponse, true);

if (!isset($tokenData['access_token'])) {
    error_log("No access token in response: " . $tokenResponse);
    header('Location: ' . getRedirectPath('/views/login.html?error=' . urlencode('Failed to get access token from Google')));
    exit;
}

$accessToken = $tokenData['access_token'];

// Get user information from Google
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_USERINFO_URL . '?access_token=' . urlencode($accessToken));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$userInfoResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Google userinfo failed: " . $userInfoResponse);
    header('Location: ' . getRedirectPath('/views/login.html?error=' . urlencode('Failed to get user information from Google')));
    exit;
}

$userInfo = json_decode($userInfoResponse, true);

if (!isset($userInfo['id']) || !isset($userInfo['email'])) {
    error_log("Invalid user info: " . $userInfoResponse);
    header('Location: ' . getRedirectPath('/views/login.html?error=' . urlencode('Invalid user information from Google')));
    exit;
}

$googleId = $userInfo['id'];
$email = $userInfo['email'];
$firstName = $userInfo['given_name'] ?? '';
$lastName = $userInfo['family_name'] ?? '';
$profilePicture = $userInfo['picture'] ?? null;

// Check if user exists with this Google ID
$stmt = mysqli_prepare($conn, "SELECT user_id, email, first_name, last_name, profile_photo FROM users WHERE google_id = ?");
if (!$stmt) {
    error_log("Database prepare failed: " . mysqli_error($conn));
    header('Location: ' . getRedirectPath('/views/login.html?error=' . urlencode('Database error occurred')));
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $googleId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($user) {
    // User exists - log them in
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
    
    // Update profile picture if available and not already set
    if ($profilePicture && empty($user['profile_photo'])) {
        $updateStmt = mysqli_prepare($conn, "UPDATE users SET profile_photo = ? WHERE user_id = ?");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "ss", $profilePicture, $user['user_id']);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
    }
    
    // Redirect to dashboard expenses page
    header('Location: ' . getRedirectPath('/views/DashboardExpenses.html'));
    exit;
} else {
    // Check if user exists with this email (but no Google ID)
    $stmt = mysqli_prepare($conn, "SELECT user_id, email, first_name, last_name FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Database prepare failed: " . mysqli_error($conn));
        header('Location: ' . getRedirectPath('/views/login.html?error=' . urlencode('Database error occurred')));
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existingUser = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($existingUser) {
        // User exists with this email - link Google account
        $updateStmt = mysqli_prepare($conn, "UPDATE users SET google_id = ?, profile_photo = ? WHERE user_id = ?");
        if ($updateStmt) {
            $profilePic = $profilePicture ?? $existingUser['profile_photo'] ?? null;
            mysqli_stmt_bind_param($updateStmt, "sss", $googleId, $profilePic, $existingUser['user_id']);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
        
        $_SESSION['user_id'] = $existingUser['user_id'];
        $_SESSION['user_email'] = $existingUser['email'];
        $_SESSION['user_name'] = ($existingUser['first_name'] ?? '') . ' ' . ($existingUser['last_name'] ?? '');
        
        header('Location: ' . getRedirectPath('/views/DashboardExpenses.html'));
        exit;
    } else {
        // New user - create account
        // Generate unique user ID
        $stmt = mysqli_prepare($conn, "SELECT MAX(CAST(SUBSTRING(user_id, 5) AS UNSIGNED)) as max_num FROM users WHERE user_id LIKE 'user%'");
        if (!$stmt) {
            error_log("Database prepare failed: " . mysqli_error($conn));
            header('Location: ' . getRedirectPath('/views/SignUp.html?error=' . urlencode('Database error occurred')));
            exit;
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $nextNum = ($row['max_num'] ?? 0) + 1;
        $userId = 'user' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        mysqli_stmt_close($stmt);
        
        // Insert new user
        $insertStmt = mysqli_prepare($conn, "INSERT INTO users (user_id, first_name, last_name, email, google_id, profile_photo, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if (!$insertStmt) {
            error_log("Database prepare failed: " . mysqli_error($conn));
            header('Location: ' . getRedirectPath('/views/SignUp.html?error=' . urlencode('Database error occurred')));
            exit;
        }
        
        mysqli_stmt_bind_param($insertStmt, "ssssss", 
            $userId,
            $firstName,
            $lastName,
            $email,
            $googleId,
            $profilePicture
        );
        
        if (mysqli_stmt_execute($insertStmt)) {
            mysqli_stmt_close($insertStmt);
            
            // Set session
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            
            // Redirect to dashboard expenses page
            header('Location: ' . getRedirectPath('/views/DashboardExpenses.html'));
            exit;
        } else {
            error_log("User insert failed: " . mysqli_error($conn));
            mysqli_stmt_close($insertStmt);
            header('Location: ' . getRedirectPath('/views/SignUp.html?error=' . urlencode('Failed to create account')));
            exit;
        }
    }
}
?>

