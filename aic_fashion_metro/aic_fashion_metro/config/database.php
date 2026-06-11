<?php

$conn = mysqli_connect("localhost","root","","aic_fashion_metro");

if(!$conn){
    die("Koneksi gagal : " . mysqli_connect_error());
}

?>