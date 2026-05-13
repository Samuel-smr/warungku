<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $username = trim($_POST['username']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $shop_id = $_POST['shop_id'] ?: null;
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, nama_lengkap, role, shop_id) VALUES (?, ?, ?, 'kasir', ?)");
        if ($stmt->execute([$username, $password, $nama_lengkap, $shop_id])) {
            $message = "Pengguna (Kasir) berhasil ditambahkan.";
        }
    }
}

$users = $pdo->query("SELECT u.*, s.name as shop_name FROM users u LEFT JOIN shops s ON u.shop_id = s.id ORDER BY u.id DESC")->fetchAll();
$shops = $pdo->query("SELECT id, name FROM shops ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen User — WarungKu Admin</title>
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
    .header { margin-bottom: 30px; }
    .title { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--cream); margin-bottom: 8px; }
    
    /* MOBILE HEADER */
    .mobile-header {
      display: none; height: 64px; background: var(--surface); border-bottom: 1px solid var(--border);
      padding: 0 20px; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 900;
    }
    .menu-toggle { background: none; border: none; color: var(--gold); font-size: 24px; cursor: pointer; padding: 8px; }
    .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 950; display: none; opacity: 0; transition: opacity 0.3s; }

    .card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 12px; margin-bottom: 24px; }
    
    /* TABLE */
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .table { width: 100%; border-collapse: collapse; min-width: 700px; }
    .table th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
    .table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
    
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .badge.admin { background: rgba(224,82,82,0.1); color: var(--red); border: 1px solid rgba(224,82,82,0.3); }
    .badge.seller { background: var(--gold-dim); color: var(--gold); border: 1px solid rgba(212,168,83,0.3); }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; color: var(--text-dim); margin-bottom: 6px; font-weight: 600; }
    .form-control { width: 100%; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; font-size: 14px; }
    .btn { padding: 10px 24px; background: var(--gold); color: var(--bg); border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: transform 0.2s; }
    .btn:hover { transform: translateY(-2px); }
    .msg { padding: 12px; background: rgba(76,175,125,0.1); color: var(--green); border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(76,175,125,0.2); }

    /* RESPONSIVE */
    @media (max-width: 992px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
      .main { margin-left: 0; padding: 24px 20px; }
      .mobile-header { display: flex; }
      .overlay.active { display: block; opacity: 1; }
      .title { font-size: 28px; }
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
        <a href="users.php" class="active">👥 Manajemen User</a>
        <a href="products.php">🍔 Manajemen Produk</a>
        <a href="subscriptions.php">💎 Pembelian Token</a>
      </nav>
      <a href="logout.php" class="logout">🚪 Keluar</a>
    </aside>

    <main class="main">
      <header class="header">
        <h1 class="title">Manajemen User</h1>
        <p style="color:var(--text-dim);font-size:14px;">Tambah akun penjual dan tugaskan mereka ke toko tertentu.</p>
      </header>

      <?php if ($message): ?><div class="msg">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>

      <div class="card">
        <h3 style="margin-bottom: 16px; font-family:'Playfair Display', serif;">Tambah Akun Penjual</h3>
        <form method="POST" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
          <input type="hidden" name="action" value="add">
          <div class="form-group" style="flex:1; min-width:240px;">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required placeholder="admin_kantin">
          </div>
          <div class="form-group" style="flex:1; min-width:240px;">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
          </div>
          <div class="form-group" style="flex:1; min-width:240px;">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" required placeholder="Budi Santoso">
          </div>
          <div class="form-group" style="flex:1; min-width:240px;">
            <label>Tugaskan ke Toko</label>
            <select name="shop_id" class="form-control" required>
              <option value="">-- Pilih Toko --</option>
              <?php foreach ($shops as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="width: 100%; margin-top: 8px;">
            <button type="submit" class="btn">Simpan User</button>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Username</th>
                <th>Nama Lengkap</th>
                <th>Role</th>
                <th>Toko yang Dikelola</th>
                <th>Terdaftar</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td style="font-weight:bold; color:var(--cream);"><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                <td><span class="badge <?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                <td><?= $u['shop_name'] ? htmlspecialchars($u['shop_name']) : '<i style="color:var(--text-dim)">Tidak ada (Super Admin)</i>' ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
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

</body>
</html>
