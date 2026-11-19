<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Ambil data
$jenjang       = $_POST['jenjang'];
$is_active     = $_POST['is_active'];
$kategori      = $_POST['kategori'];
$nama_produk   = mysqli_real_escape_string($conn, $_POST['nama_produk']);
$tipe_ukuran   = $_POST['tipe_ukuran'];
$ukuran        = mysqli_real_escape_string($conn, $_POST['ukuran']);
$stok_input    = $_POST['stok'];
$target_input  = $_POST['target_tahunan']; // AMBIL DATA TARGET
$catatan_po    = mysqli_real_escape_string($conn, $_POST['catatan_po']);
$barcode_in    = mysqli_real_escape_string($conn, $_POST['kode_barcode']);

// 1. CEK PRODUK INDUK
$q_cek_p = mysqli_query($conn, "SELECT id_produk FROM products WHERE nama_produk = '$nama_produk' AND jenjang = '$jenjang'");

if (mysqli_num_rows($q_cek_p) > 0) {
    $data_p = mysqli_fetch_assoc($q_cek_p);
    $id_produk = $data_p['id_produk'];
} else {
    mysqli_query($conn, "INSERT INTO products (nama_produk, jenjang, kategori, tipe_ukuran, is_active) VALUES ('$nama_produk', '$jenjang', '$kategori', '$tipe_ukuran', '$is_active')");
    $id_produk = mysqli_insert_id($conn);
}

// 2. CEK VARIAN (UKURAN)
$q_cek_v = mysqli_query($conn, "SELECT id_variant, stok FROM variants WHERE id_produk = '$id_produk' AND ukuran = '$ukuran'");

if (mysqli_num_rows($q_cek_v) > 0) {
    // JIKA ADA -> UPDATE STOK DAN TARGET
    $data_v = mysqli_fetch_assoc($q_cek_v);
    $id_v = $data_v['id_variant'];
    $stok_baru = $data_v['stok'] + $stok_input;
    
    // UPDATE HARUS MENYERTAKAN target_tahunan
    $sql_upd = "UPDATE variants SET 
                stok = '$stok_baru', 
                target_tahunan = '$target_input', 
                catatan_po = '$catatan_po' 
                WHERE id_variant = '$id_v'";
                
    if(mysqli_query($conn, $sql_upd)) {
        $pesan = "Data Diperbarui! Stok: $stok_baru, Target: $target_input";
    } else {
        $pesan = "Error Update: " . mysqli_error($conn);
    }
} else {
    // JIKA BELUM ADA -> INSERT
    $barcode_final = empty($barcode_in) ? "$jenjang-".strtoupper(substr($nama_produk, 0, 3))."-$ukuran-".rand(100, 999) : $barcode_in;

    $sql_ins = "INSERT INTO variants (id_produk, ukuran, stok, target_tahunan, kode_barcode, catatan_po) 
                VALUES ('$id_produk', '$ukuran', '$stok_input', '$target_input', '$barcode_final', '$catatan_po')";
    
    if(mysqli_query($conn, $sql_ins)) {
        $pesan = "Data Baru Tersimpan! Target: $target_input";
    } else {
        $pesan = "Error Insert: " . mysqli_error($conn);
    }
}

echo "<script>alert('$pesan'); window.location='tambah.php';</script>";
?>