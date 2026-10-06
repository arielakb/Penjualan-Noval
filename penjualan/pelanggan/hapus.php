<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    set_flash('error', 'ID pelanggan tidak valid.');
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare('CALL sp_pelanggan_hapus(:id)');
    $stmt->execute([':id' => $id]);
    $stmt->closeCursor();

    set_flash('success', 'Pelanggan berhasil dihapus.');
} catch (PDOException $e) {
    set_flash('error', db_message($e, 'Pelanggan gagal dihapus.'));
}

header('Location: index.php');
exit;
