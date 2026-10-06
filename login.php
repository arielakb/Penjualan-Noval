<?php
declare(strict_types=1);
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';
$next = trim((string) ($_GET['next'] ?? $_POST['next'] ?? 'index.php'));

if ($next === '' || str_starts_with($next, 'http://') || str_starts_with($next, 'https://')) {
    $next = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare('CALL sp_auth_cari_username(:username)');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            $stmt->closeCursor();

            if (
                !$user ||
                (int) $user['aktif'] !== 1 ||
                !password_verify($password, (string) $user['password_hash'])
            ) {
                $error = 'Username atau password salah.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id_pengguna' => (int) $user['id_pengguna'],
                    'username' => (string) $user['username'],
                    'nama_lengkap' => (string) $user['nama_lengkap'],
                    'role' => (string) $user['role'],
                ];

                unset($_SESSION['csrf_token']);

                header('Location: ' . $next);
                exit;
            }
        } catch (PDOException $e) {
            $error = db_message(
                $e,
                'Login belum dapat digunakan. Pastikan SQL Step 5 sudah di-import.'
            );
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Aplikasi Penjualan</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="login-shell">
    <section class="login-panel">
        <div class="login-brand">
            <p class="eyebrow">SECURE ACCESS</p>
            <h1>Aplikasi Penjualan</h1>
            <p>
                Masuk menggunakan akun aplikasi. Password diverifikasi dengan
                <code>password_verify()</code> terhadap hash di database.
            </p>
        </div>

        <div class="login-card">
            <p class="eyebrow">LOGIN</p>
            <h2>Selamat datang</h2>

            <?php if ($error !== ''): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="form-grid" autocomplete="off">
                <?= csrf_input() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">

                <label>
                    <span>Username</span>
                    <input
                        type="text"
                        name="username"
                        maxlength="50"
                        value="<?= e($username) ?>"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </label>

                <label>
                    <span>Password</span>
                    <input
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </label>

                <button class="button primary full-width" type="submit">Masuk</button>
            </form>

            <div class="demo-accounts">
                <strong>Akun demo</strong>
                <span>Admin: <code>admin</code> / <code>Admin#2026</code></span>
                <span>Kasir: <code>kasir</code> / <code>Kasir#2026</code></span>
            </div>
        </div>
    </section>
</main>
</body>
</html>
