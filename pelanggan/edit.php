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
    set_flash('error', 'ID pelanggan tidak valid.');
    header('Location: index.php');
    exit;
}

$error = '';
$nama = '';
$alamat = '';
$noHp = '';
$email = '';

try {
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
            $stmt = $pdo->prepare(
                'CALL sp_pelanggan_ubah(:id, :nama, :alamat, :no_hp, :email)'
            );
            $stmt->execute([
                ':id' => $id,
                ':nama' => $nama,
                ':alamat' => $alamat,
                ':no_hp' => $noHp,
                ':email' => $email,
            ]);
            $stmt->closeCursor();

            set_flash('success', 'Pelanggan berhasil diubah.');
            header('Location: index.php');
            exit;
        }
    } else {
        $stmt = $pdo->prepare('CALL sp_pelanggan_detail(:id)');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $stmt->closeCursor();

        if (!$row) {
            throw new RuntimeException('Pelanggan tidak ditemukan.');
        }

        $nama = (string) $row['nama'];
        $alamat = (string) ($row['alamat'] ?? '');
        $noHp = (string) ($row['no_hp'] ?? '');
        $email = (string) ($row['email'] ?? '');
    }
} catch (PDOException $e) {
    $error = db_message($e, 'Data pelanggan gagal diproses.');
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Pelanggan</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container narrow">
    <nav class="topnav">
        <a href="index.php">← Data Pelanggan</a>
        <span>Edit Pelanggan</span>
    </nav>

    <section class="card">
        <p class="eyebrow">FORM PELANGGAN</p>
        <h1>Edit Pelanggan #<?= e((string) $id) ?></h1>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-grid" autocomplete="off">
                    <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $id) ?>">

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
                >
            </label>

            <label>
                <span>Email</span>
                <input
                    type="email"
                    name="email"
                    maxlength="100"
                    value="<?= e($email) ?>"
                >
            </label>

            <label>
                <span>Alamat</span>
                <textarea
                    name="alamat"
                    maxlength="255"
                    rows="4"
                ><?= e($alamat) ?></textarea>
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
