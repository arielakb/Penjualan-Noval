<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_role('ADMIN');

$keyword = trim((string) ($_GET['q'] ?? ''));
$limitRaw = trim((string) ($_GET['limit'] ?? '50'));
$limit = filter_var($limitRaw, FILTER_VALIDATE_INT);

if ($limit === false || $limit < 1 || $limit > 200) {
    $limit = 50;
}

$error = '';
$data = [];

try {
    $stmt = $pdo->prepare('CALL sp_audit_tampil(:keyword, :limit_data)');
    $stmt->bindValue(':keyword', $keyword, PDO::PARAM_STR);
    $stmt->bindValue(':limit_data', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = db_message(
        $e,
        'Audit log gagal dimuat. Pastikan SQL Step 4 sudah di-import.'
    );
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Log</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container">
    <nav class="topnav">
        <a href="../index.php">← Dashboard</a>
        <span>Audit Log</span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">AUDIT DATABASE</p>
            <h1>Audit Log</h1>
            <p class="muted">
                Perubahan tabel produk dicatat otomatis oleh trigger database.
            </p>
        </div>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="card">
        <form method="get" class="audit-filter">
            <input
                type="search"
                name="q"
                value="<?= e($keyword) ?>"
                placeholder="Cari aktivitas, tabel, keterangan, atau ID..."
                autocomplete="off"
            >

            <select name="limit">
                <?php foreach ([25, 50, 100, 200] as $option): ?>
                    <option
                        value="<?= e((string) $option) ?>"
                        <?= $limit === $option ? 'selected' : '' ?>
                    >
                        <?= e((string) $option) ?> data
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="button primary" type="submit">Cari</button>
            <?php if ($keyword !== '' || $limit !== 50): ?>
                <a class="button ghost" href="index.php">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID Log</th>
                    <th>Waktu</th>
                    <th>Aktivitas</th>
                    <th>Tabel</th>
                    <th>ID Data</th>
                    <th>Keterangan</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$data): ?>
                    <tr>
                        <td colspan="6" class="empty">Belum ada audit log.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <?php
                        $activity = strtoupper((string) $row['aktivitas']);
                        $badgeClass = match ($activity) {
                            'INSERT' => 'success',
                            'DELETE' => 'danger',
                            default => 'warning',
                        };
                        ?>
                        <tr>
                            <td>#<?= e((string) $row['id_log']) ?></td>
                            <td><?= e(date('d-m-Y H:i:s', strtotime($row['waktu']))) ?></td>
                            <td><span class="badge <?= e($badgeClass) ?>"><?= e($activity) ?></span></td>
                            <td><?= e($row['nama_tabel']) ?></td>
                            <td><?= e((string) ($row['id_data'] ?? '-')) ?></td>
                            <td><?= e($row['keterangan'] ?? '-') ?></td>
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
