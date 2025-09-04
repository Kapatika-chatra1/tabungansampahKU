<?php /* tidak perlu PHP khusus—biarkan kosong saja agar bisa .php */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bank Sampah Desa Karangsewu</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png" />

  <!-- CSS lokal (pakai query agar tidak kena cache) -->
  <link rel="stylesheet" href="home.css?v=2" />

  <!-- Leaflet (untuk peta) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
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
    <!-- Ganti iframe -> div untuk Leaflet -->
    <div id="map" style="height: 320px; width: 100%; border-radius: 12px; overflow: hidden;"></div>
  </section>

  <!-- Footer -->
  <footer id="kontak">
    <p>📍 Desa Karangsewu &nbsp;|&nbsp; 🌐 @banksampahkarangsewu</p>
    <p>© 2025 Bank Sampah Karangsewu</p>
  </footer>

  <!-- JS: Leaflet + file lokal (pakai query agar tidak ke-cache) -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="home.js?v=2"></script>

  <!-- Init kecil: aktifkan slide pertama (jaga-jaga) + inisialisasi peta -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Pastikan slide pertama aktif
      const slides = document.querySelectorAll('.slide');
      if (slides.length && !document.querySelector('.slide.active')) {
        slides[0].classList.add('active');
      }

      // Inisialisasi Leaflet
      const map = L.map('map').setView([-7.9539772, 110.1813977], 11);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      // Marker contoh
      L.marker([-7.9490876, 110.1975741])
        .addTo(map)
        .bindPopup('Titik Bank Sampah Sorogaten');
    });
  </script>
</body>
</html>
