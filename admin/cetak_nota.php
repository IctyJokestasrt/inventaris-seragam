<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Ambil ID dari URL
$id_order = mysqli_real_escape_string($conn, $_GET['id']);

// 1. Ambil Data Siswa
$q_order = mysqli_query($conn, "SELECT * FROM orders WHERE id_order = '$id_order'");
$order = mysqli_fetch_assoc($q_order);

if (!$order) {
    echo "Data pesanan tidak ditemukan.";
    exit;
}

// 2. Ambil Rincian Barang (FIXED: Menggunakan nama kolom 'catatan_item')
$q_detail = mysqli_query($conn, "
    SELECT p.nama_produk, v.ukuran, od.qty, od.catatan_item 
    FROM order_details od
    JOIN variants v ON od.id_variant = v.id_variant
    JOIN products p ON v.id_produk = p.id_produk
    WHERE od.id_order = '$id_order'
");

$kode_transaksi = "ORD-" . $id_order;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota #<?php echo $id_order; ?> - <?php echo $order['nama_siswa']; ?></title>
    <!-- Library Barcode -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <style>
        /* CSS Formal & Minimalis (Zero Finance) */
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; }
        body { 
            font-family: 'Arial', sans-serif; 
            margin: 0; padding: 0; 
            color: #000; 
            background: #fff;
            line-height: 1.4;
        }

        .container {
            width: 750px;
            margin: auto;
            padding: 40px;
        }

        /* Navigasi Layar (Tidak ikut diprint) */
        .no-print-nav {
            background: #f1f5f9;
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        /* Header Nota */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .brand h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .brand p { margin: 3px 0; font-size: 11px; line-height: 1.4; }
        
        .invoice-title { text-align: right; }
        .invoice-title h2 { margin: 0; font-size: 22px; }
        #barcodeImg { margin-top: 5px; height: 50px; }

        /* Info Pengukuran */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            font-size: 13px;
        }
        .info-box { width: 50%; }
        .info-box strong { font-size: 16px; display: block; margin-bottom: 5px; text-transform: uppercase; }

        /* Tabel Rincian Barang (Tanpa Harga) */
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th { background: #f2f2f2; padding: 12px; border: 1px solid #000; font-size: 11px; text-transform: uppercase; text-align: left; }
        td { padding: 12px; border: 1px solid #000; font-size: 13px; }

        /* Area Tanda Tangan */
        .signature-section {
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-top: 50px;
        }
        .sig-box { width: 250px; }
        .sig-space { height: 80px; } 
        .sig-line { border-bottom: 1px solid #000; display: inline-block; width: 220px; margin-bottom: 5px; }

        .footer-note { 
            margin-top: 60px; 
            font-size: 10px; 
            color: #444; 
            font-style: italic; 
            border-top: 1px solid #eee; 
            padding-top: 10px; 
        }

        @media print {
            .no-print-nav { display: none; }
            .container { width: 100%; padding: 0; margin: 0; }
            body { background: #fff; }
            @page { margin: 1.5cm; }
        }

        .btn {
            padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; color: white; text-decoration: none; display: inline-block; margin: 0 5px;
        }
        .btn-print { background: #4f46e5; }
        .btn-close { background: #64748b; }
    </style>
</head>
<body onload="generateBarcode()">

    <div class="no-print-nav">
        <button onclick="window.print()" class="btn btn-print">🖨️ CETAK NOTA SEKARANG</button>
        <button onclick="window.close()" class="btn btn-close">TUTUP</button>
    </div>

    <div class="container">
        
        <!-- HEADER -->
        <div class="header">
            <div class="brand">
                <h1>KEDAI NUSAPUTERA</h1>
                <p>Yayasan Pendidikan Nusa Putera - Unit Inventaris Seragam</p>
                <p>Jl. Raya Pendidikan No. 123, Jawa Tengah | Telp: (021) 12345678</p>
            </div>
            <div class="invoice-title">
                <h2>BUKTI PENGUKURAN</h2>
                <p>Ref: #<strong><?php echo $kode_transaksi; ?></strong></p>
                <p>Tgl: <?php echo date('d M Y', strtotime($order['tgl_ukur'])); ?></p>
                <svg id="barcodeImg"></svg>
            </div>
        </div>

        <!-- INFO SISWA -->
        <div class="info-section">
            <div class="info-box">
                <small>Diberikan Kepada:</small>
                <strong><?php echo $order['nama_siswa']; ?></strong>
                Kelas: <?php echo $order['kelas_calon']; ?> (Unit: <?php echo $order['jenjang_siswa']; ?>)<br>
                Bordir Nama: <b><?php echo $order['bordir_nama'] ?: '-'; ?></b>
            </div>
            <div class="info-box" style="text-align: right;">
                <small>Status Pengambilan:</small><br>
                <div style="display:inline-block; border:2px solid #000; padding:5px 15px; font-weight:bold; font-size:14px; margin-top:5px;">
                    <?php echo strtoupper($order['status_order']); ?>
                </div>
            </div>
        </div>

        <!-- TABEL RINCIAN BARANG (MURNI INVENTARIS) -->
        <table>
            <thead>
                <tr>
                    <th width="40" style="text-align:center;">No</th>
                    <th>Nama Pakaian / Seragam</th>
                    <th width="100" style="text-align:center;">Size</th>
                    <th width="60" style="text-align:center;">Qty</th>
                    <th>Catatan Khusus (LD/PJ)</th>
                </tr>
            </thead>
            <tbody>
                <?php $n=1; while($row = mysqli_fetch_assoc($q_detail)): ?>
                <tr>
                    <td align="center"><?php echo $n++; ?></td>
                    <td><?php echo $row['nama_produk']; ?></td>
                    <td align="center"><b><?php echo $row['ukuran']; ?></b></td>
                    <td align="center"><?php echo $row['qty']; ?></td>
                    <td><?php echo $row['catatan_item'] ?: '-'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- AREA TANDA TANGAN MANUAL (KOSONG) -->
        <div class="signature-section">
            <div class="sig-box">
                <p>Kedai,</p>
                <div class="sig-space"></div>
                <div class="sig-line"></div>
                <p>( Nama Terang & Cap )</p>
            </div>
            <div class="sig-box">
                <p>Tanda Terima (Penerima),</p>
                <div class="sig-space"></div>
                <div class="sig-line"></div>
                <p>( Nama Terang / Siswa )</p>
            </div>
        </div>

        <!-- CATATAN -->
        <div class="footer-note">
            <strong>Perhatian:</strong> Bukti ini wajib dibawa saat pengambilan barang fisik di kedai koperasi. Pastikan rincian ukuran di atas sudah sesuai sebelum meninggalkan lokasi pengukuran.
        </div>

    </div>

    <script>
        function generateBarcode() {
            JsBarcode("#barcodeImg", "<?php echo $kode_transaksi; ?>", {
                format: "CODE128", width: 1.5, height: 40, displayValue: true, fontSize: 12
            });
        }
    </script>
</body>
</html>