<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Ambil Role dari Session
$role = $_SESSION['role'] ?? 'admin';

// --- 1. LOGIC QUERY STATISTIK STOK (Murni Barang Fisik) ---
$where_clause = ($jenjang_aktif != 'ALL') ? " WHERE p.jenjang = '$jenjang_aktif'" : "";

$q_stok = mysqli_query($conn, "
    SELECT 
        SUM(v.stok) as total_stok, 
        SUM(v.target_tahunan) as total_target 
    FROM variants v 
    JOIN products p ON v.id_produk = p.id_produk 
    $where_clause
");
$d_stok = mysqli_fetch_assoc($q_stok);

// Hitung Defisit (Kekurangan Stok Fisik)
$defisit = max(0, ($d_stok['total_target'] ?? 0) - ($d_stok['total_stok'] ?? 0));

// --- 2. LOGIC DATA DISTRIBUSI ---
$where_dist = ($jenjang_aktif != 'ALL') ? " AND jenjang_siswa = '$jenjang_aktif'" : "";

// Hitung barang yang sudah diserahkan (Status: diambil)
$q_diserahkan = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status_order = 'diambil' $where_dist");
$jml_diserahkan = mysqli_fetch_assoc($q_diserahkan)['total'] ?? 0;

// Hitung antrean yang masih menunggu barang (Status: proses/po)
$q_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status_order IN ('proses', 'ukur') $where_dist");
$jml_pending = mysqli_fetch_assoc($q_pending)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kedai - Nusa Putera</title>
    <!-- CSS & Fonts -->
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>

    <!-- SIDEBAR COMPONENT (Receipt Style - Light Mode) -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <!-- HEADER AREA -->
        <div class="header-area">
            <div>
                <h2 style="margin:0; font-size: 26px; letter-spacing: -1px;">Dashboard Kedai</h2>
                <p style="margin:5px 0 0; color:var(--text-muted); font-size:14px;">
                    Logistik & Inventaris Unit: <span style="color:var(--primary); font-weight:700;"><?php echo $jenjang_aktif; ?></span>
                </p>
            </div>
            
            <!-- Jenjang Selector (Pill Style) -->
            <div class="jenjang-selector">
                <?php 
                $list_jenjang = ['ALL', 'TK', 'SD', 'SMP', 'SMA', 'SMK'];
                foreach($list_jenjang as $j) {
                    $active = ($jenjang_aktif == $j) ? 'active' : '';
                    echo "<a href='../config/helper.php?set_jenjang=$j' class='jenjang-btn $active'>$j</a>";
                }
                ?>
            </div>
        </div>

        <!-- STATS GRID (LOGISTIK VERSION) -->
        <div class="stats-grid">
            
            <!-- Card 1: Stok Fisik -->
            <div class="stat-card card-blue">
                <div class="stat-header">
                    <div class="stat-icon"><i class="ph-fill ph-package"></i></div>
                    <span class="badge bg-blue">GUDANG</span>
                </div>
                <div class="stat-value"><?php echo number_format($d_stok['total_stok'] ?? 0); ?></div>
                <div class="stat-label">Total Unit Tersedia</div>
            </div>

            <!-- Card 2: Defisit (Sesuai Revisi) -->
            <div class="stat-card card-red">
                <div class="stat-header">
                    <div class="stat-icon"><i class="ph-fill ph-warning-octagon"></i></div>
                    <span class="badge bg-red">DEFISIT</span>
                </div>
                <div class="stat-value"><?php echo number_format($defisit); ?></div>
                <div class="stat-label">Kekurangan Stok</div>
            </div>

            <!-- Card 3: Pesanan Menunggu -->
            <div class="stat-card card-orange">
                <div class="stat-header">
                    <div class="stat-icon"><i class="ph-fill ph-clock-counter-clockwise"></i></div>
                    <span class="badge bg-yellow">PENDING</span>
                </div>
                <div class="stat-value"><?php echo $jml_pending; ?></div>
                <div class="stat-label">Antrean Belum Diserahkan</div>
            </div>

            <!-- Card 4: Sukses Distribusi -->
            <div class="stat-card card-green">
                <div class="stat-header">
                    <div class="stat-icon" style="background:#dcfce7; color:#15803d;"><i class="ph-fill ph-check-circle"></i></div>
                    <span class="badge bg-green">SUCCESS</span>
                </div>
                <div class="stat-value"><?php echo $jml_diserahkan; ?></div>
                <div class="stat-label">Barang Sudah Diserahkan</div>
            </div>

        </div>

        <!-- QUICK ACTION SECTION -->
        <div class="section-title">
            <i class="ph-fill ph-lightning"></i> Alat Kerja Operasional
        </div>
        
        <div class="quick-menu">
            <!-- Akses Admin Kedai -->
            <?php if($role == 'admin'): ?>
                <a href="input_ukur.php" class="quick-card">
                    <div class="quick-icon"><i class="ph-fill ph-measuring-tape"></i></div>
                    <div class="quick-title">Pengukuran</div>
                    <p class="quick-desc">Input data ukuran siswa</p>
                </a>

                <a href="validasi_ambil.php" class="quick-card">
                    <div class="quick-icon"><i class="ph-fill ph-qr-code"></i></div>
                    <div class="quick-title">Penyerahan</div>
                    <p class="quick-desc">Validasi serah terima fisik</p>
                </a>

                <a href="tukar_barang.php" class="quick-card">
                    <div class="quick-icon"><i class="ph-fill ph-arrows-left-right"></i></div>
                    <div class="quick-title">Tukar Ukuran</div>
                    <p class="quick-desc">Proses ganti size/retur</p>
                </a>
            <?php endif; ?>

            <!-- Akses Semua Role -->
            <a href="po_vendor.php" class="quick-card">
                <div class="quick-icon"><i class="ph-fill ph-truck"></i></div>
                <div class="quick-title">PO Vendor</div>
                <p class="quick-desc">Pantau kedatangan stok</p>
            </a>

            <a href="laporan_matriks.php" class="quick-card">
                <div class="quick-icon"><i class="ph-fill ph-grid-nine"></i></div>
                <div class="quick-title">Matriks Stok</div>
                <p class="quick-desc">Detail stok model Excel</p>
            </a>

            <a href="riwayat_transaksi.php" class="quick-card">
                <div class="quick-icon"><i class="ph-fill ph-files"></i></div>
                <div class="quick-title">Log Aktivitas</div>
                <p class="quick-desc">Audit riwayat distribusi</p>
            </a>
        </div>

    </div>

</body>
</html>