<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Admin
if ($_SESSION['role'] != 'admin') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_wave    = $_POST['id_wave'];
    $nama_siswa = mysqli_real_escape_string($conn, strtoupper($_POST['nama_siswa']));
    $no_hp      = $_POST['no_hp']; // Nomor HP dari form
    $kelas      = mysqli_real_escape_string($conn, $_POST['kelas']);
    $jenjang    = $_POST['jenjang'];
    $items      = $_POST['items']; 

    // Mulai Transaksi Database
    mysqli_begin_transaction($conn);

    try {
        // 1. Tentukan status awal sebagai 'ready'
        $status_final = 'ready'; 

        // 2. Simpan Header Order (Menyimpan Nomor HP untuk database)
        $q_order = "INSERT INTO orders (id_wave, nama_siswa, nomor_hp, kelas_calon, jenjang_siswa, tgl_ukur, status_order, total_biaya) 
                    VALUES ('$id_wave', '$nama_siswa', '$no_hp', '$kelas', '$jenjang', NOW(), 'ready', 0)";
        
        if (!mysqli_query($conn, $q_order)) {
            throw new Exception("Gagal menyimpan data identitas siswa.");
        }

        $id_order = mysqli_insert_id($conn);
        
        $rincian_pesan_wa = "";
        $teks_bordir_final = "";

        // 3. Loop Item yang dipilih
        foreach ($items as $id_produk => $detail) {
            if (isset($detail['beli']) && $detail['beli'] == '1') {
                $id_variant = $detail['id_variant'];
                $qty        = (int)$detail['qty'];
                $catatan    = mysqli_real_escape_string($conn, $detail['catatan']);

                if (empty($id_variant)) {
                    throw new Exception("ID Varian tidak ditemukan untuk salah satu produk.");
                }

                // Cek Stok di Gudang
                $q_v = mysqli_query($conn, "SELECT v.ukuran, p.nama_produk, v.stok FROM variants v JOIN products p ON v.id_produk=p.id_produk WHERE v.id_variant = '$id_variant'");
                $d_v = mysqli_fetch_assoc($q_v);
                
                // Jika ada satu saja item yang stoknya kurang, maka status jadi PO
                if (($d_v['stok'] ?? 0) < $qty) {
                    $status_final = 'po';
                }

                // Simpan ke detail pesanan
                mysqli_query($conn, "INSERT INTO order_details (id_order, id_variant, qty, catatan_item, is_received) 
                                     VALUES ('$id_order', '$id_variant', '$qty', '$catatan', 0)");
                
                // Susun teks rincian untuk WhatsApp
                $rincian_pesan_wa .= "\n- {$d_v['nama_produk']} ({$d_v['ukuran']}) x$qty";

                // Ambil teks bordir jika ini adalah item bordir nama
                if (strpos($catatan, 'Teks Bordir:') !== false) {
                    $teks_bordir_final = str_replace('Teks Bordir: ', '', $catatan);
                }
            }
        }

        // 4. Update Status Akhir & Nama Bordir ke Tabel Orders
        mysqli_query($conn, "UPDATE orders SET status_order = '$status_final', bordir_nama = '$teks_bordir_final' WHERE id_order = '$id_order'");

        // --- 5. INTEGRASI FONNTE WA API (MENGIRIM LINK) ---
        $token_fonnte = "SxQcVAsZoXzEHA7JdxVG"; // GANTI DENGAN TOKEN ANDA
        
        // Bersihkan nomor HP agar formatnya pasti 628...
        $target_wa = preg_replace('/[^0-9]/', '', $no_hp);
        if (substr($target_wa, 0, 1) === '0') {
            $target_wa = '62' . substr($target_wa, 1);
        } elseif (substr($target_wa, 0, 2) !== '62') {
            $target_wa = '62' . $target_wa;
        }

        // Link publik yang tidak butuh login (view_nota.php di luar folder admin)
        $link_nota = "https://kedai.nusaputera.id/view_nota.php?id=$id_order";
        
        $pesan_wa = "Halo Ayah/Bunda *$nama_siswa*,\n\nTerima kasih telah melakukan pengukuran seragam di *Kedai Nusa Putera*.\n\n*Rincian Pesanan:*$rincian_pesan_wa\n\nStatus Pesanan: *" . strtoupper($status_final) . "*\n\nLihat Nota Digital Anda di sini:\n$link_nota\n\nMohon simpan pesan ini sebagai bukti saat pengambilan barang nanti. Terima kasih!";

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.fonnte.com/send',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array(
            'target' => $target_wa,
            'message' => $pesan_wa,
            'countryCode' => '62',
          ),
          CURLOPT_HTTPHEADER => array("Authorization: $token_fonnte"),
          CURLOPT_SSL_VERIFYPEER => false, // Mengatasi kendala SSL di beberapa hosting
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // --------------------------------------------------
        $log_msg = "Admin mencatat pesanan siswa baru: " . strtoupper($nama_siswa) . " (Nota ORD-$id_order).";
mysqli_query($conn, "INSERT INTO internal_messages (sender_id, receiver_role, pesan) VALUES (1, 'manager', '$log_msg')");
        mysqli_commit($conn);

        echo "<script>
                alert('Berhasil! Data disimpan dan Nota dikirim ke WhatsApp (Status: " . strtoupper($status_final) . ")');
                window.location='cetak_nota.php?id=$id_order';
              </script>";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>
                alert('Kesalahan: " . $e->getMessage() . "');
                window.history.back();
              </script>";
    }

} else {
    header("Location: input_ukur.php");
}
?>