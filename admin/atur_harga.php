<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi: Hanya Admin yang bisa mengatur harga
if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Konfigurasi Tahun Ajaran Aktif (Bisa dibuat dinamis jika perlu)
$tahun_ajaran_aktif = "2025/2026";

// --- LOGIKA SIMPAN HARGA ---
if (isset($_POST['simpan_semua_harga'])) {
    $daftar_harga = $_POST['harga']; // Berupa array [id_produk => nominal]

    mysqli_begin_transaction($conn);
    try {
        foreach ($daftar_harga as $id_produk => $nominal) {
            $id_p = mysqli_real_escape_string($conn, $id_produk);
            $price = (int)$nominal;

            // Cek apakah sudah ada harga untuk produk ini di tahun ajaran ini
            $q_cek = mysqli_query($conn, "SELECT id_harga FROM prices WHERE id_produk = '$id_p' AND tahun_ajaran = '$tahun_ajaran_aktif'");
            
            if (mysqli_num_rows($q_cek) > 0) {
                // Jika sudah ada -> UPDATE
                mysqli_query($conn, "UPDATE prices SET harga_jual = '$price' WHERE id_produk = '$id_p' AND tahun_ajaran = '$tahun_ajaran_aktif'");
            } else {
                // Jika belum ada -> INSERT
                mysqli_query($conn, "INSERT INTO prices (id_produk, tahun_ajaran, harga_jual) VALUES ('$id_p', '$tahun_ajaran_aktif', '$price')");
            }
        }
        mysqli_commit($conn);
        // Tambahkan trigger notifikasi harga
$pesan_harga = "Admin (".$_SESSION['username'].") telah memperbarui Master Harga Jual pada " . date('d/m/Y H:i');
mysqli_query($conn, "INSERT INTO internal_messages (sender_id, receiver_role, pesan) VALUES ('1', 'manager', '$pesan_harga')");
        echo "<script>alert('Semua harga berhasil diperbarui!'); window.location='atur_harga.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal menyimpan harga: " . $e->getMessage() . "');</script>";
    }
}

// --- AMBIL DATA PRODUK & HARGA SAAT INI ---
$sql = "
    SELECT 
        p.id_produk, 
        p.nama_produk, 
        p.jenjang, 
        p.kategori, 
        IFNULL(pr.harga_jual, 0) as harga_saat_ini
    FROM products p
    LEFT JOIN prices pr ON p.id_produk = pr.id_produk AND pr.tahun_ajaran = '$tahun_ajaran_aktif'
    WHERE p.is_active = 1
    ORDER BY p.jenjang ASC, p.kategori ASC, p.nama_produk ASC
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Harga - Kedai Nusaputera</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .main-content { padding: 30px 50px; }
        .table-price { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-price th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .table-price td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .input-price-field {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-weight: 700;
            color: #16a34a;
            font-size: 15px;
            text-align: right;
        }
        .input-price-field:focus { border-color: var(--primary); outline: none; background: #f0fdf4; }

        .currency-symbol { font-size: 12px; color: #94a3b8; margin-right: 5px; font-weight: 400; }
        
        .section-header {
            background: #f1f5f9;
            padding: 10px 20px;
            font-weight: 800;
            font-size: 12px;
            color: #475569;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="header-area">
            <div>
                <h2>💰 Master Harga Jual</h2>
                <p>Kelola daftar harga seragam dan atribut untuk periode: <b><?php echo $tahun_ajaran_aktif; ?></b></p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="jual_langsung.php" class="btn btn-outline">
                    <i class="ph ph-shopping-cart"></i> Ke Menu Kasir
                </a>
            </div>
        </div>

        <form method="POST">
            <div class="card" style="padding:0; overflow:hidden;">
                <table class="table-price">
                    <thead>
                        <tr>
                            <th width="100">Unit</th>
                            <th>Kategori</th>
                            <th>Nama Produk</th>
                            <th width="250" style="text-align:right;">Harga Jual (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $current_jenjang = "";
                        while($row = mysqli_fetch_assoc($result)): 
                            // Penanda grup jenjang agar rapi
                            if($current_jenjang != $row['jenjang']):
                                $current_jenjang = $row['jenjang'];
                        ?>
                            <tr>
                                <td colspan="4" class="section-header">Unit Pendidikan: <?php echo $current_jenjang; ?></td>
                            </tr>
                        <?php endif; ?>

                        <tr>
                            <td><span class="badge" style="background:#eef2ff; color:var(--primary);"><?php echo $row['jenjang']; ?></span></td>
                            <td><small style="color:#64748b;"><?php echo $row['kategori']; ?></small></td>
                            <td><strong><?php echo $row['nama_produk']; ?></strong></td>
                            <td>
                                <div style="display:flex; align-items:center;">
                                    <span class="currency-symbol">Rp</span>
                                    <input type="number" 
                                           name="harga[<?php echo $row['id_produk']; ?>]" 
                                           value="<?php echo $row['harga_saat_ini']; ?>" 
                                           class="input-price-field" 
                                           min="0" step="500">
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div style="padding:30px; background:#f8fafc; border-top:1px solid #e2e8f0; text-align:right;">
                    <button type="submit" name="simpan_semua_harga" class="btn btn-primary" style="padding: 15px 60px; font-size: 16px;">
                        <i class="ph-bold ph-floppy-disk"></i> SIMPAN PERUBAHAN HARGA
                    </button>
                </div>
            </div>
        </form>
    </div>

</body>
</html>