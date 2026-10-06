<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'id_transaksi', FILTER_VALIDATE_INT);
}

if (!$id || $id <= 0) {
    set_flash('error', 'ID transaksi tidak valid.');
    header('Location: index.php');
    exit;
}

$error = '';
$header = null;
$items = [];
$produk = [];

function loadDraft(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('CALL sp_transaksi_draft_header(:id)');
    $stmt->execute([':id' => $id]);
    $header = $stmt->fetch();
    $stmt->closeCursor();

    $stmt = $pdo->prepare('CALL sp_transaksi_draft_detail(:id)');
    $stmt->execute([':id' => $id]);
    $items = $stmt->fetchAll();
    $stmt->closeCursor();

    $stmt = $pdo->query('CALL sp_produk_tampil()');
    $products = $stmt->fetchAll();
    $stmt->closeCursor();

    return [$header, $items, $products];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'add') {
            $idProduk = filter_input(INPUT_POST, 'id_produk', FILTER_VALIDATE_INT);
            $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

            if (!$idProduk || !$qty || $qty <= 0) {
                throw new RuntimeException('Produk dan qty harus diisi dengan benar.');
            }

            $stmt = $pdo->prepare(
                'CALL sp_transaksi_tambah_item(:id_transaksi, :id_produk, :qty)'
            );
            $stmt->execute([
                ':id_transaksi' => $id,
                ':id_produk' => $idProduk,
                ':qty' => $qty,
            ]);
            $stmt->closeCursor();

            set_flash('success', 'Produk berhasil ditambahkan ke keranjang.');
            header('Location: keranjang.php?id=' . urlencode((string) $id));
            exit;
        }

        if ($action === 'update') {
            $idDetail = filter_input(INPUT_POST, 'id_detail', FILTER_VALIDATE_INT);
            $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

            if (!$idDetail || !$qty || $qty <= 0) {
                throw new RuntimeException('Qty tidak valid.');
            }

            $stmt = $pdo->prepare(
                'CALL sp_transaksi_ubah_item(:id_detail, :qty)'
            );
            $stmt->execute([
                ':id_detail' => $idDetail,
                ':qty' => $qty,
            ]);
            $stmt->closeCursor();

            set_flash('success', 'Qty berhasil diperbarui.');
            header('Location: keranjang.php?id=' . urlencode((string) $id));
            exit;
        }

        if ($action === 'delete') {
            $idDetail = filter_input(INPUT_POST, 'id_detail', FILTER_VALIDATE_INT);

            if (!$idDetail) {
                throw new RuntimeException('Item transaksi tidak valid.');
            }

            $stmt = $pdo->prepare('CALL sp_transaksi_hapus_item(:id_detail)');
            $stmt->execute([':id_detail' => $idDetail]);
            $stmt->closeCursor();

            set_flash('success', 'Item dihapus dari keranjang.');
            header('Location: keranjang.php?id=' . urlencode((string) $id));
            exit;
        }

        if ($action === 'finish') {
            $stmt = $pdo->prepare('CALL sp_transaksi_finalisasi(:id)');
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();
            $stmt->closeCursor();

            $total = $result['total'] ?? 0;
            set_flash(
                'success',
                'Transaksi #' . $id . ' berhasil disimpan. Total ' . rupiah($total) . '.'
            );
            header('Location: index.php');
            exit;
        }

        if ($action === 'cancel') {
            $stmt = $pdo->prepare('CALL sp_transaksi_batalkan_draft(:id)');
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();

            set_flash('success', 'Draft transaksi dibatalkan. Stok tidak berubah.');
            header('Location: index.php');
            exit;
        }

        throw new RuntimeException('Aksi tidak dikenal.');
    } catch (PDOException $e) {
        $error = db_message($e, 'Proses transaksi gagal.');
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

try {
    [$header, $items, $produk] = loadDraft($pdo, (int) $id);
} catch (PDOException $e) {
    $error = db_message($e, 'Draft transaksi gagal dimuat.');
}

$flash = get_flash();

$estimasiTotal = 0.0;
foreach ($items as $item) {
    $estimasiTotal += (float) $item['subtotal'];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang Transaksi</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container">
    <nav class="topnav">
        <a href="index.php">← Transaksi</a>
        <span>Keranjang #<?= e((string) $id) ?></span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">LANGKAH 2</p>
            <h1>Keranjang Penjualan</h1>
            <?php if ($header): ?>
                <p class="muted">
                    Pelanggan: <strong><?= e($header['pelanggan']) ?></strong>
                    <?php if ($header['no_hp']): ?>
                        · <?= e($header['no_hp']) ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
        <span class="badge warning">DRAFT</span>
    </header>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($header): ?>
        <div class="transaction-layout">
            <section class="card">
                <h2>Tambah Produk</h2>

                <form method="post" class="form-grid">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id_transaksi" value="<?= e((string) $id) ?>">
                    <input type="hidden" name="action" value="add">

                    <label>
                        <span>Produk *</span>
                        <select name="id_produk" required>
                            <option value="">-- Pilih produk --</option>
                            <?php foreach ($produk as $row): ?>
                                <option
                                    value="<?= e((string) $row['id_produk']) ?>"
                                    <?= ((int) $row['stok'] <= 0) ? 'disabled' : '' ?>
                                >
                                    <?= e($row['nama_produk']) ?>
                                    — <?= e(rupiah($row['harga'])) ?>
                                    — stok <?= e((string) $row['stok']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span>Qty *</span>
                        <input type="number" name="qty" min="1" step="1" value="1" required>
                    </label>

                    <button class="button primary" type="submit">+ Tambah ke Keranjang</button>
                </form>

                <div class="info-box">
                    Harga dan stok akan diperiksa kembali ketika finalisasi.
                    Jika stok berubah dan tidak mencukupi, database melakukan rollback.
                </div>
            </section>

            <section class="card">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">RINGKASAN</p>
                        <h2>Item Transaksi</h2>
                    </div>
                    <strong class="total-big"><?= e(rupiah($estimasiTotal)) ?></strong>
                </div>

                <?php if (!$items): ?>
                    <div class="empty">Keranjang masih kosong.</div>
                <?php else: ?>
                    <div class="cart-list">
                        <?php foreach ($items as $item): ?>
                            <article class="cart-item">
                                <div class="cart-main">
                                    <strong><?= e($item['nama_produk']) ?></strong>
                                    <span class="muted">
                                        <?= e(rupiah($item['harga'])) ?> × <?= e((string) $item['qty']) ?>
                                        · stok saat ini <?= e((string) $item['stok_saat_ini']) ?>
                                    </span>
                                </div>

                                <div class="cart-price">
                                    <?= e(rupiah($item['subtotal'])) ?>
                                </div>

                                <div class="cart-controls">
                                    <form method="post" class="inline-form">
                    <?= csrf_input() ?>
                                        <input type="hidden" name="id_transaksi" value="<?= e((string) $id) ?>">
                                        <input type="hidden" name="id_detail" value="<?= e((string) $item['id_detail']) ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input
                                            class="qty-input"
                                            type="number"
                                            name="qty"
                                            min="1"
                                            step="1"
                                            value="<?= e((string) $item['qty']) ?>"
                                            required
                                        >
                                        <button class="button small" type="submit">Ubah</button>
                                    </form>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Hapus item ini dari keranjang?');"
                                    >
                    <?= csrf_input() ?>
                                        <input type="hidden" name="id_transaksi" value="<?= e((string) $id) ?>">
                                        <input type="hidden" name="id_detail" value="<?= e((string) $item['id_detail']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="button small danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="checkout-actions">
                    <form
                        method="post"
                        onsubmit="return confirm('Finalisasi transaksi? Setelah selesai stok akan dikurangi dan transaksi tidak dapat diedit.');"
                    >
                    <?= csrf_input() ?>
                        <input type="hidden" name="id_transaksi" value="<?= e((string) $id) ?>">
                        <input type="hidden" name="action" value="finish">
                        <button
                            class="button primary"
                            type="submit"
                            <?= !$items ? 'disabled' : '' ?>
                        >
                            Finalisasi Transaksi
                        </button>
                    </form>

                    <form
                        method="post"
                        onsubmit="return confirm('Batalkan draft transaksi ini?');"
                    >
                    <?= csrf_input() ?>
                        <input type="hidden" name="id_transaksi" value="<?= e((string) $id) ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button class="button danger" type="submit">Batalkan Draft</button>
                    </form>
                </div>
            </section>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
