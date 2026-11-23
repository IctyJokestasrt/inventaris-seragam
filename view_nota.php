<?php
include 'config/koneksi.php';

if (!isset($_GET['id'])) {
    die("Nota tidak ditemukan.");
}

$id_order = mysqli_real_escape_string($conn, $_GET['id']);

// 1. Ambil Data Utama Pesanan
$q_order = mysqli_query($conn, "SELECT o.*, w.nama_gelombang FROM orders o LEFT JOIN waves w ON o.id_wave = w.id_wave WHERE o.id_order = '$id_order'");
$order = mysqli_fetch_assoc($q_order);

if (!$order) { die("Data pesanan tidak ditemukan."); }

// 2. Ambil Detail Barang
$q_detail = mysqli_query($conn, "
    SELECT p.nama_produk, v.ukuran, od.qty, od.catatan_item 
    FROM order_details od
    JOIN variants v ON od.id_variant = v.id_variant
    JOIN products p ON v.id_produk = p.id_produk
    WHERE od.id_order = '$id_order'
");

$kode_transaksi = "ORD-" . $id_order;
$status = $order['status_order'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Digital - <?php echo htmlspecialchars($order['nama_siswa']); ?></title>
    <!-- JsBarcode Library -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f1f5f9; padding: 20px; color: #334155; margin: 0; }
        .container { max-width: 500px; margin: auto; background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-thermal { background: #1e293b; color: white; border: none; padding: 12px 20px; border-radius: 50px; cursor: pointer; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; }

        .header { text-align: center; border-bottom: 2px dashed #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #1e293b; font-size: 20px; text-transform: uppercase; }
        
        /* Status Badge */
        .status-badge { display: inline-block; padding: 6px 15px; border-radius: 8px; font-weight: bold; font-size: 12px; text-transform: uppercase; margin-top: 10px; }
        .status-po { background: #1e293b; color: #fff; }
        .status-ready { background: #10b981; color: #fff; } 
        .status-diambil { background: #3b82f6; color: #fff; } 
        .status-batal { background: #ef4444; color: #fff; }

        .barcode-area { text-align: center; margin: 20px 0; }
        #barcodeImg { max-width: 100%; height: 60px; }

        .info-row { margin-bottom: 15px; }
        .info-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .info-value { font-size: 16px; font-weight: 800; color: #1e293b; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; font-size: 12px; color: #64748b; border-bottom: 2px solid #f1f5f9; padding: 10px 5px; text-transform: uppercase; }
        td { padding: 12px 5px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; vertical-align: top; }

        .delivery-box { background: #eff6ff; border: 1px solid #bfdbfe; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: center; color: #1e3a8a; }
        .footer { margin-top: 30px; font-size: 11px; color: #94a3b8; text-align: center; line-height: 1.6; }

        /* FORMAT KHUSUS PRINTER THERMAL */
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            .container { 
                width: 80mm;
                max-width: 80mm;
                box-shadow: none; 
                padding: 5mm;
                margin: 0;
                border: none;
            }
            .status-badge { border: 1px solid #000; color: #000; background: #fff; }
            .header h2 { font-size: 16px; }
            td, th { font-size: 12px; }
            .info-value { font-size: 14px; }
        }
    </style>
</head>
<body onload="generateBarcode()">

    <!-- Tombol Cetak Struk (Hanya Muncul di Layar) -->
    <div class="no-print">
        <button onclick="window.print()" class="btn-thermal">
            CETAK STRUK (THERMAL)
        </button>
    </div>

    <div class="container">
        <div class="header">
            <h2>KEDAI NUSAPUTERA</h2>
            <p style="margin:5px 0; font-size:11px; color: #64748b;">Bukti Pengukuran Seragam Resmi</p>
            
            <?php 
                $class_status = "status-po";
                if($status == 'ready') $class_status = "status-ready";
                if($status == 'diambil') $class_status = "status-diambil";
                if($status == 'batal') $class_status = "status-batal";
            ?>
            <div class="status-badge <?php echo $class_status; ?>">
                Status: <?php echo strtoupper($status); ?>
            </div>
        </div>

        <div class="barcode-area">
            <svg id="barcodeImg"></svg>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 5px;"><?php echo $kode_transaksi; ?></div>
        </div>

        <div class="info-row">
            <div class="info-label">Nama Siswa</div>
            <div class="info-value"><?php echo strtoupper(htmlspecialchars($order['nama_siswa'])); ?></div>
        </div>

        <div class="info-row">
            <div class="info-label">Unit / Kelas</div>
            <div style="font-size:14px; font-weight: 600;"><?php echo htmlspecialchars($order['jenjang_siswa']); ?> - <?php echo htmlspecialchars($order['kelas_calon']); ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ITEM</th>
                    <th style="text-align:center">SIZE</th>
                    <th style="text-align:center">QTY</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($q_detail)): ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($row['nama_produk']); ?>
                        <?php if(!empty($row['catatan_item'])): ?>
                            <br><small style="color:#64748b; font-size:10px; font-style: italic;"><?php echo htmlspecialchars($row['catatan_item']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center"><b><?php echo htmlspecialchars($row['ukuran']); ?></b></td>
                    <td style="text-align:center"><?php echo (int)$row['qty']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- INFO SERAH TERIMA -->
        <?php if($status == 'diambil'): ?>
            <div class="delivery-box">
                <small style="font-weight:bold; text-transform:uppercase;">Sudah Diserahkan</small>
                <div style="font-size:13px; margin-top:5px;">
                    Penerima: <b><?php echo strtoupper(htmlspecialchars($order['nama_pengambil'])); ?></b><br>
                    Waktu: <?php echo date('d/m/Y H:i', strtotime($order['tgl_ambil'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="footer">
            Tgl Pengukuran: <?php echo date('d/m/Y', strtotime($order['tgl_ukur'])); ?><br>
            Gelombang: <?php echo htmlspecialchars($order['nama_gelombang'] ?? '-'); ?><br><br>
            <i>*Tunjukkan Barcode ini kepada petugas kedai saat pengambilan barang fisik.</i>
        </div>
    </div>

    <script>
        function generateBarcode() {
            JsBarcode("#barcodeImg", "<?php echo $kode_transaksi; ?>", {
                format: "CODE128",
                width: 2,
                height: 50,
                displayValue: false,
                lineColor: "#000"
            });
        }
    </script>
</body>
</html>