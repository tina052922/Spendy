<?php
/**
 * Google OAuth Configuration
 * 
 * IMPORTANT: To fix "redirect_uri_mismatch" error:
 * 1. Go to https://console.cloud.google.com/
 * 2. Select your project: spendy-app-480000
 * 3. Go to APIs & Services > Credentials
 * 4. Click on your OAuth 2.0 Client ID
 * 5. Under "Authorized redirect URIs", add the EXACT redirect URI that this script generates
 * 
 * To see what redirect URI is being used, visit: http://your-domain/Spendy/api/debug_redirect_uri.php
 * 
 * Common redirect URIs to add:
 *    - http://localhost/Spendy/api/google_callback.php (for XAMPP local testing)
 *    - http://localhost:80/Spendy/api/google_callback.php (if using port 80)
 *    - http://127.0.0.1/Spendy/api/google_callback.php (alternative localhost)
 *    - https://spendyapp.infinityfreeapp.com/api/google_callback.php (production)
 * 
 * NOTE: The redirect URI must match EXACTLY (including http vs https, port numbers, and path)
 */

// Google OAuth Configuration
// Credentials from Google Cloud Console - Project: spendy-app-480000
define('GOOGLE_CLIENT_ID', '1070397679346-avnbto7vmiao1e3urtsiheqq3bpb0a25.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Redirect URI - automatically detects environment and builds from actual request
// This ensures the redirect URI matches exactly what Google expects
function getRedirectUri() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Build the base URL
    $baseUrl = $protocol . '://' . $host;
    
    // Get the current script's directory to determine the project path
    // When called from api/google_auth.php, SCRIPT_NAME will be /Spendy/api/google_auth.php
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Extract the project directory (e.g., /Spendy)
    $projectPath = '';
    if (preg_match('#^/([^/]+)#', $scriptName, $matches)) {
        $projectPath = '/' . $matches[1];
    }
    
    // Build the redirect URI
    $redirectPath = $projectPath . '/api/google_callback.php';
    
    return $baseUrl . $redirectPath;
}

// Define the redirect URI
define('GOOGLE_REDIRECT_URI', getRedirectUri());

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

