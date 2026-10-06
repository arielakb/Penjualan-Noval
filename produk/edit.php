<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id <= 0) {
    set_flash('error', 'ID produk tidak valid.');
    header('Location: index.php');
    exit;
}

$error = '';
$nama = '';
$harga = '';
$stok = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $stmt = $pdo->prepare(
                'CALL sp_produk_ubah(:id, :nama_produk, :harga, :stok)'
            );
            $stmt->execute([
                ':id' => $id,
                ':nama_produk' => $nama,
                ':harga' => $harga,
                ':stok' => (int) $stok,
            ]);
            $stmt->closeCursor();

            set_flash('success', 'Produk berhasil diubah.');
            header('Location: index.php');
            exit;
        }
    } else {
        $stmt = $pdo->prepare('CALL sp_produk_detail(:id)');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $stmt->closeCursor();

        if (!$row) {
            throw new RuntimeException('Produk tidak ditemukan.');
        }

        $nama = (string) $row['nama_produk'];
        $harga = (string) $row['harga'];
        $stok = (string) $row['stok'];
    }
} catch (PDOException $e) {
    $error = db_message($e, 'Data produk gagal diproses.');
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Produk</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container narrow">
    <nav class="topnav">
        <a href="index.php">← Data Produk</a>
        <span>Edit Produk</span>
    </nav>

    <section class="card">
        <p class="eyebrow">FORM PRODUK</p>
        <h1>Edit Produk #<?= e((string) $id) ?></h1>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-grid" autocomplete="off">
                    <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $id) ?>">

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
                <button class="button primary" type="submit">Simpan Perubahan</button>
                <a class="button ghost" href="index.php">Batal</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
