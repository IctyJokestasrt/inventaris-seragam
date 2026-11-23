<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Akses (Bisa diakses Admin dan Manager)
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: index.php"); 
    exit;
}

// --- LOGIKA FILTER TANGGAL ---
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01'); // Default awal bulan
$tgl_sampai = $_GET['tgl_sampai'] ?? date('Y-m-d'); // Default hari ini

// Query hanya mengambil order yang total biayanya > 0 (Transaksi Penjualan Langsung)
$where_clause = "WHERE total_biaya > 0 AND DATE(tgl_ukur) BETWEEN '$tgl_mulai' AND '$tgl_sampai'";

// 1. Ambil Statistik Global
$q_stats = mysqli_query($conn, "SELECT SUM(total_biaya) as grand_total, COUNT(id_order) as total_trx FROM orders $where_clause");
$stats = mysqli_fetch_assoc($q_stats);
$grand_total = $stats['grand_total'] ?? 0;
$total_trx = $stats['total_trx'] ?? 0;

// 2. Ambil Data Tabel
$sql_data = "SELECT * FROM orders $where_clause ORDER BY tgl_ukur DESC";
$q_data = mysqli_query($conn, $sql_data);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Penjualan Tunai - Kedai Nusaputera</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body, input, select, button { font-family: 'Inter', 'Segoe UI', sans-serif !important; }
        .main-content { padding: 30px 40px; background: #f8fafc; }
        
        /* HEADER & TOMBOL EXPORT */
        .page-header {
            display: flex; justify-content: space-between; align-items: flex-end; 
            margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px dashed #cbd5e1;
        }
        .btn-export {
            background: #10b981; color: white; padding: 10px 20px; border-radius: 8px;
            font-size: 13px; font-weight: 700; text-decoration: none;
            display: flex; align-items: center; gap: 8px; transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
        }
        .btn-export:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 6px 10px -1px rgba(16, 185, 129, 0.3); }

        /* SUMMARY CARDS - MODERN CLEAN STYLE */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
        .stat-card { 
            background: #fff; padding: 25px; border-radius: 16px; 
            border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            display: flex; align-items: center; gap: 20px; position: relative; overflow: hidden;
        }
        .stat-icon-wrapper {
            width: 65px; height: 65px; border-radius: 16px;
            display: flex; justify-content: center; align-items: center; font-size: 32px;
        }
        .icon-green { background: #dcfce7; color: #16a34a; }
        .icon-blue { background: #eff6ff; color: #2563eb; }
        
        .stat-info { display: flex; flex-direction: column; }
        .stat-title { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 5px; }
        .stat-value { font-size: 32px; font-weight: 800; color: #1e293b; line-height: 1; letter-spacing: -1px; font-family: inherit;}

        /* Card Customizer */
        .card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

        /* Filter Area */
        .filter-area { padding: 20px 25px; margin-bottom: 25px; display: flex; gap: 15px; align-items: flex-end;}
        .label-sm { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block; }

        /* Data List Styling */
        .data-header {
            display: grid; grid-template-columns: 120px 200px 1fr 150px; gap: 20px;
            padding: 15px 25px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
            font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;
        }
        
        .data-row {
            display: grid; grid-template-columns: 120px 200px 1fr 150px; gap: 20px;
            padding: 20px 25px; border-bottom: 1px solid #f1f5f9; align-items: center;
        }
        .data-row:hover { background: #fcfcfc; }
        .data-row:last-child { border-bottom: none; }

        .data-id { color: var(--primary); font-weight: 800; font-size: 14px; }
        .data-date { color: #94a3b8; font-size: 11px; margin-top: 4px; display: block; }
        .data-name { font-weight: 700; font-size: 14px; text-transform: uppercase; color: #1e293b; }
        .item-list-container { font-size: 12px; color: #475569; line-height: 1.6; }
        .data-nominal { font-size: 16px; font-weight: 800; color: #16a34a; text-align: right; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <!-- HEADER PENGATURAN BARU -->
        <div class="page-header">
            <div>
                <h2 style="margin:0; letter-spacing: -1px; font-size: 24px; color: #1e293b;">
                    <i class="ph-fill ph-chart-line-up" style="color: var(--primary); margin-right: 8px;"></i> Rekap Penjualan Tunai
                </h2>
                <p style="margin:4px 0 0; color:#64748b; font-size: 13px;">Laporan pendapatan dari penjualan retail & dadakan.</p>
            </div>
            <a href="export_keuangan.php?tgl_mulai=<?php echo $tgl_mulai; ?>&tgl_sampai=<?php echo $tgl_sampai; ?>" class="btn-export">
                <i class="ph-bold ph-file-xls" style="font-size:18px;"></i> Export Laporan
            </a>
        </div>

        <!-- STATISTIK KEUANGAN (DESAIN BARU) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon-wrapper icon-green">
                    <i class="ph-fill ph-money"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Total Pemasukan Tunai</span>
                    <span class="stat-value">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon-wrapper icon-blue">
                    <i class="ph-fill ph-receipt"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Total Transaksi Selesai</span>
                    <span class="stat-value"><?php echo number_format($total_trx); ?> <span style="font-size: 16px; font-weight: 600; opacity: 0.5;">Nota</span></span>
                </div>
            </div>
        </div>

        <!-- FILTER TANGGAL -->
        <div class="card filter-area">
            <form method="GET" style="display:flex; gap:15px; width: 100%; align-items: flex-end;">
                <div style="flex:1;">
                    <label class="label-sm">Dari Tanggal</label>
                    <input type="date" name="tgl_mulai" class="form-control" value="<?php echo $tgl_mulai; ?>" style="background: #f8fafc; margin: 0;">
                </div>
                <div style="flex:1;">
                    <label class="label-sm">Sampai Tanggal</label>
                    <input type="date" name="tgl_sampai" class="form-control" value="<?php echo $tgl_sampai; ?>" style="background: #f8fafc; margin: 0;">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="padding:10px 25px; border-radius: 8px; margin: 0;">
                        <i class="ph-bold ph-funnel"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- DATA LIST -->
        <div class="card" style="padding:0; overflow:hidden;">
            <div class="data-header">
                <div>Waktu & Nota</div>
                <div>Pembeli / Unit</div>
                <div>Rincian Pembelian</div>
                <div style="text-align:right;">Nominal Transaksi</div>
            </div>

            <div class="data-body">
                <?php 
                if(mysqli_num_rows($q_data) == 0) {
                    echo "<div style='padding:60px; text-align:center; color:#94a3b8;'>
                            <i class='ph ph-receipt' style='font-size:48px; opacity:0.3; display:block; margin-bottom:15px;'></i>
                            Tidak ada transaksi keuangan pada periode ini.
                          </div>";
                }
                
                while($row = mysqli_fetch_assoc($q_data)): 
                    $ido = $row['id_order'];
                ?>
                <div class="data-row">
                    <div>
                        <span class="data-id">#ORD-<?php echo $ido; ?></span>
                        <span class="data-date"><?php echo date('d M Y, H:i', strtotime($row['tgl_ukur'])); ?></span>
                    </div>
                    
                    <div>
                        <div class="data-name"><?php echo $row['nama_siswa']; ?></div>
                        <span class="badge" style="background:#eef2ff; color:var(--primary); font-size:10px; margin-top:6px; font-weight: 700; border: 1px solid #bfdbfe;"><?php echo $row['jenjang_siswa']; ?></span>
                    </div>
                    
                    <div class="item-list-container">
                        <?php 
                        $q_item = mysqli_query($conn, "SELECT p.nama_produk, v.ukuran, od.qty FROM order_details od JOIN variants v ON od.id_variant=v.id_variant JOIN products p ON v.id_produk=p.id_produk WHERE od.id_order='$ido'");
                        $items =[];
                        while($it = mysqli_fetch_assoc($q_item)){ 
                            $items[] = "<b>{$it['qty']}x</b> {$it['nama_produk']} <span style='color:#94a3b8;'>(Size: {$it['ukuran']})</span>"; 
                        }
                        echo implode("<br>", $items);
                        ?>
                    </div>
                    
                    <div class="data-nominal">
                        Rp <?php echo number_format($row['total_biaya'], 0, ',', '.'); ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

    </div>
</body>
</html>