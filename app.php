<?php
// ============================================================
// app.php — One-Click Order System (Optimized for 40+ & Mixed)
// ============================================================
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$shop_id = $_SESSION['shop_id'] ?? 0;

// Handle AJAX Status Update
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = (int)$_POST['id'];
    $status = $_POST['status']; 
    
    if ($id && in_array($status, ['selesai', 'dibatalkan'])) {
        $stmt = $pdo->prepare("UPDATE pesanan SET status = :status WHERE id = :id AND shop_id = :shop_id");
        $stmt->execute(['status' => $status, 'id' => $id, 'shop_id' => $shop_id]);
        echo json_encode(['error' => false]);
    } else {
        echo json_encode(['error' => true]);
    }
    exit;
}

// Cek Langganan
$stmtSub = $pdo->prepare("SELECT name, subscription_ends_at, enable_qty_input FROM shops WHERE id = :shop_id");
$stmtSub->execute(['shop_id' => $shop_id]);
$shop_info = $stmtSub->fetch();
$enable_qty_input = $shop_info['enable_qty_input'] ?? 0;

$is_expired = false;
$days_left = 999;
if ($shop_info && $shop_info['subscription_ends_at']) {
    $expiry_time = strtotime($shop_info['subscription_ends_at'] . ' 23:59:59');
    if ($expiry_time < time()) {
        $is_expired = true;
    } else {
        $days_left = floor(($expiry_time - time()) / 86400);
    }
}

if ($is_expired) {
    die("Masa aktif toko " . htmlspecialchars($shop_info['name'] ?? 'Toko') . " telah habis. Silakan hubungi admin.");
}

// Ambil Menu - Urutkan Berdasarkan Penjualan Terbanyak (Terlaris)
$stmt = $pdo->prepare("
    SELECT m.id, m.nama, k.nama as category, m.harga, m.gambar_url, m.stok,
           (SELECT COALESCE(SUM(dp.jumlah), 0) 
            FROM detail_pesanan dp 
            JOIN pesanan p ON dp.pesanan_id = p.id 
            WHERE dp.menu_id = m.id AND p.status = 'selesai') as total_terjual
    FROM menu m 
    JOIN kategori k ON k.id = m.kategori_id 
    WHERE m.shop_id = :shop_id AND m.stok = 1
    ORDER BY total_terjual DESC, k.urutan, m.nama
");
$stmt->execute(['shop_id' => $shop_id]);
$menu_items = $stmt->fetchAll();
$categories = array_unique(array_column($menu_items, 'category'));

// Ambil Pesanan AKTIF
function getActiveOrders($pdo, $shop_id) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.kode_pesanan, p.total, p.status, p.created_at, GROUP_CONCAT(dp.nama_menu SEPARATOR ', ') as items
        FROM pesanan p
        JOIN detail_pesanan dp ON p.id = dp.pesanan_id
        WHERE p.shop_id = :shop_id AND p.status = 'proses'
        GROUP BY p.id
        ORDER BY p.id DESC
    ");
    $stmt->execute(['shop_id' => $shop_id]);
    return $stmt->fetchAll();
}

if (isset($_GET['ajax_active_orders'])) {
    $orders = getActiveOrders($pdo, $shop_id);
    foreach ($orders as $o) {
        echo '<div class="history-item" data-id="'.$o['id'].'">
                <div class="h-meta">Jam: '.date('H:i', strtotime($o['created_at'])).' · #'.$o['kode_pesanan'].'</div>
                <div class="h-name">'.htmlspecialchars($o['items']).'</div>
                <div class="h-price">Rp '.number_format($o['total'], 0, ',', '.').'</div>
                <div class="h-actions">
                    <button class="h-btn btn-done" onclick="updateStatus('.$o['id'].', \'selesai\')">✅ SELESAI</button>
                    <button class="h-btn btn-cancel" onclick="updateStatus('.$o['id'].', \'dibatalkan\')" title="Batalkan Pesanan"><span>❌</span></button>
                </div>
              </div>';
    }
    if (empty($orders)) echo '<div class="empty-msg">Belum ada pesanan aktif.<br><small>Klik menu di sebelah untuk menambah pesanan.</small></div>';
    exit;
}

// Handle AJAX Update Check (Heartbeat)
if (isset($_GET['check_updates'])) {
    // Check Orders
    $stmtO = $pdo->prepare("SELECT MAX(id) as max_id, COUNT(*) as total FROM pesanan WHERE shop_id = :shop_id AND status = 'proses'");
    $stmtO->execute(['shop_id' => $shop_id]);
    $orderData = $stmtO->fetch();

    // Check Menu Version (Real-time CRUD Sync)
    $stmtM = $pdo->prepare("SELECT MAX(updated_at) as menu_version, COUNT(*) as menu_count FROM menu WHERE shop_id = :shop_id AND stok = 1");
    $stmtM->execute(['shop_id' => $shop_id]);
    $menuData = $stmtM->fetch();

    echo json_encode([
        'max_id' => $orderData['max_id'],
        'total' => $orderData['total'],
        'menu_version' => $menuData['menu_version'],
        'menu_count' => $menuData['menu_count']
    ]);
    exit;
}

// Handle AJAX Menu List Refresh
if (isset($_GET['ajax_menu'])) {
    $stmt = $pdo->prepare("
        SELECT m.id, m.nama, k.nama as category, m.harga, m.gambar_url,
               (SELECT COALESCE(SUM(dp.jumlah), 0) 
                FROM detail_pesanan dp 
                JOIN pesanan p ON dp.pesanan_id = p.id 
                WHERE dp.menu_id = m.id AND p.status = 'selesai') as total_terjual
        FROM menu m 
        JOIN kategori k ON k.id = m.kategori_id 
        WHERE m.shop_id = :shop_id AND m.stok = 1
        ORDER BY total_terjual DESC, k.urutan, m.nama
    ");
    $stmt->execute(['shop_id' => $shop_id]);
    $items = $stmt->fetchAll();
    
    foreach ($items as $index => $item) {
        $terjual = number_format($item['total_terjual'], 0, ',', '.');
        $harga = number_format($item['harga'], 0, ',', '.');
        $img = $item['gambar_url'] ?: 'assets/default_menu.png';
        $pop = ($item['total_terjual'] > 0 && $index < 3) ? '<div class="badge-popular">🔥 <span>TERLARIS</span></div>' : '';
        
        echo '
        <div class="card" id="menu-'.$item['id'].'" onclick="handleMenuClick('.$item['id'].', \''.addslashes($item['nama']).'\', '.$item['harga'].')" data-cat="'.htmlspecialchars($item['category']).'">
            '.$pop.'
            <div class="badge-sold">📊 '.$terjual.'</div>
            <img class="card-img" src="'.$img.'" onerror="this.onerror=null; this.src=\'assets/default_menu.png\'">
            <div class="card-body">
                <div class="card-name">'.htmlspecialchars($item['nama']).'</div>
                <div class="card-price">Rp '.$harga.'</div>
                <div class="card-sold">📈 Terjual: '.$terjual.'</div>
            </div>
        </div>';
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir WarungKu — Mudah & Cepat</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#d4a853">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script>
        const ENABLE_QTY_INPUT = <?= $enable_qty_input ? 'true' : 'false' ?>;
        const savedTheme = localStorage.getItem('warungku_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);

        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('SW Registered!', reg))
                    .catch(err => console.log('SW Registration failed!', err));
            });
        }
    </script>
    <style>
        :root {
            --bg: #0f0e0b; --surface: #1a1814; --surface2: #231f1a;
            --gold: #d4a853; --cream: #f5edd8; --text: #f0e8d5; --text-dim: #8a7f6e;
            --border: rgba(212,168,83,0.25); --red: #ff5e5e; --green: #4caf7d;
        }
        [data-theme="light"] {
            --bg: #fdfaf6; --surface: #ffffff; --surface2: #f4efeb;
            --gold: #b38222; --cream: #1a1814; --text: #3c3730; --text-dim: #7a7265;
            --border: rgba(179, 130, 34, 0.25);
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'DM Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; height: 100vh; height: 100dvh; overflow: hidden; font-size: 16px; transition: background 0.3s; }

        /* SIDEBAR */
        .sidebar { width: 350px; background: var(--surface); border-right: 2px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 24px; border-bottom: 2px solid var(--border); background: var(--surface2); }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; color: var(--gold); font-size: 24px; }
        .btn-expand-sidebar { display: none; }
        .history-list { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
        
        .order-summary-bar { display: none; background: var(--surface2); padding: 12px 20px; border-bottom: 2px solid var(--border); overflow-x: auto; white-space: nowrap; gap: 10px; scrollbar-width: none; align-items: center; }
        .order-summary-bar::-webkit-scrollbar { display: none; }
        .summary-badge { background: var(--surface); border: 1px solid var(--border); padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 700; color: var(--gold); display: flex; align-items: center; gap: 8px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .summary-badge span { background: var(--gold); color: var(--bg); padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 900; }
        .summary-badge.active { background: var(--gold); color: var(--bg); border-color: var(--gold); }
        .summary-badge.active span { background: var(--bg); color: var(--gold); }
        
        .history-item { background: var(--surface2); border: 2px solid var(--border); border-radius: 16px; padding: 20px; animation: slideIn 0.3s ease; transition: 0.2s; }
        .history-item.highlighted { border-color: var(--gold); border-width: 3px; transform: scale(1.02); box-shadow: 0 10px 30px rgba(212,168,83,0.3); z-index: 10; position: relative; }
        @keyframes slideIn { from { opacity:0; transform:translateY(15px); } }
        
        .h-meta { font-size: 13px; color: var(--text-dim); margin-bottom: 8px; font-weight: 700; text-transform: uppercase; }
        .h-name { font-size: 18px; font-weight: 700; color: var(--cream); margin-bottom: 8px; line-height: 1.4; }
        .h-price { font-size: 20px; font-weight: 700; color: var(--gold); margin-bottom: 16px; }
        
        .h-actions { display: flex; gap: 12px; }
        .h-btn { padding: 14px; border-radius: 12px; border: none; font-size: 14px; font-weight: 800; cursor: pointer; transition: 0.2s; letter-spacing: 0.5px; display: flex; align-items: center; justify-content: center; }
        .btn-done { flex: 1; background: var(--green); color: #fff; box-shadow: 0 4px 0 #2d6648; }
        .btn-cancel { width: 55px; background: var(--red); color: #fff; box-shadow: 0 4px 0 #a33b3b; border: none; }
        .btn-cancel span { background: #fff; width: 30px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .h-btn:active { transform: translateY(2px); box-shadow: none; }
        .empty-msg { text-align: center; color: var(--text-dim); padding: 60px 20px; font-size: 18px; line-height: 1.6; }

        /* MAIN CONTENT */
        .main { flex: 1; display: flex; flex-direction: column; background: var(--bg); position: relative; min-width: 0; }
        .topbar { min-height: 80px; height: auto; padding: 12px 32px; border-bottom: 2px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: var(--surface); flex-wrap: wrap; gap: 16px; }
        .brand { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 900; color: var(--gold); white-space: nowrap; }
        .brand span { color: var(--cream); }
        
        .btn-nav { padding: 12px 18px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 15px; border: 2px solid var(--border); transition: 0.2s; display: flex; align-items: center; gap: 8px; cursor: pointer; background: none; color: var(--text); white-space: nowrap; }
        .btn-nav.manage { color: var(--gold); border-color: var(--gold); }
        .btn-nav.theme { color: var(--text-dim); }
        .btn-nav.logout { color: var(--red); border-color: var(--red); }
        .btn-nav:hover { background: var(--surface2); }

        /* TOPBAR TOGGLE STYLE */
        .topbar-toggle { display: none; }
        .brand-wrapper { display: flex; align-items: center; justify-content: space-between; width: auto; }
        .topbar-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

        .content { flex: 1; overflow-y: auto; padding: 32px; position: relative; }
        
        .sticky-controls { position: sticky; top: -32px; background: var(--bg); z-index: 90; padding: 10px 0 20px; margin-top: -10px; }
        
        .search-wrapper { position: relative; margin-bottom: 16px; }
        .search-input { width: 100%; background: var(--surface); border: 2px solid var(--border); padding: 14px 20px 14px 50px; border-radius: 15px; color: var(--text); font-size: 16px; font-weight: 600; transition: 0.2s; }
        .search-input:focus { border-color: var(--gold); outline: none; box-shadow: 0 0 0 4px var(--gold-dim); }
        .search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 18px; }

        .filter-bar { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 12px; padding-top: 12px; scrollbar-width: none; }
        .filter-bar::-webkit-scrollbar { display: none; }
        .filter-btn { padding: 12px 24px; border-radius: 100px; border: 2px solid var(--border); background: var(--surface); color: var(--text-dim); cursor: pointer; white-space: nowrap; font-size: 16px; font-weight: 700; transition: 0.2s; }
        .filter-btn.active { background: var(--gold); color: var(--bg); border-color: var(--gold); }

        /* RAKITAN AREA */
        .rakitan-bar { background: #ff9800; color: #000; padding: 20px 32px; display: none; align-items: center; justify-content: space-between; border-bottom: 4px solid #cc7a00; }
        .rakitan-items { font-weight: 800; font-size: 18px; }
        .btn-rakitan-send { background: #000; color: #fff; padding: 12px 30px; border-radius: 12px; border: none; font-weight: 900; font-size: 16px; cursor: pointer; box-shadow: 0 4px 0 #333; }
        .btn-rakitan-cancel { background: transparent; border: 2px solid rgba(0,0,0,0.3); color: #000; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }

        /* GRID & CARDS */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px; }
        .card { background: var(--surface); border: 2px solid var(--border); border-radius: 20px; overflow: hidden; cursor: pointer; transition: 0.2s; position: relative; }
        .card:hover { transform: translateY(-4px); border-color: var(--gold); box-shadow: 0 12px 40px rgba(0,0,0,0.6); }
        .card:active { transform: scale(0.96); }
        .card.selected { border-color: #ff9800; background: rgba(255, 152, 0, 0.1); border-width: 4px; }
        .card.sold-out { opacity: 0.6; filter: grayscale(0.5); }
        .sold-out-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 24px; z-index: 10; letter-spacing: 2px; }
        .card-img { width: 100%; height: 180px; object-fit: cover; }
        .card-body { padding: 16px; text-align: center; }
        .card-name { font-weight: 700; color: var(--cream); margin-bottom: 8px; font-size: 18px; min-height: 50px; display: flex; align-items: center; justify-content: center; }
        .card-price { color: var(--gold); font-weight: 900; font-size: 22px; background: var(--surface2); padding: 8px; border-radius: 10px; margin-bottom: 12px; }
        
        .btn-toggle-stock { width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface2); color: var(--text-dim); font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-toggle-stock:hover { background: var(--surface); color: var(--gold); border-color: var(--gold); }
        .card.sold-out .btn-toggle-stock { background: var(--gold); color: var(--bg); border: none; }
        
        .card-sold { font-size: 14px; color: var(--text-dim); font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 4px; }
        .badge-popular { position: absolute; top: 12px; left: 12px; background: #ff9800; color: #000; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 900; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 5; }
        .badge-sold { position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.7); color: #fff; padding: 4px 8px; border-radius: 8px; font-size: 11px; font-weight: 800; display: none; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1); z-index: 5; }

        .toast { position: fixed; top: 32px; left: 50%; transform: translateX(-50%); background: var(--green); color: #fff; padding: 18px 32px; border-radius: 20px; font-weight: 800; font-size: 18px; z-index: 1000; display: none; box-shadow: 0 15px 50px rgba(0,0,0,0.8); animation: toastIn 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.28); width: max-content; min-width: 350px; max-width: 90%; text-align: center; border: 2px solid rgba(255,255,255,0.2); }
        @keyframes toastIn { from { top: -100px; opacity: 0; } to { top: 32px; opacity: 1; } }
        .loader { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 999; display: none; align-items: center; justify-content: center; color: var(--gold); font-weight: 800; font-size: 20px; }
        .offline-indicator { position: fixed; bottom: 20px; right: 20px; background: #ff9800; color: #000; padding: 12px 20px; border-radius: 50px; font-weight: 800; font-size: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; gap: 10px; animation: pulse 2s infinite; border: 2px solid #fff; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }

        @media (max-width: 992px) {
            body { flex-direction: column; height: auto; min-height: 100vh; overflow: visible; padding-bottom: 72px; }
            body.sidebar-open { overflow: hidden; }
            .sidebar { 
                position: fixed; bottom: 0; left: 0; right: 0; width: 100%; 
                height: 72px; z-index: 900; 
                border-top: 2px solid var(--border); border-right: none; 
                transition: 0.3s ease; overflow: hidden; background: var(--surface);
            }
            .sidebar-header { height: 72px; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; cursor: pointer; }
            .btn-expand-sidebar { 
                display: flex; align-items: center; justify-content: center;
                background: var(--surface); border: 2px solid var(--border);
                color: var(--gold); width: 40px; height: 40px; border-radius: 10px;
                cursor: pointer; font-size: 20px; transition: 0.2s;
            }
            .sidebar.full-screen { 
                height: 100vh !important; max-height: 100vh !important;
                z-index: 2000; background: var(--bg); border: none;
            }
            .sidebar.full-screen .btn-expand-sidebar { background: var(--gold); color: var(--bg); border-color: var(--gold); }
            .history-list { display: none; }
            .sidebar.full-screen .history-list { display: flex; height: calc(100vh - 140px) !important; overflow-y: auto; }
            .order-summary-bar { display: none; }
            .sidebar.full-screen .order-summary-bar { display: flex; overflow-x: auto; }
            .main { flex: none; width: 100%; min-height: 100vh; }
            .topbar { padding: 16px; height: auto; flex-direction: column; align-items: flex-start; position: sticky; top: 0; z-index: 100; }
            .brand-wrapper { display: flex; align-items: center; justify-content: space-between; width: 100%; }
            .topbar-toggle { 
                display: flex; align-items: center; justify-content: center;
                background: var(--surface2); border: 2px solid var(--border);
                color: var(--gold); width: 40px; height: 40px; border-radius: 10px;
                cursor: pointer; font-size: 18px; transition: 0.2s;
            }
            .topbar.expanded .topbar-toggle { background: var(--gold); color: var(--bg); border-color: var(--gold); }
            
            .topbar-actions { 
                display: none; width: 100%; padding-top: 16px; margin-top: 12px;
                border-top: 1px solid var(--border);
                flex-direction: column; align-items: stretch !important; gap: 10px !important;
            }
            .topbar.expanded .topbar-actions { display: flex; }
            
            .content { padding: 20px; overflow: visible; flex: none; }
            .grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .btn-nav { width: 100%; justify-content: center; padding: 10px; font-size: 14px; border-radius: 10px; }
            .brand { font-size: 20px; }
            .filter-btn { padding: 8px 16px; font-size: 14px; }
            .sticky-controls { top: 0; padding: 15px 0; background: var(--bg); border-bottom: 1px solid var(--border); margin-bottom: 20px; }
            .search-input { padding: 12px 18px 12px 45px; font-size: 14px; }
            .card-img { height: 110px; }
            .card-body { padding: 10px; }
            .card-name { font-size: 14px; min-height: 35px; margin-bottom: 4px; }
            .card-price { font-size: 16px; padding: 4px; margin-bottom: 0; }
            .card-sold { display: none; }
            .badge-popular span { display: none; }
            .badge-popular { padding: 4px 6px; }
            .badge-sold { display: flex; align-items: center; gap: 3px; }
            .empty-msg small { display: none; }
            .toast { min-width: 90%; font-size: 16px; padding: 14px 20px; }
            .offline-indicator { bottom: 92px; }
        }
    </style>
</head>
<body>

    <?php if ($days_left <= 7): ?>
        <div style="position: fixed; top: 0; left: 0; right: 0; background: var(--red); color: #fff; padding: 10px; text-align: center; font-weight: 800; z-index: 2000; font-size: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
            ⚠️ MASA AKTIF TOKO TINGGAL <?= $days_left ?> HARI LAGI. Segera hubungi Admin untuk perpanjang!
        </div>
        <style> body { padding-top: 40px; } .topbar { top: 40px; } .rakitan-bar { top: 40px; } </style>
    <?php endif; ?>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header" onclick="toggleSidebarFull()">
            <h2>Pesanan Aktif</h2>
            <button class="btn-expand-sidebar" id="expandBtn" aria-label="Toggle Full Screen">
                <span id="expandIcon">⛶</span>
            </button>
        </div>
        <div class="order-summary-bar" id="orderSummaryBar"></div>
        <div class="history-list" id="orderList">
            <!-- AJAX LOAD -->
        </div>
    </aside>

    <main class="main">
        <!-- RAKITAN BAR -->
        <div class="rakitan-bar" id="rakitanBar">
            <div class="rakitan-items">
                🍽 <span id="rakitanCount">0</span> Item Terpilih: <span id="rakitanTotal">Rp 0</span>
            </div>
            <div style="display:flex; gap:12px;">
                <button class="btn-rakitan-cancel" onclick="cancelMixed()">Batal</button>
                <button class="btn-rakitan-send" onclick="sendMixed()">🚀 PESAN SEKARANG</button>
            </div>
        </div>

        <header class="topbar" id="mainTopbar">
            <div class="brand-wrapper">
                <div class="brand">Warung<span>Ku</span></div>
                <button class="topbar-toggle" onclick="toggleTopbar()" id="toggleBtn" aria-label="Toggle Menu">
                    <span id="toggleIcon">☰</span>
                </button>
            </div>
            <div class="topbar-actions" id="topbarActions">
                <button onclick="startMixed()" class="btn-nav manage" id="mixedBtn" style="background:var(--gold); color:#000;">➕ Pesanan Campur</button>
                <button onclick="toggleTheme()" class="btn-nav theme" id="themeBtn">☀️ Mode Terang</button>
                <a href="kelola_menu.php" class="btn-nav manage">⚙️ Kelola Menu</a>
                <a href="riwayat.php" class="btn-nav history">📋 Riwayat</a>
                <a href="logout.php" class="btn-nav logout">🚪 Keluar</a>
            </div>
        </header>

        <div class="content">
            <div class="sticky-controls">
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" class="search-input" placeholder="Cari menu favorit..." onkeyup="applyFilters()">
                </div>
                <div class="filter-bar">
                    <button class="filter-btn active" onclick="filterMenu('Semua', this)">Semua Menu</button>
                    <?php foreach ($categories as $cat): ?>
                        <button class="filter-btn" onclick="filterMenu('<?= htmlspecialchars($cat) ?>', this)"><?= htmlspecialchars($cat) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid">
                <?php foreach ($menu_items as $index => $item): ?>
                    <div class="card" id="menu-<?= $item['id'] ?>" onclick="handleMenuClick(<?= $item['id'] ?>, '<?= addslashes($item['nama']) ?>', <?= $item['harga'] ?>)" data-cat="<?= htmlspecialchars($item['category']) ?>">
                        <?php if ($item['total_terjual'] > 0 && $index < 3): ?>
                            <div class="badge-popular">🔥 <span>TERLARIS</span></div>
                        <?php endif; ?>
                        <div class="badge-sold">📊 <?= number_format($item['total_terjual'], 0, ',', '.') ?></div>
                        <img class="card-img" src="<?= $item['gambar_url'] ?: 'assets/default_menu.png' ?>" onerror="this.onerror=null; this.src='assets/default_menu.png'">
                        <div class="card-body">
                            <div class="card-name"><?= htmlspecialchars($item['nama']) ?></div>
                            <div class="card-price">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                            <div class="card-sold">📈 Terjual: <?= number_format($item['total_terjual'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <div class="loader" id="loader">Sedang Diproses...</div>
    <div class="toast" id="toast"></div>
    <div class="offline-indicator" id="offlineIndicator">
        <span>📶</span> <span id="offlineCount">0</span> Pesanan Menunggu Sinkronisasi...
    </div>

    <!-- QTY MODAL -->
    <style>
        /* MODAL & QTY INPUT */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 3000; padding: 20px; }
        .modal { background: var(--surface); border: 2px solid var(--border); border-radius: 20px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.6); }
        #qtyInput::-webkit-inner-spin-button, #qtyInput::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        #qtyInput { -moz-appearance: textfield; }
    </style>
    <div class="modal-overlay" id="qtyModal" style="z-index: 3000;">
        <div class="modal" style="max-width: 320px; text-align: center; padding: 32px 24px;">
            <h3 id="qtyModalName" style="color:var(--cream); margin-bottom: 24px; font-size: 20px; font-family: 'Playfair Display', serif;">Nama Menu</h3>
            <div style="display:flex; align-items:center; justify-content:center; gap: 16px; margin-bottom: 32px;">
                <button onclick="document.getElementById('qtyInput').stepDown()" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid var(--border); background: var(--surface2); color: var(--gold); font-size: 24px; font-weight: bold; cursor: pointer; transition:0.2s;">-</button>
                <input type="number" id="qtyInput" value="1" min="1" style="width: 60px; text-align: center; font-size: 32px; font-weight: 900; background: transparent; border: none; color: var(--cream); outline: none; padding:0;">
                <button onclick="document.getElementById('qtyInput').stepUp()" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid var(--border); background: var(--surface2); color: var(--gold); font-size: 24px; font-weight: bold; cursor: pointer; transition:0.2s;">+</button>
            </div>
            <div style="display:flex; gap: 12px; justify-content: center;">
                <button onclick="closeQtyModal()" style="flex:1; padding: 14px; border-radius: 16px; border: 2px solid var(--red); background: transparent; color: var(--red); font-size: 24px; cursor: pointer; transition:0.2s;" title="Batal">❌</button>
                <button onclick="submitQty()" style="flex:1; padding: 14px; border-radius: 16px; border: none; background: var(--green); color: #fff; font-size: 24px; cursor: pointer; box-shadow: 0 4px 0 #2d6648; transition:0.2s;" title="Pesan">✅</button>
            </div>
        </div>
    </div>

    <script>
        let currentQtyItem = null;
        function openQtyModal(id, name, price) {
            currentQtyItem = { id, name, price };
            document.getElementById('qtyModalName').textContent = name;
            document.getElementById('qtyInput').value = 1;
            document.getElementById('qtyModal').style.display = 'flex';
        }
        function closeQtyModal() {
            document.getElementById('qtyModal').style.display = 'none';
            currentQtyItem = null;
        }
        function submitQty() {
            const qty = parseInt(document.getElementById('qtyInput').value) || 1;
            if (isMixedMode) {
                mixedItems.push({ menu_id: currentQtyItem.id, nama: currentQtyItem.name, harga: currentQtyItem.price, jumlah: qty });
                document.getElementById('menu-' + currentQtyItem.id).classList.add('selected');
                updateRakitanUI();
            } else {
                quickOrder(currentQtyItem.id, currentQtyItem.name, qty);
            }
            closeQtyModal();
        }
        // --- TOPBAR LOGIC ---
        function toggleTopbar() {
            const topbar = document.getElementById('mainTopbar');
            const icon = document.getElementById('toggleIcon');
            topbar.classList.toggle('expanded');
            
            if (topbar.classList.contains('expanded')) {
                icon.textContent = '✕';
            } else {
                icon.textContent = '☰';
            }
        }

        function toggleSidebarFull() {
            if (window.innerWidth > 992) return;
            const sidebar = document.getElementById('sidebar');
            const icon = document.getElementById('expandIcon');
            const isFull = sidebar.classList.toggle('full-screen');
            document.body.classList.toggle('sidebar-open');
            
            icon.textContent = isFull ? '✕' : '⛶';
        }

        let currentHighlightItem = null;

        function highlightOrdersByItem(itemName, btn) {
            const items = document.querySelectorAll('#orderList .history-item');
            const badges = document.querySelectorAll('.summary-badge');
            
            if (currentHighlightItem === itemName) {
                // Remove highlight
                items.forEach(el => el.classList.remove('highlighted'));
                if (btn) btn.classList.remove('active');
                currentHighlightItem = null;
            } else {
                // Add highlight
                items.forEach(el => {
                    const nameEl = el.querySelector('.h-name');
                    if (nameEl && nameEl.textContent.includes(itemName)) {
                        el.classList.add('highlighted');
                    } else {
                        el.classList.remove('highlighted');
                    }
                });
                badges.forEach(b => b.classList.remove('active'));
                if (btn) btn.classList.add('active');
                currentHighlightItem = itemName;
            }
        }

        function updateOrderSummary() {
            const names = document.querySelectorAll('#orderList .h-name');
            const summary = {};
            names.forEach(el => {
                // Split by comma and clean whitespace
                const items = el.textContent.split(',').map(s => s.trim());
                items.forEach(item => {
                    if (item) summary[item] = (summary[item] || 0) + 1;
                });
            });

            const bar = document.getElementById('orderSummaryBar');
            if (!bar) return;
            
            let html = '';
            // Sort by count descending
            const sortedItems = Object.entries(summary).sort((a, b) => b[1] - a[1]);
            
            for (const [name, count] of sortedItems) {
                const isActive = (currentHighlightItem === name);
                const safeName = name.replace(/'/g, "\\'");
                html += `<div class="summary-badge ${isActive ? 'active' : ''}" onclick="highlightOrdersByItem('${safeName}', this)">${name} <span>${count}</span></div>`;
            }
            bar.innerHTML = html || '<div style="color:var(--text-dim); font-size:12px; padding: 5px;">Belum ada pesanan aktif</div>';
            
            // Re-apply highlight to the new items if needed
            if (currentHighlightItem) {
                const items = document.querySelectorAll('#orderList .history-item');
                items.forEach(el => {
                    const nameEl = el.querySelector('.h-name');
                    if (nameEl && nameEl.textContent.includes(currentHighlightItem)) {
                        el.classList.add('highlighted');
                    }
                });
            }
        }

        // --- THEME LOGIC ---
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('warungku_theme', next);
            updateThemeBtn();
        }
        function updateThemeBtn() {
            const current = document.documentElement.getAttribute('data-theme');
            const btn = document.getElementById('themeBtn');
            btn.innerHTML = current === 'dark' ? '☀️ Mode Terang' : '🌙 Mode Gelap';
        }
        updateThemeBtn();

        // --- ORDER LOGIC ---
        let isMixedMode = false;
        let mixedItems = [];

        function startMixed() {
            isMixedMode = true;
            mixedItems = [];
            document.getElementById('rakitanBar').style.display = 'flex';
            document.getElementById('mixedBtn').style.display = 'none';
            updateRakitanUI();
        }

        function cancelMixed() {
            isMixedMode = false;
            mixedItems = [];
            document.getElementById('rakitanBar').style.display = 'none';
            document.getElementById('mixedBtn').style.display = 'block';
            document.querySelectorAll('.card.selected').forEach(c => c.classList.remove('selected'));
        }

        function handleMenuClick(id, name, price) {
            if (ENABLE_QTY_INPUT) {
                openQtyModal(id, name, price);
                return;
            }

            if (isMixedMode) {
                // Add to mixed items
                mixedItems.push({ menu_id: id, nama: name, harga: price, jumlah: 1 });
                document.getElementById('menu-' + id).classList.add('selected');
                updateRakitanUI();
            } else {
                // Regular one-click
                quickOrder(id, name, 1);
            }
        }

        function updateRakitanUI() {
            const total = mixedItems.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
            const totalQty = mixedItems.reduce((sum, item) => sum + item.jumlah, 0);
            document.getElementById('rakitanCount').textContent = totalQty;
            document.getElementById('rakitanTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        async function sendMixed() {
            if (mixedItems.length === 0) return alert('Pilih menu dulu!');
            
            document.getElementById('loader').style.display = 'flex';
            const kode = 'MIX-' + Math.random().toString(36).substr(2, 6).toUpperCase();
            const orderData = { kode_pesanan: kode, items: mixedItems };
            const orderName = "Pesanan Campur";
            
            try {
                const res = await fetch('api_checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderData)
                });
                const data = await res.json();
                if (data.error) throw new Error(data.message);

                showToast(`✅ Pesanan Campur Berhasil!`);
                cancelMixed();
                refreshActiveOrders();
            } catch (e) {
                console.warn("Offline detected, queuing order...");
                offlineQueue.add(orderData, orderName);
                cancelMixed();
            } finally {
                document.getElementById('loader').style.display = 'none';
            }
        }

        // --- OFFLINE SYNC LOGIC ---
        class OfflineQueue {
            constructor() {
                this.queue = JSON.parse(localStorage.getItem('warungku_offline_orders') || '[]');
                this.isSyncing = false;
                setTimeout(() => this.updateUI(), 500);
            }

            add(orderData, name) {
                this.queue.push({ data: orderData, name: name, id: Date.now() });
                this.save();
                this.updateUI();
                showToast("📡 Internet mati, pesanan disimpan offline!");
                this.trySync();
            }

            save() { localStorage.setItem('warungku_offline_orders', JSON.stringify(this.queue)); }

            updateUI() {
                const indicator = document.getElementById('offlineIndicator');
                const countText = document.getElementById('offlineCount');
                if (indicator && this.queue.length > 0) {
                    indicator.style.display = 'flex';
                    countText.textContent = this.queue.length;
                } else if (indicator) {
                    indicator.style.display = 'none';
                }
            }

            async trySync() {
                if (this.isSyncing || this.queue.length === 0) return;
                this.isSyncing = true;

                while (this.queue.length > 0) {
                    const order = this.queue[0];
                    try {
                        const res = await fetch('api_checkout.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(order.data)
                        });
                        const data = await res.json();
                        if (!data.error) {
                            this.queue.shift();
                            this.save();
                            this.updateUI();
                            showToast(`✅ Pesanan "${order.name}" tersinkron!`);
                        } else { break; }
                    } catch (e) { break; }
                }
                this.isSyncing = false;
                if (this.queue.length === 0) refreshActiveOrders();
            }
        }

        const offlineQueue = new OfflineQueue();
        window.addEventListener('online', () => offlineQueue.trySync());
        setInterval(() => offlineQueue.trySync(), 10000); // Berkala tiap 10 detik

        // --- SOUND LOGIC ---
        const notificationSound = new Audio("https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3");
        notificationSound.load();

        let lastMaxId = 0;
        let lastTotal = -1;
        let lastMenuVersion = '';
        let lastMenuCount = -1;
        let isFirstLoad = true;
        let audioUnlocked = false;
        let lastActiveOrdersHtml = ''; // Cache untuk pesanan online terakhir

        // Unlock audio on first interaction
        document.addEventListener('click', () => {
            if (!audioUnlocked) {
                notificationSound.play().then(() => {
                    notificationSound.pause();
                    notificationSound.currentTime = 0;
                    audioUnlocked = true;
                }).catch(e => {});
            }
        }, { once: true });

        let lastOfflineCount = -1;

        async function refreshActiveOrders() {
            try {
                const checkRes = await fetch('app.php?check_updates=1');
                const checkData = await checkRes.json();
                
                const currentMaxId = parseInt(checkData.max_id || 0);
                const currentTotal = parseInt(checkData.total || 0);
                const currentMenuVersion = checkData.menu_version || '';
                const currentMenuCount = parseInt(checkData.menu_count || 0);

                let needsRender = false;

                // Sinkronisasi Menu (CRUD)
                if (!isFirstLoad && (currentMenuVersion !== lastMenuVersion || currentMenuCount !== lastMenuCount)) {
                    await refreshMenuList();
                }

                // 3. Cek apakah Order berubah
                if (isFirstLoad || currentMaxId !== lastMaxId || currentTotal !== lastTotal) {
                    const res = await fetch('app.php?ajax_active_orders=1');
                    lastActiveOrdersHtml = await res.text();
                    needsRender = true;
                    
                    if (!isFirstLoad && currentMaxId > lastMaxId) {
                        notificationSound.play().catch(e => {
                            showToast("🔔 Pesanan Baru Masuk! (Klik layar untuk aktifkan suara)");
                        });
                    }
                }

                // Cek apakah ada perubahan jumlah antrean offline
                if (offlineQueue.queue.length !== lastOfflineCount) {
                    needsRender = true;
                    lastOfflineCount = offlineQueue.queue.length;
                }

                if (needsRender) {
                    renderFullOrderList();
                }

                lastMaxId = currentMaxId;
                lastTotal = currentTotal;
                lastMenuVersion = currentMenuVersion;
                lastMenuCount = currentMenuCount;
                isFirstLoad = false;
            } catch (e) {
                console.warn("Gagal refresh (Mungkin Offline):", e);
                // Jika offline, kita tetap render jika jumlah antrean berubah
                if (offlineQueue.queue.length !== lastOfflineCount) {
                    renderFullOrderList();
                    lastOfflineCount = offlineQueue.queue.length;
                }
            }
        }

        function renderFullOrderList() {
            let html = lastActiveOrdersHtml || '<div class="empty-msg">Menghubungkan ke server...</div>';
            
            if (offlineQueue.queue.length > 0) {
                let offlineHtml = '';
                offlineQueue.queue.forEach(order => {
                    offlineHtml += renderOfflineOrder(order);
                });
                if (html.includes('empty-msg')) html = '';
                html = offlineHtml + html;
            }
            
            const listEl = document.getElementById('orderList');
            if (listEl) {
                listEl.innerHTML = html;
                updateOrderSummary();
            }
        }

        function renderOfflineOrder(order) {
            // Jika pesanan campur, ambil nama-nama itemnya
            let itemText = order.name;
            if (order.data.items.length > 1) {
                itemText = order.data.items.map(i => i.nama).filter(n => n).join(', ');
            }
            return `
                <div class="history-item" style="border-color: #ff9800; border-style: dashed; opacity: 0.8;">
                    <div class="h-meta" style="color: #ff9800; font-weight: 900;">📶 OFFLINE · MENUNGGU SINYAL</div>
                    <div class="h-name">${itemText}</div>
                    <div class="h-meta">Kode: ${order.data.kode_pesanan}</div>
                    <div style="margin-top:10px; font-size:12px; color: var(--text-dim); font-style: italic;">
                        Pesanan tersimpan di memori HP. Otomatis terkirim saat sinyal kembali.
                    </div>
                    <div class="h-actions" style="margin-top:10px; opacity: 0.5; pointer-events: none;">
                        <button class="h-btn btn-done">✅ SELESAI</button>
                        <button class="h-btn btn-cancel"><span>❌</span></button>
                    </div>
                </div>`;
        }

        async function refreshMenuList() {
            try {
                const res = await fetch('app.php?ajax_menu=1');
                const html = await res.text();
                const grid = document.querySelector('.grid');
                if (grid) {
                    grid.innerHTML = html;
                    applyFilters(); // Re-apply current search/category
                }
            } catch (e) {
                console.error("Gagal refresh menu:", e);
            }
        }

        async function updateStatus(id, status) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('id', id);
            formData.append('status', status);

            const res = await fetch('app.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.error) refreshActiveOrders();
        }

        let currentCategory = 'Semua';

        function filterMenu(cat, btn) {
            currentCategory = cat;
            if (btn) {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
            applyFilters();
        }

        function applyFilters() {
            const searchQuery = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.card').forEach(c => {
                const name = c.querySelector('.card-name').textContent.toLowerCase();
                const category = c.dataset.cat;
                
                const matchesSearch = name.includes(searchQuery);
                const matchesCategory = (currentCategory === 'Semua' || category === currentCategory);
                
                c.style.display = (matchesSearch && matchesCategory) ? 'block' : 'none';
            });
        }

        let busy = false;
        async function quickOrder(id, name, qty = 1) {
            if (busy) return;
            busy = true;
            document.getElementById('loader').style.display = 'flex';

            const kode = 'ORD-' + Math.random().toString(36).substr(2, 6).toUpperCase();
            const orderData = { kode_pesanan: kode, items: [{ menu_id: id, jumlah: qty }] };
            
            try {
                const res = await fetch('api_checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderData)
                });
                const data = await res.json();
                if (data.error) throw new Error(data.message);

                showToast(`✅ ${name} Berhasil!`);
                refreshActiveOrders();
            } catch (e) {
                console.warn("Offline detected, queuing order...");
                offlineQueue.add(orderData, name);
            } finally {
                document.getElementById('loader').style.display = 'none';
                busy = false;
            }
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.display = 'none';
            void t.offsetWidth; 
            t.style.display = 'block';
            if (window.toastTimeout) clearTimeout(window.toastTimeout);
            window.toastTimeout = setTimeout(() => t.style.display = 'none', 3000);
        }

        refreshActiveOrders();
        setInterval(refreshActiveOrders, 2000);
    </script>
</body>
</html>