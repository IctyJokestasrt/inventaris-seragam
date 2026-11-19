<?php
include '../config/koneksi.php';
include '../config/helper.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: index.php"); 
    exit;
}

$f_jenjang = $_GET['jenjang'] ?? 'ALL';
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_sampai = $_GET['tgl_sampai'] ?? date('Y-m-d');

$where = " WHERE DATE(o.tgl_ukur) BETWEEN '$tgl_mulai' AND '$tgl_sampai'";
if ($f_jenjang != 'ALL') { $where .= " AND o.jenjang_siswa = '$f_jenjang'"; }

$sql = "SELECT o.*, w.nama_gelombang FROM orders o LEFT JOIN waves w ON o.id_wave = w.id_wave $where ORDER BY o.tgl_ukur DESC";
$query = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Distribusi Barang</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .main-content { padding: 30px 40px; background: #f8fafc; }
        
        /* Card Styling */
        .history-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            display: grid;
            grid-template-columns: 100px 180px 1fr 120px 150px 100px;
            gap: 20px;
            align-items: center;
        }
        .history-card:hover { transform: translateY(-3px); box-shadow: 0 12px 20px -5px rgba(0,0,0,0.05); border-color: var(--primary); }

        /* Data Info Group */
        .info-group { display: flex; flex-direction: column; gap: 4px; }
        .label-tiny { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-main { font-size: 14px; font-weight: 700; color: #1e293b; }
        .data-sub { font-size: 12px; color: #64748b; }

        /* Status Badges */
        .badge-status {
            padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-align: center;
        }
        .bg-po { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .bg-ready { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .bg-diambil { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }

        /* Action Buttons */
        .action-container { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-icon {
            width: 38px; height: 38px; border-radius: 10px; border: 1px solid #e2e8f0;
            background: #fff; color: #64748b; display: flex; align-items: center;
            justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none;
        }
        .btn-icon:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* Modal Custom */
        .modal { 
            display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; 
            background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); align-items: center; justify-content: center; 
        }
        .modal-content { background: #fff; padding: 30px; border-radius: 20px; width: 90%; max-width: 700px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .table-modal { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-modal th { background: #f8fafc; padding: 12px; text-align: left; font-size: 11px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        .table-modal td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="header-area">
            <div>
                <h2 style="margin:0; letter-spacing: -1px;">📜 Riwayat Distribusi Barang</h2>
                <p style="margin:2px 0 0; color:#64748b;">Pantau status pesanan dan log penyerahan fisik seragam.</p>
            </div>
            <a href="export_custom.php?jenjang=<?php echo $f_jenjang; ?>&tgl_mulai=<?php echo $tgl_mulai; ?>&tgl_sampai=<?php echo $tgl_sampai; ?>" class="btn" style="background:#16a34a; color:white; font-weight:bold; padding:12px 25px; border-radius:12px; text-decoration:none; display:flex; align-items:center; gap:8px;">
                <i class="ph-bold ph-file-xls" style="font-size:20px;"></i> Export Laporan Audit
            </a>
        </div>

        <!-- Filter Area -->
        <div class="card" style="margin-bottom:25px; padding: 20px;">
            <form method="GET" style="display:flex; gap:15px; align-items: flex-end;">
                <div style="flex:1">
                    <label class="label-sm">Unit Unit</label>
                    <select name="jenjang" class="form-control">
                        <option value="ALL">SEMUA JENJANG</option>
                        <?php foreach(['TK','SD','SMP','SMA','SMK'] as $j) { $s = ($f_jenjang==$j)?'selected':''; echo "<option value='$j' $s>$j</option>"; } ?>
                    </select>
                </div>
                <div style="flex:1.5">
                    <label class="label-sm">Rentang Tanggal</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="date" name="tgl_mulai" class="form-control" value="<?php echo $tgl_mulai; ?>">
                        <span style="color:#cbd5e1">-</span>
                        <input type="date" name="tgl_sampai" class="form-control" value="<?php echo $tgl_sampai; ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="padding:12px 20px;"><i class="ph ph-funnel"></i></button>
                <a href="riwayat_transaksi.php" class="btn btn-outline" style="padding:12px;"><i class="ph ph-arrows-clockwise"></i></a>
            </form>
        </div>

        <!-- List Data -->
        <?php while($row = mysqli_fetch_assoc($query)): $ido = $row['id_order']; ?>
        <div class="history-card">
            
            <!-- ID & Waktu -->
            <div class="info-group">
                <span class="label-tiny">ID Pesanan</span>
                <span class="data-main">#<?php echo $ido; ?></span>
                <span class="data-sub"><?php echo date('d/m/y', strtotime($row['tgl_ukur'])); ?></span>
            </div>

            <!-- Nama & Kelas -->
            <div class="info-group">
                <span class="label-tiny">Nama Siswa</span>
                <span class="data-main" style="text-transform: uppercase;"><?php echo $row['nama_siswa']; ?></span>
                <span class="data-sub"><?php echo $row['jenjang_siswa']; ?> - <?php echo $row['kelas_calon']; ?></span>
            </div>

            <!-- Rincian Barang -->
            <div class="info-group">
                <span class="label-tiny">Rincian Barang</span>
                <span class="data-sub" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;">
                    <?php 
                    $q_item = mysqli_query($conn, "SELECT p.nama_produk, v.ukuran FROM order_details od JOIN variants v ON od.id_variant=v.id_variant JOIN products p ON v.id_produk=p.id_produk WHERE od.id_order='$ido'");
                    $items = []; while($it = mysqli_fetch_assoc($q_item)){ $items[] = $it['nama_produk']." (".$it['ukuran'].")"; }
                    echo implode(", ", $items);
                    ?>
                </span>
            </div>

            <!-- Status -->
            <div class="info-group">
                <span class="label-tiny">Status</span>
                <?php 
                    $st = $row['status_order'];
                    $cls = ($st=='ready') ? 'bg-ready' : (($st=='diambil') ? 'bg-diambil' : 'bg-po');
                ?>
                <div class="badge-status <?php echo $cls; ?>"><?php echo strtoupper($st); ?></div>
            </div>

            <!-- Penerima -->
            <div class="info-group">
                <span class="label-tiny">Penerima Barang</span>
                <span class="data-main" style="font-size: 13px;">
                    <?php echo $row['nama_pengambil'] ? strtoupper($row['nama_pengambil']) : '<span style="color:#cbd5e1; font-weight:normal; font-style:italic;">Belum Diambil</span>'; ?>
                </span>
                <span class="data-sub" style="font-size: 10px;"><?php echo $row['tgl_ambil'] ? date('d/m H:i', strtotime($row['tgl_ambil'])) : ''; ?></span>
            </div>

            <!-- Aksi -->
            <div class="action-container">
                <button onclick="openDetail(<?php echo $ido; ?>, '<?php echo $row['nama_siswa']; ?>')" class="btn-icon" title="Lihat Rincian Stok">
                    <i class="ph-bold ph-list-magnifying-glass" style="font-size:20px;"></i>
                </button>
                <a href="cetak_nota.php?id=<?php echo $ido; ?>" target="_blank" class="btn-icon" title="Cetak Nota">
                    <i class="ph-bold ph-printer" style="font-size:20px;"></i>
                </a>
            </div>

        </div>
        <?php endwhile; ?>
    </div>

    <!-- Modal AJAX (Tetap Sama) -->
    <div id="modalDetail" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:15px;">
                <h3 id="modalTitle" style="margin:0; font-size:18px; color:var(--primary);">Detail Barang</h3>
                <button onclick="closeModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>
            </div>
            <div id="modalBody" style="margin-top:20px;"></div>
        </div>
    </div>

    <script>
    function openDetail(id, nama) {
        $('#modalTitle').html("<i class='ph ph-user'></i> Rincian: " + nama);
        $('#modalBody').html("<div style='text-align:center; padding:20px;'><i class='ph ph-circle-notch' style='font-size:32px; animation: spin 1s linear infinite;'></i></div>");
        $('#modalDetail').css('display', 'flex');
        $.get('ajax_detail_stok.php', {id: id}, function(data) { $('#modalBody').html(data); });
    }
    function closeModal() { $('#modalDetail').hide(); }
    window.onclick = function(event) { if (event.target == document.getElementById('modalDetail')) { closeModal(); } }
    </script>

</body>
</html>