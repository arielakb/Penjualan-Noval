-- ============================================================
-- STEP 4 - DASHBOARD, LAPORAN PENJUALAN, DAN AUDIT LOG
-- Jalankan SEKALI setelah Step 3 berhasil.
-- Target: XAMPP MySQL/MariaDB + phpMyAdmin
-- ============================================================

USE db_penjualan;

DROP PROCEDURE IF EXISTS sp_dashboard_ringkasan;
DROP PROCEDURE IF EXISTS sp_dashboard_transaksi_terbaru;
DROP PROCEDURE IF EXISTS sp_dashboard_stok_rendah;
DROP PROCEDURE IF EXISTS sp_laporan_penjualan;
DROP PROCEDURE IF EXISTS sp_laporan_produk_terlaris;
DROP PROCEDURE IF EXISTS sp_audit_tampil;

DELIMITER $$

-- ============================================================
-- 1. Ringkasan dashboard
-- ============================================================
CREATE PROCEDURE sp_dashboard_ringkasan()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM pelanggan) AS jumlah_pelanggan,
        (SELECT COUNT(*) FROM produk) AS jumlah_produk,
        (
            SELECT COUNT(*)
            FROM transaksi
            WHERE status = 'SELESAI'
        ) AS jumlah_transaksi_selesai,
        (
            SELECT COALESCE(SUM(total), 0)
            FROM transaksi
            WHERE status = 'SELESAI'
        ) AS total_omzet,
        (
            SELECT COUNT(*)
            FROM produk
            WHERE stok <= 5
        ) AS produk_stok_rendah;
END$$

-- ============================================================
-- 2. Lima transaksi terbaru untuk dashboard
-- ============================================================
CREATE PROCEDURE sp_dashboard_transaksi_terbaru()
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
    WHERE t.status = 'SELESAI'
    ORDER BY t.id_transaksi DESC
    LIMIT 5;
END$$

-- ============================================================
-- 3. Produk stok rendah untuk dashboard
-- ============================================================
CREATE PROCEDURE sp_dashboard_stok_rendah()
BEGIN
    SELECT
        id_produk,
        nama_produk,
        harga,
        stok
    FROM produk
    WHERE stok <= 5
    ORDER BY stok ASC, nama_produk ASC
    LIMIT 10;
END$$

-- ============================================================
-- 4. Laporan penjualan berdasarkan VIEW v_laporan_penjualan
-- Parameter tanggal boleh NULL.
-- p_tanggal_mulai dan p_tanggal_selesai bertipe DATE.
-- ============================================================
CREATE PROCEDURE sp_laporan_penjualan(
    IN p_tanggal_mulai DATE,
    IN p_tanggal_selesai DATE
)
BEGIN
    IF p_tanggal_mulai IS NOT NULL
       AND p_tanggal_selesai IS NOT NULL
       AND p_tanggal_mulai > p_tanggal_selesai THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Tanggal mulai tidak boleh melebihi tanggal selesai.';
    END IF;

    SELECT
        id_transaksi,
        tanggal,
        id_pelanggan,
        pelanggan,
        total,
        status
    FROM v_laporan_penjualan
    WHERE status = 'SELESAI'
      AND (p_tanggal_mulai IS NULL OR DATE(tanggal) >= p_tanggal_mulai)
      AND (p_tanggal_selesai IS NULL OR DATE(tanggal) <= p_tanggal_selesai)
    ORDER BY tanggal DESC, id_transaksi DESC;
END$$

-- ============================================================
-- 5. Produk terlaris berdasarkan VIEW v_detail_penjualan
-- ============================================================
CREATE PROCEDURE sp_laporan_produk_terlaris(
    IN p_tanggal_mulai DATE,
    IN p_tanggal_selesai DATE
)
BEGIN
    IF p_tanggal_mulai IS NOT NULL
       AND p_tanggal_selesai IS NOT NULL
       AND p_tanggal_mulai > p_tanggal_selesai THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Tanggal mulai tidak boleh melebihi tanggal selesai.';
    END IF;

    SELECT
        id_produk,
        nama_produk,
        SUM(qty) AS total_qty,
        SUM(subtotal) AS total_penjualan
    FROM v_detail_penjualan
    WHERE status = 'SELESAI'
      AND (p_tanggal_mulai IS NULL OR DATE(tanggal) >= p_tanggal_mulai)
      AND (p_tanggal_selesai IS NULL OR DATE(tanggal) <= p_tanggal_selesai)
    GROUP BY
        id_produk,
        nama_produk
    ORDER BY total_qty DESC, total_penjualan DESC, nama_produk ASC
    LIMIT 10;
END$$

-- ============================================================
-- 6. Audit log
-- Keyword boleh kosong; limit dibatasi 1..200.
-- ============================================================
CREATE PROCEDURE sp_audit_tampil(
    IN p_keyword VARCHAR(100),
    IN p_limit INT
)
BEGIN
    DECLARE v_keyword VARCHAR(100);
    DECLARE v_limit INT DEFAULT 50;

    SET v_keyword = TRIM(COALESCE(p_keyword, ''));

    IF p_limit IS NOT NULL AND p_limit BETWEEN 1 AND 200 THEN
        SET v_limit = p_limit;
    END IF;

    SELECT
        id_log,
        aktivitas,
        nama_tabel,
        id_data,
        keterangan,
        waktu
    FROM audit_log
    WHERE
        v_keyword = ''
        OR aktivitas LIKE CONCAT('%', v_keyword, '%')
        OR nama_tabel LIKE CONCAT('%', v_keyword, '%')
        OR COALESCE(keterangan, '') LIKE CONCAT('%', v_keyword, '%')
        OR CAST(id_data AS CHAR) LIKE CONCAT('%', v_keyword, '%')
    ORDER BY id_log DESC
    LIMIT v_limit;
END$$

DELIMITER ;

-- ============================================================
-- DCL
-- Hak SELECT langsung pada tabel tetap TIDAK diberikan.
-- User aplikasi tetap mengakses data melalui stored procedure.
-- ============================================================

GRANT EXECUTE ON db_penjualan.* TO 'app_penjualan'@'localhost';
GRANT EXECUTE ON db_penjualan.* TO 'app_penjualan'@'127.0.0.1';

-- Tidak ada CALL pada akhir file agar aman saat Import phpMyAdmin.
