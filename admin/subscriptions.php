<?php
// ============================================================
// subscriptions.php — Manajemen Langganan Toko (Admin)
// ============================================================
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$message = '';
$error = '';

// ── PROSES AKSI (APPROVE / REJECT) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['sub_id'])) {
    $sub_id = (int)$_POST['sub_id'];

    if ($_POST['action'] === 'approve') {
        // Ambil data sub dengan status pending
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ? AND status = 'pending'");
        $stmt->execute([$sub_id]);
        $sub = $stmt->fetch();

        if ($sub) {
            $pdo->beginTransaction();
            try {
                // Update status sub menjadi 'active' (sesuai ENUM di SQL)
                // Note: Schema SQL menggunakan 'active', bukan 'paid'
                $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'active' WHERE id = ?");
                $stmt->execute([$sub_id]);

                // Ambil durasi hari dari plan
                $stmtPlan = $pdo->prepare("SELECT duration_days FROM subscription_plans WHERE id = ?");
                $stmtPlan->execute([$sub['plan_id']]);
                $days = (int)$stmtPlan->fetchColumn() ?: 30;

                $shop_id = $sub['shop_id'];

                // Perpanjang masa aktif toko
                $stmtShop = $pdo->prepare("SELECT subscription_ends_at FROM shops WHERE id = ?");
                $stmtShop->execute([$shop_id]);
                $current_end = $stmtShop->fetchColumn();

                $base_date = (strtotime($current_end) > time()) ? $current_end : date('Y-m-d');
                $new_end = date('Y-m-d', strtotime("$base_date + $days days"));

                $stmtShopUp = $pdo->prepare("UPDATE shops SET subscription_ends_at = ?, status = 'active' WHERE id = ?");
                $stmtShopUp->execute([$new_end, $shop_id]);

                $pdo->commit();
                $message = "Pembayaran berhasil divalidasi. Masa aktif toko telah diperpanjang.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Gagal memproses: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'reject') {
        // Status 'expired' digunakan untuk pembatalan/non-aktif sesuai ENUM
        $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?");
        $stmt->execute([$sub_id]);
        $message = "Tagihan telah dibatalkan.";
    }
}

// ── TAMBAH TAGIHAN MANUAL ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_manual') {
    $shop_id = (int)$_POST['shop_id'];
    $plan_id = (int)$_POST['plan_id'];

    // Ambil data plan
    $stmtPlan = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmtPlan->execute([$plan_id]);
    $plan = $stmtPlan->fetch();

    if ($plan && $shop_id > 0) {
        $amount = $plan['price'];
        // Tentukan started_at & ends_at sementara (akan di-update saat approve)
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime("+$plan[duration_days] days"));

        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (shop_id, plan_id, amount_paid, status, started_at, ends_at) 
            VALUES (?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([$shop_id, $plan_id, $amount, $start, $end]);
        $message = "Tagihan manual berhasil dibuat. Silakan Approve untuk aktivasi.";
    } else {
        $error = "Data paket atau toko tidak valid.";
    }
}

// ── PAGINATION ──
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_subs = $pdo->query("SELECT COUNT(*) FROM subscriptions")->fetchColumn();
$total_pages = ceil($total_subs / $limit);

// ── FETCH DATA ──
$subs = $pdo->query("
    SELECT sub.*, s.name as shop_name, p.name as plan_name
    FROM subscriptions sub 
    JOIN shops s ON sub.shop_id = s.id 
    JOIN subscription_plans p ON sub.plan_id = p.id
    ORDER BY sub.id DESC
    LIMIT $limit OFFSET $offset
")->fetchAll();

$shops = $pdo->query("SELECT id, name FROM shops WHERE status = 'active' ORDER BY name")->fetchAll();
$all_plans = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Langganan — WarungKu Admin</title>
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
    
    .main { flex: 1; padding: 40px; margin-left: 260px; min-width: 0; }
    .header { margin-bottom: 30px; }
    .title { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--cream); margin-bottom: 8px; }
    
    /* MOBILE HEADER */
    .mobile-header {
      display: none; height: 64px; background: var(--surface); border-bottom: 1px solid var(--border);
      padding: 0 20px; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 2000;
    }
    .menu-toggle { background: none; border: none; color: var(--gold); font-size: 24px; cursor: pointer; padding: 8px; }
    .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 950; display: none; opacity: 0; transition: opacity 0.3s; }

    .card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 12px; margin-bottom: 24px; }
    
    /* TABLE */
    .table-responsive { width: 100%; overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; min-width: 800px; }
    .table th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
    .table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
    
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .badge.active { background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid rgba(76,175,125,0.3); }
    .badge.pending { background: rgba(212, 168, 83, 0.1); color: var(--gold); border: 1px solid rgba(212, 168, 83, 0.3); }
    .badge.expired { background: rgba(224,82,82,0.1); color: var(--red); border: 1px solid rgba(224,82,82,0.3); }
    
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; color: var(--text-dim); margin-bottom: 6px; font-weight: 600; }
    .form-control { width: 100%; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; font-size: 14px; }
    .btn { padding: 10px 20px; background: var(--gold); color: var(--bg); border: none; border-radius: 8px; font-weight: 700; font-size:13px; cursor: pointer; transition: 0.2s; }
    .btn:hover { opacity: 0.9; }
    .btn.green { color: var(--green); background: rgba(76,175,125,0.1); border: 1px solid var(--green); }
    .btn.red { color: var(--red); background: rgba(224,82,82,0.1); border: 1px solid var(--red); }

    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 14px; }
    .alert-success { background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid var(--green); }
    .alert-error { background: rgba(224,82,82,0.1); color: var(--red); border: 1px solid var(--red); }

    @media (max-width: 992px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
      .main { margin-left: 0; padding: 24px 20px; }
      .mobile-header { display: flex; }
      .overlay.active { display: block; opacity: 1; }
    }

    /* PAGINATION */
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
    .page-link { 
      padding: 8px 16px; background: var(--surface); border: 1px solid var(--border); 
      color: var(--text-dim); text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 13px;
      transition: all 0.2s;
    }
    .page-link:hover { border-color: var(--gold); color: var(--gold); }
    .page-link.active { background: var(--gold); color: var(--bg); border-color: var(--gold); }
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
        <a href="subscriptions.php" class="active">💎 Pembelian Token</a>
        <a href="logs.php">📜 Log Aktivitas</a>
      </nav>
      <a href="logout.php" class="logout">🚪 Keluar</a>
    </aside>

    <main class="main">
      <header class="header">
        <h1 class="title">Manajemen Langganan</h1>
        <p style="color:var(--text-dim);font-size:14px;">Validasi pembayaran paket langganan kantin.</p>
      </header>

      <?php if ($message): ?><div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div class="card">
        <h3 style="margin-bottom: 20px; font-family:'Playfair Display', serif; color:var(--gold);">Buat Tagihan Manual</h3>
        <form method="POST" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
          <input type="hidden" name="action" value="add_manual">
          <div class="form-group" style="flex:1; min-width:200px;">
            <label>Pilih Toko</label>
            <select name="shop_id" class="form-control" required>
              <option value="">-- Pilih Toko --</option>
              <?php foreach ($shops as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="flex:1; min-width:200px;">
            <label>Paket Langganan</label>
            <select name="plan_id" class="form-control" required>
              <?php foreach ($all_plans as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= rupiah($p['price']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <button type="submit" class="btn">Buat Tagihan</button>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Toko</th>
                <th>Paket</th>
                <th>Nominal</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($subs as $sub): ?>
                <tr>
                  <td>#<?= $sub['id'] ?><br><small style="color:var(--text-dim)"><?= date('d/m/Y', strtotime($sub['created_at'])) ?></small></td>
                  <td style="font-weight:700; color:var(--cream);"><?= htmlspecialchars($sub['shop_name']) ?></td>
                  <td><span style="color:var(--gold); font-weight:600;"><?= strtoupper($sub['plan_name'] ?? '') ?></span></td>
                  <td style="font-weight:600;"><?= rupiah($sub['amount_paid']) ?></td>
                  <td><span class="badge <?= $sub['status'] ?>"><?= strtoupper($sub['status']) ?></span></td>
                  <td>
                    <?php if ($sub['status'] === 'pending'): ?>
                        <div style="display:flex; gap:8px;">
                          <form method="POST" onsubmit="return confirm('Approve pembayaran ini?')">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                            <button type="submit" class="btn green">Approve</button>
                          </form>
                          <form method="POST" onsubmit="return confirm('Batalkan tagihan ini?')">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                            <button type="submit" class="btn red">Batal</button>
                          </form>
                        </div>
                    <?php else: ?>
                        <small style="color:var(--text-dim)">Proses: <?= $sub['status'] === 'active' ? 'Selesai' : 'Dibatalkan' ?></small>
                    <?php endif; ?>
                  </td>
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

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
      const alerts = document.querySelectorAll('.alert, .msg');
      alerts.forEach(el => {
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px)';
        setTimeout(() => el.remove(), 500);
      });
    }, 5000);
  </script>
</body>
</html>
