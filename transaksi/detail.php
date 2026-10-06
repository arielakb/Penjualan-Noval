<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    set_flash('error', 'ID transaksi tidak valid.');
    header('Location: index.php');
    exit;
}

$error = '';
$rows = [];

try {
    $stmt = $pdo->prepare('CALL sp_transaksi_detail(:id)');
    $stmt->execute([':id' => $id]);
    $rows = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = db_message($e, 'Detail transaksi gagal dimuat.');
}

$header = $rows[0] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Transaksi</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container">
    <nav class="topnav">
        <a href="index.php">← Transaksi</a>
        <span>Detail #<?= e((string) $id) ?></span>
    </nav>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($header): ?>
        <?php
        $status = (string) $header['status'];
        $badgeClass = match ($status) {
            'SELESAI' => 'success',
            'DIBATALKAN' => 'danger',
            default => 'warning',
        };
        ?>
        <header class="page-header">
            <div>
                <p class="eyebrow">DETAIL PENJUALAN</p>
                <h1>Transaksi #<?= e((string) $id) ?></h1>
                <p class="muted">
                    <?= e(date('d-m-Y H:i', strtotime($header['tanggal']))) ?>
                    · <?= e($header['pelanggan']) ?>
                </p>
            </div>
            <span class="badge <?= e($badgeClass) ?>"><?= e($status) ?></span>
        </header>

        <section class="card">
            <div class="section-heading">
                <h2>Rincian Produk</h2>
                <strong class="total-big"><?= e(rupiah($header['total'])) ?></strong>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="right">Harga</th>
                        <th class="right">Qty</th>
                        <th class="right">Subtotal</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $hasItem = false;
                    foreach ($rows as $row):
                        if ($row['id_detail'] === null) {
                            continue;
                        }
                        $hasItem = true;
                    ?>
                        <tr>
                            <td><strong><?= e($row['nama_produk']) ?></strong></td>
                            <td class="right"><?= e(rupiah($row['harga'])) ?></td>
                            <td class="right"><?= e((string) $row['qty']) ?></td>
                            <td class="right"><?= e(rupiah($row['subtotal'])) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$hasItem): ?>
                        <tr>
                            <td colspan="4" class="empty">Tidak ada item pada transaksi ini.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
