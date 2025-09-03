<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bank Sampah Desa Karangsewu</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png">
  <link rel="stylesheet" href="home.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="logo">🌱 <b>Bank Sampah KarangsewuKU</b></div>
    <nav>
      <a href="#home">Home</a>
      <a href="#transaksi">Transaksi</a>
      <a href="#maps">Peta</a>
      <a href="#kontak">Kontak</a>
      <a href="login.php" class="login-btn">Masuk</a>
      <a href="register.php" class="register-btn">Daftar</a>
    </nav>
  </header>

  <!-- Hero -->
  <section class="hero" id="home">
    <h1>Kelola Sampah, Wujudkan Desa Bersih & Sejahtera</h1>
    <p>Bersama masyarakat Karangsewu, kita ciptakan lingkungan yang sehat dan bernilai ekonomi.</p>
    <div class="slider-container">
        <div class="slide">
            <img src="img/pabrikgula.jpg" alt="Deskripsi gambar 1">
        </div>
        <div class="slide">
            <img src="img/tambak.JPG" alt="Deskripsi gambar 2">
        </div>
        <div class="slide">
            <img src="img/gambar3.JPG" alt="Deskripsi gambar 3">
        </div>
    </div>

  </section>

  <!-- Features -->
  <section class="features">
    <div class="card">♻️  Transaksi Sampah</div>
    <div class="card">🗺️ Lacak Pengepul</div>
    <div class="card">🧴 Jenis Sampah</div>
  </section> 
  <!-- Maps -->
  <section class="maps" id="maps">
  <h2>Lokasi Pengepul</h2>
  <!-- Peta Leaflet -->
  <div id="map" style="height:450px; width:600px;"></div>
</section>

  <!-- Footer -->
  <footer id="kontak">
    <p>📍 Desa Karangsewu | 🌐 @banksampahkarangsewu</p>
    <p>© 2025 Bank Sampah Karangsewu</p>
  </footer>

  <script src="home.js"></script>
  <script src="runSlider.js"></script>
  
  <script>
  // Inisialisasi peta
  var map = L.map('map').setView([-7.9539772,110.1813977], 11); // Karangsewu, DIY

  // Layer OpenStreetMap
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // Tambah marker contoh
  L.marker([-7.9490876, 110.1975741])
    .addTo(map)
    .bindPopup("Titik Bank Sampah Sorogaten")
    .openPopup();
</script>
</body>
</html>
