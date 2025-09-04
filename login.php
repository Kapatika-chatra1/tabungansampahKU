<?php
ob_start();
session_start();
require 'koneksi.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_hp   = trim($_POST['no_hp'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($no_hp === '' || $password === '') {
        $login_error = 'Nomor HP dan password wajib diisi!';
    } else {
        $sql  = "SELECT * FROM account WHERE no_hp = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $no_hp);
            $stmt->execute();
            $result = $stmt->get_result();

            $akun = null;
            if ($result && $result->num_rows === 1) {
                $akun = $result->fetch_assoc();
            }

            if ($akun) {
                if (password_verify($password, $akun['password'])) {
                    $_SESSION['id_user'] = $akun['id_user'];
                    $_SESSION['nama']    = $akun['nama'];
                    $_SESSION['role']    = $akun['role'];
                    session_write_close();
                    $redirect = ($akun['role'] === 'admin') ? 'admin.php' : 'user.php';
                    header("Location: $redirect");
                    exit();
                } else {
                    $login_error = 'Password salah!';
                }
            } else {
                $login_error = 'Akun tidak ditemukan!';
            }
            $stmt->close();
        } else {
            $login_error = 'Kesalahan server. Coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bank Sampah Karangsewu – Masuk / Daftar</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png">
  <link rel="stylesheet" href="login.css?v=3">
</head>
<body>
  <div class="container">
    <!-- Kiri -->
    <div class="left">
      <a href="home.php" class="back-btn" aria-label="Kembali ke Beranda">← Kembali ke Beranda</a>
      <div class="content">
        <div class="logo">🌱 Bank Sampah <br><small>Desa Karangsewu</small></div>
        <h1>Bersama Membangun Desa Hijau &amp; Berkelanjutan</h1>
        <p>Bergabunglah dengan komunitas peduli lingkungan. Kelola sampah dengan bijak, raih keuntungan ekonomi, dan wujudkan desa yang bersih serta sejahtera.</p>
        <ul>
          <li>♻️ Sistem pengelolaan sampah yang efisien</li>
          <li>🌍 Kontribusi nyata untuk lingkungan</li>
          <li>🤝 Komunitas yang peduli dan saling mendukung</li>
        </ul>
      </div>
    </div>

    <!-- Kanan -->
    <div class="right">
      <!-- Tabs -->
      <div class="tabs" role="tablist" aria-label="Pilih formulir">
        <button type="button" class="tab active" role="tab" aria-selected="true"
                aria-controls="loginForm" id="tab-login"
                onclick="showForm('login')">Masuk</button>
        <button type="button" class="tab" role="tab" aria-selected="false"
                aria-controls="registerForm" id="tab-register"
                onclick="showForm('register')">Daftar</button>
      </div>

      <!-- Form Login -->
      <form id="loginForm" class="form active" method="POST" aria-labelledby="tab-login" autocomplete="on">
        <h2>Selamat Datang Kembali</h2>
        <p>Masuk ke akun Anda untuk melanjutkan</p>
        <input type="text" inputmode="numeric" id="no_hp" name="no_hp" placeholder="Nomor HP" required>
        <input type="password" name="password" placeholder="Masukkan password" required>
        <button type="submit" class="btn">Masuk</button>
        <?php if ($login_error): ?>
          <p style="color:#c62828; margin-top:10px; font-weight:600;">
            <?= htmlspecialchars($login_error) ?>
          </p>
        <?php endif; ?>
      </form>

      <!-- Form Register -->
      <form id="registerForm" class="form" action="register.php" method="POST" aria-labelledby="tab-register">
        <h2>Bergabung dengan Kami</h2>
        <p>Daftarkan diri Anda untuk memulai</p>
        <input type="text" name="nama" placeholder="Nama Lengkap" required>
        <input type="text" inputmode="numeric" name="no_hp" placeholder="Nomor HP" required>
        <input type="password" name="password" placeholder="Minimal 6 karakter" minlength="6" required>
        <button type="submit" class="btn">Daftar Sekarang</button>
      </form>
    </div>
  </div>

  <script src="login.js?v=3"></script>
</body>
</html>
