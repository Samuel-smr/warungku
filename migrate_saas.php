<?php
require_once __DIR__ . '/config.php';

echo "Memulai migrasi SaaS...\n";

try {
    $pdo = getDB();
    
    // 1. Buat tabel shops
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shops (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            domain VARCHAR(100) NULL,
            subscription_ends_at DATE NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "1. Tabel 'shops' siap.\n";

    // 2. Buat tabel subscriptions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            shop_id INT UNSIGNED NOT NULL,
            plan_type ENUM('1_month', '6_months', '12_months') NOT NULL,
            amount INT UNSIGNED NOT NULL,
            status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            paid_at TIMESTAMP NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "2. Tabel 'subscriptions' siap.\n";

    // 3. Alter tabel users
    try { $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'seller') NOT NULL DEFAULT 'seller' AFTER password_hash;"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN shop_id INT UNSIGNED NULL AFTER role;"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_user_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE SET NULL;"); } catch (PDOException $e) {}
    echo "3. Kolom 'role' dan 'shop_id' ditambahkan ke tabel 'users'.\n";

    // 4. Alter tabel kategori, menu, pesanan
    try { $pdo->exec("ALTER TABLE kategori ADD COLUMN shop_id INT UNSIGNED NULL AFTER id;"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE kategori ADD CONSTRAINT fk_kategori_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE;"); } catch (PDOException $e) {}

    try { $pdo->exec("ALTER TABLE menu ADD COLUMN shop_id INT UNSIGNED NULL AFTER id;"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE menu ADD CONSTRAINT fk_menu_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE;"); } catch (PDOException $e) {}

    try { $pdo->exec("ALTER TABLE pesanan ADD COLUMN shop_id INT UNSIGNED NULL AFTER id;"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE pesanan ADD CONSTRAINT fk_pesanan_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE;"); } catch (PDOException $e) {}
    
    echo "4. Kolom 'shop_id' ditambahkan ke 'kategori', 'menu', 'pesanan'.\n";

    // 5. Setup data awal (Shop Default dan Admin)
    // Cek apakah ada shop
    $stmt = $pdo->query("SELECT id FROM shops LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO shops (name, subscription_ends_at, status) VALUES ('Kantin Pusat WarungKu', '2030-12-31', 'active')");
        $shopId = $pdo->lastInsertId();
        
        // Update data lama ke shop default
        $pdo->exec("UPDATE kategori SET shop_id = $shopId WHERE shop_id IS NULL");
        $pdo->exec("UPDATE menu SET shop_id = $shopId WHERE shop_id IS NULL");
        $pdo->exec("UPDATE pesanan SET shop_id = $shopId WHERE shop_id IS NULL");
        $pdo->exec("UPDATE users SET shop_id = $shopId, role = 'admin' WHERE username = 'admin'");
        echo "5. Setup data default selesai. Toko awal ID: $shopId\n";
    }

    echo "\n=== MIGRASI SELESAI ===";

} catch (Exception $e) {
    echo "\nGAGAL: " . $e->getMessage();
}
