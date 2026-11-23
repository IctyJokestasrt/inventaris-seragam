]<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Akses
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: index.php"); exit;
}

// 1. Logika Filter Jenjang
$f_jenjang = $_GET['jenjang'] ?? 'ALL';
$where_clause = ($f_jenjang != 'ALL') ? "WHERE p.jenjang = '$f_jenjang'" : "";

// 2. Query Audit Mutasi Stok
// Logika: Ambil stok fisik saat ini, lalu hitung total masuk & keluar dari log
$sql = "
    SELECT 
        p.id_produk,
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
            SUM(CASE WHEN jenis_aksi IN ('KELUAR', 'RUSAK', 'TUKAR') THEN qty ELSE 0 END) as total_keluar
        FROM stock_logs
        GROUP BY id_variant
    ) AS logs ON v.id_variant = logs.id_variant
    $where_clause
    ORDER BY p.kategori ASC, p.nama_produk ASC
";

$result = mysqli_query($conn, $sql);
$tahun_ajaran = date('Y') . "/" . (date('Y') + 1);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Audit Mutasi Stok - Kedai Nusaputera</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .table-audit { width: 100%; border-collapse: collapse; background: white; }
        .table-audit th { background: #f8fafc; padding: 15px; text-align: center; font-size: 11px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        .table-audit td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; text-align: center; }
        .text-left { text-align: left !important; }
        
        .stok-awal { background: #f8fafc; font-weight: 600; }
        .stok-masuk { color: #16a34a; font-weight: 700; }
        .stok-keluar { color: #dc2626; font-weight: 700; }
        .stok-akhir { background: #eff6ff; font-weight: 800; color: #1d4ed8; font-size: 16px; }

        /* TOMBOL EXCEL KIRI BAWAH (Sesuai Permintaan) */
        .btn-export-fixed {
            position: fixed; bottom: 30px; left: 30px;
            z-index: 1000; background: #16a34a; color: white;
            padding: 15px 25px; border-radius: 50px; border: none;
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.3); cursor: pointer;
            display: flex; align-items: center; gap: 10px; font-weight: bold;
            text-decoration: none; transition: 0.3s;
        }
        .btn-export-fixed:hover { transform: translateY(-5px); background: #15803d; color: white; }

        @media print {
            .btn-export-fixed, .header-area .jenjang-selector, .no-print { display: none !important; }
            .main-content { padding: 0; }
            .card { box-shadow: none; border: 1px solid #eee; }
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="header-area">
            <div>
                <h2>📋 Laporan Mutasi & Audit Stok</h2>
                <p>Periode: <b><?php echo $tahun_ajaran; ?></b> | Unit: <b><?php echo $f_jenjang; ?></b></p>
            </div>
            
            <div class="jenjang-selector">
                <?php 
                foreach(['ALL', 'TK', 'SD', 'SMP', 'SMA', 'SMK'] as $j) {
                    $act = ($f_jenjang == $j) ? 'active' : '';
                    echo "<a href='laporan_audit.php?jenjang=$j' class='jenjang-btn $act'>$j</a>";
                }
                ?>
            </div>
        </div>

        <div class="card" style="padding:0; overflow:hidden;">
            <table class="table-audit">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th class="text-left">Nama Seragam & Size</th>
                        <th>Kategori</th>
                        <th>Stok Awal</th>
                        <th>Masuk (+)</th>
                        <th>Keluar (-)</th>
                        <th>Stok Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if(mysqli_num_rows($result) > 0):
                        while($row = mysqli_fetch_assoc($result)): 
                            // Rumus Stok Awal: Stok Sekarang - Masuk + Keluar
                            $stok_awal = $row['stok_akhir'] - $row['masuk'] + $row['keluar'];
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td class="text-left">
                            <strong><?php echo $row['nama_produk']; ?></strong><br>
                            <span class="badge" style="background:#f1f5f9; color:#64748b; font-size:10px;">UKURAN: <?php echo $row['ukuran']; ?></span>
                        </td>
                        <td><small><?php echo $row['kategori']; ?></small></td>
                        <td class="stok-awal"><?php echo $stok_awal; ?></td>
                        <td class="stok-masuk"><?php echo $row['masuk']; ?></td>
                        <td class="stok-keluar"><?php echo $row['keluar']; ?></td>
                        <td class="stok-akhir"><?php echo $row['stok_akhir']; ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" style="padding:50px; color:#94a3b8;">Tidak ada data mutasi untuk unit ini.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="no-print" style="margin-top: 20px; text-align: center; color: #94a3b8; font-size: 12px;">
            <i class="ph ph-info"></i> Stok Awal dihitung berdasarkan riwayat mutasi dari Stok Akhir saat ini.
        </div>
    </div>

    <!-- TOMBOL EXPORT EXCEL MELAYANG DI KIRI BAWAH -->
    <a href="export_audit.php?jenjang=<?php echo $f_jenjang; ?>" class="btn-export-fixed">
        <i class="ph-bold ph-file-xls"></i> Export ke Excel
    </a>

</body>
</html>