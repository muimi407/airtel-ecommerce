<?php
require_once '../config/config.php';

// Destroy admin session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_name']);

redirect('login.php');
?>