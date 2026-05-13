<?php
// ============================================================
// users.php — Manajemen User & Staff (Admin)
// ============================================================
require_once __DIR__ . '/../config.php';

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
        $username = trim($_POST['username']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $phone = trim($_POST['phone']);
        $shop_id = (int)$_POST['shop_id'];

        // Cek apakah toko sudah punya admin (Owner)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE shop_id = ? AND role = 'admin'");
        $stmtCheck->execute([$shop_id]);
        $hasAdmin = $stmtCheck->fetchColumn() > 0;
        
        $role = $hasAdmin ? 'kasir' : 'admin';

        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, nama_lengkap, phone, role, shop_id) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $pdo->beginTransaction();
            if ($stmt->execute([$username, $password, $nama_lengkap, $phone, $role, $shop_id])) {
                // Jika user pertama (Owner), update owner_name di tabel shops
                if (!$hasAdmin) {
                    $stmtUpdateShop = $pdo->prepare("UPDATE shops SET owner_name = ? WHERE id = ?");
                    $stmtUpdateShop->execute([$nama_lengkap, $shop_id]);
                }
                $pdo->commit();
                $message = "User berhasil ditambahkan sebagai " . strtoupper($role);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Gagal menambah user: Username mungkin sudah ada atau gangguan sistem.";
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        $shop_id = (int)$_POST['shop_id'];

        if (!empty($_POST['password'])) {
            $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, phone = ?, role = ?, shop_id = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $phone, $role, $shop_id, $password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, phone = ?, role = ?, shop_id = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $phone, $role, $shop_id, $id]);
        }
        $message = "Data user berhasil diperbarui.";
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ? AND role != 'superadmin'");
        if ($stmt->execute([$id])) {
            $message = "User berhasil dinonaktifkan (Soft Delete).";
        }
    }
}

// ── SEARCH & FILTER ──
$search = trim($_GET['search'] ?? '');
$filter_shop = $_GET['filter_shop'] ?? '';
$filter_role = $_GET['filter_role'] ?? '';

$where = "u.role IN ('admin', 'kasir') AND u.deleted_at IS NULL";
$params = [];

if ($search) {
    $where .= " AND (u.username LIKE ? OR u.nama_lengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_shop) {
    $where .= " AND u.shop_id = ?";
    $params[] = (int)$filter_shop;
}
if ($filter_role) {
    $where .= " AND u.role = ?";
    $params[] = $filter_role;
}

// ── PAGINATION ──
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $where");
$countStmt->execute($params);
$total_users = $countStmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// ── FETCH DATA ──
$sql = "
    SELECT u.*, s.name as shop_name,
           (SELECT SUM(total) FROM pesanan p WHERE p.nama_kasir = u.nama_lengkap AND p.shop_id = u.shop_id AND p.status = 'selesai') as total_sales,
           (SELECT COUNT(*) FROM pesanan p WHERE p.nama_kasir = u.nama_lengkap AND p.shop_id = u.shop_id AND p.status = 'selesai') as total_orders
    FROM users u 
    LEFT JOIN shops s ON u.shop_id = s.id 
    WHERE $where
    ORDER BY u.id DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$shops = $pdo->query("SELECT id, name FROM shops WHERE status = 'active' ORDER BY name")->fetchAll();
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
    
    .card { background: var(--surface); border: 1px solid var(--border); padding: 24px; border-radius: 12px; margin-bottom: 24px; }
    
    /* TABLE */
    .table-responsive { width: 100%; overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; min-width: 800px; }
    .table th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
    .table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
    
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .badge.admin { background: var(--gold-dim); color: var(--gold); border: 1px solid rgba(212,168,83,0.3); }
    .badge.kasir { background: rgba(245,237,216,0.05); color: var(--text-dim); border: 1px solid var(--border); }
    
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; color: var(--text-dim); margin-bottom: 6px; font-weight: 600; }
    .form-control { width: 100%; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; font-size: 14px; }
    
    .btn { padding: 10px 24px; background: var(--gold); color: var(--bg); border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn:hover { opacity: 0.9; }
    .btn-tool { padding: 6px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; cursor: pointer; }
    
    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 14px; background: rgba(76,175,125,0.1); color: var(--green); border: 1px solid var(--green); }
    .alert-error { background: rgba(224,82,82,0.1); color: var(--red); border-color: var(--red); }

    /* PASSWORD TOGGLE */
    .password-wrapper { position: relative; }
    .password-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: var(--text-dim); cursor: pointer;
      font-size: 16px; padding: 4px; display: flex; align-items: center; justify-content: center;
    }
    .password-toggle:hover { color: var(--gold); }

    /* MODAL */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
    .modal { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 500px; padding: 32px; }

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
        <a href="users.php" class="active">👥 Manajemen User</a>
        <a href="categories.php">📁 Manajemen Kategori</a>
        <a href="products.php">🍔 Manajemen Produk</a>
        <a href="subscriptions.php">💎 Pembelian Token</a>
        <a href="logs.php">📜 Log Aktivitas</a>
      </nav>
      <a href="logout.php" class="logout">🚪 Keluar</a>
    </aside>

    <main class="main">
      <header class="header">
        <h1 class="title">Manajemen User</h1>
        <p style="color:var(--text-dim);font-size:14px;">Kelola akun Owner Toko dan Kasir.</p>
      </header>

      <?php if ($message): ?><div class="alert">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div class="card">
        <h3 style="margin-bottom: 20px; font-family:'Playfair Display', serif; color:var(--gold);">Tambah User Baru</h3>
        <form method="POST" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
          <input type="hidden" name="action" value="add">
          <div class="form-group" style="flex:1; min-width:200px;">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required placeholder="admin_kantin">
          </div>
          <div class="form-group" style="flex:1; min-width:200px;">
            <label>Password</label>
            <div class="password-wrapper">
              <input type="password" name="password" class="form-control" required placeholder="••••••••">
              <button type="button" class="password-toggle" onclick="togglePassword(this)">👁️</button>
            </div>
          </div>
          <div class="form-group" style="flex:1; min-width:200px;">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" required placeholder="Budi Santoso">
          </div>
          <div class="form-group" style="flex:1; min-width:200px;">
            <label>WhatsApp (628xxx)</label>
            <input type="text" name="phone" class="form-control" placeholder="62812345678">
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
        <p style="font-size: 11px; color: var(--text-dim); margin-top: 8px;">* Akun pertama di setiap toko akan otomatis menjadi <b>OWNER (ADMIN)</b>, akun selanjutnya menjadi <b>KASIR</b>.</p>
      </div>

      <div class="card" style="padding: 16px;">
        <form method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
          <input type="text" name="search" class="form-control" placeholder="Cari username / nama..." value="<?= htmlspecialchars($search) ?>" style="flex:2; min-width:200px;">
          
          <select name="filter_shop" class="form-control" style="flex:1; min-width:150px;">
            <option value="">-- Semua Toko --</option>
            <?php foreach ($shops as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $filter_shop == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>

          <select name="filter_role" class="form-control" style="flex:1; min-width:120px;">
            <option value="">-- Semua Role --</option>
            <option value="admin" <?= $filter_role == 'admin' ? 'selected' : '' ?>>Owner (Admin)</option>
            <option value="kasir" <?= $filter_role == 'kasir' ? 'selected' : '' ?>>Kasir</option>
          </select>

          <button type="submit" class="btn" style="padding: 10px 20px;">🔍 Filter</button>
          <?php if($search || $filter_shop || $filter_role): ?>
            <a href="users.php" class="btn" style="background:var(--surface2); color:var(--text-dim); border:1px solid var(--border); text-decoration:none; padding: 10px 20px;">Reset</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Username</th>
                <th>Kontak</th>
                <th>Last Login</th>
                <th>Nama Lengkap</th>
                <th>Role</th>
                <th>Toko</th>
                <th>Performance</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td style="font-weight:bold; color:var(--cream);">
                  <?= htmlspecialchars($u['username']) ?>
                  <div style="font-size:10px; color:var(--text-dim)"><?= $u['phone'] ?: '-' ?></div>
                </td>
                <td>
                  <?php if($u['phone']): ?>
                    <button class="btn-tool" style="color:#25D366; border-color:rgba(37,211,102,0.3)" onclick="sendWA('<?= $u['phone'] ?>', '<?= $u['username'] ?>', '<?= $u['nama_lengkap'] ?>')" title="Kirim Kredensial via WA">
                      <svg style="width:16px; height:16px; fill:currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.411-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </button>
                  <?php else: ?>
                    <span style="color:var(--text-dim); font-size:11px;">N/A</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="font-size:11px; color:var(--cream);"><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Belum pernah' ?></div>
                </td>
                <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                <td><span class="badge <?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                <td><?= htmlspecialchars($u['shop_name'] ?: '-') ?></td>
                <td>
                  <div style="font-size: 13px; font-weight: 700; color: var(--gold);"><?= rupiah($u['total_sales'] ?: 0) ?></div>
                  <div style="font-size: 10px; color: var(--text-dim);"><?= (int)$u['total_orders'] ?> Transaksi</div>
                </td>
                <td>
                  <div style="display:flex; gap:8px;">
                    <button class="btn-tool" onclick='openEdit(<?= json_encode($u) ?>)'>✏️</button>
                    <form method="POST" onsubmit="return confirm('Hapus user ini?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
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
            <?php 
              $query = $_GET;
              $query['page'] = $i;
              $url = "?" . http_build_query($query);
            ?>
            <a href="<?= $url ?>" class="page-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- MODAL EDIT -->
  <div class="modal-overlay" id="editModal">
    <div class="modal">
      <h2 style="font-family:'Playfair Display', serif; color:var(--gold); margin-bottom:24px;">Ubah Data User</h2>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editId">
        
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input type="text" name="nama_lengkap" id="editNama" class="form-control" required>
        </div>

        <div class="form-group">
          <label>WhatsApp (Format: 628xxx)</label>
          <input type="text" name="phone" id="editPhone" class="form-control" placeholder="62812345678">
        </div>
        
        <div class="form-group">
          <label>Password Baru (Kosongkan jika tidak diubah)</label>
          <div class="password-wrapper">
            <input type="password" name="password" class="form-control" placeholder="••••••••">
            <button type="button" class="password-toggle" onclick="togglePassword(this)">👁️</button>
          </div>
        </div>

        <div class="form-group">
          <label>Role</label>
          <select name="role" id="editRole" class="form-control">
            <option value="admin">OWNER (ADMIN)</option>
            <option value="kasir">KASIR</option>
          </select>
        </div>

        <div class="form-group">
          <label>Pindah ke Toko</label>
          <select name="shop_id" id="editShop" class="form-control">
            <?php foreach ($shops as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
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
      document.getElementById('editNama').value = data.nama_lengkap;
      document.getElementById('editPhone').value = data.phone;
      document.getElementById('editRole').value = data.role;
      document.getElementById('editShop').value = data.shop_id;
      document.getElementById('editModal').style.display = 'flex';
    }

    function sendWA(phone, username, name) {
      const msg = `Halo ${name},\n\nBerikut adalah kredensial akun WarungKu Anda:\nUsername: *${username}*\nLink Login: ${window.location.origin}/login.php\n\nSilakan gunakan username tersebut untuk login. Jika Anda lupa password, admin telah meresetnya sesuai permintaan.`;
      const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
      window.open(url, '_blank');
    }
    function closeEdit() {
      document.getElementById('editModal').style.display = 'none';
    }

    function togglePassword(btn) {
      const input = btn.previousElementSibling;
      if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
      } else {
        input.type = 'password';
        btn.textContent = '👁️';
      }
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
