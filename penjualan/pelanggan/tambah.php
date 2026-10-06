<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';

$nama = '';
$alamat = '';
$noHp = '';
$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim((string) ($_POST['nama'] ?? ''));
    $alamat = trim((string) ($_POST['alamat'] ?? ''));
    $noHp = trim((string) ($_POST['no_hp'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($nama === '') {
        $error = 'Nama pelanggan wajib diisi.';
    } elseif (mb_strlen($nama) > 100) {
        $error = 'Nama pelanggan maksimal 100 karakter.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (mb_strlen($alamat) > 255) {
        $error = 'Alamat maksimal 255 karakter.';
    } elseif (mb_strlen($noHp) > 20) {
        $error = 'Nomor HP maksimal 20 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'CALL sp_pelanggan_tambah(:nama, :alamat, :no_hp, :email)'
            );

            $stmt->execute([
                ':nama' => $nama,
                ':alamat' => $alamat,
                ':no_hp' => $noHp,
                ':email' => $email,
            ]);

            $stmt->fetch();
            $stmt->closeCursor();

            set_flash('success', 'Pelanggan berhasil ditambahkan.');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = db_message($e, 'Pelanggan gagal ditambahkan.');
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Pelanggan</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container narrow">
    <nav class="topnav">
        <a href="index.php">← Data Pelanggan</a>
        <span>Tambah Pelanggan</span>
    </nav>

    <section class="card">
        <p class="eyebrow">FORM PELANGGAN</p>
        <h1>Tambah Pelanggan</h1>
        <p class="muted">
            Data dikirim ke <code>sp_pelanggan_tambah</code>. PHP tidak melakukan
            INSERT langsung ke tabel pelanggan.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-grid" autocomplete="off">
            <label>
                <span>Nama pelanggan *</span>
                <input
                    type="text"
                    name="nama"
                    maxlength="100"
                    value="<?= e($nama) ?>"
                    required
                    autofocus
                >
            </label>

            <label>
                <span>Nomor HP</span>
                <input
                    type="text"
                    name="no_hp"
                    maxlength="20"
                    inputmode="tel"
                    value="<?= e($noHp) ?>"
                    placeholder="Contoh: 081234567890"
                >
            </label>

            <label>
                <span>Email</span>
                <input
                    type="email"
                    name="email"
                    maxlength="100"
                    value="<?= e($email) ?>"
                    placeholder="nama@example.com"
                >
            </label>

            <label>
                <span>Alamat</span>
                <textarea
                    name="alamat"
                    maxlength="255"
                    rows="4"
                    placeholder="Alamat pelanggan"
                ><?= e($alamat) ?></textarea>
            </label>

            <div class="actions">
                <button class="button primary" type="submit">Simpan Pelanggan</button>
                <a class="button ghost" href="index.php">Batal</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
