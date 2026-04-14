<?php
// includes/auth_check.php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool { return isset($_SESSION['user_id']); }

function requireLogin(string $to = '/index.php'): void {
    if (!isLoggedIn()) { header("Location: $to"); exit; }
}

function requireAdmin(string $to = '/history.php'): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') { header("Location: $to"); exit; }
}

function currentUser(): array {
    return [
        'id'           => $_SESSION['user_id']           ?? 0,
        'name'         => $_SESSION['user_name']         ?? '',
        'role'         => $_SESSION['user_role']         ?? '',
        'email'        => $_SESSION['user_email']        ?? '',
        'avatar_color' => $_SESSION['user_avatar_color'] ?? '#E53935',
    ];
}

function isAdmin(): bool { return ($_SESSION['user_role'] ?? '') === 'admin'; }
