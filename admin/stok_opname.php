<?php
include '../config/koneksi.php';
include '../config/helper.php';

// --- LOGIK SORTING ---
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'p.nama_produk';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Putar balik order untuk link selanjutnya (ASC <-> DESC)
$next_order = ($sort_order == 'ASC') ? 'DESC' : 'ASC';
$sort_icon = ($sort_order == 'ASC') ? ' 🔼' : ' 🔽';

// Validasi kolom agar tidak terjadi SQL Injection
$allowed_sort = ['p.nama_produk', 'p.jenjang', 'v.stok', 'v.target_tahunan', 'defisit'];
if (!in_array($sort_column, $allowed_sort)) {
    $sort_column = 'p.nama_produk';
}

// Logika sorting khusus untuk kolom virtual "defisit"
$orderby = ($sort_column == 'defisit') ? "(v.target_tahunan - v.stok) $sort_order" : "$sort_column $sort_order";


// --- LOGIK FILTER JENJANG ---
$where_clause = "";
if ($jenjang_aktif != 'ALL') {
    $where_clause = " WHERE p.jenjang = '$jenjang_aktif'";
}

// --- QUERY DATA STOK ---
$query = "SELECT p.nama_produk, p.jenjang, p.is_active, v.* 
          FROM variants v 
          JOIN products p ON v.id_produk = p.id_produk 
          $where_clause 
          ORDER BY $orderby";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok - Nusa Putera</title>
    <!-- CSS & Icons -->
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        /* Style tambahan untuk link sorting di header tabel */
        .sort-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .sort-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

    <!-- SIDEBAR FIX -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <!-- HEADER AREA -->
        <div class="header-area">
            <div>
                <h2>📊 Laporan Stok & Defisit</h2>
                <p>Monitoring ketersediaan unit dan perencanaan produksi: <b><?php echo $jenjang_aktif; ?></b></p>
            </div>
            
            <!-- Jenjang Selector (Pill Style) -->
            <div class="jenjang-selector">
                <?php 
                $list = ['ALL', 'TK', 'SD', 'SMP', 'SMA', 'SMK'];
                foreach($list as $j) {
                    $act = ($jenjang_aktif == $j) ? 'active' : '';
                    echo "<a href='../config/helper.php?set_jenjang=$j' class='jenjang-btn $act'>$j</a>";
                }
                ?>
            </div>
        </div>

        <!-- INFO BOX (PROFESSIONAL TERMS) -->
        <div class="card" style="padding:15px 25px; border-left:4px solid #3b82f6; background:#eff6ff; margin-bottom:30px; display:flex; align-items:center; gap:15px;">
            <i class="ph-fill ph-info" style="font-size:24px; color:#3b82f6;"></i>
            <div>
                <h4 style="margin:0; font-size:14px; color:#1e40af;">Analisa Kebutuhan</h4>
                <p style="margin:0; font-size:12px; color:#1e3a8a;">
                    Status <strong style="color:#ef4444;">DEFISIT</strong> muncul jika Stok Real di gudang lebih kecil dari Target Kebutuhan Tahunan.
                </p>
            </div>
        </div>

        <!-- TABEL LAPORAN STOK -->
        <div class="card" style="padding:0; overflow:hidden;">
            <div style="padding:25px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                <h4 style="margin:0;"><i class="ph ph-list-numbers"></i> Rincian Inventaris</h4>
                <span class="badge bg-blue" style="font-size:10px;">TOTAL DATA: <?php echo mysqli_num_rows($result); ?></span>
            </div>
            
            <div style="overflow-x:auto;">
                <table style="width:100%; border-spacing:0;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="padding-left:25px;">
                                <a href="?sort=p.nama_produk&order=<?php echo $next_order; ?>" class="sort-link">
                                    Nama Produk <?php if($sort_column == 'p.nama_produk') echo $sort_icon; ?>
                                </a>
                            </th>
                            <th>Jenjang</th>
                            <th>Ukuran</th>
                            <th style="text-align:center;">
                                <a href="?sort=v.stok&order=<?php echo $next_order; ?>" class="sort-link" style="justify-content:center;">
                                    Stok Real <?php if($sort_column == 'v.stok') echo $sort_icon; ?>
                                </a>
                            </th>
                            <th style="text-align:center;">Target Tahunan</th>
                            <th style="text-align:center; padding-right:25px;">
                                <a href="?sort=defisit&order=<?php echo $next_order; ?>" class="sort-link" style="justify-content:center;">
                                    Status <?php if($sort_column == 'defisit') echo $sort_icon; ?>
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result) > 0):
                            while($row = mysqli_fetch_assoc($result)): 
                                $stok = $row['stok'];
                                $target = $row['target_tahunan'];
                                $defisit_val = $target - $stok;
                                
                                $is_defisit = ($defisit_val > 0);
                                $row_bg = $is_defisit ? "style='background-color:#fff1f2;'" : "";
                        ?>
                        <tr <?php echo $row_bg; ?>>
                            <td style="padding-left:25px;">
                                <strong style="color:var(--text-main);"><?php echo $row['nama_produk']; ?></strong><br>
                                <small style="color:var(--text-muted); font-size:11px;"><?php echo $row['is_active'] ? 'Model Aktif' : 'Model Lama (Old)'; ?></small>
                            </td>
                            <td>
                                <span class="badge" style="background:#f1f5f9; color:#64748b;"><?php echo $row['jenjang']; ?></span>
                            </td>
                            <td>
                                <b style="color:var(--primary); font-size:14px;"><?php echo $row['ukuran']; ?></b>
                            </td>
                            <td align="center">
                                <span style="font-weight:700; font-size:16px; color:var(--text-main);"><?php echo number_format($stok); ?></span>
                            </td>
                            <td align="center" style="color:var(--text-muted);">
                                <?php echo number_format($target); ?>
                            </td>
                            <td align="center" style="padding-right:25px;">
                                <?php if($is_defisit): ?>
                                    <span class="badge bg-red" style="padding:6px 12px; font-weight:800;">
                                        DEFISIT <?php echo number_format($defisit_val); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-green" style="padding:6px 12px; font-weight:800;">
                                        AMAN
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:80px; color:var(--text-muted);">
                                <i class="ph ph-package" style="font-size:48px; opacity:0.2;"></i><br><br>
                                Tidak ada data stok untuk unit ini.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- FOOTER BUTTON -->
        <div style="margin-top:20px; text-align:right;">
             <a href="export_stok.php" class="btn btn-outline"> <!-- Tidak perlu parameter jenjang di URL karena sudah pakai session -->
                <i class="ph ph-file-xls"></i> Export ke Excel
            </a>
        </div>

    </div>

</body>
</html>