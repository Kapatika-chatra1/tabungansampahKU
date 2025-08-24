<?php
session_start();
require 'koneksi.php';

// cek apakah sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

// cek role admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Bank Sampah Karangsewu</title>
  <link rel="stylesheet" href="home.css">
  <style>
    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .user-info h3 {
      margin: 0;
    }
    .user-info a.btn-logout {
      text-decoration: none;
      background-color: #f44336;
      color: white;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 0.9em;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="logo">🌱 Bank Sampah Karangsewu</div>
    <nav>
      <a href="admin.php#home">Home</a>
      <a href="admin.php#transaksi">Transaksi</a>
      <a href="admin.php#maps">Peta</a>
      <a href="admin.php#kontak">Kontak</a>
      
      <?php if(isset($_SESSION['id_user'])): ?>
        <div class="user-info">
          <h3><?= htmlspecialchars($_SESSION['nama']); ?></h3>
          <a href="logout.php" class="btn-logout">Keluar</a>
        </div>
      <?php else: ?>
        <a href="login.php" class="login-btn">Masuk</a>
        <a href="register.php" class="register-btn">Daftar</a>
      <?php endif; ?>
    </nav>
  </header>

  <!-- Hero -->
  <section class="hero" id="home">
    <h1>Selamat Datang Admin, <?= htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p>Ini adalah halaman dashboard khusus admin. Anda dapat memantau dan mengelola transaksi sampah di sini.</p>
  </section>

  <!-- Features / Dashboard -->
  <section class="features">
    <div class="card">♻️ Kelola Transaksi</div>
    <div class="card">🧾 Laporan Harian</div>
    <div class="card">🛠️ Pengaturan Sistem</div>
  </section>

  <!-- Transaksi -->
  <section class="transaksi" id="transaksi">
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
  </section>

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
</body>
</html>
