<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi: Hanya Admin yang bisa melakukan transaksi penjualan
if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_pembeli = mysqli_real_escape_string($conn, strtoupper($_POST['nama_pembeli']));
    $jenjang      = $_POST['jenjang'];
    $items        = $_POST['items']; // Diambil dari form dinamis di jual_langsung.php
    $total_bayar  = 0;

    // Mulai Transaksi Database (Integritas Data)
    mysqli_begin_transaction($conn);

    try {
        // 1. BUAT HEADER PESANAN
        // Status langsung 'diambil' karena barang diserahkan di tempat
        $q_order = "INSERT INTO orders (
                        nama_siswa, 
                        jenjang_siswa, 
                        tgl_ukur, 
                        status_order, 
                        tgl_ambil, 
                        nama_pengambil, 
                        total_biaya, 
                        sudah_bayar
                    ) VALUES (
                        '$nama_pembeli', 
                        '$jenjang', 
                        NOW(), 
                        'diambil', 
                        NOW(), 
                        '$nama_pembeli', 
                        0, 
                        0
                    )";
        
        if (!mysqli_query($conn, $q_order)) {
            throw new Exception("Gagal membuat header transaksi.");
        }

        $id_order = mysqli_insert_id($conn);

        // 2. PROSES TIAP ITEM
        foreach ($items as $item) {
            $id_variant = $item['id_variant'];
            $qty        = (int)$item['qty'];
            $harga      = (int)$item['harga'];
            $subtotal   = $qty * $harga;
            $total_bayar += $subtotal;

            // A. Cek Stok Fisik (Lock for Update agar tidak ada selisih jika 2 admin input barengan)
            $q_cek = mysqli_query($conn, "SELECT stok FROM variants WHERE id_variant = '$id_variant' FOR UPDATE");
            $d_cek = mysqli_fetch_assoc($q_cek);
            
            if (!$d_cek || $d_cek['stok'] < $qty) {
                // Cari nama produk untuk pesan error yang informatif
                $q_info = mysqli_query($conn, "SELECT p.nama_produk FROM products p JOIN variants v ON p.id_produk=v.id_produk WHERE v.id_variant='$id_variant'");
                $nama_p = mysqli_fetch_assoc($q_info)['nama_produk'];
                throw new Exception("Stok untuk $nama_p tidak mencukupi (Tersisa: {$d_cek['stok']}).");
            }

            // B. POTONG STOK FISIK DI GUDANG
            $q_potong = "UPDATE variants SET stok = stok - $qty WHERE id_variant = '$id_variant'";
            mysqli_query($conn, $q_potong);

            // C. SIMPAN DETAIL PESANAN (is_received = 1 karena langsung diserahkan)
            $q_detail = "INSERT INTO order_details (id_order, id_variant, qty, is_received, tgl_terima) 
                         VALUES ('$id_order', '$id_variant', '$qty', 1, NOW())";
            mysqli_query($conn, $q_detail);

            // D. CATAT KE LOG AKTIVITAS (Audit Mutasi)
            $ket_log = "Penjualan Langsung: $nama_pembeli (Nota #$id_order)";
            $q_log = "INSERT INTO stock_logs (id_variant, jenis_aksi, qty, keterangan, tgl_log) 
                      VALUES ('$id_variant', 'KELUAR', '$qty', '$ket_log', NOW())";
            mysqli_query($conn, $q_log);
        }

        // 3. UPDATE TOTAL KEUANGAN PADA ORDER
        mysqli_query($conn, "UPDATE orders SET total_biaya = '$total_bayar', sudah_bayar = '$total_bayar' WHERE id_order = '$id_order'");

        // 4. UPDATE SALDO KAS KEDAI (Jika tabel kas_kedai sudah ada datanya)
        // Kita asumsikan ID KAS adalah 1
        mysqli_query($conn, "UPDATE kas_kedai SET saldo_saat_ini = saldo_saat_ini + $total_bayar WHERE id_kas = 1");

        // Jika semua langkah berhasil, terapkan ke database
        mysqli_commit($conn);
        $log_msg = "Admin melakukan Penjualan Langsung (Koperasi) senilai Rp ".number_format($total_bayar)." ke pembeli: $nama_pembeli.";
mysqli_query($conn, "INSERT INTO internal_messages (sender_id, receiver_role, pesan) VALUES (1, 'manager', '$log_msg')");

        echo "<script>
                alert('Transaksi Berhasil! Stok telah dipotong dan pemasukan dicatat.');
                window.location='cetak_nota.php?id=$id_order';
              </script>";

    } catch (Exception $e) {
        // Jika ada satu saja langkah yang gagal, batalkan semua (stok tidak jadi berkurang)
        mysqli_rollback($conn);
        echo "<script>
                alert('Transaksi Gagal: " . $e->getMessage() . "');
                window.history.back();
              </script>";
    }

} else {
    // Jika diakses tanpa POST
    header("Location: jual_langsung.php");
}
?>