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
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, nama_lengkap, role, shop_id) VALUES (?, ?, ?, 'seller', ?)");
        if ($stmt->execute([$username, $password, $nama_lengkap, $shop_id])) {
            $message = "Penjual berhasil ditambahkan.";
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
  <title>Manajemen User — WarungKu Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0f0e0b; --surface: #1a1814; --surface2: #231f1a;
      --gold: #d4a853; --gold-dim: rgba(212, 168, 83, 0.12);
      --cream: #f5edd8; --text: #f0e8d5; --text-dim: #8a7f6e;
      --border: rgba(212, 168, 83, 0.15); --red: #e05252; --green: #4caf7d;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
    body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
    
    .sidebar { width: 260px; background: var(--surface); border-right: 1px solid var(--border); padding: 24px 0; display: flex; flex-direction: column; }
    .brand { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 900; color: var(--gold); padding: 0 24px 32px; }
    .brand span { color: var(--cream); }
    .nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
    .nav a { padding: 12px 16px; color: var(--text-dim); text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 8px; transition: all 0.2s; }
    .nav a:hover { background: var(--surface2); color: var(--cream); }
    .nav a.active { background: var(--gold-dim); color: var(--gold); border: 1px solid var(--border); }
    
    .main { flex: 1; padding: 40px; overflow-y: auto; }
    .header { margin-bottom: 30px; }
    .title { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--cream); margin-bottom: 8px; }
    
    .card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 12px; margin-bottom: 24px; }
    .table { width: 100%; border-collapse: collapse; }
    .table th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-size: 12px; text-transform: uppercase; }
    .table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
    
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .badge.admin { background: rgba(224,82,82,0.1); color: var(--red); border: 1px solid rgba(224,82,82,0.3); }
    .badge.seller { background: var(--gold-dim); color: var(--gold); border: 1px solid rgba(212,168,83,0.3); }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; color: var(--text-dim); margin-bottom: 6px; }
    .form-control { width: 100%; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; }
    .btn { padding: 10px 20px; background: var(--gold); color: var(--bg); border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
    .msg { padding: 12px; background: rgba(76,175,125,0.1); color: var(--green); border-radius: 8px; margin-bottom: 20px; }
  </style>
</head>
<body>

  <aside class="sidebar">
    <div class="brand">Warung<span>Ku</span> Admin</div>
    <nav class="nav">
      <a href="index.php">Dashboard</a>
      <a href="shops.php">Manajemen Toko</a>
      <a href="users.php" class="active">Manajemen User</a>
      <a href="products.php">Manajemen Produk</a>
      <a href="subscriptions.php">Pembelian Token</a>
    </nav>
  </aside>

  <main class="main">
    <header class="header">
      <h1 class="title">Manajemen User</h1>
      <p style="color:var(--text-dim);font-size:14px;">Tambah akun penjual dan tugaskan mereka ke toko tertentu.</p>
    </header>

    <?php if ($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="card" style="margin-bottom: 30px;">
      <h3 style="margin-bottom: 16px;">Tambah Akun Penjual</h3>
      <form method="POST" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="flex:1; min-width:200px;">
          <label>Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group" style="flex:1; min-width:200px;">
          <label>Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group" style="flex:1; min-width:200px;">
          <label>Nama Lengkap</label>
          <input type="text" name="nama_lengkap" class="form-control" required>
        </div>
        <div class="form-group" style="flex:1; min-width:200px;">
          <label>Tugaskan ke Toko</label>
          <select name="shop_id" class="form-control" required>
            <option value="">-- Pilih Toko --</option>
            <?php foreach ($shops as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <button type="submit" class="btn">Simpan User</button>
        </div>
      </form>
    </div>

    <div class="card">
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
  </main>

</body>
</html>
