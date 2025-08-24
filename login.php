<?php
ob_start();
session_start();
require 'koneksi.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($no_hp === '' || $password === '') {
        $login_error = "Nomor HP dan password wajib diisi!";
    } else {
        $sql  = "SELECT * FROM account WHERE no_hp = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $no_hp);
        $stmt->execute();
        $result = $stmt->get_result();

        $akun = null; // inisialisasi

if ($result && $result->num_rows === 1) {
    $akun = $result->fetch_assoc();
}

if ($akun) {
    if (password_verify($password, $akun['password'])) {
        $_SESSION['id_akun'] = $akun['id_akun'];
        $_SESSION['nama']    = $akun['nama'];
        $_SESSION['role']    = $akun['role'];
        session_write_close();
        $redirect = $akun['role'] === 'admin' ? 'admin.php' : 'user.php';
        header("Location: $redirect");
        exit();
    } else {
        $login_error = "Password salah!";
    }
} else {
    $login_error = "Akun tidak ditemukan!";
}

    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bank Sampah Karangsewu - Login</title>
  <link rel="stylesheet" href="login.css">
</head>

<body>
  <div class="container">
    <!-- Kiri -->
    <div class="left">
      <a href="home.php" class="back-btn">← Kembali ke Beranda</a>
      <div class="content">
        <div class="logo">🌱 Bank Sampah <br><small>Desa Karangsewu</small></div>
        <h1>Bersama Membangun Desa Hijau & Berkelanjutan</h1>
        <p>Bergabunglah dengan komunitas peduli lingkungan. Kelola sampah dengan bijak, raih keuntungan ekonomi, dan
          wujudkan desa yang bersih dan sejahtera.</p>
        <ul>
          <li>♻️ Sistem pengelolaan sampah yang efisien</li>
          <li>🌍 Kontribusi nyata untuk lingkungan</li>
          <li>🤝 Komunitas yang peduli dan saling mendukung</li>
        </ul>
      </div>
    </div>

    <!-- Kanan -->
    <div class="right">
      <div class="tabs">
        <button class="tab active" onclick="showForm('login')">Masuk</button>
        <button class="tab" onclick="showForm('register')">Daftar</button>
      </div>

      <!-- Form Login -->
      <form id="loginForm" class="form active" method="POST">
        <h2>Selamat Datang Kembali</h2>
        <p>Masuk ke akun Anda untuk melanjutkan</p>
        <input type="text" id="no_hp" name="no_hp" placeholder="Nomor HP" required>
        <input type="password" name="password" placeholder="Masukkan password" required>
        <button type="submit" class="btn">Masuk</button>
        <?php if($login_error) echo "<p style='color:red; margin-top:10px;'>$login_error</p>"; ?>
        <div class="demo">
          <p><b>Demo credentials:</b></p>
          <p>Admin: admin@banksampah.id / admin123</p>
          <p>User: budi@gmail.com / budi123</p>
        </div>
      </form>

      <!-- Form Register -->
      <form id="registerForm" class="form" action="register.php" method="POST">
        <h2>Bergabung dengan Kami</h2>
        <p>Daftarkan diri Anda untuk memulai</p>
        <input type="text" name="nama" placeholder="Nama Lengkap" required>
        <input type="text" name="no_hp" placeholder="Nomor HP" required>
        <input type="password" name="password" placeholder="Minimal 6 karakter" required>
        <button type="submit" class="btn">Daftar Sekarang</button>
      </form>
    </div>
  </div>

  <script src="login.js"></script>
</body>

</html>
