<?php
// ============================================================
// shops.php — Manajemen Toko (Admin)
// ============================================================
require_once __DIR__ . '/../config.php';

// Proteksi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$message = '';
$error = '';

// ── HANDLE ACTIONS (CRUD) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name = trim($_POST['name']);
        // Default 1 bulan dari hari ini jika tidak diisi
        $ends_at = $_POST['ends_at'] ?: date('Y-m-d', strtotime('+1 month'));
        
        $stmt = $pdo->prepare("INSERT INTO shops (name, subscription_ends_at) VALUES (?, ?)");
        if ($stmt->execute([$name, $ends_at])) {
            $message = "Toko '$name' berhasil ditambahkan (Aktif s/d " . date('d/m/Y', strtotime($ends_at)) . ").";
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $ends_at = $_POST['ends_at'] ?: null;
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE shops SET name = ?, subscription_ends_at = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$name, $ends_at, $status, $id])) {
            $message = "Data toko berhasil diperbarui.";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Pastikan bukan toko ID 1 (Toko Utama/Contoh) jika diperlukan proteksi
        $stmt = $pdo->prepare("DELETE FROM shops WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Toko berhasil dihapus.";
        }
    }
}

// ── PAGINATION ──
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_shops = $pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn();
$total_pages = ceil($total_shops / $limit);

$shops = $pdo->query("SELECT * FROM shops ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Toko — WarungKu Admin</title>
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

    .mobile-header {
      display: none; height: 64px; background: var(--surface); border-bottom: 1px solid var(--border);
      padding: 0 20px; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 2000;
    }
    .menu-toggle { background: none; border: none; color: var(--gold); font-size: 24px; cursor: pointer; }
    .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 950; display: none; }
    
    .card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 12px; margin-bottom: 24px; }
    
    /* TABLE */
    .table-responsive { width: 100%; overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; min-width: 700px; }
    .table th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
    .table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
    
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .badge.active { background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid rgba(76,175,125,0.3); }
    .badge.inactive { background: rgba(224,82,82,0.1); color: var(--red); border: 1px solid rgba(224,82,82,0.3); }
    
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; color: var(--text-dim); margin-bottom: 6px; font-weight: 600; }
    .form-control { width: 100%; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; font-size: 14px; }
    .form-control:focus { outline: none; border-color: var(--gold); }
    
    .btn { padding: 10px 20px; background: var(--gold); color: var(--bg); border: none; border-radius: 8px; font-weight: 700; font-size:13px; cursor: pointer; transition: 0.2s; }
    .btn:hover { opacity: 0.9; }
    .btn-tool { padding: 6px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; cursor: pointer; }
    .btn-tool:hover { border-color: var(--gold); }
    .btn-tool.del:hover { border-color: var(--red); color: var(--red); }

    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 14px; background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid var(--green); }

    /* MODAL */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
    .modal { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 500px; padding: 32px; }

    @media (max-width: 992px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
      .main { margin-left: 0; padding: 24px 20px; }
      .mobile-header { display: flex; }
      .overlay.active { display: block; }
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
        <a href="shops.php" class="active">🏪 Manajemen Toko</a>
        <a href="users.php">👥 Manajemen User</a>
        <a href="categories.php">📁 Manajemen Kategori</a>
        <a href="products.php">🍔 Manajemen Produk</a>
        <a href="subscriptions.php">💎 Pembelian Token</a>
        <a href="logs.php">📜 Log Aktivitas</a>
      </nav>
      <a href="logout.php" class="logout">🚪 Keluar</a>
    </aside>

    <main class="main">
      <header class="header">
        <h1 class="title">Manajemen Toko</h1>
        <p style="color:var(--text-dim);font-size:14px;">Tambah, ubah, atau hapus tenant toko.</p>
      </header>

      <?php if ($message): ?><div class="alert">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>

      <div class="card">
        <h3 style="margin-bottom: 20px; font-family:'Playfair Display', serif; color:var(--gold);">Tambah Toko Baru</h3>
        <form method="POST" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
          <input type="hidden" name="action" value="add">
          <div class="form-group" style="flex:2; min-width:200px;">
            <label>Nama Toko</label>
            <input type="text" name="name" class="form-control" required placeholder="Contoh: Kantin Teknik">
          </div>
          <div class="form-group" style="flex:1; min-width:150px;">
            <label>Masa Aktif Awal (Trial 1 Bln)</label>
            <input type="date" name="ends_at" class="form-control" value="<?= date('Y-m-d', strtotime('+1 month')) ?>">
          </div>
          <div class="form-group">
            <button type="submit" class="btn">Simpan Toko</button>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama Toko</th>
                <th>Masa Aktif</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shops as $s): ?>
              <tr>
                <td>#<?= $s['id'] ?></td>
                <td style="font-weight:bold; color:var(--cream);"><?= htmlspecialchars($s['name']) ?></td>
                <td><?= $s['subscription_ends_at'] ? date('d/m/Y', strtotime($s['subscription_ends_at'])) : '-' ?></td>
                <td><span class="badge <?= $s['status'] ?>"><?= strtoupper($s['status']) ?></span></td>
                <td>
                  <div style="display:flex; gap:8px;">
                    <button class="btn-tool" onclick='openEdit(<?= json_encode($s) ?>)'>✏️</button>
                    <form method="POST" onsubmit="return confirm('Hapus toko ini?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $s['id'] ?>">
                      <button type="submit" class="btn-tool del">🗑</button>
                    </form>
                  </div>
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

  <!-- MODAL EDIT -->
  <div class="modal-overlay" id="editModal">
    <div class="modal">
      <h2 style="font-family:'Playfair Display', serif; color:var(--gold); margin-bottom:24px;">Ubah Data Toko</h2>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editId">
        
        <div class="form-group">
          <label>Nama Toko</label>
          <input type="text" name="name" id="editName" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label>Masa Aktif</label>
          <input type="date" name="ends_at" id="editEnds" class="form-control">
        </div>

        <div class="form-group">
          <label>Status Toko</label>
          <select name="status" id="editStatus" class="form-control">
            <option value="active">ACTIVE</option>
            <option value="inactive">INACTIVE</option>
          </select>
        </div>

        <div style="display:flex; gap:12px; margin-top:32px;">
          <button type="button" class="btn" style="background:transparent; border:1px solid var(--border); color:var(--text-dim); flex:1;" onclick="closeEdit()">Batal</button>
          <button type="submit" class="btn" style="flex:2;">SIMPAN PERUBAHAN</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('active');
      document.getElementById('overlay').classList.toggle('active');
    }

    function openEdit(data) {
      document.getElementById('editId').value = data.id;
      document.getElementById('editName').value = data.name;
      document.getElementById('editEnds').value = data.subscription_ends_at;
      document.getElementById('editStatus').value = data.status;
      document.getElementById('editModal').style.display = 'flex';
    }

    function closeEdit() {
      document.getElementById('editModal').style.display = 'none';
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
