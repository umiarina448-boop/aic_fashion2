<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id == 0){
    header("Location: index.php");
    exit;
}

// Gunakan prepared statement untuk keamanan
$stmt_produk = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt_produk->bind_param("i", $id);
$stmt_produk->execute();
$produk_result = $stmt_produk->get_result();
$produk = $produk_result->fetch_assoc();

if(!$produk){
    die("❌ Produk tidak ditemukan");
}

// Ambil size dari tabel product_sizes atau default size
$stmt_sizes = $conn->prepare("SELECT * FROM product_sizes WHERE product_id = ?");
$stmt_sizes->bind_param("i", $id);
$stmt_sizes->execute();
$sizes = $stmt_sizes->get_result();

// Jika tidak ada size di tabel, berikan default
$has_sizes = $sizes->num_rows > 0;

// Ambil review produk
$stmt_review = $conn->prepare("
    SELECT r.*, u.nama 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt_review->bind_param("i", $id);
$stmt_review->execute();
$reviews = $stmt_review->get_result();

// Hitung rata-rata rating
$stmt_avg = $conn->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_review 
    FROM reviews 
    WHERE product_id = ?
");
$stmt_avg->bind_param("i", $id);
$stmt_avg->execute();
$rating_data = $stmt_avg->get_result()->fetch_assoc();
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_review = $rating_data['total_review'] ?? 0;

/* ADD TO CART */
if(isset($_POST['add_cart'])){
    $size = $_POST['size'] ?? 'Free';
    $qty = (int)$_POST['qty'];
    
    if($qty < 1) $qty = 1;
    
    // Cek apakah sudah ada di cart
    $stmt_check = $conn->prepare("
        SELECT id, qty FROM cart 
        WHERE user_id = ? AND product_id = ? AND size = ?
    ");
    $stmt_check->bind_param("iis", $user_id, $id, $size);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();
    
    if($check_result->num_rows > 0){
        // Update qty jika sudah ada
        $existing = $check_result->fetch_assoc();
        $new_qty = $existing['qty'] + $qty;
        $stmt_update = $conn->prepare("
            UPDATE cart SET qty = ? WHERE id = ?
        ");
        $stmt_update->bind_param("ii", $new_qty, $existing['id']);
        $stmt_update->execute();
    } else {
        // Insert baru
        $stmt_insert = $conn->prepare("
            INSERT INTO cart (user_id, product_id, size, qty) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt_insert->bind_param("iisi", $user_id, $id, $size, $qty);
        $stmt_insert->execute();
    }
    
    header("Location: cart.php");
    exit;
}

/* BUY NOW */
if(isset($_POST['buy_now'])){
    $size = $_POST['size'] ?? 'Free';
    $qty = (int)$_POST['qty'];
    
    if($qty < 1) $qty = 1;
    
    $_SESSION['buy_now'] = [
        'product_id' => $id,
        'size' => $size,
        'qty' => $qty
    ];
    
    header("Location: checkout.php?mode=buy_now");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produk['nama_produk']); ?> - AIC Fashion Metro</title>
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
            max-width: 1100px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        .product-wrapper {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            gap: 30px;
            padding: 30px;
            flex-wrap: wrap;
        }

        /* PRODUCT IMAGE */
        .product-image {
            flex: 1;
            min-width: 280px;
        }

        .product-image img {
            width: 100%;
            border-radius: 20px;
            object-fit: cover;
            background: #f5f5f5;
        }

        /* PRODUCT INFO */
        .product-info {
            flex: 1;
            min-width: 280px;
        }

        .product-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 28px;
            font-weight: 700;
            color: #EE6C4D;
            margin-bottom: 15px;
        }

        /* RATING */
        .rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stars {
            color: #ffc107;
            font-size: 14px;
        }

        .rating-count {
            color: #777;
            font-size: 13px;
        }

        /* SIZE SELECTION */
        .section-label {
            font-weight: 600;
            margin: 15px 0 10px;
            display: block;
            color: #333;
        }

        .size-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 5px;
        }

        .size-group input {
            display: none;
        }

        .size-box {
            display: inline-block;
            padding: 10px 20px;
            border: 1.5px solid #e0e0e0;
            border-radius: 40px;
            cursor: pointer;
            background: white;
            font-weight: 500;
            font-size: 14px;
            transition: 0.2s;
        }

        .size-group input:checked + .size-box {
            background: #EE6C4D;
            color: white;
            border-color: #EE6C4D;
        }

        .size-box:hover {
            border-color: #EE6C4D;
        }

        /* QUANTITY */
        .qty-box {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 10px 0 20px;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border: 1.5px solid #e0e0e0;
            background: white;
            font-size: 20px;
            cursor: pointer;
            border-radius: 12px;
            transition: 0.2s;
        }

        .qty-btn:hover {
            background: #EE6C4D;
            color: white;
            border-color: #EE6C4D;
        }

        .qty-input {
            width: 70px;
            height: 40px;
            text-align: center;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
        }

        /* BUTTONS */
        .btn-cart {
            width: 100%;
            padding: 14px;
            background: #EE6C4D;
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-cart:hover {
            background: #d95a3c;
            transform: translateY(-2px);
        }

        .btn-buy {
            width: 100%;
            padding: 14px;
            background: #222;
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-buy:hover {
            background: #000;
            transform: translateY(-2px);
        }

        /* TABS */
        .tabs {
            display: flex;
            gap: 5px;
            margin: 25px 0 15px;
            border-bottom: 1px solid #eee;
        }

        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #777;
            transition: 0.2s;
        }

        .tab-btn.active {
            color: #EE6C4D;
            border-bottom: 2px solid #EE6C4D;
        }

        .tab-content {
            padding: 15px 0;
            font-size: 14px;
            line-height: 1.8;
            color: #555;
        }

        /* REVIEW ITEM */
        .review-item {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .reviewer-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .review-date {
            font-size: 11px;
            color: #999;
        }

        .review-stars {
            color: #ffc107;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .review-text {
            font-size: 13px;
            color: #666;
        }

        .no-review {
            text-align: center;
            padding: 30px;
            color: #999;
        }

        /* STOCK INFO */
        .stock-info {
            margin: 10px 0;
            font-size: 13px;
        }

        .in-stock {
            color: #2ecc71;
        }

        .low-stock {
            color: #ff9800;
        }

        /* RESPONSIVE */
        @media (max-width: 700px) {
            .container {
                margin-top: 70px;
            }
            .product-wrapper {
                padding: 20px;
            }
            .product-title {
                font-size: 22px;
            }
            .product-price {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<a href="javascript:history.back()" class="back-btn">
    <i class="fa-solid fa-chevron-left"></i>
</a>

<div class="container">
    <div class="product-wrapper">
        <!-- PRODUCT IMAGE -->
        <div class="product-image">
            <img src="../uploads/<?php echo htmlspecialchars($produk['gambar']); ?>" 
                 alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>"
                 onerror="this.src='https://placehold.co/600x600?text=No+Image'">
        </div>

        <!-- PRODUCT INFO -->
        <div class="product-info">
            <h1 class="product-title"><?php echo htmlspecialchars($produk['nama_produk']); ?></h1>
            
            <div class="product-price">
                Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?>
            </div>

            <!-- RATING -->
            <div class="rating">
                <div class="stars">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <?php if($i <= $avg_rating): ?>
                            <i class="fa-solid fa-star"></i>
                        <?php elseif($i - 0.5 <= $avg_rating): ?>
                            <i class="fa-solid fa-star-half-alt"></i>
                        <?php else: ?>
                            <i class="fa-regular fa-star"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <span class="rating-count"><?php echo $avg_rating; ?> (<?php echo $total_review; ?> ulasan)</span>
            </div>

            <!-- STOCK INFO -->
            <?php 
            $stok = $produk['stok'] ?? 0;
            $stock_class = $stok > 10 ? 'in-stock' : ($stok > 0 ? 'low-stock' : 'out-of-stock');
            $stock_text = $stok > 10 ? 'Tersedia' : ($stok > 0 ? 'Stok terbatas ('.$stok.')' : 'Habis');
            ?>
            <div class="stock-info <?php echo $stock_class; ?>">
                <i class="fa-solid fa-box"></i> <?php echo $stock_text; ?>
            </div>

            <form method="POST">
                <!-- SIZE SELECTION -->
                <label class="section-label"><i class="fa-solid fa-ruler"></i> Pilih Ukuran</label>
                <div class="size-group">
                    <?php if($has_sizes): ?>
                        <?php while($s = $sizes->fetch_assoc()): 
                            $id_size = "size_" . $s['ukuran'];
                        ?>
                            <input type="radio" name="size" id="<?php echo $id_size; ?>" 
                                   value="<?php echo htmlspecialchars($s['ukuran']); ?>" required>
                            <label for="<?php echo $id_size; ?>" class="size-box">
                                <?php echo htmlspecialchars($s['ukuran']); ?>
                            </label>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <input type="radio" name="size" id="size_free" value="Free" checked required>
                        <label for="size_free" class="size-box">Free Size</label>
                    <?php endif; ?>
                </div>

                <!-- QUANTITY -->
                <label class="section-label"><i class="fa-solid fa-calculator"></i> Jumlah</label>
                <div class="qty-box">
                    <button type="button" class="qty-btn" onclick="decrementQty()">-</button>
                    <input type="number" name="qty" id="qty" value="1" min="1" max="<?php echo $stok > 0 ? $stok : 10; ?>" class="qty-input">
                    <button type="button" class="qty-btn" onclick="incrementQty()">+</button>
                </div>

                <!-- BUTTONS -->
                <button type="submit" name="add_cart" class="btn-cart" <?php echo $stok <= 0 ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-cart-plus"></i> Tambah ke Keranjang
                </button>

                <button type="submit" name="buy_now" class="btn-buy" <?php echo $stok <= 0 ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-bolt"></i> Beli Sekarang
                </button>
            </form>
        </div>
    </div>

    <!-- TABS SECTION -->
    <div class="product-wrapper" style="margin-top: 20px; padding: 20px;">
        <div style="width: 100%;">
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab('desc')">Deskripsi</button>
                <button class="tab-btn" onclick="openTab('info')">Informasi</button>
                <button class="tab-btn" onclick="openTab('review')">Ulasan (<?php echo $total_review; ?>)</button>
            </div>

            <div id="desc" class="tab-content">
                <?php echo !empty($produk['deskripsi']) ? nl2br(htmlspecialchars($produk['deskripsi'])) : "Belum ada deskripsi untuk produk ini."; ?>
            </div>

            <div id="info" class="tab-content" style="display: none;">
                <ul style="list-style: none; padding-left: 0;">
                    <li><i class="fa-regular fa-circle-check" style="color: #2ecc71;"></i> Produk 100% Original</li>
                    <li><i class="fa-regular fa-circle-check" style="color: #2ecc71;"></i> Bahan berkualitas premium</li>
                    <li><i class="fa-regular fa-circle-check" style="color: #2ecc71;"></i> Jahitan rapi dan nyaman</li>
                    <li><i class="fa-regular fa-circle-check" style="color: #2ecc71;"></i> Tersedia berbagai ukuran</li>
                    <li><i class="fa-regular fa-circle-check" style="color: #2ecc71;"></i> Garansi 7 hari</li>
                </ul>
            </div>

            <div id="review" class="tab-content" style="display: none;">
                <?php if($reviews->num_rows > 0): ?>
                    <?php while($review = $reviews->fetch_assoc()): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="reviewer-name">
                                    <i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($review['nama']); ?>
                                </span>
                                <span class="review-date">
                                    <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                                </span>
                            </div>
                            <div class="review-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $review['rating']): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="review-text">
                                <?php echo htmlspecialchars($review['review']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-review">
                        <i class="fa-regular fa-comment"></i> Belum ada ulasan untuk produk ini.<br>
                        <small>Jadilah yang pertama memberikan ulasan!</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Quantity handlers
const qtyInput = document.getElementById('qty');
const maxStock = <?php echo $stok > 0 ? $stok : 999; ?>;

function incrementQty() {
    let current = parseInt(qtyInput.value);
    if (current < maxStock) {
        qtyInput.value = current + 1;
    }
}

function decrementQty() {
    let current = parseInt(qtyInput.value);
    if (current > 1) {
        qtyInput.value = current - 1;
    }
}

// Tab handlers
function openTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Show selected tab
    document.getElementById(tabName).style.display = 'block';
    
    // Update active button
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Find and activate the clicked button
    event.target.classList.add('active');
}

// Prevent form submit on enter in qty input
qtyInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
    }
});
</script>

</body>
</html>