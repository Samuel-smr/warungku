<?php
require_once __DIR__ . '/../config.php';

// Proteksi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pdo = getDB();

// Fetch Stats
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'kasir' OR role = 'admin'")->fetchColumn(),
    'shops' => $pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn(),
    'subs' => $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status != 'pending'")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(amount_paid) FROM subscriptions WHERE status != 'pending'")->fetchColumn() ?? 0
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — WarungKu</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0f0e0b; --surface: #1a1814; --surface2: #231f1a;
      --gold: #d4a853; --gold-dim: rgba(212, 168, 83, 0.12);
      --cream: #f5edd8; --text: #f0e8d5; --text-dim: #8a7f6e;
      --border: rgba(212, 168, 83, 0.15); --red: #e05252;
      --radius: 12px;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
    body { background: var(--bg); color: var(--text); min-height: 100vh; overflow-x: hidden; }
    
    /* LAYOUT */
    .layout { display: flex; min-height: 100vh; }

    /* SIDEBAR */
    .sidebar { 
      width: 260px; background: var(--surface); border-right: 1px solid var(--border); 
      padding: 24px 0; display: flex; flex-direction: column;
      position: fixed; top: 0; bottom: 0; left: 0; z-index: 1000;
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .brand { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 900; color: var(--gold); padding: 0 24px 32px; }
    .brand span { color: var(--cream); }
    .nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
    .nav a { padding: 12px 16px; color: var(--text-dim); text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; gap: 12px; }
    .nav a:hover { background: var(--surface2); color: var(--cream); }
    .nav a.active { background: var(--gold-dim); color: var(--gold); border: 1px solid var(--border); }
    .logout { margin: 24px 12px 0; padding: 12px 16px; color: var(--red); text-decoration: none; font-size: 14px; font-weight: 600; text-align: center; border: 1px solid rgba(224,82,82,0.3); border-radius: 8px; }
    
    /* MAIN CONTENT */
    .main { flex: 1; padding: 40px; margin-left: 260px; min-width: 0; }
    .header { margin-bottom: 40px; }
    .title { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--cream); margin-bottom: 8px; }
    .subtitle { color: var(--text-dim); font-size: 14px; }
    
    /* MOBILE HEADER */
    .mobile-header {
      display: none; height: 64px; background: var(--surface); border-bottom: 1px solid var(--border);
      padding: 0 20px; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 900;
    }
    .menu-toggle { background: none; border: none; color: var(--gold); font-size: 24px; cursor: pointer; padding: 8px; }
    .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 950; display: none; opacity: 0; transition: opacity 0.3s; }

    /* STATS GRID */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .stat-card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 16px; transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-4px); border-color: var(--gold); }
    .stat-title { color: var(--text-dim); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 700; }
    .stat-value { font-size: 32px; font-weight: 700; color: var(--gold); font-family: 'Playfair Display', serif; }

    /* RESPONSIVE */
    @media (max-width: 992px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
      .main { margin-left: 0; padding: 24px 20px; }
      .mobile-header { display: flex; }
      .overlay.active { display: block; opacity: 1; }
      .title { font-size: 28px; }
    }
    @media (max-width: 480px) {
      .stats-grid { grid-template-columns: 1fr; }
      .stat-card { padding: 20px; }
      .stat-value { font-size: 28px; }
    }
  </style>
</head>
<body>

  <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

  <header class="mobile-header">
    <div class="brand" style="padding:0; margin:0; font-size:20px;">Warung<span>Ku</span> Admin</div>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
  </header>

  <div class="layout">
    <aside class="sidebar" id="sidebar">
      <div class="brand">Warung<span>Ku</span> Admin</div>
      <nav class="nav">
        <a href="index.php" class="active">📊 Dashboard</a>
        <a href="shops.php">🏪 Manajemen Toko</a>
        <a href="users.php">👥 Manajemen User</a>
        <a href="products.php">🍔 Manajemen Produk</a>
        <a href="subscriptions.php">💎 Pembelian Token</a>
      </nav>
      <a href="logout.php" class="logout">🚪 Keluar</a>
    </aside>

    <main class="main">
      <header class="header">
        <h1 class="title">Dashboard</h1>
        <p class="subtitle">Ikhtisar platform SaaS WarungKu</p>
      </header>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-title">Total Penjual</div>
          <div class="stat-value"><?= number_format($stats['users']) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Total Toko Aktif</div>
          <div class="stat-value"><?= number_format($stats['shops']) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Langganan Terjual</div>
          <div class="stat-value"><?= number_format($stats['subs']) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Total Pendapatan</div>
          <div class="stat-value"><?= rupiah($stats['revenue']) ?></div>
        </div>
      </div>
    </main>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('active');
      document.getElementById('overlay').classList.toggle('active');
    }
  </script>

</body>
</html>
