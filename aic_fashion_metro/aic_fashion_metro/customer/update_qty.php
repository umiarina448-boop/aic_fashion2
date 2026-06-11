<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    exit;
}

$id = $_POST['id'];
$action = $_POST['action'];

$q = mysqli_query($conn,"SELECT qty FROM cart WHERE id='$id'");
$row = mysqli_fetch_assoc($q);

$qty = $row['qty'];

if($action == "plus") $qty++;
if($action == "minus") {
    $qty--;
    if($qty < 1) $qty = 1;
}

mysqli_query($conn,"UPDATE cart SET qty='$qty' WHERE id='$id'");

echo json_encode([
    "success" => true,
    "qty" => $qty
]);