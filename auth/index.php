<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_role('ADMIN');

$error = '';
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = filter_input(INPUT_POST, 'id_pengguna', FILTER_VALIDATE_INT);
    $aktif = filter_input(INPUT_POST, 'aktif', FILTER_VALIDATE_INT);

    if (!$id || !in_array($aktif, [0,1], true)) {
        $error = 'Data pengguna tidak valid.';
    } elseif ((int) current_user()['id_pengguna'] === $id && $aktif === 0) {
        $error = 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'CALL sp_pengguna_ubah_status(:id_pengguna, :aktif)'
            );
            $stmt->execute([
                ':id_pengguna' => $id,
                ':aktif' => $aktif,
            ]);
            $stmt->closeCursor();

            set_flash('success', 'Status pengguna berhasil diperbarui.');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = db_message($e, 'Status pengguna gagal diperbarui.');
        }
    }
}

$users = [];

try {
    $stmt = $pdo->query('CALL sp_pengguna_tampil()');
    $users = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = db_message($e, 'Data pengguna gagal dimuat.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengguna Aplikasi</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container">
    <nav class="topnav">
        <a href="../index.php">← Dashboard</a>
        <span>Pengguna Aplikasi</span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">ADMINISTRASI</p>
            <h1>Pengguna Aplikasi</h1>
            <p class="muted">Halaman ini hanya dapat diakses role ADMIN.</p>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td>#<?= e((string) $row['id_pengguna']) ?></td>
                        <td><strong><?= e($row['username']) ?></strong></td>
                        <td><?= e($row['nama_lengkap']) ?></td>
                        <td><span class="badge warning"><?= e($row['role']) ?></span></td>
                        <td>
                            <?php if ((int) $row['aktif'] === 1): ?>
                                <span class="badge success">AKTIF</span>
                            <?php else: ?>
                                <span class="badge danger">NONAKTIF</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <?php if ((int) $row['id_pengguna'] !== (int) current_user()['id_pengguna']): ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id_pengguna" value="<?= e((string) $row['id_pengguna']) ?>">
                                        <input type="hidden" name="aktif" value="<?= (int) $row['aktif'] === 1 ? '0' : '1' ?>">
                                        <button
                                            class="button small <?= (int) $row['aktif'] === 1 ? 'danger' : 'primary' ?>"
                                            type="submit"
                                        >
                                            <?= (int) $row['aktif'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">Akun aktif</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
