<?php
// Database configuration
$servername = "localhost"; // Server name (usually "localhost")
$username = "root"; // Replace with your MySQL username (e.g., "root")
$password = ""; // Replace with your MySQL password
$dbname = "jiranidb"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set charset to UTF-8
$conn->set_charset("utf8");

// You can add other global settings here (e.g., session configurations)
?>