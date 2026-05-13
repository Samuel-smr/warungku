<?php
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND role = 'admin' LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['shop_id'] = $user['shop_id'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Username tidak ditemukan atau Anda bukan Admin.';
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
  <title>Admin Login — WarungKu</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#0f0e0b;--surface:#1a1814;--surface2:#231f1a;
      --gold:#d4a853;--gold-light:#e8c47a;--gold-dim:rgba(212,168,83,.12);
      --cream:#f5edd8;--cream-dim:rgba(245,237,216,.6);
      --red:#e05252;--text:#f0e8d5;--text-dim:#8a7f6e;
      --border:rgba(212,168,83,.15);--radius:16px;
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
    }

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

    .badge-admin {
      display: inline-block;
      background: var(--red);
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 100px;
      vertical-align: middle;
      margin-left: 8px;
      letter-spacing: 1px;
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
      border-color: var(--red);
      box-shadow: 0 0 0 3px rgba(224,82,82,0.15);
    }

    .btn-submit {
      width: 100%;
      padding: 16px;
      background: var(--red);
      color: #fff;
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
      box-shadow: 0 12px 30px rgba(224,82,82,0.3);
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
  </style>
</head>
<body>

  <div class="login-card">
    <div class="brand">Warung<b>Ku</b> <span class="badge-admin">ADMIN</span></div>
    <div class="subtitle">Secure Super Admin Access</div>

    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Admin Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Superadmin ID" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-submit">Akses Dashboard</button>
    </form>
  </div>

</body>
</html>
