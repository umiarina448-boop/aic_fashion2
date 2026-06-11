<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>AIC Fashion Metro | Toko Fashion Wanita Modern Terpercaya</title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: #f5f5f5;
        }

        /* ========== NAVBAR ========== */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            background: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 8%;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 26px;
            font-weight: 800;
            color: #EE4D2D;
            cursor: pointer;
        }

        .logo i {
            font-size: 28px;
            color: #EE4D2D;
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .menu a {
            text-decoration: none;
            margin-left: 18px;
            color: #222;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.2s;
        }

        .menu a:hover {
            color: #EE4D2D;
        }

        .login-btn {
            border: 1px solid #EE4D2D;
            padding: 8px 20px;
            border-radius: 20px;
            color: #EE4D2D !important;
            background: transparent;
            font-weight: 600;
        }

        .login-btn:hover {
            background: #EE4D2D;
            color: white !important;
        }

        .register-btn {
            background: #EE4D2D;
            color: white !important;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(238, 77, 45, 0.2);
        }

        .register-btn:hover {
            background: #d73c1a;
            transform: translateY(-1px);
        }

        /* ========== HERO SECTION dengan gambar latar ========== */
        .hero {
            margin-top: 72px;
            min-height: 600px;
            height: 90vh;
            background: linear-gradient(135deg, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0.3) 100%),
                        url('https://images.unsplash.com/photo-1539109136881-3be0616acf4b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center 30%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
        }

        /* overlay pattern biar lebih menarik */
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 40%, rgba(238,77,45,0.15) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-content {
            max-width: 750px;
            color: white;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 58px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 15px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 18px;
            line-height: 1.7;
            margin-bottom: 30px;
            text-shadow: 0 1px 8px rgba(0,0,0,0.2);
        }

        .shop-btn {
            display: inline-block;
            background: #EE4D2D;
            color: white;
            text-decoration: none;
            padding: 14px 38px;
            border-radius: 40px;
            font-weight: 700;
            transition: 0.2s;
            box-shadow: 0 8px 18px rgba(238, 77, 45, 0.3);
        }

        .shop-btn:hover {
            background: #d73c1a;
            transform: scale(1.02);
        }

        /* ========== SECTION GAYA UMUM ========== */
        section {
            padding: 70px 8%;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
            font-size: 34px;
            font-weight: 700;
            color: #222;
            position: relative;
        }

        .section-title:after {
            content: '';
            display: block;
            width: 70px;
            height: 4px;
            background: #EE4D2D;
            margin: 12px auto 0;
            border-radius: 4px;
        }

        /* ========== KATEGORI GRID ========== */
        .kategori-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 28px;
        }

        .kategori-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.04);
            transition: all 0.25s ease;
            cursor: pointer;
            text-align: center;
        }

        .kategori-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
        }

        .kategori-card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .kategori-card:hover img {
            transform: scale(1.02);
        }

        .kategori-card h3 {
            padding: 18px 0 20px;
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e1e1e;
            background: white;
        }

        /* ========== SECTION ABU-ABU DENGAN GAMBAR DEKORATIF ========== */
        .fitur-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
            overflow: hidden;
        }

        /* Gambar dekoratif di background section abu-abu */
        .fitur-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1445205170230-053b83016050?ixlib=rb-4.0.3&auto=format&fit=crop&w=2071&q=80');
            background-size: cover;
            background-position: center;
            opacity: 0.08;
            pointer-events: none;
        }

        /* pattern dot tambahan */
        .fitur-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(#EE4D2D 1.5px, transparent 1.5px);
            background-size: 30px 30px;
            opacity: 0.08;
            pointer-events: none;
        }

        .fitur-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 30px;
            position: relative;
            z-index: 2;
        }

        .fitur-card {
            background: white;
            padding: 38px 20px;
            text-align: center;
            border-radius: 28px;
            box-shadow: 0 12px 22px rgba(0, 0, 0, 0.04);
            transition: all 0.2s;
            border: 1px solid #f0f0f0;
            backdrop-filter: blur(2px);
        }

        .fitur-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px rgba(238, 77, 45, 0.08);
            border-color: #ffe0d9;
        }

        .fitur-card i {
            font-size: 48px;
            color: #EE4D2D;
            margin-bottom: 20px;
        }

        .fitur-card h3 {
            font-size: 1.4rem;
            margin-bottom: 12px;
            font-weight: 600;
            color: #111;
        }

        .fitur-card p {
            color: #5a5a5a;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* ========== FOOTER ========== */
        footer {
            background: #ffffff;
            color: #2e2e2e;
            padding: 56px 8% 32px;
            border-top: 1px solid #ececec;
            margin-top: 20px;
        }

        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 45px;
            margin-bottom: 30px;
        }

        .footer-container h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 18px;
            color: #EE4D2D;
        }

        .footer-container p {
            line-height: 1.75;
            color: #5f5f5f;
            font-size: 0.9rem;
        }

        .social {
            display: flex;
            gap: 18px;
            margin-top: 12px;
        }

        .social i {
            font-size: 26px;
            color: #3a3a3a;
            transition: all 0.2s;
            cursor: pointer;
        }

        .social i:hover {
            color: #EE4D2D;
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 28px;
            border-top: 1px solid #e9e9e9;
            font-size: 0.85rem;
            color: #7e7e7e;
            margin-top: 20px;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 880px) {
            .navbar {
                padding: 12px 5%;
            }
            .menu a {
                margin-left: 12px;
                font-size: 0.85rem;
            }
            .login-btn, .register-btn {
                padding: 6px 16px;
            }
            .hero-content h1 {
                font-size: 40px;
            }
            .section-title {
                font-size: 28px;
            }
            .kategori-card img {
                height: 200px;
            }
        }

        @media (max-width: 720px) {
            .menu {
                display: none;
            }
            .hero {
                margin-top: 68px;
                height: 70vh;
                min-height: 500px;
            }
            .hero-content h1 {
                font-size: 32px;
            }
            .hero-content p {
                font-size: 16px;
            }
            .shop-btn {
                padding: 12px 28px;
            }
            section {
                padding: 50px 5%;
            }
            .fitur-card {
                padding: 28px 16px;
            }
        }

        .scroll-target {
            scroll-margin-top: 85px;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="logo" id="homeLogoBtn">
        <i class="fa-solid fa-bag-shopping"></i> 
        AIC Fashion Metro
    </div>
    <div class="menu">
        <a id="homeLink">Home</a>
        <a id="produkLink">Produk</a>
        <a id="tentangLink">Tentang</a>
        <a href="login.php" class="login-btn">Login</a>
        <a href="register.php" class="register-btn">Register</a>
    </div>
</nav>

<!-- HERO dengan gambar latar fashion wanita -->
<section class="hero" id="heroSection">
    <div class="hero-content">
        <h1>Tampil Cantik & Percaya Diri</h1>
        <p>
            Koleksi Fashion Wanita Lengkap & Terpercaya
        </p>
        <a href="register.php" class="shop-btn">Belanja Sekarang <i class="fa-solid fa-arrow-right"></i></a>
    </div>
</section>

<!-- KATEGORI FAVORIT -->
<section id="kategoriSection" class="scroll-target">
    <h2 class="section-title">Kategori Favorit</h2>
    <div class="kategori-grid">
        <div class="kategori-card">
            <img src="Gambar/33a47821713c1140c6ba290d9c5bfe02.jpg" alt="Hijab modern" loading="lazy">
            <h3>Tunik</h3>
        </div>
        <div class="kategori-card">
            <img src="Gambar/2e412f054bbd5e5ec5d45b75634d0164.jpg" alt="Gamis elegan" loading="lazy">
            <h3>Gamis</h3>
        </div>
        <div class="kategori-card">
            <img src="Gambar/2d979e899dedabedf086a4c85d6fbb0a.jpg" alt="Blouse trendy" loading="lazy">
            <h3>Blouse</h3>
        </div>
        <div class="kategori-card">
            <img src="Gambar/957fc76d2a2fa883093b30ca262c680c.jpg" alt="Celana nyaman" loading="lazy">
            <h3>Celana</h3>
        </div>
        <div class="kategori-card">
            <img src="Gambar/85edd6e764fb7eb80aed2506e9ee0068.jpg" alt="Aksesoris fashionable" loading="lazy">
            <h3>Mukena</h3>
        </div>
    </div>
</section>

<!-- SECTION ABU-ABU dengan gambar background dekoratif -->
<section id="tentangSection" class="fitur-section scroll-target">
    <h2 class="section-title">Mengapa Memilih Kami?</h2>
    <div class="fitur-grid">
        <div class="fitur-card">
            <i class="fa-solid fa-circle-check"></i>
            <h3>Produk Berkualitas</h3>
            <p>Bahan terbaik, jahitan rapi, dan nyaman dipakai sehari-hari.</p>
        </div>
        <div class="fitur-card">
            <i class="fa-solid fa-truck-fast"></i>
            <h3>Pengiriman Cepat</h3>
            <p>Pesanan diproses maksimal 1x24 jam & aman sampai tujuan.</p>
        </div>
        <div class="fitur-card">
            <i class="fa-solid fa-chart-line"></i>
            <h3>Fashion Terbaru</h3>
            <p>Koleksi selalu update mengikuti tren hijab & busana modern.</p>
        </div>
        <div class="fitur-card">
            <i class="fa-solid fa-lock"></i>
            <h3>Belanja Aman</h3>
            <p>Keamanan data terenkripsi dan sistem pembayaran terpercaya.</p>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-container">
        <div>
            <h3><i class="fa-solid fa-store"></i> AIC Fashion Metro</h3>
            <p>Platform fashion wanita terdepan yang menghadirkan gaya elegan, modern, dan percaya diri. Dari hijab hingga aksesoris, kami siap menemani harimu.</p>
        </div>
        <div>
            <h3><i class="fa-regular fa-address-card"></i> Kontak Kami</h3>
            <p>📍 Metro Lampung, Indonesia</p>
            <p>📞 0812-3456-7890</p>
            <p>✉ info@aicfashion.com</p>
        </div>
        <div>
            <h3><i class="fa-regular fa-heart"></i> Ikuti Kami</h3>
            <div class="social">
                <i class="fab fa-instagram"></i>
                <i class="fab fa-facebook"></i>
                <i class="fab fa-tiktok"></i>
                <i class="fab fa-youtube"></i>
            </div>
            <p style="margin-top: 18px; font-size: 13px;">Dapatkan info promo dan diskon menarik!</p>
        </div>
    </div>
    <div class="footer-bottom">
        © 2026 AIC Fashion Metro. All Rights Reserved. | Belanja fashion wanita mudah & aman.
    </div>
</footer>

<script>
    (function() {
        const homeLink = document.getElementById('homeLink');
        const produkLink = document.getElementById('produkLink');
        const tentangLink = document.getElementById('tentangLink');
        const logoBtn = document.getElementById('homeLogoBtn');
        
        const heroSection = document.getElementById('heroSection');
        const kategoriSection = document.getElementById('kategoriSection');
        const tentangSection = document.getElementById('tentangSection');
        
        function smoothScrollToElement(element, offset = 80) {
            if (!element) return;
            const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
            const offsetPosition = elementPosition - offset;
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
        
        if (homeLink) {
            homeLink.addEventListener('click', function(e) {
                e.preventDefault();
                smoothScrollToElement(heroSection, 70);
            });
        }
        
        if (logoBtn) {
            logoBtn.addEventListener('click', function() {
                smoothScrollToElement(heroSection, 70);
            });
        }
        
        if (produkLink) {
            produkLink.addEventListener('click', function(e) {
                e.preventDefault();
                smoothScrollToElement(kategoriSection, 70);
            });
        }
        
        if (tentangLink) {
            tentangLink.addEventListener('click', function(e) {
                e.preventDefault();
                smoothScrollToElement(tentangSection, 70);
            });
        }
    })();
</script>
</body>
</html>