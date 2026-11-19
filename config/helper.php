<?php
// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. CEK LOGIN
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: ../login.php");
    exit;
}

// 2. LOGIKA SET JENJANG (REVISI AGAR TIDAK SELALU KE DASHBOARD)
if (isset($_GET['set_jenjang'])) {
    $_SESSION['jenjang_aktif'] = $_GET['set_jenjang'];
    
    // Cek apakah ada instruksi redirect ke halaman spesifik?
    if (isset($_GET['redirect'])) {
        // Jika ada, balik ke halaman tersebut
        header("Location: ../" . $_GET['redirect']);
    } else {
        // Jika tidak ada, baru lempar ke dashboard (default)
        header("Location: ../admin/index.php");
    }
    exit;
}

// Simpan jenjang aktif ke variabel
$jenjang_aktif = $_SESSION['jenjang_aktif'] ?? 'ALL';
?>