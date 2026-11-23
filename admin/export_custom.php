<?php
include '../config/koneksi.php';
include '../config/helper.php';

// Proteksi Akses
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    exit;
}

// 1. Set Header Excel
$filename = "LAPORAN_AUDIT_DISTRIBUSI_ALL_UNIT_" . date('d-m-Y') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil list jenjang yang ada
$list_jenjang = ['TK', 'SD', 'SMP', 'SMA', 'SMK'];

echo "<h3>LAPORAN AUDIT & LOG DISTRIBUSI BARANG (EKSPANSI PER ITEM)</h3>";
echo "<h4>Waktu Unduh: " . date('d/m/Y H:i') . "</h4>";

foreach ($list_jenjang as $jn) {
    // A. Ambil Daftar Produk untuk Header Kolom (Spesifik Jenjang + UMUM)
    $produk_header = [];
    $sql_p = "SELECT id_produk, nama_produk FROM products 
              WHERE (jenjang = '$jn' OR jenjang = 'UMUM') 
              AND is_active = 1 
              ORDER BY kategori ASC, nama_produk ASC";
    $q_p = mysqli_query($conn, $sql_p);
    while ($p = mysqli_fetch_assoc($q_p)) {
        $produk_header[$p['id_produk']] = $p['nama_produk'];
    }

    if (empty($produk_header)) continue;

    // B. Ambil Data Pesanan Siswa di Jenjang Ini
    $sql_o = "SELECT * FROM orders WHERE jenjang_siswa = '$jn' ORDER BY tgl_ukur DESC";
    $q_o = mysqli_query($conn, $sql_o);

    // C. Ambil Detail Item untuk Pemetaan (Mapping)
    $detail_map = [];
    $q_d = mysqli_query($conn, "SELECT od.id_order, v.id_produk, v.ukuran, od.qty, od.catatan_item, p.nama_produk 
                                FROM order_details od 
                                JOIN variants v ON od.id_variant = v.id_variant
                                JOIN products p ON v.id_produk = p.id_produk");
    while ($d = mysqli_fetch_assoc($q_d)) {
        // Simpan ukuran dan qty
        $detail_map[$d['id_order']][$d['id_produk']] = [
            'ukuran' => $d['ukuran'],
            'qty' => $d['qty'],
            'catatan' => $d['catatan_item'],
            'nama' => $d['nama_produk']
        ];
    }

    // D. Mulai Gambar Tabel per Jenjang
    echo "<br><b>UNIT JENJANG: $jn</b>";
    echo "<table border='1'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>NO</th>";
    echo "<th>WAKTU & TANGGAL</th>";
    echo "<th>NAMA SISWA</th>";
    echo "<th>UNIT/KELAS</th>";
    
    // Header Produk Dinamis
    foreach ($produk_header as $id_p => $nama_p) {
        $clean_name = str_ireplace([" $jn", " Sekolah"], "", $nama_p);
        echo "<th>" . strtoupper($clean_name) . "</th>";
    }

    echo "<th>STATUS</th>";
    echo "<th>PENERIMA BARANG</th>";
    echo "</tr>";

    $no = 1;
    if (mysqli_num_rows($q_o) > 0) {
        while ($row = mysqli_fetch_assoc($q_o)) {
            $ido = $row['id_order'];
            echo "<tr>";
            echo "<td align='center'>$no</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($row['tgl_ukur'])) . "</td>";
            echo "<td>" . strtoupper($row['nama_siswa']) . "</td>";
            echo "<td align='center'>" . $row['jenjang_siswa'] . " / " . $row['kelas_calon'] . "</td>";

            // Isi Kolom Produk (Menampilkan Ukuran)
            foreach ($produk_header as $id_p => $nama_p) {
                $val = "";
                if (isset($detail_map[$ido][$id_p])) {
                    $item = $detail_map[$ido][$id_p];
                    
                    // Logika tampilan: Jika Bordir Nama tampilkan teksnya, jika lain tampilkan Ukuran
                    if ($item['nama'] == 'Bordir Nama') {
                        $val = str_ireplace("Teks Bordir: ", "", $item['catatan']);
                    } else {
                        $val = $item['ukuran'];
                    }
                }
                echo "<td align='center' style='color: red; font-weight: bold;'>" . $val . "</td>";
            }

            echo "<td align='center'><b>" . strtoupper($row['status_order']) . "</b></td>";
            echo "<td>";
            if ($row['status_order'] == 'diambil') {
                echo strtoupper($row['nama_pengambil']) . " (" . date('d/m/y', strtotime($row['tgl_ambil'])) . ")";
            } else {
                echo "Belum Diserahkan";
            }
            echo "</td>";
            echo "</tr>";
            $no++;
        }
    } else {
        $total_col = 5 + count($produk_header);
        echo "<tr><td colspan='$total_col' align='center'>Tidak ada data distribusi untuk unit $jn</td></tr>";
    }
    echo "</table><br>";
}
?>