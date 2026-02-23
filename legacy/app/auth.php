<?php
// app/auth.php
require_once __DIR__ . '/config.php';

session_start();

function requireAuth() {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        // Redirect to login page
        header("Location: /login.php");
        exit;
    }
}

function login($password) {
    if ($password === APP_PASSWORD) {
        $_SESSION['authenticated'] = true;
        return true;
    }
    return false;
}

function logout() {
    $_SESSION = [];
    session_destroy();
}
