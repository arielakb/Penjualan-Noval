-- ============================================================
-- PENGUJIAN MANUAL DATABASE
-- Jalankan SATU BAGIAN pada satu waktu di phpMyAdmin.
-- ============================================================

USE db_penjualan;

-- A. Cek master data
CALL sp_pelanggan_tampil();
CALL sp_produk_tampil();

-- B. Cek dashboard
CALL sp_dashboard_ringkasan();

-- C. Cek laporan
CALL sp_laporan_penjualan(NULL, NULL);
CALL sp_laporan_produk_terlaris(NULL, NULL);

-- D. Cek audit
CALL sp_audit_tampil('', 50);

-- E. Cek pengguna
CALL sp_pengguna_tampil();

-- F. Cek view langsung menggunakan akun root/phpMyAdmin
SELECT * FROM v_laporan_penjualan ORDER BY id_transaksi DESC;
SELECT * FROM v_detail_penjualan ORDER BY id_transaksi DESC, id_produk;

-- G. Cek privilege user aplikasi
SHOW GRANTS FOR 'app_penjualan'@'localhost';
