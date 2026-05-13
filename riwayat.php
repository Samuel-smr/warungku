<?php
// ============================================================
// riwayat.php — Riwayat Pesanan (Optimized for 40+)
// ============================================================
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$shop_id = $_SESSION['shop_id'] ?? 0;

// Handle Actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['kode_pesanan'])) {
        $stmt = $pdo->prepare("DELETE FROM pesanan WHERE kode_pesanan = :kode AND shop_id = :shop_id");
        $stmt->execute(['kode' => $_POST['kode_pesanan'], 'shop_id' => $shop_id]);
    } elseif ($_POST['action'] === 'clear_all') {
        $stmt = $pdo->prepare("DELETE FROM pesanan WHERE shop_id = :shop_id");
        $stmt->execute(['shop_id' => $shop_id]);
    }
    header('Location: riwayat.php');
    exit;
}

// Ambil Riwayat
$stmt = $pdo->prepare("
    SELECT p.kode_pesanan, p.total, p.status, p.created_at, 
           dp.nama_menu, dp.gambar_url, dp.harga_satuan
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id = dp.pesanan_id
    WHERE p.shop_id = :shop_id
    ORDER BY p.id DESC
");
$stmt->execute(['shop_id' => $shop_id]);
$orders = $stmt->fetchAll();

// Stats
$total_revenue = 0;
foreach($orders as $o) if($o['status'] === 'selesai') $total_revenue += $o['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat — WarungKu</title>
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
        body { background: var(--bg); color: var(--text); min-height: 100vh; font-size: 16px; transition: background 0.3s; }

        .topbar { height: 80px; padding: 0 40px; border-bottom: 2px solid var(--border); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; background: var(--surface); z-index: 100; }
        .brand { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 900; color: var(--gold); text-decoration: none; }
        .btn-back { color: var(--gold); text-decoration: none; font-size: 16px; font-weight: 700; padding: 10px 20px; border: 2px solid var(--gold); border-radius: 12px; transition: 0.2s; }
        .btn-back:hover { background: var(--gold); color: var(--bg); }

        .container { max-width: 900px; margin: 48px auto; padding: 0 20px; }
        .header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 48px; gap: 24px; flex-wrap: wrap; }
        .header h1 { font-family: 'Playfair Display', serif; font-size: 40px; color: var(--cream); line-height: 1.1; }
        
        .stats { display: flex; gap: 40px; background: var(--surface); padding: 24px 32px; border-radius: 20px; border: 2px solid var(--border); }
        .stat-item { text-align: right; }
        .stat-label { font-size: 13px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 4px; }
        .stat-val { font-size: 26px; font-weight: 800; color: var(--gold); }

        .controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn-clear { background: transparent; border: 2px solid rgba(255, 94, 94, 0.4); color: var(--red); padding: 12px 24px; border-radius: 12px; cursor: pointer; font-size: 15px; font-weight: 800; transition: 0.2s; }
        .btn-clear:hover { background: var(--red); color: #fff; border-color: var(--red); }

        .order-card { background: var(--surface); border: 2px solid var(--border); border-radius: 24px; padding: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 24px; transition: 0.2s; position: relative; }
        .order-card:hover { border-color: rgba(212,168,83,0.5); }
        .order-card.cancelled { opacity: 0.5; filter: grayscale(1); }
        
        .order-img { width: 100px; height: 100px; border-radius: 20px; object-fit: cover; border: 2px solid var(--border); }
        .order-info { flex: 1; }
        .o-meta { font-size: 13px; color: var(--text-dim); margin-bottom: 6px; font-weight: 700; }
        .o-name { font-size: 20px; font-weight: 700; color: var(--cream); margin-bottom: 6px; }
        .o-price { font-size: 22px; font-weight: 900; color: var(--gold); }
        
        .order-status { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 12px; }
        .status-badge { font-size: 12px; font-weight: 800; padding: 6px 14px; border-radius: 8px; letter-spacing: 1px; display: inline-block; }
        .status-badge.selesai { background: rgba(76,175,125,0.15); color: var(--green); border: 2px solid var(--green); }
        .status-badge.proses { background: rgba(212,168,83,0.15); color: var(--gold); border: 2px solid var(--gold); }
        .status-badge.dibatalkan { background: rgba(255,94,94,0.15); color: var(--red); border: 2px solid var(--red); }
        
        .btn-del-item { background: var(--surface2); border: 2px solid var(--border); color: var(--text-dim); cursor: pointer; width: 40px; height: 40px; border-radius: 50%; font-size: 24px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-del-item:hover { color: var(--red); border-color: var(--red); }

        .empty { text-align: center; padding: 100px 20px; color: var(--text-dim); }
        
        @media (max-width: 768px) {
            .header { margin-bottom: 32px; }
            .header h1 { font-size: 32px; }
            .stats { width: 100%; justify-content: space-between; gap: 10px; padding: 20px; }
            .stat-val { font-size: 20px; }
            .order-card { flex-wrap: wrap; padding: 20px; gap: 16px; }
            .order-img { width: 80px; height: 80px; }
            .order-status { width: 100%; flex-direction: row; justify-content: space-between; align-items: center; border-top: 2px solid var(--border); padding-top: 16px; }
            .topbar { padding: 0 16px; }
        }
    </style>
</head>
<body>

    <header class="topbar">
        <a href="app.php" class="brand">Warung<span>Ku</span></a>
        <a href="app.php" class="btn-back">← Kembali</a>
    </header>

    <div class="container">
        <div class="header">
            <div>
                <h1>Riwayat Penjualan</h1>
                <p style="color:var(--text-dim); font-size:18px;">Daftar transaksi yang sudah selesai</p>
            </div>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-label">Total Terjual</div>
                    <div class="stat-val"><?= count($orders) ?> Pesanan</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total Uang Masuk</div>
                    <div class="stat-val">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($orders)): ?>
            <div class="controls">
                <span style="font-size:16px; color:var(--text-dim); font-weight:700;">Menampilkan semua riwayat</span>
                <form method="POST" onsubmit="return confirm('⚠️ Hapus SEMUA riwayat pesanan? Tindakan ini tidak bisa dibatalkan.')">
                    <input type="hidden" name="action" value="clear_all">
                    <button type="submit" class="btn-clear">🗑 Hapus Seluruh Riwayat</button>
                </form>
            </div>

            <?php foreach ($orders as $o): ?>
                <div class="order-card <?= $o['status'] === 'dibatalkan' ? 'cancelled' : '' ?>">
                    <img class="order-img" src="<?= $o['gambar_url'] ?: 'assets/default_menu.png' ?>" onerror="this.onerror=null; this.src='assets/default_menu.png'">
                    <div class="order-info">
                        <div class="o-meta"><?= date('d M Y', strtotime($o['created_at'])) ?> · Jam <?= date('H:i', strtotime($o['created_at'])) ?> · #<?= $o['kode_pesanan'] ?></div>
                        <div class="o-name"><?= htmlspecialchars($o['nama_menu']) ?></div>
                        <div class="o-price">Rp <?= number_format($o['total'], 0, ',', '.') ?></div>
                    </div>
                    <div class="order-status">
                        <div class="status-badge <?= $o['status'] ?>"><?= strtoupper($o['status']) ?></div>
                        <form method="POST" onsubmit="return confirm('Hapus satu catatan ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="kode_pesanan" value="<?= $o['kode_pesanan'] ?>">
                            <button type="submit" class="btn-del-item" title="Hapus Catatan">×</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="empty">
                <div style="font-size:64px; margin-bottom:24px;">📋</div>
                <h3 style="font-size:24px; color:var(--cream); margin-bottom:12px;">Belum Ada Penjualan</h3>
                <p style="font-size:18px;">Catatan pesanan yang selesai akan muncul di sini.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>