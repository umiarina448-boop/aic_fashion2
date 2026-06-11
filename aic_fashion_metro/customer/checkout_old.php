<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* USER DATA */
$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM users WHERE id='$user_id'
"));

/* SELECTED CART */
$selected = $_POST['cart_ids'] ?? [];

if (empty($selected)) {
    echo "<script>
        alert('Silakan pilih produk terlebih dahulu!');
        window.location.href='cart.php';
    </script>";
    exit;
}

$selected = array_map('intval', $selected);
$ids = implode(",", $selected);

/* GET SELECTED ITEMS */
$cart = mysqli_query($conn, "
    SELECT c.*, p.nama_produk, p.harga, p.gambar
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id='$user_id'
    AND c.id IN ($ids)
");

$items = [];
$subtotal = 0;

while ($row = mysqli_fetch_assoc($cart)) {
    $items[] = $row;
    $subtotal += $row['harga'] * $row['qty'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>

    <style>
        body { font-family: Arial; background:#f4f4f4; margin:0; }

        .container {
            max-width:1000px;
            margin:auto;
            display:grid;
            grid-template-columns:2fr 1fr;
            gap:20px;
            padding:20px;
        }

        .box {
            background:#fff;
            padding:20px;
            border-radius:12px;
        }

        .item {
            display:flex;
            gap:10px;
            margin-bottom:10px;
        }

        img {
            width:70px;
            height:70px;
            object-fit:cover;
            border-radius:8px;
        }

        .btn {
            width:100%;
            padding:12px;
            background:#EE6C4D;
            color:#fff;
            border:none;
            border-radius:10px;
            font-weight:bold;
            cursor:pointer;
        }

        .total {
            font-size:20px;
            font-weight:bold;
        }
    </style>
</head>

<body>

<div class="container">

    <!-- LEFT -->
    <div>

        <div class="box">
            <h3>📍 Alamat Pengiriman</h3>
            <p>
                <b><?= $user['nama']; ?></b><br>
                <?= $user['no_hp']; ?><br>
                <?= $user['alamat']; ?>
            </p>
        </div>

        <div class="box">
            <h3>🛒 Produk Dipilih</h3>

            <?php foreach ($items as $row) { ?>

                <div class="item">
                    <img src="../uploads/<?= $row['gambar']; ?>">

                    <div>
                        <b><?= $row['nama_produk']; ?></b><br>
                        Qty: <?= $row['qty']; ?><br>
                        Rp <?= number_format($row['harga'] * $row['qty']); ?>
                    </div>
                </div>

            <?php } ?>

        </div>

    </div>

    <!-- RIGHT -->
    <div class="box">

        <h3>Ringkasan</h3>

        <p>Subtotal</p>
        <div class="total">
            Rp <?= number_format($subtotal); ?>
        </div>

        <hr>

        <form method="POST" action="proses_checkout.php">

            <?php foreach ($selected as $id) { ?>
                <input type="hidden" name="cart_ids[]" value="<?= $id; ?>">
            <?php } ?>

            <button type="submit" class="btn">
                Buat Pesanan
            </button>

        </form>

    </div>

</div>

</body>
</html>