<?php
session_start();

/**
 * Return current user or null
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user is logged in
 */
function require_auth() {
    if (!isset($_SESSION['user'])) {
        header("Location: /../login.php");
        exit();
    }
}

/**
 * Require specific role(s) to access a page
 * @param string|array $roles
 */
function require_role($roles) {
    require_auth();
    $user = current_user();

    if (is_string($roles)) {
        $roles = [$roles];
    }

    if (!in_array($user['role'], $roles)) {
        http_response_code(403);
        die("Access denied: insufficient permissions.");
    }
}

/**
 * Check role quickly
 */
function is_role($role) {
    $user = current_user();
    return $user && $user['role'] === $role;
}
