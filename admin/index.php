<?php
require_once '../config/config.php';

// Check if admin is logged in and redirect accordingly
if (isAdmin()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
?>