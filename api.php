<?php
require_once 'config.php';
check_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'simpan_transaksi') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
        echo json_encode(['ok' => false, 'msg' => 'Data item tidak valid']);
        exit;
    }

    $pdo = get_db();
    $user_id = $_SESSION['user_id'];
    $diskon = (float)($input['diskon'] ?? 0);
    $bayar = (float)($input['bayar'] ?? 0);
    $metode = in_array($input['metode'], ['cash', 'qris', 'transfer']) ? $input['metode'] : 'cash';

    try {
        $pdo->beginTransaction();

        $kodeTrx = 'TRX' . date('YmdHis') . rand(100, 999);
        $subtotal = 0;
        $detailItems = [];

        // 1. Validasi stok & hitung subtotal (dengan lock FOR UPDATE)
        foreach ($input['items'] as $item) {
            $stmt = $pdo->prepare("SELECT id, nama, harga_jual, stok FROM produk WHERE id = ? FOR UPDATE");
            $stmt->execute([$item['id']]);
            $produk = $stmt->fetch();

            if (!$produk) {
                throw new Exception("Produk ID {$item['id']} tidak ditemukan");
            }
            if ($produk['stok'] < $item['qty']) {
                throw new Exception("Stok {$produk['nama']} tidak mencukupi (tersisa: {$produk['stok']})");
            }

            $itemSubtotal = $produk['harga_jual'] * $item['qty'];
            $subtotal += $itemSubtotal;
            $detailItems[] = [
                'produk_id' => $produk['id'],
                'nama_produk' => $produk['nama'],
                'harga' => $produk['harga_jual'],
                'qty' => $item['qty'],
                'subtotal' => $itemSubtotal
            ];
        }

        $total = max(0, $subtotal - $diskon);
        $kembalian = max(0, $bayar - $total);

        // 2. Insert Transaksi
        $stmt = $pdo->prepare("INSERT INTO transaksi (kode, user_id, subtotal, diskon, total, bayar, kembalian, metode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kodeTrx, $user_id, $subtotal, $diskon, $total, $bayar, $kembalian, $metode]);
        $transaksiId = $pdo->lastInsertId();

        // 3. Insert Detail & Update Stok
        $stmtDetail = $pdo->prepare("INSERT INTO transaksi_detail (transaksi_id, produk_id, nama_produk, harga, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtUpdateStok = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");

        foreach ($detailItems as $detail) {
            $stmtDetail->execute([
                $transaksiId, $detail['produk_id'], $detail['nama_produk'], 
                $detail['harga'], $detail['qty'], $detail['subtotal']
            ]);
            $stmtUpdateStok->execute([$detail['qty'], $detail['produk_id']]);
        }

        $pdo->commit();

        echo json_encode([
            'ok' => true,
            'id' => $transaksiId,
            'kode' => $kodeTrx,
            'total' => $total,
            'bayar' => $bayar,
            'kembalian' => $kembalian
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
} else {
    echo json_encode(['ok' => false, 'msg' => 'Method tidak diizinkan']);
}