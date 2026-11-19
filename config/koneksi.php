<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_seragam"; // Pastikan nama DB sesuai yang kita buat di CMD tadi

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}
?>