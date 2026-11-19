<?php
include '../config/koneksi.php';

// Ambil ID dari URL (index.php?id=...)
// Catatan: Karena di index.php kita belum pasang parameter ID, 
// nanti di index.php tombol hapusnya harus diubah sedikit.
// Tapi buat file ini dulu saja.

if(isset($_GET['id'])){
    $id = $_GET['id'];
    
    // Hapus varian saja
    $hapus = mysqli_query($conn, "DELETE FROM variants WHERE id_variant = '$id'");
    
    if($hapus){
        header("Location: index.php");
    } else {
        echo "Gagal hapus";
    }
}
?>