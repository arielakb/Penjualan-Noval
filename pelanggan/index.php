<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_login();

$keyword = trim((string) ($_GET['q'] ?? ''));
$flash = get_flash();
$error = '';
$pelanggan = [];

try {
    if ($keyword !== '') {
        $stmt = $pdo->prepare('CALL sp_pelanggan_cari(:keyword)');
        $stmt->execute([':keyword' => $keyword]);
    } else {
        $stmt = $pdo->query('CALL sp_pelanggan_tampil()');
    }

    $pelanggan = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = db_message($e, 'Data pelanggan gagal dimuat.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Pelanggan</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container">
    <nav class="topnav">
        <a href="../index.php">← Dashboard</a>
        <span>Data Pelanggan</span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">MASTER DATA</p>
            <h1>Data Pelanggan</h1>
            <p class="muted">CRUD dijalankan melalui stored procedure di database.</p>
        </div>
        <a class="button primary" href="tambah.php">+ Tambah Pelanggan</a>
    </header>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="card">
        <form class="search" method="get">
            <input
                type="search"
                name="q"
                value="<?= e($keyword) ?>"
                placeholder="Cari nama, email, atau no. HP..."
                autocomplete="off"
            >
            <button class="button" type="submit">Cari</button>
            <?php if ($keyword !== ''): ?>
                <a class="button ghost" href="index.php">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Alamat</th>
                    <th>Dibuat</th>
                    <th class="right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$pelanggan): ?>
                    <tr>
                        <td colspan="6" class="empty">Data pelanggan tidak ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pelanggan as $row): ?>
                        <tr>
                            <td>#<?= e((string) $row['id_pelanggan']) ?></td>
                            <td><strong><?= e($row['nama']) ?></strong></td>
                            <td>
                                <div><?= e($row['no_hp'] ?: '-') ?></div>
                                <div class="muted small-text"><?= e($row['email'] ?: '-') ?></div>
                            </td>
                            <td><?= e($row['alamat'] ?: '-') ?></td>
                            <td><?= e(date('d-m-Y H:i', strtotime($row['created_at']))) ?></td>
                            <td>
                                <div class="row-actions">
                                    <a class="button small" href="edit.php?id=<?= urlencode((string) $row['id_pelanggan']) ?>">
                                        Edit
                                    </a>
                                    <form
                                        method="post"
                                        action="hapus.php"
                                        onsubmit="return confirm('Yakin ingin menghapus pelanggan ini?');"
                                    >
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $row['id_pelanggan']) ?>">
                                        <button class="button small danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
