# STEP 1 — Aplikasi Pengelolaan Data Penjualan

Teknologi:
- XAMPP
- Apache
- MySQL/MariaDB
- phpMyAdmin
- PHP Native + PDO

## 1. Salin folder proyek

Salin folder `penjualan` ini ke:

C:\xampp\htdocs\penjualan

Jika Anda mengekstrak ZIP yang diberikan, pastikan hasil akhirnya seperti:

C:\xampp\htdocs\penjualan\index.php
C:\xampp\htdocs\penjualan\db_penjualan.sql
C:\xampp\htdocs\penjualan\config\database.php
C:\xampp\htdocs\penjualan\produk\index.php

JANGAN sampai menjadi:

C:\xampp\htdocs\penjualan\penjualan_step1\index.php

## 2. Nyalakan XAMPP

Jalankan:
- Apache
- MySQL

Keduanya harus berstatus Running.

## 3. Import database

1. Buka http://localhost/phpmyadmin
2. Klik menu Import.
3. Pilih file `db_penjualan.sql`.
4. Klik Import / Go.
5. Di akhir import akan tampil hasil:
   - daftar produk
   - status `IMPORT BERHASIL`

Script SQL akan membuat:
- database `db_penjualan`
- tabel pelanggan
- tabel produk
- tabel transaksi
- tabel detail_transaksi
- tabel audit_log
- function `fn_hitung_subtotal`
- stored procedure produk
- stored procedure pelanggan
- stored procedure transaksi
- trigger audit produk
- view laporan
- user aplikasi `app_penjualan`

User aplikasi:
- username: app_penjualan
- password: AppPenjualan#2026

## 4. Buka aplikasi

Buka:

http://localhost/penjualan/

Lalu klik `Buka Data Produk`.

Atau langsung:

http://localhost/penjualan/produk/

## 5. Tes CRUD produk

Coba urutan berikut:

1. Tambah produk baru.
2. Cari produk dari kotak pencarian.
3. Edit nama/harga/stok.
4. Hapus produk yang belum pernah dipakai transaksi.

Semua operasi PHP memanggil stored procedure:
- `sp_produk_tampil`
- `sp_produk_cari`
- `sp_produk_detail`
- `sp_produk_tambah`
- `sp_produk_ubah`
- `sp_produk_hapus`

PHP tidak melakukan INSERT/UPDATE/DELETE langsung ke tabel produk.

## 6. Jika koneksi database gagal

Buka:

config/database.php

Default yang dipakai:

host     = 127.0.0.1
port     = 3306
database = db_penjualan
user     = app_penjualan
password = AppPenjualan#2026

Jika port MySQL XAMPP Anda bukan 3306, ubah nilai `$port`.

## 7. Tes langsung dari phpMyAdmin

Jalankan:

CALL sp_produk_tampil();

CALL sp_produk_cari('mouse');

CALL sp_produk_tambah('Webcam', 250000, 7);

CALL sp_produk_ubah(1, 'Keyboard Mechanical Pro', 399000, 15);

SELECT * FROM audit_log ORDER BY id_log DESC;

## 8. Catatan penting

File `db_penjualan.sql` dirancang agar dapat di-import ulang dengan bersih.
Import ulang akan menghapus lalu membuat ulang tabel proyek, sehingga data
yang sudah dimasukkan ke `db_penjualan` akan hilang.

Jangan import ulang jika Anda sudah memiliki data penting tanpa backup.


## Jika sebelumnya muncul #2014 Commands out of sync

Gunakan file `db_penjualan.sql` versi v2 ini dan import ulang.

File ini:
- memakai `SET FOREIGN_KEY_CHECKS = 0` dan `= 1`, bukan `ON/OFF`;
- tidak menjalankan `CALL sp_produk_tampil()` saat proses Import;
- memindahkan tes stored procedure ke langkah setelah import.

Setelah import benar-benar selesai, buka tab SQL dan jalankan tes ini secara terpisah:

USE db_penjualan;
CALL sp_produk_tampil();

Setelah hasil CALL tampil, jangan gabungkan dengan query lain dalam eksekusi yang sama.
Jalankan query berikut pada eksekusi baru:

SELECT 'IMPORT BERHASIL' AS status, DATABASE() AS database_aktif, VERSION() AS versi_dbms;


# STEP 2 — Modul Data Pelanggan

Tidak perlu import SQL lagi jika Step 1 sudah berhasil, karena stored procedure pelanggan
sudah dibuat pada `db_penjualan.sql`.

File baru:
- pelanggan/index.php
- pelanggan/tambah.php
- pelanggan/edit.php
- pelanggan/hapus.php

Buka:
http://localhost/penjualan/pelanggan/

Tes:
1. Tambah pelanggan.
2. Cari pelanggan berdasarkan nama/email/no. HP.
3. Edit pelanggan.
4. Hapus pelanggan yang belum mempunyai transaksi.

Stored procedure yang dipakai:
- sp_pelanggan_tampil
- sp_pelanggan_cari
- sp_pelanggan_detail
- sp_pelanggan_tambah
- sp_pelanggan_ubah
- sp_pelanggan_hapus

Jika pelanggan sudah memiliki transaksi, procedure akan menolak penghapusan agar relasi
foreign key tetap konsisten.
