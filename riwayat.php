<?php
// ============================================================
// riwayat.php — Riwayat Pesanan WarungKu (Simplified)
// ============================================================
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$shop_id = $_SESSION['shop_id'] ?? 0;

// Handle Delete Action
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
    <style>
        :root {
            --bg: #0f0e0b; --surface: #1a1814; --surface2: #231f1a;
            --gold: #d4a853; --cream: #f5edd8; --text: #f0e8d5; --text-dim: #8a7f6e;
            --border: rgba(212,168,83,0.15); --red: #e05252; --green: #4caf7d;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'DM Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }

        .topbar { height: 70px; padding: 0 40px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; background: rgba(15,14,11,0.9); backdrop-filter: blur(10px); z-index: 100; }
        .brand { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 900; color: var(--gold); text-decoration: none; }
        .btn-back { color: var(--text-dim); text-decoration: none; font-size: 14px; font-weight: 600; padding: 8px 16px; border: 1px solid var(--border); border-radius: 8px; transition: 0.2s; }
        .btn-back:hover { border-color: var(--gold); color: var(--gold); }

        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 40px; }
        .header h1 { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--cream); }
        
        .stats { display: flex; gap: 32px; }
        .stat-item { text-align: right; }
        .stat-label { font-size: 11px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; }
        .stat-val { font-size: 20px; font-weight: 700; color: var(--gold); }

        .controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn-clear { background: none; border: 1px solid rgba(224,82,82,0.3); color: var(--red); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .btn-clear:hover { background: var(--red); color: #fff; }

        .order-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 16px; display: flex; align-items: center; gap: 20px; transition: 0.2s; }
        .order-card:hover { border-color: rgba(212,168,83,0.4); }
        .order-card.cancelled { opacity: 0.5; filter: grayscale(1); }
        
        .order-img { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border); }
        .order-info { flex: 1; }
        .o-meta { font-size: 11px; color: var(--text-dim); margin-bottom: 4px; }
        .o-name { font-size: 16px; font-weight: 700; color: var(--cream); margin-bottom: 4px; }
        .o-price { font-size: 15px; font-weight: 700; color: var(--gold); }
        
        .order-status { text-align: right; }
        .status-badge { font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 4px; display: inline-block; margin-bottom: 8px; letter-spacing: 1px; }
        .status-badge.selesai { background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid var(--green); }
        .status-badge.proses { background: rgba(212,168,83,0.1); color: var(--gold); border: 1px solid var(--gold); }
        .status-badge.dibatalkan { background: rgba(224,82,82,0.1); color: var(--red); border: 1px solid var(--red); }
        
        .btn-del-item { background: none; border: none; color: var(--text-dim); cursor: pointer; padding: 8px; font-size: 18px; transition: 0.2s; }
        .btn-del-item:hover { color: var(--red); }

        .empty { text-align: center; padding: 80px 0; color: var(--text-dim); }
        
        @media (max-width: 600px) {
            .header { flex-direction: column; align-items: flex-start; gap: 20px; }
            .stat-item { text-align: left; }
            .order-card { flex-wrap: wrap; gap: 16px; }
            .order-img { width: 60px; height: 60px; }
            .order-status { width: 100%; text-align: left; border-top: 1px solid var(--border); padding-top: 12px; display: flex; align-items: center; justify-content: space-between; }
        }
    </style>
</head>
<body>

    <header class="topbar">
        <a href="app.php" class="brand">Warung<span>Ku</span></a>
        <a href="app.php" class="btn-back">← Kembali ke Kasir</a>
    </header>

    <div class="container">
        <div class="header">
            <div>
                <h1>Riwayat Pesanan</h1>
                <p style="color:var(--text-dim); font-size:14px;">Semua transaksi yang telah diproses</p>
            </div>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-label">Total Pesanan</div>
                    <div class="stat-val"><?= count($orders) ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Pendapatan</div>
                    <div class="stat-val">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($orders)): ?>
            <div class="controls">
                <span style="font-size:13px; color:var(--text-dim)">Menampilkan <?= count($orders) ?> transaksi terakhir</span>
                <form method="POST" onsubmit="return confirm('Hapus semua riwayat?')">
                    <input type="hidden" name="action" value="clear_all">
                    <button type="submit" class="btn-clear">🗑 Hapus Semua</button>
                </form>
            </div>

            <?php foreach ($orders as $o): ?>
                <div class="order-card <?= $o['status'] === 'dibatalkan' ? 'cancelled' : '' ?>">
                    <img class="order-img" src="<?= htmlspecialchars($o['gambar_url']) ?>" onerror="this.src='https://via.placeholder.com/100/1a1814/d4a853?text=Menu'">
                    <div class="order-info">
                        <div class="o-meta"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?> · <?= $o['kode_pesanan'] ?></div>
                        <div class="o-name"><?= htmlspecialchars($o['nama_menu']) ?></div>
                        <div class="o-price">Rp <?= number_format($o['total'], 0, ',', '.') ?></div>
                    </div>
                    <div class="order-status">
                        <div class="status-badge <?= $o['status'] ?>"><?= strtoupper($o['status']) ?></div>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus item ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="kode_pesanan" value="<?= $o['kode_pesanan'] ?>">
                            <button type="submit" class="btn-del-item" title="Hapus">×</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="empty">
                <div style="font-size:48px; margin-bottom:20px;">📋</div>
                <h3>Belum ada riwayat</h3>
                <p>Pesanan yang selesai akan muncul di sini.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>