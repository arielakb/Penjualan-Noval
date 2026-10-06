-- ============================================================
-- STEP 5 - LOGIN, ROLE, DAN KEAMANAN APLIKASI
-- Jalankan SEKALI setelah Step 4 berhasil.
-- ============================================================

USE db_penjualan;

CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('ADMIN','KASIR') NOT NULL DEFAULT 'KASIR',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Akun demo. Ganti password setelah instalasi.
INSERT INTO pengguna (username, password_hash, nama_lengkap, role, aktif)
VALUES
('admin', '$2y$12$gcOOGxHbvS.A69tkzRik7eYP9Tg/OJr1gPw3Dx9MiyEj3WaTVD1Cu', 'Administrator', 'ADMIN', 1),
('kasir', '$2y$12$vJsHXH4d6iQFZJpP0S/FWuS4vD23z02ggzrrnmNz/E0PCBAWYiuHW', 'Petugas Kasir', 'KASIR', 1)
ON DUPLICATE KEY UPDATE
    nama_lengkap = VALUES(nama_lengkap),
    role = VALUES(role),
    aktif = VALUES(aktif);

DROP PROCEDURE IF EXISTS sp_auth_cari_username;
DROP PROCEDURE IF EXISTS sp_pengguna_tampil;
DROP PROCEDURE IF EXISTS sp_pengguna_ubah_status;

DELIMITER $$

CREATE PROCEDURE sp_auth_cari_username(
    IN p_username VARCHAR(50)
)
BEGIN
    SELECT
        id_pengguna,
        username,
        password_hash,
        nama_lengkap,
        role,
        aktif
    FROM pengguna
    WHERE username = TRIM(COALESCE(p_username, ''))
    LIMIT 1;
END$$

CREATE PROCEDURE sp_pengguna_tampil()
BEGIN
    SELECT
        id_pengguna,
        username,
        nama_lengkap,
        role,
        aktif,
        created_at,
        updated_at
    FROM pengguna
    ORDER BY id_pengguna;
END$$

CREATE PROCEDURE sp_pengguna_ubah_status(
    IN p_id_pengguna INT,
    IN p_aktif TINYINT
)
BEGIN
    IF p_id_pengguna IS NULL OR p_id_pengguna <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ID pengguna tidak valid.';
    END IF;

    IF p_aktif NOT IN (0,1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Status aktif tidak valid.';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pengguna WHERE id_pengguna = p_id_pengguna
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pengguna tidak ditemukan.';
    END IF;

    UPDATE pengguna
    SET aktif = p_aktif
    WHERE id_pengguna = p_id_pengguna;
END$$

DELIMITER ;

GRANT EXECUTE ON db_penjualan.* TO 'app_penjualan'@'localhost';
GRANT EXECUTE ON db_penjualan.* TO 'app_penjualan'@'127.0.0.1';

-- Akun awal:
-- ADMIN: admin / Admin#2026
-- KASIR: kasir / Kasir#2026
-- Ganti password demo setelah proses demonstrasi/pengembangan.
