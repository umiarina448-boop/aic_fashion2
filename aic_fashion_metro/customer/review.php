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

// Cek apakah pesanan milik user dan status selesai
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ? AND status = 'selesai'
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if(!$order){
    die("❌ Pesanan tidak ditemukan atau belum selesai");
}

// Ambil produk dari pesanan
$stmt_produk = $conn->prepare("
    SELECT od.*, p.nama_produk, p.gambar, p.id as product_id
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    WHERE od.order_id = ?
");
$stmt_produk->bind_param("i", $order_id);
$stmt_produk->execute();
$products = $stmt_produk->get_result();

// Cek apakah sudah pernah review
$already_reviewed = false;
$stmt_review = $conn->prepare("
    SELECT COUNT(*) as total FROM reviews 
    WHERE order_id = ? AND user_id = ?
");
$stmt_review->bind_param("ii", $order_id, $user_id);
$stmt_review->execute();
$review_check = $stmt_review->get_result()->fetch_assoc();
$already_reviewed = $review_check['total'] > 0;

// Proses submit review
if(isset($_POST['submit_review'])){
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    $product_id = (int)$_POST['product_id'];
    
    if($rating < 1 || $rating > 5){
        $error = "Rating harus antara 1-5 bintang";
    } elseif(empty($review_text)){
        $error = "Silakan tulis ulasan Anda";
    } else {
        // Simpan review
        $stmt_insert = $conn->prepare("
            INSERT INTO reviews (order_id, user_id, product_id, rating, review, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt_insert->bind_param("iiiis", $order_id, $user_id, $product_id, $rating, $review_text);
        
        if($stmt_insert->execute()){
            $success = "Terima kasih! Ulasan Anda telah disimpan.";
            // Redirect setelah 2 detik
            header("refresh:2; url=riwayat.php");
        } else {
            $error = "Gagal menyimpan ulasan. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Rating & Ulasan - AIC Fashion Metro</title>
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
            max-width: 700px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        /* CARD */
        .card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
        }

        .card-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }

        .card-header h2 {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: #EE6C4D;
        }

        .card-header p {
            color: #777;
            font-size: 13px;
            margin-top: 8px;
        }

        .card-body {
            padding: 25px;
        }

        /* PRODUCT INFO */
        .product-info {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 16px;
            margin-bottom: 25px;
        }

        .product-info img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
        }

        .product-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .product-details p {
            font-size: 12px;
            color: #888;
        }

        /* RATING STARS */
        .rating-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .rating-label {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .stars {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .star {
            font-size: 40px;
            cursor: pointer;
            color: #ddd;
            transition: 0.2s;
        }

        .star:hover,
        .star.active {
            color: #ffc107;
            transform: scale(1.1);
        }

        .rating-text {
            font-size: 13px;
            color: #888;
        }

        /* REVIEW TEXT */
        .review-section {
            margin-bottom: 25px;
        }

        .review-section label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .review-section textarea {
            width: 100%;
            padding: 15px;
            border: 1.5px solid #e0e0e0;
            border-radius: 16px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            resize: vertical;
            outline: none;
            transition: 0.2s;
        }

        .review-section textarea:focus {
            border-color: #EE6C4D;
        }

        /* ALREADY REVIEWED */
        .already-reviewed {
            text-align: center;
            padding: 40px 20px;
        }

        .already-reviewed i {
            font-size: 70px;
            color: #2ecc71;
            margin-bottom: 20px;
        }

        .already-reviewed h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .already-reviewed p {
            color: #777;
            margin-bottom: 20px;
        }

        /* BUTTON */
        .btn-submit {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #d95a3c;
            transform: translateY(-2px);
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

        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #eee;
        }

        @media (max-width: 600px) {
            .container {
                margin-top: 70px;
            }
            .star {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>

<a href="javascript:history.back()" class="back-btn">
    <i class="fa-solid fa-chevron-left"></i>
</a>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>
                <i class="fa-regular fa-star"></i> 
                Beri Rating & Ulasan
            </h2>
            <p>Bagikan pengalaman Anda menggunakan produk ini</p>
        </div>
        
        <div class="card-body">
            <?php if($already_reviewed){ ?>
                <div class="already-reviewed">
                    <i class="fa-regular fa-circle-check"></i>
                    <h3>Anda sudah memberikan ulasan</h3>
                    <p>Terima kasih atas feedback Anda! Ulasan Anda sangat membantu.</p>
                    <a href="riwayat.php" class="btn-submit" style="width: auto; padding: 10px 30px;">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Riwayat
                    </a>
                </div>
            <?php } else { 
                // Ambil produk pertama untuk direview (bisa dipilih)
                $first_product = $products->fetch_assoc();
                $products->data_seek(0); // Reset pointer
            ?>
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

                <form method="POST">
                    <!-- Pilih Produk (jika lebih dari 1) -->
                    <?php if($products->num_rows > 1){ ?>
                        <div class="review-section">
                            <label>Pilih Produk yang akan direview</label>
                            <select name="product_id" required style="width:100%; padding:12px; border-radius:12px; border:1.5px solid #e0e0e0;">
                                <?php while($prod = $products->fetch_assoc()){ ?>
                                    <option value="<?php echo $prod['product_id']; ?>">
                                        <?php echo htmlspecialchars($prod['nama_produk']); ?> (Size: <?php echo $prod['size']; ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    <?php } else { ?>
                        <input type="hidden" name="product_id" value="<?php echo $first_product['product_id']; ?>">
                        <div class="product-info">
                            <img src="../uploads/<?php echo htmlspecialchars($first_product['gambar']); ?>" 
                                 alt="<?php echo htmlspecialchars($first_product['nama_produk']); ?>"
                                 onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                            <div class="product-details">
                                <h4><?php echo htmlspecialchars($first_product['nama_produk']); ?></h4>
                                <p>Size: <?php echo htmlspecialchars($first_product['size'] ?? 'Free'); ?> | Qty: <?php echo $first_product['qty']; ?></p>
                            </div>
                        </div>
                    <?php } ?>
                    
                    <!-- Rating Bintang -->
                    <div class="rating-section">
                        <div class="rating-label">Berapa rating Anda untuk produk ini?</div>
                        <div class="stars">
                            <?php for($i = 1; $i <= 5; $i++){ ?>
                                <i class="fa-regular fa-star star" data-rating="<?php echo $i; ?>"></i>
                            <?php } ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" required>
                        <div class="rating-text" id="ratingText"></div>
                    </div>
                    
                    <!-- Ulasan Teks -->
                    <div class="review-section">
                        <label>Tulis Ulasan Anda</label>
                        <textarea name="review_text" rows="5" placeholder="Ceritakan pengalaman Anda menggunakan produk ini..."></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review" class="btn-submit">
                        <i class="fa-regular fa-paper-plane"></i> Kirim Ulasan
                    </button>
                </form>
            <?php } ?>
        </div>
    </div>
</div>

<script>
// Rating bintang logic
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('ratingValue');
const ratingText = document.getElementById('ratingText');

const ratingMessages = {
    1: '⭐ Sangat Tidak Puas',
    2: '⭐⭐ Tidak Puas',
    3: '⭐⭐⭐ Cukup Puas',
    4: '⭐⭐⭐⭐ Puas',
    5: '⭐⭐⭐⭐⭐ Sangat Puas'
};

stars.forEach(star => {
    star.addEventListener('click', function() {
        const rating = parseInt(this.dataset.rating);
        ratingInput.value = rating;
        
        // Update tampilan bintang
        stars.forEach((s, index) => {
            if(index < rating) {
                s.classList.remove('fa-regular');
                s.classList.add('fa-solid');
                s.classList.add('active');
            } else {
                s.classList.remove('fa-solid');
                s.classList.add('fa-regular');
                s.classList.remove('active');
            }
        });
        
        // Update teks rating
        ratingText.textContent = ratingMessages[rating] || '';
    });
});

// Hover effect
stars.forEach(star => {
    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.rating);
        stars.forEach((s, index) => {
            if(index < rating) {
                s.classList.remove('fa-regular');
                s.classList.add('fa-solid');
            } else {
                s.classList.remove('fa-solid');
                s.classList.add('fa-regular');
            }
        });
    });
    
    star.addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingInput.value) || 0;
        stars.forEach((s, index) => {
            if(index < currentRating) {
                s.classList.remove('fa-regular');
                s.classList.add('fa-solid');
            } else {
                s.classList.remove('fa-solid');
                s.classList.add('fa-regular');
            }
        });
    });
});
</script>

</body>
</html>