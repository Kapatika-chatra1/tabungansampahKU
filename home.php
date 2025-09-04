<?php /* kosongkan saja agar bisa dieksekusi sebagai .php */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bank Sampah Desa Karangsewu</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png" />

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

  <!-- App CSS -->
  <link rel="stylesheet" href="home.css?v=5" />
</head>
<body>

  <!-- Back to top -->
  <button class="totop" id="toTop" aria-label="Kembali ke atas">↑</button>

  <!-- Header -->
  <header id="siteHeader" class="reveal">
    <div class="container header-inner">
      <a class="brand" href="#home" aria-label="Beranda">
        <span class="brand-logo">🌱</span>
        <span class="brand-text"><strong>Bank Sampah</strong> KarangsewuKU</span>
      </a>

      <!-- Desktop Nav -->
      <nav class="nav-desktop" aria-label="Navigasi utama">
        <a href="#home">Home</a>
        <a href="#features">Fitur</a>
        <a href="#about">Tentang</a>
        <a href="#maps">Peta</a>
        <a href="#kontak">Kontak</a>
        <a href="login.php" class="btn btn-ghost ripple">Masuk</a>
      </nav>

      <!-- Mobile Toggle -->
      <button class="nav-toggle" id="mobileMenuToggle" aria-label="Buka menu" aria-expanded="false" aria-controls="mobileMenu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- Mobile Menu -->
  <div class="mobile-menu" id="mobileMenu" role="dialog" aria-modal="true" aria-label="Menu">
    <button class="mobile-close" id="mobileMenuClose" aria-label="Tutup menu">×</button>
    <ul class="mobile-links">
      <li><a href="#home">Home</a></li>
      <li><a href="#features">Fitur</a></li>
      <li><a href="#about">Tentang</a></li>
      <li><a href="#maps">Peta</a></li>
      <li><a href="#kontak">Kontak</a></li>
      <li class="split"></li>
      <li><a href="login.php" class="btn btn-ghost w-full ripple">Masuk</a></li>
      <!-- kalau tetap ingin “Daftar”, arahkan ke login juga sesuai permintaan -->
      <li><a href="login.php" class="btn btn-primary w-full ripple">Daftar</a></li>
    </ul>
  </div>

  <!-- HERO -->
  <section class="hero" id="home">
    <div class="container hero-grid">
      <div class="hero-text reveal">
        <div class="pill">Bersih • Hijau • Berdaya</div>
        <h1>Kelola Sampah, Wujudkan Desa <span class="grad">Bersih</span> & <span class="grad">Sejahtera</span></h1>
        <p>Bersama masyarakat Karangsewu, kita ciptakan lingkungan sehat dan bernilai ekonomi melalui pengelolaan sampah yang mudah, transparan, dan menguntungkan.</p>

        <!-- 3 tombol (rapih di mobile grid 3 kolom) -->
        <div class="hero-cta">
          <a href="login.php" class="btn btn-primary ripple">Mulai Gabung</a>
          <a href="login.php" class="btn btn-ghost ripple">Masuk</a>
          <a href="#maps" class="btn btn-outline ripple">Lihat Peta</a>
        </div>

        <!-- Stats -->
        <div class="stats">
          <div class="stat reveal" style="--d:.0s">
            <div class="num" data-count="1200">0</div>
            <div class="label">Kg Sampah Terkelola</div>
          </div>
          <div class="stat reveal" style="--d:.1s">
            <div class="num" data-count="340">0</div>
            <div class="label">Transaksi</div>
          </div>
          <div class="stat reveal" style="--d:.2s">
            <div class="num" data-count="18">0</div>
            <div class="label">Pengepul Aktif</div>
          </div>
        </div>
      </div>

      <!-- Slider -->
      <div class="hero-slider reveal">
        <div class="slider" id="slider">
          <div class="slide active"><img src="img/pabrikgula.jpg" alt="Bangunan tua pintu hijau di Karangsewu"></div>
          <div class="slide"><img src="img/tambak.JPG" alt="Area tambak di Karangsewu"></div>
          <div class="slide"><img src="img/gambar3.JPG" alt="Lingkungan desa Karangsewu"></div>
          <div class="slider-overlay"></div>
        </div>
        <div class="dots" id="sliderDots" aria-label="Kontrol slider"></div>
      </div>
    </div>
  </section>

  <!-- FITUR -->
  <section class="features" id="features" aria-label="Fitur utama">
    <div class="container">
      <h2 class="section-title reveal">Fitur Unggulan</h2>
      <p class="section-sub reveal">Semua yang Anda butuhkan untuk mengelola sampah dengan cerdas.</p>

      <div class="cards">
        <article class="card reveal" style="--d:.0s">
          <div class="icon">♻️</div>
          <h3>Transaksi Mudah</h3>
          <p>Catat, pantau, dan tarik saldo hasil setoran sampah kapan saja. Transparan dan aman.</p>
        </article>

        <article class="card reveal" style="--d:.08s">
          <div class="icon">🗺️</div>
          <h3>Lacak Pengepul</h3>
          <p>Temukan pengepul terdekat, lihat profil dan kontaknya, lalu atur penjemputan sampah.</p>
        </article>

        <article class="card reveal" style="--d:.16s">
          <div class="icon">🧴</div>
          <h3>Panduan Jenis Sampah</h3>
          <p>Belajar memilah plastik, kertas, logam, dan lainnya untuk nilai jual yang lebih tinggi.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- ABOUT -->
  <section class="about" id="about">
    <div class="container about-grid">
      <div class="about-media reveal">
        <div class="img big" style="background-image:url('img/tambak.JPG')"></div>
        <div class="img small" style="background-image:url('img/pabrikgula.jpg')"></div>
      </div>
      <div class="about-text reveal">
        <h2>Tentang Desa Karangsewu</h2>
        <p>Desa Karangsewu di pesisir Kulon Progo memadukan kearifan lokal dan inovasi. Dari sawah hijau hingga tambak produktif, kolaborasi warga menjadikan lingkungan lebih bersih dan ekonomi lebih kuat.</p>
        <ul>
          <li>🌾 Sektor agraris & perikanan yang kuat</li>
          <li>👥 Kolaborasi warga, RT/RW, & pengepul</li>
          <li>📈 Program berkelanjutan & ekonomi sirkular</li>
        </ul>
        <a href="#maps" class="btn btn-primary ripple">Lihat lokasi pengepul</a>
      </div>
    </div>
  </section>

  <!-- MAPS -->
  <section class="maps" id="maps" aria-label="Lokasi Pengepul">
    <div class="container">
      <h2 class="section-title reveal">Lokasi Pengepul</h2>
      <p class="section-sub reveal">Temukan titik terdekat dan mulai bertransaksi.</p>

      <div class="map-card reveal">
        <div id="map" class="map"></div>
        <div class="map-legend">
          <span class="dot"></span> Titik pengepul aktif
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta reveal">
    <div class="container cta-inner">
      <h3>Siap mengubah sampah jadi manfaat?</h3>
      <p>Gabung sekarang dan kelola sampah dengan cara yang mudah dan menguntungkan.</p>
      <div class="cta-actions">
        <a href="login.php" class="btn btn-primary ripple">Mulai Gabung</a>
        <a href="login.php" class="btn btn-ghost ripple">Masuk</a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer id="kontak">
    <div class="container footer-grid">
      <div class="fcol">
        <div class="brand mini"><span>🌱</span> Bank Sampah Karangsewu</div>
        <p>Desa Karangsewu, Kulon Progo<br>Yogyakarta</p>
      </div>
      <div class="fcol">
        <h4>Menu</h4>
        <ul>
          <li><a href="#home">Home</a></li>
          <li><a href="#features">Fitur</a></li>
          <li><a href="#about">Tentang</a></li>
          <li><a href="#maps">Peta</a></li>
        </ul>
      </div>
      <div class="fcol">
        <h4>Kontak</h4>
        <ul>
          <li>📧 info@karangsewuku.id</li>
          <li>📞 08xx-xxxx-xxxx</li>
          <li>🌐 @banksampahkarangsewu</li>
        </ul>
      </div>
    </div>
    <div class="copy">© 2025 Bank Sampah Karangsewu • All rights reserved</div>
  </footer>

  <!-- Leaflet + App JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="home.js?v=5"></script>
</body>
</html>
