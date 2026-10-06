<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_login();

$nama = '';
$harga = '';
$stok = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nama = trim((string) ($_POST['nama_produk'] ?? ''));
    $harga = trim((string) ($_POST['harga'] ?? ''));
    $stok = trim((string) ($_POST['stok'] ?? ''));

    if ($nama === '' || $harga === '' || $stok === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!is_numeric($harga) || (float) $harga <= 0) {
        $error = 'Harga harus berupa angka lebih dari 0.';
    } elseif (filter_var($stok, FILTER_VALIDATE_INT) === false || (int) $stok < 0) {
        $error = 'Stok harus berupa bilangan bulat 0 atau lebih.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'CALL sp_produk_tambah(:nama_produk, :harga, :stok)'
            );
            $stmt->execute([
                ':nama_produk' => $nama,
                ':harga' => $harga,
                ':stok' => (int) $stok,
            ]);

            // Procedure mengembalikan id baru. Tidak wajib dipakai saat ini.
            $stmt->fetch();
            $stmt->closeCursor();

            set_flash('success', 'Produk berhasil ditambahkan.');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = db_message($e, 'Produk gagal ditambahkan.');
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Produk</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container narrow">
    <nav class="topnav">
        <a href="index.php">← Data Produk</a>
        <span>Tambah Produk</span>
    </nav>

    <section class="card">
        <p class="eyebrow">FORM PRODUK</p>
        <h1>Tambah Produk</h1>
        <p class="muted">
            PHP hanya mengirim parameter. Validasi inti dan INSERT dilakukan oleh
            <code>sp_produk_tambah</code>.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-grid" autocomplete="off">
                    <?= csrf_input() ?>
            <label>
                <span>Nama produk</span>
                <input
                    type="text"
                    name="nama_produk"
                    maxlength="100"
                    value="<?= e($nama) ?>"
                    required
                    autofocus
                >
            </label>

            <label>
                <span>Harga</span>
                <input
                    type="number"
                    name="harga"
                    min="1"
                    step="0.01"
                    value="<?= e($harga) ?>"
                    required
                >
            </label>

            <label>
                <span>Stok</span>
                <input
                    type="number"
                    name="stok"
                    min="0"
                    step="1"
                    value="<?= e($stok) ?>"
                    required
                >
            </label>

            <div class="actions">
                <button class="button primary" type="submit">Simpan Produk</button>
                <a class="button ghost" href="index.php">Batal</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
