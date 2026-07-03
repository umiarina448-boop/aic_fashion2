<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// URL service admin (tempat gambar produk disimpan)
$admin_url = "https://admin-panel-production-9a90.up.railway.app";

/* USER */
$user = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM users WHERE id='$user_id'
"));

/* SEARCH */
$keyword = isset($_GET['q']) ? $_GET['q'] : '';

if($keyword != ""){
    $produk = mysqli_query($conn,"
        SELECT * FROM products
        WHERE nama_produk LIKE '%$keyword%'
        ORDER BY id DESC
    ");
} else {
    $produk = mysqli_query($conn,"
        SELECT * FROM products
        ORDER BY id DESC
    ");
}

/* COUNT CART */
$cart_result = mysqli_query($conn,"
SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'
");
$cart_count = mysqli_fetch_assoc($cart_result)['total'] ?? 0;

/* COUNT ORDER */
$order_result = mysqli_query($conn,"
SELECT COUNT(*) as total FROM orders WHERE user_id='$user_id'
");
$order_count = mysqli_fetch_assoc($order_result)['total'] ?? 0;

// Ambil nama user untuk sapaan
$user_name = $user['nama'] ?? 'Pelanggan';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - AIC Fashion Metro</title>
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

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #EE6C4D;
            padding: 12px 30px;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1000;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .logo {
            font-weight: 700;
            font-size: 20px;
            letter-spacing: -0.5px;
        }

        .logo i {
            margin-right: 8px;
        }

        .search form {
            display: flex;
        }

        .search input {
            width: 320px;
            padding: 10px 16px;
            border: none;
            border-radius: 30px;
            outline: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .search input:focus {
            box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .menu a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 30px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .menu a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .badge {
            background: white;
            color: #EE6C4D;
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 4px;
        }

        .welcome {
            background: linear-gradient(135deg, #EE6C4D 0%, #ff8a65 100%);
            margin: 20px 30px;
            padding: 25px 30px;
            border-radius: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .welcome h2 {
            font-size: 22px;
            font-weight: 600;
        }

        .welcome p {
            opacity: 0.9;
            font-size: 14px;
            margin-top: 5px;
        }

        .welcome-stats {
            display: flex;
            gap: 25px;
        }

        .stat {
            text-align: center;
            background: rgba(255,255,255,0.2);
            padding: 8px 18px;
            border-radius: 40px;
        }

        .stat-number {
            font-size: 22px;
            font-weight: 700;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }

        .section-title {
            margin: 20px 30px 15px;
            font-size: 22px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #EE6C4D;
            font-size: 24px;
        }

        .container {
            padding: 0 30px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
        }

        .card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
        }

        .card-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: 0.3s;
        }

        .card:hover .card-img {
            transform: scale(1.02);
        }

        .card-content {
            padding: 15px;
        }

        .card-title {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .price {
            color: #EE6C4D;
            font-weight: 700;
            font-size: 18px;
            margin: 8px 0;
        }

        .btn-detail {
            display: block;
            text-align: center;
            margin-top: 12px;
            padding: 10px;
            background: #f0f0f0;
            color: #EE6C4D;
            text-decoration: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-detail:hover {
            background: #EE6C4D;
            color: white;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            margin: 20px 30px;
        }

        .empty-state i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #999;
            font-size: 16px;
        }

        @media (max-width: 800px) {
            .navbar {
                flex-direction: column;
                padding: 15px;
            }
            .search input {
                width: 100%;
            }
            .menu {
                justify-content: center;
            }
            .welcome {
                flex-direction: column;
                text-align: center;
                margin: 15px;
                padding: 20px;
            }
            .container {
                padding: 0 15px;
                gap: 15px;
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            }
            .section-title {
                margin: 15px 15px 10px;
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            .menu a {
                padding: 6px 10px;
                font-size: 12px;
            }
            .card-title {
                font-size: 13px;
            }
            .price {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">
        <i class="fa-solid fa-bag-shopping"></i> AIC Fashion Metro
    </div>

    <div class="search">
        <form method="GET">
            <input type="text" name="q" placeholder="🔍 Cari produk, hijab, gamis..." value="<?php echo htmlspecialchars($keyword); ?>">
        </form>
    </div>

    <div class="menu">
        <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <?php if($cart_count > 0){ ?>
                <span class="badge"><?php echo $cart_count; ?></span>
            <?php } ?>
        </a>
        <a href="orders.php">
            <i class="fa-solid fa-box"></i> Pesanan
            <?php if($order_count > 0){ ?>
                <span class="badge"><?php echo $order_count; ?></span>
            <?php } ?>
        </a>
        <a href="riwayat.php">
            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat
        </a>
        <a href="profil.php">
            <i class="fa-solid fa-user"></i> Profil
        </a>
        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>

<div class="welcome">
    <div>
        <h2><i class="fa-regular fa-hand-peace"></i> Halo, <?php echo htmlspecialchars($user_name); ?>!</h2>
        <p>Selamat berbelanja di AIC Fashion. Temukan koleksi terbaru untuk tampil cantik setiap hari.</p>
    </div>
    <div class="welcome-stats">
        <div class="stat">
            <div class="stat-number"><?php echo $cart_count; ?></div>
            <div class="stat-label">Di Keranjang</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $order_count; ?></div>
            <div class="stat-label">Pesanan</div>
        </div>
    </div>
</div>

<div class="section-title">
    <i class="fa-solid fa-fire-flame-curved"></i>
    <span><?php echo $keyword ? "Hasil pencarian: \"" . htmlspecialchars($keyword) . "\"" : "Produk Terbaru"; ?></span>
</div>

<div class="container">
    <?php if(mysqli_num_rows($produk) == 0){ ?>
        <div class="empty-state">
            <i class="fa-solid fa-box-open"></i>
            <p>😔 Produk tidak ditemukan</p>
            <p style="font-size: 14px; margin-top: 8px;">Coba kata kunci lain atau lihat koleksi lainnya.</p>
        </div>
    <?php } ?>

    <?php while($row = mysqli_fetch_assoc($produk)){ ?>
        <div class="card">
            <img class="card-img" src="<?php echo $admin_url; ?>/uploads/<?php echo htmlspecialchars($row['gambar']); ?>"
                 alt="<?php echo htmlspecialchars($row['nama_produk']); ?>"
                 onerror="this.src='https://placehold.co/400x400?text=No+Image'">
            <div class="card-content">
                <div class="card-title"><?php echo htmlspecialchars($row['nama_produk']); ?></div>
                <div class="price">
                    Rp <?php echo number_format($row['harga'],0,',','.'); ?>
                </div>
                <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn-detail">
                    <i class="fa-regular fa-eye"></i> Lihat Detail
                </a>
            </div>
        </div>
    <?php } ?>
</div>

</body>
</html>
