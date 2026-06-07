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
        if ($stmt->execute(['kode' => $_POST['kode_pesanan'], 'shop_id' => $shop_id])) {
            logActivity($_SESSION['user_id'], 'DELETE_ORDER', "Menghapus pesanan #{$_POST['kode_pesanan']}");
        }
    } elseif ($_POST['action'] === 'clear_all') {
        $stmt = $pdo->prepare("DELETE FROM pesanan WHERE shop_id = :shop_id");
        if ($stmt->execute(['shop_id' => $shop_id])) {
            logActivity($_SESSION['user_id'], 'CLEAR_HISTORY', "Membersihkan seluruh riwayat pesanan Toko ID $shop_id");
        }
    }
    header('Location: riwayat.php');
    exit;
}

// Filter Logic
$filter = $_GET['filter'] ?? 'semua';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where_sql = "WHERE p.shop_id = :shop_id";
$params = ['shop_id' => $shop_id];

if ($filter === 'hari_ini') {
    $where_sql .= " AND DATE(p.created_at) = CURDATE()";
} elseif ($filter === 'minggu_ini') {
    $where_sql .= " AND YEARWEEK(p.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'bulan_ini') {
    $where_sql .= " AND MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE())";
} elseif ($filter === 'rentang' && $start_date && $end_date) {
    $where_sql .= " AND DATE(p.created_at) BETWEEN :start_date AND :end_date";
    $params['start_date'] = $start_date;
    $params['end_date'] = $end_date;
}

// Ambil Riwayat
$stmt = $pdo->prepare("
    SELECT p.kode_pesanan, p.total, p.status, p.created_at, 
           dp.nama_menu, dp.gambar_url, dp.harga_satuan, dp.jumlah
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id = dp.pesanan_id
    $where_sql
    ORDER BY p.id DESC
");
$stmt->execute($params);
$raw_orders = $stmt->fetchAll();

$grouped_orders = [];
$item_summary = [];
$total_revenue = 0;
$total_sold = 0; // Total orders

foreach($raw_orders as $row) {
    $k = $row['kode_pesanan'];
    if (!isset($grouped_orders[$k])) {
        $grouped_orders[$k] = [
            'kode_pesanan' => $row['kode_pesanan'],
            'total' => $row['total'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'items' => []
        ];
        if ($row['status'] === 'selesai') {
            $total_revenue += $row['total'];
            $total_sold++;
        }
    }
    
    $jumlah = $row['jumlah'] ?? 1;
    $grouped_orders[$k]['items'][] = [
        'nama_menu' => $row['nama_menu'],
        'gambar_url' => $row['gambar_url'],
        'harga_satuan' => $row['harga_satuan'],
        'jumlah' => $jumlah
    ];

    if ($row['status'] === 'selesai') {
        $nama = $row['nama_menu'];
        if (!isset($item_summary[$nama])) {
            $item_summary[$nama] = [
                'jumlah' => 0,
                'gambar_url' => $row['gambar_url']
            ];
        }
        $item_summary[$nama]['jumlah'] += $jumlah;
    }
}

// Urutkan rekap dari yang paling banyak terjual
uasort($item_summary, function($a, $b) { return $b['jumlah'] <=> $a['jumlah']; });
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
        
        /* Summary Grid */
        .summary-section { margin-bottom: 40px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; }
        .summary-card { background: var(--surface); border: 2px solid var(--border); border-radius: 16px; padding: 12px; display: flex; align-items: center; gap: 12px; transition: 0.2s; }
        .summary-card img { width: 50px; height: 50px; border-radius: 12px; object-fit: cover; }
        .sc-info { flex: 1; }
        .sc-name { font-size: 15px; font-weight: 700; color: var(--cream); margin-bottom: 4px; line-height: 1.2; }
        .sc-qty { font-size: 13px; font-weight: 800; color: var(--gold); }

        .o-item-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .o-item-row:last-child { margin-bottom: 0; }
        .o-item-img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); }
        .o-item-name { font-weight: 700; color: var(--cream); font-size: 16px; }
        .o-item-meta { font-size: 13px; color: var(--text-dim); }

        /* Filters */
        .filters { display: flex; gap: 12px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 8px; scrollbar-width: none; -ms-overflow-style: none; }
        .filters::-webkit-scrollbar { display: none; }
        .filter-chip { padding: 10px 20px; border: 2px solid var(--border); border-radius: 20px; background: var(--surface); color: var(--text); font-size: 15px; font-weight: 700; text-decoration: none; white-space: nowrap; cursor: pointer; transition: 0.2s; }
        .filter-chip:hover { border-color: var(--gold); }
        .filter-chip.active { background: var(--gold); color: var(--bg); border-color: var(--gold); }
        
        .custom-date { display: none; background: var(--surface2); padding: 20px; border-radius: 16px; margin-bottom: 24px; border: 2px solid var(--border); animation: fadeIn 0.3s; }
        .custom-date.show { display: block; }
        .date-inputs { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; }
        .date-field { flex: 1; min-width: 140px; display: flex; flex-direction: column; gap: 8px; }
        .date-field label { font-size: 14px; font-weight: 700; color: var(--text-dim); }
        .date-field input { background: var(--surface); border: 2px solid var(--border); padding: 12px 16px; border-radius: 12px; color: var(--text); font-size: 15px; outline: none; font-family: inherit; color-scheme: dark; }
        [data-theme="light"] .date-field input { color-scheme: light; }
        .btn-apply { background: var(--gold); color: var(--bg); border: none; padding: 14px 24px; border-radius: 12px; font-size: 15px; font-weight: 800; cursor: pointer; height: 50px; white-space: nowrap; transition: 0.2s; }
        .btn-apply:hover { opacity: 0.9; transform: translateY(-2px); }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .summary-card { padding: 10px; flex-direction: column; text-align: center; }
            .summary-card img { width: 60px; height: 60px; }
            
            .btn-clear span { display: none; }
            .btn-clear { padding: 12px; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-left: 12px; flex-shrink: 0; }
            .date-inputs { flex-direction: column; align-items: stretch; }
            .btn-apply { width: 100%; margin-top: 8px; }
            .filters { padding-bottom: 12px; margin-left: -20px; margin-right: -20px; padding-left: 20px; padding-right: 20px; } /* Bleed edge for horizontal scroll */
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
                    <div class="stat-label">Pesanan Selesai</div>
                    <div class="stat-val"><?= $total_sold ?> Pesanan</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total Uang Masuk</div>
                    <div class="stat-val">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <div class="filters">
            <a href="?filter=semua" class="filter-chip <?= $filter=='semua'?'active':'' ?>">Semua</a>
            <a href="?filter=hari_ini" class="filter-chip <?= $filter=='hari_ini'?'active':'' ?>">Hari Ini</a>
            <a href="?filter=minggu_ini" class="filter-chip <?= $filter=='minggu_ini'?'active':'' ?>">Minggu Ini</a>
            <a href="?filter=bulan_ini" class="filter-chip <?= $filter=='bulan_ini'?'active':'' ?>">Bulan Ini</a>
            <button class="filter-chip <?= $filter=='rentang'?'active':'' ?>" onclick="document.getElementById('custom-date').classList.toggle('show')">Pilih Tanggal</button>
        </div>

        <form id="custom-date" class="custom-date <?= $filter=='rentang'?'show':'' ?>" method="GET">
            <input type="hidden" name="filter" value="rentang">
            <div class="date-inputs">
                <div class="date-field">
                    <label>Dari Tanggal</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                </div>
                <div class="date-field">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                </div>
                <button type="submit" class="btn-apply">Terapkan</button>
            </div>
        </form>

        <?php if (!empty($item_summary)): ?>
            <div class="summary-section">
                <h2 style="font-size:20px; color:var(--cream); margin-bottom:16px;">Rekap Menu Terjual</h2>
                <div class="summary-grid">
                    <?php foreach($item_summary as $name => $data): ?>
                        <div class="summary-card">
                            <img src="<?= $data['gambar_url'] ?: 'assets/default_menu.png' ?>" onerror="this.src='assets/default_menu.png'">
                            <div class="sc-info">
                                <div class="sc-name"><?= htmlspecialchars($name) ?></div>
                                <div class="sc-qty"><?= $data['jumlah'] ?> Porsi</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($grouped_orders)): ?>
            <div class="controls">
                <span style="font-size:16px; color:var(--text-dim); font-weight:700;">
                    <?php
                        if ($filter == 'hari_ini') echo "Menampilkan riwayat hari ini";
                        elseif ($filter == 'minggu_ini') echo "Menampilkan riwayat minggu ini";
                        elseif ($filter == 'bulan_ini') echo "Menampilkan riwayat bulan ini";
                        elseif ($filter == 'rentang') echo "Menampilkan riwayat dari " . date('d M Y', strtotime($start_date)) . " - " . date('d M Y', strtotime($end_date));
                        else echo "Menampilkan semua riwayat";
                    ?>
                </span>
                <form method="POST" onsubmit="return confirm('⚠️ Hapus SEMUA riwayat pesanan? Tindakan ini tidak bisa dibatalkan.')">
                    <input type="hidden" name="action" value="clear_all">
                    <button type="submit" class="btn-clear" title="Hapus Seluruh Riwayat">🗑 <span>Hapus Seluruh Riwayat</span></button>
                </form>
            </div>

            <?php foreach ($grouped_orders as $o): ?>
                <div class="order-card <?= $o['status'] === 'dibatalkan' ? 'cancelled' : '' ?>">
                    <div class="order-info">
                        <div class="o-meta" style="margin-bottom:16px; padding-bottom:12px; border-bottom:1px dashed var(--border);"><?= date('d M Y', strtotime($o['created_at'])) ?> · Jam <?= date('H:i', strtotime($o['created_at'])) ?> · #<?= $o['kode_pesanan'] ?></div>
                        
                        <div class="o-items-list">
                            <?php foreach($o['items'] as $item): ?>
                                <div class="o-item-row">
                                    <img class="o-item-img" src="<?= $item['gambar_url'] ?: 'assets/default_menu.png' ?>" onerror="this.onerror=null; this.src='assets/default_menu.png'">
                                    <div>
                                        <div class="o-item-name"><?= htmlspecialchars($item['nama_menu']) ?></div>
                                        <div class="o-item-meta"><?= $item['jumlah'] ?>x @ Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="o-price" style="margin-top:16px; color:var(--gold);">Total: Rp <?= number_format($o['total'], 0, ',', '.') ?></div>
                    </div>
                    <div class="order-status">
                        <div class="status-badge <?= $o['status'] ?>"><?= strtoupper($o['status']) ?></div>
                        <form method="POST" onsubmit="return confirm('Hapus pesanan ini beserta semua menunya?')">
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