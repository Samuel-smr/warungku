<?php
// ============================================================
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    // Set lifetime to 1 year (31536000 seconds)
    $lifetime = 31536000;
    ini_set('session.gc_maxlifetime', $lifetime);
    ini_set('session.cookie_lifetime', $lifetime);
    
    // Set cookie parameters for modern PHP and better persistence
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params($lifetime, '/; samesite=Lax', '', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), true);
    }
    
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Sesuaikan dengan user MySQL kamu
define('DB_PASS', 'Infinix123?');            // Sesuaikan dengan password MySQL kamu
define('DB_NAME', 'warungku');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');
    
// ── Buat koneksi PDO ──
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Sinkronisasi timezone database dengan Asia/Jakarta
            $pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            // Tampilkan pesan error yang ramah
            http_response_code(500);
            die(json_encode([
                'error' => true,
                'message' => 'Koneksi database gagal: ' . $e->getMessage(),
                'hint' => 'Pastikan MySQL aktif dan config.php sudah disesuaikan.'
            ]));
        }
    }

    return $pdo;
}

// ── Helper: format Rupiah ──
function rupiah(int $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// ── Helper: response JSON ──
function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Helper: Log Aktivitas ──
function logActivity(int $userId, string $action, string $description = null)
{
    try {
        $pdo = getDB();
        $shopId = $_SESSION['shop_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, shop_id, action, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $shopId, $action, $description]);
    } catch (Exception $e) {
        // Silently fail logging if database error occurs to avoid breaking the app
    }
}

// ── CSRF Protection ──
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(?string $token): bool
{
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
