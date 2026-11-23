<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Akses
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: index.php"); exit;
}

// 1. Tentukan Jenjang dari URL
if (!isset($_GET['jenjang'])) {
    die("Error: Pilih jenjang terlebih dahulu.");
}
$jenjang_cetak = mysqli_real_escape_string($conn, $_GET['jenjang']);
$id_gelombang = mysqli_real_escape_string($conn, $_GET['gelombang'] ?? '');

// 2. Ambil Daftar Produk untuk Header Tabel (Hanya Seragam & Olahraga)
$produk_header = [];
$q_header = mysqli_query($conn, "
    SELECT id_produk, nama_produk 
    FROM products 
    WHERE jenjang = '$jenjang_cetak' AND is_active = 1 
    AND kategori IN ('SERAGAM', 'OLAHRAGA')
    ORDER BY kategori ASC, nama_produk ASC
");
while ($h = mysqli_fetch_assoc($q_header)) {
    $produk_header[$h['id_produk']] = $h['nama_produk'];
}

// 3. Ambil Data Siswa (Orders)
$where_order = "jenjang_siswa = '$jenjang_cetak'";
if(!empty($id_gelombang)) {
    $where_order .= " AND id_wave = '$id_gelombang'";
}

$q_orders = mysqli_query($conn, "
    SELECT id_order, nama_siswa, tgl_ukur 
    FROM orders 
    WHERE $where_order
    ORDER BY nama_siswa ASC
");

// 4. Ambil Detail Ukuran DAN Teks Bordir
$detail_pesanan = [];
$bordir_texts = []; // Array khusus menampung teks bordir

$q_details = mysqli_query($conn, "
    SELECT od.id_order, v.id_produk, v.ukuran, od.catatan_item, p.nama_produk
    FROM order_details od
    JOIN variants v ON od.id_variant = v.id_variant
    JOIN products p ON v.id_produk = p.id_produk
");

while ($d = mysqli_fetch_assoc($q_details)) {
    // Simpan ukuran untuk tabel utama
    $detail_pesanan[$d['id_order']][$d['id_produk']] = $d['ukuran'];
    
    // Khusus jika itemnya adalah 'Bordir Nama', ambil catatan_item-nya
    if ($d['nama_produk'] == 'Bordir Nama') {
        // Bersihkan teks "Teks Bordir: " agar hanya muncul namanya saja
        $clean_text = str_ireplace('Teks Bordir: ', '', $d['catatan_item']);
        $bordir_texts[$d['id_order']] = $clean_text;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Pengukuran - <?php echo $jenjang_cetak; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #fff; margin: 0; padding: 20px; color: #000; }
        .header-laporan { text-align: center; margin-bottom: 30px; }
        .header-laporan h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header-laporan h2 { margin: 5px 0; font-size: 14px; font-weight: normal; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 2px solid #000; padding: 8px 5px; text-align: center; }
        th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; }
        td.nama-siswa { text-align: left; padding-left: 10px; font-weight: bold; width: 200px; }
        .no-urut { width: 30px; }
        .no-nota { width: 70px; }
        .col-bordir { width: 150px; font-weight: bold; text-align: left; padding-left: 10px; background-color: #fafafa; }
        .no-print-area { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 20px; }
        .btn { padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 14px; cursor: pointer; border: none; font-weight: bold; }
        .btn-print { background: #334155; color: #fff; margin-right: 10px; }
        .btn-back { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { padding: 0; }
            .no-print-area { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print-area">
        <button onclick="window.print()" class="btn btn-print">🖨️ CETAK REKAPITULASI</button>
        <a href="laporan_matriks.php" class="btn btn-back">KEMBALI</a>
    </div>

    <div class="header-laporan">
        <h1>REKAPITULASI PENGUKURAN SERAGAM <?php echo ($jenjang_cetak == 'TK' ? 'PG / TK' : $jenjang_cetak); ?></h1>
        <h2>TAHUN AJARAN <?php echo date('Y') . "/" . (date('Y') + 1); ?></h2>
    </div>

    <table>
        <thead>
            <tr>
                <th class="no-urut" rowspan="2">NO</th>
                <th rowspan="2">NAMA SISWA</th>
                <th class="no-nota" rowspan="2">NO. NOTA</th>
                <th colspan="<?php echo count($produk_header); ?>">JENIS SERAGAM (UKURAN)</th>
                <th rowspan="2">TEKS BORDIR NAMA</th>
            </tr>
            <tr>
                <?php foreach($produk_header as $id_p => $nama_p): ?>
                    <th><?php echo str_ireplace([' TK', ' SD', ' SMP', ' SMA', ' SMK'], '', $nama_p); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if (mysqli_num_rows($q_orders) > 0):
                while($siswa = mysqli_fetch_assoc($q_orders)):
                    $id_o = $siswa['id_order'];
                    $nota = sprintf('%03d', $id_o) . '/' . date('y', strtotime($siswa['tgl_ukur']));
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td class="nama-siswa"><?php echo strtoupper($siswa['nama_siswa']); ?></td>
                <td><?php echo $nota; ?></td>

                <?php foreach($produk_header as $id_p => $nama_p): ?>
                    <td><?php echo $detail_pesanan[$id_o][$id_p] ?? '-'; ?></td>
                <?php endforeach; ?>
                
                <!-- KOLOM BORDIR NAMA (AMBIL DARI ARRAY bordir_texts) -->
                <td class="col-bordir">
                    <?php echo $bordir_texts[$id_o] ?? '-'; ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
                <td colspan="<?php echo 4 + count($produk_header); ?>" style="padding: 50px;">Belum ada data pesanan.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>