-- ============================================================
-- STEP 3 - TRANSAKSI PENJUALAN MULTI-PRODUK
-- Jalankan SEKALI setelah Step 1/Step 2 sudah berhasil.
-- Target: XAMPP MySQL/MariaDB + phpMyAdmin
-- ============================================================

USE db_penjualan;

-- Tambahkan status DRAFT agar transaksi dapat disusun sebagai keranjang
-- sebelum stok benar-benar dikurangi.
ALTER TABLE transaksi
    MODIFY status ENUM('DRAFT','SELESAI','DIBATALKAN')
    NOT NULL DEFAULT 'DRAFT';

DROP PROCEDURE IF EXISTS sp_transaksi_buat_draft;
DROP PROCEDURE IF EXISTS sp_transaksi_draft_header;
DROP PROCEDURE IF EXISTS sp_transaksi_draft_detail;
DROP PROCEDURE IF EXISTS sp_transaksi_tambah_item;
DROP PROCEDURE IF EXISTS sp_transaksi_ubah_item;
DROP PROCEDURE IF EXISTS sp_transaksi_hapus_item;
DROP PROCEDURE IF EXISTS sp_transaksi_finalisasi;
DROP PROCEDURE IF EXISTS sp_transaksi_batalkan_draft;
DROP PROCEDURE IF EXISTS sp_transaksi_tampil;
DROP PROCEDURE IF EXISTS sp_transaksi_detail;

DELIMITER $$

-- ============================================================
-- Membuat transaksi kosong (DRAFT)
-- ============================================================
CREATE PROCEDURE sp_transaksi_buat_draft(
    IN p_id_pelanggan INT
)
BEGIN
    IF p_id_pelanggan IS NULL OR p_id_pelanggan <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pelanggan wajib dipilih.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pelanggan
        WHERE id_pelanggan = p_id_pelanggan
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pelanggan tidak ditemukan.';
    END IF;

    INSERT INTO transaksi (
        id_pelanggan,
        tanggal,
        total,
        status
    ) VALUES (
        p_id_pelanggan,
        NOW(),
        0,
        'DRAFT'
    );

    SELECT LAST_INSERT_ID() AS id_transaksi;
END$$

-- ============================================================
-- Header draft + identitas pelanggan
-- ============================================================
CREATE PROCEDURE sp_transaksi_draft_header(
    IN p_id_transaksi BIGINT
)
BEGIN
    DECLARE v_status VARCHAR(20);

    IF p_id_transaksi IS NULL OR p_id_transaksi <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ID transaksi tidak valid.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM transaksi
        WHERE id_transaksi = p_id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi tidak ditemukan.';
    END IF;

    SELECT status
    INTO v_status
    FROM transaksi
    WHERE id_transaksi = p_id_transaksi;

    IF v_status <> 'DRAFT' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi ini sudah tidak dapat diedit.';
    END IF;

    SELECT
        t.id_transaksi,
        t.tanggal,
        t.id_pelanggan,
        p.nama AS pelanggan,
        p.no_hp,
        p.email,
        t.total,
        t.status
    FROM transaksi t
    INNER JOIN pelanggan p
        ON p.id_pelanggan = t.id_pelanggan
    WHERE t.id_transaksi = p_id_transaksi
    LIMIT 1;
END$$

-- ============================================================
-- Isi keranjang / detail draft
-- ============================================================
CREATE PROCEDURE sp_transaksi_draft_detail(
    IN p_id_transaksi BIGINT
)
BEGIN
    SELECT
        d.id_detail,
        d.id_transaksi,
        d.id_produk,
        p.nama_produk,
        d.qty,
        d.harga,
        d.subtotal,
        p.stok AS stok_saat_ini
    FROM detail_transaksi d
    INNER JOIN produk p
        ON p.id_produk = d.id_produk
    WHERE d.id_transaksi = p_id_transaksi
    ORDER BY d.id_detail;
END$$

-- ============================================================
-- Tambah produk ke draft.
-- Jika produk sudah ada, qty akan ditambahkan.
-- Belum mengurangi stok fisik; stok dikurangi saat finalisasi.
-- ============================================================
CREATE PROCEDURE sp_transaksi_tambah_item(
    IN p_id_transaksi BIGINT,
    IN p_id_produk INT,
    IN p_qty INT
)
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_stok INT;
    DECLARE v_harga DECIMAL(14,2);
    DECLARE v_qty_lama INT DEFAULT 0;
    DECLARE v_qty_baru INT DEFAULT 0;
    DECLARE v_id_detail BIGINT DEFAULT NULL;

    IF p_qty IS NULL OR p_qty <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Qty harus lebih dari 0.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM transaksi
        WHERE id_transaksi = p_id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi tidak ditemukan.';
    END IF;

    SELECT status
    INTO v_status
    FROM transaksi
    WHERE id_transaksi = p_id_transaksi;

    IF v_status <> 'DRAFT' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi sudah tidak dapat diubah.';
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
    WHERE id_produk = p_id_produk;

    IF EXISTS (
        SELECT 1
        FROM detail_transaksi
        WHERE id_transaksi = p_id_transaksi
          AND id_produk = p_id_produk
    ) THEN
        SELECT id_detail, qty
        INTO v_id_detail, v_qty_lama
        FROM detail_transaksi
        WHERE id_transaksi = p_id_transaksi
          AND id_produk = p_id_produk
        ORDER BY id_detail
        LIMIT 1;

        SET v_qty_baru = v_qty_lama + p_qty;

        IF v_qty_baru > v_stok THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Qty melebihi stok produk yang tersedia.';
        END IF;

        UPDATE detail_transaksi
        SET
            qty = v_qty_baru,
            harga = v_harga,
            subtotal = fn_hitung_subtotal(v_harga, v_qty_baru)
        WHERE id_detail = v_id_detail;
    ELSE
        IF p_qty > v_stok THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Qty melebihi stok produk yang tersedia.';
        END IF;

        INSERT INTO detail_transaksi (
            id_transaksi,
            id_produk,
            qty,
            harga,
            subtotal
        ) VALUES (
            p_id_transaksi,
            p_id_produk,
            p_qty,
            v_harga,
            fn_hitung_subtotal(v_harga, p_qty)
        );
    END IF;
END$$

-- ============================================================
-- Ubah qty item draft
-- ============================================================
CREATE PROCEDURE sp_transaksi_ubah_item(
    IN p_id_detail BIGINT,
    IN p_qty INT
)
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_id_produk INT;
    DECLARE v_stok INT;
    DECLARE v_harga DECIMAL(14,2);

    IF p_qty IS NULL OR p_qty <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Qty harus lebih dari 0.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM detail_transaksi
        WHERE id_detail = p_id_detail
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Item transaksi tidak ditemukan.';
    END IF;

    SELECT
        t.status,
        d.id_produk,
        p.stok,
        p.harga
    INTO
        v_status,
        v_id_produk,
        v_stok,
        v_harga
    FROM detail_transaksi d
    INNER JOIN transaksi t
        ON t.id_transaksi = d.id_transaksi
    INNER JOIN produk p
        ON p.id_produk = d.id_produk
    WHERE d.id_detail = p_id_detail
    LIMIT 1;

    IF v_status <> 'DRAFT' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi sudah tidak dapat diubah.';
    END IF;

    IF p_qty > v_stok THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Qty melebihi stok produk yang tersedia.';
    END IF;

    UPDATE detail_transaksi
    SET
        qty = p_qty,
        harga = v_harga,
        subtotal = fn_hitung_subtotal(v_harga, p_qty)
    WHERE id_detail = p_id_detail;
END$$

-- ============================================================
-- Hapus item dari draft
-- ============================================================
CREATE PROCEDURE sp_transaksi_hapus_item(
    IN p_id_detail BIGINT
)
BEGIN
    DECLARE v_status VARCHAR(20);

    IF NOT EXISTS (
        SELECT 1
        FROM detail_transaksi
        WHERE id_detail = p_id_detail
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Item transaksi tidak ditemukan.';
    END IF;

    SELECT t.status
    INTO v_status
    FROM detail_transaksi d
    INNER JOIN transaksi t
        ON t.id_transaksi = d.id_transaksi
    WHERE d.id_detail = p_id_detail
    LIMIT 1;

    IF v_status <> 'DRAFT' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi sudah tidak dapat diubah.';
    END IF;

    DELETE FROM detail_transaksi
    WHERE id_detail = p_id_detail;
END$$

-- ============================================================
-- FINALISASI TRANSAKSI
-- Inilah proses utama:
-- START TRANSACTION -> CURSOR/LOOP -> FOR UPDATE -> validasi stok
-- -> hitung subtotal -> kurangi stok -> hitung total -> COMMIT.
-- Jika ada error, EXIT HANDLER menjalankan ROLLBACK.
-- ============================================================
CREATE PROCEDURE sp_transaksi_finalisasi(
    IN p_id_transaksi BIGINT
)
BEGIN
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_id_produk INT;
    DECLARE v_qty INT;
    DECLARE v_stok INT;
    DECLARE v_harga DECIMAL(14,2);
    DECLARE v_subtotal DECIMAL(16,2);
    DECLARE v_total DECIMAL(16,2) DEFAULT 0;
    DECLARE v_status VARCHAR(20);
    DECLARE v_jumlah_item INT DEFAULT 0;

    DECLARE cur_item CURSOR FOR
        SELECT id_produk, qty
        FROM detail_transaksi
        WHERE id_transaksi = p_id_transaksi
        ORDER BY id_detail;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF p_id_transaksi IS NULL OR p_id_transaksi <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ID transaksi tidak valid.';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM transaksi
        WHERE id_transaksi = p_id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi tidak ditemukan.';
    END IF;

    SELECT status
    INTO v_status
    FROM transaksi
    WHERE id_transaksi = p_id_transaksi
    FOR UPDATE;

    IF v_status <> 'DRAFT' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi sudah pernah diproses.';
    END IF;

    SELECT COUNT(*)
    INTO v_jumlah_item
    FROM detail_transaksi
    WHERE id_transaksi = p_id_transaksi;

    IF v_jumlah_item = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Keranjang transaksi masih kosong.';
    END IF;

    SET v_total = 0;
    SET v_done = 0;

    OPEN cur_item;

    read_loop: LOOP
        FETCH cur_item INTO v_id_produk, v_qty;

        IF v_done = 1 THEN
            LEAVE read_loop;
        END IF;

        SELECT stok, harga
        INTO v_stok, v_harga
        FROM produk
        WHERE id_produk = v_id_produk
        FOR UPDATE;

        IF v_stok < v_qty THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Finalisasi gagal: stok salah satu produk tidak mencukupi.';
        END IF;

        SET v_subtotal = fn_hitung_subtotal(v_harga, v_qty);

        UPDATE detail_transaksi
        SET
            harga = v_harga,
            subtotal = v_subtotal
        WHERE id_transaksi = p_id_transaksi
          AND id_produk = v_id_produk;

        UPDATE produk
        SET stok = stok - v_qty
        WHERE id_produk = v_id_produk;

        SET v_total = v_total + v_subtotal;
    END LOOP;

    CLOSE cur_item;

    UPDATE transaksi
    SET
        total = v_total,
        status = 'SELESAI'
    WHERE id_transaksi = p_id_transaksi;

    COMMIT;

    SELECT
        p_id_transaksi AS id_transaksi,
        v_total AS total,
        'SELESAI' AS status;
END$$

-- ============================================================
-- Batalkan draft tanpa mengurangi stok
-- ============================================================
CREATE PROCEDURE sp_transaksi_batalkan_draft(
    IN p_id_transaksi BIGINT
)
BEGIN
    DECLARE v_status VARCHAR(20);

    IF NOT EXISTS (
        SELECT 1
        FROM transaksi
        WHERE id_transaksi = p_id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi tidak ditemukan.';
    END IF;

    SELECT status
    INTO v_status
    FROM transaksi
    WHERE id_transaksi = p_id_transaksi;

    IF v_status <> 'DRAFT' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Hanya transaksi draft yang dapat dibatalkan.';
    END IF;

    UPDATE transaksi
    SET
        total = 0,
        status = 'DIBATALKAN'
    WHERE id_transaksi = p_id_transaksi;
END$$

-- ============================================================
-- Riwayat transaksi
-- ============================================================
CREATE PROCEDURE sp_transaksi_tampil()
BEGIN
    SELECT
        t.id_transaksi,
        t.tanggal,
        p.nama AS pelanggan,
        COUNT(d.id_detail) AS jumlah_baris,
        COALESCE(SUM(d.qty), 0) AS jumlah_barang,
        t.total,
        t.status
    FROM transaksi t
    INNER JOIN pelanggan p
        ON p.id_pelanggan = t.id_pelanggan
    LEFT JOIN detail_transaksi d
        ON d.id_transaksi = t.id_transaksi
    GROUP BY
        t.id_transaksi,
        t.tanggal,
        p.nama,
        t.total,
        t.status
    ORDER BY t.id_transaksi DESC;
END$$

-- ============================================================
-- Detail transaksi selesai/dibatalkan/draft
-- ============================================================
CREATE PROCEDURE sp_transaksi_detail(
    IN p_id_transaksi BIGINT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM transaksi
        WHERE id_transaksi = p_id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaksi tidak ditemukan.';
    END IF;

    SELECT
        t.id_transaksi,
        t.tanggal,
        pl.nama AS pelanggan,
        pl.no_hp,
        pl.email,
        t.total,
        t.status,
        d.id_detail,
        d.id_produk,
        pr.nama_produk,
        d.qty,
        d.harga,
        d.subtotal
    FROM transaksi t
    INNER JOIN pelanggan pl
        ON pl.id_pelanggan = t.id_pelanggan
    LEFT JOIN detail_transaksi d
        ON d.id_transaksi = t.id_transaksi
    LEFT JOIN produk pr
        ON pr.id_produk = d.id_produk
    WHERE t.id_transaksi = p_id_transaksi
    ORDER BY d.id_detail;
END$$

DELIMITER ;

-- Tidak ada CALL di akhir file agar aman saat Import phpMyAdmin.
-- Setelah import selesai, uji secara terpisah:
-- CALL sp_transaksi_tampil();
