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
$stmtSub = $pdo->prepare("SELECT name, subscription_ends_at FROM shops WHERE id = :shop_id");
$stmtSub->execute(['shop_id' => $shop_id]);
$shop_info = $stmtSub->fetch();

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
        echo '<div class="history-item">
                <div class="h-meta">Jam: '.date('H:i', strtotime($o['created_at'])).' · #'.$o['kode_pesanan'].'</div>
                <div class="h-name">'.htmlspecialchars($o['items']).'</div>
                <div class="h-price">Rp '.number_format($o['total'], 0, ',', '.').'</div>
                <div class="h-actions">
                    <button class="h-btn btn-done" onclick="updateStatus('.$o['id'].', \'selesai\')">✅ SELESAI</button>
                    <button class="h-btn btn-cancel" onclick="updateStatus('.$o['id'].', \'dibatalkan\')">❌ BATAL</button>
                </div>
              </div>';
    }
    if (empty($orders)) echo '<div class="empty-msg">Belum ada pesanan aktif.<br><small>Klik menu di sebelah untuk menambah pesanan.</small></div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir WarungKu — Mudah & Cepat</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script>
        const savedTheme = localStorage.getItem('warungku_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
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
        .history-list { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
        
        .history-item { background: var(--surface2); border: 2px solid var(--border); border-radius: 16px; padding: 20px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity:0; transform:translateY(15px); } }
        
        .h-meta { font-size: 13px; color: var(--text-dim); margin-bottom: 8px; font-weight: 700; text-transform: uppercase; }
        .h-name { font-size: 18px; font-weight: 700; color: var(--cream); margin-bottom: 8px; line-height: 1.4; }
        .h-price { font-size: 20px; font-weight: 700; color: var(--gold); margin-bottom: 16px; }
        
        .h-actions { display: flex; gap: 12px; }
        .h-btn { flex: 1; padding: 14px; border-radius: 12px; border: none; font-size: 14px; font-weight: 800; cursor: pointer; transition: 0.2s; letter-spacing: 0.5px; }
        .btn-done { background: var(--green); color: #fff; box-shadow: 0 4px 0 #2d6648; }
        .btn-cancel { background: var(--red); color: #fff; box-shadow: 0 4px 0 #a33b3b; }
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

        .content { flex: 1; overflow-y: auto; padding: 32px; }
        .filter-bar { display: flex; gap: 12px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 12px; }
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
        .card-img { width: 100%; height: 180px; object-fit: cover; }
        .card-body { padding: 16px; text-align: center; }
        .card-name { font-weight: 700; color: var(--cream); margin-bottom: 8px; font-size: 18px; min-height: 50px; display: flex; align-items: center; justify-content: center; }
        .card-price { color: var(--gold); font-weight: 900; font-size: 22px; background: var(--surface2); padding: 8px; border-radius: 10px; margin-bottom: 8px; }
        .card-sold { font-size: 14px; color: var(--text-dim); font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 4px; }
        .badge-popular { position: absolute; top: 12px; left: 12px; background: #ff9800; color: #000; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 900; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }

        .toast { position: fixed; top: 32px; left: 50%; transform: translateX(-50%); background: var(--green); color: #fff; padding: 16px 40px; border-radius: 100px; font-weight: 800; font-size: 18px; z-index: 1000; display: none; box-shadow: 0 10px 40px rgba(0,0,0,0.8); animation: toastIn 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28); }
        @keyframes toastIn { from { top: -100px; opacity: 0; } to { top: 32px; opacity: 1; } }
        .loader { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 999; display: none; align-items: center; justify-content: center; color: var(--gold); font-weight: 800; font-size: 20px; }

        @media (max-width: 992px) {
            body { flex-direction: column-reverse; height: auto; min-height: 100vh; overflow: visible; }
            .sidebar { width: 100%; height: auto; max-height: 500px; border-right: none; border-top: 2px solid var(--border); }
            .history-list { height: 350px; }
            .main { flex: none; width: 100%; min-height: 100vh; }
            .topbar { padding: 0 16px; height: auto; padding: 16px; flex-wrap: wrap; gap: 12px; position: sticky; top: 0; z-index: 100; }
            .content { padding: 20px; overflow: visible; flex: none; }
            .grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; }
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

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Pesanan Aktif</h2>
        </div>
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

        <header class="topbar">
            <div class="brand">Warung<span>Ku</span></div>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <button onclick="startMixed()" class="btn-nav manage" id="mixedBtn" style="background:var(--gold); color:#000;">➕ Pesanan Campur (Rakitan)</button>
                <button onclick="toggleTheme()" class="btn-nav theme" id="themeBtn">☀️ Mode Terang</button>
                <a href="kelola_menu.php" class="btn-nav manage">⚙️ Kelola Menu</a>
                <a href="riwayat.php" class="btn-nav history">📋 Riwayat</a>
                <a href="logout.php" class="btn-nav logout">🚪 Keluar</a>
            </div>
        </header>

        <div class="content">
            <div class="filter-bar">
                <button class="filter-btn active" onclick="filterMenu('Semua', this)">Semua Menu</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="filter-btn" onclick="filterMenu('<?= htmlspecialchars($cat) ?>', this)"><?= htmlspecialchars($cat) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="grid">
                <?php foreach ($menu_items as $index => $item): ?>
                    <div class="card" id="menu-<?= $item['id'] ?>" onclick="handleMenuClick(<?= $item['id'] ?>, '<?= addslashes($item['nama']) ?>', <?= $item['harga'] ?>)" data-cat="<?= htmlspecialchars($item['category']) ?>">
                        <?php if ($item['total_terjual'] > 0 && $index < 3): ?>
                            <div class="badge-popular">🔥 TERLARIS</div>
                        <?php endif; ?>
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

    <script>
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
            if (isMixedMode) {
                // Add to mixed items
                mixedItems.push({ menu_id: id, nama: name, harga: price, jumlah: 1 });
                document.getElementById('menu-' + id).classList.add('selected');
                updateRakitanUI();
            } else {
                // Regular one-click
                quickOrder(id, name);
            }
        }

        function updateRakitanUI() {
            const total = mixedItems.reduce((sum, item) => sum + item.harga, 0);
            document.getElementById('rakitanCount').textContent = mixedItems.length;
            document.getElementById('rakitanTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        async function sendMixed() {
            if (mixedItems.length === 0) return alert('Pilih menu dulu!');
            
            document.getElementById('loader').style.display = 'flex';
            const kode = 'MIX-' + Math.random().toString(36).substr(2, 6).toUpperCase();
            
            try {
                const res = await fetch('api_checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ kode_pesanan: kode, items: mixedItems })
                });
                const data = await res.json();
                if (data.error) throw new Error(data.message);

                showToast(`✅ Pesanan Campur Berhasil!`);
                cancelMixed();
                refreshActiveOrders();
            } catch (e) {
                alert('Gagal: ' + e.message);
            } finally {
                document.getElementById('loader').style.display = 'none';
            }
        }

        async function refreshActiveOrders() {
            const res = await fetch('app.php?ajax_active_orders=1');
            const html = await res.text();
            document.getElementById('orderList').innerHTML = html;
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

        function filterMenu(cat, btn) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.card').forEach(c => {
                c.style.display = (cat === 'Semua' || c.dataset.cat === cat) ? 'block' : 'none';
            });
        }

        let busy = false;
        async function quickOrder(id, name) {
            if (busy) return;
            busy = true;
            document.getElementById('loader').style.display = 'flex';

            const kode = 'ORD-' + Math.random().toString(36).substr(2, 6).toUpperCase();
            try {
                const res = await fetch('api_checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ kode_pesanan: kode, items: [{ menu_id: id, jumlah: 1 }] })
                });
                const data = await res.json();
                if (data.error) throw new Error(data.message);

                showToast(`✅ ${name} Berhasil!`);
                refreshActiveOrders();
            } catch (e) {
                alert('Gagal: ' + e.message);
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
    </script>
</body>
</html>