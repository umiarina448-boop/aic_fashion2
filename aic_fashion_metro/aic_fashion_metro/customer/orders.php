<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Gunakan prepared statement untuk keamanan
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE user_id = ?
    ORDER BY id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - AIC Fashion Metro</title>
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

        .container h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .container h2 i {
            color: #EE6C4D;
        }

        /* CARD ORDER */
        .order-card {
            background: white;
            border-radius: 20px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
            transition: 0.2s;
            border: 1px solid #f0f0f0;
        }

        .order-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        /* ORDER HEADER */
        .order-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .order-id {
            font-weight: 700;
            color: #EE6C4D;
            font-size: 14px;
        }

        .order-date {
            font-size: 12px;
            color: #888;
        }

        .order-date i {
            margin-right: 5px;
        }

        /* ORDER BODY */
        .order-body {
            padding: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .order-image {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
        }

        .order-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            background: #f5f5f5;
        }

        .order-info {
            flex: 1;
            min-width: 200px;
        }

        .product-name {
            font-weight: 600;
            font-size: 16px;
            color: #333;
            margin-bottom: 8px;
        }

        .product-meta {
            font-size: 13px;
            color: #777;
            margin-bottom: 5px;
        }

        .total-price {
            font-weight: 700;
            color: #EE6C4D;
            font-size: 18px;
            margin: 10px 0;
        }

        /* STATUS BADGE */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-menunggu { background: #fff3e0; color: #ff9800; }
        .status-diproses { background: #e3f2fd; color: #2196f3; }
        .status-dikirim { background: #e8f5e9; color: #4caf50; }
        .status-selesai { background: #e8f5e9; color: #2ecc71; }
        .status-batal { background: #ffebee; color: #f44336; }
        .status-pending { background: #fff3e0; color: #ff9800; }
        .status-proses { background: #e3f2fd; color: #2196f3; }
        .status-kirim { background: #e8f5e9; color: #4caf50; }

        /* ORDER FOOTER */
        .order-footer {
            background: #fafafa;
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .address-info {
            font-size: 12px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .address-info i {
            color: #EE6C4D;
        }

        .btn-detail {
            background: #EE6C4D;
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-detail:hover {
            background: #d95a3c;
            transform: translateY(-2px);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 25px;
        }

        .shop-link {
            display: inline-block;
            background: #EE6C4D;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 40px;
            font-weight: 600;
            transition: 0.2s;
        }

        .shop-link:hover {
            background: #d95a3c;
            transform: translateY(-2px);
        }

        /* RESPONSIVE */
        @media (max-width: 600px) {
            .container {
                margin-top: 70px;
                padding: 0 15px;
            }
            .order-body {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .order-footer {
                flex-direction: column;
            }
            .order-header {
                flex-direction: column;
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
    <h2>
        <i class="fa-solid fa-box"></i> 
        Pesanan Saya
    </h2>

    <?php if($data->num_rows == 0){ ?>
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <h3>Belum Ada Pesanan</h3>
            <p>Yuk mulai belanja dan dapatkan produk favoritmu!</p>
            <a href="dashboard.php" class="shop-link">
                <i class="fa-solid fa-shop"></i> Belanja Sekarang
            </a>
        </div>
    <?php } ?>

    <?php while($order = $data->fetch_assoc()){ 
        $order_id = $order['id'];
        
        // Ambil produk pertama untuk thumbnail (pakai prepared statement)
        $stmt_produk = $conn->prepare("
            SELECT p.gambar, p.nama_produk, od.qty, od.harga
            FROM order_details od
            JOIN products p ON od.product_id = p.id
            WHERE od.order_id = ?
            LIMIT 1
        ");
        $stmt_produk->bind_param("i", $order_id);
        $stmt_produk->execute();
        $produk = $stmt_produk->get_result()->fetch_assoc();
        
        // Hitung jumlah item dalam pesanan
        $stmt_count = $conn->prepare("
            SELECT COUNT(*) as total_item, SUM(qty) as total_qty 
            FROM order_details 
            WHERE order_id = ?
        ");
        $stmt_count->bind_param("i", $order_id);
        $stmt_count->execute();
        $item_count = $stmt_count->get_result()->fetch_assoc();
        
        // Format tanggal
        $tanggal = date('d M Y, H:i', strtotime($order['created_at'] ?? $order['tanggal'] ?? 'now'));
        
        // Status styling
        $status = strtolower($order['status'] ?? 'pending');
        $status_map = [
            'menunggu' => 'menunggu',
            'pending' => 'menunggu',
            'proses' => 'diproses',
            'diproses' => 'diproses',
            'kirim' => 'dikirim',
            'dikirim' => 'dikirim',
            'selesai' => 'selesai',
            'batal' => 'batal'
        ];
        $status_class = $status_map[$status] ?? 'menunggu';
        $status_label = ucfirst($status_class);
        if($status_class == 'dikirim') $status_label = 'Dikirim';
        if($status_class == 'diproses') $status_label = 'Diproses';
    ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <span class="order-id">
                        <i class="fa-solid fa-hashtag"></i> ORDER #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                    </span>
                    <span class="order-date">
                        <i class="fa-regular fa-calendar"></i> <?php echo $tanggal; ?>
                    </span>
                </div>
                <div>
                    <span class="status-badge status-<?php echo $status_class; ?>">
                        <i class="fa-solid <?php 
                            echo $status_class == 'menunggu' ? 'fa-clock' : 
                                ($status_class == 'diproses' ? 'fa-spinner' : 
                                ($status_class == 'dikirim' ? 'fa-truck' : 
                                ($status_class == 'selesai' ? 'fa-circle-check' : 'fa-times-circle'))); 
                        ?>"></i>
                        <?php echo $status_label; ?>
                    </span>
                </div>
            </div>
            
            <div class="order-body">
                <div class="order-image">
                    <img src="../uploads/<?php echo htmlspecialchars($produk['gambar'] ?? 'default.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($produk['nama_produk'] ?? 'Produk'); ?>"
                         onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                </div>
                <div class="order-info">
                    <div class="product-name">
                        <?php echo htmlspecialchars($produk['nama_produk'] ?? 'Produk tidak tersedia'); ?>
                    </div>
                    <div class="product-meta">
                        <?php if(($item_count['total_qty'] ?? 0) > 1){ ?>
                            + <?php echo ($item_count['total_qty'] - 1); ?> produk lainnya
                        <?php } ?>
                    </div>
                    <div class="total-price">
                        Rp <?php echo number_format($order['total_harga'] ?? $order['total'] ?? 0, 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
            
            <div class="order-footer">
                <div class="address-info">
                    <i class="fa-solid fa-location-dot"></i>
                    <?php 
                    $alamat = $order['alamat'] ?? $order['shipping_address'] ?? 'Alamat tidak tersedia';
                    $alamat_singkat = strlen($alamat) > 50 ? substr($alamat, 0, 50) . '...' : $alamat;
                    echo htmlspecialchars($alamat_singkat);
                    ?>
                </div>
                <a href="detail_pesanan.php?id=<?php echo $order['id']; ?>" class="btn-detail">
                    <i class="fa-regular fa-eye"></i> Lihat Detail
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    <?php } ?>
</div>

</body>
</html>