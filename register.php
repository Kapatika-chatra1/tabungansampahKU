<?php
ob_start();
session_start();
require 'koneksi.php';

$register_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Normalisasi nomor HP: buang non-digit (opsional)
    $no_hp_digits = preg_replace('/\D+/', '', $no_hp);

    // ---- Validasi dasar ----
    if ($nama === '' || $no_hp === '' || $password === '') {
        $register_error = "Semua field wajib diisi!";
    } elseif (strlen($password) < 6) {
        $register_error = "Password minimal 6 karakter.";
    } elseif (strlen($no_hp_digits) < 8) {
        $register_error = "Nomor HP tidak valid.";
    }

    if ($register_error === '') {
        // Cek duplikasi no_hp
        $stmt_check = $conn->prepare("SELECT id_user FROM account WHERE no_hp = ? LIMIT 1");
        $stmt_check->bind_param("s", $no_hp);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check && $res_check->num_rows > 0) {
            $register_error = "Nomor HP sudah terdaftar!";
        } else {
            // Simpan akun
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $conn->prepare(
                "INSERT INTO account (nama, no_hp, password, role) VALUES (?, ?, ?, 'user')"
            );
            $stmt_insert->bind_param("sss", $nama, $no_hp, $hash);

            if ($stmt_insert->execute()) {
                // Flag agar login.php otomatis buka tab "Masuk" + tampilkan alert
                $_SESSION['registered_ok'] = 1;
                header('Location: login.php?registered=1');
                exit();
            } else {
                $register_error = "Terjadi kesalahan saat registrasi. Coba lagi.";
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
  <title>Bank Sampah Karangsewu - Daftar</title>
  <link rel="stylesheet" href="login.css?v=4">
</head>
<body>

<div class="container">
  <!-- Kiri -->
  <div class="left">
    <a href="home.php" class="back-btn">← Kembali ke Beranda</a>
    <div class="content">
      <div class="logo">🌱 Bank Sampah <br><small>Desa Karangsewu</small></div>
      <h1>Bersama Membangun Desa Hijau & Berkelanjutan</h1>
      <p>Kelola sampah dengan bijak, raih nilai ekonomi, dan ciptakan lingkungan yang bersih serta sehat.</p>
      <ul>
        <li>♻️ Sistem pengelolaan sampah yang efisien</li>
        <li>🌍 Kontribusi nyata untuk lingkungan</li>
        <li>🤝 Komunitas yang peduli dan saling mendukung</li>
      </ul>
    </div>
  </div>

  <!-- Kanan -->
  <div class="right">
    <div class="tabs" role="tablist" aria-label="Daftar">
      <button class="tab active" role="tab" aria-selected="true">Daftar</button>
    </div>

    <form id="registerForm" class="form active" method="POST" novalidate>
      <h2>Bergabung dengan Kami</h2>
      <p>Daftarkan diri Anda untuk memulai</p>

      <input type="text"   name="nama"     placeholder="Nama Lengkap" required autocomplete="name" />
      <input type="text"   name="no_hp"    placeholder="Nomor HP" required inputmode="numeric" autocomplete="tel" />
      <input type="password" name="password" placeholder="Minimal 6 karakter" required autocomplete="new-password" />

      <button type="submit" class="btn">Daftar Sekarang</button>

      <?php if ($register_error): ?>
        <p class="error-msg" style="margin-top:10px;"><?= htmlspecialchars($register_error) ?></p>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Boleh pakai login.js untuk efek tombol & toggle mata password -->
<script src="login.js?v=4"></script>
</body>
</html>
