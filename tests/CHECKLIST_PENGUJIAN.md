# Checklist Pengujian Akhir

Gunakan checklist ini dan ambil screenshot bagian penting untuk bukti asesmen.

| No | Skenario | Hasil yang diharapkan |
|---:|---|---|
| 1 | Login ADMIN benar | Masuk dashboard |
| 2 | Login password salah | Ditolak |
| 3 | Login KASIR | Masuk dashboard |
| 4 | KASIR membuka `/audit/` | HTTP 403 |
| 5 | Tambah pelanggan | Data muncul |
| 6 | Edit pelanggan | Data berubah |
| 7 | Hapus pelanggan tanpa transaksi | Berhasil |
| 8 | Hapus pelanggan yang sudah bertransaksi | Ditolak DB |
| 9 | Tambah produk | Berhasil |
| 10 | Harga <= 0 | Ditolak |
| 11 | Stok negatif | Ditolak |
| 12 | Edit produk | Audit UPDATE muncul |
| 13 | Hapus produk belum dipakai | Berhasil |
| 14 | Hapus produk yang sudah dipakai transaksi | Ditolak DB |
| 15 | Buat transaksi multi-produk | Draft terbentuk |
| 16 | Qty melebihi stok | Ditolak |
| 17 | Finalisasi transaksi valid | Status SELESAI |
| 18 | Finalisasi mengurangi stok | Stok berkurang |
| 19 | Finalisasi gagal | ROLLBACK, stok tidak setengah berubah |
| 20 | Laporan semua tanggal | Transaksi selesai muncul |
| 21 | Filter laporan tanggal | Data sesuai periode |
| 22 | Produk terlaris | Qty agregat sesuai transaksi |
| 23 | Audit log | INSERT/UPDATE/DELETE produk tercatat |
| 24 | Logout | Session berakhir, kembali login |
| 25 | Akses halaman tanpa login | Dialihkan ke login |
