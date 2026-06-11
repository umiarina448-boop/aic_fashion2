<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Gunakan prepared statement untuk keamanan
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

if(!$user){
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';

/* Proses update profil */
if(isset($_POST['update_profile'])){
    $nama = trim($_POST['nama']);
    $no_hp = trim($_POST['no_hp']);
    $alamat = trim($_POST['alamat']);
    
    // Validasi
    if(empty($nama)){
        $error = "Nama tidak boleh kosong";
    } elseif(empty($no_hp)){
        $error = "Nomor HP tidak boleh kosong";
    } elseif(empty($alamat)){
        $error = "Alamat tidak boleh kosong";
    } elseif(!preg_match('/^[0-9+\-\s]+$/', $no_hp)){
        $error = "Format nomor HP tidak valid";
    } else {
        $stmt_update = $conn->prepare("
            UPDATE users SET 
                nama = ?,
                no_hp = ?,
                alamat = ?
            WHERE id = ?
        ");
        $stmt_update->bind_param("sssi", $nama, $no_hp, $alamat, $user_id);
        
        if($stmt_update->execute()){
            $_SESSION['nama'] = $nama; // Update session
            $message = "Profil berhasil diperbarui!";
            // Refresh data user
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Gagal memperbarui profil: " . $conn->error;
        }
    }
}

/* Proses ganti password */
if(isset($_POST['change_password'])){
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($current_password) || empty($new_password) || empty($confirm_password)){
        $error = "Semua field password harus diisi";
    } elseif($new_password !== $confirm_password){
        $error = "Password baru dan konfirmasi tidak cocok";
    } elseif(strlen($new_password) < 6){
        $error = "Password minimal 6 karakter";
    } else {
        // Verifikasi password lama
        if(password_verify($current_password, $user['password'])){
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_pass->bind_param("si", $hashed_password, $user_id);
            
            if($stmt_pass->execute()){
                $message = "Password berhasil diubah!";
            } else {
                $error = "Gagal mengubah password";
            }
        } else {
            $error = "Password saat ini salah";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - AIC Fashion Metro</title>
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
            max-width: 800px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        /* HEADER */
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #EE6C4D, #ff8a65);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 10px 25px rgba(238,108,77,0.3);
        }

        .avatar span {
            font-size: 40px;
            font-weight: 700;
            color: white;
        }

        .profile-header h2 {
            font-size: 24px;
            color: #333;
        }

        .profile-header p {
            color: #777;
            font-size: 14px;
        }

        /* CARD */
        .card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
            margin-bottom: 25px;
        }

        .card-header {
            background: #f8f9fa;
            padding: 18px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header i {
            font-size: 22px;
            color: #EE6C4D;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .card-body {
            padding: 25px;
        }

        /* FORM */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 6px;
            color: #EE6C4D;
            width: 20px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #EE6C4D;
            box-shadow: 0 0 0 3px rgba(238,108,77,0.1);
        }

        .form-group textarea {
            resize: vertical;
        }

        /* INFO BOX */
        .info-box {
            background: #f0f7ff;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #0066cc;
        }

        .info-box i {
            font-size: 18px;
        }

        /* ALERT */
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        /* BUTTON */
        .btn-primary {
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

        .btn-primary:hover {
            background: #d95a3c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(238,108,77,0.3);
        }

        .btn-secondary {
            width: 100%;
            padding: 14px;
            background: transparent;
            border: 1.5px solid #EE6C4D;
            color: #EE6C4D;
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

        .btn-secondary:hover {
            background: #EE6C4D;
            color: white;
        }

        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #eee;
        }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 16px;
        }

        .stat-card i {
            font-size: 28px;
            color: #EE6C4D;
            margin-bottom: 8px;
        }

        .stat-card .number {
            font-size: 22px;
            font-weight: 700;
            color: #333;
        }

        .stat-card .label {
            font-size: 11px;
            color: #888;
        }

        /* RESPONSIVE */
        @media (max-width: 600px) {
            .container {
                margin-top: 70px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

<a href="javascript:history.back()" class="back-btn">
    <i class="fa-solid fa-chevron-left"></i>
</a>

<div class="container">
    <!-- PROFILE HEADER -->
    <div class="profile-header">
        <div class="avatar">
            <span><?php echo strtoupper(substr($user['nama'], 0, 1)); ?></span>
        </div>
        <h2><?php echo htmlspecialchars($user['nama']); ?></h2>
        <p><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
    </div>

    <!-- ALERT MESSAGES -->
    <?php if($message){ ?>
        <div class="alert-success">
            <i class="fa-regular fa-circle-check"></i>
            <?php echo $message; ?>
        </div>
    <?php } ?>
    
    <?php if($error){ ?>
        <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <!-- STATISTIK CARD (Opsional) -->
    <?php
    // Hitung jumlah pesanan
    $stmt_order = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
    $stmt_order->bind_param("i", $user_id);
    $stmt_order->execute();
    $order_count = $stmt_order->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Hitung jumlah produk di keranjang
    $stmt_cart = $conn->prepare("SELECT COUNT(*) as total FROM cart WHERE user_id = ?");
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $cart_count = $stmt_cart->get_result()->fetch_assoc()['total'] ?? 0;
    ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fa-solid fa-box"></i>
            <div class="number"><?php echo $order_count; ?></div>
            <div class="label">Total Pesanan</div>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-cart-shopping"></i>
            <div class="number"><?php echo $cart_count; ?></div>
            <div class="label">Di Keranjang</div>
        </div>
        <div class="stat-card">
            <i class="fa-regular fa-calendar"></i>
            <div class="number"><?php echo date('Y'); ?></div>
            <div class="label">Member Since</div>
        </div>
    </div>

    <!-- FORM EDIT PROFIL -->
    <div class="card">
        <div class="card-header">
            <i class="fa-regular fa-user"></i>
            <h3>Informasi Profil</h3>
        </div>
        <div class="card-body">
            <div class="info-box">
                <i class="fa-solid fa-circle-info"></i>
                Data ini akan digunakan untuk proses checkout dan pengiriman pesanan
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fa-regular fa-user"></i> Nama Lengkap</label>
                    <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fa-regular fa-envelope"></i> Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background:#f5f5f5">
                    <small style="color:#999; font-size:11px;">Email tidak dapat diubah</small>
                </div>
                
              <div class="form-group">
                 <label><i class="fa-solid fa-phone"></i> Nomor HP</label>
                <input type="tel" name="no_hp" value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>" placeholder="081234567890" required>
                </div>
                
                <div class="form-group">
                     <label><i class="fa-solid fa-location-dot"></i> Alamat Lengkap</label>
                     <textarea name="alamat" rows="4" placeholder="Jl. Contoh No. 123, RT/RW, Kelurahan, Kecamatan, Kota, Kode Pos" required><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="update_profile" class="btn-primary">
                    <i class="fa-regular fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- FORM GANTI PASSWORD -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-lock"></i>
            <h3>Ganti Password</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label><i class="fa-solid fa-key"></i> Password Saat Ini</label>
                    <input type="password" name="current_password" placeholder="Masukkan password lama" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fa-solid fa-lock"></i> Password Baru</label>
                    <input type="password" name="new_password" placeholder="Minimal 6 karakter" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fa-solid fa-check-circle"></i> Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" placeholder="Ulangi password baru" required>
                </div>
                
                <button type="submit" name="change_password" class="btn-secondary">
                    <i class="fa-solid fa-arrow-rotate-right"></i> Ganti Password
                </button>
            </form>
        </div>
    </div>

    <!-- TOMBOL LOGOUT -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="../logout.php" style="color: #f44336; text-decoration: none; font-size: 14px;">
            <i class="fa-solid fa-right-from-bracket"></i> Logout dari akun
        </a>
    </div>
</div>

</body>
</html>
