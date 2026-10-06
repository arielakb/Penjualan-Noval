-- ============================================================
-- DATABASE PROYEK: APLIKASI PENGELOLAAN DATA PENJUALAN
-- Target: XAMPP (MySQL/MariaDB) + phpMyAdmin + PHP PDO
-- ============================================================
-- PERINGATAN:
-- Script ini melakukan DROP tabel/routine/view proyek agar dapat
-- di-import ulang dengan bersih. Data lama di db_penjualan akan hilang.
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_penjualan
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_penjualan;

SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS v_detail_penjualan;
DROP VIEW IF EXISTS v_laporan_penjualan;

DROP TRIGGER IF EXISTS trg_produk_insert;
DROP TRIGGER IF EXISTS trg_produk_update;
DROP TRIGGER IF EXISTS trg_produk_delete;

DROP PROCEDURE IF EXISTS sp_produk_tampil;
DROP PROCEDURE IF EXISTS sp_produk_cari;
DROP PROCEDURE IF EXISTS sp_produk_detail;
DROP PROCEDURE IF EXISTS sp_produk_tambah;
DROP PROCEDURE IF EXISTS sp_produk_ubah;
DROP PROCEDURE IF EXISTS sp_produk_hapus;

DROP PROCEDURE IF EXISTS sp_pelanggan_tampil;
DROP PROCEDURE IF EXISTS sp_pelanggan_cari;
DROP PROCEDURE IF EXISTS sp_pelanggan_detail;
DROP PROCEDURE IF EXISTS sp_pelanggan_tambah;
DROP PROCEDURE IF EXISTS sp_pelanggan_ubah;
DROP PROCEDURE IF EXISTS sp_pelanggan_hapus;

DROP PROCEDURE IF EXISTS sp_transaksi_tampil;
DROP PROCEDURE IF EXISTS sp_transaksi_detail;
DROP PROCEDURE IF EXISTS sp_penjualan_satu_produk;

DROP FUNCTION IF EXISTS fn_hitung_subtotal;

DROP TABLE IF EXISTS detail_transaksi;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS pelanggan;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. TABEL
-- ============================================================

CREATE TABLE pelanggan (
    id_pelanggan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    alamat VARCHAR(255) NULL,
    no_hp VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pelanggan_nama (nama),
    INDEX idx_pelanggan_email (email)
) ENGINE=InnoDB;

CREATE TABLE produk (
    id_produk INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(100) NOT NULL,
    harga DECIMAL(14,2) NOT NULL,
    stok INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_produk_nama (nama_produk),
    INDEX idx_produk_harga (harga)
) ENGINE=InnoDB;

CREATE TABLE transaksi (
    id_transaksi BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pelanggan INT UNSIGNED NOT NULL,
    tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(16,2) NOT NULL DEFAULT 0,
    status ENUM('SELESAI','DIBATALKAN') NOT NULL DEFAULT 'SELESAI',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaksi_pelanggan
        FOREIGN KEY (id_pelanggan)
        REFERENCES pelanggan(id_pelanggan)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_transaksi_tanggal (tanggal),
    INDEX idx_transaksi_pelanggan (id_pelanggan)
) ENGINE=InnoDB;

CREATE TABLE detail_transaksi (
    id_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_transaksi BIGINT UNSIGNED NOT NULL,
    id_produk INT UNSIGNED NOT NULL,
    qty INT UNSIGNED NOT NULL,
    harga DECIMAL(14,2) NOT NULL,
    subtotal DECIMAL(16,2) NOT NULL,
    CONSTRAINT fk_detail_transaksi
        FOREIGN KEY (id_transaksi)
        REFERENCES transaksi(id_transaksi)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_detail_produk
        FOREIGN KEY (id_produk)
        REFERENCES produk(id_produk)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_detail_transaksi (id_transaksi),
    INDEX idx_detail_produk (id_produk)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
    id_log BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aktivitas VARCHAR(20) NOT NULL,
    nama_tabel VARCHAR(50) NOT NULL,
    id_data BIGINT UNSIGNED NULL,
    keterangan VARCHAR(500) NULL,
    waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_waktu (waktu),
    INDEX idx_audit_tabel (nama_tabel)
) ENGINE=InnoDB;

-- ============================================================
-- 2. DATA AWAL UNTUK PENGUJIAN
-- ============================================================

INSERT INTO pelanggan (nama, alamat, no_hp, email) VALUES
('Andi Saputra', 'Depok', '081234567890', 'andi@example.com'),
('Budi Santoso', 'Jakarta', '081298765432', 'budi@example.com'),
('Citra Lestari', 'Bogor', '081311112222', 'citra@example.com');

INSERT INTO produk (nama_produk, harga, stok) VALUES
('Keyboard Mechanical', 350000.00, 20),
('Mouse Wireless',       175000.00, 30),
('Monitor 24 Inch',     1750000.00, 10),
('Flashdisk 32GB',        85000.00, 25);

-- ============================================================
-- 3. FUNCTION
-- ============================================================

DELIMITER $$

CREATE FUNCTION fn_hitung_subtotal(
    p_harga DECIMAL(14,2),
    p_qty INT
)
RETURNS DECIMAL(16,2)
DETERMINISTIC
NO SQL
BEGIN
    IF p_harga IS NULL OR p_harga < 0 THEN
        RETURN 0;
    END IF;

    IF p_qty IS NULL OR p_qty <= 0 THEN
        RETURN 0;
    END IF;

    RETURN p_harga * p_qty;
END$$

-- ============================================================
-- 4. STORED PROCEDURE PRODUK
-- Semua operasi tampil/cari/detail/tambah/ubah/hapus lewat DBMS.
-- ============================================================

CREATE PROCEDURE sp_produk_tampil()
BEGIN
    SELECT
        id_produk,
        nama_produk,
        harga,
        stok,
        created_at,
        updated_at
    FROM produk
    ORDER BY id_produk DESC;
END$$

CREATE PROCEDURE sp_produk_cari(
    IN p_keyword VARCHAR(100)
)
BEGIN
    DECLARE v_keyword VARCHAR(100);

    SET v_keyword = TRIM(COALESCE(p_keyword, ''));

    SELECT
        id_produk,
        nama_produk,
        harga,
        stok,
        created_at,
        updated_at
    FROM produk
    WHERE
        v_keyword = ''
        OR nama_produk LIKE CONCAT('%', v_keyword, '%')
        OR CAST(id_produk AS CHAR) LIKE CONCAT('%', v_keyword, '%')
    ORDER BY id_produk DESC;
END$$

CREATE PROCEDURE sp_produk_detail(
    IN p_id_produk INT
)
BEGIN
    IF p_id_produk IS NULL OR p_id_produk <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ID produk tidak valid.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM produk
        WHERE id_produk = p_id_produk
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Produk tidak ditemukan.';
    END IF;

    SELECT
        id_produk,
        nama_produk,
        harga,
        stok,
        created_at,
        updated_at
    FROM produk
    WHERE id_produk = p_id_produk
    LIMIT 1;
END$$

CREATE PROCEDURE sp_produk_tambah(
    IN p_nama_produk VARCHAR(100),
    IN p_harga DECIMAL(14,2),
    IN p_stok INT
)
BEGIN
    DECLARE v_nama VARCHAR(100);

    SET v_nama = TRIM(COALESCE(p_nama_produk, ''));

    IF v_nama = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Nama produk wajib diisi.';
    END IF;

    IF CHAR_LENGTH(v_nama) > 100 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Nama produk maksimal 100 karakter.';
    END IF;

    IF p_harga IS NULL OR p_harga <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Harga harus lebih dari 0.';
    END IF;

    IF p_stok IS NULL OR p_stok < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stok tidak boleh negatif.';
    END IF;

    INSERT INTO produk (nama_produk, harga, stok)
    VALUES (v_nama, p_harga, p_stok);

    SELECT LAST_INSERT_ID() AS id_produk_baru;
END$$

CREATE PROCEDURE sp_produk_ubah(
    IN p_id_produk INT,
    IN p_nama_produk VARCHAR(100),
    IN p_harga DECIMAL(14,2),
    IN p_stok INT
)
BEGIN
    DECLARE v_nama VARCHAR(100);

    SET v_nama = TRIM(COALESCE(p_nama_produk, ''));

    IF p_id_produk IS NULL OR p_id_produk <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ID produk tidak valid.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM produk
        WHERE id_produk = p_id_produk
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Produk tidak ditemukan.';
    END IF;

    IF v_nama = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Nama produk wajib diisi.';
    END IF;

    IF p_harga IS NULL OR p_harga <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Harga harus lebih dari 0.';
    END IF;

    IF p_stok IS NULL OR p_stok < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stok tidak boleh negatif.';
    END IF;

    UPDATE produk
    SET
        nama_produk = v_nama,
        harga = p_harga,
        stok = p_stok
    WHERE id_produk = p_id_produk;
END$$

CREATE PROCEDURE sp_produk_hapus(
    IN p_id_produk INT
)
BEGIN
    IF p_id_produk IS NULL OR p_id_produk <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ID produk tidak valid.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM produk
        WHERE id_produk = p_id_produk
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Produk tidak ditemukan.';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM detail_transaksi
        WHERE id_produk = p_id_produk
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Produk sudah digunakan pada transaksi dan tidak boleh dihapus.';
    END IF;

    DELETE FROM produk
    WHERE id_produk = p_id_produk;
END$$

-- ============================================================
-- 5. STORED PROCEDURE PELANGGAN
-- Disiapkan untuk tahap berikutnya.
-- ============================================================

CREATE PROCEDURE sp_pelanggan_tampil()
BEGIN
    SELECT
        id_pelanggan,
        nama,
        alamat,
        no_hp,
        email,
        created_at,
        updated_at
    FROM pelanggan
    ORDER BY id_pelanggan DESC;
END$$

CREATE PROCEDURE sp_pelanggan_cari(
    IN p_keyword VARCHAR(100)
)
BEGIN
    DECLARE v_keyword VARCHAR(100);
    SET v_keyword = TRIM(COALESCE(p_keyword, ''));

    SELECT
        id_pelanggan,
        nama,
        alamat,
        no_hp,
        email,
        created_at,
        updated_at
    FROM pelanggan
    WHERE
        v_keyword = ''
        OR nama LIKE CONCAT('%', v_keyword, '%')
        OR COALESCE(email, '') LIKE CONCAT('%', v_keyword, '%')
        OR COALESCE(no_hp, '') LIKE CONCAT('%', v_keyword, '%')
    ORDER BY id_pelanggan DESC;
END$$

CREATE PROCEDURE sp_pelanggan_detail(
    IN p_id_pelanggan INT
)
BEGIN
    IF p_id_pelanggan IS NULL OR p_id_pelanggan <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ID pelanggan tidak valid.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pelanggan
        WHERE id_pelanggan = p_id_pelanggan
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pelanggan tidak ditemukan.';
    END IF;

    SELECT
        id_pelanggan,
        nama,
        alamat,
        no_hp,
        email,
        created_at,
        updated_at
    FROM pelanggan
    WHERE id_pelanggan = p_id_pelanggan
    LIMIT 1;
END$$

CREATE PROCEDURE sp_pelanggan_tambah(
    IN p_nama VARCHAR(100),
    IN p_alamat VARCHAR(255),
    IN p_no_hp VARCHAR(20),
    IN p_email VARCHAR(100)
)
BEGIN
    DECLARE v_nama VARCHAR(100);
    SET v_nama = TRIM(COALESCE(p_nama, ''));

    IF v_nama = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Nama pelanggan wajib diisi.';
    END IF;

    INSERT INTO pelanggan (nama, alamat, no_hp, email)
    VALUES (
        v_nama,
        NULLIF(TRIM(COALESCE(p_alamat, '')), ''),
        NULLIF(TRIM(COALESCE(p_no_hp, '')), ''),
        NULLIF(TRIM(COALESCE(p_email, '')), '')
    );

    SELECT LAST_INSERT_ID() AS id_pelanggan_baru;
END$$

CREATE PROCEDURE sp_pelanggan_ubah(
    IN p_id_pelanggan INT,
    IN p_nama VARCHAR(100),
    IN p_alamat VARCHAR(255),
    IN p_no_hp VARCHAR(20),
    IN p_email VARCHAR(100)
)
BEGIN
    DECLARE v_nama VARCHAR(100);
    SET v_nama = TRIM(COALESCE(p_nama, ''));

    IF p_id_pelanggan IS NULL OR p_id_pelanggan <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ID pelanggan tidak valid.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pelanggan
        WHERE id_pelanggan = p_id_pelanggan
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pelanggan tidak ditemukan.';
    END IF;

    IF v_nama = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Nama pelanggan wajib diisi.';
    END IF;

    UPDATE pelanggan
    SET
        nama = v_nama,
        alamat = NULLIF(TRIM(COALESCE(p_alamat, '')), ''),
        no_hp = NULLIF(TRIM(COALESCE(p_no_hp, '')), ''),
        email = NULLIF(TRIM(COALESCE(p_email, '')), '')
    WHERE id_pelanggan = p_id_pelanggan;
END$$

CREATE PROCEDURE sp_pelanggan_hapus(
    IN p_id_pelanggan INT
)
BEGIN
    IF p_id_pelanggan IS NULL OR p_id_pelanggan <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ID pelanggan tidak valid.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pelanggan
        WHERE id_pelanggan = p_id_pelanggan
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pelanggan tidak ditemukan.';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM transaksi
        WHERE id_pelanggan = p_id_pelanggan
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pelanggan sudah memiliki transaksi dan tidak boleh dihapus.';
    END IF;

    DELETE FROM pelanggan
    WHERE id_pelanggan = p_id_pelanggan;
END$$

-- ============================================================
-- 6. TRANSAKSI PENJUALAN
-- Versi awal: satu produk per transaksi.
-- Menggunakan START TRANSACTION / COMMIT / ROLLBACK + handler.
-- ============================================================

CREATE PROCEDURE sp_penjualan_satu_produk(
    IN p_id_pelanggan INT,
    IN p_id_produk INT,
    IN p_qty INT
)
BEGIN
    DECLARE v_stok INT DEFAULT NULL;
    DECLARE v_harga DECIMAL(14,2) DEFAULT NULL;
    DECLARE v_subtotal DECIMAL(16,2) DEFAULT 0;
    DECLARE v_id_transaksi BIGINT UNSIGNED DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF p_qty IS NULL OR p_qty <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Qty harus lebih dari 0.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pelanggan
        WHERE id_pelanggan = p_id_pelanggan
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pelanggan tidak ditemukan.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM produk
        WHERE id_produk = p_id_produk
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Produk tidak ditemukan.';
    END IF;

    SELECT stok, harga
    INTO v_stok, v_harga
    FROM produk
    WHERE id_produk = p_id_produk
    FOR UPDATE;

    IF v_stok < p_qty THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stok produk tidak mencukupi.';
    END IF;

    SET v_subtotal = fn_hitung_subtotal(v_harga, p_qty);

    INSERT INTO transaksi (
        id_pelanggan,
        tanggal,
        total,
        status
    ) VALUES (
        p_id_pelanggan,
        NOW(),
        v_subtotal,
        'SELESAI'
    );

    SET v_id_transaksi = LAST_INSERT_ID();

    INSERT INTO detail_transaksi (
        id_transaksi,
        id_produk,
        qty,
        harga,
        subtotal
    ) VALUES (
        v_id_transaksi,
        p_id_produk,
        p_qty,
        v_harga,
        v_subtotal
    );

    UPDATE produk
    SET stok = stok - p_qty
    WHERE id_produk = p_id_produk;

    COMMIT;

    SELECT
        v_id_transaksi AS id_transaksi,
        v_subtotal AS total;
END$$

CREATE PROCEDURE sp_transaksi_tampil()
BEGIN
    SELECT
        t.id_transaksi,
        t.tanggal,
        p.nama AS pelanggan,
        t.total,
        t.status
    FROM transaksi t
    INNER JOIN pelanggan p
        ON p.id_pelanggan = t.id_pelanggan
    ORDER BY t.id_transaksi DESC;
END$$

CREATE PROCEDURE sp_transaksi_detail(
    IN p_id_transaksi BIGINT
)
BEGIN
    SELECT
        t.id_transaksi,
        t.tanggal,
        pl.nama AS pelanggan,
        pr.nama_produk,
        d.qty,
        d.harga,
        d.subtotal,
        t.total,
        t.status
    FROM transaksi t
    INNER JOIN pelanggan pl
        ON pl.id_pelanggan = t.id_pelanggan
    INNER JOIN detail_transaksi d
        ON d.id_transaksi = t.id_transaksi
    INNER JOIN produk pr
        ON pr.id_produk = d.id_produk
    WHERE t.id_transaksi = p_id_transaksi
    ORDER BY d.id_detail;
END$$

-- ============================================================
-- 7. TRIGGER AUDIT PRODUK
-- ============================================================

CREATE TRIGGER trg_produk_insert
AFTER INSERT ON produk
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        aktivitas,
        nama_tabel,
        id_data,
        keterangan
    ) VALUES (
        'INSERT',
        'produk',
        NEW.id_produk,
        CONCAT(
            'Tambah produk: ', NEW.nama_produk,
            ', harga=', NEW.harga,
            ', stok=', NEW.stok
        )
    );
END$$

CREATE TRIGGER trg_produk_update
AFTER UPDATE ON produk
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        aktivitas,
        nama_tabel,
        id_data,
        keterangan
    ) VALUES (
        'UPDATE',
        'produk',
        NEW.id_produk,
        CONCAT(
            'Ubah produk: ', OLD.nama_produk, ' -> ', NEW.nama_produk,
            '; harga ', OLD.harga, ' -> ', NEW.harga,
            '; stok ', OLD.stok, ' -> ', NEW.stok
        )
    );
END$$

CREATE TRIGGER trg_produk_delete
AFTER DELETE ON produk
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        aktivitas,
        nama_tabel,
        id_data,
        keterangan
    ) VALUES (
        'DELETE',
        'produk',
        OLD.id_produk,
        CONCAT('Hapus produk: ', OLD.nama_produk)
    );
END$$

DELIMITER ;

-- ============================================================
-- 8. VIEW LAPORAN
-- ============================================================

CREATE VIEW v_laporan_penjualan AS
SELECT
    t.id_transaksi,
    t.tanggal,
    p.id_pelanggan,
    p.nama AS pelanggan,
    t.total,
    t.status
FROM transaksi t
INNER JOIN pelanggan p
    ON p.id_pelanggan = t.id_pelanggan;

CREATE VIEW v_detail_penjualan AS
SELECT
    t.id_transaksi,
    t.tanggal,
    pl.nama AS pelanggan,
    pr.id_produk,
    pr.nama_produk,
    d.qty,
    d.harga,
    d.subtotal,
    t.status
FROM detail_transaksi d
INNER JOIN transaksi t
    ON t.id_transaksi = d.id_transaksi
INNER JOIN pelanggan pl
    ON pl.id_pelanggan = t.id_pelanggan
INNER JOIN produk pr
    ON pr.id_produk = d.id_produk;

-- ============================================================
-- 9. USER APLIKASI + DCL
-- Aplikasi hanya diberi hak EXECUTE pada stored program.
-- Tidak diberi SELECT/INSERT/UPDATE/DELETE langsung ke tabel.
-- ============================================================

DROP USER IF EXISTS 'app_penjualan'@'localhost';
CREATE USER 'app_penjualan'@'localhost'
IDENTIFIED BY 'AppPenjualan#2026';

DROP USER IF EXISTS 'app_penjualan'@'127.0.0.1';
CREATE USER 'app_penjualan'@'127.0.0.1'
IDENTIFIED BY 'AppPenjualan#2026';

GRANT EXECUTE ON db_penjualan.* TO 'app_penjualan'@'localhost';
GRANT EXECUTE ON db_penjualan.* TO 'app_penjualan'@'127.0.0.1';

-- ============================================================
-- 10. SELESAI
-- ============================================================
-- PENTING:
-- Jangan menjalankan CALL stored procedure yang menghasilkan result set
-- di dalam proses IMPORT phpMyAdmin. Pada sebagian versi phpMyAdmin/MariaDB
-- hal itu dapat memicu error:
-- #2014 - Commands out of sync; you can't run this command now
--
-- Setelah import selesai, lakukan pengujian secara TERPISAH melalui tab SQL:
--
-- USE db_penjualan;
-- CALL sp_produk_tampil();
-- SELECT 'IMPORT BERHASIL' AS status, DATABASE() AS database_aktif, VERSION() AS versi_dbms;
