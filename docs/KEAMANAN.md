# Dokumentasi Keamanan

## Lapisan keamanan yang diterapkan

1. **User database terbatas**
   - PHP menggunakan `app_penjualan`, bukan `root`.
   - Hak aplikasi diberikan melalui `GRANT EXECUTE`.
   - CRUD bisnis dilakukan lewat stored procedure.

2. **Prepared statement PDO**
   - Parameter user tidak digabung langsung ke string SQL.

3. **Password hashing**
   - Password pengguna disimpan dengan `password_hash()`.
   - Login memverifikasi dengan `password_verify()`.
   - Password plaintext tidak disimpan di database.

4. **Session**
   - Login menyimpan identitas minimum ke `$_SESSION`.
   - Setelah login dilakukan `session_regenerate_id(true)`.

5. **Role**
   - `ADMIN`: seluruh modul termasuk Audit Log dan Pengguna.
   - `KASIR`: dashboard, master data, transaksi, dan laporan.
   - Halaman audit dan pengguna memakai `require_role('ADMIN')`.

6. **CSRF**
   - Form yang mengubah data memakai token acak.
   - Request POST diverifikasi dengan `hash_equals()`.

7. **XSS**
   - Output user ke HTML memakai helper `e()` yang memanggil `htmlspecialchars()`.

8. **Validasi database**
   - Stored procedure tetap memvalidasi ID, stok, harga, qty, dan status transaksi.
   - Validasi PHP hanya lapisan pertama; DBMS tetap menjadi sumber aturan utama.

## Akun demo

- ADMIN: `admin` / `Admin#2026`
- KASIR: `kasir` / `Kasir#2026`

Password demo wajib diganti bila aplikasi dipakai di luar lingkungan tugas/demonstrasi.
