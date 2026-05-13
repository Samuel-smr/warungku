<?php
require_once __DIR__ . '/config.php';

// Jika sudah login, lempar ke app.php
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
        header('Location: admin/index.php');
    } else {
        header('Location: app.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND role IN ('admin', 'kasir') LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'] ?? 'seller';
                $_SESSION['shop_id'] = $user['shop_id'] ?? null;
                
                // Update last_login
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                logActivity($user['id'], 'LOGIN', 'User login via shop portal');
                
                header('Location: app.php');
                exit;
            } else {
                $error = 'Username atau password salah, atau Anda tidak memiliki akses ke sini.';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    } else {
        $error = 'Mohon isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — WarungKu</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="logo.png">
  <script>
    const savedTheme = localStorage.getItem('warungku_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
  </script>
  <style>
    :root{
      --bg:#0f0e0b;--surface:#1a1814;--surface2:#231f1a;
      --gold:#d4a853;--gold-light:#e8c47a;--gold-dim:rgba(212,168,83,.12);
      --cream:#f5edd8;--cream-dim:rgba(245,237,216,.6);
      --red:#e05252;--text:#f0e8d5;--text-dim:#8a7f6e;
      --border:rgba(212,168,83,.15);--radius:16px;
    }

    [data-theme="light"] {
      --bg: #fdfaf6;
      --surface: #ffffff;
      --surface2: #f4efeb;
      --gold: #b38222;
      --gold-light: #c2902f;
      --gold-dim: rgba(179, 130, 34, 0.15);
      --cream: #1a1814;
      --cream-dim: rgba(26, 24, 20, 0.7);
      --red: #d33c3c;
      --text: #3c3730;
      --text-dim: #7a7265;
      --border: rgba(179, 130, 34, 0.25);
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:var(--bg); color:var(--text); font-family:'DM Sans',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; padding:20px; }
    
    .login-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      width: 100%;
      max-width: 400px;
      padding: 40px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.5);
      position: relative;
      z-index: 10;
    }
    [data-theme="light"] .login-card { box-shadow: 0 20px 60px rgba(0,0,0,0.08); }

    .brand {
      font-family: 'Playfair Display', serif;
      font-size: 32px;
      font-weight: 900;
      color: var(--gold);
      text-align: center;
      margin-bottom: 8px;
    }
    .brand b { color: var(--cream); }
    
    .subtitle {
      text-align: center;
      color: var(--text-dim);
      font-size: 14px;
      margin-bottom: 32px;
    }

    .form-group { margin-bottom: 20px; }
    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--cream-dim);
      margin-bottom: 8px;
    }
    .form-control {
      width: 100%;
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 15px;
      padding: 14px 16px;
      border-radius: 12px;
      transition: all 0.2s;
    }
    .form-control:focus {
      outline: none;
      border-color: var(--gold);
      box-shadow: 0 0 0 3px var(--gold-dim);
    }

    .btn-submit {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      color: var(--bg);
      border: none;
      border-radius: 12px;
      font-family: 'DM Sans', sans-serif;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 10px;
    }
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(212,168,83,0.3);
    }

    .error-msg {
      background: rgba(224,82,82,0.1);
      border: 1px solid rgba(224,82,82,0.3);
      color: var(--red);
      padding: 12px;
      border-radius: 8px;
      font-size: 13px;
      margin-bottom: 20px;
      text-align: center;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 24px;
      color: var(--text-dim);
      text-decoration: none;
      font-size: 13px;
      transition: color 0.2s;
    }
    .back-link:hover { color: var(--gold); }

    .theme-toggle {
      position: fixed;
      top: 20px;
      right: 20px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      color: var(--text-dim);
      cursor: pointer;
      padding: 10px;
      font-size: 18px;
      transition: all 0.2s;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 100;
    }
    .theme-toggle:hover {
      color: var(--gold);
      transform: translateY(-2px);
    }

    /* PASSWORD TOGGLE */
    .password-wrapper { position: relative; }
    .password-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: var(--text-dim); cursor: pointer;
      font-size: 18px; padding: 4px; display: flex; align-items: center; justify-content: center;
      z-index: 5;
    }
    .password-toggle:hover { color: var(--gold); }
  </style>
</head>
<body>

  <button class="theme-toggle" onclick="toggleTheme()" id="themeToggleBtn">☀️</button>

  <div class="login-card">
    <div class="brand">Warung<b>Ku</b></div>
    <div class="subtitle">Masuk untuk mengelola pesanan</div>

    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrapper">
          <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
          <button type="button" class="password-toggle" onclick="togglePassword(this)">👁️</button>
        </div>
      </div>
      <button type="submit" class="btn-submit">Masuk →</button>
    </form>

    <a href="index.php" class="back-link">← Kembali ke Halaman Utama</a>
  </div>

  <script>
    function toggleTheme() {
      const current = document.documentElement.getAttribute('data-theme');
      const next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('warungku_theme', next);
      updateThemeIcon(next);
    }

    function updateThemeIcon(theme) {
      const icon = theme === 'dark' ? '☀️' : '🌙';
      document.getElementById('themeToggleBtn').textContent = icon;
    }

    // Set initial icon
    updateThemeIcon(document.documentElement.getAttribute('data-theme'));

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
  </script>

</body>
</html>
