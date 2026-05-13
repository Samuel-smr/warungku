<?php
// ============================================================
// categories.php — Manajemen Kategori Global (Admin)
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
        $shop_id = (int)$_POST['shop_id'];
        $nama = trim($_POST['nama']);
        $urutan = (int)$_POST['urutan'];

        $stmt = $pdo->prepare("INSERT INTO kategori (shop_id, nama, urutan) VALUES (?, ?, ?)");
        if ($stmt->execute([$shop_id, $nama, $urutan])) {
            $message = "Kategori '$nama' berhasil ditambahkan.";
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $shop_id = (int)$_POST['shop_id'];
        $nama = trim($_POST['nama']);
        $urutan = (int)$_POST['urutan'];

        $stmt = $pdo->prepare("UPDATE kategori SET shop_id = ?, nama = ?, urutan = ? WHERE id = ?");
        if ($stmt->execute([$shop_id, $nama, $urutan, $id])) {
            $message = "Kategori berhasil diperbarui.";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Cek apakah kategori masih digunakan oleh menu
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM menu WHERE kategori_id = ?");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = "Gagal menghapus: Kategori masih digunakan oleh beberapa produk.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = "Kategori berhasil dihapus.";
            }
        }
    }
}

// ── PAGINATION ──
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
$total_pages = ceil($total_kategori / $limit);

// Ambil semua kategori dari semua toko
$categories = $pdo->query("
    SELECT k.*, s.name as shop_name 
    FROM kategori k 
    JOIN shops s ON k.shop_id = s.id 
    ORDER BY s.name, k.urutan ASC
    LIMIT $limit OFFSET $offset
")->fetchAll();

// Data untuk form (Shops)
$shops = $pdo->query("SELECT id, name FROM shops WHERE status = 'active' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Kategori — WarungKu Admin</title>
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
    .header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
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
    .table-responsive { width: 100%; overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; min-width: 600px; }
    .table th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
    .table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; color: var(--text-dim); margin-bottom: 6px; font-weight: 600; }
    .form-control { width: 100%; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; font-size: 14px; }
    
    .btn { padding: 10px 24px; background: var(--gold); color: var(--bg); border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 14px; }
    .btn:hover { opacity: 0.9; }
    .btn-tool { padding: 6px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; cursor: pointer; }

    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 14px; background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid var(--green); }
    .alert-error { background: rgba(224,82,82,0.1); color: var(--red); border-color: var(--red); }

    @media (max-width: 992px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
      .main { margin-left: 0; padding: 24px 20px; }
      .mobile-header { display: flex; }
      .overlay.active { display: block; opacity: 1; }
      .header { flex-direction: column; align-items: flex-start; gap: 16px; }
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

    /* MODAL */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
    .modal { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 500px; padding: 32px; }
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
        <a href="categories.php" class="active">📁 Manajemen Kategori</a>
        <a href="products.php">🍔 Manajemen Produk</a>
        <a href="subscriptions.php">💎 Pembelian Token</a>
        <a href="logs.php">📜 Log Aktivitas</a>
      </nav>
      <a href="logout.php" class="logout">🚪 Keluar</a>
    </aside>

    <main class="main">
      <header class="header">
        <div>
          <h1 class="title">Manajemen Kategori</h1>
          <p style="color:var(--text-dim);font-size:14px;">Kelola kategori menu untuk seluruh tenant.</p>
        </div>
        <button class="btn" onclick="openAdd()">+ Tambah Kategori</button>
      </header>

      <?php if ($message): ?><div class="alert">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Nama Kategori</th>
                <th>Toko Pemilik</th>
                <th>Urutan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $c): ?>
              <tr>
                <td style="font-weight:bold; color:var(--cream);"><?= htmlspecialchars($c['nama']) ?></td>
                <td><span style="color:var(--gold); font-size:12px; font-weight:600;"><?= htmlspecialchars($c['shop_name']) ?></span></td>
                <td><?= $c['urutan'] ?></td>
                <td>
                  <div style="display:flex; gap:8px;">
                    <button class="btn-tool" onclick='openEdit(<?= json_encode($c) ?>)'>✏️</button>
                    <form method="POST" onsubmit="return confirm('Hapus kategori ini?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button type="submit" class="btn-tool" style="color:var(--red)">🗑</button>
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

  <!-- MODAL ADD/EDIT -->
  <div class="modal-overlay" id="categoryModal">
    <div class="modal">
      <h2 id="modalTitle" style="font-family:'Playfair Display', serif; color:var(--gold); margin-bottom:24px;">Tambah Kategori Baru</h2>
      <form method="POST">
        <input type="hidden" name="action" id="modalAction" value="add">
        <input type="hidden" name="id" id="categoryId">
        
        <div class="form-group">
          <label>Toko Pemilik</label>
          <select name="shop_id" id="formShop" class="form-control" required>
            <option value="">-- Pilih Toko --</option>
            <?php foreach ($shops as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Nama Kategori</label>
          <input type="text" name="nama" id="formNama" class="form-control" required placeholder="Minuman Dingin">
        </div>

        <div class="form-group">
          <label>Urutan Tampil</label>
          <input type="number" name="urutan" id="formUrutan" class="form-control" required value="0">
        </div>

        <div style="display:flex; gap:12px; margin-top:32px;">
          <button type="button" class="btn" style="background:transparent; border:1px solid var(--border); color:var(--text-dim); flex:1;" onclick="closeModal()">Batal</button>
          <button type="submit" class="btn" style="flex:2;">SIMPAN KATEGORI</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('active');
      document.getElementById('overlay').classList.toggle('active');
    }

    function openAdd() {
      document.getElementById('modalTitle').textContent = 'Tambah Kategori Baru';
      document.getElementById('modalAction').value = 'add';
      document.getElementById('categoryId').value = '';
      document.getElementById('formShop').value = '';
      document.getElementById('formNama').value = '';
      document.getElementById('formUrutan').value = '0';
      document.getElementById('categoryModal').style.display = 'flex';
    }

    function openEdit(data) {
      document.getElementById('modalTitle').textContent = 'Ubah Data Kategori';
      document.getElementById('modalAction').value = 'edit';
      document.getElementById('categoryId').value = data.id;
      document.getElementById('formShop').value = data.shop_id;
      document.getElementById('formNama').value = data.nama;
      document.getElementById('formUrutan').value = data.urutan;
      document.getElementById('categoryModal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('categoryModal').style.display = 'none';
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
      const alerts = document.querySelectorAll('.alert');
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
