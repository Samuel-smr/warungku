<?php
// ============================================================
// products.php — Direktori & CRUD Produk Global (Admin)
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
        $kategori_id = (int)$_POST['kategori_id'];
        $nama = trim($_POST['nama']);
        $harga = (int)$_POST['harga'];
        $stok = (int)$_POST['stok'];
        $gambar_url = trim($_POST['gambar_url']);
        $deskripsi = trim($_POST['deskripsi']);

        // Handle Image Upload
        if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['gambar_file']['tmp_name'];
            $ext = pathinfo($_FILES['gambar_file']['name'], PATHINFO_EXTENSION);
            $new_name = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = __DIR__ . '/../uploads/' . $new_name;
            
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $gambar_url = 'uploads/' . $new_name;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO menu (shop_id, kategori_id, nama, harga, stok, gambar_url, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$shop_id, $kategori_id, $nama, $harga, $stok, $gambar_url, $deskripsi])) {
            logActivity($_SESSION['user_id'], 'ADD_PRODUCT', "Menambah produk '$nama' ke Toko ID $shop_id");
            $message = "Produk '$nama' berhasil ditambahkan.";
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $shop_id = (int)$_POST['shop_id'];
        $kategori_id = (int)$_POST['kategori_id'];
        $nama = trim($_POST['nama']);
        $harga = (int)$_POST['harga'];
        $stok = (int)$_POST['stok'];
        $gambar_url = trim($_POST['gambar_url']);
        $deskripsi = trim($_POST['deskripsi']);

        // Handle Image Upload
        if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['gambar_file']['tmp_name'];
            $ext = pathinfo($_FILES['gambar_file']['name'], PATHINFO_EXTENSION);
            $new_name = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = __DIR__ . '/../uploads/' . $new_name;
            
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $gambar_url = 'uploads/' . $new_name;
            }
        }

        $stmt = $pdo->prepare("UPDATE menu SET shop_id = ?, kategori_id = ?, nama = ?, harga = ?, stok = ?, gambar_url = ?, deskripsi = ? WHERE id = ?");
        if ($stmt->execute([$shop_id, $kategori_id, $nama, $harga, $stok, $gambar_url, $deskripsi, $id])) {
            logActivity($_SESSION['user_id'], 'EDIT_PRODUCT', "Mengubah produk ID $id ('$nama')");
            $message = "Produk berhasil diperbarui.";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ?");
        if ($stmt->execute([$id])) {
            logActivity($_SESSION['user_id'], 'DELETE_PRODUCT', "Menghapus produk ID $id");
            $message = "Produk berhasil dihapus.";
        }
    }
}

// ── SEARCH & FILTER ──
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$f_shop = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
$f_kat = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "m.nama LIKE ?";
    $params[] = "%$search%";
}
if ($f_shop) {
    $where[] = "m.shop_id = ?";
    $params[] = $f_shop;
}
if ($f_kat) {
    $where[] = "m.kategori_id = ?";
    $params[] = $f_kat;
}

$where_sql = implode(" AND ", $where);

// ── PAGINATION ──
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM menu m WHERE $where_sql");
$stmt_count->execute($params);
$total_products = $stmt_count->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Ambil semua menu dari semua toko
$stmt = $pdo->prepare("
    SELECT m.*, s.name as shop_name, k.nama as kategori_nama
    FROM menu m 
    JOIN shops s ON m.shop_id = s.id 
    LEFT JOIN kategori k ON m.kategori_id = k.id
    WHERE $where_sql
    ORDER BY m.id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$menus = $stmt->fetchAll();

// Data untuk form (Shops & Kategori)
$shops = $pdo->query("SELECT id, name FROM shops WHERE status = 'active' ORDER BY name")->fetchAll();
$all_kategori = $pdo->query("SELECT k.*, s.name as shop_name FROM kategori k JOIN shops s ON k.shop_id = s.id ORDER BY s.name, k.nama")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Direktori Produk — WarungKu Admin</title>
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
      position: sticky; top: 0; z-index: 900;
    }
    .menu-toggle { background: none; border: none; color: var(--gold); font-size: 24px; cursor: pointer; padding: 8px; }
    .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 950; display: none; opacity: 0; transition: opacity 0.3s; }

    .card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 12px; margin-bottom: 24px; }
    .table-responsive { width: 100%; overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; min-width: 800px; }
    .table th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
    .table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; color: var(--text-dim); margin-bottom: 6px; font-weight: 600; }
    .form-control { width: 100%; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; font-size: 14px; }
    
    .btn { padding: 10px 24px; background: var(--gold); color: var(--bg); border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 14px; }
    .btn:hover { opacity: 0.9; }
    .btn-tool { padding: 6px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; cursor: pointer; }

    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 14px; background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid var(--green); }

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
    .modal { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 600px; padding: 32px; max-height: 90vh; overflow-y: auto; }
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
        <a href="products.php" class="active">🍔 Manajemen Produk</a>
        <a href="subscriptions.php">💎 Pembelian Token</a>
        <a href="logs.php">📜 Log Aktivitas</a>
      </nav>
      <a href="logout.php" class="logout">🚪 Keluar</a>
    </aside>

    <main class="main">
      <header class="header">
        <div>
          <h1 class="title">Direktori Produk</h1>
          <p style="color:var(--text-dim);font-size:14px;">Kelola seluruh daftar produk dari semua tenant.</p>
        </div>
        <button class="btn" onclick="openAdd()">+ Tambah Produk</button>
      </header>

      <?php if ($message): ?><div class="alert">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>

      <!-- FILTER BAR -->
      <div class="card" style="margin-bottom:20px; padding:16px;">
        <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
          <div style="flex:2; min-width:200px;">
            <label style="display:block; font-size:11px; color:var(--text-dim); margin-bottom:4px; font-weight:700; text-transform:uppercase;">Cari Produk</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan nama produk...">
          </div>
          <div style="flex:1; min-width:150px;">
            <label style="display:block; font-size:11px; color:var(--text-dim); margin-bottom:4px; font-weight:700; text-transform:uppercase;">Filter Toko</label>
            <select name="shop_id" class="form-control" onchange="this.form.submit()">
              <option value="">-- Semua Toko --</option>
              <?php foreach ($shops as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $f_shop == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="flex:1; min-width:150px;">
            <label style="display:block; font-size:11px; color:var(--text-dim); margin-bottom:4px; font-weight:700; text-transform:uppercase;">Filter Kategori</label>
            <select name="kategori_id" class="form-control" onchange="this.form.submit()">
              <option value="">-- Semua Kategori --</option>
              <?php foreach ($all_kategori as $k): ?>
                <?php if (!$f_shop || $k['shop_id'] == $f_shop): ?>
                  <option value="<?= $k['id'] ?>" <?= $f_kat == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama']) ?> (<?= htmlspecialchars($k['shop_name']) ?>)</option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex; gap:8px;">
            <button type="submit" class="btn">Cari</button>
            <a href="products.php" class="btn" style="background:var(--surface2); color:var(--text); border:1px solid var(--border); text-decoration:none; display:flex; align-items:center;">Reset</a>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Gambar</th>
                <th>Nama Produk</th>
                <th>Toko Pemilik</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($menus as $m): ?>
              <tr>
                <td>
                  <?php 
                    $img_src = $m['gambar_url'];
                    if ($img_src && !filter_var($img_src, FILTER_VALIDATE_URL)) {
                        $img_src = '../' . $img_src;
                    }
                  ?>
                  <?php if($img_src): ?>
                    <img src="<?= htmlspecialchars($img_src) ?>" style="width:40px; height:40px; object-fit:cover; border-radius:8px; border:1px solid var(--border);" onerror="this.src='../assets/default_menu.png'">
                  <?php else: ?>
                    <div style="width:40px; height:40px; background:var(--surface2); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--text-dim); font-size:16px;">🍔</div>
                  <?php endif; ?>
                </td>
                <td style="font-weight:bold; color:var(--cream);"><?= htmlspecialchars($m['nama']) ?></td>
                <td><span style="color:var(--gold); font-size:12px; font-weight:600;"><?= htmlspecialchars($m['shop_name']) ?></span></td>
                <td><?= htmlspecialchars($m['kategori_nama'] ?: '-') ?></td>
                <td><?= rupiah($m['harga']) ?></td>
                <td><?= $m['stok'] ? 'Tersedia' : 'Habis' ?></td>
                <td>
                  <div style="display:flex; gap:8px;">
                    <button class="btn-tool" onclick='openEdit(<?= json_encode($m) ?>)'>✏️</button>
                    <form method="POST" onsubmit="return confirm('Hapus produk ini?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
          <?php 
            $query_params = $_GET; 
            for ($i = 1; $i <= $total_pages; $i++): 
              $query_params['page'] = $i;
              $page_url = '?' . http_build_query($query_params);
          ?>
            <a href="<?= $page_url ?>" class="page-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- MODAL ADD/EDIT -->
  <div class="modal-overlay" id="productModal">
    <div class="modal">
      <h2 id="modalTitle" style="font-family:'Playfair Display', serif; color:var(--gold); margin-bottom:24px;">Tambah Produk Baru</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" id="modalAction" value="add">
        <input type="hidden" name="id" id="productId">
        
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label>Toko Pemilik</label>
            <select name="shop_id" id="formShop" class="form-control" required onchange="filterKategori(this.value)">
              <option value="">-- Pilih Toko --</option>
              <?php foreach ($shops as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Kategori</label>
            <select name="kategori_id" id="formKategori" class="form-control" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($all_kategori as $k): ?>
                <option value="<?= $k['id'] ?>" data-shop="<?= $k['shop_id'] ?>"><?= htmlspecialchars($k['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Nama Produk</label>
          <input type="text" name="nama" id="formNama" class="form-control" required placeholder="Nasi Goreng Spesial">
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div class="form-group">
            <label>Harga (Rp)</label>
            <input type="number" name="harga" id="formHarga" class="form-control" required placeholder="25000">
          </div>
          <div class="form-group">
            <label>Stok</label>
            <select name="stok" id="formStok" class="form-control">
              <option value="1">Tersedia</option>
              <option value="0">Habis</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Gambar Produk (Upload / URL)</label>
          <div style="display:flex; gap:12px; align-items:center;">
            <input type="file" name="gambar_file" id="formFile" class="form-control" accept="image/*" style="flex:1;">
            <span style="color:var(--text-dim); font-size:12px;">atau</span>
            <input type="url" name="gambar_url" id="formGambar" class="form-control" placeholder="https://..." style="flex:1;">
          </div>
          <small style="color:var(--text-dim); font-size:11px; margin-top:4px; display:block;">Pilih salah satu. Upload file akan diprioritaskan.</small>
        </div>

        <div class="form-group">
          <label>Deskripsi</label>
          <textarea name="deskripsi" id="formDeskripsi" class="form-control" rows="3" placeholder="Penjelasan singkat produk..."></textarea>
        </div>

        <div style="display:flex; gap:12px; margin-top:32px;">
          <button type="button" class="btn" style="background:transparent; border:1px solid var(--border); color:var(--text-dim); flex:1;" onclick="closeModal()">Batal</button>
          <button type="submit" class="btn" style="flex:2;">SIMPAN PRODUK</button>
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
      document.getElementById('modalTitle').textContent = 'Tambah Produk Baru';
      document.getElementById('modalAction').value = 'add';
      document.getElementById('productId').value = '';
      document.getElementById('formShop').value = '';
      document.getElementById('formKategori').value = '';
      document.getElementById('formNama').value = '';
      document.getElementById('formHarga').value = '';
      document.getElementById('formStok').value = '1';
      document.getElementById('formGambar').value = '';
      document.getElementById('formDeskripsi').value = '';
      document.getElementById('productModal').style.display = 'flex';
      filterKategori('');
    }

    function openEdit(data) {
      document.getElementById('modalTitle').textContent = 'Ubah Data Produk';
      document.getElementById('modalAction').value = 'edit';
      document.getElementById('productId').value = data.id;
      document.getElementById('formShop').value = data.shop_id;
      filterKategori(data.shop_id);
      document.getElementById('formKategori').value = data.kategori_id;
      document.getElementById('formNama').value = data.nama;
      document.getElementById('formHarga').value = data.harga;
      document.getElementById('formStok').value = data.stok;
      document.getElementById('formGambar').value = data.gambar_url;
      document.getElementById('formDeskripsi').value = data.deskripsi;
      document.getElementById('productModal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('productModal').style.display = 'none';
    }

    function filterKategori(shopId) {
      const select = document.getElementById('formKategori');
      const options = select.querySelectorAll('option');
      options.forEach(opt => {
        if (!opt.value) return;
        if (!shopId || opt.dataset.shop == shopId) {
          opt.style.display = 'block';
        } else {
          opt.style.display = 'none';
        }
      });
      if (shopId) select.value = '';
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
