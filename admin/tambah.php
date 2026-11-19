<?php 
include '../config/koneksi.php'; 
include '../config/helper.php';

// Proteksi: Hanya Admin (Kedai) yang bisa akses
if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// LOGIKA HAPUS VARIAN (DIPERBAIKI DENGAN TRANSACTION & CASCADE SIMULATED)
if (isset($_GET['hapus_id'])) {
    $id_v = mysqli_real_escape_string($conn, $_GET['hapus_id']);
    
    // Mulai Transaksi
    mysqli_begin_transaction($conn);

    try {
        // 1. Hapus data anak di tabel stock_logs
        mysqli_query($conn, "DELETE FROM stock_logs WHERE id_variant = '$id_v'");

        // 2. Hapus data anak di tabel order_details
        // Catatan: Jika order_details terhapus, pastikan order induk tidak jadi 'gantung'
        // Untuk aman, kita hapus dulu detailnya
        mysqli_query($conn, "DELETE FROM order_details WHERE id_variant = '$id_v'");
        
        // 3. Hapus data Varian itu sendiri
        mysqli_query($conn, "DELETE FROM variants WHERE id_variant = '$id_v'");
        
        // Commit jika semua sukses
        mysqli_commit($conn);
        echo "<script>alert('Varian ukuran berhasil dihapus! (Data riwayat yang terkait juga dihapus)'); window.location='tambah.php';</script>";

    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($conn);
        echo "<script>alert('Gagal menghapus varian: Terjadi kesalahan database saat pembersihan data.'); window.location='tambah.php';</script>";
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Produk - Koperasi Nusa Putera</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- jQuery untuk filter ukuran otomatis -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Style Tambahan untuk konsistensi */
        .badge-aktif { background:#dcfce7; color:#166534; }
        .badge-old { background:#fee2e2; color:#991b1b; }
    </style>
</head>
<body>

    <!-- SIDEBAR (Receipt Style) -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="header-area">
            <div>
                <h2>📦 Master Produk & Stok</h2>
                <p>Otoritas Kedai: Kelola model, stok, dan spesifikasi ukuran standar.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="tambah_ukuran.php" class="btn btn-outline">
                    <i class="ph ph-gear"></i> Atur Daftar Ukuran
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="ph ph-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- FORM INPUT PRODUK -->
        <div class="card">
            <form action="proses_tambah.php" method="POST">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                    
                    <!-- Sisi Kiri: Identitas Produk -->
                    <div>
                        <h4 class="menu-label" style="color:var(--primary); margin-top:0;">
                            <i class="ph-fill ph-info"></i> Identitas Model
                        </h4>
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="font-size:11px; font-weight:800; color:var(--text-muted);">UNIT JENJANG</label>
                                <select name="jenjang" required>
                                    <option value="TK">TK</option>
                                    <option value="SD">SD</option>
                                    <option value="SMP">SMP</option>
                                    <option value="SMA">SMA</option>
                                    <option value="SMK">SMK</option>
                                    <option value="UMUM">UMUM</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:800; color:var(--text-muted);">STATUS MODEL</label>
                                <select name="is_active" required>
                                    <option value="1">✅ Model Aktif (Bisa PO)</option>
                                    <option value="0">❌ Tidak Aktif (Hanya Stok Sisa)</option>
                                </select>
                            </div>
                        </div>

                        <label style="font-size:11px; font-weight:800; color:var(--text-muted);">KATEGORI SERAGAM</label>
                        <select name="kategori" required>
                            <option value="SERAGAM">Seragam Utama (Kemeja/Celana/Rok)</option>
                            <option value="OLAHRAGA">Seragam Olahraga (Setel/Pisah)</option>
                            <option value="ATRIBUT">Atribut (Topi/Dasi/Sabuk/Badge)</option>
                        </select>

                        <label style="font-size:11px; font-weight:800; color:var(--text-muted);">NAMA PRODUK</label>
                        <input type="text" name="nama_produk" placeholder="Cth: Batik SMP Lengan Pendek" required>
                        
                        <label style="font-size:11px; font-weight:800; color:var(--text-muted);">TIPE UKURAN (MEMPENGARUHI PILIHAN SIZE)</label>
                        <select name="tipe_ukuran" id="tipe_ukuran" required>
                            <option value="ALPHA">ALPHA (S, M, L, XL - 12XL)</option>
                            <option value="NUMERIC">NUMERIC (Celana: 27, 28, 29...)</option>
                            <option value="ALLSIZE">ALL SIZE (Aksesoris)</option>
                        </select>
                    </div>

                    <!-- Sisi Kanan: Detail Ukuran & Stok -->
                    <div>
                        <h4 class="menu-label" style="color:var(--primary); margin-top:0;">
                            <i class="ph-fill ph-package"></i> Detail Inventaris
                        </h4>
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="font-size:11px; font-weight:800; color:var(--text-muted);">PILIH UKURAN</label>
                                <select name="ukuran" id="select_size" required>
                                    <option value="">-- Pilih Tipe Dulu --</option>
                                    <?php 
                                    // Ambil semua ukuran untuk disaring nanti via JS
                                    $q_s = mysqli_query($conn, "SELECT * FROM master_sizes ORDER BY urutan ASC");
                                    while($s = mysqli_fetch_assoc($q_s)){
                                        echo "<option value='{$s['nama_size']}' class='opt-size size-{$s['tipe_size']}'>{$s['nama_size']}</option>";
                                    }
                                    ?>
                                    <option value="ALLSIZE" class="opt-size size-ALLSIZE">ALLSIZE</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:800; color:var(--text-muted);">STOK AWAL GUDANG</label>
                                <input type="number" name="stok" value="0" required>
                            </div>
                        </div>

                        <label style="font-size:11px; font-weight:800; color:var(--text-muted);">TARGET TAHUNAN (DEFISIT)</label>
                        <input type="number" name="target_tahunan" placeholder="Target kebutuhan tahun ini..." required>

                        <label style="font-size:11px; font-weight:800; color:var(--text-muted);">CATATAN SPESIFIKASI PO (LD / UKURAN KHUSUS)</label>
                        <textarea name="catatan_po" rows="2" placeholder="Cth: Khusus PO LD wajib 145cm..."></textarea>

                        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:15px; margin-top:10px;">
                            <i class="ph-bold ph-floppy-disk"></i> SIMPAN DATA KE KEDAI
                        </button>
                    </div>

                </div>
            </form>
        </div>

        <!-- TABEL DAFTAR PRODUK (Pencegah Ganda) -->
        <div class="header-area" style="margin-top:50px; margin-bottom:10px;">
            <div>
                <h3>📦 Daftar Produk & Ukuran Terdaftar</h3>
                <p style="font-size:13px; color:var(--text-muted);">Daftar seluruh seragam yang ada di tangan kedai.</p>
            </div>
        </div>

        <div class="card" style="padding:0; overflow:hidden;">
            <table style="width:100%; border-spacing:0;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding-left:25px;">Nama Produk</th>
                        <th>Jenjang</th>
                        <th>Status</th>
                        <th style="text-align:center;">Ukuran</th>
                        <th style="text-align:center;">Stok</th>
                        <th style="text-align:center; padding-right:25px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q = mysqli_query($conn, "
                        SELECT p.nama_produk, p.jenjang, p.is_active, v.ukuran, v.stok, v.id_variant 
                        FROM variants v 
                        JOIN products p ON v.id_produk = p.id_produk 
                        ORDER BY p.jenjang DESC, p.nama_produk ASC, v.ukuran ASC
                    ");

                    if (mysqli_num_rows($q) == 0) {
                        echo "<tr><td colspan='6' align='center' style='padding:40px; color:#94a3b8;'>Belum ada data seragam terdaftar.</td></tr>";
                    }

                    while($row = mysqli_fetch_assoc($q)):
                        $st_label = $row['is_active'] ? 'AKTIF' : 'OLD';
                        $st_color = $row['is_active'] ? 'badge-aktif' : 'badge-old';
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding:15px 25px;">
                            <strong><?php echo $row['nama_produk']; ?></strong>
                        </td>
                        <td><span class="badge" style="background:#eef2ff; color:var(--primary);"><?php echo $row['jenjang']; ?></span></td>
                        <td><span class="badge <?php echo $st_color; ?>" style="font-size:9px;"><?php echo $st_label; ?></span></td>
                        <td align="center"><b><?php echo $row['ukuran']; ?></b></td>
                        <td align="center"><?php echo $row['stok']; ?> <small>pcs</small></td>
                        <td style="text-align:center; padding-right:25px;">
                            <a href="tambah.php?hapus_id=<?php echo $row['id_variant']; ?>" 
                               class="btn" style="background:#fee2e2; color:#ef4444; padding:8px;" 
                               onclick="return confirm('Hapus varian ini? Semua data stok dan pesanan yang terkait akan ikut terhapus.')">
                                <i class="ph-bold ph-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- SCRIPT JAVASCRIPT: FILTER UKURAN OTOMATIS -->
    <script>
    $(document).ready(function(){
        function filterSize() {
            let tipe = $('#tipe_ukuran').val(); // ALPHA, NUMERIC, atau ALLSIZE
            
            // Sembunyikan semua opsi dulu
            $('.opt-size').hide();
            
            // Tampilkan hanya yang sesuai dengan tipe
            if(tipe == 'ALPHA') {
                $('.size-ALPHA').show();
            } else if(tipe == 'NUMERIC') {
                $('.size-NUMERIC').show();
            } else if(tipe == 'ALLSIZE') {
                $('.size-ALLSIZE').show();
            }
            
            // Reset pilihan dropdown ukuran
            $('#select_size').val('');
        }

        // Jalankan saat tipe ukuran diganti
        $('#tipe_ukuran').change(filterSize);
        
        // Jalankan sekali saat pertama load
        filterSize();
    });
    </script>

</body>
</html>