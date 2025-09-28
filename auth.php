<?php
session_start();
include_once 'config.php';

// -------------------------
// Check if user is logged in
// -------------------------
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

// -------------------------
// Fetch current user with role and permissions
// -------------------------
$stmt = $conn->prepare("
    SELECT u.*, r.name as role_name, r.permissions 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    WHERE u.id = :user_id
");
$stmt->execute([':user_id' => $_SESSION['user']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found, destroy session and redirect
if(!$currentUser){
    session_destroy();
    header("Location: login.php");
    exit;
}

// -------------------------
// Permission check function
// -------------------------
if(!function_exists('hasPermission')) {
    function hasPermission($permission) {
        global $currentUser;
        
        if($currentUser['role_name'] === 'Administrator') {
            return true;
        }
        
        $permissions = explode(',', $currentUser['permissions']);
        return in_array($permission, $permissions) || in_array('all', $permissions);
    }
}
?>
