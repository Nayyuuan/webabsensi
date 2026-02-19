
<?php
// api.php
// File ini bertugas menghubungkan HTML/JS ke Database MySQL

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Mengizinkan akses dari domain manapun (untuk development)

// Konfigurasi Database
$host = 'localhost';
$db   = 'botabsen';
$user = 'root';      // User default XAMPP biasanya 'root'
$pass = 'sellala';          // Password default XAMPP biasanya kosong
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Membuat koneksi
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Query mengambil semua data, diurutkan dari yang terbaru (ID terbesar)
    $stmt = $pdo->query("SELECT * FROM kehadiran ORDER BY id DESC");
    $data = $stmt->fetchAll();
    
    // Mengirimkan data dalam format JSON
    echo json_encode($data);

} catch (\PDOException $e) {
    // Jika koneksi gagal, kirim pesan error
    // Pastikan format tetap JSON
    echo json_encode(['error' => 'Koneksi Database Gagal: ' . $e->getMessage()]);
}
?>
