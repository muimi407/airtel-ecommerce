<?php
require_once 'config/config.php';

// Destroy session
session_destroy();
redirect('index.php');
?>