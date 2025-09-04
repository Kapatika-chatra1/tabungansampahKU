<?php /* biarkan kosong agar file bisa dieksekusi sebagai .php */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bank Sampah Desa Karangsewu</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png" />
  <link rel="stylesheet" href="home.css?v=2" />
</head>
<body>

  <!-- Header -->
  <header>
    <div class="logo">🌱 <b>Bank Sampah KarangsewuKU</b></div>

    <!-- NAV DESKTOP -->
    <nav class="desktop-nav" aria-label="Navigasi utama">
      <a href="#home">Home</a>
      <a href="#maps">Peta</a>
      <a href="#kontak">Kontak</a>
      <a href="login.php" class="login-btn">Masuk</a>
      <a href="register.php" class="register-btn">Daftar</a>
    </nav>

    <!-- TOMBOL HAMBURGER (HP) -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle"
            aria-label="Buka menu" aria-expanded="false" aria-controls="mobileMenu">
      <span class="hamburger"></span>
      <span class="hamburger"></span>
      <span class="hamburger"></span>
    </button>
  </header>

  <!-- PANEL MOBILE MENU -->
  <div class="mobile-menu" id="mobileMenu" role="dialog" aria-modal="true" aria-label="Menu">
    <button class="mobile-close" id="mobileMenuClose" aria-label="Tutup menu">×</button>
    <ul class="mobile-nav-links">
      <li><a href="#home">Home</a></li>
      <li><a href="#maps">Peta</a></li>
      <li><a href="#kontak">Kontak</a></li>
      <li><a href="login.php">Masuk</a></li>
      <li><a href="register.php">Daftar</a></li>
    </ul>
  </div>

  <!-- Hero -->
  <section class="hero" id="home">
    <h1>Kelola Sampah, Wujudkan Desa Bersih &amp; Sejahtera</h1>
    <p>Bersama masyarakat Karangsewu, kita ciptakan lingkungan yang sehat dan bernilai ekonomi.</p>

    <!-- Slider -->
    <div class="slider-container" aria-label="Galeri foto">
      <div class="slide"><img src="img/pabrikgula.jpg" alt="Bangunan tua pintu hijau di Karangsewu"></div>
      <div class="slide"><img src="img/tambak.JPG"       alt="Area tambak di Karangsewu"></div>
      <div class="slide"><img src="img/gambar3.JPG"     alt="Lingkungan desa Karangsewu"></div>
    </div>
  </section>

  <!-- Features -->
  <section class="features" aria-label="Fitur utama">
    <div class="card">♻️ Transaksi Sampah</div>
    <div class="card">🗺️ Lacak Pengepul</div>
    <div class="card">🧴 Jenis Sampah</div>
  </section>

  <!-- Maps -->
  <section class="maps" id="maps" aria-label="Lokasi Pengepul">
    <h2>Lokasi Pengepul</h2>
    <iframe
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3958.616302826964!2d110.267!3d-7.373!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN8KwMjInMjIuMCJTIDExMMKwMTUnNTIuMCJF!5e0!3m2!1sid!2sid!4v0000000000000"
      width="100%" height="320" style="border:0;" allowfullscreen="" loading="lazy"
      referrerpolicy="no-referrer-when-downgrade" title="Peta lokasi pengepul Karangsewu">
    </iframe>
  </section>

  <!-- Footer -->
  <footer id="kontak">
    <p>📍 Desa Karangsewu &nbsp;|&nbsp; 🌐 @banksampahkarangsewu</p>
    <p>© 2025 Bank Sampah Karangsewu</p>
  </footer>

  <script src="home.js?v=2"></script>
</body>
</html>
