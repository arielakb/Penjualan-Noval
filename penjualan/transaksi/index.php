<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';

$flash = get_flash();
$error = '';
$data = [];

try {
    $stmt = $pdo->query('CALL sp_transaksi_tampil()');
    $data = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = db_message($e, 'Riwayat transaksi gagal dimuat. Pastikan SQL Step 3 sudah di-import.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaksi Penjualan</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container">
    <nav class="topnav">
        <a href="../index.php">← Dashboard</a>
        <span>Transaksi Penjualan</span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">PENJUALAN</p>
            <h1>Transaksi Penjualan</h1>
            <p class="muted">
                Stok baru dikurangi ketika transaksi difinalisasi oleh database.
            </p>
        </div>
        <a class="button primary" href="tambah.php">+ Transaksi Baru</a>
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
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th class="right">Barang</th>
                    <th class="right">Total</th>
                    <th>Status</th>
                    <th class="right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$data): ?>
                    <tr>
                        <td colspan="7" class="empty">Belum ada transaksi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <?php
                        $status = (string) $row['status'];
                        $badgeClass = match ($status) {
                            'SELESAI' => 'success',
                            'DIBATALKAN' => 'danger',
                            default => 'warning',
                        };
                        ?>
                        <tr>
                            <td>#<?= e((string) $row['id_transaksi']) ?></td>
                            <td><?= e(date('d-m-Y H:i', strtotime($row['tanggal']))) ?></td>
                            <td><strong><?= e($row['pelanggan']) ?></strong></td>
                            <td class="right"><?= e((string) $row['jumlah_barang']) ?></td>
                            <td class="right"><?= e(rupiah($row['total'])) ?></td>
                            <td><span class="badge <?= e($badgeClass) ?>"><?= e($status) ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <?php if ($status === 'DRAFT'): ?>
                                        <a class="button small primary" href="keranjang.php?id=<?= urlencode((string) $row['id_transaksi']) ?>">
                                            Lanjutkan
                                        </a>
                                    <?php else: ?>
                                        <a class="button small" href="detail.php?id=<?= urlencode((string) $row['id_transaksi']) ?>">
                                            Detail
                                        </a>
                                    <?php endif; ?>
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
