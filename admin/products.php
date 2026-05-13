<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pdo = getDB();

// Fetch all menus from all shops
$menus = $pdo->query("
    SELECT m.*, k.nama as kategori_nama, s.name as shop_name 
    FROM menu m 
    LEFT JOIN kategori k ON m.kategori_id = k.id 
    LEFT JOIN shops s ON m.shop_id = s.id 
    ORDER BY s.id, k.urutan, m.nama
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Manajemen Produk — WarungKu Admin</title>
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
    .badge.green { background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid rgba(76,175,125,0.3); }
    .badge.red { background: rgba(224,82,82,0.1); color: var(--red); border: 1px solid rgba(224,82,82,0.3); }
    
    .img-thumb { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); }
  </style>
</head>
<body>

  <aside class="sidebar">
    <div class="brand">Warung<span>Ku</span> Admin</div>
    <nav class="nav">
      <a href="index.php">Dashboard</a>
      <a href="shops.php">Manajemen Toko</a>
      <a href="users.php">Manajemen User</a>
      <a href="products.php" class="active">Manajemen Produk</a>
      <a href="subscriptions.php">Pembelian Token</a>
    </nav>
  </aside>

  <main class="main">
    <header class="header">
      <h1 class="title">Direktori Produk</h1>
      <p style="color:var(--text-dim);font-size:14px;">Melihat seluruh daftar produk dari semua tenant di platform.</p>
    </header>

    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Gambar</th>
            <th>Nama Produk</th>
            <th>Toko Pemilik</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($menus as $m): ?>
          <tr>
            <td>
              <?php if($m['gambar_url']): ?>
                <img src="../<?= htmlspecialchars($m['gambar_url']) ?>" class="img-thumb" onerror="this.src='https://via.placeholder.com/40/231f1a/d4a853?text=?'">
              <?php else: ?>
                <div class="img-thumb" style="background:var(--surface2);display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:10px;">NoImg</div>
              <?php endif; ?>
            </td>
            <td style="font-weight:bold; color:var(--cream);"><?= htmlspecialchars($m['nama']) ?></td>
            <td style="color:var(--gold);"><?= htmlspecialchars($m['shop_name'] ?? 'Toko Default') ?></td>
            <td><?= htmlspecialchars($m['kategori_nama']) ?></td>
            <td><?= rupiah($m['harga']) ?></td>
            <td>
              <?php if($m['stok'] == 1): ?>
                <span class="badge green">Tersedia</span>
              <?php else: ?>
                <span class="badge red">Habis</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($menus)): ?>
          <tr><td colspan="6" style="text-align:center;">Belum ada produk yang didaftarkan oleh tenant manapun.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

</body>
</html>
