<?php
// riwayat.php — Halaman Riwayat Pesanan WarungKu
require_once __DIR__ . '/config.php';

$db_error = null;
$ordersData = [];

try {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT 
            p.id as pid, p.kode_pesanan, p.created_at, p.subtotal as p_subtotal, p.pajak, p.total,
            d.menu_id, d.nama_menu, d.gambar_url, d.harga_satuan, d.jumlah
        FROM pesanan p
        LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
        ORDER BY p.id DESC
    ");
    $rows = $stmt->fetchAll();
    
    $ordersMap = [];
    foreach ($rows as $row) {
        $oid = $row['kode_pesanan'];
        if (!isset($ordersMap[$oid])) {
            $ordersMap[$oid] = [
                'orderId' => $oid,
                'timestamp' => str_replace(' ', 'T', $row['created_at']),
                'subtotal' => (int)$row['p_subtotal'],
                'tax' => (int)$row['pajak'],
                'total' => (int)$row['total'],
                'items' => []
            ];
        }
        if ($row['menu_id']) {
            $ordersMap[$oid]['items'][] = [
                'id' => (int)$row['menu_id'],
                'name' => $row['nama_menu'],
                'image' => $row['gambar_url'],
                'price' => (int)$row['harga_satuan'],
                'qty' => (int)$row['jumlah']
            ];
        }
    }
    $ordersData = array_values($ordersMap);
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

$ordersJson = json_encode($ordersData);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Pesanan — WarungKu</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0f0e0b;
    --surface: #1a1814;
    --surface2: #231f1a;
    --surface3: #2d2820;
    --gold: #d4a853;
    --gold-light: #e8c47a;
    --gold-dim: rgba(212,168,83,0.12);
    --cream: #f5edd8;
    --cream-dim: rgba(245,237,216,0.6);
    --red: #e05252;
    --green: #4caf7d;
    --blue: #5b9bd5;
    --text: #f0e8d5;
    --text-dim: #8a7f6e;
    --border: rgba(212,168,83,0.15);
    --radius: 14px;
  }

  [data-theme="light"] {
    --bg: #fdfaf6;
    --surface: #ffffff;
    --surface2: #f4efeb;
    --surface3: #e8e1d7;
    --gold: #b38222;
    --gold-light: #c2902f;
    --gold-dim: rgba(179, 130, 34, 0.15);
    --cream: #1a1814;
    --cream-dim: rgba(26, 24, 20, 0.7);
    --red: #d33c3c;
    --green: #2c8558;
    --text: #3c3730;
    --text-dim: #7a7265;
    --border: rgba(179, 130, 34, 0.25);
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
  }

  /* ── TOPBAR ── */
  .topbar {
    position: sticky; top: 0; z-index: 100;
    background: rgba(15,14,11,0.92);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--border);
    padding: 0 40px;
    height: 68px;
    display: flex; align-items: center; justify-content: space-between;
  }
  .brand {
    font-family: 'Playfair Display', serif;
    font-size: 24px; font-weight: 900;
    color: var(--gold);
    letter-spacing: -0.5px;
    text-decoration: none;
  }
  .brand span { color: var(--cream); }
  .nav-back {
    display: flex; align-items: center; gap: 8px;
    color: var(--text-dim);
    font-size: 13px; text-decoration: none;
    padding: 8px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    transition: all 0.2s;
  }
  .nav-back:hover { border-color: var(--gold); color: var(--gold); }

  /* ── CONTAINER ── */
  .container {
    max-width: 860px;
    margin: 0 auto;
    padding: 48px 24px 80px;
  }

  /* ── PAGE HEADER ── */
  .page-header {
    display: flex; align-items: flex-end; justify-content: space-between;
    margin-bottom: 36px;
    flex-wrap: wrap; gap: 16px;
  }
  .page-title {
    font-family: 'Playfair Display', serif;
    font-size: 36px; font-weight: 700;
    color: var(--cream);
    line-height: 1;
  }
  .page-subtitle {
    color: var(--text-dim);
    font-size: 14px;
    margin-top: 8px;
  }
  .stats-bar {
    display: flex; gap: 24px;
  }
  .stat {
    text-align: right;
  }
  .stat-val {
    font-family: 'Playfair Display', serif;
    font-size: 22px; font-weight: 700;
    color: var(--gold);
  }
  .stat-label {
    font-size: 11px; color: var(--text-dim);
    text-transform: uppercase; letter-spacing: 1px;
  }

  /* ── FILTER ROW ── */
  .filter-row {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap; gap: 12px;
  }
  .filter-tabs {
    display: flex; gap: 8px;
  }
  .ftab {
    padding: 7px 18px;
    border-radius: 100px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-dim);
    font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 500;
    cursor: pointer; transition: all 0.2s;
  }
  .ftab:hover { border-color: var(--gold); color: var(--gold); }
  .ftab.active { background: var(--gold); color: var(--bg); border-color: var(--gold); font-weight: 600; }

  .clear-all-btn {
    padding: 7px 16px;
    border-radius: 8px;
    border: 1px solid rgba(224,82,82,0.3);
    background: transparent;
    color: var(--red);
    font-family: 'DM Sans', sans-serif;
    font-size: 12px; cursor: pointer;
    transition: all 0.2s;
  }
  .clear-all-btn:hover { background: var(--red); color: #fff; border-color: var(--red); }

  /* ── ORDER CARDS ── */
  .orders-list {
    display: flex; flex-direction: column; gap: 12px;
  }

  .order-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: border-color 0.2s;
  }
  .order-card:hover { border-color: rgba(212,168,83,0.35); }
  .order-card.expanded { border-color: var(--gold); }

  /* ── CARD HEADER (clickable) ── */
  .card-head {
    display: flex; align-items: center; gap: 16px;
    padding: 20px 24px;
    cursor: pointer;
    user-select: none;
    transition: background 0.15s;
  }
  .card-head:hover { background: rgba(212,168,83,0.04); }

  .order-num {
    font-size: 11px; font-weight: 700;
    color: var(--gold); letter-spacing: 1px;
    background: var(--gold-dim);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 4px 10px;
    white-space: nowrap;
  }

  .head-info { flex: 1; min-width: 0; }
  .head-date {
    font-size: 15px; font-weight: 600;
    color: var(--cream);
    margin-bottom: 3px;
  }
  .head-meta {
    font-size: 12px; color: var(--text-dim);
    display: flex; align-items: center; gap: 12px;
    flex-wrap: wrap;
  }
  .meta-dot { color: var(--border); }

  /* Thumbnail strip */
  .thumb-strip {
    display: flex; gap: -6px;
    margin: 0 8px;
  }
  .thumb-img {
    width: 36px; height: 36px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid var(--bg);
    margin-left: -8px;
    transition: transform 0.2s;
  }
  .thumb-strip:hover .thumb-img { transform: translateX(4px); }
  .thumb-img:first-child { margin-left: 0; }
  .thumb-more {
    width: 36px; height: 36px;
    border-radius: 8px;
    background: var(--surface3);
    border: 2px solid var(--bg);
    margin-left: -8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 700;
    color: var(--text-dim);
  }

  .head-total {
    text-align: right;
    flex-shrink: 0;
  }
  .total-val {
    font-size: 17px; font-weight: 800;
    color: var(--gold);
  }
  .total-label {
    font-size: 10px; color: var(--text-dim);
    text-transform: uppercase; letter-spacing: 0.8px;
  }

  .chevron {
    color: var(--text-dim);
    font-size: 14px;
    transition: transform 0.3s;
    flex-shrink: 0;
  }
  .order-card.expanded .chevron { transform: rotate(180deg); }

  /* ── CARD BODY (collapsible) ── */
  .card-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border-top: 0px solid var(--border);
  }
  .order-card.expanded .card-body {
    max-height: 1000px;
    border-top: 1px solid var(--border);
  }

  .card-body-inner { padding: 24px; }

  /* Item rows */
  .item-row {
    display: flex; align-items: center; gap: 16px;
    padding: 14px 0;
    border-bottom: 1px solid rgba(212,168,83,0.07);
  }
  .item-row:last-child { border-bottom: none; }

  .item-img {
    width: 60px; height: 60px;
    border-radius: 10px; object-fit: cover;
    flex-shrink: 0;
    border: 1px solid var(--border);
  }
  .item-info { flex: 1; min-width: 0; }
  .item-name {
    font-size: 15px; font-weight: 600;
    color: var(--cream);
    margin-bottom: 4px;
  }
  .item-unit {
    font-size: 12px; color: var(--text-dim);
  }
  .item-qty {
    text-align: center;
    min-width: 60px;
  }
  .qty-badge {
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--surface3);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 4px 12px;
    font-size: 13px; font-weight: 700;
    color: var(--cream);
  }
  .item-sub {
    font-size: 15px; font-weight: 700;
    color: var(--gold);
    text-align: right;
    min-width: 100px;
  }

  /* Summary */
  .receipt-summary {
    margin-top: 20px;
    padding: 16px 20px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
  }
  .sum-row {
    display: flex; justify-content: space-between;
    font-size: 13px; color: var(--text-dim);
    margin-bottom: 8px;
  }
  .sum-row:last-child { margin-bottom: 0; }
  .sum-row.grand {
    font-size: 16px; font-weight: 800;
    color: var(--cream);
    padding-top: 12px; margin-top: 8px;
    border-top: 1px solid var(--border);
  }
  .sum-row.grand span:last-child { color: var(--gold); }

  /* Print / action row */
  .card-actions {
    display: flex; gap: 10px;
    padding: 0 24px 20px;
  }
  .action-btn {
    padding: 9px 18px;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 600;
    cursor: pointer; transition: all 0.2s;
  }
  .btn-print {
    background: var(--gold-dim);
    border: 1px solid var(--border);
    color: var(--gold);
  }
  .btn-print:hover { background: var(--gold); color: var(--bg); }
  .btn-delete {
    background: transparent;
    border: 1px solid rgba(224,82,82,0.3);
    color: var(--red);
  }
  .btn-delete:hover { background: var(--red); color: #fff; border-color: var(--red); }

  /* ── EMPTY STATE ── */
  .empty-state {
    text-align: center;
    padding: 80px 32px;
    color: var(--text-dim);
  }
  .empty-icon { font-size: 56px; margin-bottom: 20px; opacity: 0.4; }
  .empty-title {
    font-family: 'Playfair Display', serif;
    font-size: 24px; color: var(--cream);
    margin-bottom: 10px;
  }
  .empty-sub { font-size: 14px; line-height: 1.7; margin-bottom: 28px; }
  .goto-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--bg); border: none;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px; font-weight: 700;
    text-decoration: none; cursor: pointer;
    transition: all 0.2s;
  }
  .goto-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(212,168,83,0.35); }

  /* ── PRINT AREA ── */
  @media print {
    .topbar, .filter-row, .card-actions, .stats-bar, .nav-back { display: none !important; }
    .order-card { border: 1px solid #ccc !important; page-break-inside: avoid; }
    .card-body { max-height: none !important; border-top: 1px solid #ccc !important; }
    body { background: #fff; color: #000; }
  }

  /* Scrollbar */
  ::-webkit-scrollbar { width: 4px; }
  ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

  /* Responsive */
  @media (max-width: 768px) {
    .container { padding: 24px 16px 80px; }
    .topbar { padding: 0 16px; height: 60px; }
    .brand { font-size: 20px; }
    .page-title { font-size: 26px; }
    .stats-bar { width: 100%; justify-content: space-between; margin-top: 16px; }
    .stat { text-align: left; }
    .card-head { flex-wrap: wrap; padding: 16px; }
    .head-info { flex: 1 1 100%; order: -1; margin-bottom: 8px; }
    .order-num { order: -2; margin-bottom: 8px; }
    .head-total { text-align: left; margin-left: auto; }
    .card-body-inner { padding: 16px; }
    .card-actions { padding: 0 16px 16px; flex-wrap: wrap; }
    .action-btn { flex: 1; text-align: center; }
    .item-row { flex-wrap: wrap; }
    .item-img { width: 48px; height: 48px; }
    .item-sub { text-align: left; width: 100%; margin-top: 8px; font-size: 14px; }
    .filter-tabs { overflow-x: auto; padding-bottom: 8px; flex: 1; }
    .filter-tabs::-webkit-scrollbar { height: 2px; }
  }
  ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
</style>
<script>
  const savedTheme = localStorage.getItem('warungku_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', savedTheme);
</script>
</head>
<body>

<?php if ($db_error): ?>
<div style="background:#e05252;color:#fff;padding:12px 32px;font-size:13px;font-weight:600;text-align:center;">
  ⚠️ Gagal memuat data dari database: <?= htmlspecialchars($db_error) ?>
</div>
<?php endif; ?>
<!-- ── TOPBAR ── -->
<header class="topbar">
  <a href="index.php" class="brand">Warung<span>Ku</span></a>
  <div style="display:flex;gap:12px;align-items:center;">
    <button onclick="toggleTheme()" id="themeToggleBtn" style="background:transparent;border:1px solid var(--border);border-radius:8px;color:var(--text-dim);cursor:pointer;padding:8px 10px;font-size:14px;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-dim)'">☀️</button>
    <a href="index.php" class="nav-back">← Kembali ke Menu</a>
  </div>
</header>

<!-- ── CONTAINER ── -->
<div class="container">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Riwayat Pesanan</h1>
      <p class="page-subtitle">Semua transaksi yang telah diproses</p>
    </div>
    <div class="stats-bar" id="statsBar" style="display:none">
      <div class="stat">
        <div class="stat-val" id="statTotal">0</div>
        <div class="stat-label">Total Transaksi</div>
      </div>
      <div class="stat">
        <div class="stat-val" id="statRevenue">Rp0</div>
        <div class="stat-label">Total Pendapatan</div>
      </div>
    </div>
  </div>

  <!-- Filter & Actions -->
  <div class="filter-row" id="filterRow" style="display:none">
    <div class="filter-tabs">
      <button class="ftab active" onclick="setFilter('semua',this)">Semua</button>
      <button class="ftab" onclick="setFilter('hari-ini',this)">Hari Ini</button>
      <button class="ftab" onclick="setFilter('minggu-ini',this)">Minggu Ini</button>
    </div>
    <button class="clear-all-btn" onclick="clearAllOrders()">🗑 Hapus Semua</button>
  </div>

  <!-- Orders List -->
  <div class="orders-list" id="ordersList"></div>

  <!-- Empty State (ditampilkan via JS) -->
  <div class="empty-state" id="emptyState" style="display:none">
    <div class="empty-icon">📋</div>
    <div class="empty-title">Belum Ada Riwayat</div>
    <div class="empty-sub">Riwayat pesanan akan muncul di sini<br>setelah kamu menyelesaikan checkout pertama.</div>
    <a href="index.php" class="goto-btn">🍽 Mulai Pesan Sekarang</a>
  </div>

</div>

<!-- ── PRINT MODAL ── -->
<div id="printOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;width:420px;max-height:90vh;overflow-y:auto;">
    <div id="printContent" style="padding:32px;font-family:'DM Sans',sans-serif;color:var(--text);">
      <!-- filled by JS -->
    </div>
    <div style="padding:0 24px 24px;display:flex;gap:10px;">
      <button onclick="window.print()" style="flex:1;padding:12px;background:linear-gradient(135deg,var(--gold),var(--gold-light));color:var(--bg);border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;">🖨 Print</button>
      <button onclick="document.getElementById('printOverlay').style.display='none'" style="padding:12px 20px;background:transparent;border:1px solid var(--border);border-radius:8px;color:var(--text-dim);cursor:pointer;font-size:13px;">Tutup</button>
    </div>
  </div>
</div>

<script>
// ── HELPERS ──
function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('warungku_theme', next);
  document.getElementById('themeToggleBtn').textContent = next === 'dark' ? '☀️' : '🌙';
}
document.getElementById('themeToggleBtn').textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';

function fmt(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID');
}

function fmtDate(iso) {
  const d = new Date(iso);
  const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
  const hm = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()} · ${hm}`;
}

function isToday(iso) {
  const d = new Date(iso), now = new Date();
  return d.toDateString() === now.toDateString();
}

function isThisWeek(iso) {
  const d = new Date(iso), now = new Date();
  const startOfWeek = new Date(now);
  startOfWeek.setDate(now.getDate() - now.getDay());
  startOfWeek.setHours(0,0,0,0);
  return d >= startOfWeek;
}

// ── LOAD ORDERS ──
const dbOrders = <?php echo $ordersJson; ?>;

function getOrders() {
  return dbOrders;
}

// ── CURRENT FILTER ──
let currentFilter = 'semua';

function setFilter(filter, btn) {
  currentFilter = filter;
  document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderOrders();
}

// ── TOGGLE EXPAND ──
function toggleCard(id) {
  const card = document.getElementById('oc-' + id);
  card.classList.toggle('expanded');
}

// ── DELETE ORDER ──
function deleteOrder(id, e) {
  e.stopPropagation();
  if (!confirm('Hapus pesanan ini dari riwayat?')) return;
  
  fetch('api_delete_pesanan.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'delete', kode_pesanan: id })
  })
  .then(res => res.json())
  .then(res => {
      if(res.error) alert(res.message);
      else location.reload();
  })
  .catch(err => alert('Terjadi kesalahan.'));
}

// ── CLEAR ALL ──
function clearAllOrders() {
  if (!confirm('Hapus SEMUA riwayat pesanan? Tindakan ini tidak bisa dibatalkan.')) return;
  
  fetch('api_delete_pesanan.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'clear' })
  })
  .then(res => res.json())
  .then(res => {
      if(res.error) alert(res.message);
      else location.reload();
  })
  .catch(err => alert('Terjadi kesalahan.'));
}

// ── PRINT ORDER ──
function printOrder(id, e) {
  e.stopPropagation();
  const orders = getOrders();
  const order = orders.find(o => o.orderId === id);
  if (!order) return;

  let rowsHTML = order.items.map(item => `
    <tr>
      <td style="padding:8px 0;font-size:13px;border-bottom:1px solid #333">${item.name}</td>
      <td style="padding:8px 0;text-align:center;font-size:13px;border-bottom:1px solid #333">${item.qty}x</td>
      <td style="padding:8px 0;text-align:right;font-size:13px;border-bottom:1px solid #333;color:var(--gold)">${fmt(item.price * item.qty)}</td>
    </tr>`).join('');

  document.getElementById('printContent').innerHTML = `
    <div style="text-align:center;margin-bottom:24px;">
      <div style="font-family:'Playfair Display',serif;font-size:28px;font-weight:900;color:var(--gold)">WarungKu</div>
      <div style="font-size:12px;color:var(--text-dim);margin-top:4px">${fmtDate(order.timestamp)}</div>
      <div style="margin-top:10px;padding:5px 14px;background:var(--gold-dim);border:1px solid var(--border);border-radius:100px;display:inline-block;font-size:11px;font-weight:700;color:var(--gold);letter-spacing:1px">#${order.orderId}</div>
    </div>
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="border-bottom:1px solid var(--border)">
          <th style="text-align:left;font-size:10px;color:var(--text-dim);padding-bottom:8px;letter-spacing:1px;text-transform:uppercase">Item</th>
          <th style="text-align:center;font-size:10px;color:var(--text-dim);padding-bottom:8px;letter-spacing:1px;text-transform:uppercase">Qty</th>
          <th style="text-align:right;font-size:10px;color:var(--text-dim);padding-bottom:8px;letter-spacing:1px;text-transform:uppercase">Harga</th>
        </tr>
      </thead>
      <tbody>${rowsHTML}</tbody>
    </table>
    <div style="margin-top:16px;padding:14px;background:var(--surface2);border-radius:8px;border:1px solid var(--border)">
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-dim);margin-bottom:6px"><span>Subtotal</span><span>${fmt(order.subtotal)}</span></div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-dim);margin-bottom:10px"><span>Pajak (10%)</span><span>${fmt(order.tax)}</span></div>
      <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;color:var(--cream);padding-top:10px;border-top:1px solid var(--border)"><span>Total</span><span style="color:var(--gold)">${fmt(order.total)}</span></div>
    </div>
    <div style="text-align:center;margin-top:20px;font-size:11px;color:var(--text-dim)">Terima kasih telah memesan di WarungKu ✨</div>
  `;
  document.getElementById('printOverlay').style.display = 'flex';
}

// ── RENDER ──
function renderOrders() {
  const allOrders = getOrders();

  // Filter
  let orders = allOrders;
  if (currentFilter === 'hari-ini') orders = allOrders.filter(o => isToday(o.timestamp));
  if (currentFilter === 'minggu-ini') orders = allOrders.filter(o => isThisWeek(o.timestamp));

  const list = document.getElementById('ordersList');
  const emptyState = document.getElementById('emptyState');
  const filterRow = document.getElementById('filterRow');
  const statsBar = document.getElementById('statsBar');

  // Stats (always from all orders)
  const totalRevenue = allOrders.reduce((s, o) => s + o.total, 0);
  document.getElementById('statTotal').textContent = allOrders.length;
  document.getElementById('statRevenue').textContent = 'Rp ' + totalRevenue.toLocaleString('id-ID');

  if (allOrders.length === 0) {
    emptyState.style.display = 'block';
    filterRow.style.display = 'none';
    statsBar.style.display = 'none';
    list.innerHTML = '';
    return;
  }

  emptyState.style.display = 'none';
  filterRow.style.display = 'flex';
  statsBar.style.display = 'flex';

  if (orders.length === 0) {
    list.innerHTML = `<div style="text-align:center;padding:60px;color:var(--text-dim)">
      <div style="font-size:36px;margin-bottom:16px;opacity:.4">🔍</div>
      <div style="font-size:15px">Tidak ada pesanan di periode ini</div>
    </div>`;
    return;
  }

  list.innerHTML = orders.map((order, index) => {
    const thumbsHtml = buildThumbs(order.items);
    const itemCount = order.items.reduce((s, i) => s + i.qty, 0);
    const menuCount = order.items.length;

    return `
    <div class="order-card" id="oc-${order.orderId}">
      <div class="card-head" onclick="toggleCard('${order.orderId}')">
        <div class="order-num">#${order.orderId}</div>

        <div class="head-info">
          <div class="head-date">${fmtDate(order.timestamp)}</div>
          <div class="head-meta">
            <span>${menuCount} menu</span>
            <span class="meta-dot">·</span>
            <span>${itemCount} item</span>
          </div>
        </div>

        <div class="thumb-strip">${thumbsHtml}</div>

        <div class="head-total">
          <div class="total-val">${fmt(order.total)}</div>
          <div class="total-label">Total Bayar</div>
        </div>

        <span class="chevron">▼</span>
      </div>

      <!-- COLLAPSIBLE BODY -->
      <div class="card-body">
        <div class="card-body-inner">
          ${order.items.map(item => `
            <div class="item-row">
              <img class="item-img" src="${item.image}" alt="${item.name}"
                onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=120&h=120&fit=crop'">
              <div class="item-info">
                <div class="item-name">${item.name}</div>
                <div class="item-unit">${fmt(item.price)} / porsi</div>
              </div>
              <div class="item-qty">
                <span class="qty-badge">${item.qty}×</span>
              </div>
              <div class="item-sub">${fmt(item.price * item.qty)}</div>
            </div>
          `).join('')}

          <div class="receipt-summary">
            <div class="sum-row"><span>Subtotal</span><span>${fmt(order.subtotal)}</span></div>
            <div class="sum-row"><span>Pajak (10%)</span><span>${fmt(order.tax)}</span></div>
            <div class="sum-row grand"><span>Total Bayar</span><span>${fmt(order.total)}</span></div>
          </div>
        </div>

        <div class="card-actions">
          <button class="action-btn btn-print" onclick="printOrder('${order.orderId}', event)">🖨 Cetak Struk</button>
          <button class="action-btn btn-delete" onclick="deleteOrder('${order.orderId}', event)">🗑 Hapus</button>
        </div>
      </div>
    </div>`;
  }).join('');
}

function buildThumbs(items) {
  const max = 3;
  let html = '';
  const shown = items.slice(0, max);
  const rest = items.length - max;

  shown.forEach(item => {
    html += `<img class="thumb-img" src="${item.image}" alt="${item.name}"
      onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=80&h=80&fit=crop'">`;
  });

  if (rest > 0) {
    html += `<div class="thumb-more">+${rest}</div>`;
  }
  return html;
}

// ── INIT ──
renderOrders();

// Auto expand first card jika ada
setTimeout(() => {
  const firstCard = document.querySelector('.order-card');
  if (firstCard) firstCard.classList.add('expanded');
}, 100);

// Close print overlay on bg click
document.getElementById('printOverlay').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
