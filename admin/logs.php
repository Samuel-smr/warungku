<?php
// ============================================================
// logs.php — Log Aktivitas Sistem (Superadmin)
// ============================================================
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

$pdo = getDB();

// ── PAGINATION ──
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ── FETCH DATA ──
$total_logs = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$total_pages = ceil($total_logs / $limit);

$stmt = $pdo->prepare("
    SELECT l.*, u.username, u.nama_lengkap, s.name as shop_name 
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    LEFT JOIN shops s ON l.shop_id = s.id
    ORDER BY l.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute();
$logs = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activity Logs — WarungKu Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0f0e0b; --surface: #1a1814; --surface2: #231f1a;
      --gold: #d4a853; --gold-dim: rgba(212, 168, 83, 0.12);
      --cream: #f5edd8; --text: #f0e8d5; --text-dim: #8a7f6e;
      --border: rgba(212, 168, 83, 0.15); --red: #e05252; --green: #4caf7d;
      --radius: 12px;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
    body { background: var(--bg); color: var(--text); min-height: 100vh; }
    .layout { display: flex; min-height: 100vh; }
    .sidebar { 
      width: 260px; background: var(--surface); border-right: 1px solid var(--border); 
      padding: 24px 0; display: flex; flex-direction: column;
      position: fixed; top: 0; bottom: 0; left: 0; z-index: 1000;
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .brand { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 900; color: var(--gold); padding: 0 24px 32px; }
    .brand span { color: var(--cream); }
    .nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
    .nav a { padding: 12px 16px; color: var(--text-dim); text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 8px; transition: 0.2s; display: flex; align-items: center; gap: 12px; }
    .nav a:hover { background: var(--surface2); color: var(--cream); }
    .nav a.active { background: var(--gold-dim); color: var(--gold); border: 1px solid var(--border); }
    .logout { margin: 24px 12px 0; padding: 12px 16px; color: var(--red); text-decoration: none; font-size: 14px; font-weight: 600; text-align: center; border: 1px solid rgba(224,82,82,0.3); border-radius: 8px; }
    
    .main { flex: 1; padding: 40px; margin-left: 260px; min-width: 0; }
    .title { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--cream); margin-bottom: 30px; }
    
    .card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 12px; }
    .table-responsive { width: 100%; overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; min-width: 800px; }
    .table th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
    .table td { padding: 14px 12px; border-bottom: 1px solid var(--border); font-size: 13px; }
    
    .action-badge { font-weight: 700; font-size: 10px; padding: 2px 8px; border-radius: 4px; background: var(--surface2); border: 1px solid var(--border); color: var(--gold); }
    .timestamp { color: var(--text-dim); font-size: 11px; }

    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
    .page-link { padding: 8px 16px; background: var(--surface); border: 1px solid var(--border); color: var(--text-dim); text-decoration: none; border-radius: 8px; font-size: 13px; }
    .page-link.active { background: var(--gold); color: var(--bg); }

    .mobile-header {
      display: none; height: 64px; background: var(--surface); border-bottom: 1px solid var(--border);
      padding: 0 20px; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 2000;
    }
    .menu-toggle { background: none; border: none; color: var(--gold); font-size: 24px; cursor: pointer; padding: 8px; }
    .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 950; display: none; opacity: 0; transition: opacity 0.3s; }

    @media (max-width: 992px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
      .main { margin-left: 0; padding: 24px 20px; }
      .mobile-header { display: flex; }
      .overlay.active { display: block; opacity: 1; }
      .title { font-size: 24px; margin-bottom: 20px; }
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
        <a href="index.php">📊 Dashboard</a>
        <a href="shops.php">🏪 Manajemen Toko</a>
        <a href="users.php">👥 Manajemen User</a>
        <a href="categories.php">📁 Manajemen Kategori</a>
        <a href="products.php">🍔 Manajemen Produk</a>
        <a href="subscriptions.php">💎 Pembelian Token</a>
        <a href="logs.php" class="active">📜 Log Aktivitas</a>
      </nav>
      <a href="logout.php" class="logout">🚪 Keluar</a>
    </aside>

    <main class="main">
      <h1 class="title">Log Aktivitas Sistem</h1>
      
      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>User</th>
                <th>Toko</th>
                <th>Aksi</th>
                <th>Deskripsi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $l): ?>
              <tr>
                <td class="timestamp"><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></td>
                <td>
                  <div style="font-weight:600; color:var(--cream);"><?= htmlspecialchars($l['nama_lengkap'] ?: 'System') ?></div>
                  <div style="font-size:10px; color:var(--text-dim);">@<?= htmlspecialchars($l['username'] ?: 'system') ?></div>
                </td>
                <td><?= htmlspecialchars($l['shop_name'] ?: '-') ?></td>
                <td><span class="action-badge"><?= $l['action'] ?></span></td>
                <td style="color:var(--cream);"><?= htmlspecialchars($l['description']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" class="page-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
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
