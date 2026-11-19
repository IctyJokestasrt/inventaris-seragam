<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Admin Kedai
if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// --- LOGIKA BORDIR NAMA (DITARUH DI KIRI) ---
$id_bordir_produk = 0;
$id_bordir_variant = 0;

$q_bordir = mysqli_query($conn, "SELECT id_produk FROM products WHERE nama_produk = 'Bordir Nama' AND jenjang = 'UMUM' LIMIT 1");
if ($row = mysqli_fetch_assoc($q_bordir)) {
    $id_bordir_produk = $row['id_produk'];
    $q_var = mysqli_query($conn, "SELECT id_variant FROM variants WHERE id_produk = '$id_bordir_produk' LIMIT 1");
    if ($r_var = mysqli_fetch_assoc($q_var)) {
        $id_bordir_variant = $r_var['id_variant'];
    }
}

// Validasi jika produk dasar belum ada
if ($id_bordir_produk == 0) {
    $error_msg = "<b>DATA MASTER BELUM LENGKAP:</b> Produk 'Bordir Nama' tidak ditemukan di database unit UMUM.";
}

$tahun_ajaran = date('Y') . "/" . (date('Y') + 1);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Layanan Pengukuran - Nusa Putera</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .main-content { padding: 30px 50px; }
        .table-order tr.selected { background: #f0fdf4; border-left: 4px solid #22c55e; }
        .qty-input { width: 65px !important; text-align: center; font-weight: bold; }
        .label-sm { font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 5px; text-transform: uppercase; }
        .input-group { margin-bottom: 15px; }
        .sticky-submit { position: sticky; bottom: 0; background: #fff; padding: 20px; border-top: 2px solid #e2e8f0; box-shadow: 0 -5px 10px rgba(0,0,0,0.05); display: flex; justify-content: center; z-index: 100; }
        .box-special { background: #fff7ed; border: 1px solid #fed7aa; padding: 15px; border-radius: 12px; margin-bottom: 15px; }
        option.stok-ready { color: #16a34a; font-weight: bold; }
        option.stok-po { color: #dc2626; }
        .input-highlight { border: 2px solid #3b82f6 !important; background: #eff6ff; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="header-area">
            <div>
                <h2>Layanan Pengukuran</h2>
                <p>Unit Aktif: <b><?php echo $jenjang_aktif; ?></b> | Nota otomatis dikirim via WhatsApp.</p>
            </div>
            
            <div class="jenjang-selector">
                <?php 
                foreach(['ALL', 'TK', 'SD', 'SMP', 'SMA', 'SMK'] as $j) {
                    $act = ($jenjang_aktif == $j) ? 'active' : '';
                    echo "<a href='../config/helper.php?set_jenjang=$j&redirect=admin/input_ukur.php' class='jenjang-btn $act'>$j</a>";
                }
                ?>
            </div>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="card" style="border-left: 5px solid #ef4444; color: #b91c1c;"><?php echo $error_msg; ?></div>
        <?php elseif ($jenjang_aktif == 'ALL'): ?>
            <div style="text-align: center; padding: 80px; color: #94a3b8; border: 2px dashed #cbd5e1; border-radius: 20px;">
                <i class="ph ph-hand-pointing" style="font-size: 64px; opacity: 0.3;"></i>
                <h3>Pilih Unit Jenjang Terlebih Dahulu</h3>
            </div>
        <?php else: ?>
            
            <form action="proses_ukur.php" method="POST" id="formUkur">
                <input type="hidden" name="jenjang" value="<?php echo $jenjang_aktif; ?>">

                <div style="display:grid; grid-template-columns: 350px 1fr; gap:30px; align-items: start; margin-bottom: 100px;">
                    
                    <!-- === KOLOM KIRI: IDENTITAS & BORDIR NAMA === -->
                    <div class="card">
                        <h4 class="menu-label" style="color:var(--primary); margin-top:0; margin-bottom:20px;">Identitas Siswa</h4>
                        
                        <div class="input-group">
                            <label class="label-sm">Gelombang</label>
                            <select name="id_wave" class="form-control" required>
                                <?php 
                                $q_wave = mysqli_query($conn, "SELECT * FROM waves WHERE is_active = 1 ORDER BY id_wave DESC");
                                while($w = mysqli_fetch_assoc($q_wave)) { echo "<option value='{$w['id_wave']}'>{$w['nama_gelombang']}</option>"; }
                                ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label class="label-sm">Nama Lengkap Siswa</label>
                            <input type="text" name="nama_siswa" id="nama_siswa" class="form-control" placeholder="Nama Sesuai Akta..." required style="text-transform:uppercase;">
                        </div>

                        <!-- KOLOM NO HP (NEW) -->
                        <div class="input-group">
                            <label class="label-sm">Nomor WhatsApp (Aktif)</label>
                            <input type="text" name="no_hp" id="no_hp" class="form-control" placeholder="Cth: 8123456789" required>
                            <small id="hp_info" style="font-size: 10px; color: #94a3b8; display: block; margin-top: 5px;">*Wajib diisi untuk pengiriman Nota WA.</small>
                        </div>
                        
                        <div class="input-group">
                            <label class="label-sm">Kelas / No. Daftar</label>
                            <input type="text" name="kelas" class="form-control" placeholder="Cth: 1-A / PPDB-01" required>
                        </div>

                        <hr style="margin: 20px 0; border:0; border-top:1px dashed #ccc;">
                        
                        <div class="box-special">
                            <label class="label-sm" style="color:#c2410c; margin-bottom:10px;">BORDIR NAMA (CUSTOM)</label>
                            <div class="input-group">
                                <label class="label-sm">Teks Nama di Bordir</label>
                                <input type="text" id="input_teks_bordir" class="form-control" placeholder="Cth: KIANO K. T" style="text-transform:uppercase; border-color: #f59e0b;">
                            </div>
                            <div class="input-group">
                                <label class="label-sm">Jumlah Pcs</label>
                                <input type="number" name="items[<?php echo $id_bordir_produk; ?>][qty]" id="qty_bordir" value="0" min="0" class="form-control" style="border-color:#fb923c; font-weight:bold;">
                                <input type="hidden" name="items[<?php echo $id_bordir_produk; ?>][beli]" id="check_bordir" value="0">
                                <input type="hidden" name="items[<?php echo $id_bordir_produk; ?>][id_variant]" value="<?php echo $id_bordir_variant; ?>">
                                <input type="hidden" name="items[<?php echo $id_bordir_produk; ?>][catatan]" id="catatan_bordir" value="">
                            </div>
                        </div>
                    </div>

                    <!-- === KOLOM KANAN: DAFTAR SEMUA ITEM === -->
                    <div class="card" style="padding:0; overflow:hidden;">
                        <table class="table-order" style="width:100%; border-spacing:0;">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th style="padding:15px; width:40px;">#</th>
                                    <th style="padding:15px; text-align:left;">Item & Kategori</th>
                                    <th style="padding:15px; width:220px;">Ukuran (Stok)</th>
                                    <th style="padding:15px; width:70px;">Qty</th>
                                    <th style="padding:15px; text-align:left;">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT DISTINCT p.* FROM products p 
                                        JOIN variants v ON p.id_produk = v.id_produk 
                                        WHERE (p.jenjang = '$jenjang_aktif' OR p.jenjang = 'UMUM') 
                                        AND p.is_active = 1 
                                        AND p.id_produk != '$id_bordir_produk'
                                        ORDER BY kategori ASC, p.nama_produk ASC";
                                $q_p = mysqli_query($conn, $sql);
                                while($prod = mysqli_fetch_assoc($q_p)): $idp = $prod['id_produk']; ?>
                                <tr class="row-item" id="row-<?php echo $idp; ?>">
                                    <td align="center"><input type="checkbox" name="items[<?php echo $idp; ?>][beli]" value="1" class="check-item" data-id="<?php echo $idp; ?>"></td>
                                    <td style="padding:15px;"><strong><?php echo $prod['nama_produk']; ?></strong><br><small style="color:#94a3b8; font-size:10px;"><?php echo $prod['kategori']; ?> | <?php echo $prod['jenjang']; ?></small></td>
                                    <td style="padding:15px;">
                                        <select name="items[<?php echo $idp; ?>][id_variant]" class="form-control sel-variant" disabled required>
                                            <option value="">- Size -</option>
                                            <?php $q_v = mysqli_query($conn, "SELECT v.* FROM variants v LEFT JOIN master_sizes ms ON v.ukuran = ms.nama_size WHERE v.id_produk = '$idp' ORDER BY ms.urutan ASC");
                                            $count_variant = mysqli_num_rows($q_v);
                                            while($v = mysqli_fetch_assoc($q_v)):
                                                $selected = ($count_variant == 1) ? 'selected' : '';
                                                $info = ($v['stok'] > 0) ? "Ready: ".$v['stok'] : "PO";
                                                $cls  = ($v['stok'] > 0) ? "stok-ready" : "stok-po"; ?>
                                            <option value="<?php echo $v['id_variant']; ?>" class="<?php echo $cls; ?>" <?php echo $selected; ?>><?php echo $v['ukuran']; ?> (<?php echo $info; ?>)</option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td style="padding:15px;"><input type="number" name="items[<?php echo $idp; ?>][qty]" value="1" min="1" class="form-control qty-input" disabled></td>
                                    <td style="padding:15px;"><input type="text" name="items[<?php echo $idp; ?>][catatan]" placeholder="..." class="form-control" disabled></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sticky-submit">
                    <button type="submit" class="btn btn-primary" style="padding: 15px 60px; font-weight: bold; border-radius: 50px; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);">
                        <i class="ph-bold ph-paper-plane-tilt"></i> SIMPAN & TERBITKAN NOTA WA
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    $(document).ready(function() {
        // --- FEATURE: AUTO-FILL NO HP BERDASARKAN NAMA ---
        $('#nama_siswa').on('blur', function() {
            let nama = $(this).val();
            if(nama.length > 3) {
                $.getJSON('get_phone.php', {nama: nama}, function(data) {
                    if(data.status == 'success') {
                        $('#no_hp').val(data.phone).addClass('input-highlight');
                        $('#hp_info').html("<i class='ph ph-check-circle'></i> Nomor ditemukan otomatis dari database.");
                        setTimeout(() => { $('#no_hp').removeClass('input-highlight'); }, 2000);
                    }
                });
            }
        });

        $('.check-item').on('change', function() {
            let id = $(this).data('id'); let row = $('#row-' + id);
            if($(this).is(':checked')) { row.addClass('selected'); row.find('select, input').prop('disabled', false); } 
            else { row.removeClass('selected'); row.find('select, input').prop('disabled', true); }
        });

        function updateBordir() {
            let qty = parseInt($('#qty_bordir').val()) || 0;
            let teks = $('#input_teks_bordir').val();
            if(qty > 0) {
                $('#check_bordir').val(1);
                $('#catatan_bordir').val("Teks Bordir: " + teks);
                $('#input_teks_bordir').prop('required', true);
            } else {
                $('#check_bordir').val(0);
                $('#input_teks_bordir').prop('required', false);
            }
        }
        $('#qty_bordir, #input_teks_bordir').on('input change', updateBordir);
    });
    </script>
</body>
</html>