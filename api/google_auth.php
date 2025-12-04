<?php
/**
 * Google OAuth Authorization Redirect
 * This file redirects users to Google's OAuth consent screen
 */

require_once __DIR__ . '/../includes/google_oauth.php';

// Get the authorization URL and redirect
$authUrl = getGoogleAuthUrl();
header('Location: ' . $authUrl);
exit;
?>

