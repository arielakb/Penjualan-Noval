<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') .
        '">';
}

function verify_csrf(): void
{
    $sent = (string) ($_POST['csrf_token'] ?? '');
    $stored = (string) ($_SESSION['csrf_token'] ?? '');

    if ($stored === '' || $sent === '' || !hash_equals($stored, $sent)) {
        http_response_code(419);
        exit('Permintaan ditolak: token keamanan tidak valid. Silakan kembali dan coba lagi.');
    }
}

function is_logged_in(): bool
{
    return isset($_SESSION['user'])
        && is_array($_SESSION['user'])
        && !empty($_SESSION['user']['id_pengguna']);
}

function current_user(): ?array
{
    return is_logged_in() ? $_SESSION['user'] : null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        $next = $_SERVER['REQUEST_URI'] ?? '/penjualan/';
        header('Location: /penjualan/login.php?next=' . urlencode($next));
        exit;
    }
}

function require_role(string ...$roles): void
{
    require_login();

    $user = current_user();
    $role = strtoupper((string) ($user['role'] ?? ''));

    $allowed = array_map('strtoupper', $roles);

    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('403 - Anda tidak memiliki hak akses ke halaman ini.');
    }
}
