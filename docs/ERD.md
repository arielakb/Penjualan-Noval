# ERD — Aplikasi Pengelolaan Data Penjualan

```mermaid
erDiagram
    PELANGGAN ||--o{ TRANSAKSI : memiliki
    TRANSAKSI ||--o{ DETAIL_TRANSAKSI : memiliki
    PRODUK ||--o{ DETAIL_TRANSAKSI : digunakan

    PELANGGAN {
        INT id_pelanggan PK
        VARCHAR nama
        VARCHAR alamat
        VARCHAR no_hp
        VARCHAR email
        DATETIME created_at
        DATETIME updated_at
    }

    PRODUK {
        INT id_produk PK
        VARCHAR nama_produk
        DECIMAL harga
        INT stok
        DATETIME created_at
        DATETIME updated_at
    }

    TRANSAKSI {
        BIGINT id_transaksi PK
        INT id_pelanggan FK
        DATETIME tanggal
        DECIMAL total
        ENUM status
        DATETIME created_at
    }

    DETAIL_TRANSAKSI {
        BIGINT id_detail PK
        BIGINT id_transaksi FK
        INT id_produk FK
        INT qty
        DECIMAL harga
        DECIMAL subtotal
    }

    AUDIT_LOG {
        BIGINT id_log PK
        VARCHAR aktivitas
        VARCHAR nama_tabel
        BIGINT id_data
        VARCHAR keterangan
        DATETIME waktu
    }

    PENGGUNA {
        INT id_pengguna PK
        VARCHAR username
        VARCHAR password_hash
        VARCHAR nama_lengkap
        ENUM role
        TINYINT aktif
        DATETIME created_at
        DATETIME updated_at
    }
```

Catatan:
- `audit_log` mencatat perubahan pada `produk` melalui trigger.
- `pengguna` dipakai untuk autentikasi aplikasi, tetapi tidak memiliki relasi transaksi bisnis.
