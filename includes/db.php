<?php
// Suppress error display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "spendy_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    // Don't output anything - let the calling script handle the error
    $conn = null;
}
