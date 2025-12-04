<?php
/**
 * Debug script to show the current redirect URI being used
 * This helps you identify what redirect URI to add to Google Cloud Console
 */

require_once __DIR__ . '/../includes/google_oauth.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google OAuth Redirect URI Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
        .redirect-uri {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 16px;
            word-break: break-all;
        }
        .instructions {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 25px;
        }
        .instructions li {
            margin: 8px 0;
        }
        .copy-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        .copy-btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Google OAuth Redirect URI Debug</h1>
        
        <div class="info-box">
            <strong>Current Redirect URI:</strong>
            <div class="redirect-uri" id="redirectUri">
                <?php echo htmlspecialchars(GOOGLE_REDIRECT_URI); ?>
            </div>
            <button class="copy-btn" onclick="copyToClipboard()">Copy to Clipboard</button>
        </div>
        
        <div class="instructions">
            <h2>How to Fix "redirect_uri_mismatch" Error:</h2>
            <ol>
                <li>Copy the redirect URI shown above</li>
                <li>Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console Credentials</a></li>
                <li>Select your project: <strong>spendy-app-480000</strong></li>
                <li>Click on your OAuth 2.0 Client ID</li>
                <li>Scroll down to "Authorized redirect URIs"</li>
                <li>Click "ADD URI" and paste the redirect URI exactly as shown above</li>
                <li>Click "SAVE"</li>
                <li>Wait a few minutes for changes to propagate, then try again</li>
            </ol>
        </div>
        
        <div class="info-box" style="margin-top: 20px;">
            <strong>Server Information:</strong><br>
            Protocol: <?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'HTTPS' : 'HTTP'; ?><br>
            Host: <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Not set'); ?><br>
            Script Name: <?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'Not set'); ?><br>
            Request URI: <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Not set'); ?>
        </div>
    </div>
    
    <script>
        function copyToClipboard() {
            const uri = document.getElementById('redirectUri').textContent;
            navigator.clipboard.writeText(uri).then(function() {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.style.background = '#45a049';
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.style.background = '#4CAF50';
                }, 2000);
            });
        }
    </script>
</body>
</html>

