<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Akses
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: index.php"); 
    exit;
}

// 1. PROSES SIMPAN (TAMBAH & EDIT)
if (isset($_POST['simpan'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_gelombang']);
    
    // --- PERBAIKAN FORMAT TANGGAL DI SINI ---
    $tgl_mulai_raw = $_POST['tgl_mulai'];
    $tgl_selesai_raw = $_POST['tgl_selesai'];

    // Konversi dari D/M/Y atau Y-M-D ke YYYY-MM-DD untuk MySQL
    // str_replace digunakan untuk memastikan strtotime memahami format D/M/Y dengan benar
    $tgl_mulai = date('Y-m-d', strtotime(str_replace('/', '-', $tgl_mulai_raw)));
    $tgl_selesai = date('Y-m-d', strtotime(str_replace('/', '-', $tgl_selesai_raw)));
    // --- AKHIR PERBAIKAN TANGGAL ---

    $status = $_POST['is_active'];
    $id = $_POST['id_wave'];

    if (!empty($id)) {
        // Update Data
        $sql = "UPDATE waves SET 
                nama_gelombang = '$nama', 
                tgl_mulai = '$tgl_mulai', 
                tgl_selesai = '$tgl_selesai', 
                is_active = '$status' 
                WHERE id_wave = '$id'";
    } else {
        // Insert Baru
        $sql = "INSERT INTO waves (nama_gelombang, tgl_mulai, tgl_selesai, is_active) 
                VALUES ('$nama', '$tgl_mulai', '$tgl_selesai', '$status')";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: manajemen_gelombang.php?msg=success");
    } else {
        // Echo error detail dari MySQL jika ada
        echo "Error: " . mysqli_error($conn);
    }
}

// 2. PROSES HAPUS
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM waves WHERE id_wave = '$id'");
    header("Location: manajemen_gelombang.php?msg=deleted");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Gelombang | Kedai Seragam</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        /* Styling Khusus Modal agar melayang sempurna */
        #modalWave {
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(15, 23, 42, 0.7); /* Overlay gelap transparan */
            z-index: 99999; 
            align-items: center; 
            justify-content: center; 
            backdrop-filter: blur(5px); /* Efek blur di belakang pop-up */
        }
        
        .modal-content {
            background: white;
            width: 100%;
            max-width: 450px;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: none;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .label-sm {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 5px;
            display: block;
            text-transform: uppercase;
        }

        .form-group { margin-bottom: 15px; }

        .table-custom { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-custom th { text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .table-custom td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .badge-active { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; }
        .badge-inactive { background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="header-area">
            <div>
                <h2>🌊 Manajemen Gelombang & Jadwal</h2>
                <p>Periode pendaftaran dan pengukuran seragam siswa.</p>
            </div>
            <button onclick="openModal()" class="btn btn-primary">
                <i class="ph-bold ph-plus"></i> Tambah Periode
            </button>
        </div>

        <div class="card">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Nama Gelombang</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th style="text-align:center;">Status Akses</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $today = date('Y-m-d');
                    $q = mysqli_query($conn, "SELECT * FROM waves ORDER BY id_wave DESC");
                    
                    if(mysqli_num_rows($q) == 0) {
                        echo "<tr><td colspan='5' align='center' style='color:#94a3b8; padding:30px;'>Belum ada data gelombang.</td></tr>";
                    }

                    while($row = mysqli_fetch_assoc($q)):
                        $is_expired = ($row['tgl_selesai'] < $today && $row['tgl_selesai'] != '0000-00-00');
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo $row['nama_gelombang']; ?></strong>
                        </td>
                        <td><?php echo ($row['tgl_mulai'] && $row['tgl_mulai'] != '0000-00-00') ? date('d M Y', strtotime($row['tgl_mulai'])) : '-'; ?></td>
                        <td>
                            <?php echo ($row['tgl_selesai'] && $row['tgl_selesai'] != '0000-00-00') ? date('d M Y', strtotime($row['tgl_selesai'])) : '-'; ?>
                            <?php if($is_expired): ?>
                                <span style="color: #ef4444; font-size: 10px; display: block; font-weight: bold;">(SUDAH BERAKHIR)</span>
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <?php if($row['is_active']): ?>
                                <span class="badge-active">BUKA</span>
                            <?php else: ?>
                                <span class="badge-inactive">TUTUP</span>
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <button onclick='editData(<?php echo json_encode($row); ?>)' class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                            <a href="?hapus=<?php echo $row['id_wave']; ?>" onclick="return confirm('Hapus gelombang ini? Semua data pesanan yang terkait mungkin terpengaruh.')" class="btn" style="color:#ef4444; font-size: 12px; margin-left: 10px;">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL POP-UP (Posisikan di luar main-content) -->
    <div id="modalWave">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 id="modalTitle" style="margin:0; color: #1e293b;">Pengaturan Gelombang</h3>
                <button type="button" onclick="closeModal()" style="background:none; border:none; cursor:pointer; font-size:24px; color:#94a3b8;">&times;</button>
            </div>

            <form method="POST">
                <input type="hidden" name="id_wave" id="id_wave">
                
                <div class="form-group">
                    <label class="label-sm">Nama Gelombang</label>
                    <input type="text" name="nama_gelombang" id="nama_gelombang" class="form-control" required placeholder="Contoh: PPDB 2026 Utama" style="width:100%; box-sizing:border-box;">
                </div>
                
                <div style="display: flex; gap: 15px;" class="form-group">
                    <div style="flex: 1;">
                        <label class="label-sm">Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" id="tgl_mulai" class="form-control" required style="width:100%; box-sizing:border-box;">
                    </div>
                    <div style="flex: 1;">
                        <label class="label-sm">Tanggal Selesai</label>
                        <input type="date" name="tgl_selesai" id="tgl_selesai" class="form-control" required style="width:100%; box-sizing:border-box;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="label-sm">Status Akses (Manual)</label>
                    <select name="is_active" id="is_active" class="form-control" style="width:100%; box-sizing:border-box;">
                        <option value="1">Buka Akses Pesanan</option>
                        <option value="0">Tutup Akses Pesanan</option>
                    </select>
                </div>

                <div style="display:flex; gap:10px; margin-top: 30px;">
                    <button type="submit" name="simpan" class="btn btn-primary" style="flex:2; padding: 15px;">Simpan Perubahan</button>
                    <button type="button" onclick="closeModal()" class="btn btn-outline" style="flex:1; padding: 15px;">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalWave');

        function openModal() {
            // Reset Form
            document.getElementById('id_wave').value = '';
            document.getElementById('nama_gelombang').value = '';
            document.getElementById('tgl_mulai').value = '';
            document.getElementById('tgl_selesai').value = '';
            document.getElementById('is_active').value = '1';
            document.getElementById('modalTitle').innerText = 'Tambah Gelombang Baru';
            
            modal.style.display = 'flex';
        }

        function closeModal() { 
            modal.style.display = 'none'; 
        }

        function editData(data) {
            document.getElementById('modalTitle').innerText = 'Edit Pengaturan Gelombang';
            document.getElementById('id_wave').value = data.id_wave;
            document.getElementById('nama_gelombang').value = data.nama_gelombang;
            document.getElementById('tgl_mulai').value = data.tgl_mulai;
            document.getElementById('tgl_selesai').value = data.tgl_selesai;
            document.getElementById('is_active').value = data.is_active;
            
            modal.style.display = 'flex';
        }

        // Tutup modal jika user klik di area luar box putih
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>