<?php
/**
 * EMU Activity & Event Reporting Portal
 * Server-side session login, role checks and CSRF tokens shared by all pages.
 */

session_start();

// Demo accounts for the course prototype: username => [password, role].
// shortcut: plain demo passwords in code, replace with a user store and password_hash() before real use.
const DEMO_USERS = [
    'admin' => ['admin', 'admin'],
    'user'  => ['user', 'user'],
];

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

// Stops the request unless the session has one of $roles; POSTs also need a valid CSRF token.
function require_role(array $roles) {
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        header('Location: index.php?login=required');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid form token. Reload the page and try again.');
    }
}
