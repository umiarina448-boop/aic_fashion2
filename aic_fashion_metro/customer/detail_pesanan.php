<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$order_id){
    die("❌ ID pesanan tidak valid");
}

// Gunakan prepared statement untuk keamanan
$stmt_order = $conn->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ?
");
$stmt_order->bind_param("ii", $order_id, $user_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();
$order = $order_result->fetch_assoc();

if(!$order){
    die("❌ Pesanan tidak ditemukan");
}

// Ambil items dengan prepared statement
$stmt_items = $conn->prepare("
    SELECT od.*, p.nama_produk, p.gambar, p.harga as product_price
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    WHERE od.order_id = ?
");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

// STEP status (lebih lengkap)
$steps = [
    'pending' => ['label' => 'Menunggu', 'icon' => 'fa-clock', 'desc' => 'Pesanan menunggu konfirmasi'],
    'proses' => ['label' => 'Diproses', 'icon' => 'fa-spinner', 'desc' => 'Pesanan sedang diproses'],
    'dikirim' => ['label' => 'Dikirim', 'icon' => 'fa-truck', 'desc' => 'Pesanan dalam perjalanan'],
    'selesai' => ['label' => 'Selesai', 'icon' => 'fa-circle-check', 'desc' => 'Pesanan telah diterima']
];

$current = strtolower($order['status'] ?? 'pending');
$stepKeys = array_keys($steps);
$stepIndex = array_search($current, $stepKeys);
if($stepIndex === false) $stepIndex = 0;

// Estimasi pengiriman (opsional)
$estimasi = "";
if($current == 'dikirim'){
    $estimasi = "Pesanan Anda sedang dalam perjalanan. Perkiraan sampai dalam 1-3 hari.";
} elseif($current == 'proses'){
    $estimasi = "Pesanan sedang diproses. Akan segera dikirim.";
} elseif($current == 'pending'){
    $estimasi = "Menunggu konfirmasi pembayaran.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - AIC Fashion Metro</title>
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

        /* BACK BUTTON */
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #EE6C4D;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0,0,0,.1);
            transition: .2s;
            z-index: 999;
        }

        .back-btn:hover {
            background: #EE6C4D;
            color: white;
            transform: translateX(-3px);
        }

        /* CONTAINER */
        .container {
            max-width: 900px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        /* CARD */
        .card {
            background: white;
            border-radius: 24px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
        }

        .card-header {
            background: #f8f9fa;
            padding: 18px 25px;
            border-bottom: 1px solid #eee;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h3 i {
            color: #EE6C4D;
        }

        .card-body {
            padding: 25px;
        }

        /* ORDER INFO */
        .order-id {
            font-size: 14px;
            color: #888;
            margin-bottom: 10px;
        }

        .order-id span {
            color: #EE6C4D;
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending { background: #fff3e0; color: #ff9800; }
        .status-proses { background: #e3f2fd; color: #2196f3; }
        .status-dikirim { background: #e8f5e9; color: #4caf50; }
        .status-selesai { background: #e8f5e9; color: #2ecc71; }
        .status-batal { background: #ffebee; color: #f44336; }

        /* TRACKING */
        .track-container {
            padding: 20px 0;
        }

        .track {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 20px 0;
        }

        .track::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 10%;
            right: 10%;
            height: 4px;
            background: #e0e0e0;
            z-index: 0;
            border-radius: 10px;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e0e0e0;
            color: white;
            font-size: 20px;
            transition: 0.3s;
        }

        .step.active .step-icon {
            background: #EE6C4D;
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(238,108,77,0.3);
        }

        .step.completed .step-icon {
            background: #2ecc71;
        }

        .step-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }

        .step.active .step-label {
            color: #EE6C4D;
        }

        .step-desc {
            font-size: 10px;
            color: #999;
        }

        .estimasi {
            background: #e8f5e9;
            padding: 12px 20px;
            border-radius: 12px;
            margin-top: 20px;
            font-size: 13px;
            color: #2e7d32;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ITEM LIST */
        .item {
            display: flex;
            gap: 18px;
            padding: 15px;
            border: 1px solid #f0f0f0;
            border-radius: 16px;
            margin-bottom: 12px;
            transition: 0.2s;
        }

        .item:hover {
            background: #fafafa;
        }

        .item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            background: #f5f5f5;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .item-meta {
            font-size: 13px;
            color: #777;
            margin-bottom: 8px;
        }

        .item-price {
            color: #EE6C4D;
            font-weight: 600;
            font-size: 14px;
        }

        .item-subtotal {
            text-align: right;
            min-width: 100px;
        }

        .item-subtotal .label {
            font-size: 11px;
            color: #999;
        }

        .item-subtotal .value {
            font-weight: 700;
            color: #333;
        }

        /* SUMMARY */
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row.total {
            border-bottom: none;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 700;
        }

        .summary-row.total .value {
            color: #EE6C4D;
            font-size: 22px;
        }

        /* ADDRESS */
        .address-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 16px;
            display: flex;
            gap: 12px;
        }

        .address-box i {
            color: #EE6C4D;
            font-size: 20px;
            margin-top: 3px;
        }

        .address-text {
            flex: 1;
            line-height: 1.6;
            color: #555;
            font-size: 14px;
        }

        .map-container {
            margin-top: 15px;
            border-radius: 16px;
            overflow: hidden;
        }

        iframe {
            width: 100%;
            height: 250px;
            border: 0;
        }

        /* BUTTON */
        .btn-track {
            display: inline-block;
            background: #EE6C4D;
            color: white;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 15px;
            transition: 0.2s;
        }

        .btn-track:hover {
            background: #d95a3c;
            transform: translateY(-2px);
        }

        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #eee;
        }

        @media (max-width: 600px) {
            .container {
                margin-top: 70px;
                padding: 0 15px;
            }
            .track::before {
                left: 5%;
                right: 5%;
            }
            .step-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            .item {
                flex-direction: column;
                text-align: center;
            }
            .item-subtotal {
                text-align: center;
            }
        }
    </style>
</head>
<body>

<!-- BACK BUTTON -->
<a href="javascript:history.back()" class="back-btn">
    <i class="fa-solid fa-chevron-left"></i>
</a>

<div class="container">
    
    <!-- CARD: ORDER INFO -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="fa-solid fa-receipt"></i> 
                Informasi Pesanan
            </h3>
        </div>
        <div class="card-body">
            <div class="order-id">
                <i class="fa-regular fa-hashtag"></i> Nomor Order: 
                <span>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div style="margin: 15px 0;">
                <span class="status-badge status-<?php echo $current; ?>">
                    <i class="fa-solid <?php 
                        echo $current == 'pending' ? 'fa-clock' : 
                            ($current == 'proses' ? 'fa-spinner' : 
                            ($current == 'dikirim' ? 'fa-truck' : 'fa-circle-check')); 
                    ?>"></i>
                    <?php echo ucfirst($current); ?>
                </span>
            </div>
            <div>
                <i class="fa-regular fa-calendar"></i> Tanggal Pesanan: 
                <?php echo date('d F Y, H:i', strtotime($order['created_at'] ?? $order['tanggal'] ?? 'now')); ?>
            </div>
        </div>
    </div>

    <!-- CARD: STATUS TRACKING -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="fa-solid fa-timeline"></i> 
                Status Pesanan
            </h3>
        </div>
        <div class="card-body">
            <div class="track-container">
                <div class="track">
                    <?php foreach($stepKeys as $i => $key){ 
                        $step = $steps[$key];
                        $isActive = ($stepIndex >= $i);
                        $isCompleted = ($stepIndex > $i);
                    ?>
                        <div class="step <?php echo $isActive ? 'active' : ''; ?> <?php echo $isCompleted ? 'completed' : ''; ?>">
                            <div class="step-icon">
                                <i class="fa-solid <?php echo $step['icon']; ?>"></i>
                            </div>
                            <div class="step-label"><?php echo $step['label']; ?></div>
                            <div class="step-desc"><?php echo $step['desc']; ?></div>
                        </div>
                    <?php } ?>
                </div>
                
                <?php if($estimasi){ ?>
                    <div class="estimasi">
                        <i class="fa-solid fa-truck-fast"></i>
                        <?php echo $estimasi; ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- CARD: PRODUCT LIST -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="fa-solid fa-bag-shopping"></i> 
                Produk yang Dibeli
            </h3>
        </div>
        <div class="card-body">
            <?php 
            $subtotal_all = 0;
            while($row = $items->fetch_assoc()){ 
                $subtotal = ($row['harga'] ?? $row['product_price']) * $row['qty'];
                $subtotal_all += $subtotal;
            ?>
                <div class="item">
                    <img src="../uploads/<?php echo htmlspecialchars($row['gambar']); ?>" 
                         alt="<?php echo htmlspecialchars($row['nama_produk']); ?>"
                         onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($row['nama_produk']); ?></div>
                        <div class="item-meta">
                            Size: <?php echo htmlspecialchars($row['size'] ?? 'Free'); ?> | 
                            Qty: <?php echo $row['qty']; ?>
                        </div>
                        <div class="item-price">
                            Rp <?php echo number_format($row['harga'] ?? $row['product_price'], 0, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="item-subtotal">
                        <div class="label">Subtotal</div>
                        <div class="value">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></div>
                    </div>
                </div>
            <?php } ?>
            
            <hr>
            
            <div class="summary-row">
                <span>Subtotal</span>
                <span>Rp <?php echo number_format($subtotal_all, 0, ',', '.'); ?></span>
            </div>
            <div class="summary-row">
                <span>Ongkos Kirim</span>
                <span>Rp <?php echo number_format($order['ongkir'] ?? 0, 0, ',', '.'); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total Pembayaran</span>
                <span class="value">Rp <?php echo number_format($order['total_harga'] ?? $subtotal_all, 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <!-- CARD: SHIPPING ADDRESS -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="fa-solid fa-location-dot"></i> 
                Alamat Pengiriman
            </h3>
        </div>
        <div class="card-body">
            <div class="address-box">
                <i class="fa-solid fa-map-pin"></i>
                <div class="address-text">
                    <?php echo nl2br(htmlspecialchars($order['alamat'] ?? 'Alamat tidak tersedia')); ?>
                </div>
            </div>
            
            <?php if(!empty($order['alamat'])) { ?>
                <div class="map-container">
                    <iframe
                        loading="lazy"
                        src="https://www.google.com/maps?q=<?php echo urlencode($order['alamat']); ?>&output=embed">
                    </iframe>
                </div>
                <a href="https://www.google.com/maps?q=<?php echo urlencode($order['alamat']); ?>" 
                   target="_blank" class="btn-track">
                    <i class="fa-solid fa-map"></i> Buka di Google Maps
                </a>
            <?php } ?>
        </div>
    </div>

    <!-- BUTTON BACK TO ORDERS -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="orders.php" class="btn-track" style="background: #6c757d;">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Pesanan Saya
        </a>
    </div>
</div>

</body>
</html>