<?php
/**
 * Google OAuth Configuration
 * 
 * To get your Client ID and Client Secret:
 * 1. Go to https://console.cloud.google.com/
 * 2. Select your project: spendy-app-480000
 * 3. Go to APIs & Services > Credentials
 * 4. Create OAuth 2.0 Client ID (if not already created)
 * 5. Add authorized redirect URIs:
 *    - https://spendyapp.infinityfreeapp.com/api/google_callback.php
 *    - http://localhost/Spendy/api/google_callback.php (for local testing)
 */

// Google OAuth Configuration
// Credentials from Google Cloud Console - Project: spendy-app-480000
define('GOOGLE_CLIENT_ID', '1070397679346-avnbto7vmiao1e3urtsiheqq3bpb0a25.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Redirect URI - automatically detects environment
// For production, use: https://spendyapp.infinityfreeapp.com/api/google_callback.php
// For local testing, use: http://localhost/Spendy/api/google_callback.php
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        // Local development
        define('GOOGLE_REDIRECT_URI', 'http://localhost/Spendy/api/google_callback.php');
    } else {
        // Production (InfinityFree)
        define('GOOGLE_REDIRECT_URI', 'https://spendyapp.infinityfreeapp.com/api/google_callback.php');
    }
} else {
    // Default to production if we can't detect
    define('GOOGLE_REDIRECT_URI', 'https://spendyapp.infinityfreeapp.com/api/google_callback.php');
}

// Google OAuth endpoints
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

// Scopes - what information we want from Google
define('GOOGLE_SCOPES', 'email profile');

/**
 * Get Google OAuth authorization URL
 */
function getGoogleAuthUrl() {
    $params = array(
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => GOOGLE_SCOPES,
        'access_type' => 'online',
        'prompt' => 'select_account'
    );
    
    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}
?>

