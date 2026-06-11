<?php
session_start();
include "../config/database.php";

/* SECURITY CHECK */
if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

/* VALIDASI ID ORDER */
if(!isset($_GET['id']) || empty($_GET['id'])){
    header("Location: dashboard.php");
    exit;
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

/* AMBIL DATA ORDER dengan prepared statement */
$stmt_order = $conn->prepare("
    SELECT o.*, u.nama, u.email 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt_order->bind_param("ii", $order_id, $user_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();
$order = $order_result->fetch_assoc();

if(!$order){
    header("Location: dashboard.php");
    exit;
}

// Format tanggal
$tanggal = date('d F Y, H:i', strtotime($order['created_at'] ?? 'now'));

// Estimasi pengiriman berdasarkan status
$estimasi = "";
if($order['status'] == 'pending'){
    $estimasi = "Pesanan Anda sedang menunggu konfirmasi. Kami akan segera memproses pesanan Anda.";
} elseif($order['status'] == 'proses'){
    $estimasi = "Pesanan sedang diproses. Akan segera dikirim dalam 1-2 hari.";
} elseif($order['status'] == 'dikirim'){
    $estimasi = "Pesanan sedang dalam perjalanan. Perkiraan sampai dalam 1-3 hari.";
} else {
    $estimasi = "Terima kasih telah berbelanja di AIC Fashion Metro!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - AIC Fashion Metro</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .success-card {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.1);
            animation: fadeInUp 0.5s ease;
            text-align: center;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* HEADER */
        .success-header {
            background: linear-gradient(135deg, #EE6C4D, #ff8a65);
            padding: 30px 20px;
            color: white;
        }

        .check-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .check-icon i {
            font-size: 45px;
            color: #2ecc71;
        }

        .success-header h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .success-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* BODY */
        .success-body {
            padding: 30px;
        }

        .order-info {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .order-number {
            margin-bottom: 15px;
        }

        .order-number .label {
            font-size: 13px;
            color: #888;
            margin-bottom: 5px;
        }

        .order-number .number {
            font-size: 28px;
            font-weight: 700;
            color: #EE6C4D;
            letter-spacing: 1px;
        }

        .order-detail {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-top: 1px solid #eee;
        }

        .order-detail .label {
            font-size: 13px;
            color: #888;
        }

        .order-detail .value {
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
            margin-top: 10px;
        }

        .status-pending {
            background: #fff3e0;
            color: #ff9800;
        }

        .status-proses {
            background: #e3f2fd;
            color: #2196f3;
        }

        .status-dikirim {
            background: #e8f5e9;
            color: #4caf50;
        }

        .status-selesai {
            background: #e8f5e9;
            color: #2ecc71;
        }

        /* ESTIMASI */
        .estimasi-box {
            background: #e8f5e9;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }

        .estimasi-box i {
            font-size: 24px;
            color: #2ecc71;
        }

        .estimasi-box p {
            font-size: 13px;
            color: #2e7d32;
            line-height: 1.5;
            margin: 0;
        }

        /* BUTTONS */
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: #EE6C4D;
            color: white;
        }

        .btn-primary:hover {
            background: #d95a3c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(238,108,77,0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #555;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid #EE6C4D;
            color: #EE6C4D;
        }

        .btn-outline:hover {
            background: #EE6C4D;
            color: white;
        }

        /* SHARE SECTION */
        .share-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .share-section p {
            font-size: 12px;
            color: #999;
            margin-bottom: 12px;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .social-icons a {
            width: 38px;
            height: 38px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            text-decoration: none;
            transition: 0.2s;
        }

        .social-icons a:hover {
            background: #EE6C4D;
            color: white;
            transform: translateY(-3px);
        }

        /* RESPONSIVE */
        @media (max-width: 500px) {
            .success-card {
                border-radius: 24px;
            }
            .success-body {
                padding: 20px;
            }
            .btn-group {
                flex-direction: column;
            }
            .order-number .number {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

<div class="success-card">
    <div class="success-header">
        <div class="check-icon">
            <i class="fa-regular fa-circle-check"></i>
        </div>
        <h2>Pesanan Berhasil!</h2>
        <p>Terima kasih telah berbelanja di AIC Fashion Metro</p>
    </div>
    
    <div class="success-body">
        <!-- ORDER INFO -->
        <div class="order-info">
            <div class="order-number">
                <div class="label">Nomor Pesanan</div>
                <div class="number">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
            </div>
            
            <div class="order-detail">
                <span class="label">Tanggal Pesanan</span>
                <span class="value"><?php echo $tanggal; ?></span>
            </div>
            
            <div class="order-detail">
                <span class="label">Total Pembayaran</span>
                <span class="value">Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
            </div>
            
            <div class="order-detail">
                <span class="label">Metode Pembayaran</span>
                <span class="value"><?php echo htmlspecialchars($order['metode_pembayaran'] ?? 'COD'); ?></span>
            </div>
            
            <div>
                <span class="status-badge status-<?php echo $order['status']; ?>">
                    <i class="fa-solid <?php 
                        echo $order['status'] == 'pending' ? 'fa-clock' : 
                            ($order['status'] == 'proses' ? 'fa-spinner' : 
                            ($order['status'] == 'dikirim' ? 'fa-truck' : 'fa-circle-check')); 
                    ?>"></i>
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
        </div>
        
        <!-- ESTIMASI -->
        <div class="estimasi-box">
            <i class="fa-regular fa-clock"></i>
            <p><?php echo $estimasi; ?></p>
        </div>
        
        <!-- BUTTONS -->
        <div class="btn-group">
            <a href="detail_pesanan.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                <i class="fa-regular fa-eye"></i> Lihat Detail Pesanan
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fa-solid fa-gauge-high"></i> Ke Dashboard
            </a>
        </div>
        
        <!-- SHARE SECTION -->
        <div class="share-section">
            <p>Bagikan pesanan Anda ke media sosial</p>
            <div class="social-icons">
                <a href="#" onclick="shareToWA()">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="#" onclick="shareToIG()">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" onclick="shareToFB()">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" onclick="copyOrderId()">
                    <i class="fa-regular fa-copy"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi share ke WhatsApp
function shareToWA() {
    const orderId = '<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>';
    const text = `Halo! Saya baru saja berbelanja di AIC Fashion. Pesanan saya #${orderId}. Yuk belanja juga di AIC Fashion!`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
}

// Fungsi share ke Instagram (buka profile)
function shareToIG() {
    window.open('https://www.instagram.com/', '_blank');
}

// Fungsi share ke Facebook
function shareToFB() {
    const url = window.location.href;
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
}

// Copy nomor order
function copyOrderId() {
    const orderId = '<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>';
    navigator.clipboard.writeText(`#${orderId}`).then(() => {
        alert('Nomor pesanan berhasil disalin!');
    });
}
</script>

</body>
</html>