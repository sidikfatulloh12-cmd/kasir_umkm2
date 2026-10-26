<?php
require_once 'config.php';
check_auth();
if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

$pdo = get_db();
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');

// Stats
$stmtStats = $pdo->prepare("SELECT COUNT(id) as jumlah, SUM(total) as omzet FROM transaksi WHERE DATE(tanggal) BETWEEN ? AND ?");
$stmtStats->execute([$tanggal_mulai, $tanggal_selesai]);
$stats = $stmtStats->fetch();
$rata_rata = $stats['jumlah'] > 0 ? $stats['omzet'] / $stats['jumlah'] : 0;

// Top Produk
$stmtTop = $pdo->prepare("SELECT td.nama_produk, SUM(td.qty) as total_qty, SUM(td.subtotal) as total_omzet FROM transaksi_detail td JOIN transaksi t ON td.transaksi_id = t.id WHERE DATE(t.tanggal) BETWEEN ? AND ? GROUP BY td.produk_id ORDER BY total_qty DESC LIMIT 10");
$stmtTop->execute([$tanggal_mulai, $tanggal_selesai]);
$topProduk = $stmtTop->fetchAll();
$maxQty = $topProduk ? $topProduk[0]['total_qty'] : 1;

// Riwayat Transaksi
$stmtRiwayat = $pdo->prepare("SELECT t.*, u.nama as kasir FROM transaksi t LEFT JOIN users u ON t.user_id = u.id WHERE DATE(t.tanggal) BETWEEN ? AND ? ORDER BY t.tanggal DESC");
$stmtRiwayat->execute([$tanggal_mulai, $tanggal_selesai]);
$riwayat = $stmtRiwayat->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Kasir UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg glass-topbar sticky-top px-3 px-md-4">
        <div class="container-fluid p-0">
            <a class="navbar-brand fw-bold text-primary" href="index.php"><i class="bi bi-arrow-left me-2"></i>Kembali ke Kasir</a>
            <span class="navbar-text fw-semibold">Laporan Penjualan</span>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Filter -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Dari Tanggal</label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="<?= e($tanggal_mulai) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Sampai Tanggal</label>
                        <input type="date" name="tanggal_selesai" class="form-control" value="<?= e($tanggal_selesai) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-2"></i>Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-semibold mb-1">Total Transaksi</div>
                        <h3 class="fw-bold mb-0"><?= $stats['jumlah'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-semibold mb-1">Total Omzet</div>
                        <h3 class="fw-bold text-primary mb-0"><?= format_rupiah($stats['omzet'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-semibold mb-1">Rata-rata per Transaksi</div>
                        <h3 class="fw-bold text-success mb-0"><?= format_rupiah($rata_rata) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Top Produk -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Top 10 Produk Terlaris</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topProduk)): ?>
                            <p class="text-muted text-center py-4">Belum ada data</p>
                        <?php else: ?>
                            <?php foreach ($topProduk as $index => $p): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="fw-semibold"><?= ($index + 1) ?>. <?= e($p['nama_produk']) ?></span>
                                        <span class="text-muted"><?= $p['total_qty'] ?> terjual</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary" style="width: <?= ($p['total_qty'] / $maxQty) * 100 ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Riwayat Transaksi -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Transaksi</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light sticky-top">
                                    <tr>
                                        <th class="ps-4">Kode</th>
                                        <th>Tanggal</th>
                                        <th>Kasir</th>
                                        <th>Metode</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($riwayat as $r): ?>
                                    <tr>
                                        <td class="ps-4 fw-mono small"><?= e($r['kode']) ?></td>
                                        <td class="small"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                                        <td class="small"><?= e($r['kasir'] ?? '-') ?></td>
                                        <td><span class="badge bg-light text-dark border text-uppercase"><?= e($r['metode']) ?></span></td>
                                        <td class="text-end fw-bold"><?= format_rupiah($r['total']) ?></td>
                                        <td class="text-center">
                                            <a href="struk.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-light text-primary" title="Cetak Struk">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($riwayat)): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data transaksi</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>