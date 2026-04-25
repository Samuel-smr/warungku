<?php
// ============================================================
// api_delete_pesanan.php — Endpoint POST untuk menghapus pesanan
// ============================================================

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => true, 'message' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['action'])) {
    jsonResponse(['error' => true, 'message' => 'Data tidak valid'], 422);
}

$pdo = getDB();

try {
    if ($body['action'] === 'clear') {
        // Hapus semua pesanan
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE detail_pesanan;");
        $pdo->exec("TRUNCATE TABLE pesanan;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        
        jsonResponse(['error' => false, 'message' => 'Semua pesanan berhasil dihapus']);
    } elseif ($body['action'] === 'delete' && isset($body['kode_pesanan'])) {
        // Hapus pesanan tertentu berdasarkan kode_pesanan
        $kode = trim($body['kode_pesanan']);
        
        $pdo->beginTransaction();
        
        // Cari ID pesanan
        $stmt = $pdo->prepare("SELECT id FROM pesanan WHERE kode_pesanan = :kode");
        $stmt->execute([':kode' => $kode]);
        $pesananId = $stmt->fetchColumn();
        
        if ($pesananId) {
            $pdo->prepare("DELETE FROM detail_pesanan WHERE pesanan_id = ?")->execute([$pesananId]);
            $pdo->prepare("DELETE FROM pesanan WHERE id = ?")->execute([$pesananId]);
        }
        
        $pdo->commit();
        
        jsonResponse(['error' => false, 'message' => 'Pesanan berhasil dihapus']);
    } else {
        jsonResponse(['error' => true, 'message' => 'Aksi tidak valid'], 400);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['error' => true, 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
}
