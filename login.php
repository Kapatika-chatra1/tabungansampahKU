<?php
/* ===== Session cookie di-root (“/”) agar berlaku lintas folder ===== */
if (PHP_VERSION_ID >= 70300) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,   // true jika sudah HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
} else {
  session_set_cookie_params(0, '/');
}
session_start();

require 'koneksi.php';

$login_error = '';
$next = $_GET['next'] ?? $_POST['next'] ?? '';

/* whitelist next agar tidak open redirect */
$allow_next = '';
if ($next && preg_match('~^[a-zA-Z0-9/_\-.]+$~', $next) && !str_starts_with($next, 'http')) {
  $allow_next = $next;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($no_hp === '' || $password === '') {
        $login_error = 'Nomor HP dan password wajib diisi!';
    } else {
        $stmt = $conn->prepare("SELECT id_user, nama, role, password FROM account WHERE no_hp = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $no_hp);
            $stmt->execute();
            $res  = $stmt->get_result();
            $akun = ($res && $res->num_rows === 1) ? $res->fetch_assoc() : null;
            $stmt->close();

            if ($akun) {
                $hash = (string)$akun['password'];
                $ok   = false;

                // Jika sudah bcrypt ($2y$...), pakai password_verify
                if (strlen($hash) > 20 && strncmp($hash, '$2y$', 4) === 0) {
                    $ok = password_verify($password, $hash);
                } else {
                    // Legacy: simpan plaintext (contoh di dump ada "0")
                    $ok = hash_equals($hash, $password);
                    // Jika cocok, auto-upgrade ke bcrypt
                    if ($ok) {
                        $new = password_hash($password, PASSWORD_DEFAULT);
                        $up  = $conn->prepare("UPDATE account SET password=? WHERE id_user=?");
                        if ($up) { $up->bind_param("si",$new,$akun['id_user']); $up->execute(); $up->close(); }
                    }
                }

                if ($ok) {
                    session_regenerate_id(true);
                    $_SESSION['id_user'] = (int)$akun['id_user'];
                    $_SESSION['nama']    = $akun['nama'];
                    $_SESSION['role']    = $akun['role'];
                    session_write_close();

                    // Mapping role -> halaman default
                    $redirects = [
                        'super_admin' => 'super_admin.php',
                        'admin'       => 'admin.php',
                        'user'        => 'user.php',
                    ];
                    $default = $redirects[$akun['role']] ?? 'user.php';
                    $dest    = $allow_next ?: $default;

                    header("Location: {$dest}");
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
  <title>Bank Sampah Karangsewu – Masuk</title>
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
      <form id="loginForm" class="form active" method="POST" autocomplete="on">
        <h2>Selamat Datang Kembali</h2>
        <p>Masuk ke akun Anda untuk melanjutkan</p>
        <input type="hidden" name="next" value="<?= htmlspecialchars($allow_next, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="text" inputmode="numeric" id="no_hp" name="no_hp" placeholder="Nomor HP" required>
        <input type="password" name="password" placeholder="Masukkan password" required>
        <button type="submit" class="btn">Masuk</button>
        <?php if ($login_error): ?>
          <p style="color:#c62828; margin-top:10px; font-weight:600;">
            <?= htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?>
          </p>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <script src="login.js?v=3"></script>
</body>
</html>
