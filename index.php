<?php
declare(strict_types=1);
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/auth.php';
require_login();
$user = current_user();

$stats = [
    'jumlah_pelanggan' => 0,
    'jumlah_produk' => 0,
    'jumlah_transaksi_selesai' => 0,
    'total_omzet' => 0,
    'produk_stok_rendah' => 0,
];
$transaksiTerbaru = [];
$stokRendah = [];
$dashboardError = '';

try {
    $stmt = $pdo->query('CALL sp_dashboard_ringkasan()');
    $row = $stmt->fetch();
    $stmt->closeCursor();

    if ($row) {
        $stats = array_merge($stats, $row);
    }

    $stmt = $pdo->query('CALL sp_dashboard_transaksi_terbaru()');
    $transaksiTerbaru = $stmt->fetchAll();
    $stmt->closeCursor();

    $stmt = $pdo->query('CALL sp_dashboard_stok_rendah()');
    $stokRendah = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $dashboardError = 'Dashboard statistik belum aktif. Import SQL Step 4 terlebih dahulu.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplikasi Penjualan</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container">
    <div class="userbar">
        <div>
            <strong><?= e($user['nama_lengkap'] ?? '') ?></strong>
            <span><?= e($user['role'] ?? '') ?></span>
        </div>
        <form method="post" action="logout.php">
            <?= csrf_input() ?>
            <button class="button small" type="submit">Logout</button>
        </form>
    </div>
    <section class="hero dashboard-hero">
        <div>
            <p class="eyebrow">DATABASE PROGRAMMER</p>
            <h1>Aplikasi Pengelolaan Data Penjualan</h1>
            <p class="muted">
                CRUD, transaksi, laporan, dan audit dikelola melalui stored program MySQL/MariaDB.
            </p>
        </div>

        <div class="actions">
            <a class="button primary" href="transaksi/tambah.php">+ Transaksi Baru</a>
            <a class="button" href="laporan/index.php">Lihat Laporan</a>
        </div>
    </section>

    <?php if ($dashboardError !== ''): ?>
        <div class="alert error"><?= e($dashboardError) ?></div>
    <?php endif; ?>

    <section class="stats-grid">
        <article class="stat-card">
            <span class="stat-label">Pelanggan</span>
            <strong><?= e((string) $stats['jumlah_pelanggan']) ?></strong>
            <a href="pelanggan/index.php">Kelola pelanggan →</a>
        </article>

        <article class="stat-card">
            <span class="stat-label">Produk</span>
            <strong><?= e((string) $stats['jumlah_produk']) ?></strong>
            <a href="produk/index.php">Kelola produk →</a>
        </article>

        <article class="stat-card">
            <span class="stat-label">Transaksi Selesai</span>
            <strong><?= e((string) $stats['jumlah_transaksi_selesai']) ?></strong>
            <a href="transaksi/index.php">Lihat transaksi →</a>
        </article>

        <article class="stat-card accent">
            <span class="stat-label">Total Omzet</span>
            <strong class="money-stat"><?= e(rupiah($stats['total_omzet'])) ?></strong>
            <a href="laporan/index.php">Buka laporan →</a>
        </article>
    </section>

    <section class="module-grid dashboard-modules">
        <a class="module-card" href="pelanggan/index.php">
            <span class="module-no">01</span>
            <h2>Data Pelanggan</h2>
            <p>Kelola master pelanggan melalui stored procedure.</p>
        </a>

        <a class="module-card" href="produk/index.php">
            <span class="module-no">02</span>
            <h2>Data Produk</h2>
            <p>Kelola harga, stok, dan perubahan produk.</p>
        </a>

        <a class="module-card" href="transaksi/index.php">
            <span class="module-no">03</span>
            <h2>Transaksi</h2>
            <p>Keranjang multi-produk, commit, rollback, dan riwayat.</p>
        </a>

        <a class="module-card" href="laporan/index.php">
            <span class="module-no">04</span>
            <h2>Laporan</h2>
            <p>Filter penjualan berdasarkan tanggal dan lihat produk terlaris.</p>
        </a>

        <?php if (($user['role'] ?? '') === 'ADMIN'): ?>
        <a class="module-card" href="audit/index.php">
            <span class="module-no">05</span>
            <h2>Audit Log</h2>
            <p>Tinjau jejak INSERT, UPDATE, dan DELETE yang dicatat trigger.</p>
        </a>
                <a class="module-card" href="auth/index.php">
            <span class="module-no">06</span>
            <h2>Pengguna</h2>
            <p>Kelola status akun aplikasi dan pembatasan role.</p>
        </a>
        <?php endif; ?>
    </section>

    <section class="dashboard-two-col">
        <section class="card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">TERBARU</p>
                    <h2>Transaksi Selesai</h2>
                </div>
                <a href="transaksi/index.php">Semua →</a>
            </div>

            <?php if (!$transaksiTerbaru): ?>
                <div class="empty">Belum ada transaksi selesai.</div>
            <?php else: ?>
                <div class="simple-list">
                    <?php foreach ($transaksiTerbaru as $row): ?>
                        <a
                            class="simple-list-item"
                            href="transaksi/detail.php?id=<?= urlencode((string) $row['id_transaksi']) ?>"
                        >
                            <div>
                                <strong>#<?= e((string) $row['id_transaksi']) ?> · <?= e($row['pelanggan']) ?></strong>
                                <span><?= e(date('d-m-Y H:i', strtotime($row['tanggal']))) ?></span>
                            </div>
                            <b><?= e(rupiah($row['total'])) ?></b>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">PERHATIAN</p>
                    <h2>Stok Rendah</h2>
                </div>
                <span class="badge warning"><?= e((string) $stats['produk_stok_rendah']) ?> produk</span>
            </div>

            <?php if (!$stokRendah): ?>
                <div class="empty">Tidak ada produk dengan stok ≤ 5.</div>
            <?php else: ?>
                <div class="simple-list">
                    <?php foreach ($stokRendah as $row): ?>
                        <a
                            class="simple-list-item"
                            href="produk/edit.php?id=<?= urlencode((string) $row['id_produk']) ?>"
                        >
                            <div>
                                <strong><?= e($row['nama_produk']) ?></strong>
                                <span><?= e(rupiah($row['harga'])) ?></span>
                            </div>
                            <b>Stok <?= e((string) $row['stok']) ?></b>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>
</body>
</html>
