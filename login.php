<?php
ob_start();
session_start();
require 'koneksi.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($no_hp === '' || $password === '') {
        $login_error = 'Nomor HP dan password wajib diisi!';
    } else {
        $sql = "SELECT * FROM account WHERE no_hp = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $no_hp);
            $stmt->execute();

            // get_result() but fallback bisa ditambahkan jika environment tidak mendukung
            $result = $stmt->get_result();
            $akun = null;
            if ($result && $result->num_rows === 1) {
                $akun = $result->fetch_assoc();
            }

            $stmt->close();

            if ($akun) {
                if (password_verify($password, $akun['password'])) {
                    // set session
                    $_SESSION['id_user'] = $akun['id_user'];
                    $_SESSION['nama']    = $akun['nama'];
                    $_SESSION['role']    = $akun['role'];
                    session_write_close();

                    // mapping role -> page (mudah ditambah nantinya)
                    $redirects = [
                        'super_admin' => 'super_admin.php',
                        'admin'       => 'admin.php',
                        'user'        => 'user.php'
                    ];
                    $redirect = $redirects[$akun['role']] ?? 'user.php';

                    header("Location: $redirect");
                    exit();
                } else {
                    $login_error = 'Password salah!';
                }
            } else {
                $login_error = 'Akun tidak ditemukan!';
            }
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

    </div>
  </div>

  <script src="login.js?v=3"></script>
</body>
</html>
