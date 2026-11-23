<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Akses
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    exit;
}

// Ambil parameter dari URL
$jenjang = $_GET['jenjang'] ?? '';
$id_wave = $_GET['gelombang'] ?? '';

if (empty($jenjang) || empty($id_wave)) {
    die("Pilih Jenjang dan Gelombang terlebih dahulu di panel Filter!");
}

// 1. Ambil Nama Gelombang untuk Judul
$q_w = mysqli_query($conn, "SELECT nama_gelombang FROM waves WHERE id_wave = '$id_wave'");
$nama_gelombang = mysqli_fetch_assoc($q_w)['nama_gelombang'] ?? '';

// 2. Set Header Excel
$filename = "REKAP_PENGUKURAN_" . $jenjang . "_" . str_replace(' ', '_', $nama_gelombang) . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// --- DATA PREPARATION ---

// A. Ambil Produk SERAGAM & OLAHRAGA
$produk_list =[];
$sql_p = "SELECT id_produk, nama_produk FROM products WHERE jenjang = '$jenjang' AND is_active = 1 AND kategori IN ('SERAGAM','OLAHRAGA') ORDER BY kategori ASC, nama_produk ASC";
$q_p = mysqli_query($conn, $sql_p);
while($p = mysqli_fetch_assoc($q_p)) { 
    $produk_list[$p['id_produk']] = $p['nama_produk']; 
}

// B. Ambil Atribut UMUM secara Dinamis
$atribut_umum =[];
$sql_a = "SELECT id_produk, nama_produk FROM products 
          WHERE jenjang = 'UMUM' 
          AND kategori = 'ATRIBUT' 
          AND nama_produk NOT IN ('Bordir Nama', 'Badge/Lokasi Bordir') 
          AND is_active = 1 
          ORDER BY nama_produk ASC";
$q_a = mysqli_query($conn, $sql_a);
while($a = mysqli_fetch_assoc($q_a)) {
    $atribut_umum[$a['id_produk']] = $a['nama_produk'];
}

// C. Ambil Master Ukuran Standar
$master_sizes =[];
$q_s = mysqli_query($conn, "SELECT nama_size FROM master_sizes ORDER BY tipe_size DESC, CAST(nama_size AS UNSIGNED) ASC, urutan ASC");
while($s = mysqli_fetch_assoc($q_s)) { $master_sizes[] = $s['nama_size']; }

// D. Ambil Data Siswa
$q_siswa = mysqli_query($conn, "SELECT id_order, nama_siswa, tgl_ukur FROM orders WHERE jenjang_siswa = '$jenjang' AND id_wave = '$id_wave' ORDER BY nama_siswa ASC");

// E. Ambil Semua Detail Order & Rangkai Catatan
$order_sizes =[]; 
$order_qty = [];   
$order_received =[]; 
$bordir_texts = []; 
$bordir_received_status =[]; 

$q_details = mysqli_query($conn, "
    SELECT od.id_order, v.id_produk, v.ukuran, od.catatan_item, p.nama_produk, od.qty, od.is_received 
    FROM order_details od 
    JOIN variants v ON od.id_variant = v.id_variant 
    JOIN products p ON v.id_produk = p.id_produk
");

while($d = mysqli_fetch_assoc($q_details)) {
    $catatan = trim($d['catatan_item']);
    
    // LOGIKA BARU: Jika ada catatan (dan bukan bordir), gabungkan dengan ukuran
    if ($d['nama_produk'] != 'Bordir Nama' && !empty($catatan)) {
        $display_size = $d['ukuran'] . " (" . $catatan . ")";
    } else {
        $display_size = $d['ukuran'];
    }

    $order_sizes[$d['id_order']][$d['id_produk']] = $display_size;
    $order_qty[$d['id_order']][$d['id_produk']] = $d['qty'];
    $order_received[$d['id_order']][$d['id_produk']] = $d['is_received'];
    
    if($d['nama_produk'] == 'Bordir Nama') {
        $bordir_texts[$d['id_order']] = str_ireplace('Teks Bordir: ', '', $catatan);
        $bordir_received_status[$d['id_order']] = $d['is_received'];
    }
}
?>

<style>
    .text-red { color: #ff0000; font-weight: bold; }
    .text-black { color: #000000; font-weight: normal; }
    .bg-gray { background-color: #f2f2f2; font-weight: bold; }
    .text-custom { color: #d97706; font-style: italic; font-weight: bold; } /* Warna khusus untuk yang ada catatannya */
</style>

<table border="1">
    <!-- JUDUL -->
    <tr>
        <th colspan="<?php echo 4 + count($produk_list) + count($atribut_umum); ?>" style="font-size: 16px; height: 30px;">
            REKAP PENGUKURAN SERAGAM <?php echo $jenjang; ?> - <?php echo strtoupper($nama_gelombang); ?>
        </th>
    </tr>
    <tr>
        <th colspan="<?php echo 4 + count($produk_list) + count($atribut_umum); ?>">Tahun Ajaran <?php echo date('Y')." / ".(date('Y')+1); ?></th>
    </tr>
    <tr><th colspan="<?php echo 4 + count($produk_list) + count($atribut_umum); ?>"></th></tr>

    <!-- HEADER TABEL UTAMA -->
    <tr class="bg-gray">
        <th>NO</th>
        <th>NAMA</th>
        <th>NOMOR NOTA</th>
        <?php foreach($produk_list as $np): ?>
            <th><?php echo strtoupper(str_ireplace([' TK',' SD',' SMP',' SMA',' SMK'], '', $np)); ?></th>
        <?php endforeach; ?>
        <?php foreach($atribut_umum as $na): ?>
            <th><?php echo strtoupper($na); ?></th>
        <?php endforeach; ?>
        <th>BORDIR NAMA</th>
    </tr>

    <!-- DATA RINCIAN SISWA -->
    <?php 
    $no = 1;
    $summary_counts =[]; // Untuk tabel ringkasan bawah
    while($s = mysqli_fetch_assoc($q_siswa)): 
        $ido = $s['id_order'];
        $nota = sprintf('%03d', $ido) . '/' . date('y', strtotime($s['tgl_ukur']));
    ?>
    <tr>
        <td align="center"><?php echo $no++; ?></td>
        <td><?php echo strtoupper($s['nama_siswa']); ?></td>
        <td align="center"><?php echo $nota; ?></td>
        
        <!-- Kolom Ukuran Seragam -->
        <?php foreach($produk_list as $idp => $np): 
            $size = $order_sizes[$ido][$idp] ?? '';
            $is_received = $order_received[$ido][$idp] ?? 0;
            
            // Pewarnaan Hitam/Merah
            $style_color = ($is_received == 1) ? "text-black" : "text-red";
            
            // Highlight khusus jika mengandung tanda kurung (berarti ada catatan)
            if (strpos($size, '(') !== false && $is_received == 0) {
                $style_color = "text-custom"; // Merah orange untuk custom size
            }

            if(!empty($size)) {
                // Rekap dihitung berdasarkan NAMA SIZE + CATATAN
                $summary_counts[$idp][$size] = ($summary_counts[$idp][$size] ?? 0) + 1;
            }
        ?>
            <td align="center" class="<?php echo $style_color; ?>"><?php echo $size; ?></td>
        <?php endforeach; ?>

        <!-- Kolom Atribut Umum -->
        <?php foreach($atribut_umum as $idp => $na): 
            $qty = $order_qty[$ido][$idp] ?? '';
            $is_received = $order_received[$ido][$idp] ?? 0;
            $style_color = ($is_received == 1) ? "text-black" : "text-red";
        ?>
            <td align="center" class="<?php echo $style_color; ?>"><?php echo $qty; ?></td>
        <?php endforeach; ?>

        <!-- Kolom Teks Bordir -->
        <?php 
            $b_text = $bordir_texts[$ido] ?? '';
            $b_status = $bordir_received_status[$ido] ?? 0;
            $b_color = ($b_status == 1) ? "text-black" : "text-red";
        ?>
        <td class="<?php echo $b_color; ?>"><?php echo strtoupper($b_text); ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<br><br>

<!-- TABEL RINGKASAN REKAP UKURAN (BAGIAN BAWAH) -->
<table border="0">
    <tr>
    <?php 
    $col_count = 0;
    foreach($produk_list as $idp => $np): 
        if($col_count % 4 == 0 && $col_count != 0) echo "</tr><tr><td colspan='10' style='height:30px;'></td></tr><tr>";
    ?>
        <td valign="top" style="padding-right: 25px;">
            <table border="1">
                <tr class="bg-gray">
                    <th colspan="2" style="height: 25px;"><?php echo strtoupper(str_ireplace([' TK',' SD',' SMP',' SMA',' SMK'], '', $np)); ?></th>
                </tr>
                <tr class="bg-gray">
                    <th width="150">UKURAN</th>
                    <th width="80">JUMLAH</th>
                </tr>
                <?php 
                $subtotal = 0;
                $printed_sizes =[]; // Menyimpan ukuran standar yang sudah di-print
                
                // 1. Lakukan Looping untuk Ukuran Standar (Master Sizes)
                foreach($master_sizes as $sz): 
                    $qty = $summary_counts[$idp][$sz] ?? 0;
                    // Tampilkan size standar meskipun 0, atau size khusus jika ada isinya
                    if($qty == 0 && !in_array($sz,['S','M','L','XL','2XL','3XL'])) continue; 
                    
                    $subtotal += $qty;
                    $printed_sizes[] = $sz; // Tandai sudah di-print
                ?>
                <tr>
                    <td align="center"><b><?php echo $sz; ?></b></td>
                    <td align="center"><?php echo $qty; ?></td>
                </tr>
                <?php endforeach; ?>

                <?php 
                // 2. Lakukan Looping KHUSUS untuk Ukuran yang memiliki Catatan Tambahan (Custom)
                if(isset($summary_counts[$idp])) {
                    foreach($summary_counts[$idp] as $custom_size => $qty) {
                        // Jika ukuran ini belum ter-print di atas (artinya ini custom ada tanda kurungnya)
                        if (!in_array($custom_size, $printed_sizes) && $qty > 0) {
                            $subtotal += $qty;
                ?>
                <tr>
                    <td align="left" style="padding-left:5px; color:#d97706;"><i><?php echo $custom_size; ?></i></td>
                    <td align="center" style="color:#d97706; font-weight:bold;"><?php echo $qty; ?></td>
                </tr>
                <?php 
                        }
                    }
                }
                ?>

                <!-- TOTAL KESELURUHAN PRODUK -->
                <tr class="bg-gray">
                    <th align="right">TOTAL</th>
                    <th align="center"><?php echo $subtotal; ?></th>
                </tr>
            </table>
        </td>
    <?php $col_count++; endforeach; ?>
    </tr>
</table>