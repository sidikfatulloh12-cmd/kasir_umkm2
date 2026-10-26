<?php
require_once 'config.php';
check_auth();

$pdo = get_db();
$kategoris = $pdo->query("SELECT * FROM kategori ORDER BY id")->fetchAll();
$produks = $pdo->query("SELECT p.*, k.nama as kategori_nama FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id WHERE p.aktif = 1 ORDER BY p.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Kasir UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Topbar -->
    <nav class="navbar navbar-expand-lg glass-topbar sticky-top px-3 px-md-4">
        <div class="container-fluid p-0">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="index.php">
                <div class="brand-icon"><i class="bi bi-shop"></i></div>
                <span class="d-none d-sm-inline">Kasir UMKM</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small d-none d-md-inline">Halo, <?= e($_SESSION['nama']) ?></span>
                <div class="dropdown">
                    <button class="btn btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="produk.php"><i class="bi bi-box-seam me-2"></i>Produk</a></li>
                            <li><a class="dropdown-item" href="laporan.php"><i class="bi bi-graph-up me-2"></i>Laporan</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-3 pos-container">
        <div class="row g-3">
            <!-- Kolom Kiri: Katalog Produk -->
            <div class="col-lg-8 col-md-7">
                <!-- Search & Filter -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="searchProduk" class="form-control border-start-0" placeholder="Cari nama produk...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2 overflow-auto category-tabs pb-1" id="categoryTabs">
                                    <button class="btn btn-sm btn-primary rounded-pill px-3 active" data-kategori="all">Semua</button>
                                    <?php foreach ($kategoris as $kat): ?>
                                        <button class="btn btn-sm btn-light border rounded-pill px-3" data-kategori="<?= $kat['id'] ?>">
                                            <?= e($kat['nama']) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid Produk -->
                <div class="row g-3" id="productGrid">
                    <?php foreach ($produks as $produk): 
                        $emoji = '📦';
                        if (stripos($produk['kategori_nama'], 'makan') !== false) $emoji = '🍜';
                        elseif (stripos($produk['kategori_nama'], 'minum') !== false) $emoji = '🥤';
                        elseif (stripos($produk['kategori_nama'], 'sembako') !== false) $emoji = '🌾';
                        
                        $stockClass = $produk['stok'] == 0 ? 'bg-danger' : ($produk['stok'] <= 5 ? 'bg-warning text-dark' : 'bg-success');
                        $stockText = $produk['stok'] == 0 ? 'Habis' : ($produk['stok'] <= 5 ? 'Sisa ' . $produk['stok'] : 'Tersedia');
                    ?>
                    <div class="col-6 col-md-4 col-lg-3 product-item" data-kategori="<?= $produk['kategori_id'] ?>" data-nama="<?= strtolower($produk['nama']) ?>">
                        <div class="product-card h-100" onclick="addToCart(<?= $produk['id'] ?>, '<?= e($produk['nama']) ?>', <?= $produk['harga_jual'] ?>, <?= $produk['stok'] ?>)" style="<?= $produk['stok'] == 0 ? 'opacity: 0.6; pointer-events: none;' : '' ?>">
                            <div class="product-emoji"><?= $emoji ?></div>
                            <div class="product-body">
                                <h6 class="fw-bold mb-1 text-truncate"><?= e($produk['nama']) ?></h6>
                                <div class="text-primary fw-bold mb-2"><?= format_rupiah($produk['harga_jual']) ?></div>
                                <span class="badge <?= $stockClass ?> <?= $produk['stok'] <= 5 && $produk['stok'] > 0 ? 'pulse-badge' : '' ?> rounded-pill">
                                    <?= $stockText ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Kolom Kanan: Keranjang -->
            <div class="col-lg-4 col-md-5 d-none d-md-block">
                <div class="cart-panel card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-cart3 me-2"></i>Keranjang</h5>
                    </div>
                    <div class="card-body p-0 flex-grow-1 overflow-auto" id="cartItems" style="max-height: 400px;">
                        <div class="text-center text-muted py-5" id="emptyCart">
                            <i class="bi bi-cart-x display-4 d-block mb-2"></i>
                            <small>Keranjang masih kosong</small>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-bold" id="subtotalDisplay">Rp 0</span>
                        </div>
                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Diskon (Rp)</span>
                            <input type="number" class="form-control text-end" id="inputDiskon" value="0" min="0" onchange="calculateTotal()">
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold fs-5">Total</span>
                            <span class="fw-bold fs-4 text-primary" id="totalDisplay">Rp 0</span>
                        </div>
                        <button class="btn btn-primary btn-lg w-100 fw-bold btn-bayar" onclick="openPaymentModal()" id="btnBayar" disabled>
                            <i class="bi bi-cash-coin me-2"></i>BAYAR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Cart Toggle -->
    <button class="btn btn-primary rounded-circle shadow-lg mobile-cart-btn d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileCart">
        <i class="bi bi-cart3 fs-4"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="mobileCartCount">0</span>
    </button>

    <!-- Mobile Cart Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileCart">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold">Keranjang</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column" id="mobileCartBody">
            <!-- Diisi oleh JS -->
        </div>
    </div>

    <!-- Modal Pembayaran -->
    <div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <small class="text-muted">Total Tagihan</small>
                        <h2 class="fw-bold text-primary" id="modalTotal">Rp 0</h2>
                    </div>
                    
                    <ul class="nav nav-pills mb-3 justify-content-center" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill px-4" id="pills-cash-tab" data-bs-toggle="pill" data-bs-target="#pills-cash" type="button">Tunai</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-4" id="pills-qris-tab" data-bs-toggle="pill" data-bs-target="#pills-qris" type="button">QRIS</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-4" id="pills-transfer-tab" data-bs-toggle="pill" data-bs-target="#pills-transfer" type="button">Transfer</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="pills-tabContent">
                        <!-- Tunai -->
                        <div class="tab-pane fade show active" id="pills-cash">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Uang Diterima</label>
                                <input type="text" class="form-control form-control-lg text-end fw-bold" id="inputBayar" placeholder="0" oninput="formatInputRupiah(this); calculateKembalian()">
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-3 justify-content-center">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setBayar(5000)">5rb</button>
                                <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setBayar(10000)">10rb</button>
                                <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setBayar(20000)">20rb</button>
                                <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setBayar(50000)">50rb</button>
                                <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setBayar(100000)">100rb</button>
                                <button class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="setBayarPas()">UANG PAS</button>
                            </div>
                            <div class="alert d-flex justify-content-between align-items-center" id="kembalianAlert" role="alert">
                                <span>Kembalian:</span>
                                <span class="fw-bold fs-5" id="kembalianDisplay">Rp 0</span>
                            </div>
                        </div>
                        
                        <!-- QRIS / Transfer -->
                        <div class="tab-pane fade text-center py-3" id="pills-qris">
                            <p class="text-muted">Scan QRIS atau transfer ke rekening berikut:</p>
                            <h4 class="fw-bold text-primary mb-3" id="qrisTotal">Rp 0</h4>
                            <div class="bg-light p-4 rounded-3 d-inline-block">
                                <i class="bi bi-qr-code display-1 text-secondary"></i>
                                <p class="mb-0 mt-2 small text-muted">QRIS Placeholder</p>
                            </div>
                        </div>
                        <div class="tab-pane fade text-center py-3" id="pills-transfer">
                            <p class="text-muted">Transfer ke rekening berikut:</p>
                            <h5 class="fw-bold">BCA 1234567890</h5>
                            <p>a.n. Toko UMKM</p>
                            <h4 class="fw-bold text-primary" id="transferTotal">Rp 0</h4>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary px-4 fw-bold" id="btnProsesBayar" onclick="prosesTransaksi()" disabled>
                        <span class="spinner-border spinner-border-sm d-none" id="loadingSpinner"></span>
                        <span id="btnProsesText">Proses Pembayaran</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage">Transaksi berhasil!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];
        let totalBelanja = 0;
        const produkData = <?= json_encode($produks) ?>;

        // Filter Kategori
        document.querySelectorAll('.category-tabs button').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.category-tabs button').forEach(b => {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-light');
                });
                this.classList.remove('btn-light');
                this.classList.add('btn-primary', 'active');
                
                const kat = this.dataset.kategori;
                document.querySelectorAll('.product-item').forEach(item => {
                    if (kat === 'all' || item.dataset.kategori === kat) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // Search Real-time
        document.getElementById('searchProduk').addEventListener('input', function() {
            const keyword = this.value.toLowerCase();
            document.querySelectorAll('.product-item').forEach(item => {
                if (item.dataset.nama.includes(keyword)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        function addToCart(id, nama, harga, stok) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                if (existing.qty < stok) {
                    existing.qty++;
                } else {
                    showToast('Stok tidak mencukupi!', 'danger');
                    return;
                }
            } else {
                cart.push({ id, nama, harga, qty: 1, stok });
            }
            renderCart();
        }

        function updateQty(id, delta) {
            const item = cart.find(i => i.id === id);
            if (item) {
                item.qty += delta;
                if (item.qty <= 0) {
                    cart = cart.filter(i => i.id !== id);
                } else if (item.qty > item.stok) {
                    item.qty = item.stok;
                    showToast('Stok maksimal tercapai', 'warning');
                }
                renderCart();
            }
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            const mobileContainer = document.getElementById('mobileCartBody');
            const emptyMsg = document.getElementById('emptyCart');
            
            if (cart.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-5" id="emptyCart"><i class="bi bi-cart-x display-4 d-block mb-2"></i><small>Keranjang masih kosong</small></div>';
                mobileContainer.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-cart-x display-4 d-block mb-2"></i><small>Keranjang masih kosong</small></div>';
                document.getElementById('btnBayar').disabled = true;
                document.getElementById('mobileCartCount').textContent = '0';
            } else {
                let html = '';
                let mobileHtml = '';
                cart.forEach(item => {
                    const subtotal = item.harga * item.qty;
                    html += `
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom cart-item-slide">
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1 text-truncate" style="max-width: 200px;">${item.nama}</h6>
                                <small class="text-muted">${formatRupiah(item.harga)} x ${item.qty}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateQty(${item.id}, -1)">-</button>
                                <span class="fw-bold" style="min-width: 20px; text-align: center;">${item.qty}</span>
                                <button class="btn btn-sm btn-outline-primary rounded-circle" onclick="updateQty(${item.id}, 1)">+</button>
                            </div>
                            <div class="fw-bold ms-2">${formatRupiah(subtotal)}</div>
                        </div>
                    `;
                    mobileHtml += html; // Simplified for mobile
                });
                container.innerHTML = html;
                mobileContainer.innerHTML = html + `
                    <div class="mt-auto pt-3 border-top">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold fs-5">Total</span>
                            <span class="fw-bold fs-4 text-primary" id="mobileTotalDisplay">${formatRupiah(getSubtotal())}</span>
                        </div>
                        <button class="btn btn-primary btn-lg w-100 fw-bold" onclick="openPaymentModal()" data-bs-dismiss="offcanvas">BAYAR</button>
                    </div>
                `;
                document.getElementById('btnBayar').disabled = false;
                document.getElementById('mobileCartCount').textContent = cart.reduce((sum, item) => sum + item.qty, 0);
            }
            calculateTotal();
        }

        function getSubtotal() {
            return cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
        }

        function calculateTotal() {
            const subtotal = getSubtotal();
            const diskon = parseInt(document.getElementById('inputDiskon').value) || 0;
            totalBelanja = Math.max(0, subtotal - diskon);
            
            document.getElementById('subtotalDisplay').textContent = formatRupiah(subtotal);
            document.getElementById('totalDisplay').textContent = formatRupiah(totalBelanja);
            if (document.getElementById('mobileTotalDisplay')) {
                document.getElementById('mobileTotalDisplay').textContent = formatRupiah(totalBelanja);
            }
            calculateKembalian();
        }

        function openPaymentModal() {
            if (cart.length === 0) return;
            document.getElementById('modalTotal').textContent = formatRupiah(totalBelanja);
            document.getElementById('qrisTotal').textContent = formatRupiah(totalBelanja);
            document.getElementById('transferTotal').textContent = formatRupiah(totalBelanja);
            document.getElementById('inputBayar').value = '';
            document.getElementById('kembalianDisplay').textContent = 'Rp 0';
            document.getElementById('kembalianAlert').className = 'alert alert-secondary d-flex justify-content-between align-items-center';
            document.getElementById('btnProsesBayar').disabled = true;
            
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        }

        function formatInputRupiah(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            input.value = value ? formatRupiah(parseInt(value)) : '';
        }

        function parseRupiah(str) {
            return parseInt(str.replace(/[^0-9]/g, '')) || 0;
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function setBayar(nominal) {
            document.getElementById('inputBayar').value = formatRupiah(nominal);
            calculateKembalian();
        }

        function setBayarPas() {
            document.getElementById('inputBayar').value = formatRupiah(totalBelanja);
            calculateKembalian();
        }

        function calculateKembalian() {
            const bayar = parseRupiah(document.getElementById('inputBayar').value);
            const kembalian = bayar - totalBelanja;
            const alertBox = document.getElementById('kembalianAlert');
            const btnProses = document.getElementById('btnProsesBayar');
            
            document.getElementById('kembalianDisplay').textContent = formatRupiah(Math.max(0, kembalian));
            
            if (bayar >= totalBelanja && totalBelanja > 0) {
                alertBox.className = 'alert alert-success d-flex justify-content-between align-items-center';
                btnProses.disabled = false;
            } else {
                alertBox.className = 'alert alert-warning d-flex justify-content-between align-items-center';
                btnProses.disabled = true;
            }
        }

        function prosesTransaksi() {
            const btn = document.getElementById('btnProsesBayar');
            const spinner = document.getElementById('loadingSpinner');
            const text = document.getElementById('btnProsesText');
            
            btn.disabled = true;
            spinner.classList.remove('d-none');
            text.textContent = 'Memproses...';

            const metode = document.querySelector('.nav-link.active').id.includes('qris') ? 'qris' : 
                          (document.querySelector('.nav-link.active').id.includes('transfer') ? 'transfer' : 'cash');
            
            const payload = {
                items: cart.map(item => ({ id: item.id, qty: item.qty })),
                diskon: parseInt(document.getElementById('inputDiskon').value) || 0,
                bayar: parseRupiah(document.getElementById('inputBayar').value),
                metode: metode
            };

            fetch('api.php?action=simpan_transaksi', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    showToast('Transaksi berhasil!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                    window.open(`struk.php?id=${data.id}`, '_blank');
                    cart = [];
                    document.getElementById('inputDiskon').value = 0;
                    renderCart();
                    // Reload halaman untuk update stok real-time
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.msg || 'Gagal memproses transaksi', 'danger');
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    text.textContent = 'Proses Pembayaran';
                }
            })
            .catch(err => {
                showToast('Terjadi kesalahan jaringan', 'danger');
                btn.disabled = false;
                spinner.classList.add('d-none');
                text.textContent = 'Proses Pembayaran';
            });
        }

        function showToast(msg, type) {
            const toast = document.getElementById('liveToast');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            document.getElementById('toastMessage').textContent = msg;
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }
    </script>
</body>
</html>