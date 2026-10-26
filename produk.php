<?php
require_once 'config.php';
check_auth();
if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak. Hanya admin yang dapat mengelola produk.");
}

$pdo = get_db();
$pesan = '';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'tambah' || $action === 'edit') {
            $kode = trim($_POST['kode']);
            $nama = trim($_POST['nama']);
            $kategori_id = (int)$_POST['kategori_id'];
            $harga_beli = (float)$_POST['harga_beli'];
            $harga_jual = (float)$_POST['harga_jual'];
            $stok = (int)$_POST['stok'];
            $satuan = trim($_POST['satuan']);
            
            if ($action === 'tambah') {
                $stmt = $pdo->prepare("INSERT INTO produk (kode, nama, kategori_id, harga_beli, harga_jual, stok, satuan) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$kode, $nama, $kategori_id, $harga_beli, $harga_jual, $stok, $satuan]);
                $pesan = "Produk berhasil ditambahkan.";
            } else {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE produk SET kode=?, nama=?, kategori_id=?, harga_beli=?, harga_jual=?, stok=?, satuan=? WHERE id=?");
                $stmt->execute([$kode, $nama, $kategori_id, $harga_beli, $harga_jual, $stok, $satuan, $id]);
                $pesan = "Produk berhasil diperbarui.";
            }
        } elseif ($action === 'hapus') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
            $stmt->execute([$id]);
            $pesan = "Produk berhasil dihapus.";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $pesan = "Kode produk sudah digunakan!";
        } else {
            $pesan = "Error: " . $e->getMessage();
        }
    }
}

$kategoris = $pdo->query("SELECT * FROM kategori ORDER BY id")->fetchAll();
$produks = $pdo->query("SELECT p.*, k.nama as kategori_nama FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id ORDER BY p.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Kasir UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg glass-topbar sticky-top px-3 px-md-4">
        <div class="container-fluid p-0">
            <a class="navbar-brand fw-bold text-primary" href="index.php"><i class="bi bi-arrow-left me-2"></i>Kembali ke Kasir</a>
            <span class="navbar-text fw-semibold">Manajemen Produk</span>
        </div>
    </nav>

    <div class="container py-4">
        <?php if ($pesan): ?>
            <div class="alert alert-info alert-dismissible fade show"><?= e($pesan) ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Daftar Produk</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduk" onclick="resetForm()">
                <i class="bi bi-plus-lg me-1"></i> Tambah Produk
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Kode</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th class="text-end">Harga Jual</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produks as $p): 
                                $stockClass = $p['stok'] == 0 ? 'bg-danger' : ($p['stok'] <= 5 ? 'bg-warning text-dark' : 'bg-success');
                            ?>
                            <tr>
                                <td class="ps-4 fw-mono"><?= e($p['kode']) ?></td>
                                <td class="fw-semibold"><?= e($p['nama']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($p['kategori_nama'] ?? '-') ?></span></td>
                                <td class="text-end fw-bold"><?= format_rupiah($p['harga_jual']) ?></td>
                                <td class="text-center"><span class="badge <?= $stockClass ?>"><?= $p['stok'] ?> <?= e($p['satuan']) ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light text-primary" onclick="editProduk(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus produk ini?')">
                                        <input type="hidden" name="action" value="hapus">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="modalProduk" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0">
                <form method="POST">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold" id="modalTitle">Tambah Produk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="tambah">
                        <input type="hidden" name="id" id="formId">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kode Produk</label>
                            <input type="text" name="kode" id="formKode" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Produk</label>
                            <input type="text" name="nama" id="formNama" class="form-control" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Kategori</label>
                                <select name="kategori_id" id="formKategori" class="form-select" required>
                                    <?php foreach ($kategoris as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= e($k['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Satuan</label>
                                <input type="text" name="satuan" id="formSatuan" class="form-control" value="pcs" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Harga Beli</label>
                                <input type="number" name="harga_beli" id="formHargaBeli" class="form-control" min="0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Harga Jual</label>
                                <input type="number" name="harga_jual" id="formHargaJual" class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Stok Awal</label>
                            <input type="number" name="stok" id="formStok" class="form-control" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('modalTitle').textContent = 'Tambah Produk';
            document.getElementById('formId').value = '';
            document.getElementById('formKode').value = '';
            document.getElementById('formNama').value = '';
            document.getElementById('formHargaBeli').value = '';
            document.getElementById('formHargaJual').value = '';
            document.getElementById('formStok').value = '';
        }

        function editProduk(data) {
            document.getElementById('formAction').value = 'edit';
            document.getElementById('modalTitle').textContent = 'Edit Produk';
            document.getElementById('formId').value = data.id;
            document.getElementById('formKode').value = data.kode;
            document.getElementById('formNama').value = data.nama;
            document.getElementById('formKategori').value = data.kategori_id;
            document.getElementById('formHargaBeli').value = data.harga_beli;
            document.getElementById('formHargaJual').value = data.harga_jual;
            document.getElementById('formStok').value = data.stok;
            document.getElementById('formSatuan').value = data.satuan;
            
            new bootstrap.Modal(document.getElementById('modalProduk')).show();
        }
    </script>
</body>
</html>