<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

date_default_timezone_set('Asia/Jakarta');

// === 1. FUNGSI LOGIKA WAKTU (OTOMATIS) ===
function getJenisAbsensi() {
    $jam_sekarang = date("H:i");
    
    // Logika waktu sesuai permintaan
    if ($jam_sekarang >= "06:00" && $jam_sekarang <= "08:30") {
        return "datang";
    } elseif ($jam_sekarang > "08:30" && $jam_sekarang < "16:00") {
        return "terlambat";
    } else {
        // Mencakup jam 16:00 s/d 23:59 DAN 00:00 s/d 05:59
        return "pulang";
    }
}

// === 2. FUNGSI KOMPRESI GAMBAR ===
function compressAndSaveImage($source_string, $destination_path) {
    // Buat image resource dari string binary
    $image = @imagecreatefromstring($source_string);
    if (!$image) return false;

    // Cek dimensi gambar
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Resize jika gambar terlalu besar (lebar > 1000px)
    $max_width = 1000;
    if ($width > $max_width) {
        $new_width = $max_width;
        $new_height = ($height / $width) * $new_width;
        
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Resize
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        $image = $new_image;
    }

    // Simpan sebagai JPG dengan Kualitas 60%
    $result = imagejpeg($image, $destination_path, 60);
    
    // Bersihkan memori
    imagedestroy($image);
    
    return $result;
}

// === 3. PROSES UTAMA ===
$jsonInput = file_get_contents("php://input");
$data = json_decode($jsonInput, true);
$response = array();

if (isset($data['image'])) {
    
    $base64_string = $data['image'];

    // Bersihkan header base64 (data:image/xyz;base64,...)
    if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
        $base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
    }

    $base64_string = str_replace(' ', '+', $base64_string);
    $image_data = base64_decode($base64_string);

    if ($image_data === false) {
        echo json_encode(["status" => "error", "message" => "Gagal decode Base64"]);
        exit;
    }

    // Tentukan Folder
    $target_dir = "img/"; 
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate Data Otomatis
    $jenis_absensi = getJenisAbsensi();
    $timestamp = date("d_m_Y_H_i_s");
    
    // Nama file
    $file_name = "foto_{$jenis_absensi}_{$timestamp}.jpg";
    $file_path = $target_dir . $file_name;

    // Simpan dengan Kompresi
    if (compressAndSaveImage($image_data, $file_path)) {
        
        $file_size_kb = round(filesize($file_path) / 1024, 2) . " KB";

        // === STRUKTUR RESPON BARU (FLAT) ===
        $response = array(
            "status" => "success",
            "message" => "Foto berhasil disimpan & dikompres.",
            "jenis_absensi" => $jenis_absensi,
            "file_name" => $file_name,
            "file_path" => $file_path,       // Path lengkap (img/foto_xxx.jpg)
            "ukuran_file" => $file_size_kb,
            "waktu_server" => date("H:i:s")
        );

    } else {
        // Fallback jika kompresi gagal, simpan biasa (write binary)
        if(file_put_contents($file_path, $image_data)){
            $response = array(
                "status" => "warning", 
                "message" => "Foto disimpan tanpa kompresi (GD library error).",
                "jenis_absensi" => $jenis_absensi,
                "file_name" => $file_name,
                "file_path" => $file_path
            );
        } else {
             $response = array("status" => "error", "message" => "Gagal menulis file ke disk.");
        }
    }

} else {
    $response = array("status" => "error", "message" => "Parameter 'image' tidak ditemukan.");
}

echo json_encode($response);
?>