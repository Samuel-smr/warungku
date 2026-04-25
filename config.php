<?php
// ============================================================
// config.php — Konfigurasi Database WarungKu
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Sesuaikan dengan user MySQL kamu
define('DB_PASS', '');            // Sesuaikan dengan password MySQL kamu
define('DB_NAME', 'warungku');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// ── Buat koneksi PDO ──
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
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
function rupiah(int $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// ── Helper: response JSON ──
function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
