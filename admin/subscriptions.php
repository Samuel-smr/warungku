<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$message = '';

// Proses status pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['sub_id'])) {
    $sub_id = (int)$_POST['sub_id'];
    
    if ($_POST['action'] === 'approve') {
        // Ambil data sub
        $sub = $pdo->query("SELECT * FROM subscriptions WHERE id = $sub_id AND status = 'pending'")->fetch();
        if ($sub) {
            $pdo->beginTransaction();
            try {
                // Update status sub
                $pdo->exec("UPDATE subscriptions SET status = 'paid', paid_at = NOW() WHERE id = $sub_id");
                
                // Tambah durasi ke toko
                $months = 1;
                if ($sub['plan_type'] === '6_months') $months = 6;
                if ($sub['plan_type'] === '12_months') $months = 12;
                
                $shop_id = $sub['shop_id'];
                // Jika sudah punya expired date, tambah. Jika null/expired, mulai dari hari ini.
                $pdo->exec("
                    UPDATE shops 
                    SET subscription_ends_at = CASE 
                        WHEN subscription_ends_at > NOW() THEN DATE_ADD(subscription_ends_at, INTERVAL $months MONTH)
                        ELSE DATE_ADD(NOW(), INTERVAL $months MONTH)
                    END
                    WHERE id = $shop_id
                ");
                
                $pdo->commit();
                $message = "Pembayaran berhasil divalidasi. Masa aktif toko telah diperpanjang.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Error: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'reject') {
        $pdo->exec("UPDATE subscriptions SET status = 'cancelled' WHERE id = $sub_id AND status = 'pending'");
        $message = "Pembelian token dibatalkan.";
    }
}

// Tambah token manual oleh admin (misal penjual bayar tunai)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_manual') {
    $shop_id = (int)$_POST['shop_id'];
    $plan_type = $_POST['plan_type'];
    
    $amounts = ['1_month' => 20000, '6_months' => 110000, '12_months' => 200000];
    $amount = $amounts[$plan_type] ?? 0;
    
    if ($shop_id && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO subscriptions (shop_id, plan_type, amount, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$shop_id, $plan_type, $amount]);
        $message = "Tagihan token berhasil dibuat. Silakan Approve untuk memperpanjang otomatis.";
    }
}

$subs = $pdo->query("
    SELECT sub.*, s.name as shop_name 
    FROM subscriptions sub 
    JOIN shops s ON sub.shop_id = s.id 
    ORDER BY sub.id DESC
")->fetchAll();
$shops = $pdo->query("SELECT id, name FROM shops ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pembelian Token — WarungKu Admin</title>
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
    .badge.paid { background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid rgba(76,175,125,0.3); }
    .badge.pending { background: rgba(212, 168, 83, 0.1); color: var(--gold); border: 1px solid rgba(212, 168, 83, 0.3); }
    .badge.cancelled { background: rgba(224,82,82,0.1); color: var(--red); border: 1px solid rgba(224,82,82,0.3); }
    
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; color: var(--text-dim); margin-bottom: 6px; }
    .form-control { width: 100%; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; }
    .btn { padding: 8px 16px; background: var(--gold); color: var(--bg); border: none; border-radius: 6px; font-weight: bold; font-size:13px; cursor: pointer; }
    .btn.green { background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid var(--green); }
    .btn.green:hover { background: var(--green); color: #fff; }
    .btn.red { background: rgba(224,82,82,0.1); color: var(--red); border: 1px solid var(--red); margin-left:8px; }
    .btn.red:hover { background: var(--red); color: #fff; }
    .msg { padding: 12px; background: rgba(76,175,125,0.1); color: var(--green); border-radius: 8px; margin-bottom: 20px; }
  </style>
</head>
<body>

  <aside class="sidebar">
    <div class="brand">Warung<span>Ku</span> Admin</div>
    <nav class="nav">
      <a href="index.php">Dashboard</a>
      <a href="shops.php">Manajemen Toko</a>
      <a href="users.php">Manajemen User</a>
      <a href="products.php">Manajemen Produk</a>
      <a href="subscriptions.php" class="active">Pembelian Token</a>
    </nav>
  </aside>

  <main class="main">
    <header class="header">
      <h1 class="title">Pembelian Token Langganan</h1>
      <p style="color:var(--text-dim);font-size:14px;">Validasi pembayaran langganan kantin. Menyetujui token otomatis memperpanjang masa aktif toko.</p>
    </header>

    <?php if ($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="card" style="margin-bottom: 30px;">
      <h3 style="margin-bottom: 16px;">Buat Tagihan Manual</h3>
      <form method="POST" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
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
          <select name="plan_type" class="form-control" required>
            <option value="1_month">1 Bulan (Rp 20.000)</option>
            <option value="6_months">6 Bulan (Rp 110.000)</option>
            <option value="12_months">1 Tahun (Rp 200.000)</option>
          </select>
        </div>
        <div class="form-group">
          <button type="submit" class="btn" style="padding:10px 20px;">Buat Tagihan</button>
        </div>
      </form>
    </div>

    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>ID Tagihan</th>
            <th>Toko Pemesan</th>
            <th>Paket</th>
            <th>Nominal</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subs as $sub): ?>
          <tr>
            <td>#<?= $sub['id'] ?><br><small style="color:var(--text-dim)"><?= date('d M Y', strtotime($sub['created_at'])) ?></small></td>
            <td style="font-weight:bold; color:var(--cream);"><?= htmlspecialchars($sub['shop_name']) ?></td>
            <td><?= str_replace('_', ' ', strtoupper($sub['plan_type'])) ?></td>
            <td><?= rupiah($sub['amount']) ?></td>
            <td><span class="badge <?= $sub['status'] ?>"><?= strtoupper($sub['status']) ?></span></td>
            <td>
              <?php if($sub['status'] === 'pending'): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="approve">
                  <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                  <button type="submit" class="btn green" onclick="return confirm('Tandai Lunas dan perpanjang masa aktif toko?')">Approve</button>
                </form>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="reject">
                  <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                  <button type="submit" class="btn red" onclick="return confirm('Tolak dan batalkan tagihan ini?')">Tolak</button>
                </form>
              <?php else: ?>
                <span style="color:var(--text-dim);font-size:12px;">Selesai pada <?= $sub['paid_at'] ? date('d M Y', strtotime($sub['paid_at'])) : '-' ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($subs)): ?>
          <tr><td colspan="6" style="text-align:center;">Belum ada riwayat pembelian langganan.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

</body>
</html>
