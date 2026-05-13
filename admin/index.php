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
    'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn(),
    'shops' => $pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn(),
    'subs' => $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'paid'")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(amount) FROM subscriptions WHERE status = 'paid'")->fetchColumn() ?? 0
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard — WarungKu</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0f0e0b; --surface: #1a1814; --surface2: #231f1a;
      --gold: #d4a853; --gold-dim: rgba(212, 168, 83, 0.12);
      --cream: #f5edd8; --text: #f0e8d5; --text-dim: #8a7f6e;
      --border: rgba(212, 168, 83, 0.15); --red: #e05252;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
    body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
    
    /* SIDEBAR */
    .sidebar { width: 260px; background: var(--surface); border-right: 1px solid var(--border); padding: 24px 0; display: flex; flex-direction: column; }
    .brand { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 900; color: var(--gold); padding: 0 24px 32px; }
    .brand span { color: var(--cream); }
    .nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
    .nav a { padding: 12px 16px; color: var(--text-dim); text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 8px; transition: all 0.2s; }
    .nav a:hover { background: var(--surface2); color: var(--cream); }
    .nav a.active { background: var(--gold-dim); color: var(--gold); border: 1px solid var(--border); }
    .logout { margin: 24px 12px 0; padding: 12px 16px; color: var(--red); text-decoration: none; font-size: 14px; font-weight: 600; text-align: center; border: 1px solid rgba(224,82,82,0.3); border-radius: 8px; }
    
    /* MAIN CONTENT */
    .main { flex: 1; padding: 40px; overflow-y: auto; }
    .header { margin-bottom: 40px; }
    .title { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--cream); margin-bottom: 8px; }
    .subtitle { color: var(--text-dim); font-size: 14px; }
    
    /* STATS GRID */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .stat-card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 16px; }
    .stat-title { color: var(--text-dim); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
    .stat-value { font-size: 32px; font-weight: 700; color: var(--gold); font-family: 'Playfair Display', serif; }
  </style>
</head>
<body>

  <aside class="sidebar">
    <div class="brand">Warung<span>Ku</span> Admin</div>
    <nav class="nav">
      <a href="index.php" class="active">Dashboard</a>
      <a href="shops.php">Manajemen Toko</a>
      <a href="users.php">Manajemen User</a>
      <a href="products.php">Manajemen Produk</a>
      <a href="subscriptions.php">Pembelian Token</a>
    </nav>
    <a href="logout.php" class="logout">Keluar</a>
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

</body>
</html>
