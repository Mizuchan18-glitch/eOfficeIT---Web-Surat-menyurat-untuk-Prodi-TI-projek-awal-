<?php
$host = "sql300.infinityfree.com";
$user = "if0_40956669";
$pass = "Annejaa18";
$db   = "if0_40956669_prodi_surat";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}
?>