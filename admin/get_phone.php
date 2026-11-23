<?php
header('Content-Type: application/json');
include '../config/koneksi.php';

if (isset($_GET['nama'])) {
    $nama = mysqli_real_escape_string($conn, strtoupper(trim($_GET['nama'])));

    // Query untuk mengambil nomor HP terbaru berdasarkan nama siswa
    $sql = "SELECT nomor_hp FROM orders 
            WHERE UPPER(nama_siswa) = '$nama' 
            AND nomor_hp IS NOT NULL 
            AND nomor_hp != '' 
            ORDER BY id_order DESC LIMIT 1";
            
    $query = mysqli_query($conn, $sql);

    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        echo json_encode([
            'status' => 'success',
            'phone'  => $data['nomor_hp']
        ]);
    } else {
        echo json_encode([
            'status' => 'failed'
        ]);
    }
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter nama tidak ditemukan'
    ]);
}
?>