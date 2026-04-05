<?php
require_once __DIR__ . '/../config.php';

function sessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    sessionStart();
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function login(string $username, string $password): bool {
    require_once __DIR__ . '/db.php';
    sessionStart();
    $user = dbFetch("SELECT * FROM users WHERE username = ?", [$username]);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}

function logout(): void {
    sessionStart();
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

function currentUser(): ?array {
    sessionStart();
    if (!isLoggedIn()) return null;
    return ['id' => $_SESSION['user_id'], 'username' => $_SESSION['username']];
}
