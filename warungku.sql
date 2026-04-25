-- ============================================================
-- warungku.sql — Schema & Seed Data WarungKu
-- Jalankan: mysql -u root -p < warungku.sql
-- Atau import lewat phpMyAdmin / TablePlus / DBeaver
-- ============================================================

CREATE DATABASE IF NOT EXISTS warungku
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE warungku;

-- ──────────────────────────────────────────────────────────
-- TABEL: kategori
-- Menyimpan kategori menu (Makanan Berat, Minuman, dsb.)
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS kategori (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nama        VARCHAR(100)    NOT NULL,
    urutan      TINYINT         NOT NULL DEFAULT 0   COMMENT 'Urutan tampil di filter',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kategori_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: menu
-- Menyimpan daftar makanan/minuman yang dijual
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS menu (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    kategori_id     INT UNSIGNED    NOT NULL,
    nama            VARCHAR(150)    NOT NULL,
    deskripsi       TEXT            DEFAULT NULL,
    harga           INT UNSIGNED    NOT NULL                    COMMENT 'Dalam satuan Rupiah (tanpa desimal)',
    gambar_url      VARCHAR(500)    DEFAULT NULL                COMMENT 'URL gambar menu',
    stok            TINYINT(1)      NOT NULL DEFAULT 1          COMMENT '1 = tersedia, 0 = habis',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_menu_kategori (kategori_id),
    KEY idx_menu_stok (stok),
    CONSTRAINT fk_menu_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: pesanan (header)
-- Satu baris = satu sesi checkout pelanggan
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pesanan (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    kode_pesanan    VARCHAR(30)     NOT NULL                    COMMENT 'Contoh: ORD-123456',
    nama_kasir      VARCHAR(100)    NOT NULL DEFAULT 'Admin',
    subtotal        INT UNSIGNED    NOT NULL DEFAULT 0          COMMENT 'Sebelum pajak',
    pajak           INT UNSIGNED    NOT NULL DEFAULT 0          COMMENT 'Nominal pajak (10%)',
    total           INT UNSIGNED    NOT NULL DEFAULT 0          COMMENT 'subtotal + pajak',
    status          ENUM('selesai','dibatalkan') NOT NULL DEFAULT 'selesai',
    catatan         TEXT            DEFAULT NULL                COMMENT 'Catatan tambahan opsional',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kode_pesanan (kode_pesanan),
    KEY idx_pesanan_status (status),
    KEY idx_pesanan_tanggal (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: detail_pesanan (line items)
-- Satu baris = satu item menu dalam satu pesanan
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    pesanan_id      INT UNSIGNED    NOT NULL,
    menu_id         INT UNSIGNED    NOT NULL,
    nama_menu       VARCHAR(150)    NOT NULL                    COMMENT 'Snapshot nama saat order (antisipasi menu dihapus)',
    harga_satuan    INT UNSIGNED    NOT NULL                    COMMENT 'Snapshot harga saat order',
    gambar_url      VARCHAR(500)    DEFAULT NULL                COMMENT 'Snapshot gambar saat order',
    jumlah          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    subtotal        INT UNSIGNED    NOT NULL                    COMMENT 'harga_satuan × jumlah',
    PRIMARY KEY (id),
    KEY idx_detail_pesanan (pesanan_id),
    KEY idx_detail_menu (menu_id),
    CONSTRAINT fk_detail_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
    CONSTRAINT fk_detail_menu    FOREIGN KEY (menu_id)    REFERENCES menu(id)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA — Kategori
-- ============================================================
INSERT INTO kategori (id, nama, urutan) VALUES
(1, 'Makanan Berat',  1),
(2, 'Makanan Ringan', 2),
(3, 'Camilan',        3),
(4, 'Minuman',        4);

-- ============================================================
-- SEED DATA — Menu
-- ============================================================
INSERT INTO menu (id, kategori_id, nama, deskripsi, harga, gambar_url, stok) VALUES
( 1, 1, 'Nasi Goreng Spesial',  'Nasi goreng dengan telur, ayam, & udang',          35000, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400&h=300&fit=crop', 1),
( 2, 1, 'Ayam Bakar Madu',      'Ayam bakar dengan bumbu madu khas',                45000, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c3?w=400&h=300&fit=crop', 1),
( 3, 1, 'Mie Ayam Bakso',       'Mie dengan ayam cincang & bakso sapi',             28000, 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=400&h=300&fit=crop', 1),
( 4, 1, 'Soto Betawi',          'Soto betawi dengan santan & jeroan',               32000, 'https://images.unsplash.com/photo-1555126634-323283e090fa?w=400&h=300&fit=crop', 1),
( 5, 1, 'Rendang Sapi',         'Rendang sapi empuk bumbu rempah kaya',             55000, 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=400&h=300&fit=crop', 1),
( 6, 1, 'Bakso Jumbo',          'Bakso besar dengan kuah kaldu sapi',               25000, 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=400&h=300&fit=crop', 1),
( 7, 2, 'Gado-Gado Jakarta',    'Sayuran segar dengan bumbu kacang',                22000, 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=400&h=300&fit=crop', 1),
( 8, 3, 'Pisang Goreng Keju',   'Pisang goreng dengan topping keju leleh',          18000, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&h=300&fit=crop', 1),
( 9, 3, 'Martabak Manis',       'Martabak tebal dengan topping coklat keju',        30000, 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=400&h=300&fit=crop', 1),
(10, 4, 'Es Teh Manis',         'Teh manis segar dengan es batu',                    8000, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&h=300&fit=crop', 1),
(11, 4, 'Jus Alpukat',          'Jus alpukat creamy dengan susu kental',            18000, 'https://images.unsplash.com/photo-1638176066022-f624cef5a7b3?w=400&h=300&fit=crop', 1),
(12, 4, 'Kopi Susu Kekinian',   'Kopi susu dengan gula aren pilihan',               20000, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&h=300&fit=crop', 1);
