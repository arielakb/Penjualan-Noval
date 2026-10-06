<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';

$error = '';
$pelanggan = [];
$idPelanggan = '';

try {
    $stmt = $pdo->query('CALL sp_pelanggan_tampil()');
    $pelanggan = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = db_message($e, 'Daftar pelanggan gagal dimuat.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $idPelanggan = trim((string) ($_POST['id_pelanggan'] ?? ''));

    if (filter_var($idPelanggan, FILTER_VALIDATE_INT) === false || (int) $idPelanggan <= 0) {
        $error = 'Pelanggan wajib dipilih.';
    } else {
        try {
            $stmt = $pdo->prepare('CALL sp_transaksi_buat_draft(:id_pelanggan)');
            $stmt->execute([':id_pelanggan' => (int) $idPelanggan]);
            $row = $stmt->fetch();
            $stmt->closeCursor();

            if (!$row || empty($row['id_transaksi'])) {
                throw new RuntimeException('ID transaksi tidak diterima dari database.');
            }

            header('Location: keranjang.php?id=' . urlencode((string) $row['id_transaksi']));
            exit;
        } catch (PDOException $e) {
            $error = db_message($e, 'Transaksi baru gagal dibuat.');
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaksi Baru</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container narrow">
    <nav class="topnav">
        <a href="index.php">← Transaksi</a>
        <span>Transaksi Baru</span>
    </nav>

    <section class="card">
        <p class="eyebrow">LANGKAH 1</p>
        <h1>Pilih Pelanggan</h1>
        <p class="muted">
            Database akan membuat transaksi berstatus <code>DRAFT</code>.
            Setelah itu Anda dapat menambahkan beberapa produk.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$pelanggan): ?>
            <div class="alert error">
                Belum ada pelanggan. Tambahkan pelanggan terlebih dahulu.
            </div>
            <a class="button" href="../pelanggan/tambah.php">Tambah Pelanggan</a>
        <?php else: ?>
            <form method="post" class="form-grid">
                <label>
                    <span>Pelanggan *</span>
                    <select name="id_pelanggan" required autofocus>
                        <option value="">-- Pilih pelanggan --</option>
                        <?php foreach ($pelanggan as $row): ?>
                            <option
                                value="<?= e((string) $row['id_pelanggan']) ?>"
                                <?= ((string) $row['id_pelanggan'] === $idPelanggan) ? 'selected' : '' ?>
                            >
                                <?= e($row['nama']) ?>
                                <?= $row['no_hp'] ? ' — ' . e($row['no_hp']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="actions">
                    <button class="button primary" type="submit">Buat Keranjang</button>
                    <a class="button ghost" href="index.php">Batal</a>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
