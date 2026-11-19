<?php 
include '../config/koneksi.php'; 
include '../config/helper.php';

// Proteksi Admin Kedai
if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// 1. PROSES TAMBAH UKURAN
if (isset($_POST['simpan_ukuran'])) {
    $tipe = $_POST['tipe_size'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_size']);
    $urut = $_POST['urutan'];

    $q = "INSERT INTO master_sizes (tipe_size, nama_size, urutan) VALUES ('$tipe', '$nama', '$urut')";
    if (mysqli_query($conn, $q)) {
        echo "<script>alert('Ukuran baru berhasil ditambahkan!'); window.location='tambah_ukuran.php';</script>";
    }
}

// 2. PROSES HAPUS
if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    mysqli_query($conn, "DELETE FROM master_sizes WHERE id_size = '$id'");
    header("Location: tambah_ukuran.php");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Ukuran - Koperasi</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>

    <!-- SIDEBAR -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="header-area">
            <div>
                <h2>📏 Master Ukuran Seragam</h2>
                <p>Kelola daftar ukuran standar (S-12XL) dan nomor celana.</p>
            </div>
            <a href="tambah.php" class="btn btn-outline">Kembali ke Produk</a>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1.5fr; gap:30px; align-items: start;">
            
            <!-- FORM TAMBAH UKURAN -->
            <div class="card">
                <h4 class="menu-label" style="color:var(--primary); margin-top:0;">Tambah Ukuran Baru</h4>
                <form method="POST">
                    <label style="font-size:11px; font-weight:800;">TIPE UKURAN</label>
                    <select name="tipe_size" required>
                        <option value="ALPHA">ALPHA (Huruf: S, M, L, XL...)</option>
                        <option value="NUMERIC">NUMERIC (Angka: 27, 28, 29...)</option>
                    </select>

                    <label style="font-size:11px; font-weight:800;">NAMA UKURAN</label>
                    <input type="text" name="nama_size" placeholder="Cth: 10XL atau 35" required>

                    <label style="font-size:11px; font-weight:800;">URUTAN TAMPIL (ANGKA)</label>
                    <input type="number" name="urutan" placeholder="Cth: 16" required>
                    <small style="color:var(--text-muted); display:block; margin-top:-15px; margin-bottom:20px; font-size:11px;">
                        *Gunakan angka lebih besar untuk ukuran yang lebih besar.
                    </small>

                    <button type="submit" name="simpan_ukuran" class="btn btn-primary" style="width:100%; justify-content:center; padding:15px;">
                        <i class="ph-bold ph-plus-circle"></i> DAFTARKAN UKURAN
                    </button>
                </form>
            </div>

            <!-- DAFTAR UKURAN EXISTING -->
            <div class="card" style="padding:0; overflow:hidden;">
                <div style="padding:20px; border-bottom:1px solid #e5e7eb; background:#f8fafc;">
                    <h4 style="margin:0;">📋 Daftar Ukuran Saat Ini</h4>
                </div>
                <div style="max-height: 500px; overflow-y: auto;">
                    <table style="width:100%; border-spacing:0;">
                        <thead>
                            <tr style="background:#f9fafb;">
                                <th style="padding-left:20px;">Nama Ukuran</th>
                                <th>Tipe</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $q_size = mysqli_query($conn, "SELECT * FROM master_sizes ORDER BY tipe_size ASC, urutan ASC");
                            while($rs = mysqli_fetch_assoc($q_size)):
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding:15px 20px;">
                                    <strong style="font-size:16px;"><?php echo $rs['nama_size']; ?></strong>
                                </td>
                                <td>
                                    <span class="badge" style="background:#eef2ff; color:var(--primary);">
                                        <?php echo $rs['tipe_size']; ?>
                                    </span>
                                </td>
                                <td align="center">
                                    <a href="tambah_ukuran.php?hapus_id=<?php echo $rs['id_size']; ?>" 
                                       style="color:#ef4444;" onclick="return confirm('Hapus ukuran ini?')">
                                        <i class="ph-bold ph-trash" style="font-size:18px;"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</body>
</html>