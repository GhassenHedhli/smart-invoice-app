<?php
// index.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Otherwise, go to dashboard
header("Location: dashboard.php");
exit;
?>
