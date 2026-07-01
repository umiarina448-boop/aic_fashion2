<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Gunakan prepared statement untuk keamanan
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

if(!$user){
    header("Location: ../login.php");
    exit;
}

$items = [];
$subtotal = 0;

$mode = $_GET['mode'] ?? null;

/* ======================
   CART MODE (PRIORITAS)
====================== */
if(isset($_POST['cart_ids']) && !empty($_POST['cart_ids'])){

    $cart_ids = $_POST['cart_ids'];
    $id_list = implode(",", array_map('intval', $cart_ids));
    
    $stmt_cart = $conn->prepare("
        SELECT c.*, p.nama_produk, p.harga, p.gambar, p.stok
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.id IN ($id_list)
    ");
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $cart_result = $stmt_cart->get_result();

    while($row = $cart_result->fetch_assoc()){
        $items[] = $row;
        $subtotal += $row['harga'] * $row['qty'];
    }

    $_SESSION['checkout_items'] = $cart_ids;

/* ======================
   BUY NOW MODE
====================== */
} elseif($mode == 'buy_now' && isset($_SESSION['buy_now'])) {

    $b = $_SESSION['buy_now'];
    
    $stmt_produk = $conn->prepare("
        SELECT p.*, ? as size 
        FROM products p 
        WHERE p.id = ?
    ");
    $stmt_produk->bind_param("si", $b['size'], $b['product_id']);
    $stmt_produk->execute();
    $product_result = $stmt_produk->get_result();

    while($row = $product_result->fetch_assoc()){
        $row['qty'] = $b['qty'];
        $row['size'] = $b['size'] ?? 'Free';
        $row['cart_id'] = null;
        $items[] = $row;
        $subtotal += $row['harga'] * $b['qty'];
    }

} else {
    die("❌ Tidak ada produk dipilih. <a href='cart.php'>Kembali ke Keranjang</a>");
}

if(empty($items)){
    die("❌ Tidak ada produk dipilih. <a href='cart.php'>Kembali ke Keranjang</a>");
}

/* ======================
   CHECKOUT PROCESS
====================== */
if(isset($_POST['checkout'])){
    
    $ongkir = (int)$_POST['ongkir'];
    $payment = $_POST['payment'];
    $total = $subtotal + $ongkir;
    
    if(empty($user['alamat'])){
        $error = "Alamat pengiriman belum lengkap. Silakan <a href='profil.php'>update profil</a> terlebih dahulu.";
    } else {
        $conn->begin_transaction();
        
        try {
            $stmt_order = $conn->prepare("
                INSERT INTO orders 
                (user_id, toko_id, total_harga, status, alamat, no_hp, ongkir, metode_pembayaran, created_at)
                VALUES (?, 2, ?, 'pending', ?, ?, ?, ?, NOW())
            ");
            $stmt_order->bind_param("iissss", $user_id, $total, $user['alamat'], $user['no_hp'], $ongkir, $payment);
            $stmt_order->execute();
            $order_id = $conn->insert_id;
            
            foreach($items as $row){
                $product_id = isset($row['product_id']) ? $row['product_id'] : $row['id'];
                $qty = $row['qty'];
                $harga = $row['harga'];
                $size = $row['size'] ?? 'Free';
                
                $stmt_detail = $conn->prepare("
                    INSERT INTO order_details (order_id, product_id, qty, harga, size)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_detail->bind_param("iiiss", $order_id, $product_id, $qty, $harga, $size);
                $stmt_detail->execute();
                
                $stmt_stock = $conn->prepare("
                    UPDATE products SET stok = stok - ? WHERE id = ?
                ");
                $stmt_stock->bind_param("ii", $qty, $product_id);
                $stmt_stock->execute();
            }
            
            if(isset($_SESSION['checkout_items']) && !empty($_SESSION['checkout_items'])){
                $id_list = implode(",", array_map('intval', $_SESSION['checkout_items']));
                $stmt_delete = $conn->prepare("
                    DELETE FROM cart WHERE user_id = ? AND id IN ($id_list)
                ");
                $stmt_delete->bind_param("i", $user_id);
                $stmt_delete->execute();
                unset($_SESSION['checkout_items']);
            }
            
            unset($_SESSION['buy_now']);
            
            $conn->commit();
            
            header("Location: success.php?id=$order_id");
            exit;
            
        } catch(Exception $e){
            $conn->rollback();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$default_ongkir = 10000;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - AIC Fashion Metro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            padding-bottom: 40px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 20px;
            padding: 10px 20px;
            background: #EE6C4D;
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 600;
            transition: 0.2s;
        }

        .back-btn:hover {
            background: #d95a3c;
            transform: translateX(-3px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.8fr 1fr;
            gap: 25px;
            padding: 0 20px;
        }

        .left {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .right {
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
        }

        .card-header {
            background: #f8f9fa;
            padding: 18px 22px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header i {
            font-size: 20px;
            color: #EE6C4D;
        }

        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .card-body {
            padding: 22px;
        }

        .address-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 16px;
            line-height: 1.7;
            font-size: 14px;
            color: #555;
        }

        .edit-link {
            display: inline-block;
            margin-top: 12px;
            color: #EE6C4D;
            text-decoration: none;
            font-size: 13px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border: 1.5px solid #e0e0e0;
            border-radius: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: 0.2s;
        }

        .radio-option:hover {
            border-color: #EE6C4D;
            background: #fff8f5;
        }

        .radio-option input {
            width: 18px;
            height: 18px;
            accent-color: #EE6C4D;
        }

        .radio-content {
            flex: 1;
        }

        .radio-title {
            font-weight: 600;
            color: #333;
        }

        .radio-price {
            color: #EE6C4D;
            font-weight: 500;
            font-size: 14px;
        }

        .radio-desc {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        .product-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-item img {
            width: 75px;
            height: 75px;
            object-fit: cover;
            border-radius: 12px;
            background: #f5f5f5;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .product-meta {
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
        }

        .product-price {
            font-weight: 600;
            color: #EE6C4D;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            color: #555;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 16px;
            font-weight: 700;
        }

        .summary-total .value {
            color: #EE6C4D;
            font-size: 22px;
            font-weight: 800;
        }

        .btn-checkout {
            width: 100%;
            padding: 16px;
            background: #EE6C4D;
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-checkout:hover {
            background: #d95a3c;
            transform: translateY(-2px);
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        hr {
            margin: 15px 0;
            border: none;
            border-top: 1px solid #eee;
        }

        @media (max-width: 800px) {
            .container {
                grid-template-columns: 1fr;
            }
            .right {
                position: static;
            }
            .back-btn {
                margin: 15px;
            }
        }
    </style>
</head>
<body>

<a href="javascript:history.back()" class="back-btn">
    <i class="fa-solid fa-arrow-left"></i> Kembali
</a>

<div class="container">
    <!-- FORM CHECKOUT -->
    <form method="POST" class="left" id="checkoutForm">
        <!-- ALAMAT PENGIRIMAN -->
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-location-dot"></i>
                <h3>Alamat Pengiriman</h3>
            </div>
            <div class="card-body">
                <div class="address-info">
                    <i class="fa-regular fa-user"></i> <strong><?php echo htmlspecialchars($user['nama']); ?></strong><br>
                    <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($user['no_hp'] ?: 'Belum diisi'); ?><br>
                    <i class="fa-solid fa-map-pin"></i> <?php echo nl2br(htmlspecialchars($user['alamat'] ?: 'Belum diisi')); ?>
                </div>
                <a href="profil.php" class="edit-link">
                    <i class="fa-regular fa-pen-to-square"></i> Ubah Alamat
                </a>
            </div>
        </div>

        <!-- METODE PENGIRIMAN -->
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-truck"></i>
                <h3>Metode Pengiriman</h3>
            </div>
            <div class="card-body">
                <label class="radio-option">
                    <input type="radio" name="ongkir" value="10000" checked>
                    <div class="radio-content">
                        <div class="radio-title">Dalam Kota</div>
                        <div class="radio-desc">Estimasi 1-2 hari</div>
                    </div>
                    <div class="radio-price">Rp 10.000</div>
                </label>

                <label class="radio-option">
                    <input type="radio" name="ongkir" value="25000">
                    <div class="radio-content">
                        <div class="radio-title">Luar Kota</div>
                        <div class="radio-desc">Estimasi 3-5 hari</div>
                    </div>
                    <div class="radio-price">Rp 25.000</div>
                </label>
            </div>
        </div>

        <!-- METODE PEMBAYARAN -->
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-credit-card"></i>
                <h3>Metode Pembayaran</h3>
            </div>
            <div class="card-body">
                <label class="radio-option">
                    <input type="radio" name="payment" value="COD" checked>
                    <div class="radio-content">
                        <div class="radio-title">COD (Bayar di Tempat)</div>
                        <div class="radio-desc">Bayar saat pesanan tiba</div>
                    </div>
                </label>

                <label class="radio-option">
                    <input type="radio" name="payment" value="Transfer Bank">
                    <div class="radio-content">
                        <div class="radio-title">Transfer Bank</div>
                        <div class="radio-desc">BCA / Mandiri / BRI</div>
                    </div>
                </label>

                <label class="radio-option">
                    <input type="radio" name="payment" value="E-Wallet">
                    <div class="radio-content">
                        <div class="radio-title">E-Wallet</div>
                        <div class="radio-desc">OVO / GoPay / Dana</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- PRODUK YANG DIBELI -->
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-bag-shopping"></i>
                <h3>Produk yang Dibeli (<?php echo count($items); ?> item)</h3>
            </div>
            <div class="card-body">
                <?php foreach($items as $row){ ?>
                    <div class="product-item">
                        <img src="../uploads/<?php echo htmlspecialchars($row['gambar']); ?>" 
                             alt="<?php echo htmlspecialchars($row['nama_produk']); ?>"
                             onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                        <div class="product-details">
                            <div class="product-name"><?php echo htmlspecialchars($row['nama_produk']); ?></div>
                            <div class="product-meta">
                                Size: <?php echo htmlspecialchars($row['size'] ?? 'Free'); ?> | 
                                Qty: <?php echo $row['qty']; ?>
                            </div>
                            <div class="product-price">
                                Rp <?php echo number_format($row['harga'] * $row['qty'], 0, ',', '.'); ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Hidden inputs untuk cart_ids -->
        <?php if(isset($_POST['cart_ids']) && !empty($_POST['cart_ids'])): ?>
            <?php foreach($_POST['cart_ids'] as $cid): ?>
                <input type="hidden" name="cart_ids[]" value="<?php echo $cid; ?>">
            <?php endforeach; ?>
        <?php endif; ?>
    </form>

    <!-- RIGHT SIDE - RINGKASAN BELANJA -->
    <div class="right">
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-receipt"></i>
                <h3>Ringkasan Belanja</h3>
            </div>
            <div class="card-body">
                <div class="summary-row">
                    <span>Subtotal (<?php echo count($items); ?> item)</span>
                    <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span>Ongkos Kirim</span>
                    <span>Rp <span id="ongkirText"><?php echo number_format($default_ongkir, 0, ',', '.'); ?></span></span>
                </div>
                
                <hr>
                
                <div class="summary-total">
                    <span>Total Pembayaran</span>
                    <span class="value" id="totalText">Rp <?php echo number_format($subtotal + $default_ongkir, 0, ',', '.'); ?></span>
                </div>

                <button type="submit" name="checkout" form="checkoutForm" class="btn-checkout">
                    <i class="fa-regular fa-circle-check"></i> Buat Pesanan
                </button>
                
                <p style="font-size: 11px; color: #999; text-align: center; margin-top: 12px;">
                    <i class="fa-regular fa-shield"></i> Data Anda aman dan terenkripsi
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Update total saat ongkir berubah
const subtotal = <?php echo $subtotal; ?>;
const ongkirRadios = document.querySelectorAll('input[name="ongkir"]');
const ongkirText = document.getElementById('ongkirText');
const totalText = document.getElementById('totalText');

function updateTotal() {
    let ongkir = 0;
    ongkirRadios.forEach(radio => {
        if(radio.checked) {
            ongkir = parseInt(radio.value);
        }
    });
    ongkirText.innerText = ongkir.toLocaleString();
    totalText.innerText = 'Rp ' + (subtotal + ongkir).toLocaleString();
}

ongkirRadios.forEach(radio => {
    radio.addEventListener('change', updateTotal);
});

// Initial update
updateTotal();
</script>

</body>
</html>
