<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_login();

$mulai = trim((string) ($_GET['mulai'] ?? ''));
$selesai = trim((string) ($_GET['selesai'] ?? ''));

function validDateOrEmpty(string $date): bool
{
    if ($date === '') {
        return true;
    }

    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

$error = '';
$transaksi = [];
$produkTerlaris = [];
$totalOmzet = 0.0;
$totalTransaksi = 0;

if (!validDateOrEmpty($mulai) || !validDateOrEmpty($selesai)) {
    $error = 'Format tanggal tidak valid.';
} elseif ($mulai !== '' && $selesai !== '' && $mulai > $selesai) {
    $error = 'Tanggal mulai tidak boleh melebihi tanggal selesai.';
} else {
    try {
        $stmt = $pdo->prepare(
            'CALL sp_laporan_penjualan(:mulai, :selesai)'
        );
        $stmt->execute([
            ':mulai' => $mulai !== '' ? $mulai : null,
            ':selesai' => $selesai !== '' ? $selesai : null,
        ]);
        $transaksi = $stmt->fetchAll();
        $stmt->closeCursor();

        foreach ($transaksi as $row) {
            $totalOmzet += (float) $row['total'];
        }
        $totalTransaksi = count($transaksi);

        $stmt = $pdo->prepare(
            'CALL sp_laporan_produk_terlaris(:mulai, :selesai)'
        );
        $stmt->execute([
            ':mulai' => $mulai !== '' ? $mulai : null,
            ':selesai' => $selesai !== '' ? $selesai : null,
        ]);
        $produkTerlaris = $stmt->fetchAll();
        $stmt->closeCursor();
    } catch (PDOException $e) {
        $error = db_message(
            $e,
            'Laporan gagal dimuat. Pastikan SQL Step 4 sudah di-import.'
        );
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penjualan</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container">
    <nav class="topnav">
        <a href="../index.php">← Dashboard</a>
        <span>Laporan Penjualan</span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">LAPORAN</p>
            <h1>Laporan Penjualan</h1>
            <p class="muted">
                Data laporan dibaca dari VIEW melalui stored procedure database.
            </p>
        </div>
        <button class="button" type="button" onclick="window.print()">Cetak</button>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="card no-print-filter">
        <form method="get" class="filter-grid">
            <label>
                <span>Tanggal mulai</span>
                <input type="date" name="mulai" value="<?= e($mulai) ?>">
            </label>

            <label>
                <span>Tanggal selesai</span>
                <input type="date" name="selesai" value="<?= e($selesai) ?>">
            </label>

            <div class="filter-actions">
                <button class="button primary" type="submit">Terapkan Filter</button>
                <a class="button ghost" href="index.php">Reset</a>
            </div>
        </form>
    </section>

    <section class="stats-grid report-stats">
        <article class="stat-card">
            <span class="stat-label">Transaksi Selesai</span>
            <strong><?= e((string) $totalTransaksi) ?></strong>
        </article>

        <article class="stat-card accent">
            <span class="stat-label">Omzet Periode</span>
            <strong><?= e(rupiah($totalOmzet)) ?></strong>
        </article>
    </section>

    <section class="card report-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">TRANSAKSI</p>
                <h2>Rincian Penjualan</h2>
            </div>
            <span class="muted">
                <?= $mulai !== '' ? e($mulai) : 'Semua waktu' ?>
                <?= ($mulai !== '' || $selesai !== '') ? ' — ' : '' ?>
                <?= $selesai !== '' ? e($selesai) : '' ?>
            </span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th class="right">Total</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$transaksi): ?>
                    <tr>
                        <td colspan="5" class="empty">Tidak ada data pada periode ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transaksi as $row): ?>
                        <tr>
                            <td>
                                <a href="../transaksi/detail.php?id=<?= urlencode((string) $row['id_transaksi']) ?>">
                                    #<?= e((string) $row['id_transaksi']) ?>
                                </a>
                            </td>
                            <td><?= e(date('d-m-Y H:i', strtotime($row['tanggal']))) ?></td>
                            <td><strong><?= e($row['pelanggan']) ?></strong></td>
                            <td class="right"><?= e(rupiah($row['total'])) ?></td>
                            <td><span class="badge success">SELESAI</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card report-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">TOP 10</p>
                <h2>Produk Terlaris</h2>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Produk</th>
                    <th class="right">Qty Terjual</th>
                    <th class="right">Nilai Penjualan</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$produkTerlaris): ?>
                    <tr>
                        <td colspan="4" class="empty">Belum ada data produk terjual.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produkTerlaris as $i => $row): ?>
                        <tr>
                            <td>#<?= e((string) ($i + 1)) ?></td>
                            <td><strong><?= e($row['nama_produk']) ?></strong></td>
                            <td class="right"><?= e((string) $row['total_qty']) ?></td>
                            <td class="right"><?= e(rupiah($row['total_penjualan'])) ?></td>
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
