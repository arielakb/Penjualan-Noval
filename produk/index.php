<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_login();

$keyword = trim((string) ($_GET['q'] ?? ''));
$flash = get_flash();
$error = '';
$produk = [];

try {
    if ($keyword !== '') {
        $stmt = $pdo->prepare('CALL sp_produk_cari(:keyword)');
        $stmt->execute([':keyword' => $keyword]);
    } else {
        $stmt = $pdo->query('CALL sp_produk_tampil()');
    }

    $produk = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = db_message($e, 'Data produk gagal dimuat.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Produk</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container">
    <nav class="topnav">
        <a href="../index.php">← Dashboard</a>
        <span>Data Produk</span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">MASTER DATA</p>
            <h1>Data Produk</h1>
            <p class="muted">CRUD dijalankan melalui stored procedure di database.</p>
        </div>
        <a class="button primary" href="tambah.php">+ Tambah Produk</a>
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
                placeholder="Cari ID atau nama produk..."
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
                    <th>Nama Produk</th>
                    <th class="right">Harga</th>
                    <th class="right">Stok</th>
                    <th>Dibuat</th>
                    <th class="right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$produk): ?>
                    <tr>
                        <td colspan="6" class="empty">Data produk tidak ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produk as $row): ?>
                        <tr>
                            <td>#<?= e((string) $row['id_produk']) ?></td>
                            <td><strong><?= e($row['nama_produk']) ?></strong></td>
                            <td class="right"><?= e(rupiah($row['harga'])) ?></td>
                            <td class="right"><?= e((string) $row['stok']) ?></td>
                            <td><?= e(date('d-m-Y H:i', strtotime($row['created_at']))) ?></td>
                            <td>
                                <div class="row-actions">
                                    <a class="button small" href="edit.php?id=<?= urlencode((string) $row['id_produk']) ?>">
                                        Edit
                                    </a>
                                    <form
                                        method="post"
                                        action="hapus.php"
                                        onsubmit="return confirm('Yakin ingin menghapus produk ini?');"
                                    >
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $row['id_produk']) ?>">
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
