<?php
/**
 * Database Configuration File for Spendy Application
 * 
 * This file contains database connection settings and utility functions
 * for connecting to MySQL database via XAMPP.
 * 
 * @author Spendy Development Team
 * @version 1.0
 */

// ============================================================================
// ERROR REPORTING CONFIGURATION
// ============================================================================
// Uncomment the line below to enable error reporting during development
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// For production, keep errors hidden and log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// ============================================================================
// DATABASE CONFIGURATION CONSTANTS
// ============================================================================

/**
 * Database host address
 * Default: 'localhost' for XAMPP local development
 * Change this if your MySQL server is on a different host
 */
define('DB_HOST', 'localhost');

/**
 * MySQL username
 * Default: 'root' for XAMPP
 * Change this to your MySQL username if different
 */
define('DB_USER', 'root');

/**
 * MySQL password
 * Default: '' (empty) for XAMPP
 * Change this to your MySQL password if you've set one
 */
define('DB_PASS', '');

/**
 * Database name
 * This should match the database created in phpMyAdmin
 * Default: 'spendy_db'
 */
define('DB_NAME', 'spendy_db');

/**
 * Database connection charset
 * UTF-8 ensures proper handling of special characters and international text
 */
define('DB_CHARSET', 'utf8mb4');

// ============================================================================
// TIMEZONE CONFIGURATION
// ============================================================================

/**
 * Set default timezone for PHP date/time functions
 * Asia/Manila is used for Philippine Peso (₱) currency context
 * Change this to your local timezone if needed
 */
date_default_timezone_set('Asia/Manila');

// ============================================================================
// DATABASE CONNECTION FUNCTION
// ============================================================================

/**
 * Creates and returns a MySQL database connection
 * 
 * This function establishes a connection to the MySQL database using
 * the configuration constants defined above. It includes comprehensive
 * error handling to help diagnose connection issues.
 * 
 * @param bool $throwException If true, throws exception instead of calling die() (useful for API endpoints)
 * @return mysqli|null Returns a mysqli connection object on success, null on failure
 * @throws Exception Throws exception if connection fails and $throwException is true
 * 
 * @example
 * $conn = getDBConnection();
 * $result = $conn->query("SELECT * FROM users");
 * 
 * @example For API endpoints:
 * try {
 *     $conn = getDBConnection(true);
 * } catch (Exception $e) {
 *     http_response_code(500);
 *     echo json_encode(['success' => false, 'message' => $e->getMessage()]);
 *     exit;
 * }
 */
function getDBConnection($throwException = false) {
    // Initialize connection variable
    $conn = null;
    
    try {
        // Attempt to create a new MySQLi connection
        // mysqli is the improved MySQL extension for PHP
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check if connection was successful
        if ($conn->connect_error) {
            // Log the connection error for debugging
            error_log("Database Connection Error: " . $conn->connect_error);
            
            // Throw exception with detailed error message
            throw new Exception(
                "Database connection failed: " . $conn->connect_error . 
                " (Error Code: " . $conn->connect_errno . ")"
            );
        }
        
        // Set the character set to UTF-8 to handle special characters properly
        // This prevents issues with international characters, emojis, etc.
        if (!$conn->set_charset(DB_CHARSET)) {
            error_log("Error loading character set " . DB_CHARSET . ": " . $conn->error);
            // Don't throw exception here as connection is still usable
            // but log the warning for debugging
        }
        
        // Return the successful connection
        return $conn;
        
    } catch (Exception $e) {
        // Log the exception details
        error_log("Exception in getDBConnection(): " . $e->getMessage());
        
        // If $throwException is true, re-throw the exception for API endpoints to handle
        if ($throwException) {
            throw $e;
        }
        
        // Otherwise, use the old behavior (die) for backward compatibility
        // In production, return a user-friendly error message
        // In development, you might want to show the full error
        if (ini_get('display_errors')) {
            die("Database Connection Error: " . $e->getMessage());
        } else {
            // Generic error message for production
            die("Database connection error. Please contact the administrator.");
        }
    }
}

/**
 * Validates database connection configuration
 * 
 * This function checks if all required database constants are defined
 * and not empty (except password which can be empty for XAMPP)
 * 
 * @return bool Returns true if configuration is valid, false otherwise
 */
function validateDBConfig() {
    $errors = [];
    
    // Check if all required constants are defined
    if (!defined('DB_HOST') || empty(DB_HOST)) {
        $errors[] = "DB_HOST is not defined or empty";
    }
    
    if (!defined('DB_USER') || empty(DB_USER)) {
        $errors[] = "DB_USER is not defined or empty";
    }
    
    // Password can be empty for XAMPP default setup
    if (!defined('DB_PASS')) {
        $errors[] = "DB_PASS is not defined";
    }
    
    if (!defined('DB_NAME') || empty(DB_NAME)) {
        $errors[] = "DB_NAME is not defined or empty";
    }
    
    // Log errors if any
    if (!empty($errors)) {
        foreach ($errors as $error) {
            error_log("Database Configuration Error: " . $error);
        }
        return false;
    }
    
    return true;
}

/**
 * Tests the database connection
 * 
 * This function attempts to connect to the database and returns
 * a status message. Useful for diagnostic purposes.
 * 
 * @return array Returns array with 'success' (bool) and 'message' (string)
 */
function testDBConnection() {
    // First validate configuration
    if (!validateDBConfig()) {
        return [
            'success' => false,
            'message' => 'Database configuration is invalid. Please check config.php'
        ];
    }
    
    try {
        $conn = getDBConnection();
        
        // Test the connection with a simple query
        $result = $conn->query("SELECT 1");
        
        if ($result) {
            $conn->close();
            return [
                'success' => true,
                'message' => 'Database connection successful!'
            ];
        } else {
            $conn->close();
            return [
                'success' => false,
                'message' => 'Connection established but query test failed'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Connection failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Ensures a user exists in the users table
 * 
 * This function checks if a user with the given user_id exists in the database.
 * If the user doesn't exist, it creates a new user record. This prevents
 * foreign key constraint errors when inserting income or expense records.
 * 
 * @param mysqli $conn Database connection object
 * @param string $user_id The user ID to check/create
 * @param string $username Optional username (default: 'User')
 * @param string $email Optional email (default: null)
 * @return bool Returns true if user exists or was created successfully, false otherwise
 */
function ensureUserExists($conn, $user_id, $username = 'User', $email = null) {
    // Sanitize input
    $user_id = $conn->real_escape_string($user_id);
    $username = $conn->real_escape_string($username);
    $email = $email ? $conn->real_escape_string($email) : null;
    
    // Check if user exists
    $check_sql = "SELECT user_id FROM users WHERE user_id = '$user_id' LIMIT 1";
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        // User exists, return true
        return true;
    }
    
    // User doesn't exist, create it
    $email_part = $email ? "'$email'" : "NULL";
    $insert_sql = "INSERT INTO users (user_id, username, email) 
                   VALUES ('$user_id', '$username', $email_part)
                   ON DUPLICATE KEY UPDATE username = '$username'";
    
    if ($conn->query($insert_sql)) {
        error_log("Created new user: $user_id");
        return true;
    } else {
        error_log("Error creating user $user_id: " . $conn->error);
        return false;
    }
}

// ============================================================================
// INITIALIZATION
// ============================================================================

// Validate configuration on file load (optional - can be commented out for performance)
// Uncomment the line below to validate config on every page load
// validateDBConfig();
?>

