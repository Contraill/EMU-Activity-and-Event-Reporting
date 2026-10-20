<?php
/**
 * EMU Activity & Event Reporting Portal
 * Handles the login form (POST) and logout (?logout).
 */

require 'auth.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
    $user = DEMO_USERS[trim($_POST['username'] ?? '')] ?? null;
    if ($user && hash_equals($user[0], $_POST['password'] ?? '')) {
        session_regenerate_id(true);
        $_SESSION['role'] = $user[1];
        header('Location: ' . ($user[1] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
        exit;
    }
}

header('Location: index.php?login=failed');
