<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kasir UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid vh-100">
        <div class="row h-100">
            <!-- Panel Kiri -->
            <div class="col-md-6 d-none d-md-flex flex-column justify-content-center align-items-center text-white position-relative overflow-hidden bg-primary-gradient">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
                <div class="position-relative z-1 text-center px-5">
                    <span class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill fw-bold">✨ Sistem Kasir Modern</span>
                    <h1 class="fw-bold display-4 mb-4" style="letter-spacing: -0.03em;">Kelola toko Anda<br>lebih mudah</h1>
                    <div class="row g-3 text-start mt-5">
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-lightning-charge-fill fs-4"></i>
                                <span>Transaksi Cepat</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shield-check fs-4"></i>
                                <span>Keamanan Data</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-graph-up-arrow fs-4"></i>
                                <span>Laporan Real-time</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-printer fs-4"></i>
                                <span>Cetak Struk Thermal</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel Kanan -->
            <div class="col-md-6 d-flex flex-column justify-content-center align-items-center p-4">
                <div class="w-100" style="max-width: 400px;">
                    <div class="text-center mb-4 d-md-none">
                        <h2 class="fw-bold text-primary" style="letter-spacing: -0.03em;">Kasir UMKM</h2>
                    </div>
                    <h3 class="fw-bold mb-4">Selamat Datang</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username" class="form-control form-control-lg" placeholder="Masukkan username" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg" placeholder="Masukkan password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                        </button>
                    </form>
                    
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Default: <strong>admin</strong> / <strong>admin123</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>