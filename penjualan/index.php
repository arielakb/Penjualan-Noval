<?php
declare(strict_types=1);
require __DIR__ . '/config/database.php';
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
    <section class="hero">
        <p class="eyebrow">DATABASE PROGRAMMER</p>
        <h1>Aplikasi Pengelolaan Data Penjualan</h1>
        <p class="muted">
            PHP Native + PDO. CRUD dan proses transaksi utama dijalankan
            melalui stored program MySQL/MariaDB.
        </p>
        <div class="actions">
            <a class="button primary" href="transaksi/index.php">Transaksi Penjualan</a>
            <a class="button" href="pelanggan/index.php">Data Pelanggan</a>
            <a class="button" href="produk/index.php">Data Produk</a>
        </div>
    </section>

    <section class="module-grid">
        <a class="module-card" href="pelanggan/index.php">
            <span class="module-no">01</span>
            <h2>Data Pelanggan</h2>
            <p>Tambah, cari, ubah, dan hapus pelanggan melalui stored procedure.</p>
        </a>

        <a class="module-card" href="produk/index.php">
            <span class="module-no">02</span>
            <h2>Data Produk</h2>
            <p>Kelola produk, harga, dan stok dengan procedure serta audit trigger.</p>
        </a>

        <a class="module-card" href="transaksi/index.php">
            <span class="module-no">03</span>
            <h2>Transaksi</h2>
            <p>Keranjang multi-produk, validasi stok, rollback, commit, dan riwayat.</p>
        </a>

        <div class="module-card disabled">
            <span class="module-no">04</span>
            <h2>Laporan & Audit</h2>
            <p>Tahap berikutnya: view laporan, audit log, dashboard, dan pengujian akhir.</p>
        </div>
    </section>
</main>
</body>
</html>
