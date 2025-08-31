<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bank Sampah Desa Karangsewu</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png">
  <link rel="stylesheet" href="home.css">
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
            <img src="../tabungansampahKU/img/pabrikgula.jpg" alt="Deskripsi gambar 1">
        </div>
        <div class="slide">
            <img src="../tabungansampahKU/img/tambak.JPG" alt="Deskripsi gambar 2">
        </div>
        <div class="slide">
            <img src="../tabungansampahKU/img/gambar3.JPG" alt="Deskripsi gambar 3">
        </div>
    </div>

  </section>

  <!-- Features -->
  <section class="features">
    <div class="card">♻️ Transaksi Sampah</div>
    <div class="card">🗺️ Lacak Pengepul</div>
    <div class="card">🧴 Jenis Sampah</div>
  </section> 

  <!-- Transaksi -->
  <!-- <section class="transaksi" id="transaksi">
    <h2>Form Transaksi</h2>
    <form id="transaksiForm">
      <input type="text" id="nama" placeholder="Nama penyetor" required>
      <select id="jenis">
        <option value="Botol Plastik BEP">Botol Plastik BEP</option>
        <option value="Kantong Kresek">Kantong Kresek</option>
        <option value="Gelas Plastik">Gelas Plastik</option>
      </select>
      <input type="number" id="jumlah" placeholder="Jumlah (kg)" required>
      <button type="submit">Kirim</button>
    </form>

    <h3>Riwayat Transaksi</h3>
    <table id="riwayat">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Jenis Sampah</th>
          <th>Jumlah (kg)</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </section> -->

  <!-- Maps -->
  <section class="maps" id="maps">
    <h2>Lokasi Pengepul</h2>
    <iframe 
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3958.616302826964!2d110.267!3d-7.373!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN8KwMjInMjIuMCJTIDExMMKwMTUnNTIuMCJF!5e0!3m2!1sid!2sid!4v0000000000000" 
      width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy">
    </iframe>
  </section>

  <!-- Footer -->
  <footer id="kontak">
    <p>📍 Desa Karangsewu | 🌐 @banksampahkarangsewu</p>
    <p>© 2025 Bank Sampah Karangsewu</p>
  </footer>

  <script src="home.js"></script>
  <script src="runSlider.js"></script>
</body>
</html>
