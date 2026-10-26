<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'doi_pc');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change if you have a password

// Create database connection
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . 
                "<br><br>Make sure you've:<br>1. Created the database<br>2. Run the SQL file in phpMyAdmin<br>3. Updated config.php with correct credentials");
        }
    }
    
    return $pdo;
}

// Check if admin is logged in
function requireAdmin() {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('Location: login.php');
        exit;
    }
}
