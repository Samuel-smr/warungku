<?php
// ============================================================
// app.php — One-Click Order System (WarungKu SaaS)
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
    $status = $_POST['status']; // 'selesai' or 'dibatalkan'
    
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
$shop = $stmtSub->fetch();

if ($shop && $shop['subscription_ends_at'] && strtotime($shop['subscription_ends_at'] . ' 23:59:59') < time()) {
    die("Masa aktif toko " . htmlspecialchars($shop['name']) . " telah habis. Silakan hubungi admin.");
}

// Ambil Menu
$stmt = $pdo->prepare("
    SELECT m.id, m.nama, k.nama as category, m.harga, m.gambar_url 
    FROM menu m 
    JOIN kategori k ON k.id = m.kategori_id 
    WHERE m.shop_id = :shop_id AND m.stok = 1
    ORDER BY k.urutan, m.nama
");
$stmt->execute(['shop_id' => $shop_id]);
$menu_items = $stmt->fetchAll();
$categories = array_unique(array_column($menu_items, 'category'));

// Ambil Pesanan AKTIF (status = 'proses')
function getActiveOrders($pdo, $shop_id) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.kode_pesanan, p.total, p.status, p.created_at, GROUP_CONCAT(dp.nama_menu SEPARATOR ', ') as items
        FROM pesanan p
        JOIN detail_pesanan dp ON p.id = dp.pesanan_id
        WHERE p.shop_id = :shop_id AND p.status = 'proses'
        GROUP BY p.id
        ORDER BY p.id ASC
    ");
    $stmt->execute(['shop_id' => $shop_id]);
    return $stmt->fetchAll();
}

// Jika request AJAX untuk refresh daftar pesanan aktif
if (isset($_GET['ajax_active_orders'])) {
    $orders = getActiveOrders($pdo, $shop_id);
    foreach ($orders as $o) {
        echo '<div class="history-item">
                <div class="h-meta">'.date('H:i', strtotime($o['created_at'])).' · '.$o['kode_pesanan'].'</div>
                <div class="h-name">'.htmlspecialchars($o['items']).'</div>
                <div class="h-price">Rp '.number_format($o['total'], 0, ',', '.').'</div>
                <div class="h-actions">
                    <button class="h-btn btn-done" onclick="updateStatus('.$o['id'].', \'selesai\')">Selesai</button>
                    <button class="h-btn btn-cancel" onclick="updateStatus('.$o['id'].', \'dibatalkan\')">Batal</button>
                </div>
              </div>';
    }
    if (empty($orders)) echo '<div class="empty-msg">Dapur bersih! Belum ada pesanan aktif.</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Cepat — WarungKu</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f0e0b; --surface: #1a1814; --surface2: #231f1a;
            --gold: #d4a853; --cream: #f5edd8; --text: #f0e8d5; --text-dim: #8a7f6e;
            --border: rgba(212,168,83,0.15); --red: #e05252; --green: #4caf7d;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'DM Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; height: 100vh; overflow: hidden; }

        /* SIDEBAR PESANAN AKTIF */
        .sidebar { width: 380px; background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid var(--border); background: var(--surface2); display: flex; align-items: center; justify-content: space-between; }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; color: var(--gold); font-size: 20px; }
        .history-list { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
        
        .history-item { background: var(--surface2); border: 1px solid var(--border); border-radius: 12px; padding: 14px; position: relative; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity:0; transform:translateY(10px); } }
        
        .h-meta { font-size: 11px; color: var(--text-dim); margin-bottom: 4px; }
        .h-name { font-size: 14px; font-weight: 700; color: var(--cream); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .h-price { font-size: 15px; font-weight: 700; color: var(--gold); margin-bottom: 10px; }
        
        .h-actions { display: flex; gap: 8px; }
        .h-btn { flex: 1; padding: 8px; border-radius: 8px; border: none; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-done { background: var(--green); color: #fff; }
        .btn-cancel { background: var(--red); color: #fff; }
        .h-btn:hover { opacity: 0.8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .empty-msg { text-align: center; color: var(--text-dim); padding: 60px 0; font-size: 14px; line-height: 1.6; }

        /* MAIN */
        .main { flex: 1; display: flex; flex-direction: column; background: var(--bg); }
        .topbar { height: 70px; padding: 0 32px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .brand { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 900; color: var(--gold); }
        .brand span { color: var(--cream); }
        
        .content { flex: 1; overflow-y: auto; padding: 32px; }
        .filter-bar { display: flex; gap: 8px; margin-bottom: 32px; overflow-x: auto; padding-bottom: 8px; }
        .filter-btn { padding: 8px 20px; border-radius: 100px; border: 1px solid var(--border); background: none; color: var(--text-dim); cursor: pointer; white-space: nowrap; transition: 0.2s; }
        .filter-btn.active { background: var(--gold); color: var(--bg); border-color: var(--gold); font-weight: 700; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; cursor: pointer; transition: 0.2s; position: relative; }
        .card:hover { transform: translateY(-4px); border-color: var(--gold); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .card:active { transform: scale(0.95); }
        .card-img { width: 100%; height: 140px; object-fit: cover; }
        .card-body { padding: 12px; text-align: center; }
        .card-name { font-weight: 700; color: var(--cream); margin-bottom: 4px; font-size: 15px; }
        .card-price { color: var(--gold); font-weight: 700; font-size: 17px; }

        .toast { position: fixed; bottom: 32px; left: 50%; transform: translateX(-50%); background: var(--green); color: #fff; padding: 12px 30px; border-radius: 100px; font-weight: 700; z-index: 1000; display: none; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .loader { position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(2px); z-index: 999; display: none; align-items: center; justify-content: center; }

        @media (max-width: 992px) {
            body { flex-direction: column-reverse; }
            .sidebar { width: 100%; height: 320px; border-right: none; border-top: 1px solid var(--border); }
            .topbar { padding: 0 16px; height: 60px; }
            .content { padding: 16px; }
            .grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Pesanan Aktif</h2>
            <a href="riwayat.php" style="color:var(--text-dim); text-decoration:none; font-size:12px;">Riwayat →</a>
        </div>
        <div class="history-list" id="orderList">
            <!-- AJAX LOAD -->
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="brand">Warung<span>Ku</span></div>
            <div style="display:flex; align-items:center; gap:16px;">
                <span style="font-size:14px; font-weight:600; color:var(--text-dim)"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                <a href="logout.php" style="color:var(--red); text-decoration:none; font-weight:700; font-size:13px;">Keluar</a>
            </div>
        </header>

        <div class="content">
            <div class="filter-bar">
                <button class="filter-btn active" onclick="filterMenu('Semua', this)">Semua</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="filter-btn" onclick="filterMenu('<?= htmlspecialchars($cat) ?>', this)"><?= htmlspecialchars($cat) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="grid">
                <?php foreach ($menu_items as $item): ?>
                    <div class="card" onclick="quickOrder(<?= $item['id'] ?>, '<?= addslashes($item['nama']) ?>')" data-cat="<?= htmlspecialchars($item['category']) ?>">
                        <img class="card-img" src="<?= htmlspecialchars($item['gambar_url']) ?>" onerror="this.src='https://via.placeholder.com/200/1a1814/d4a853?text=Menu'">
                        <div class="card-body">
                            <div class="card-name"><?= htmlspecialchars($item['nama']) ?></div>
                            <div class="card-price">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <div class="loader" id="loader"></div>
    <div class="toast" id="toast"></div>

    <script>
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

                showToast(`✓ ${name} Berhasil!`);
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
            t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 2000);
        }

        refreshActiveOrders();
    </script>
</body>
</html>