<?php
/* =======================================================================
   ADMIN DASHBOARD — Bank Sampah Karangsewu
   - Satu file UI + API ringan (JSON)
   - Endpoint dipilih via ?action=...
======================================================================= */

session_start();
require 'koneksi.php';

/* ===== Guard: hanya admin ===== */
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
  if (isset($_GET['action'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "Unauthorized"]);
  } else {
    header('Location: login.php');
  }
  exit();
}

/* ===== Helper response JSON (steril) ===== */
function respond($data, $code=200){
  while (ob_get_level()) { ob_end_clean(); }  // buang buffer agar tidak ada Notice/whitespace nyasar
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit();
}

/* Saat mode API, jangan tampilkan error ke output */
if (isset($_GET['action'])) {
  ini_set('display_errors', '0');
  error_reporting(0);
}

/* =======================================================================
   API endpoints
======================================================================= */
if (isset($_GET['action'])) {
  $a = $_GET['action'];

  switch ($a) {
    /* ---------- TRANSAKSI ---------- */
    case 'create': {
      $nama     = trim($_POST['nama'] ?? '');
      $id_jenis = (int) ($_POST['id_jenis'] ?? 0);
      $jumlah   = (int) ($_POST['jumlah'] ?? 0);
      if ($nama === '' || $id_jenis <= 0 || $jumlah <= 0) respond(["error"=>"Data transaksi tidak lengkap atau jumlah tidak valid."],400);

      // user by nama
      $q = $conn->prepare("SELECT id_user, no_hp, nama FROM account WHERE nama=? LIMIT 1");
      $q->bind_param("s", $nama);
      $q->execute(); $res = $q->get_result();
      if ($res->num_rows === 0) respond(["error"=>"User tidak ditemukan"],404);
      $u = $res->fetch_assoc();
      $id_user   = (int)$u['id_user'];
      $no_hp     = $u['no_hp'];
      $user_nama = $u['nama'];

      // harga jenis
      $qh = $conn->prepare("SELECT harga FROM jenis_sampah WHERE id_jenis=? LIMIT 1");
      $qh->bind_param("i", $id_jenis);
      $qh->execute(); $resH = $qh->get_result();
      if ($resH->num_rows === 0) respond(["error"=>"Jenis sampah tidak valid"],400);
      $harga   = (int)$resH->fetch_assoc()['harga'];
      $nominal = $jumlah * $harga;

      $conn->begin_transaction();
      try {
        $insT = $conn->prepare("INSERT INTO `transaction` (id_user, no_hp, id_jenis, jumlah_setoran, tanggal) VALUES (?, ?, ?, ?, NOW())");
        $insT->bind_param("isii", $id_user, $no_hp, $id_jenis, $jumlah);
        $insT->execute();

        $cekS = $conn->prepare("SELECT id_saldo, saldo FROM saldo WHERE id_user=?");
        $cekS->bind_param("i", $id_user);
        $cekS->execute(); $resS = $cekS->get_result();

        if ($resS->num_rows > 0) {
          $rowS = $resS->fetch_assoc();
          $newSaldo = (int)$rowS['saldo'] + $nominal;
          $updS = $conn->prepare("UPDATE saldo SET saldo=? WHERE id_saldo=?");
          $updS->bind_param("ii", $newSaldo, $rowS['id_saldo']);
          $updS->execute();
        } else {
          $insS = $conn->prepare("INSERT INTO saldo (id_user, nama, saldo) VALUES (?, ?, ?)");
          $insS->bind_param("isi", $id_user, $user_nama, $nominal);
          $insS->execute();
        }

        $conn->commit();
        respond(["success"=>true]);
      } catch (Throwable $e) {
        $conn->rollback();
        respond(["error"=>"Gagal menyimpan transaksi: ".$e->getMessage()],500);
      }
    } break;

    case 'read': {
      $sql = "SELECT 
                t.id_trans, a.nama, j.jenis AS jenis_sampah, j.harga,
                t.jumlah_setoran, (t.jumlah_setoran * j.harga) AS nominal, t.tanggal
              FROM `transaction` t
              JOIN account a ON t.id_user = a.id_user
              JOIN jenis_sampah j ON t.id_jenis = j.id_jenis
              WHERE t.tanggal >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
              ORDER BY t.id_trans ASC";
      $res  = $conn->query($sql);
      respond($res ? $res->fetch_all(MYSQLI_ASSOC) : []);
    } break;

    case 'update': {
      $id        = (int) ($_POST['id'] ?? 0);
      $id_jenis  = (int) ($_POST['id_jenis'] ?? 0);
      $jumlah    = (int) ($_POST['jumlah'] ?? 0);
      if ($id<=0 || $id_jenis<=0 || $jumlah<=0) respond(["error"=>"Data update tidak lengkap/valid."],400);

      $getOld = $conn->prepare("SELECT id_user, id_jenis, jumlah_setoran FROM `transaction` WHERE id_trans=?");
      $getOld->bind_param("i", $id); $getOld->execute();
      $oldRes = $getOld->get_result();
      if ($oldRes->num_rows === 0) respond(["error"=>"Transaksi tidak ditemukan"],404);
      $old = $oldRes->fetch_assoc();
      $id_user       = (int)$old['id_user'];
      $id_jenis_lama = (int)$old['id_jenis'];
      $jumlah_lama   = (int)$old['jumlah_setoran'];

      $hLrow = $conn->query("SELECT harga FROM jenis_sampah WHERE id_jenis=".$id_jenis_lama);
      $hL = $hLrow && $hLrow->num_rows ? (int)$hLrow->fetch_assoc()['harga'] : 0;
      $nominal_lama = $jumlah_lama * $hL;

      $hBrow = $conn->query("SELECT harga FROM jenis_sampah WHERE id_jenis=".$id_jenis);
      $hB = $hBrow && $hBrow->num_rows ? (int)$hBrow->fetch_assoc()['harga'] : 0;
      if ($hB <= 0) respond(["error"=>"Jenis sampah baru tidak valid"],400);
      $nominal_baru = $jumlah * $hB;
      $delta = $nominal_baru - $nominal_lama;

      $conn->begin_transaction();
      try {
        $updT = $conn->prepare("UPDATE `transaction` SET id_jenis=?, jumlah_setoran=? WHERE id_trans=?");
        $updT->bind_param("iii", $id_jenis, $jumlah, $id);
        $updT->execute();

        $cekS = $conn->prepare("SELECT id_saldo, saldo FROM saldo WHERE id_user=?");
        $cekS->bind_param("i", $id_user);
        $cekS->execute();
        $resS = $cekS->get_result();
        if ($resS->num_rows > 0) {
          $rowS = $resS->fetch_assoc();
          $newSaldo = max(0, (int)$rowS['saldo'] + $delta);
          $updS = $conn->prepare("UPDATE saldo SET saldo=? WHERE id_saldo=?");
          $updS->bind_param("ii", $newSaldo, $rowS['id_saldo']);
          $updS->execute();
        }

        $conn->commit();
        respond(["success"=>true]);
      } catch (Throwable $e) {
        $conn->rollback();
        respond(["error"=>"Gagal update transaksi: ".$e->getMessage()],500);
      }
    } break;

    case 'delete': {
      $id = (int) ($_POST['id'] ?? 0);
      if ($id <= 0) respond(["error"=>"ID tidak valid."],400);

      $conn->begin_transaction();
      try {
        $getOld = $conn->prepare("SELECT id_user, id_jenis, jumlah_setoran FROM `transaction` WHERE id_trans=?");
        $getOld->bind_param("i",$id); $getOld->execute();
        $oldRes = $getOld->get_result();
        if ($oldRes->num_rows === 0) throw new Exception("Transaksi tidak ditemukan.");
        $old = $oldRes->fetch_assoc();
        $id_user = (int)$old['id_user'];
        $id_jenis= (int)$old['id_jenis'];
        $jumlah_lama = (int)$old['jumlah_setoran'];

        $hargaRow = $conn->query("SELECT harga FROM jenis_sampah WHERE id_jenis=".$id_jenis);
        $harga = $hargaRow && $hargaRow->num_rows ? (int)$hargaRow->fetch_assoc()['harga'] : 0;
        $nominal_lama = $jumlah_lama * $harga;

        $del = $conn->prepare("DELETE FROM `transaction` WHERE id_trans=?");
        $del->bind_param("i",$id); $del->execute();

        if ($nominal_lama > 0) {
          $cekS = $conn->prepare("SELECT id_saldo, saldo FROM saldo WHERE id_user=?");
          $cekS->bind_param("i",$id_user); $cekS->execute();
          $resS = $cekS->get_result();
          if ($resS->num_rows > 0) {
            $rowS = $resS->fetch_assoc();
            $newSaldo = max(0, (int)$rowS['saldo'] - $nominal_lama);
            $updS = $conn->prepare("UPDATE saldo SET saldo=? WHERE id_saldo=?");
            $updS->bind_param("ii",$newSaldo,$rowS['id_saldo']);
            $updS->execute();
          }
        }
        $conn->commit();
        respond(["success"=>true]);
      } catch (Throwable $e) {
        $conn->rollback();
        respond(["error"=>"Gagal menghapus transaksi: ".$e->getMessage()],500);
      }
    } break;

    /* ---------- USER ---------- */
    case 'createUser': {
      $nama   = trim($_POST['nama'] ?? '');
      $no_hp  = trim($_POST['no_hp'] ?? '');
      $alamat = trim($_POST['alamat'] ?? '');
      if ($nama==='' || $no_hp==='') respond(["error"=>"Data user tidak lengkap."],400);

      $cek = $conn->prepare("SELECT id_user FROM account WHERE no_hp=? LIMIT 1");
      $cek->bind_param("s",$no_hp); $cek->execute();
      if ($cek->get_result()->num_rows > 0) respond(["error"=>"No HP sudah terdaftar."],409);

      $password = password_hash("user123", PASSWORD_DEFAULT);
      $role = "user";

      $ins = $conn->prepare("INSERT INTO account (nama, no_hp, alamat, password, role) VALUES (?, ?, ?, ?, ?)");
      $ins->bind_param("sssss", $nama, $no_hp, $alamat, $password, $role);
      if ($ins->execute()) respond(["success"=>true]);
      respond(["error"=>"Gagal menambah user: ".$ins->error],500);
    } break;

    case 'readUser': {
      $sql = "SELECT id_user, nama, no_hp, alamat, role FROM account WHERE role='user' ORDER BY id_user ASC";
      $res = $conn->query($sql);
      respond($res ? $res->fetch_all(MYSQLI_ASSOC) : []);
    } break;

    /* ---------- KATEGORI ---------- */
    case 'readKategori': {
      $res = $conn->query("SELECT id_kategori, kategori FROM kategori ORDER BY id_kategori ASC");
      if ($res === false) respond(["error"=>"Query failed: ".$conn->error],500);
      respond($res->fetch_all(MYSQLI_ASSOC));
    } break;

    /* ---------- JENIS SAMPAH ---------- */
    case 'readSampah': {
      $res = $conn->query("SELECT id_jenis, jenis, harga, id_kategori FROM jenis_sampah ORDER BY id_jenis ASC");
      if ($res === false) respond(["error"=>"Query failed: ".$conn->error],500);
      respond($res->fetch_all(MYSQLI_ASSOC));
    } break;

    case 'createJenis': {
      $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
      $nama_jenis  = trim($_POST['nama_jenis'] ?? '');
      $harga       = (int) ($_POST['harga'] ?? 0);
      if ($id_kategori<=0 || $nama_jenis==='' || $harga<=0) respond(["error"=>"Data jenis sampah tidak lengkap/valid."],400);

      $cekKat = $conn->prepare("SELECT id_kategori FROM kategori WHERE id_kategori=? LIMIT 1");
      $cekKat->bind_param("i",$id_kategori); $cekKat->execute();
      if ($cekKat->get_result()->num_rows===0) respond(["error"=>"Kategori tidak valid."],400);

      $ins = $conn->prepare("INSERT INTO jenis_sampah (id_kategori, jenis, harga) VALUES (?, ?, ?)");
      $ins->bind_param("isi",$id_kategori,$nama_jenis,$harga);
      if ($ins->execute()) respond(["success"=>true]);
      respond(["error"=>"Gagal menambah jenis sampah: ".$ins->error],500);
    } break;

    case 'updateJenis': {
      $id_jenis    = (int)($_POST['id_jenis'] ?? 0);
      $nama_jenis  = trim($_POST['nama_jenis'] ?? '');
      $harga       = (int)($_POST['harga'] ?? 0);
      $id_kategori = isset($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : 0;

      if ($id_jenis<=0 || $nama_jenis==='' || $harga<=0) respond(["error"=>"Data update jenis tidak lengkap/valid."],400);

      if ($id_kategori>0) {
        $cekK = $conn->prepare("SELECT 1 FROM kategori WHERE id_kategori=? LIMIT 1");
        $cekK->bind_param("i",$id_kategori); $cekK->execute();
        if ($cekK->get_result()->num_rows===0) respond(["error"=>"Kategori tidak valid"],400);
        $upd = $conn->prepare("UPDATE jenis_sampah SET jenis=?, harga=?, id_kategori=? WHERE id_jenis=?");
        $upd->bind_param("siii",$nama_jenis,$harga,$id_kategori,$id_jenis);
      } else {
        $upd = $conn->prepare("UPDATE jenis_sampah SET jenis=?, harga=? WHERE id_jenis=?");
        $upd->bind_param("sii",$nama_jenis,$harga,$id_jenis);
      }
      if ($upd->execute()) respond(["success"=>true]);
      respond(["error"=>"Gagal update jenis: ".$upd->error],500);
    } break;

    case 'deleteJenis': {
      $id_jenis = (int)($_POST['id_jenis'] ?? 0);
      if ($id_jenis<=0) respond(["error"=>"ID jenis tidak valid."],400);

      $cek = $conn->prepare("SELECT COUNT(*) c FROM `transaction` WHERE id_jenis=?");
      $cek->bind_param("i",$id_jenis); $cek->execute();
      if ((int)$cek->get_result()->fetch_assoc()['c'] > 0)
        respond(["error"=>"Tidak bisa menghapus: jenis sudah dipakai pada transaksi."],400);

      $del = $conn->prepare("DELETE FROM jenis_sampah WHERE id_jenis=?");
      $del->bind_param("i",$id_jenis);
      if ($del->execute()) respond(["success"=>true]);
      respond(["error"=>"Gagal menghapus jenis: ".$del->error],500);
    } break;

    /* ---------- GANTI PASSWORD ADMIN ---------- */
    case 'changePassword': {
      $old = $_POST['old_password'] ?? '';
      $new = $_POST['new_password'] ?? '';

      if (strlen(trim($new)) < 6) respond(["success"=>false,"error"=>"Minimal 6 karakter."],400);
      if ($old === $new) respond(["success"=>false,"error"=>"Password baru tidak boleh sama dengan password lama."],400);

      $id_admin = (int)($_SESSION['id_user'] ?? 0);
      if ($id_admin <= 0) respond(["error"=>"Unauthorized"],403);

      $st = $conn->prepare("SELECT password FROM account WHERE id_user=? LIMIT 1");
      $st->bind_param("i",$id_admin);
      $st->execute(); $st->bind_result($pwd_db);
      if(!$st->fetch()){ $st->close(); respond(["success"=>false,"error"=>"User tidak ditemukan."],404); }
      $st->close();

      $valid = false;
      if (strpos($pwd_db,'$2y$')===0) {
        $valid = password_verify($old,$pwd_db);
      } else {
        $valid = hash_equals($pwd_db, $old);
      }
      if (!$valid) respond(["success"=>false,"error"=>"Password lama salah."],400);

      $hash = password_hash($new, PASSWORD_DEFAULT);
      $up = $conn->prepare("UPDATE account SET password=? WHERE id_user=?");
      $up->bind_param("si",$hash,$id_admin);
      $ok = $up->execute(); $up->close();

      respond(["success"=>$ok]);
    } break;

    default:
      respond(["error"=>"Invalid action"],400);
  }
  exit();
}

/* =======================================================================
   Render Halaman (no ?action)
======================================================================= */
$css  = 'admin.css?v='   . (file_exists('admin.css')   ? filemtime('admin.css')   : time());
$js1  = 'admin.js?v='    . (file_exists('admin.js')    ? filemtime('admin.js')    : time());
$js2  = 'admin.ui.js?v=' . (file_exists('admin.ui.js') ? filemtime('admin.ui.js') : time());
?>
<!DOCTYPE html>
<html lang="id" class="no-js">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="x-ua-compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Admin - Bank Sampah Karangsewu</title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $css ?>">
</head>
<body>
  <!-- Header -->
  <header class="a-header" id="topHeader">
    <div class="brand">
      <button id="navToggle" class="icon-btn" aria-label="Menu" aria-controls="mainNav" aria-expanded="false">☰</button>
      <span>🌱 Bank Sampah Karangsewu</span>
    </div>
    <nav id="mainNav" class="a-nav">
      <span class="badge">🔒 Role: Admin</span>
      <a class="nav-link" href="#home">Home</a>
      <a class="nav-link" href="#transaksi">Transaksi</a>
      <a class="nav-link" href="#users">User</a>
      <a class="nav-link" href="#kontak">Kontak</a>
      <button type="button" class="btn ghost" id="openPw">Ganti Password</button>
      <a href="logout.php" class="btn danger">Keluar</a>
    </nav>
  </header>

  <!-- Hero -->
  <section class="hero" id="home" aria-label="Selamat Datang">
    <div class="hero-inner">
      <h1>Selamat Datang, <span><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></span></h1>
      <p>Kelola transaksi, jenis sampah, kategori, dan user dengan tampilan ringan dan responsif.</p>
    </div>
  </section>

  <!-- Main -->
  <main class="a-main">
    <section class="grid two" id="transaksi">
      <!-- Form Transaksi -->
      <form id="transaksiForm" class="card elevate fade-in" autocomplete="off">
        <div class="card-head"><h2>🧾 Form Transaksi</h2></div>

        <label>Nama penyetor
          <input type="text" id="nama" placeholder="Nama penyetor" required>
        </label>

        <label>Jenis sampah
          <select id="jenis" required></select>
        </label>

        <label>Jumlah (kg)
          <input type="number" id="jumlah" min="1" step="1" placeholder="Jumlah (kg)" required>
        </label>

        <!-- KALKULASI LIVE -->
        <div id="calcBox" class="calc" aria-live="polite">
          <div>Harga/kg: <strong id="hargaPerKg">Rp 0</strong></div>
          <div>Total: <strong id="totalNominal">Rp 0</strong></div>
        </div>

        <div class="actions">
          <button type="submit" class="btn">Tambah</button>
          <button type="reset" class="btn ghost">Reset</button>
        </div>
      </form>

      <!-- Daftar Jenis + Form Tambah -->
      <div class="card elevate fade-in delay-1">
        <div class="card-head"><h2>♻️ Daftar Jenis Sampah</h2></div>

        <div class="table-wrap">
          <table id="sampahTable" class="a-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama Jenis</th>
                <th>Harga/kg</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody><!-- by JS --></tbody>
          </table>
        </div>

        <hr style="border:none;border-top:1px solid var(--line);margin:16px 0">

        <form id="sampahForm" class="grid two gap" autocomplete="off">
          <label>Kategori
            <select id="sampah_kategori" required></select>
          </label>
          <label>Nama Jenis
            <input type="text" id="sampah_nama" placeholder="contoh: Botol PET" required>
          </label>
          <label>Harga/kg
            <input type="number" id="sampah_harga" min="100" step="50" placeholder="contoh: 3000" required>
          </label>
          <div class="actions">
            <button type="submit" class="btn">Tambah Jenis</button>
          </div>
        </form>
      </div>
    </section>

    <!-- User Directory + Form Tambah User -->
    <section class="card elevate fade-in delay-2" id="users">
      <div class="card-head">
        <h2>👥 Daftar User</h2>
        <div class="filters">
          <div class="search">
            <input id="userSearch" type="text" placeholder="Cari id/nama/no hp/alamat…" autocomplete="off">
            <button id="clearSearch" class="clear" title="Bersihkan">✕</button>
          </div>
          <label class="switch">
            <input type="checkbox" id="onlyUsers" checked>
            <span>Hanya role: user</span>
          </label>
        </div>
      </div>

      <div class="table-wrap">
        <table id="userTable" class="a-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama</th>
              <th>No HP</th>
              <th>Alamat</th>
              <th>Role</th>
            </tr>
          </thead>
          <tbody><!-- by JS --></tbody>
        </table>
      </div>

      <div class="table-foot">
        <div id="userCount" class="muted">0 user</div>
      </div>

      <hr style="border:none;border-top:1px solid var(--line);margin:16px 0">

      <form id="userForm" class="grid two gap" autocomplete="off">
        <label>Nama
          <input type="text" id="user_nama" required placeholder="Nama lengkap">
        </label>
        <label>No HP
          <input type="text" id="user_hp" required placeholder="08xxxx">
        </label>
        <label class="full">Alamat
          <input type="text" id="user_alamat" placeholder="(opsional)">
        </label>
        <div class="actions"><button type="submit" class="btn">Tambah User</button></div>
      </form>
    </section>

    <!-- Riwayat Transaksi -->
    <section class="card elevate fade-in" id="history">
      <div class="card-head"><h2>🗂️ Riwayat Transaksi (2 bulan terakhir)</h2></div>
      <div class="table-wrap">
        <table id="riwayat" class="a-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama</th>
              <th>Jenis Sampah</th>
              <th>Jumlah (kg)</th>
              <th>Tanggal</th>
              <th class="hide-mobile">Aksi</th>
            </tr>
          </thead>
          <tbody><!-- by JS --></tbody>
        </table>
      </div>
    </section>
  </main>

  <footer id="kontak" class="a-footer">
    <div>📍 Desa Karangsewu &nbsp;|&nbsp; 🌐 @banksampahkarangsewu</div>
    <div>© <?= date('Y'); ?> Bank Sampah Karangsewu</div>
  </footer>

  <!-- Modal Ganti Password (Admin) -->
  <div class="modal" id="pwModal" aria-hidden="true" role="dialog" aria-label="Ganti Password">
    <div class="modal__content">
      <h2 style="margin:0 0 8px">Ganti Password</h2>
      <form id="pwForm" autocomplete="off">
        <label>Password lama
          <span class="pw-wrap">
            <input type="password" id="old_password" name="old_password" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" aria-label="Tampilkan/Sembunyikan">👁</button>
          </span>
        </label>

        <label>Password baru (min 6)
          <span class="pw-wrap">
            <input type="password" id="new_password" name="new_password" minlength="6" autocomplete="new-password" required>
            <button type="button" class="pw-toggle" aria-label="Tampilkan/Sembunyikan">👁</button>
          </span>
        </label>

        <div class="pw-meter" aria-hidden="true">
          <div class="pw-meter__bar" id="pwBar"></div>
          <div class="pw-meter__text" id="pwText">Kekuatan: -</div>
        </div>

        <label>Konfirmasi password baru
          <span class="pw-wrap">
            <input type="password" id="confirm_password" minlength="6" autocomplete="new-password" required>
            <button type="button" class="pw-toggle" aria-label="Tampilkan/Sembunyikan">👁</button>
          </span>
        </label>

        <div class="pw-hint">Tips: gunakan huruf besar/kecil, angka, dan simbol untuk kata sandi yang kuat.</div>

        <div class="actions" style="margin-top:12px">
          <button type="submit" class="btn" id="pwSave">Simpan</button>
          <button type="button" class="btn ghost" id="pwCancel">Batal</button>
        </div>
      </form>
    </div>
    <div class="modal__backdrop" id="pwBackdrop"></div>
  </div>

  <script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js-anim');</script>
  <script src="<?= $js1 ?>" defer></script>
  <script src="<?= $js2 ?>" defer></script>
</body>
</html>
