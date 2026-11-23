<?php
// Pastikan tidak ada spasi atau baris kosong sebelum tag php ini
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Akses: Hanya Admin atau Manager
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: index.php"); 
    exit;
}

// 1. Ambil Parameter Filter
$f_jenjang = $_GET['jenjang'] ?? 'ALL';
$where_clause = ($f_jenjang != 'ALL') ? "WHERE p.jenjang = '$f_jenjang'" : "";

// 2. Set Konfigurasi Nama File dan Header Excel
$filename = "AUDIT_MUTASI_STOK_" . $f_jenjang . "_" . date('d-m-Y_H-i') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// 3. Query Data Mutasi (Logika yang sama dengan laporan_audit.php)
$sql = "
    SELECT 
        p.kategori,
        p.nama_produk,
        v.ukuran,
        v.stok AS stok_akhir,
        IFNULL(logs.total_masuk, 0) AS masuk,
        IFNULL(logs.total_keluar, 0) AS keluar
    FROM variants v
    JOIN products p ON v.id_produk = p.id_produk
    LEFT JOIN (
        SELECT 
            id_variant,
            SUM(CASE WHEN jenis_aksi = 'MASUK' THEN qty ELSE 0 END) as total_masuk,
            SUM(CASE WHEN jenis_aksi = 'KELUAR' OR jenis_aksi = 'RUSAK' THEN qty ELSE 0 END) as total_keluar
        FROM stock_logs
        GROUP BY id_variant
    ) AS logs ON v.id_variant = logs.id_variant
    $where_clause
    ORDER BY p.kategori ASC, p.nama_produk ASC
";

$result = mysqli_query($conn, $sql);
$tahun_ajaran = date('Y') . "/" . (date('Y') + 1);
?>

<!-- TABEL UNTUK EXCEL -->
<table border="1">
    <thead>
        <tr>
            <th colspan="8" style="font-size: 14px; font-weight: bold; height: 30px; vertical-align: middle;">
                LAPORAN AUDIT MUTASI STOK KEDAI NUSAPUTERA
            </th>
        </tr>
        <tr>
            <th colspan="8" style="font-size: 12px; font-weight: bold;">
                Unit Jenjang: <?php echo $f_jenjang; ?> | Periode Tahun Ajaran: <?php echo $tahun_ajaran; ?>
            </th>
        </tr>
        <tr>
            <th colspan="8" style="font-size: 10px; font-style: italic; text-align: left;">
                Waktu Unduh: <?php echo date('d/m/Y H:i'); ?>
            </th>
        </tr>
        <tr></tr> <!-- Baris Kosong Jarak -->
        
        <tr style="background-color: #e2e8f0; font-weight: bold;">
            <th width="50">NO</th>
            <th width="100">TAHUN</th>
            <th width="120">KATEGORI</th>
            <th width="250">NAMA SERAGAM & UKURAN</th>
            <th width="100">STOK AWAL</th>
            <th width="100">MASUK (+)</th>
            <th width="100">KELUAR (-)</th>
            <th width="100">STOK AKHIR</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        if(mysqli_num_rows($result) > 0):
            while($row = mysqli_fetch_assoc($result)): 
                // Kalkulasi Stok Awal: Stok Sekarang - Masuk + Keluar
                $stok_awal = $row['stok_akhir'] - $row['masuk'] + $row['keluar'];
        ?>
        <tr>
            <td align="center"><?php echo $no++; ?></td>
            <td align="center"><?php echo $tahun_ajaran; ?></td>
            <td align="center"><?php echo $row['kategori']; ?></td>
            <td><?php echo $row['nama_produk'] . " (Size: " . $row['ukuran'] . ")"; ?></td>
            <td align="center" style="background-color: #f8fafc;"><?php echo $stok_awal; ?></td>
            <td align="center" style="color: #16a34a; font-weight: bold;"><?php echo $row['masuk']; ?></td>
            <td align="center" style="color: #dc2626; font-weight: bold;"><?php echo $row['keluar']; ?></td>
            <td align="center" style="font-weight: bold; background-color: #f1f5f9;"><?php echo $row['stok_akhir']; ?></td>
        </tr>
        <?php 
            endwhile; 
        else:
        ?>
        <tr>
            <td colspan="8" align="center" style="height: 50px;">Tidak ada data mutasi stok ditemukan untuk unit ini.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<br>
<p style="font-size: 10px;">
    <i>Laporan ini dihasilkan secara otomatis oleh Sistem Kedai Nusaputera.</i>
</p>