<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'admin';
?>

<div class="sidebar">
    <div class="brand">
        <i class="ph-fill ph-house-line"></i> KEDAI NUSAPUTERA
    </div>
    
    <div style="padding: 0 10px; margin-bottom: 20px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 15px;">
        <small style="color: #94a3b8; font-size: 10px; font-weight: 700; text-transform: uppercase;">Akses Sistem</small><br>
        <strong style="color: #1e293b; font-size: 13px; text-transform: uppercase;">
            <?php echo $role; ?> : <?php echo $_SESSION['nama_lengkap']; ?>
        </strong>
    </div>

    <div class="menu-label">Inventaris & Logistik</div>
    <nav>
        <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="ph ph-squares-four"></i> Dashboard
        </a>
        <a href="riwayat_transaksi.php" class="nav-link <?php echo ($current_page == 'riwayat_transaksi.php') ? 'active' : ''; ?>">
            <i class="ph ph-clock-counter-clockwise"></i> Log Distribusi Barang
        </a>
        <a href="stok_opname.php" class="nav-link <?php echo ($current_page == 'stok_opname.php') ? 'active' : ''; ?>">
            <i class="ph ph-package"></i> Laporan Stok & Defisit
        </a>
        <a href="laporan_matriks.php" class="nav-link <?php echo ($current_page == 'laporan_matriks.php') ? 'active' : ''; ?>">
            <i class="ph ph-grid-nine"></i> Matriks Stok (Excel View)
        </a>
    </nav>

    <div class="menu-label" style="margin-top:20px;">Produksi & Supply</div>
    <nav>
        <a href="po_vendor.php" class="nav-link <?php echo ($current_page == 'po_vendor.php' || $current_page == 'detail_po_vendor.php') ? 'active' : ''; ?>">
            <i class="ph ph-truck"></i> PO Vendor (Masuk Barang)
        </a>
    </nav>

    <?php if($role == 'admin'): ?>
    <div class="menu-label" style="margin-top:20px;">Operasional Kedai</div>
    <nav>
        <a href="input_ukur.php" class="nav-link <?php echo ($current_page == 'input_ukur.php') ? 'active' : ''; ?>">
            <i class="ph ph-measuring-tape"></i> Pengukuran Siswa
        </a>
        <a href="tukar_barang.php" class="nav-link <?php echo ($current_page == 'tukar_barang.php') ? 'active' : ''; ?>">
            <i class="ph ph-arrows-left-right"></i> Tukar Ukuran
        </a>
        <a href="koreksi_stok.php" class="nav-link <?php echo ($current_page == 'koreksi_stok.php') ? 'active' : ''; ?>">
            <i class="ph ph-wrench"></i> Koreksi Barang Rusak
        </a>
        <a href="validasi_ambil.php" class="nav-link <?php echo ($current_page == 'validasi_ambil.php') ? 'active' : ''; ?>">
            <i class="ph ph-qr-code"></i> Penyerahan Barang
        </a>
        <a href="tambah.php" class="nav-link <?php echo ($current_page == 'tambah.php') ? 'active' : ''; ?>">
            <i class="ph ph-plus-circle"></i> Master Produk
        </a>
        <a href="manajemen_gelombang.php" class="nav-link">
    <i class="ph-bold ph-calendar"></i>
    <span>Master Gelombang</span>
</a>
    </nav>
    <?php endif; ?>

    <a href="../logout.php" class="nav-link logout">
        <i class="ph-bold ph-sign-out"></i> Keluar Sistem
    </a>
</div>