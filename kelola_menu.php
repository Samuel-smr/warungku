<?php
// ============================================================
// kelola_menu.php — Manajemen Menu (Optimized for 40+)
// ============================================================
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$shop_id = $_SESSION['shop_id'] ?? 0;

// ── HANDLE ACTIONS ──
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_menu') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama']);
        $harga = (int)$_POST['harga'];
        $kategori_id = (int)$_POST['kategori_id'];
        $gambar_url = trim($_POST['gambar_url']);
        $stok = (int)($_POST['stok'] ?? 1);

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE menu SET nama = :nama, harga = :harga, kategori_id = :kat, gambar_url = :img, stok = :stok WHERE id = :id AND shop_id = :shop_id");
            $stmt->execute(['nama' => $nama, 'harga' => $harga, 'kat' => $kategori_id, 'img' => $gambar_url, 'stok' => $stok, 'id' => $id, 'shop_id' => $shop_id]);
            $message = '✅ Menu berhasil diperbarui!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO menu (shop_id, kategori_id, nama, harga, gambar_url, stok) VALUES (:shop_id, :kat, :nama, :harga, :img, :stok)");
            $stmt->execute(['shop_id' => $shop_id, 'kat' => $kategori_id, 'nama' => $nama, 'harga' => $harga, 'img' => $gambar_url, 'stok' => $stok]);
            $message = '✨ Menu baru berhasil ditambahkan!';
        }
    } elseif ($action === 'delete_menu') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM menu WHERE id = :id AND shop_id = :shop_id");
        $stmt->execute(['id' => $id, 'shop_id' => $shop_id]);
        $message = '🗑 Menu berhasil dihapus!';
    } elseif ($action === 'toggle_stock') {
        $id = (int)$_POST['id'];
        $stok = (int)$_POST['stok'];
        $stmt = $pdo->prepare("UPDATE menu SET stok = :stok WHERE id = :id AND shop_id = :shop_id");
        $stmt->execute(['stok' => $stok, 'id' => $id, 'shop_id' => $shop_id]);
        $message = $stok === 1 ? '✅ Stok menu tersedia kembali!' : '🚫 Menu ditandai Habis!';
    } elseif ($action === 'save_kategori') {
        $nama_kat = trim($_POST['nama_kategori']);
        if ($nama_kat) {
            $stmt = $pdo->prepare("INSERT INTO kategori (shop_id, nama, urutan) VALUES (:shop_id, :nama, 0)");
            $stmt->execute(['shop_id' => $shop_id, 'nama' => $nama_kat]);
            $message = '📁 Kategori baru berhasil ditambahkan!';
        }
    }
}

// ── FETCH DATA ──
$categories = $pdo->prepare("SELECT * FROM kategori WHERE shop_id = :shop_id ORDER BY urutan");
$categories->execute(['shop_id' => $shop_id]);
$categories = $categories->fetchAll();

$menus = $pdo->prepare("
    SELECT m.*, k.nama as kategori_nama 
    FROM menu m 
    LEFT JOIN kategori k ON m.kategori_id = k.id 
    WHERE m.shop_id = :shop_id 
    ORDER BY k.urutan, m.nama
");
$menus->execute(['shop_id' => $shop_id]);
$menus = $menus->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu — WarungKu</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script>
        const savedTheme = localStorage.getItem('warungku_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <style>
        :root {
            --bg: #0f0e0b; --surface: #1a1814; --surface2: #231f1a;
            --gold: #d4a853; --cream: #f5edd8; --text: #f0e8d5; --text-dim: #8a7f6e;
            --border: rgba(212,168,83,0.25); --red: #ff5e5e; --green: #4caf7d;
        }
        [data-theme="light"] {
            --bg: #fdfaf6; --surface: #ffffff; --surface2: #f4efeb;
            --gold: #b38222; --cream: #1a1814; --text: #3c3730; --text-dim: #7a7265;
            --border: rgba(179, 130, 34, 0.25);
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'DM Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; font-size: 16px; transition: background 0.3s; }

        .topbar { height: 80px; padding: 0 40px; border-bottom: 2px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: var(--surface); position: sticky; top: 0; z-index: 100; }
        .brand { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 900; color: var(--gold); text-decoration: none; }
        .btn-back { color: var(--gold); text-decoration: none; font-size: 16px; font-weight: 700; padding: 10px 20px; border: 2px solid var(--gold); border-radius: 12px; transition: 0.2s; }
        .btn-back:hover { background: var(--gold); color: var(--bg); }

        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 48px; gap: 20px; flex-wrap: wrap; }
        .header h1 { font-family: 'Playfair Display', serif; font-size: 36px; color: var(--cream); }
        
        .btn-main { background: var(--gold); color: var(--bg); border: none; padding: 14px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.2s; font-size: 16px; box-shadow: 0 4px 0 #b38222; }
        .btn-main:active { transform: translateY(2px); box-shadow: none; }
        .btn-sec { background: transparent; border: 2px solid var(--border); color: var(--text); padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 16px; }

        .grid { display: flex; flex-direction: column; gap: 12px; }
        .card { background: var(--surface); border: 2px solid var(--border); border-radius: 16px; padding: 12px 20px; display: flex; gap: 20px; align-items: center; transition: 0.2s; }
        .card:hover { border-color: var(--gold); background: var(--surface2); }
        .card-img { width: 60px; height: 60px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border); }
        .card-info { flex: 1; display: flex; align-items: center; gap: 24px; }
        .card-main { flex: 1; min-width: 0; }
        .card-name { font-weight: 700; color: var(--cream); font-size: 16px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-cat { font-size: 11px; color: var(--text-dim); text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; }
        .card-price-wrapper { min-width: 120px; text-align: right; }
        .card-price { color: var(--gold); font-weight: 800; font-size: 16px; }
        
        .card-actions { display: flex; align-items: center; gap: 8px; border-left: 1px solid var(--border); padding-left: 20px; }
        .btn-tool { background: var(--surface2); border: 1px solid var(--border); color: var(--text); padding: 8px 12px; border-radius: 10px; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .btn-tool:hover { border-color: var(--gold); color: var(--gold); transform: translateY(-2px); }
        .btn-tool.del:hover { border-color: var(--red); color: var(--red); }

        /* MODAL */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 200; padding: 20px; }
        .modal { background: var(--surface); border: 2px solid var(--border); border-radius: 24px; width: 100%; max-width: 550px; padding: 40px; }
        .modal-title { font-family: 'Playfair Display', serif; font-size: 28px; color: var(--gold); margin-bottom: 32px; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-size: 14px; color: var(--text-dim); margin-bottom: 10px; font-weight: 700; text-transform: uppercase; }
        .form-control { width: 100%; background: var(--surface2); border: 2px solid var(--border); padding: 16px; border-radius: 12px; color: var(--text); font-size: 16px; font-weight: 500; }
        .form-control:focus { outline: none; border-color: var(--gold); }
        
        .modal-footer { display: flex; gap: 16px; margin-top: 40px; }
        .btn-modal { flex: 1; padding: 16px; border-radius: 12px; font-weight: 800; cursor: pointer; font-size: 16px; }
        .btn-modal.primary { background: var(--gold); color: var(--bg); border: none; }
        .btn-modal.cancel { background: transparent; border: 2px solid var(--border); color: var(--text-dim); }

        .msg { background: var(--green); color: #fff; padding: 16px 24px; border-radius: 12px; margin-bottom: 32px; font-size: 16px; font-weight: 700; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.4); }

        @media (max-width: 600px) {
            .header { margin-bottom: 24px; text-align: center; justify-content: center; }
            .header h1 { font-size: 28px; width: 100%; }
            .header p { width: 100%; }
            .btn-main, .btn-sec { width: 100%; padding: 14px; }
            .card { flex-wrap: wrap; padding: 16px; gap: 12px; }
            .card-img { width: 45px; height: 45px; border-radius: 10px; }
            .card-info { flex: 1; min-width: 0; gap: 5px; flex-direction: column; align-items: flex-start; }
            .card-main { width: 100%; }
            .card-name { white-space: normal; font-size: 15px; line-height: 1.3; }
            .card-cat { display: block; font-size: 10px; }
            .card-price-wrapper { min-width: 0; text-align: left; width: 100%; }
            .card-price { font-size: 15px; }
            .card-actions { width: 100%; border-left: none; padding-left: 0; border-top: 1px solid var(--border); padding-top: 12px; margin-top: 4px; justify-content: space-between; }
            .btn-tool { flex: 1; display: flex; justify-content: center; padding: 10px; }
            .modal { padding: 24px; border-radius: 0; height: 100%; max-width: none; overflow-y: auto; }
            .topbar { padding: 0 16px; height: 70px; }
        }
    </style>
</head>
<body>

    <header class="topbar">
        <a href="app.php" class="brand">Warung<span>Ku</span></a>
        <a href="app.php" class="btn-back">← Kembali</a>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="msg" id="msgBox"><?= $message ?></div>
            <script>setTimeout(() => document.getElementById('msgBox').style.display='none', 4000);</script>
        <?php endif; ?>

        <div class="header">
            <div>
                <h1>Kelola Menu</h1>
                <p style="color:var(--text-dim); font-size:16px;">Tambahkan atau ubah menu makanan & minuman</p>
            </div>
            <div style="display:flex; gap:16px; flex-wrap:wrap;">
                <button class="btn-sec" onclick="openModal('modalKategori')">📁 Tambah Kategori</button>
                <button class="btn-main" onclick="openMenuModal()">+ Tambah Menu Baru</button>
            </div>
        </div>

        <div class="grid">
            <?php foreach ($menus as $m): ?>
                    <div class="card" style="<?= $m['stok'] == 0 ? 'opacity:0.6; filter:grayscale(0.5);' : '' ?>">
                        <img class="card-img" src="<?= $m['gambar_url'] ?: 'assets/default_menu.png' ?>" onerror="this.onerror=null; this.src='assets/default_menu.png'">
                        <div class="card-info">
                            <div class="card-main">
                                <div class="card-name"><?= htmlspecialchars($m['nama']) ?> <?= $m['stok'] == 0 ? '<span style="color:var(--red); font-size:11px;">(HABIS)</span>' : '' ?></div>
                                <div class="card-cat"><?= htmlspecialchars($m['kategori_nama'] ?? 'Tanpa Kategori') ?></div>
                            </div>
                            <div class="card-price-wrapper">
                                <div class="card-price">Rp <?= number_format($m['harga'], 0, ',', '.') ?></div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_stock">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <input type="hidden" name="stok" value="<?= $m['stok'] == 1 ? 0 : 1 ?>">
                                <button type="submit" class="btn-tool" title="<?= $m['stok'] == 1 ? 'Tandai Habis' : 'Tandai Tersedia' ?>">
                                    <?= $m['stok'] == 1 ? '🚫' : '➕' ?>
                                </button>
                            </form>
                            <button class="btn-tool" onclick='editMenu(<?= json_encode($m) ?>)' title="Ubah Data">✏️</button>
                            <form method="POST" onsubmit="return confirm('Hapus menu ini?')">
                                <input type="hidden" name="action" value="delete_menu">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn-tool del" title="Hapus Permanen">🗑</button>
                            </form>
                        </div>
                    </div>
            <?php endforeach; ?>
            <?php if (empty($menus)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 80px 20px; color: var(--text-dim);">
                    <p style="font-size:20px;">Belum ada menu yang didaftarkan.<br>Gunakan tombol di atas untuk menambah menu pertama Anda.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL MENU -->
    <div class="modal-overlay" id="modalMenu">
        <div class="modal">
            <h2 class="modal-title" id="menuModalTitle">Tambah Menu Baru</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_menu">
                <input type="hidden" name="id" id="formId" value="0">
                
                <div class="form-group">
                    <label>Nama Makanan / Minuman</label>
                    <input type="text" name="nama" id="formNama" class="form-control" required placeholder="Misal: Nasi Goreng Gila">
                </div>
                
                <div class="form-group" style="display:flex; gap:20px;">
                    <div style="flex:1">
                        <label>Harga Jual (Rp)</label>
                        <input type="number" name="harga" id="formHarga" class="form-control" required placeholder="0">
                    </div>
                    <div style="flex:1">
                        <label>Kategori</label>
                        <select name="kategori_id" id="formKat" class="form-control" required>
                            <?php if(empty($categories)): ?>
                                <option value="" disabled selected>Tambah kategori dulu!</option>
                            <?php endif; ?>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Alamat Gambar (Link URL)</label>
                    <input type="text" name="gambar_url" id="formImg" class="form-control" placeholder="https://...">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal cancel" onclick="closeModal('modalMenu')">Batal</button>
                    <button type="submit" class="btn-modal primary">SIMPAN DATA</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KATEGORI -->
    <div class="modal-overlay" id="modalKategori">
        <div class="modal">
            <h2 class="modal-title">Tambah Kategori Baru</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_kategori">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control" required placeholder="Misal: Minuman Dingin">
                </div>
                
                <p style="font-size:14px; color:var(--text-dim); margin-bottom:12px; font-weight:700;">Kategori yang sudah ada:</p>
                <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:32px;">
                    <?php foreach($categories as $c): ?>
                        <span style="font-size:13px; background:var(--surface2); border:2px solid var(--border); padding:6px 14px; border-radius:100px; font-weight:600;"><?= htmlspecialchars($c['nama']) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal cancel" onclick="closeModal('modalKategori')">Tutup</button>
                    <button type="submit" class="btn-modal primary">TAMBAHKAN</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        function openMenuModal() {
            document.getElementById('menuModalTitle').textContent = 'Tambah Menu Baru';
            document.getElementById('formId').value = 0;
            document.getElementById('formNama').value = '';
            document.getElementById('formHarga').value = '';
            document.getElementById('formImg').value = '';
            openModal('modalMenu');
        }

        function editMenu(data) {
            document.getElementById('menuModalTitle').textContent = 'Ubah Data Menu';
            document.getElementById('formId').value = data.id;
            document.getElementById('formNama').value = data.nama;
            document.getElementById('formHarga').value = data.harga;
            document.getElementById('formKat').value = data.kategori_id;
            document.getElementById('formImg').value = data.gambar_url;
            openModal('modalMenu');
        }
    </script>
</body>
</html>
