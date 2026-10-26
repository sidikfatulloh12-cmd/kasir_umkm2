<?php
require_once 'config.php';
check_auth();

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();

$stmt = $pdo->prepare("SELECT t.*, u.nama as kasir FROM transaksi t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    die("Transaksi tidak ditemukan");
}

$stmtDetail = $pdo->prepare("SELECT * FROM transaksi_detail WHERE transaksi_id = ?");
$stmtDetail->execute([$id]);
$details = $stmtDetail->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk <?= e($trx['kode']) ?></title>
    <style>
        @media print {
            @page { margin: 0; size: 80mm auto; }
            body { margin: 0; padding: 0; }
        }
        body {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 10px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .item-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .item-name { flex: 1; }
        .no-print { text-align: center; margin-top: 20px; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center bold" style="font-size: 16px;">TOKO UMKM MAJU JAYA</div>
    <div class="text-center">Jl. Contoh No. 123, Kota Anda<br>Telp: 0812-3456-7890</div>
    <div class="divider"></div>
    
    <div>Kode : <?= e($trx['kode']) ?></div>
    <div>Tgl   : <?= date('d/m/Y H:i', strtotime($trx['tanggal'])) ?></div>
    <div>Kasir : <?= e($trx['kasir'] ?? 'Unknown') ?></div>
    <div class="divider"></div>

    <?php foreach ($details as $d): ?>
    <div class="item-row">
        <span class="item-name"><?= e($d['nama_produk']) ?></span>
    </div>
    <div class="item-row" style="font-size: 11px; color: #333;">
        <span><?= $d['qty'] ?> x <?= number_format($d['harga'], 0, ',', '.') ?></span>
        <span><?= number_format($d['subtotal'], 0, ',', '.') ?></span>
    </div>
    <?php endforeach; ?>

    <div class="divider"></div>
    <div class="item-row bold">
        <span>Subtotal</span>
        <span><?= number_format($trx['subtotal'], 0, ',', '.') ?></span>
    </div>
    <?php if ($trx['diskon'] > 0): ?>
    <div class="item-row">
        <span>Diskon</span>
        <span>- <?= number_format($trx['diskon'], 0, ',', '.') ?></span>
    </div>
    <?php endif; ?>
    <div class="item-row bold" style="font-size: 14px; margin-top: 4px;">
        <span>TOTAL</span>
        <span><?= number_format($trx['total'], 0, ',', '.') ?></span>
    </div>
    <div class="item-row">
        <span>Bayar (<?= strtoupper($trx['metode']) ?>)</span>
        <span><?= number_format($trx['bayar'], 0, ',', '.') ?></span>
    </div>
    <div class="item-row">
        <span>Kembalian</span>
        <span><?= number_format($trx['kembalian'], 0, ',', '.') ?></span>
    </div>
    
    <div class="divider"></div>
    <div class="text-center">Terima kasih atas kunjungan Anda<br>Barang yang sudah dibeli tidak dapat ditukar</div>

    <div class="no-print">
        <button onclick="window.close()">Tutup</button>
        <button onclick="window.print()">Cetak Ulang</button>
    </div>
</body>
</html>