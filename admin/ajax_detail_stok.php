<?php
include '../config/koneksi.php';

$id_order = $_GET['id'];

$sql = "SELECT p.nama_produk, v.ukuran, od.qty, v.stok as stok_gudang, od.is_received
        FROM order_details od
        JOIN variants v ON od.id_variant = v.id_variant
        JOIN products p ON v.id_produk = p.id_produk
        WHERE od.id_order = '$id_order'";

$query = mysqli_query($conn, $sql);

echo '<table class="table-modal" width="100%" border="1" style="border-collapse:collapse;">
        <thead>
            <tr style="background:#f8fafc;">
                <th>Nama Barang</th>
                <th>Size</th>
                <th>Qty</th>
                <th>Stok Gudang</th>
                <th>Status Item</th>
            </tr>
        </thead>
        <tbody>';

while ($row = mysqli_fetch_assoc($query)) {
    $qty_pesan = $row['qty'];
    $stok_fisik = $row['stok_gudang'];
    
    if ($row['is_received'] == 1) {
        $status = '<span style="color:#3b82f6; font-weight:bold;">Selesai (Diambil)</span>';
        $bg = "";
    } else if ($stok_fisik >= $qty_pesan) {
        $status = '<span style="color:#16a34a; font-weight:bold;">READY</span>';
        $bg = "background:#f0fdf4;";
    } else {
        $status = '<span style="color:#dc2626; font-weight:bold;">BELUM ADA (PO)</span>';
        $bg = "background:#fef2f2;";
    }

    echo "<tr style='$bg'>
            <td style='padding:10px;'>{$row['nama_produk']}</td>
            <td align='center'>{$row['ukuran']}</td>
            <td align='center'>$qty_pesan</td>
            <td align='center'>$stok_fisik</td>
            <td align='center'>$status</td>
          </tr>";
}
echo '</tbody></table>';