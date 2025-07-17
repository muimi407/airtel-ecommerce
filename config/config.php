<?php
session_start();

// Site configuration
define('SITE_NAME', 'Airtel Kenya Store');
define('SITE_URL', 'http://localhost/airtel-ecommerce');
define('ADMIN_EMAIL', 'admin@airtel.com');

// Database configuration
require_once 'database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatPrice($price) {
    return 'KSh ' . number_format($price, 2);
}
?>