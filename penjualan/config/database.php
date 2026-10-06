<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$host = '127.0.0.1';
$port = '3306';
$db   = 'db_penjualan';
$user = 'app_penjualan';
$pass = 'AppPenjualan#2026';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit(
        '<h2>Koneksi database gagal</h2>' .
        '<p>Pastikan MySQL di XAMPP aktif, db_penjualan sudah di-import, ' .
        'dan port MySQL sesuai dengan config/database.php.</p>'
    );
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Mengambil pesan dari SIGNAL SQLSTATE '45000' tanpa menampilkan
 * detail koneksi/SQL sensitif ke pengguna.
 */
function db_message(Throwable $e, string $fallback = 'Terjadi kesalahan pada database.'): string
{
    $message = $e->getMessage();

    if (preg_match('/1644\s+(.+)$/', $message, $match)) {
        return trim($match[1]);
    }

    if (preg_match('/SQLSTATE\[45000\].*?:\s*\d+\s+(.+)$/', $message, $match)) {
        return trim($match[1]);
    }

    return $fallback;
}
