<?php

include "config/database.php";

$message = "";

if(isset($_POST['register'])){

    $nama = htmlspecialchars($_POST['nama']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];

    if($password != $konfirmasi){

        $message = "Password dan Konfirmasi Password tidak sama!";

    }else{

        $cek = mysqli_query($conn,"SELECT * FROM users WHERE email='$email'");

        if(mysqli_num_rows($cek) > 0){

            $message = "Email sudah terdaftar!";

        }else{

            $password_hash = password_hash($password,PASSWORD_DEFAULT);

            $query = mysqli_query($conn,"
                INSERT INTO users(nama,email,password,role)
                VALUES(
                    '$nama',
                    '$email',
                    '$password_hash',
                    'customer'
                )
            ");

            if($query){

                echo "
                <script>
                    alert('Registrasi Berhasil!');
                    window.location='login.php';
                </script>
                ";

            }else{

                $message = "Registrasi Gagal!";

            }

        }

    }

}

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Register - AIC Fashion Metro</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#FFF8F5;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

/* Tombol Kembali */

.back-btn{
    position:absolute;
    top:25px;
    left:25px;

    width:45px;
    height:45px;

    background:white;
    border-radius:50%;

    display:flex;
    justify-content:center;
    align-items:center;

    text-decoration:none;
    color:#EE6C4D;

    box-shadow:0 5px 15px rgba(0,0,0,.1);

    transition:0.3s;
}

.back-btn:hover{
    background:#EE6C4D;
    color:white;
}

/* Card */

.container{
    width:420px;
    background:white;
    padding:35px;
    border-radius:20px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
}

.logo{
    text-align:center;
    margin-bottom:25px;
}

.logo h1{
    color:#EE6C4D;
    margin-bottom:5px;
}

.logo p{
    color:#777;
}

/* Input */

.input-group{
    margin-bottom:15px;
}

.input-group label{
    display:block;
    margin-bottom:5px;
    font-size:14px;
}

.input-group input{
    width:100%;
    padding:12px;
    border:1px solid #ddd;
    border-radius:10px;
    outline:none;
}

.input-group input:focus{
    border-color:#EE6C4D;
}

/* Button */

.btn{
    width:100%;
    padding:12px;

    border:none;
    border-radius:10px;

    background:#EE6C4D;

    color:white;
    font-size:16px;

    cursor:pointer;
}

.btn:hover{
    background:#d95a3c;
}

/* Error */

.error{
    background:#ffe3e3;
    color:red;

    padding:10px;
    border-radius:10px;

    margin-bottom:15px;
}

/* Login Link */

.login-link{
    text-align:center;
    margin-top:15px;
}

.login-link a{
    color:#EE6C4D;
    text-decoration:none;
    font-weight:500;
}

</style>

</head>
<body>

<a href="index.php" class="back-btn">
    <i class="fa-solid fa-chevron-left"></i>
</a>

<div class="container">

    <div class="logo">
        <h1>🛍 AIC Fashion Metro</h1>
        <p>Daftar dan mulai belanja fashion favoritmu</p>
    </div>

    <?php if($message != ""){ ?>
        <div class="error">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <div class="input-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" required>
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="input-group">
            <label>Konfirmasi Password</label>
            <input type="password" name="konfirmasi" required>
        </div>

        <button type="submit" name="register" class="btn">
            Daftar Sekarang
        </button>

    </form>

    <div class="login-link">
        Sudah punya akun?
        <a href="login.php">Login</a>
    </div>

</div>

</body>
</html>
