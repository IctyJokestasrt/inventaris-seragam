<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Akses
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    die("Akses Ditolak");
}

// Set Header Excel
$filename = "Laporan_Stok_Gudang_" . date('d-m-Y_Hi') . ".xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$filename");

// --- LOGIKA FILTER CERDAS ---
$where_clause = "WHERE 1=1";

// 1. Cek Filter Jenjang dari URL (Prioritas Utama)
if (isset($_GET['jenjang'])) {
    $f_jenjang = $_GET['jenjang'];
    
    // Jika ada nilai spesifik (misal: SD), filter query
    if ($f_jenjang != '' && $f_jenjang != 'ALL') {
        $where_clause .= " AND p.jenjang = '$f_jenjang'";
    }
    // Jika kosong (artinya user pilih 'Semua Jenjang'), biarkan WHERE 1=1
} 
// 2. Jika tidak ada parameter URL (Akses langsung), baru gunakan Session Dashboard
elseif ($jenjang_aktif != 'ALL') {
    $where_clause .= " AND p.jenjang = '$jenjang_aktif'";
}

// 3. Filter Produk Spesifik
if (isset($_GET['produk']) && $_GET['produk'] != '') {
    $prod = $_GET['produk'];
    $where_clause .= " AND p.id_produk = '$prod'";
}

// Query Data Stok
$query = "SELECT p.nama_produk, p.jenjang, p.is_active, v.ukuran, v.stok, v.target_tahunan
          FROM variants v 
          JOIN products p ON v.id_produk = p.id_produk 
          $where_clause 
          ORDER BY p.jenjang DESC, p.nama_produk ASC, v.ukuran ASC";

$result = mysqli_query($conn, $query);
?>

<table border="1">
    <thead>
        <tr>
            <th colspan="6" style="font-size: 16px; height: 40px; vertical-align: middle; background-color: #e2e8f0; text-align: center;">
                LAPORAN STOK & KEBUTUHAN PRODUKSI - YAYASAN NUSA PUTERA
            </th>
        </tr>
        <tr style="background-color: #f1f5f9; height: 30px;">
            <th style="width: 200px;">Nama Produk</th>
            <th style="width: 100px;">Unit/Jenjang</th>
            <th style="width: 80px;">Ukuran</th>
            <th style="width: 100px;">Stok Real</th>
            <th style="width: 100px;">Target Tahunan</th>
            <th style="width: 150px;">Status / Defisit</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if(mysqli_num_rows($result) > 0):
            while($row = mysqli_fetch_assoc($result)): 
                $stok = $row['stok'];
                $target = $row['target_tahunan'];
                $defisit = $target - $stok;
                $is_defisit = ($defisit > 0);
                
                // Pewarnaan Baris
                $bg_color = $is_defisit ? "#fee2e2" : "#ffffff";
                $text_color = $is_defisit ? "#b91c1c" : "#000000";
                $status_text = $is_defisit ? "KURANG " . $defisit : "AMAN";
        ?>
        <tr style="background-color: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>;">
            <td style="vertical-align: middle;"><?php echo $row['nama_produk']; ?></td>
            <td style="text-align: center; vertical-align: middle;"><?php echo $row['jenjang']; ?></td>
            <td style="text-align: center; vertical-align: middle;"><b><?php echo $row['ukuran']; ?></b></td>
            <td style="text-align: center; vertical-align: middle;">
                <?php echo number_format($stok); ?>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <?php echo number_format($target); ?>
            </td>
            <td style="text-align: center; vertical-align: middle; font-weight: bold;">
                <?php echo $status_text; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php else: ?>
        <tr><td colspan="6" align="center" style="height: 50px;">Tidak ada data stok yang sesuai filter.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<br>
<p><i>Laporan ini diunduh pada: <?php echo date('d/m/Y H:i'); ?> oleh <?php echo $_SESSION['nama_lengkap']; ?></i></p>