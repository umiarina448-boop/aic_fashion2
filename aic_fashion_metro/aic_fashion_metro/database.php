<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "aic_fashion";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

echo "<h2>Koneksi Database Berhasil!</h2>";

?>