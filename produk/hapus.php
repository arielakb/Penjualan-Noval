<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    set_flash('error', 'ID produk tidak valid.');
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare('CALL sp_produk_hapus(:id)');
    $stmt->execute([':id' => $id]);
    $stmt->closeCursor();

    set_flash('success', 'Produk berhasil dihapus.');
} catch (PDOException $e) {
    set_flash('error', db_message($e, 'Produk gagal dihapus.'));
}

header('Location: index.php');
exit;
