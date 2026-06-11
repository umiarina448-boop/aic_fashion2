<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* 🔥 AMBIL DATA USER (INI YANG KAMU LUPA) */
$user = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM users WHERE id='$user_id'
"));

/* ONGKIR */
$ongkir = isset($_POST['ongkir']) ? (int)$_POST['ongkir'] : 10000;
$payment = $_POST['payment'] ?? 'COD';

/* AMBIL CART */
$cart = mysqli_query($conn,"
SELECT c.*, p.harga
FROM cart c
JOIN products p ON c.product_id = p.id
WHERE c.user_id='$user_id'
");

$total = 0;
$items = [];

while($row = mysqli_fetch_assoc($cart)){
    $items[] = $row;
    $total += (int)$row['harga'] * (int)$row['qty'];
}

$total_final = $total + $ongkir;

/* INSERT ORDER */
mysqli_query($conn,"
INSERT INTO orders 
(user_id, total_harga, status, alamat, no_hp, ongkir, metode_pembayaran)
VALUES 
('$user_id', '$total_final', 'pending', '{$user['alamat']}', '{$user['no_hp']}', '$ongkir', '$payment')
");

$order_id = mysqli_insert_id($conn);

/* INSERT DETAIL */
foreach($items as $row){
    mysqli_query($conn,"
    INSERT INTO order_details (order_id,product_id,qty,harga)
    VALUES ('$order_id','{$row['product_id']}','{$row['qty']}','{$row['harga']}')
    ");
}

/* CLEAR CART */
mysqli_query($conn,"DELETE FROM cart WHERE user_id='$user_id'");

/* REDIRECT */
header("Location: success.php?id=$order_id");
exit;
?>