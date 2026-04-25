<?php
// ============================================================
// index.php — Halaman Pemesanan WarungKu (dengan Database)
// ============================================================
require_once __DIR__ . '/config.php';

// ── Ambil menu dari database ──
$db_error = null;
$menu_items = [];
$categories = [];

try {
    $pdo = getDB();

    // Join menu + kategori, hanya yang stok tersedia
    $rows = $pdo->query("
        SELECT
            m.id,
            m.nama        AS name,
            k.nama        AS category,
            m.harga       AS price,
            m.deskripsi   AS `desc`,
            m.gambar_url  AS image,
            m.stok
        FROM menu m
        JOIN kategori k ON k.id = m.kategori_id
        ORDER BY k.urutan, m.id
    ")->fetchAll();

    $menu_items = $rows;
    $categories = array_unique(array_column($rows, 'category'));

} catch (Exception $e) {
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WarungKu — Sistem Pemesanan</title>
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
    --text: #f0e8d5;
    --text-dim: #8a7f6e;
    --border: rgba(212,168,83,0.15);
    --radius: 14px;
    --sidebar-w: 340px;
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

  html, body {
    overflow-x: hidden;
    width: 100%;
  }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
  }

  /* ── TOP NAV ── */
  .topbar {
    position: sticky; top: 0; z-index: 100;
    background: rgba(15,14,11,0.92);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 68px;
    display: flex; align-items: center; justify-content: space-between;
  }
  .brand {
    font-family: 'Playfair Display', serif;
    font-size: 24px; font-weight: 900;
    color: var(--gold);
    letter-spacing: -0.5px;
  }
  .brand span { color: var(--cream); }
  .topbar-right {
    display: flex; align-items: center; gap: 18px;
  }
  .cart-badge {
    background: var(--gold); color: var(--bg);
    font-size: 11px; font-weight: 700;
    border-radius: 50%; width: 20px; height: 20px;
    display: flex; align-items: center; justify-content: center;
    position: absolute; top: -6px; right: -6px;
  }

  /* ── LAYOUT ── */
  .layout {
    display: flex;
    min-height: calc(100vh - 68px);
  }

  /* ── MAIN CONTENT ── */
  .main {
    flex: 1;
    padding: 36px 36px 36px 36px;
    overflow-y: auto;
    max-height: calc(100vh - 68px);
  }

  .page-title {
    font-family: 'Playfair Display', serif;
    font-size: 32px; font-weight: 700;
    color: var(--cream);
    margin-bottom: 6px;
  }
  .page-subtitle {
    color: var(--text-dim);
    font-size: 14px;
    margin-bottom: 28px;
  }

  /* ── FILTER TABS ── */
  .filter-bar {
    display: flex; gap: 8px; flex-wrap: wrap;
    margin-bottom: 32px;
  }
  .filter-btn {
    padding: 8px 20px;
    border-radius: 100px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-dim);
    font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }
  .filter-btn:hover { border-color: var(--gold); color: var(--gold); }
  .filter-btn.active {
    background: var(--gold); color: var(--bg);
    border-color: var(--gold); font-weight: 600;
  }

  /* ── MENU GRID ── */
  .menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
  }

  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
    position: relative;
  }
  .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px var(--gold);
    border-color: var(--gold);
  }
  .card-img {
    width: 100%; height: 170px;
    object-fit: cover;
    display: block;
    filter: brightness(0.88) saturate(1.1);
    transition: filter 0.3s;
  }
  .card:hover .card-img { filter: brightness(1) saturate(1.2); }

  .card-cat {
    position: absolute; top: 12px; left: 12px;
    background: rgba(15,14,11,0.82);
    backdrop-filter: blur(8px);
    color: var(--gold);
    font-size: 10px; font-weight: 600;
    letter-spacing: 1px; text-transform: uppercase;
    padding: 4px 10px; border-radius: 100px;
    border: 1px solid var(--border);
  }

  .card-body { padding: 16px; }
  .card-name {
    font-family: 'Playfair Display', serif;
    font-size: 17px; font-weight: 700;
    color: var(--cream);
    margin-bottom: 4px;
    line-height: 1.3;
  }
  .card-desc {
    font-size: 12px; color: var(--text-dim);
    margin-bottom: 14px; line-height: 1.5;
  }
  .card-footer {
    display: flex; align-items: center; justify-content: space-between;
  }
  .card-price {
    font-size: 18px; font-weight: 700;
    color: var(--gold);
    font-variant-numeric: tabular-nums;
  }
  .card-price small {
    font-size: 11px; color: var(--text-dim);
    font-weight: 400; display: block; margin-bottom: 1px;
  }

  /* ── QTY CONTROL ── */
  .qty-ctrl {
    display: flex; align-items: center; gap: 10px;
  }
  .qty-btn {
    width: 30px; height: 30px;
    border-radius: 50%;
    border: 1.5px solid var(--border);
    background: var(--surface2);
    color: var(--text);
    font-size: 16px; font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
    line-height: 1;
  }
  .qty-btn:hover { background: var(--gold); border-color: var(--gold); color: var(--bg); }
  .qty-btn.add-btn { border-color: var(--gold); color: var(--gold); }
  .qty-btn.add-btn:hover { background: var(--gold); color: var(--bg); }
  .qty-num {
    font-size: 15px; font-weight: 700;
    color: var(--cream);
    min-width: 18px; text-align: center;
  }

  /* ── IN-CART INDICATOR ── */
  .card.in-cart { border-color: rgba(212,168,83,0.4); }
  .in-cart-bar {
    position: absolute; top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    opacity: 0;
    transition: opacity 0.2s;
  }
  .card.in-cart .in-cart-bar { opacity: 1; }

  /* ── SIDEBAR ── */
  .sidebar {
    width: var(--sidebar-w);
    min-width: var(--sidebar-w);
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex; flex-direction: column;
    max-height: calc(100vh - 68px);
    position: sticky; top: 68px;
  }

  .sidebar-header {
    padding: 24px 24px 16px;
    border-bottom: 1px solid var(--border);
  }
  .sidebar-title {
    font-family: 'Playfair Display', serif;
    font-size: 20px; font-weight: 700;
    color: var(--cream);
    display: flex; align-items: center; gap: 10px;
  }
  .sidebar-title .icon {
    font-size: 18px;
  }
  .item-count {
    font-family: 'DM Sans', sans-serif;
    font-size: 12px; color: var(--text-dim);
    margin-top: 4px;
  }

  /* ── ORDER LIST ── */
  .order-list {
    flex: 1; overflow-y: auto;
    padding: 16px;
    display: flex; flex-direction: column; gap: 10px;
  }
  .order-list::-webkit-scrollbar { width: 4px; }
  .order-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

  .order-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
    animation: slideIn 0.25s ease;
  }
  @keyframes slideIn {
    from { opacity: 0; transform: translateX(12px); }
    to { opacity: 1; transform: translateX(0); }
  }
  .order-img {
    width: 52px; height: 52px;
    border-radius: 8px; object-fit: cover;
    flex-shrink: 0;
  }
  .order-info { flex: 1; min-width: 0; }
  .order-name {
    font-size: 13px; font-weight: 600;
    color: var(--cream); margin-bottom: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .order-unit-price {
    font-size: 11px; color: var(--text-dim);
  }
  .order-subtotal {
    font-size: 13px; font-weight: 700;
    color: var(--gold); white-space: nowrap;
  }
  .order-qty-ctrl {
    display: flex; align-items: center; gap: 6px;
  }
  .order-qty-btn {
    width: 24px; height: 24px;
    border-radius: 50%;
    border: 1px solid var(--border);
    background: var(--surface3);
    color: var(--text-dim);
    font-size: 14px; font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
    line-height: 1;
  }
  .order-qty-btn:hover { background: var(--gold); border-color: var(--gold); color: var(--bg); }
  .order-qty-num {
    font-size: 13px; font-weight: 700;
    color: var(--cream);
    min-width: 16px; text-align: center;
  }
  .del-btn {
    width: 24px; height: 24px;
    border-radius: 50%;
    border: 1px solid rgba(224,82,82,0.3);
    background: rgba(224,82,82,0.08);
    color: var(--red);
    font-size: 11px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
    flex-shrink: 0;
  }
  .del-btn:hover { background: var(--red); color: #fff; }

  /* ── EMPTY CART ── */
  .empty-cart {
    flex: 1;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 32px;
    text-align: center;
    color: var(--text-dim);
  }
  .empty-icon {
    font-size: 48px; margin-bottom: 16px;
    opacity: 0.4;
  }
  .empty-text { font-size: 14px; line-height: 1.6; }

  /* ── SIDEBAR FOOTER ── */
  .sidebar-footer {
    padding: 20px 24px;
    border-top: 1px solid var(--border);
  }
  .summary-row {
    display: flex; justify-content: space-between;
    font-size: 13px; color: var(--text-dim);
    margin-bottom: 6px;
  }
  .summary-row.total {
    font-size: 17px; font-weight: 700;
    color: var(--cream); margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
  }
  .summary-row.total span:last-child { color: var(--gold); }

  .checkout-btn {
    width: 100%; margin-top: 16px;
    padding: 15px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--bg);
    border: none; border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px; font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    letter-spacing: 0.3px;
    display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  .checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(212,168,83,0.4);
  }
  .checkout-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
  }
  .clear-btn {
    width: 100%; margin-top: 8px;
    padding: 10px;
    background: transparent;
    color: var(--text-dim);
    border: 1px solid var(--border);
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px; cursor: pointer;
    transition: all 0.2s;
  }
  .clear-btn:hover { border-color: var(--red); color: var(--red); }

  /* ── CHECKOUT MODAL OVERLAY ── */
  .modal-overlay {
    position: fixed; inset: 0; z-index: 200;
    background: rgba(0,0,0,0.75);
    backdrop-filter: blur(6px);
    display: flex; align-items: center; justify-content: center;
    padding: 24px;
    animation: fadeIn 0.2s ease;
  }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

  .modal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    width: 100%; max-width: 520px;
    max-height: 90vh;
    overflow-y: auto;
    animation: popIn 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  }
  @keyframes popIn {
    from { opacity: 0; transform: scale(0.92); }
    to { opacity: 1; transform: scale(1); }
  }

  .modal-header {
    padding: 28px 28px 0;
    text-align: center;
  }
  .receipt-icon {
    font-size: 40px; margin-bottom: 12px;
  }
  .modal-title {
    font-family: 'Playfair Display', serif;
    font-size: 26px; font-weight: 700;
    color: var(--cream);
    margin-bottom: 4px;
  }
  .modal-subtitle {
    font-size: 13px; color: var(--text-dim);
  }
  .order-id {
    display: inline-block;
    margin-top: 12px;
    padding: 6px 16px;
    background: var(--gold-dim);
    border: 1px solid var(--border);
    border-radius: 100px;
    font-size: 12px; font-weight: 600;
    color: var(--gold);
    letter-spacing: 1px;
  }

  .modal-body { padding: 24px 28px; }

  .receipt-table {
    width: 100%;
    border-collapse: collapse;
  }
  .receipt-table th {
    font-size: 10px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 1px;
    color: var(--text-dim);
    padding: 0 0 12px;
    text-align: left;
    border-bottom: 1px solid var(--border);
  }
  .receipt-table th:last-child { text-align: right; }
  .receipt-table td {
    padding: 14px 0;
    border-bottom: 1px solid rgba(212,168,83,0.08);
    vertical-align: middle;
  }
  .receipt-table tr:last-child td { border-bottom: none; }

  .r-item-name {
    font-size: 14px; font-weight: 600;
    color: var(--cream); display: block;
  }
  .r-item-meta {
    font-size: 11px; color: var(--text-dim);
  }
  .r-qty {
    font-size: 13px; color: var(--text-dim);
    text-align: center;
  }
  .r-price {
    font-size: 14px; font-weight: 700;
    color: var(--gold); text-align: right;
  }

  .receipt-divider {
    border: none;
    border-top: 1px dashed var(--border);
    margin: 8px 0;
  }
  .receipt-summary {
    padding: 16px 0 0;
  }
  .r-sum-row {
    display: flex; justify-content: space-between;
    font-size: 13px; color: var(--text-dim);
    margin-bottom: 8px;
  }
  .r-sum-row.grand {
    font-size: 19px; font-weight: 800;
    color: var(--cream);
    padding-top: 14px;
    margin-top: 8px;
    border-top: 1px solid var(--border);
  }
  .r-sum-row.grand span:last-child { color: var(--gold); }

  .modal-footer { padding: 0 28px 28px; }

  .modal-close-btn {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--bg);
    border: none; border-radius: 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px; font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 8px;
  }
  .modal-close-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(212,168,83,0.4);
  }
  .modal-back-btn {
    width: 100%;
    padding: 12px;
    background: transparent;
    color: var(--text-dim);
    border: 1px solid var(--border);
    border-radius: 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px; cursor: pointer;
    transition: all 0.2s;
  }
  .modal-back-btn:hover { border-color: var(--gold); color: var(--gold); }

  .status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px;
    background: rgba(76,175,125,0.12);
    border: 1px solid rgba(76,175,125,0.3);
    border-radius: 100px;
    color: var(--green);
    font-size: 12px; font-weight: 600;
    margin-top: 10px;
  }

  /* ── SCROLLBAR ── */
  .main::-webkit-scrollbar { width: 4px; }
  .main::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

  .cart-overlay {
    display: none;
    position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 150;
    backdrop-filter: blur(4px);
    opacity: 0; transition: opacity 0.3s;
  }
  .cart-overlay.show { opacity: 1; }

  .hide-mobile { display: inline; }

  .close-cart-btn {
    display: none;
    background: transparent; border: none; color: var(--text-dim);
    font-size: 24px; cursor: pointer; padding: 4px; line-height: 1;
  }

  /* Floating Cart Toggle */
  .floating-cart-toggle {
    display: none;
    position: fixed;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    background: var(--surface);
    color: var(--gold);
    border: 1px solid var(--border);
    border-right: none;
    border-radius: 16px 0 0 16px;
    padding: 16px 10px;
    cursor: pointer;
    z-index: 140;
    box-shadow: -4px 0 20px rgba(0,0,0,0.5);
    transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: center;
  }
  .floating-cart-toggle:hover { background: var(--surface2); }
  .floating-cart-toggle .arrow { font-size: 18px; font-weight: bold; margin-bottom: 6px; display: block; }
  .floating-cart-toggle .icon { font-size: 24px; display: block; }
  .floating-cart-toggle .badge {
    background: var(--gold); color: var(--bg);
    font-size: 11px; font-weight: 700;
    border-radius: 50%; width: 20px; height: 20px;
    display: flex; align-items: center; justify-content: center;
    position: absolute; top: 6px; left: 6px;
  }

  /* Responsive */
  @media (max-width: 900px) {
    :root { --sidebar-w: 300px; }
    .layout { display: block; }
    .main { padding: 24px 16px; max-height: none; overflow-y: visible; }
    .menu-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; }
    .sidebar {
      position: fixed; top: 0; right: 0; bottom: 0; height: 100vh;
      max-height: 100vh; z-index: 160;
      transform: translateX(100%);
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: -10px 0 30px rgba(0,0,0,0.5);
    }
    .sidebar.show { transform: translateX(0); }
    .cart-overlay { display: block; pointer-events: none; }
    .cart-overlay.show { pointer-events: auto; }
    .sidebar-header { display: flex; align-items: flex-start; justify-content: space-between; }
    .close-cart-btn { display: block; }
    .floating-cart-toggle { display: block; }
    .sidebar.show ~ .floating-cart-toggle { right: -80px; } /* hide when open */
    .hide-mobile { display: none !important; }
    .topbar { padding: 0 16px; }
    .brand { font-size: 20px; }
    .page-title { font-size: 24px; }
    .modal { margin: 16px; max-height: 80vh; }
    .modal-body { padding: 16px; }
    .modal-footer { padding: 0 16px 16px; }
    .receipt-table th, .receipt-table td { padding: 10px 0; }
  }
  }
</style>
<script>
  const savedTheme = localStorage.getItem('warungku_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', savedTheme);
</script>
</head>
<body>

<?php if ($db_error): ?>
<div style="background:#e05252;color:#fff;padding:12px 32px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;position:sticky;top:0;z-index:200;">
  ⚠️ Koneksi database gagal: <?= htmlspecialchars($db_error) ?> —
  <span style="font-weight:400;">Pastikan MySQL aktif & config.php sudah disesuaikan.</span>
</div>
<?php endif; ?>

<!-- ═══════════════ TOPBAR ═══════════════ -->
<header class="topbar">
  <div class="brand">Warung<span>Ku</span></div>
  <div class="topbar-right">
    <a href="addmenu.php" style="color:var(--text-dim);font-size:13px;text-decoration:none;display:flex;align-items:center;gap:6px;padding:8px 14px;border:1px solid var(--border);border-radius:8px;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-dim)'">
      ➕ <span>Tambah Menu</span>
    </a>
    <a href="riwayat.php" style="color:var(--text-dim);font-size:13px;text-decoration:none;display:flex;align-items:center;gap:6px;padding:8px 14px;border:1px solid var(--border);border-radius:8px;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-dim)'">
      📋 <span>Riwayat</span>
    </a>
    <button onclick="toggleTheme()" id="themeToggleBtn" style="background:transparent;border:1px solid var(--border);border-radius:8px;color:var(--text-dim);cursor:pointer;padding:8px 10px;font-size:14px;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-dim)'">☀️</button>
    <span style="color:var(--text-dim);font-size:13px;" class="hide-mobile">Kasir: <strong style="color:var(--cream)">Admin</strong></span>
    <div style="position:relative;cursor:pointer;" onclick="toggleMobileCart()">
      <span style="font-size:22px;">🛒</span>
      <span class="cart-badge" id="topCartBadge">0</span>
    </div>
  </div>
</header>

<!-- ═══════════════ MOBILE CART OVERLAY ═══════════════ -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleMobileCart()"></div>

<!-- ═══════════════ FLOATING CART TOGGLE ═══════════════ -->
<button class="floating-cart-toggle" onclick="toggleMobileCart()">
  <span class="badge" id="floatCartBadge">0</span>
  <span class="arrow">←</span>
  <span class="icon">🛒</span>
</button>

<!-- ═══════════════ LAYOUT ═══════════════ -->
<div class="layout">

  <!-- ── MAIN ── -->
  <main class="main">
    <h1 class="page-title">Menu Hari Ini</h1>
    <p class="page-subtitle">Pilih menu favorit pelanggan & tambahkan ke keranjang</p>

    <!-- Filter -->
    <div class="filter-bar">
      <button class="filter-btn active" onclick="filterMenu('Semua', this)">Semua</button>
      <?php foreach ($categories as $cat): ?>
        <button class="filter-btn" onclick="filterMenu('<?= htmlspecialchars($cat) ?>', this)"><?= htmlspecialchars($cat) ?></button>
      <?php endforeach; ?>
    </div>

    <!-- Menu Grid -->
    <div class="menu-grid" id="menuGrid">
      <?php foreach ($menu_items as $item): ?>
      <div class="card" id="card-<?= $item['id'] ?>" data-cat="<?= htmlspecialchars($item['category']) ?>">
        <div class="in-cart-bar"></div>
        <span class="card-cat"><?= htmlspecialchars($item['category']) ?></span>
        <img class="card-img" src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=300&fit=crop'">
        <div class="card-body">
          <div class="card-name"><?= htmlspecialchars($item['name']) ?></div>
          <div class="card-desc"><?= htmlspecialchars($item['desc']) ?></div>
          <div class="card-footer">
            <div class="card-price">
              <small>Harga</small>
              Rp <?= number_format($item['price'], 0, ',', '.') ?>
            </div>
            <div class="qty-ctrl">
              <button class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, -1)" id="btn-minus-<?= $item['id'] ?>" style="display:none">−</button>
              <span class="qty-num" id="qty-<?= $item['id'] ?>">0</span>
              <button class="qty-btn add-btn" onclick="changeQty(<?= $item['id'] ?>, 1)" id="btn-add-<?= $item['id'] ?>">+</button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </main>

  <!-- ── SIDEBAR ── -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <div>
        <div class="sidebar-title">
          <span class="icon">🧾</span> Pesanan
        </div>
        <div class="item-count" id="itemCount">Belum ada item dipilih</div>
      </div>
      <button class="close-cart-btn" onclick="toggleMobileCart()">→</button>
    </div>

    <div id="orderListWrap" style="flex:1;display:flex;flex-direction:column;overflow:hidden;">
      <!-- Empty state -->
      <div class="empty-cart" id="emptyCart">
        <div class="empty-icon">🍽️</div>
        <div class="empty-text">Keranjang masih kosong.<br>Pilih menu untuk memulai.</div>
      </div>
      <!-- Order list -->
      <div class="order-list" id="orderList" style="display:none;"></div>
    </div>

    <div class="sidebar-footer">
      <div class="summary-row"><span>Subtotal</span><span id="subtotalVal">Rp 0</span></div>
      <div class="summary-row"><span>Pajak (10%)</span><span id="taxVal">Rp 0</span></div>
      <div class="summary-row total"><span>Total</span><span id="totalVal">Rp 0</span></div>
      <button class="checkout-btn" id="checkoutBtn" onclick="doCheckout()" disabled>
        🛒 Checkout Sekarang
      </button>
      <button class="clear-btn" onclick="clearCart()">Kosongkan Pesanan</button>
    </div>
  </aside>

</div>

<!-- ═══════════════ CHECKOUT MODAL ═══════════════ -->
<div class="modal-overlay" id="checkoutModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <div class="receipt-icon">🎉</div>
      <div class="modal-title">Pesanan Berhasil!</div>
      <div class="modal-subtitle">Detail pesanan pelanggan</div>
      <div class="order-id" id="orderId"></div><br>
      <span class="status-badge">✓ Pesanan Diterima</span>
    </div>

    <div class="modal-body">
      <table class="receipt-table">
        <thead>
          <tr>
            <th style="width:50%">Item</th>
            <th style="text-align:center">Qty</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody id="receiptBody"></tbody>
      </table>
      <hr class="receipt-divider">
      <div class="receipt-summary">
        <div class="r-sum-row"><span>Subtotal</span><span id="rSubtotal"></span></div>
        <div class="r-sum-row"><span>Pajak (10%)</span><span id="rTax"></span></div>
        <div class="r-sum-row"><span>Biaya Layanan</span><span>Rp 0</span></div>
        <div class="r-sum-row grand"><span>Total Bayar</span><span id="rTotal"></span></div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="modal-close-btn" onclick="confirmCheckout()">✓ Konfirmasi & Selesai</button>
      <button class="modal-back-btn" onclick="closeModal()">← Kembali Edit Pesanan</button>
    </div>
  </div>
</div>

<!-- ═══════════════ JAVASCRIPT ═══════════════ -->
<script>
// ── DATA ──
const menuData = <?= json_encode($menu_items) ?>;
let cart = {}; // { id: quantity }

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('warungku_theme', next);
  document.getElementById('themeToggleBtn').textContent = next === 'dark' ? '☀️' : '🌙';
}

// Set initial icon
document.getElementById('themeToggleBtn').textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';

function toggleMobileCart() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('cartOverlay');
  
  if (window.innerWidth <= 900) {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
    
    if (sidebar.classList.contains('show')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
  }
}

function fmt(n) {
  return 'Rp ' + n.toLocaleString('id-ID');
}

// ── QTY CHANGE ──
function changeQty(id, delta) {
  if (!cart[id]) cart[id] = 0;
  cart[id] = Math.max(0, cart[id] + delta);
  if (cart[id] === 0) delete cart[id];
  updateCard(id);
  renderCart();
}

function updateCard(id) {
  const qty = cart[id] || 0;
  const card = document.getElementById('card-' + id);
  const qtyEl = document.getElementById('qty-' + id);
  const minusBtn = document.getElementById('btn-minus-' + id);
  const addBtn = document.getElementById('btn-add-' + id);

  qtyEl.textContent = qty;
  minusBtn.style.display = qty > 0 ? 'flex' : 'none';
  qtyEl.style.display = qty > 0 ? 'block' : 'block';

  if (qty === 0) {
    card.classList.remove('in-cart');
    qtyEl.style.color = 'var(--text-dim)';
  } else {
    card.classList.add('in-cart');
    qtyEl.style.color = 'var(--gold)';
  }
}

// ── RENDER CART SIDEBAR ──
function renderCart() {
  const orderList = document.getElementById('orderList');
  const emptyCart = document.getElementById('emptyCart');
  const itemCount = document.getElementById('itemCount');

  const cartIds = Object.keys(cart);
  const totalItems = cartIds.reduce((s, id) => s + cart[id], 0);

  document.getElementById('topCartBadge').textContent = totalItems;
  const floatBadge = document.getElementById('floatCartBadge');
  if (floatBadge) floatBadge.textContent = totalItems;

  if (cartIds.length === 0) {
    emptyCart.style.display = 'flex';
    orderList.style.display = 'none';
    itemCount.textContent = 'Belum ada item dipilih';
    updateSummary(0);
    document.getElementById('checkoutBtn').disabled = true;
    return;
  }

  emptyCart.style.display = 'none';
  orderList.style.display = 'flex';
  itemCount.textContent = totalItems + ' item · ' + cartIds.length + ' menu';
  document.getElementById('checkoutBtn').disabled = false;

  // Build order items HTML
  let html = '';
  cartIds.forEach(id => {
    const item = menuData.find(m => m.id == id);
    const qty = cart[id];
    const sub = item.price * qty;
    html += `
      <div class="order-item" id="oi-${id}">
        <img class="order-img" src="${item.image}" alt="${item.name}" onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=100&h=100&fit=crop'">
        <div class="order-info">
          <div class="order-name">${item.name}</div>
          <div class="order-unit-price">${fmt(item.price)} / porsi</div>
          <div class="order-qty-ctrl" style="margin-top:6px;">
            <button class="order-qty-btn" onclick="changeQty(${id},-1)">−</button>
            <span class="order-qty-num">${qty}</span>
            <button class="order-qty-btn" onclick="changeQty(${id},1)">+</button>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
          <div class="order-subtotal">${fmt(sub)}</div>
          <button class="del-btn" onclick="removeItem(${id})" title="Hapus">✕</button>
        </div>
      </div>`;
  });
  orderList.innerHTML = html;

  // Calculate summary
  const subtotal = cartIds.reduce((s, id) => {
    const item = menuData.find(m => m.id == id);
    return s + item.price * cart[id];
  }, 0);
  updateSummary(subtotal);
}

function updateSummary(subtotal) {
  const tax = Math.round(subtotal * 0.1);
  const total = subtotal + tax;
  document.getElementById('subtotalVal').textContent = fmt(subtotal);
  document.getElementById('taxVal').textContent = fmt(tax);
  document.getElementById('totalVal').textContent = fmt(total);
}

function removeItem(id) {
  delete cart[id];
  updateCard(id);
  renderCart();
}

function clearCart() {
  Object.keys(cart).forEach(id => {
    cart[id] = 0;
    updateCard(id);
    delete cart[id];
  });
  renderCart();
}

// ── FILTER ──
function filterMenu(cat, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.card').forEach(card => {
    if (cat === 'Semua' || card.dataset.cat === cat) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  });
}

// ── CHECKOUT ──
let currentOrderSnapshot = null;

function doCheckout() {
  const cartIds = Object.keys(cart);
  if (cartIds.length === 0) return;

  // Generate Order ID
  const orderId = 'ORD-' + Date.now().toString().slice(-6);
  document.getElementById('orderId').textContent = '#' + orderId;

  // Build receipt
  let receiptHTML = '';
  let subtotal = 0;

  cartIds.forEach(id => {
    const item = menuData.find(m => m.id == id);
    const qty = cart[id];
    const sub = item.price * qty;
    subtotal += sub;
    receiptHTML += `
      <tr>
        <td>
          <span class="r-item-name">${item.name}</span>
          <span class="r-item-meta">${fmt(item.price)} × ${qty}</span>
        </td>
        <td class="r-qty">${qty}x</td>
        <td class="r-price">${fmt(sub)}</td>
      </tr>`;
  });

  document.getElementById('receiptBody').innerHTML = receiptHTML;

  const tax = Math.round(subtotal * 0.1);
  const total = subtotal + tax;
  document.getElementById('rSubtotal').textContent = fmt(subtotal);
  document.getElementById('rTax').textContent = fmt(tax);
  document.getElementById('rTotal').textContent = fmt(total);

  // Simpan snapshot untuk disave nanti
  currentOrderSnapshot = {
    orderId,
    timestamp: new Date().toISOString(),
    items: cartIds.map(id => {
      const item = menuData.find(m => m.id == id);
      return { id: item.id, name: item.name, image: item.image, price: item.price, qty: cart[id] };
    }),
    subtotal,
    tax: Math.round(subtotal * 0.1),
    total: subtotal + Math.round(subtotal * 0.1)
  };

  document.getElementById('checkoutModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('checkoutModal').style.display = 'none';
  document.body.style.overflow = '';
  // Jika di mobile, kembalikan state scroll tanpa menutup sidebar (karena kita akan clearCart)
  if (window.innerWidth <= 900) {
     const sidebar = document.querySelector('.sidebar');
     if (!sidebar.classList.contains('show')) {
         document.body.style.overflow = '';
     } else {
         document.body.style.overflow = 'hidden';
     }
  }
}

function confirmCheckout() {
  if (!currentOrderSnapshot) return;

  const btn = document.querySelector('.modal-close-btn');
  btn.disabled = true;
  btn.textContent = '⏳ Menyimpan...';

  // Kirim ke api_checkout.php
  const payload = {
    kode_pesanan: currentOrderSnapshot.orderId,
    nama_kasir: 'Admin',
    catatan: '',
    items: currentOrderSnapshot.items.map(i => ({
      menu_id: i.id,
      jumlah:  i.qty
    }))
  };

  fetch('api_checkout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(res => {
    if (res.error) throw new Error(res.message);

    currentOrderSnapshot = null;
    clearCart();
    closeModal();

    const notif = document.createElement('div');
    notif.style.cssText = `
      position:fixed;bottom:32px;left:50%;transform:translateX(-50%);
      background:var(--green);color:#fff;
      padding:14px 28px;border-radius:100px;
      font-weight:600;font-size:14px;
      box-shadow:0 8px 30px rgba(0,0,0,0.4);
      z-index:999;animation:slideUp 0.3s ease;
      display:flex;align-items:center;gap:10px;
    `;
    notif.innerHTML = '✓ Pesanan tersimpan! <a href="riwayat.php" style="color:#fff;text-decoration:underline;font-weight:800;">Lihat Riwayat →</a>';
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 4500);
  })
  .catch(err => {
    btn.disabled = false;
    btn.textContent = '✓ Konfirmasi & Selesai';
    alert('❌ Gagal menyimpan: ' + err.message);
  });
}

// Close modal on overlay click
document.getElementById('checkoutModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// ── INIT ──
renderCart();
</script>

<style>
@keyframes slideUp {
  from { opacity:0; transform:translateX(-50%) translateY(20px); }
  to { opacity:1; transform:translateX(-50%) translateY(0); }
}
</style>

</body>
</html>
