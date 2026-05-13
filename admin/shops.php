<?php
require_once __DIR__ . '/../config.php';

// Proteksi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name']);
        $domain = trim($_POST['domain']);
        $ends_at = $_POST['ends_at'] ?: null;
        
        $stmt = $pdo->prepare("INSERT INTO shops (name, domain, subscription_ends_at) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $domain, $ends_at])) {
            $message = "Toko berhasil ditambahkan.";
        }
    }
}

$shops = $pdo->query("SELECT * FROM shops ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Manajemen Toko — WarungKu Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Reset & Base variables from index.php omitted for brevity but standard layout applies */
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
    .header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
    .title { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--cream); margin-bottom: 8px; }
    
    .card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 12px; margin-bottom: 24px; }
    .table { width: 100%; border-collapse: collapse; }
    .table th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-size: 12px; text-transform: uppercase; }
    .table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
    
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .badge.active { background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid rgba(76,175,125,0.3); }
    
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
      <a href="shops.php" class="active">Manajemen Toko</a>
      <a href="users.php">Manajemen User</a>
      <a href="products.php">Manajemen Produk</a>
      <a href="subscriptions.php">Pembelian Token</a>
    </nav>
  </aside>

  <main class="main">
    <header class="header">
      <div>
        <h1 class="title">Manajemen Toko</h1>
        <p style="color:var(--text-dim);font-size:14px;">Kelola tenant/toko yang terdaftar di platform.</p>
      </div>
    </header>

    <?php if ($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="card" style="margin-bottom: 30px;">
      <h3 style="margin-bottom: 16px;">Tambah Toko Baru</h3>
      <form method="POST" style="display:flex; gap:16px; align-items:flex-end;">
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="flex:1;">
          <label>Nama Toko</label>
          <input type="text" name="name" class="form-control" required placeholder="Contoh: Kantin Teknik">
        </div>
        <div class="form-group" style="flex:1;">
          <label>Domain Tambahan (Opsional)</label>
          <input type="text" name="domain" class="form-control" placeholder="teknik.kantin.com">
        </div>
        <div class="form-group" style="flex:1;">
          <label>Langganan Berakhir</label>
          <input type="date" name="ends_at" class="form-control">
        </div>
        <div class="form-group">
          <button type="submit" class="btn">Simpan</button>
        </div>
      </form>
    </div>

    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Toko</th>
            <th>Domain</th>
            <th>Langganan Berakhir</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($shops as $s): ?>
          <tr>
            <td>#<?= $s['id'] ?></td>
            <td style="font-weight:bold; color:var(--cream);"><?= htmlspecialchars($s['name']) ?></td>
            <td><?= htmlspecialchars($s['domain'] ?: '-') ?></td>
            <td><?= $s['subscription_ends_at'] ? date('d M Y', strtotime($s['subscription_ends_at'])) : '-' ?></td>
            <td><span class="badge active"><?= strtoupper($s['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($shops)): ?>
          <tr><td colspan="5" style="text-align:center;">Belum ada toko terdaftar.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

</body>
</html>
