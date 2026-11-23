<?php
include '../config/koneksi.php';
include '../config/helper.php';

$role = $_SESSION['role'] ?? 'admin';

// --- LOGIKA ADMIN: MEMBUAT SETORAN ---
if (isset($_POST['buat_setoran']) && $role == 'admin') {
    $jumlah = (int)$_POST['jumlah_setoran'];
    
    // Upload File Bukti
    $file_bukti = "";
    if (!empty($_FILES['file_bukti']['name'])) {
        $ext = pathinfo($_FILES['file_bukti']['name'], PATHINFO_EXTENSION);
        $file_bukti = "STR_" . time() . "." . $ext;
        $target = "../uploads/setoran/" . $file_bukti;
        if (!is_dir('../uploads/setoran/')) { mkdir('../uploads/setoran/', 0777, true); }
        move_uploaded_file($_FILES['file_bukti']['tmp_name'], $target);
    }

    mysqli_query($conn, "INSERT INTO setoran_keuangan (jumlah_setoran, file_bukti, status) VALUES ('$jumlah', '$file_bukti', 'pending')");
    
    // Kirim Notifikasi ke Manager
    $pesan_notif = "Admin menyetorkan uang kas sebesar Rp " . number_format($jumlah, 0, ',', '.') . ". Menunggu validasi Anda.";
    mysqli_query($conn, "INSERT INTO internal_messages (sender_id, receiver_role, pesan) VALUES ('1', 'manager', '$pesan_notif')");

    echo "<script>alert('Setoran berhasil diajukan. Menunggu validasi Manager.'); window.location='setoran_keuangan.php';</script>";
}

// --- LOGIKA MANAGER: CHECKLIST / APPROVE SETORAN ---
if (isset($_GET['approve']) && $role == 'manager') {
    $id_setor = mysqli_real_escape_string($conn, $_GET['approve']);
    $nama_mgr = $_SESSION['username'];
    mysqli_query($conn, "UPDATE setoran_keuangan SET status = 'disetujui', penerima = '$nama_mgr' WHERE id_setoran = '$id_setor'");
    echo "<script>alert('Setoran berhasil divalidasi!'); window.location='setoran_keuangan.php';</script>";
}

// --- HITUNG KEUANGAN KASIR ---
$q_uang_masuk = mysqli_query($conn, "SELECT SUM(total_biaya) as total FROM orders WHERE total_biaya > 0");
$uang_masuk = mysqli_fetch_assoc($q_uang_masuk)['total'] ?? 0;

$q_uang_disetor = mysqli_query($conn, "SELECT SUM(jumlah_setoran) as total FROM setoran_keuangan WHERE status = 'disetujui'");
$uang_disetor = mysqli_fetch_assoc($q_uang_disetor)['total'] ?? 0;

$kas_di_tangan = $uang_masuk - $uang_disetor;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setoran Keuangan</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .main-content { padding: 30px 40px; background: #f8fafc; }
        .summary-box { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 10px 20px rgba(37,99,235,0.2); }
        .table-setor { width: 100%; border-collapse: collapse; }
        .table-setor th { background: #f1f5f9; padding: 15px; font-size: 11px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .table-setor td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
            <div>
                <h2 style="margin:0; font-size: 24px;">💸 Validasi & Setoran Kas</h2>
                <p style="margin:4px 0 0; color:#64748b; font-size: 13px;">Kelola serah terima uang dari hasil Penjualan Langsung.</p>
            </div>
            <div class="summary-box">
                <span style="font-size: 11px; font-weight: 700; opacity: 0.8; letter-spacing: 1px; text-transform: uppercase;">Uang Kas Belum Disetor</span>
                <h1 style="margin: 5px 0 0; font-size: 32px;">Rp <?php echo number_format($kas_di_tangan, 0, ',', '.'); ?></h1>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: <?php echo ($role=='admin')?'1fr 2fr':'1fr'; ?>; gap:30px; align-items: start;">
            
            <!-- FORM SETORAN (HANYA ADMIN) -->
            <?php if($role == 'admin'): ?>
            <div class="card" style="padding: 25px;">
                <h4 style="margin:0 0 20px; color:var(--primary);">Ajukan Setoran Baru</h4>
                <form method="POST" enctype="multipart/form-data">
                    <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:8px;">Jumlah Disetorkan (Rp)</label>
                    <input type="number" name="jumlah_setoran" max="<?php echo $kas_di_tangan; ?>" value="<?php echo $kas_di_tangan; ?>" class="form-control" required style="margin-bottom:20px; font-size:20px; font-weight:bold; color:#16a34a;">
                    
                    <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:8px;">Upload Bukti Transfer / Kwitansi</label>
                    <input type="file" name="file_bukti" accept="image/*,.pdf" class="form-control" required style="margin-bottom:25px; padding:10px;">
                    
                    <button type="submit" name="buat_setoran" class="btn btn-primary" style="width:100%; padding:15px; border-radius:10px;">KIRIM SETORAN</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- TABEL RIWAYAT SETORAN (BISA DILIHAT KEDUANYA) -->
            <div class="card" style="padding:0; overflow:hidden;">
                <table class="table-setor">
                    <thead>
                        <tr>
                            <th width="150">Waktu Pengajuan</th>
                            <th width="150">Nominal Setoran</th>
                            <th width="100" style="text-align:center;">Bukti</th>
                            <th width="150" style="text-align:center;">Status Validasi</th>
                            <?php if($role == 'manager'): ?><th width="120" style="text-align:center;">Aksi (Checklist)</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $q_setor = mysqli_query($conn, "SELECT * FROM setoran_keuangan ORDER BY tgl_setor DESC");
                        if(mysqli_num_rows($q_setor) == 0) echo "<tr><td colspan='5' align='center' style='padding:40px; color:#94a3b8;'>Belum ada riwayat setoran.</td></tr>";

                        while($row = mysqli_fetch_assoc($q_setor)): 
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo date('d M Y', strtotime($row['tgl_setor'])); ?></strong><br>
                                <small style="color:#64748b;"><?php echo date('H:i', strtotime($row['tgl_setor'])); ?> WIB</small>
                            </td>
                            <td style="font-size: 16px; font-weight: 800; color: #16a34a;">Rp <?php echo number_format($row['jumlah_setoran'], 0, ',', '.'); ?></td>
                            <td align="center">
                                <?php if($row['file_bukti']): ?>
                                    <a href="../uploads/setoran/<?php echo $row['file_bukti']; ?>" target="_blank" class="btn btn-outline" style="padding:5px 10px; font-size:11px;"><i class="ph-bold ph-eye"></i> Lihat</a>
                                <?php else: echo "-"; endif; ?>
                            </td>
                            <td align="center">
                                <?php if($row['status'] == 'disetujui'): ?>
                                    <span class="badge bg-green" style="display:block;">DISETUJUI</span>
                                    <small style="color:#64748b; font-size:10px;">Oleh: <?php echo strtoupper($row['penerima']); ?></small>
                                <?php else: ?>
                                    <span class="badge bg-orange">MENUNGGU</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- CHECKLIST MANAGER -->
                            <?php if($role == 'manager'): ?>
                            <td align="center">
                                <?php if($row['status'] == 'pending'): ?>
                                    <a href="?approve=<?php echo $row['id_setoran']; ?>" onclick="return confirm('Validasi setoran ini? Uang fisik/transfer sudah Anda terima?')" class="btn btn-primary" style="padding:8px 15px; font-size:12px;"><i class="ph-bold ph-check"></i> VALIDASI</a>
                                <?php else: ?>
                                    <i class="ph-fill ph-check-circle" style="color:#10b981; font-size:24px;"></i>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>
</html>