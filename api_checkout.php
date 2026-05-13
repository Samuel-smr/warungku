<?php
// ============================================================
// api_checkout.php — Endpoint POST: simpan pesanan ke DB
// Dipanggil via fetch() dari index.php saat konfirmasi checkout
// ============================================================

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Proteksi API
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => true, 'message' => 'Unauthorized'], 401);
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => true, 'message' => 'Method not allowed'], 405);
}

// Ambil & validasi body JSON
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['kode_pesanan'], $body['items']) || !is_array($body['items']) || count($body['items']) === 0) {
    jsonResponse(['error' => true, 'message' => 'Data tidak valid atau items kosong'], 422);
}

$kodePesanan = trim($body['kode_pesanan']);
$namaKasir = trim($body['nama_kasir'] ?? 'Admin');
$catatan = trim($body['catatan'] ?? '');

// Hitung ulang di server (jangan percaya hitungan client)
$pdo = getDB();
$items = $body['items'];
$menuIds = array_column($items, 'menu_id');

// Ambil data menu dari DB untuk validasi harga
$placeholders = implode(',', array_fill(0, count($menuIds), '?'));
$stmt = $pdo->prepare(
    "SELECT id, nama, harga, gambar_url, stok FROM menu WHERE id IN ($placeholders)"
);
$stmt->execute($menuIds);
$menuRows = $stmt->fetchAll();
$menuMap = array_column($menuRows, null, 'id'); // indexed by id

// Validasi semua item ada di DB dan masih ada stok
foreach ($items as $item) {
    $mid = (int) ($item['menu_id'] ?? 0);
    if (!isset($menuMap[$mid])) {
        jsonResponse(['error' => true, 'message' => "Menu ID $mid tidak ditemukan"], 422);
    }
    if ($menuMap[$mid]['stok'] == 0) {
        jsonResponse(['error' => true, 'message' => "Menu '{$menuMap[$mid]['nama']}' sedang habis"], 422);
    }
    if ((int) ($item['jumlah'] ?? 0) < 1) {
        jsonResponse(['error' => true, 'message' => "Jumlah item tidak valid"], 422);
    }
}

// Hitung subtotal, pajak, total
$subtotal = 0;
foreach ($items as $item) {
    $mid = (int) $item['menu_id'];
    $jumlah = (int) $item['jumlah'];
    $subtotal += $menuMap[$mid]['harga'] * $jumlah;
}
$pajak = (int) round($subtotal * 0.1);
$total = $subtotal + $pajak;

// ── Transaksi DB ──
try {
    $pdo->beginTransaction();

    $shop_id = $_SESSION['shop_id'] ?? null;
    // 1. Insert pesanan (header)
    $stmtP = $pdo->prepare("
        INSERT INTO pesanan (kode_pesanan, shop_id, nama_kasir, subtotal, pajak, total, status, catatan)
        VALUES (:kode, :shop_id, :kasir, :subtotal, :pajak, :total, 'selesai', :catatan)
    ");
    $stmtP->execute([
        ':kode' => $kodePesanan,
        ':shop_id' => $shop_id,
        ':kasir' => $namaKasir,
        ':subtotal' => $subtotal,
        ':pajak' => $pajak,
        ':total' => $total,
        ':catatan' => $catatan ?: null,
    ]);
    $pesananId = (int) $pdo->lastInsertId();

    // 2. Insert detail_pesanan (line items)
    $stmtD = $pdo->prepare("
        INSERT INTO detail_pesanan (pesanan_id, menu_id, nama_menu, harga_satuan, gambar_url, jumlah, subtotal)
        VALUES (:pid, :mid, :nama, :harga, :gambar, :jumlah, :sub)
    ");
    foreach ($items as $item) {
        $mid = (int) $item['menu_id'];
        $jml = (int) $item['jumlah'];
        $harga = (int) $menuMap[$mid]['harga'];
        $stmtD->execute([
            ':pid' => $pesananId,
            ':mid' => $mid,
            ':nama' => $menuMap[$mid]['nama'],
            ':harga' => $harga,
            ':gambar' => $menuMap[$mid]['gambar_url'],
            ':jumlah' => $jml,
            ':sub' => $harga * $jml,
        ]);
    }

    $pdo->commit();

    jsonResponse([
        'error' => false,
        'message' => 'Pesanan berhasil disimpan',
        'pesanan_id' => $pesananId,
        'kode' => $kodePesanan,
        'total' => $total,
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    jsonResponse(['error' => true, 'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage()], 500);
}
