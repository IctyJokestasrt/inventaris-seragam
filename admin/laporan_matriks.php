=<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Akses
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: index.php"); exit;
}

// --- 1. LOGIKA FILTER ---
$where_clause = "WHERE 1=1"; 
$f_jenjang = $_GET['jenjang'] ?? 'ALL';
$f_produk = $_GET['produk'] ?? '';
$f_gelombang = $_GET['gelombang'] ?? '';

if ($f_jenjang != 'ALL') { 
    $where_clause .= " AND p.jenjang = '$f_jenjang'"; 
}
if (!empty($f_produk)) { 
    $where_clause .= " AND p.id_produk = '$f_produk'"; 
}

// --- 2. AMBIL HEADER UKURAN (Urutan: Numerik Kecil -> Alpha -> Numerik Besar) ---
$kolom_ukuran = [];
$q_size = mysqli_query($conn, "SELECT nama_size FROM master_sizes ORDER BY tipe_size DESC, CAST(nama_size AS UNSIGNED) ASC, urutan ASC");
while ($s = mysqli_fetch_assoc($q_size)) {
    $kolom_ukuran[] = $s['nama_size'];
}

// --- 3. QUERY DATA (STOK FISIK + BOOKING AKTIF) ---
$sql = "
    SELECT 
        p.id_produk, p.nama_produk, p.jenjang,
        v.id_variant, v.ukuran, v.stok AS stok_fisik,
        IFNULL(booked.total_booked, 0) AS stok_booking
    FROM products p
    LEFT JOIN variants v ON p.id_produk = v.id_produk
    LEFT JOIN (
        SELECT od.id_variant, SUM(od.qty) AS total_booked
        FROM order_details od
        JOIN orders o ON od.id_order = o.id_order
        WHERE od.is_received = 0 " . 
        (!empty($f_gelombang) ? " AND o.id_wave = '$f_gelombang'" : "") . "
        GROUP BY od.id_variant
    ) AS booked ON v.id_variant = booked.id_variant
    $where_clause
    ORDER BY p.jenjang DESC, p.nama_produk ASC
";
$result = mysqli_query($conn, $sql);

$matrix = [];
$produk_info = [];
while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['id_produk'];
    $uk = $row['ukuran'];
    if (!isset($produk_info[$id])) {
        $produk_info[$id] = ['nama' => $row['nama_produk'], 'jenjang' => $row['jenjang']];
    }
    $matrix[$id][$uk] = [
        'fisik' => $row['stok_fisik'],
        'booking' => $row['stok_booking'],
        'net' => $row['stok_fisik'] - $row['stok_booking']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Matriks Inventaris Real-Time</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { overflow-x: hidden; }
        
        /* SLIDER KONTROL */
        .scroll-control {
            background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; 
            border-radius: 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 15px;
        }
        .scroll-control label { font-size: 11px; font-weight: 800; color: #64748b; white-space: nowrap; }
        input[type=range] { width: 100%; cursor: pointer; accent-color: var(--primary); }

        /* CONTAINER TABEL */
        .table-container {
            width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;
            background: white; max-height: 75vh;
        }

        .matrix-table { width: max-content; min-width: 100%; border-collapse: separate; border-spacing: 0; }
        .matrix-table th, .matrix-table td { 
            padding: 10px; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; 
            text-align: center; min-width: 95px; 
        }

        /* STICKY HEADER & KOLOM */
        .matrix-table thead th { background: #f8fafc; position: sticky; top: 0; z-index: 20; color: #475569; font-size: 12px; }
        .sticky-col { 
            position: sticky; left: 0; background: #fff !important; z-index: 10; 
            text-align: left !important; border-right: 3px solid #e2e8f0 !important; min-width: 250px; 
        }
        .matrix-table thead th.sticky-col { z-index: 30; }

        /* WARNA TRANSPARANSI STOK */
        .cell-safe { background: #f0fdf4; } /* Ada fisik & ada sisa jatah */
        .cell-warning { background: #fffbeb; } /* Ada fisik, tapi sisa jatah 0 (Full Booked) */
        .cell-danger { background: #fef2f2; } /* Fisik benar-benar 0 */
        
        .stok-fisik { font-size: 18px; font-weight: 800; display:block; }
        .stok-book { font-size: 10px; color: #64748b; display:block; }
        .stok-avail { font-size: 10px; font-weight: 700; padding: 2px 4px; border-radius: 4px; margin-top: 4px; display:inline-block; }
        
        .cell-safe .stok-avail { background: #dcfce7; color: #166534; }
        .cell-warning .stok-avail { background: #fef3c7; color: #92400e; }
        .cell-danger .stok-avail { background: #fee2e2; color: #991b1b; }
        .stok-kosong { color: #e2e8f0; opacity: 0.3; }

        /* TOMBOL FILTER KIRI BAWAH (FIXED) */
        .btn-filter-fixed {
            position: fixed; bottom: 30px; left: 30px;
            z-index: 1000; background: var(--primary); color: white;
            padding: 15px 25px; border-radius: 50px; border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); cursor: pointer;
            display: flex; align-items: center; gap: 10px; font-weight: bold;
            transition: 0.3s;
        }
        .btn-filter-fixed:hover { transform: translateY(-5px); background: #4338ca; }

        /* PANEL FILTER SISI KIRI */
        .filter-panel { position: fixed; top: 0; left: -400px; width: 350px; height: 100vh; background: white; z-index: 2000; padding: 30px; box-sizing: border-box; transition: 0.4s; border-right: 1px solid #e2e8f0; box-shadow: 10px 0 30px rgba(0,0,0,0.1); }
        .filter-panel.open { left: 0; }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); z-index: 1500; display: none; backdrop-filter: blur(2px); }
        .overlay.show { display: block; }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="header-area">
            <div>
                <h2>📊 Matriks Inventaris Real-Time</h2>
                <p>Unit Aktif: <b><?php echo $f_jenjang; ?></b>. Angka utama menunjukkan <b>Stok Fisik</b> nyata di rak gudang.</p>
            </div>
        </div>

        <!-- SLIDER NAVIGASI UKURAN -->
        <div class="scroll-control">
            <label><i class="ph-bold ph-arrows-left-right"></i> GESER UKURAN:</label>
            <input type="range" min="0" max="100" value="0" id="tableSlider">
        </div>

        <div class="table-container" id="matrixContainer">
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th class="sticky-col">NAMA PRODUK</th>
                        <th>UNIT</th>
                        <?php foreach ($kolom_ukuran as $size): ?>
                            <th><?php echo $size; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produk_info as $id => $info): ?>
                    <tr>
                        <td class="sticky-col">
                            <strong><?php echo $info['nama']; ?></strong>
                        </td>
                        <td><span class="badge"><?php echo $info['jenjang']; ?></span></td>
                        
                        <?php foreach ($kolom_ukuran as $size): 
                            $data = $matrix[$id][$size] ?? null;
                            if ($data): 
                                // Penentuan Class Warna
                                if ($data['fisik'] <= 0) $class = "cell-danger";
                                elseif ($data['net'] <= 0) $class = "cell-warning";
                                else $class = "cell-safe";
                        ?>
                            <td class="<?php echo $class; ?>">
                                <span class="stok-fisik"><?php echo $data['fisik']; ?></span>
                                <span class="stok-book">Book: <?php echo $data['booking']; ?></span>
                                <span class="stok-avail"><?php echo ($data['net'] > 0 ? "Sisa: ".$data['net'] : "FULL BOOKED"); ?></span>
                            </td>
                        <?php else: ?>
                            <td class="stok-kosong">-</td>
                        <?php endif; endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TOMBOL FILTER TETAP (KIRI BAWAH) -->
    <button class="btn-filter-fixed" onclick="togglePanel()">
        <i class="ph-bold ph-sliders"></i> Filter & Export
    </button>

    <!-- OVERLAY & PANEL -->
    <div class="overlay" id="overlay" onclick="togglePanel()"></div>
    <div class="filter-panel" id="filterPanel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <h3 style="margin:0;">Filter & Export</h3>
            <button onclick="togglePanel()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        
        <form method="GET">
            <label class="label-sm">UNIT JENJANG</label>
            <select name="jenjang" id="sel_jenjang" class="form-control" style="margin-bottom:15px;">
                <option value="ALL">Semua Unit</option>
                <?php foreach(['TK','SD','SMP','SMA','SMK'] as $u) { $s = ($f_jenjang==$u)?'selected':''; echo "<option value='$u' $s>$u</option>"; } ?>
            </select>

            <label class="label-sm" id="label_gel">GELOMBANG (Filter Booking)</label>
            <select name="gelombang" id="sel_gelombang" class="form-control" style="margin-bottom:20px;">
                <option value="">Semua Gelombang</option>
                <?php 
                $q_w = mysqli_query($conn, "SELECT * FROM waves ORDER BY id_wave DESC"); 
                while($w=mysqli_fetch_assoc($q_w)){ 
                    $s=($f_gelombang==$w['id_wave'])?'selected':''; 
                    echo "<option value='{$w['id_wave']}' $s>{$w['nama_gelombang']}</option>"; 
                } 
                ?>
            </select>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-bottom:10px; padding:12px;">TERAPKAN FILTER</button>
            <a href="laporan_matriks.php" class="btn btn-outline" style="width:100%; text-align:center; display:block; box-sizing:border-box; padding:12px;">RESET VIEW</a>
        </form>
        
        <hr style="margin:30px 0; border:0; border-top:1px dashed #ccc;">
        
        <h4 style="margin-bottom:15px; font-size:14px; color:#1e293b;">Ekspor Laporan (Excel)</h4>
        
        <!-- Tombol Export Matriks Biasa -->
        <button onclick="window.open('export_stok.php?jenjang=<?php echo $f_jenjang; ?>','_blank')" class="btn" style="width:100%; background:#10b981; color:white; margin-bottom:10px; padding:12px; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">
            <i class="ph-bold ph-file-xls"></i> Excel Matriks Stok
        </button>

        <!-- Tombol Export Rekap Putih (Format yang Anda Minta) -->
        <button onclick="exportRekapSiswa()" class="btn" style="width:100%; background:#6366f1; color:white; padding:12px; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">
            <i class="ph-bold ph-microsoft-excel-logo"></i> Excel Rekap Siswa
        </button>
        <p style="font-size:10px; color:#94a3b8; margin-top:5px;">*Export Rekap Siswa membutuhkan filter Gelombang & Jenjang.</p>
    </div>

    <script>
        // SCRIPT UNTUK SLIDER TABEL
        const slider = document.getElementById('tableSlider');
        const container = document.getElementById('matrixContainer');

        if(slider && container) {
            slider.addEventListener('input', function() {
                const maxScroll = container.scrollWidth - container.clientWidth;
                container.scrollLeft = (this.value / 100) * maxScroll;
            });

            container.addEventListener('scroll', function() {
                const maxScroll = container.scrollWidth - container.clientWidth;
                if(maxScroll > 0) {
                    slider.value = (container.scrollLeft / maxScroll) * 100;
                }
            });
        }

        // SCRIPT UNTUK PANEL FILTER
        function togglePanel() {
            document.getElementById('filterPanel').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('show');
        }

        // REDIRECT KE EXPORT REKAP ALL (Format Baru Klien)
        function exportRekapSiswa() {
            var gel = document.getElementById('sel_gelombang').value;
            var jen = document.getElementById('sel_jenjang').value;
            
            if(!gel) { alert("Pilih Gelombang terlebih dahulu untuk Export Rekap!"); return; }
            if(!jen || jen === 'ALL') { alert("Pilih Unit Jenjang (TK/SD...) untuk Export Rekap!"); return; }
            
            window.location.href = "export_rekap_all.php?jenjang=" + jen + "&gelombang=" + gel;
        }
    </script>
</body>
</html>