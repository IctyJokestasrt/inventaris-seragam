<?php
// PASTIKAN TIDAK ADA SPASI ATAU BARIS KOSONG SEBELUM TAG PHP INI
include '../config/koneksi.php';
include '../config/helper.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    exit;
}

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_sampai = $_GET['tgl_sampai'] ?? date('Y-m-d');

$where_clause = "WHERE total_biaya > 0 AND DATE(tgl_ukur) BETWEEN '$tgl_mulai' AND '$tgl_sampai'";

$sql_data = "SELECT * FROM orders $where_clause ORDER BY tgl_ukur ASC";
$q_data = mysqli_query($conn, $sql_data);

// Membersihkan buffer agar tidak terjadi error corrupt excel
ob_clean();

$filename = "LAPORAN_KEUANGAN_RETAIL_" . $tgl_mulai . "_SD_" . $tgl_sampai . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>

<table border="1">
    <tr>
        <th colspan="6" style="font-size: 16px;">LAPORAN PEMASUKAN TUNAI (RETAIL KOPERASI)</th>
    </tr>
    <tr>
        <th colspan="6">Periode: <?php echo date('d/m/Y', strtotime($tgl_mulai)); ?> s/d <?php echo date('d/m/Y', strtotime($tgl_sampai)); ?></th>
    </tr>
    <tr>
        <th colspan="6">Dicetak oleh: <?php echo strtoupper($_SESSION['username']); ?> pada <?php echo date('d/m/Y H:i'); ?></th>
    </tr>
    <tr><th colspan="6"></th></tr>

    <tr style="background-color: #f2f2f2;">
        <th>NO</th>
        <th>TANGGAL & WAKTU</th>
        <th>NO. NOTA</th>
        <th>NAMA PEMBELI</th>
        <th>UNIT</th>
        <th>NOMINAL (Rp)</th>
    </tr>

    <?php 
    $no = 1;
    $grand_total = 0;
    while($row = mysqli_fetch_assoc($q_data)): 
        $grand_total += $row['total_biaya'];
    ?>
    <tr>
        <td align="center"><?php echo $no++; ?></td>
        <td align="center"><?php echo date('d/m/Y H:i', strtotime($row['tgl_ukur'])); ?></td>
        <td align="center">ORD-<?php echo $row['id_order']; ?></td>
        <td><?php echo strtoupper($row['nama_siswa']); ?></td>
        <td align="center"><?php echo $row['jenjang_siswa']; ?></td>
        <td align="right"><?php echo $row['total_biaya']; ?></td>
    </tr>
    <?php endwhile; ?>

    <tr style="background-color: #e2e8f0; font-weight: bold;">
        <td colspan="5" align="right">TOTAL PEMASUKAN PERIODE INI</td>
        <td align="right"><?php echo $grand_total; ?></td>
    </tr>
</table>
<?php exit; ?>