<?php
ob_start();
session_start();
require 'koneksi.php';

$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($nama === '' || $no_hp === '' || $password === '') {
        $register_error = "Semua field wajib diisi!";
    } else {
        $stmt_check = $conn->prepare("SELECT * FROM account WHERE no_hp = ?");
        $stmt_check->bind_param("s", $no_hp);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $register_error = "Nomor HP sudah terdaftar!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $conn->prepare("INSERT INTO account (nama,no_hp,password,role) VALUES (?,?,?, 'user')");
            $stmt_insert->bind_param("sss", $nama, $no_hp, $hash);
            if ($stmt_insert->execute()) {
                $register_success = "Registrasi berhasil! Silakan login.";
            } else {
                $register_error = "Terjadi kesalahan saat registrasi!";
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
<title>Bank Sampah Karangsewu - Register</title>
<link rel="stylesheet" href="login.css">
</head>
<body>
<div class="container">
  <div class="left">
    <a href="home.php" class="back-btn">← Kembali ke Beranda</a>
    <div class="content">
      <div class="logo">🌱 Bank Sampah <br><small>Desa Karangsewu</small></div>
      <h1>Bersama Membangun Desa Hijau & Berkelanjutan</h1>
      <p>Bergabunglah dengan komunitas peduli lingkungan. Kelola sampah dengan bijak, raih keuntungan ekonomi, dan wujudkan desa yang bersih dan sejahtera.</p>
      <ul>
        <li>♻️ Sistem pengelolaan sampah yang efisien</li>
        <li>🌍 Kontribusi nyata untuk lingkungan</li>
        <li>🤝 Komunitas yang peduli dan saling mendukung</li>
      </ul>
    </div>
  </div>

  <div class="right">
    <div class="tabs">
      <button class="tab active">Daftar</button>
    </div>
    <form id="registerForm" class="form active" method="POST">
      <h2>Bergabung dengan Kami</h2>
      <p>Daftarkan diri Anda untuk memulai</p>
      <input type="text" name="nama" placeholder="Nama Lengkap" required>
      <input type="text" name="no_hp" placeholder="Nomor HP" required>
      <input type="password" name="password" placeholder="Minimal 6 karakter" required>
      <button type="submit" class="btn">Daftar Sekarang</button>
      <?php 
        if($register_error) echo "<p style='color:red; margin-top:10px;'>$register_error</p>";
        if($register_success) echo "<p style='color:green; margin-top:10px;'>$register_success</p>";
      ?>
    </form>
  </div>
</div>
<script src="login.js"></script>
</body>
</html>
