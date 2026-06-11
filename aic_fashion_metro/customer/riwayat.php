<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// PROSES BATALKAN PESANAN
if(isset($_GET['batal']) && isset($_GET['id'])){
    $order_id = (int)$_GET['id'];
    
    // Cek apakah pesanan milik user dan status masih pending/menunggu
    $stmt_check = $conn->prepare("
        SELECT status FROM orders 
        WHERE id = ? AND user_id = ? AND status IN ('pending', 'menunggu')
    ");
    $stmt_check->bind_param("ii", $order_id, $user_id);
    $stmt_check->execute();
    $check = $stmt_check->get_result();
    
    if($check->num_rows > 0){
        // Update status menjadi batal
        $stmt_update = $conn->prepare("
            UPDATE orders SET status = 'batal' WHERE id = ? AND user_id = ?
        ");
        $stmt_update->bind_param("ii", $order_id, $user_id);
        
        if($stmt_update->execute()){
            // Optional: Kembalikan stok produk
            $stmt_items = $conn->prepare("
                SELECT product_id, qty FROM order_details WHERE order_id = ?
            ");
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $items = $stmt_items->get_result();
            
            while($item = $items->fetch_assoc()){
                $stmt_stock = $conn->prepare("
                    UPDATE products SET stok = stok + ? WHERE id = ?
                ");
                $stmt_stock->bind_param("ii", $item['qty'], $item['product_id']);
                $stmt_stock->execute();
            }
            
            $success = "Pesanan berhasil dibatalkan.";
        } else {
            $error = "Gagal membatalkan pesanan.";
        }
    } else {
        $error = "Pesanan tidak dapat dibatalkan. Status pesanan sudah diproses.";
    }
}

// Ambil data pesanan
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
    <title>Riwayat Pesanan - AIC Fashion Metro</title>
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
            max-width: 1000px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h2 {
            font-size: 28px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h2 i {
            color: #EE6C4D;
        }

        .header p {
            color: #777;
            margin-top: 8px;
            font-size: 14px;
        }

        /* ALERT */
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* FILTER TABS */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .filter-btn {
            background: none;
            border: none;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 500;
            color: #777;
            cursor: pointer;
            border-radius: 30px;
            transition: 0.2s;
        }

        .filter-btn:hover {
            color: #EE6C4D;
        }

        .filter-btn.active {
            background: #EE6C4D;
            color: white;
        }

        /* ORDER CARD */
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
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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

        .order-info-left {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
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
            margin-right: 4px;
        }

        /* STATUS BADGE */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending { background: #fff3e0; color: #ff9800; }
        .status-menunggu { background: #fff3e0; color: #ff9800; }
        .status-proses { background: #e3f2fd; color: #2196f3; }
        .status-dikemas { background: #f3e5f5; color: #9c27b0; }
        .status-dikirim { background: #e8f5e9; color: #4caf50; }
        .status-selesai { background: #e8f5e9; color: #2ecc71; }
        .status-batal { background: #ffebee; color: #f44336; }

        /* ORDER BODY */
        .order-body {
            padding: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .order-products {
            flex: 1;
        }

        .product-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #f0f0f0;
        }

        .product-mini:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .product-mini img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
            background: #f5f5f5;
        }

        .product-mini-info {
            flex: 1;
        }

        .product-mini-name {
            font-size: 13px;
            font-weight: 500;
            color: #333;
        }

        .product-mini-meta {
            font-size: 11px;
            color: #999;
        }

        .product-mini-price {
            font-size: 12px;
            font-weight: 600;
            color: #EE6C4D;
        }

        .order-summary {
            min-width: 180px;
            text-align: right;
            border-left: 1px solid #eee;
            padding-left: 20px;
        }

        .total-amount {
            font-size: 18px;
            font-weight: 700;
            color: #EE6C4D;
            margin-bottom: 5px;
        }

        .total-label {
            font-size: 11px;
            color: #999;
        }

        .item-count {
            font-size: 12px;
            color: #777;
            margin-top: 8px;
        }

        /* ORDER FOOTER */
        .order-footer {
            background: #fafafa;
            padding: 12px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-detail {
            background: #EE6C4D;
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-detail:hover {
            background: #d95a3c;
            transform: translateY(-2px);
        }

        .btn-review {
            background: transparent;
            border: 1px solid #EE6C4D;
            color: #EE6C4D;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-review:hover {
            background: #EE6C4D;
            color: white;
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid #f44336;
            color: #f44336;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-cancel:hover {
            background: #f44336;
            color: white;
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

        /* MODAL KONFIRMASI */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 400px;
            border-radius: 24px;
            padding: 25px;
            text-align: center;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content i {
            font-size: 60px;
            color: #ff9800;
            margin-bottom: 15px;
        }

        .modal-content h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .modal-content p {
            color: #777;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .modal-buttons a,
        .modal-buttons button {
            padding: 10px 24px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }

        .btn-yes {
            background: #f44336;
            color: white;
        }

        .btn-yes:hover {
            background: #d32f2f;
        }

        .btn-no {
            background: #e0e0e0;
            color: #666;
        }

        .btn-no:hover {
            background: #ccc;
        }

        /* RESPONSIVE */
        @media (max-width: 700px) {
            .container {
                margin-top: 70px;
                padding: 0 15px;
            }
            .order-body {
                flex-direction: column;
            }
            .order-summary {
                text-align: left;
                border-left: none;
                padding-left: 0;
                border-top: 1px solid #eee;
                padding-top: 15px;
                margin-top: 5px;
            }
            .order-header {
                flex-direction: column;
                text-align: center;
            }
            .order-footer {
                flex-direction: column;
            }
            .btn-detail, .btn-review, .btn-cancel {
                text-align: center;
                justify-content: center;
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
    <div class="header">
        <h2>
            <i class="fa-solid fa-clock-rotate-left"></i> 
            Riwayat Pesanan
        </h2>
        <p>Lihat semua pesanan yang pernah Anda buat</p>
    </div>

    <!-- ALERT MESSAGE -->
    <?php if(isset($success)){ ?>
        <div class="alert-success">
            <i class="fa-regular fa-circle-check"></i>
            <?php echo $success; ?>
        </div>
    <?php } ?>
    
    <?php if(isset($error)){ ?>
        <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <!-- FILTER TABS -->
    <div class="filter-tabs">
        <button class="filter-btn active" data-filter="all">Semua</button>
        <button class="filter-btn" data-filter="pending">Menunggu</button>
        <button class="filter-btn" data-filter="proses">Diproses</button>
        <button class="filter-btn" data-filter="dikirim">Dikirim</button>
        <button class="filter-btn" data-filter="selesai">Selesai</button>
        <button class="filter-btn" data-filter="batal">Dibatalkan</button>
    </div>

    <div id="ordersContainer">
        <?php if($data->num_rows == 0){ ?>
            <div class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Yuk mulai belanja dan dapatkan produk favoritmu!</p>
                <a href="dashboard.php" class="shop-link">
                    <i class="fa-solid fa-shop"></i> Belanja Sekarang
                </a>
            </div>
        <?php } ?>

        <?php while($order = $data->fetch_assoc()){ 
            $order_id = $order['id'];
            $status = strtolower($order['status'] ?? 'pending');
            
            // Ambil produk dalam pesanan
            $stmt_produk = $conn->prepare("
                SELECT p.gambar, p.nama_produk, od.qty, od.harga, od.size
                FROM order_details od
                JOIN products p ON od.product_id = p.id
                WHERE od.order_id = ?
                LIMIT 2
            ");
            $stmt_produk->bind_param("i", $order_id);
            $stmt_produk->execute();
            $produk_list = $stmt_produk->get_result();
            
            // Hitung total item
            $stmt_count = $conn->prepare("
                SELECT COUNT(*) as total_item, SUM(qty) as total_qty 
                FROM order_details 
                WHERE order_id = ?
            ");
            $stmt_count->bind_param("i", $order_id);
            $stmt_count->execute();
            $item_count = $stmt_count->get_result()->fetch_assoc();
            
            $total_item = $item_count['total_item'] ?? 0;
            $total_qty = $item_count['total_qty'] ?? 0;
            
            $tanggal = date('d M Y, H:i', strtotime($order['created_at'] ?? $order['tanggal'] ?? 'now'));
            
            // Mapping status untuk CSS
            $status_class = $status;
            if($status == 'menunggu') $status_class = 'pending';
            if($status == 'dikemas') $status_class = 'dikemas';
            
            $status_label = ucfirst($status);
            if($status == 'pending') $status_label = 'Menunggu';
            
            // Cek apakah bisa dibatalkan (hanya status pending/menunggu)
            $can_cancel = ($status == 'pending' || $status == 'menunggu');
        ?>
            <div class="order-card" data-status="<?php echo $status; ?>">
                <div class="order-header">
                    <div class="order-info-left">
                        <div class="order-id">
                            <i class="fa-regular fa-hashtag"></i> ORDER #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                        </div>
                        <div class="order-date">
                            <i class="fa-regular fa-calendar"></i> <?php echo $tanggal; ?>
                        </div>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo $status_class; ?>">
                            <i class="fa-solid <?php 
                                echo $status == 'pending' ? 'fa-clock' : 
                                    ($status == 'proses' ? 'fa-spinner' : 
                                    ($status == 'dikirim' ? 'fa-truck' : 
                                    ($status == 'selesai' ? 'fa-circle-check' : 
                                    ($status == 'batal' ? 'fa-times-circle' : 'fa-clock')))); 
                            ?>"></i>
                            <?php echo $status_label; ?>
                        </span>
                    </div>
                </div>
                
                <div class="order-body">
                    <div class="order-products">
                        <?php 
                        $product_count = 0;
                        while($produk = $produk_list->fetch_assoc()){ 
                            $product_count++;
                        ?>
                            <div class="product-mini">
                                <img src="../uploads/<?php echo htmlspecialchars($produk['gambar']); ?>" 
                                     alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>"
                                     onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                                <div class="product-mini-info">
                                    <div class="product-mini-name">
                                        <?php echo htmlspecialchars($produk['nama_produk']); ?>
                                    </div>
                                    <div class="product-mini-meta">
                                        Size: <?php echo htmlspecialchars($produk['size'] ?? 'Free'); ?> | 
                                        Qty: <?php echo $produk['qty']; ?>
                                    </div>
                                </div>
                                <div class="product-mini-price">
                                    Rp <?php echo number_format($produk['harga'] * $produk['qty'], 0, ',', '.'); ?>
                                </div>
                            </div>
                        <?php } ?>
                        
                        <?php if($total_item > 2){ ?>
                            <div style="font-size: 12px; color: #999; margin-top: 8px;">
                                dan <?php echo ($total_item - 2); ?> produk lainnya
                            </div>
                        <?php } ?>
                    </div>
                    
                    <div class="order-summary">
                        <div class="total-amount">
                            Rp <?php echo number_format($order['total_harga'] ?? 0, 0, ',', '.'); ?>
                        </div>
                        <div class="total-label">Total Pesanan</div>
                        <div class="item-count">
                            <?php echo $total_qty; ?> produk
                        </div>
                    </div>
                </div>
                
                <div class="order-footer">
                    <?php if($status == 'selesai'){ ?>
                        <a href="review.php?id=<?php echo $order_id; ?>" class="btn-review">
                            <i class="fa-regular fa-star"></i> Beri Rating
                        </a>
                    <?php } ?>
                    
                    <?php if($can_cancel){ ?>
                        <button class="btn-cancel" onclick="confirmCancel(<?php echo $order_id; ?>)">
                            <i class="fa-solid fa-ban"></i> Batalkan Pesanan
                        </button>
                    <?php } ?>
                    
                    <a href="detail_pesanan.php?id=<?php echo $order_id; ?>" class="btn-detail">
                        <i class="fa-regular fa-eye"></i> Lihat Detail
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<!-- MODAL KONFIRMASI BATAL -->
<div id="cancelModal" class="modal">
    <div class="modal-content">
        <i class="fa-solid fa-circle-exclamation"></i>
        <h3>Batalkan Pesanan?</h3>
        <p>Apakah Anda yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="modal-buttons">
            <button class="btn-no" onclick="closeModal()">Tidak, Kembali</button>
            <a href="#" id="confirmCancelBtn" class="btn-yes">Ya, Batalkan</a>
        </div>
    </div>
</div>

<script>
// Filter pesanan berdasarkan status
const filterBtns = document.querySelectorAll('.filter-btn');
const orderCards = document.querySelectorAll('.order-card');

filterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        filterBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filterValue = this.getAttribute('data-filter');
        
        orderCards.forEach(card => {
            if(filterValue === 'all') {
                card.style.display = '';
            } else {
                const cardStatus = card.getAttribute('data-status');
                if(cardStatus === filterValue) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            }
        });
    });
});

// Modal cancel
const modal = document.getElementById('cancelModal');
let currentOrderId = null;

function confirmCancel(orderId) {
    currentOrderId = orderId;
    const confirmBtn = document.getElementById('confirmCancelBtn');
    confirmBtn.href = `?batal=1&id=${orderId}`;
    modal.classList.add('show');
}

function closeModal() {
    modal.classList.remove('show');
    currentOrderId = null;
}

// Klik di luar modal untuk menutup
window.onclick = function(event) {
    if (event.target === modal) {
        closeModal();
    }
}
</script>

</body>
</html>