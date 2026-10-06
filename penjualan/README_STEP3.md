# STEP 3 — Transaksi Penjualan Multi-Produk

Step 3 menambahkan:
- transaksi berstatus DRAFT;
- pilih pelanggan;
- keranjang beberapa produk;
- tambah/ubah/hapus item keranjang;
- pengecekan stok;
- finalisasi transaksi;
- START TRANSACTION;
- COMMIT;
- ROLLBACK melalui EXIT HANDLER;
- SELECT ... FOR UPDATE;
- CURSOR dan LOOP;
- perhitungan subtotal memakai function `fn_hitung_subtotal`;
- pengurangan stok hanya saat finalisasi;
- riwayat transaksi;
- detail transaksi.

## PENTING: import SQL Step 3 terlebih dahulu

Jangan import ulang `db_penjualan.sql` Step 1 karena itu akan mereset data.

Buka phpMyAdmin, pilih database `db_penjualan`, lalu Import:

sql/step3_transaksi.sql

Atau buka tab SQL dan jalankan isi file tersebut.

File Step 3 sengaja TIDAK menjalankan CALL di bagian akhir agar tidak memicu
`#2014 Commands out of sync` saat import phpMyAdmin.

## Setelah import

Uji satu query secara terpisah:

CALL sp_transaksi_tampil();

Jika berhasil, buka:

http://localhost/penjualan/transaksi/

## Alur transaksi

1. Klik `+ Transaksi Baru`.
2. Pilih pelanggan.
3. Sistem membuat transaksi DRAFT.
4. Tambahkan satu atau beberapa produk.
5. Ubah qty bila perlu.
6. Klik `Finalisasi Transaksi`.
7. Database:
   - mengunci header transaksi;
   - membaca semua item menggunakan CURSOR;
   - mengunci produk dengan `FOR UPDATE`;
   - memeriksa stok;
   - menghitung subtotal;
   - mengurangi stok;
   - menghitung total;
   - mengubah status menjadi SELESAI;
   - COMMIT.
8. Jika salah satu stok tidak cukup, handler menjalankan ROLLBACK.

## Tes penting

Sebelum transaksi:
CALL sp_produk_tampil();

Buat transaksi dari web, lalu setelah finalisasi:
CALL sp_produk_tampil();

Stok barang yang dibeli harus berkurang.

Riwayat:
CALL sp_transaksi_tampil();

Audit perubahan stok:
SELECT * FROM audit_log ORDER BY id_log DESC;

Trigger audit produk dari Step 1 akan mencatat UPDATE stok saat transaksi difinalisasi.

## Catatan

Transaksi DRAFT tidak mengurangi stok. Ini disengaja.
Stok baru benar-benar berubah pada saat finalisasi sehingga transaksi database dapat
melakukan COMMIT/ROLLBACK secara utuh.
