<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'kasir_umkm');
define('DB_USER', 'root'); // Sesuaikan dengan user database Anda
define('DB_PASS', '');     // Sesuaikan dengan password database Anda

// Koneksi PDO
function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Helper: Escape HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Helper: Format Rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper: Cek Autentikasi
function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Auto-create admin jika tabel users kosong
try {
    $pdo = get_db();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $row = $stmt->fetch();
    if ($row['count'] == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $hash, 'Administrator', 'admin']);
    }
} catch (Exception $e) {
    // Abaikan jika tabel belum ada (user belum import SQL)
}