<?php
// ============================================================
// addmenu.php — Halaman Tambah Menu WarungKu
// ============================================================
require_once __DIR__ . '/config.php';

$db_error = null;
$success_msg = null;
$categories = [];

try {
    $pdo = getDB();
    
    // Ambil daftar kategori untuk opsi dropdown
    $stmt = $pdo->query("SELECT id, nama FROM kategori ORDER BY urutan, id");
    $categories = $stmt->fetchAll();

    // Proses Form Submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nama = trim($_POST['nama'] ?? '');
        $kategori_id = (int)($_POST['kategori_id'] ?? 0);
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = (int)($_POST['harga'] ?? 0);
        $gambar_url = trim($_POST['gambar_url'] ?? '');
        $stok = isset($_POST['stok']) ? 1 : 0;

        // Cek Upload Gambar
        if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['gambar_file']['tmp_name'];
            $name = basename($_FILES['gambar_file']['name']);
            $name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name); // bersihkan spasi & karakter aneh
            $new_name = time() . '_' . $name;
            $destination = __DIR__ . '/foodpic/' . $new_name;
            
            if (move_uploaded_file($tmp_name, $destination)) {
                // Prioritaskan upload gambar lokal
                $gambar_url = 'foodpic/' . $new_name;
            } else {
                $db_error = "Gagal mengupload gambar lokal.";
            }
        }

        // Validasi sederhana
        if (empty($nama) || empty($kategori_id) || $harga <= 0) {
            if (!$db_error) $db_error = "Nama, Kategori, dan Harga (lebih dari 0) wajib diisi.";
        } else {
            // Insert ke tabel menu
            $stmtInsert = $pdo->prepare("
                INSERT INTO menu (kategori_id, nama, deskripsi, harga, gambar_url, stok) 
                VALUES (:kategori_id, :nama, :deskripsi, :harga, :gambar_url, :stok)
            ");
            
            $stmtInsert->execute([
                ':kategori_id' => $kategori_id,
                ':nama' => $nama,
                ':deskripsi' => $deskripsi,
                ':harga' => $harga,
                ':gambar_url' => $gambar_url,
                ':stok' => $stok
            ]);
            
            $success_msg = "Menu '$nama' berhasil ditambahkan ke database!";
        }
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Menu — WarungKu</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0f0e0b;
    --surface: #1a1814;
    --surface2: #231f1a;
    --surface3: #2d2820;
    --gold: #d4a853;
    --gold-light: #e8c47a;
    --gold-dim: rgba(212,168,83,0.12);
    --cream: #f5edd8;
    --cream-dim: rgba(245,237,216,0.6);
    --red: #e05252;
    --green: #4caf7d;
    --text: #f0e8d5;
    --text-dim: #8a7f6e;
    --border: rgba(212,168,83,0.15);
    --radius: 14px;
  }

  [data-theme="light"] {
    --bg: #fdfaf6;
    --surface: #ffffff;
    --surface2: #f4efeb;
    --surface3: #e8e1d7;
    --gold: #b38222;
    --gold-light: #c2902f;
    --gold-dim: rgba(179, 130, 34, 0.15);
    --cream: #1a1814;
    --cream-dim: rgba(26, 24, 20, 0.7);
    --red: #d33c3c;
    --green: #2c8558;
    --text: #3c3730;
    --text-dim: #7a7265;
    --border: rgba(179, 130, 34, 0.25);
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
  }

  /* ── TOP NAV ── */
  .topbar {
    position: sticky; top: 0; z-index: 100;
    background: rgba(15,14,11,0.92);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 68px;
    display: flex; align-items: center; justify-content: space-between;
  }
  .brand {
    font-family: 'Playfair Display', serif;
    font-size: 24px; font-weight: 900;
    color: var(--gold);
    letter-spacing: -0.5px;
    text-decoration: none;
  }
  .brand span { color: var(--cream); }
  
  .nav-back {
    display: flex; align-items: center; gap: 8px;
    color: var(--text-dim);
    font-size: 13px; text-decoration: none;
    padding: 8px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    transition: all 0.2s;
  }
  .nav-back:hover { border-color: var(--gold); color: var(--gold); }

  /* ── MAIN CONTENT ── */
  .container {
    max-width: 680px;
    margin: 0 auto;
    padding: 40px 24px 80px;
  }

  .page-title {
    font-family: 'Playfair Display', serif;
    font-size: 32px; font-weight: 700;
    color: var(--cream);
    margin-bottom: 6px;
  }
  .page-subtitle {
    color: var(--text-dim);
    font-size: 14px;
    margin-bottom: 32px;
  }

  /* ── ALERTS ── */
  .alert {
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 24px;
    font-size: 14px; font-weight: 500;
    display: flex; align-items: center; gap: 12px;
  }
  .alert-error {
    background: rgba(224,82,82,0.1);
    border: 1px solid rgba(224,82,82,0.3);
    color: #ff8a8a;
  }
  .alert-success {
    background: rgba(76,175,125,0.1);
    border: 1px solid rgba(76,175,125,0.3);
    color: #a5dfc1;
  }

  /* ── FORM ── */
  .form-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 32px;
  }

  .form-group {
    margin-bottom: 24px;
  }
  .form-group:last-child { margin-bottom: 0; }

  label {
    display: block;
    font-size: 13px; font-weight: 600;
    color: var(--cream);
    margin-bottom: 8px;
    letter-spacing: 0.5px;
  }
  
  .required { color: var(--red); }

  input[type="text"], input[type="number"], input[type="url"], select, textarea {
    width: 100%;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 14px 16px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    transition: all 0.2s;
  }
  input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(212,168,83,0.1);
  }
  textarea {
    resize: vertical;
    min-height: 100px;
  }

  input[type="file"] {
    width: 100%;
    background: var(--surface2);
    border: 1px dashed var(--border);
    border-radius: 10px;
    padding: 14px 16px;
    color: var(--text-dim);
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    transition: all 0.2s;
    cursor: pointer;
  }
  input[type="file"]:hover {
    border-color: var(--gold);
    color: var(--cream);
  }
  input[type="file"]::file-selector-button {
    background: var(--surface3);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 6px 12px;
    color: var(--cream);
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 600;
    margin-right: 12px;
    transition: all 0.2s;
  }
  input[type="file"]::file-selector-button:hover {
    background: var(--gold);
    color: var(--bg);
    border-color: var(--gold);
  }
  .input-hint {
    font-size: 11px;
    color: var(--text-dim);
    margin-top: 6px;
    display: block;
  }
  .or-divider {
    display: flex; align-items: center; text-align: center;
    color: var(--text-dim); font-size: 11px; font-weight: 600; text-transform: uppercase;
    margin: 16px 0;
  }
  .or-divider::before, .or-divider::after {
    content: ''; flex: 1; border-bottom: 1px solid var(--border);
  }
  .or-divider span { padding: 0 10px; }
  
  
  .checkbox-group {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
  }
  .checkbox-group input[type="checkbox"] {
    width: 18px; height: 18px;
    accent-color: var(--gold);
    cursor: pointer;
  }
  .checkbox-group label {
    margin-bottom: 0;
    cursor: pointer;
    font-weight: 500;
  }

  /* ── ACTIONS ── */
  .form-actions {
    margin-top: 32px;
    display: flex; gap: 16px;
    justify-content: flex-end;
  }
  .btn {
    padding: 12px 28px;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px; font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .btn-secondary {
    background: transparent;
    color: var(--text-dim);
    border: 1px solid var(--border);
  }
  .btn-secondary:hover {
    color: var(--cream);
    border-color: var(--text);
  }
  .btn-primary {
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--bg);
  }
  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(212,168,83,0.3);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .container { padding: 24px 16px 80px; }
    .topbar { padding: 0 16px; height: 60px; }
    .brand { font-size: 20px; }
    .page-title { font-size: 26px; }
    .form-card { padding: 20px; }
    .form-actions { flex-direction: column-reverse; gap: 12px; }
    .btn { width: 100%; }
    .or-divider { margin: 24px 0; }
  }

</style>
<script>
  const savedTheme = localStorage.getItem('warungku_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', savedTheme);
</script>
</head>
<body>

<!-- ═══════════════ TOPBAR ═══════════════ -->
<header class="topbar">
  <a href="index.php" class="brand">Warung<span>Ku</span></a>
  <div style="display:flex;gap:12px;align-items:center;">
    <button type="button" onclick="toggleTheme()" id="themeToggleBtn" style="background:transparent;border:1px solid var(--border);border-radius:8px;color:var(--text-dim);cursor:pointer;padding:8px 10px;font-size:14px;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-dim)'">☀️</button>
    <a href="index.php" class="nav-back">← Kembali ke Menu</a>
  </div>
</header>

<div class="container">
  <h1 class="page-title">Tambah Menu</h1>
  <p class="page-subtitle">Masukkan rincian hidangan baru ke dalam database WarungKu</p>

  <?php if ($db_error): ?>
    <div class="alert alert-error">
      ⚠️ <?= htmlspecialchars($db_error) ?>
    </div>
  <?php endif; ?>

  <?php if ($success_msg): ?>
    <div class="alert alert-success">
      ✅ <?= htmlspecialchars($success_msg) ?>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <form method="POST" action="addmenu.php" enctype="multipart/form-data">
      
      <div class="form-group">
        <label for="nama">Nama Menu <span class="required">*</span></label>
        <input type="text" id="nama" name="nama" placeholder="Contoh: Nasi Goreng Spesial" required>
      </div>

      <div class="form-group">
        <label for="kategori_id">Kategori <span class="required">*</span></label>
        <select id="kategori_id" name="kategori_id" required>
          <option value="" disabled selected>-- Pilih Kategori --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="harga">Harga (Rp) <span class="required">*</span></label>
        <input type="number" id="harga" name="harga" placeholder="Contoh: 25000" min="1" required>
      </div>

      <div class="form-group">
        <label for="deskripsi">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" placeholder="Jelaskan detail menu ini secara singkat..."></textarea>
      </div>

      <div class="form-group">
        <label>Gambar Menu</label>
        
        <input type="file" id="gambar_file" name="gambar_file" accept="image/*">
        <span class="input-hint">Unggah gambar dari komputermu.</span>
        
        <div class="or-divider"><span>ATAU</span></div>
        
        <input type="url" id="gambar_url" name="gambar_url" placeholder="Gunakan URL Gambar (https://...)">
        <span class="input-hint">Jika file diunggah, input URL ini akan diabaikan.</span>
      </div>

      <div class="form-group">
        <div class="checkbox-group">
          <input type="checkbox" id="stok" name="stok" value="1" checked>
          <label for="stok">Stok Tersedia (Aktif)</label>
        </div>
      </div>

      <div class="form-actions">
        <button type="reset" class="btn btn-secondary">Reset</button>
        <button type="submit" class="btn btn-primary">Simpan Menu</button>
      </div>
      
    </form>
  </div>
</div>

<script>
function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('warungku_theme', next);
  document.getElementById('themeToggleBtn').textContent = next === 'dark' ? '☀️' : '🌙';
}
document.getElementById('themeToggleBtn').textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';
</script>

</body>
</html>
