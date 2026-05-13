-- ──────────────────────────────────────────────────────────
-- WARUNGKU DATABASE EXPORT
-- ──────────────────────────────────────────────────────────
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ──────────────────────────────────────────────────────────
-- TABEL: shops
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `shops` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `subscription_ends_at` date DEFAULT NULL COMMENT 'Batas akhir masa aktif (denormalized for speed)',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `shops` (`id`, `name`, `owner_name`, `phone`, `address`, `subscription_ends_at`, `status`, `created_at`) VALUES
	(1, 'Kantin Pusat WarungKu', 'Administrator', NULL, NULL, '2031-08-30', 'active', '2026-05-13 12:07:36'),
	(2, 'Warung 97', NULL, NULL, NULL, '2026-06-12', 'active', '2026-05-13 14:22:24'),
	(3, 'Warung Dummy', NULL, NULL, NULL, '2026-06-13', 'active', '2026-05-13 14:27:14');

-- ──────────────────────────────────────────────────────────
-- TABEL: subscription_plans
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `duration_days` smallint NOT NULL,
  `price` int unsigned NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `subscription_plans` (`id`, `name`, `duration_days`, `price`, `description`) VALUES
	(1, 'Bulanan', 30, 20000, 'Akses penuh selama 30 hari'),
	(2, '6 Bulan', 180, 110000, 'Hemat Rp 10.000 dengan paket 6 bulan'),
	(3, 'Tahunan', 365, 200000, 'Hemat Rp 40.000 dengan paket tahunan');

-- ──────────────────────────────────────────────────────────
-- TABEL: users
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned DEFAULT NULL COMMENT 'NULL hanya untuk Super Admin (jika ada)',
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','kasir','superadmin') NOT NULL DEFAULT 'kasir',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  KEY `idx_users_shop` (`shop_id`),
  CONSTRAINT `fk_users_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` (`id`, `shop_id`, `username`, `password_hash`, `nama_lengkap`, `phone`, `role`, `created_at`, `deleted_at`, `last_login`) VALUES
	(1, NULL, 'admin', '$2y$10$v9.6ePdzbDkNSetogfW62OPQY8yd1.5rqXeEPGttVk95rqSN9.ep2', 'Administrator', NULL, 'superadmin', '2026-05-13 12:07:36', NULL, NULL),
	(2, 1, 'warung 86', '$2y$12$Y9c69.aWZl7FsahfgRWiM.lIVdy6memjbF7ecmXEv.X0bcGFHTCpG', 'Bu Susi', NULL, 'kasir', '2026-05-13 13:04:05', NULL, NULL),
	(3, 1, 'coba', '$2y$12$ugN2JFRunyfCHrCPt.n1aeDdEE.w1kJ2vibodJkQ1z1neKlbTDyG.', 'coba', NULL, 'kasir', '2026-05-13 13:04:24', NULL, NULL),
	(6, 3, 'user_dummy', '$2y$12$KGr90R.SZamAoCXuQeJmdOqJTOocA7bVKx.YOc.H2AQizPcwZyRce', 'User Dummy', NULL, 'admin', '2026-05-13 14:30:36', NULL, NULL);

-- ──────────────────────────────────────────────────────────
-- TABEL: kategori
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `kategori` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned NOT NULL,
  `nama` varchar(100) NOT NULL,
  `urutan` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kategori_shop` (`shop_id`),
  CONSTRAINT `fk_kategori_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `kategori` (`id`, `shop_id`, `nama`, `urutan`, `created_at`) VALUES
	(1, 1, 'Makanan Berat', 1, '2026-05-13 12:07:36'),
	(2, 1, 'Makanan Ringan', 2, '2026-05-13 12:07:36'),
	(3, 1, 'Camilan', 3, '2026-05-13 12:07:36'),
	(4, 1, 'Minuman', 4, '2026-05-13 12:07:36'),
	(5, 1, 'campuran', 0, '2026-05-13 14:07:09'),
	(6, 3, 'makanan', 0, '2026-05-13 14:50:56');

-- ──────────────────────────────────────────────────────────
-- TABEL: menu
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `menu` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned NOT NULL,
  `kategori_id` int unsigned NOT NULL,
  `nama` varchar(150) NOT NULL,
  `deskripsi` text,
  `harga` int unsigned NOT NULL,
  `gambar_url` varchar(500) DEFAULT NULL,
  `stok` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_shop` (`shop_id`),
  KEY `idx_menu_kategori` (`kategori_id`),
  CONSTRAINT `fk_menu_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_menu_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `menu` (`id`, `shop_id`, `kategori_id`, `nama`, `deskripsi`, `harga`, `gambar_url`, `stok`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, 'Nasi Goreng Spesial', 'Nasi goreng dengan telur, ayam, & udang', 35000, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400&h=300&fit=crop', 1, '2026-05-13 12:07:36', '2026-05-13 12:07:36'),
	(2, 1, 4, 'Es Teh Manis', 'Teh manis segar dengan es batu', 8000, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&h=300&fit=crop', 1, '2026-05-13 12:07:36', '2026-05-13 12:07:36'),
	(4, 1, 1, 'Nasi Ayam Geprek', NULL, 13000, '', 1, '2026-05-13 13:37:14', '2026-05-13 13:37:14'),
	(5, 1, 1, 'Nasi Tempe', NULL, 10000, '', 1, '2026-05-13 13:46:40', '2026-05-13 13:46:40'),
	(6, 1, 1, 'Pecel', NULL, 10000, '', 1, '2026-05-13 13:46:47', '2026-05-13 13:46:47'),
	(7, 1, 5, 'nasi', NULL, 5000, '', 1, '2026-05-13 14:07:19', '2026-05-13 14:07:19'),
	(8, 1, 5, 'ayam', NULL, 7000, '', 1, '2026-05-13 14:08:10', '2026-05-13 14:08:10'),
	(9, 1, 5, 'sayur', NULL, 1000, '', 1, '2026-05-13 14:08:18', '2026-05-13 14:08:18'),
	(10, 1, 5, 'tempe', NULL, 1000, '', 1, '2026-05-13 14:08:27', '2026-05-13 14:08:27'),
	(11, 3, 6, 'Nasi Goreng Balapan', 'nasi goreng pedas', 13000, '', 1, '2026-05-13 14:51:33', '2026-05-13 14:51:33');

-- ──────────────────────────────────────────────────────────
-- TABEL: pesanan
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pesanan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned NOT NULL,
  `kode_pesanan` varchar(30) NOT NULL,
  `nama_kasir` varchar(100) NOT NULL DEFAULT 'Admin',
  `subtotal` int unsigned NOT NULL DEFAULT '0',
  `pajak` int unsigned NOT NULL DEFAULT '0',
  `total` int unsigned NOT NULL DEFAULT '0',
  `status` enum('proses','selesai','dibatalkan') NOT NULL DEFAULT 'proses',
  `catatan` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kode_pesanan_shop` (`shop_id`,`kode_pesanan`),
  KEY `idx_pesanan_shop` (`shop_id`),
  CONSTRAINT `fk_pesanan_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `pesanan` (`id`, `shop_id`, `kode_pesanan`, `nama_kasir`, `subtotal`, `pajak`, `total`, `status`, `catatan`, `created_at`) VALUES
	(37, 3, 'ORD-C7AUIV', 'User Dummy', 13000, 0, 13000, 'selesai', NULL, '2026-05-13 14:52:51');

-- ──────────────────────────────────────────────────────────
-- TABEL: detail_pesanan
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `detail_pesanan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pesanan_id` int unsigned NOT NULL,
  `menu_id` int unsigned NOT NULL,
  `nama_menu` varchar(150) NOT NULL,
  `harga_satuan` int unsigned NOT NULL,
  `gambar_url` varchar(500) DEFAULT NULL,
  `jumlah` smallint unsigned NOT NULL DEFAULT '1',
  `subtotal` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_detail_pesanan` (`pesanan_id`),
  CONSTRAINT `fk_detail_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `menu_id`, `nama_menu`, `harga_satuan`, `gambar_url`, `jumlah`, `subtotal`) VALUES
	(42, 37, 11, 'Nasi Goreng Balapan', 13000, '', 1, 13000);

-- ──────────────────────────────────────────────────────────
-- TABEL: subscription_tokens
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscription_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `token_code` varchar(20) NOT NULL,
  `plan_id` int unsigned NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `used_by_shop_id` int unsigned DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_code` (`token_code`),
  KEY `fk_token_plan` (`plan_id`),
  KEY `fk_token_shop` (`used_by_shop_id`),
  CONSTRAINT `fk_token_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_token_shop` FOREIGN KEY (`used_by_shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `subscription_tokens` (`id`, `token_code`, `plan_id`, `is_used`, `used_by_shop_id`, `used_at`, `created_at`) VALUES
	(1, 'TOKEN-FREE-30D', 1, 0, NULL, NULL, '2026-05-13 12:07:36'),
	(2, 'TOKEN-PREM-1YR', 3, 0, NULL, NULL, '2026-05-13 12:07:36');

-- ──────────────────────────────────────────────────────────
-- TABEL: subscriptions
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned NOT NULL,
  `plan_id` int unsigned NOT NULL,
  `plan_type` varchar(50) DEFAULT NULL,
  `amount_paid` int unsigned NOT NULL,
  `started_at` date NOT NULL,
  `ends_at` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'manual',
  `status` enum('pending','paid','active','expired','cancelled') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sub_shop` (`shop_id`),
  KEY `fk_sub_plan` (`plan_id`),
  CONSTRAINT `fk_sub_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_sub_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `subscriptions` (`id`, `shop_id`, `plan_id`, `plan_type`, `amount_paid`, `started_at`, `ends_at`, `payment_method`, `status`, `paid_at`, `created_at`) VALUES
	(1, 1, 3, '1 Tahun', 200000, '2024-01-01', '2030-12-31', 'manual', 'active', NULL, '2026-05-13 12:07:36'),
	(2, 1, 2, '6_months', 110000, '2026-05-13', '2026-05-13', 'manual', 'paid', '2026-05-13 21:16:50', '2026-05-13 14:16:41'),
	(3, 1, 1, '1_month', 20000, '2026-05-13', '2026-05-13', 'manual', 'paid', '2026-05-13 21:19:07', '2026-05-13 14:19:03'),
	(4, 1, 1, '1_month', 20000, '2026-05-13', '2026-05-13', 'manual', 'paid', '2026-05-13 21:20:46', '2026-05-13 14:20:36');

-- ──────────────────────────────────────────────────────────
-- TABEL: activity_logs
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `shop_id` int unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user` (`user_id`),
  KEY `idx_log_shop` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
