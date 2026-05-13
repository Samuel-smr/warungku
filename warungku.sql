-- ============================================================
-- warungku.sql — Schema & Seed Data WarungKu (SaaS Edition)
-- ============================================================

CREATE DATABASE IF NOT EXISTS warungku
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE warungku;

-- ──────────────────────────────────────────────────────────
-- TABEL: shops
-- Menyimpan data toko/warung
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shops (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name                VARCHAR(150)    NOT NULL,
    owner_name          VARCHAR(100)    DEFAULT NULL,
    phone               VARCHAR(20)     DEFAULT NULL,
    address             TEXT            DEFAULT NULL,
    subscription_ends_at DATE            DEFAULT NULL        COMMENT 'Batas akhir masa aktif (denormalized for speed)',
    status              ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: subscription_plans
-- Menyimpan paket langganan yang tersedia
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subscription_plans (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(50)     NOT NULL,           -- e.g., 'Bulanan', 'Tahunan'
    duration_days   SMALLINT        NOT NULL,           -- e.g., 30, 365
    price           INT UNSIGNED    NOT NULL,           -- e.g., 20000
    description     TEXT            DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: subscriptions
-- Riwayat pembayaran/langganan toko
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subscriptions (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    shop_id         INT UNSIGNED    NOT NULL,
    plan_id         INT UNSIGNED    NOT NULL,
    amount_paid     INT UNSIGNED    NOT NULL,
    started_at      DATE            NOT NULL,
    ends_at         DATE            NOT NULL,
    payment_method  VARCHAR(50)     DEFAULT 'manual',
    status          ENUM('pending', 'active', 'expired') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sub_shop (shop_id),
    CONSTRAINT fk_sub_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: subscription_tokens
-- Token untuk perpanjangan masa aktif secara manual
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subscription_tokens (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    token_code      VARCHAR(20)     NOT NULL,
    plan_id         INT UNSIGNED    NOT NULL,
    is_used         TINYINT(1)      NOT NULL DEFAULT 0,
    used_by_shop_id INT UNSIGNED    DEFAULT NULL,
    used_at         TIMESTAMP       NULL DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token_code (token_code),
    CONSTRAINT fk_token_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE,
    CONSTRAINT fk_token_shop FOREIGN KEY (used_by_shop_id) REFERENCES shops(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: users
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    shop_id         INT UNSIGNED    DEFAULT NULL                COMMENT 'NULL hanya untuk Super Admin (jika ada)',
    username        VARCHAR(50)     NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    nama_lengkap    VARCHAR(100)    NOT NULL,
    role            ENUM('admin', 'kasir', 'superadmin') NOT NULL DEFAULT 'kasir',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username),
    KEY idx_users_shop (shop_id),
    CONSTRAINT fk_users_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: kategori
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS kategori (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    shop_id     INT UNSIGNED    NOT NULL,
    nama        VARCHAR(100)    NOT NULL,
    urutan      TINYINT         NOT NULL DEFAULT 0,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_kategori_shop (shop_id),
    CONSTRAINT fk_kategori_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: menu
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS menu (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    shop_id         INT UNSIGNED    NOT NULL,
    kategori_id     INT UNSIGNED    NOT NULL,
    nama            VARCHAR(150)    NOT NULL,
    deskripsi       TEXT            DEFAULT NULL,
    harga           INT UNSIGNED    NOT NULL,
    gambar_url      VARCHAR(500)    DEFAULT NULL,
    stok            TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_menu_shop (shop_id),
    KEY idx_menu_kategori (kategori_id),
    CONSTRAINT fk_menu_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_menu_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: pesanan
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pesanan (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    shop_id         INT UNSIGNED    NOT NULL,
    kode_pesanan    VARCHAR(30)     NOT NULL,
    nama_kasir      VARCHAR(100)    NOT NULL DEFAULT 'Admin',
    subtotal        INT UNSIGNED    NOT NULL DEFAULT 0,
    pajak           INT UNSIGNED    NOT NULL DEFAULT 0,
    total           INT UNSIGNED    NOT NULL DEFAULT 0,
    status          ENUM('proses', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'proses',
    catatan         TEXT            DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kode_pesanan_shop (shop_id, kode_pesanan),
    KEY idx_pesanan_shop (shop_id),
    CONSTRAINT fk_pesanan_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────
-- TABEL: detail_pesanan
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    pesanan_id      INT UNSIGNED    NOT NULL,
    menu_id         INT UNSIGNED    NOT NULL,
    nama_menu       VARCHAR(150)    NOT NULL,
    harga_satuan    INT UNSIGNED    NOT NULL,
    gambar_url      VARCHAR(500)    DEFAULT NULL,
    jumlah          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    subtotal        INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id),
    KEY idx_detail_pesanan (pesanan_id),
    CONSTRAINT fk_detail_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Plans
INSERT INTO subscription_plans (id, name, duration_days, price, description) VALUES
(1, 'Bulanan', 30, 20000, 'Akses penuh selama 30 hari'),
(2, '6 Bulan', 180, 110000, 'Hemat Rp 10.000 dengan paket 6 bulan'),
(3, 'Tahunan', 365, 200000, 'Hemat Rp 40.000 dengan paket tahunan');

-- Initial Shop
INSERT INTO shops (id, name, owner_name, subscription_ends_at, status) VALUES
(1, 'Kantin Pusat WarungKu', 'Administrator', '2030-12-31', 'active');

-- Initial Subscription
INSERT INTO subscriptions (shop_id, plan_id, amount_paid, started_at, ends_at) VALUES
(1, 3, 200000, '2024-01-01', '2030-12-31');

-- Categories
INSERT INTO kategori (id, shop_id, nama, urutan) VALUES
(1, 1, 'Makanan Berat',  1),
(2, 1, 'Makanan Ringan', 2),
(3, 1, 'Camilan',        3),
(4, 1, 'Minuman',        4);

-- Menu
INSERT INTO menu (id, shop_id, kategori_id, nama, deskripsi, harga, gambar_url, stok) VALUES
( 1, 1, 1, 'Nasi Goreng Spesial',  'Nasi goreng dengan telur, ayam, & udang',          35000, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400&h=300&fit=crop', 1),
( 2, 1, 4, 'Es Teh Manis',         'Teh manis segar dengan es batu',                    8000, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&h=300&fit=crop', 1);

-- Users (admin123)
INSERT INTO users (username, shop_id, password_hash, nama_lengkap, role) VALUES 
('admin', 1, '$2y$10$v9.6ePdzbDkNSetogfW62OPQY8yd1.5rqXeEPGttVk95rqSN9.ep2', 'Administrator', 'admin');

-- Tokens for testing
INSERT INTO subscription_tokens (token_code, plan_id) VALUES
('TOKEN-FREE-30D', 1),
('TOKEN-PREM-1YR', 3);
