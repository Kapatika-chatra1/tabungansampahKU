<?php
/* -------------------------------------------------------
   USER DASHBOARD — Bank Sampah Karangsewu
   UI + API ringan (?action=...)
-------------------------------------------------------*/

/* ===== Session cookie di-root (/) agar lintas folder ===== */
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
if (session_status() === PHP_SESSION_NONE) session_start();

require 'koneksi.php';

/* ==== Guard: wajib login role user ==== */
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'user') {
  header("Location: login.php");
  exit();
}
$id_user = (int)$_SESSION['id_user'];

/* ==== Helpers ==== */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function out_json($a){ header('Content-Type: application/json; charset=utf-8'); echo json_encode($a); exit(); }

/* =======================================================
   API ringan (JSON/CSV) — gunakan ?action=...
======================================================= */
if (isset($_GET['action'])) {
  $action = $_GET['action'];

  // 1) Statistik (saldo, total transaksi, jenis terbanyak)
  if ($action === 'getStats') {
    // saldo
    $saldo = 0.0;
    if ($st = $conn->prepare("SELECT saldo FROM saldo WHERE id_user=? LIMIT 1")) {
      $st->bind_param("i", $id_user);
      $st->execute();
      $st->bind_result($saldo_db);
      if ($st->fetch()) $saldo = (float)$saldo_db;
      $st->close();
    }
    // total transaksi
    $total = 0;
    if ($st = $conn->prepare("SELECT COUNT(*) FROM `transaction` WHERE id_user=?")) {
      $st->bind_param("i", $id_user);
      $st->execute(); $st->bind_result($c); if ($st->fetch()) $total = (int)$c;
      $st->close();
    }
    // jenis terbanyak
    $top = '-';
    $sql = "SELECT j.jenis, SUM(t.jumlah_setoran) kg
            FROM `transaction` t
            LEFT JOIN jenis_sampah j ON j.id_jenis=t.id_jenis
            WHERE t.id_user=?
            GROUP BY j.jenis
            ORDER BY kg DESC
            LIMIT 1";
    if ($st = $conn->prepare($sql)) {
      $st->bind_param("i", $id_user);
      $st->execute();
      $r = $st->get_result();
      if ($row = $r->fetch_assoc()) $top = $row['jenis'] ?? '-';
      $st->close();
    }
    out_json(['saldo'=>$saldo, 'total'=>$total, 'top'=>$top]);
  }

  // 2) Opsi jenis (dropdown filter)
  if ($action === 'jenisOptions') {
    $rs = $conn->query("SELECT id_jenis, jenis FROM jenis_sampah ORDER BY jenis ASC");
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    out_json($rows);
  }

  // 3) Daftar transaksi user (search + filter)
  if ($action === 'listTransactions') {
    $q     = trim($_GET['q'] ?? '');
    $jenis = (int)($_GET['jenis'] ?? 0);

    $sql = "SELECT t.id_trans AS id_transaksi,
                   a.nama     AS nama_user,
                   COALESCE(j.jenis,'-') AS jenis_sampah,
                   t.jumlah_setoran,
                   t.tanggal
            FROM `transaction` t
            JOIN account a ON a.id_user=t.id_user
            LEFT JOIN jenis_sampah j ON j.id_jenis=t.id_jenis
            WHERE t.id_user=?";
    $types='i'; $params=[$id_user];

    if ($jenis>0){ $sql.=" AND t.id_jenis=?"; $types.='i'; $params[]=$jenis; }
    if ($q!==''){ $sql.=" AND (a.nama LIKE ? OR j.jenis LIKE ?)"; $types.='ss'; $like="%{$q}%"; $params[]=$like; $params[]=$like; }
    $sql.=" ORDER BY t.tanggal DESC, t.id_trans DESC";

    $st=$conn->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    out_json($rows);
  }

  // 4) Export CSV
  if ($action === 'exportCSV') {
    $q     = trim($_GET['q'] ?? '');
    $jenis = (int)($_GET['jenis'] ?? 0);

    $sql = "SELECT t.id_trans AS id_transaksi,
                   a.nama     AS nama_user,
                   COALESCE(j.jenis,'-') AS jenis_sampah,
                   t.jumlah_setoran,
                   t.tanggal
            FROM `transaction` t
            JOIN account a ON a.id_user=t.id_user
            LEFT JOIN jenis_sampah j ON j.id_jenis=t.id_jenis
            WHERE t.id_user=?";
    $types='i'; $params=[$id_user];

    if ($jenis>0){ $sql.=" AND t.id_jenis=?"; $types.='i'; $params[]=$jenis; }
    if ($q!==''){ $sql.=" AND (a.nama LIKE ? OR j.jenis LIKE ?)"; $types.='ss'; $like="%{$q}%"; $params[]=$like; $params[]=$like; }
    $sql.=" ORDER BY t.tanggal DESC, t.id_trans DESC";

    $st=$conn->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $res=$st->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=riwayat_user_'.$id_user.'.csv');
    $out=fopen('php://output','w');
    fputcsv($out, ['ID Transaksi','Nama','Jenis Sampah','Jumlah (kg)','Tanggal']);
    while($r=$res->fetch_assoc()){
      fputcsv($out, [$r['id_transaksi'],$r['nama_user'],$r['jenis_sampah'],$r['jumlah_setoran'],$r['tanggal']]);
    }
    fclose($out); exit();
  }

  // 5) Titik peta aktif
  if ($action === 'getPoints') {
    $rs = $conn->query("SELECT id,name,type,phone,address,lat,lng,active FROM location_points WHERE active=1 ORDER BY id DESC");
    $rows = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
    out_json($rows);
  }

  // 6) Ganti password
  if ($action === 'changePassword' && $_SERVER['REQUEST_METHOD']==='POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    if (strlen(trim($new)) < 6) out_json(['success'=>false,'error'=>'Minimal 6 karakter.']);

    // ambil password lama
    $st = $conn->prepare("SELECT password FROM account WHERE id_user=? LIMIT 1");
    $st->bind_param("i",$id_user);
    $st->execute(); $st->bind_result($pwd_db);
    if(!$st->fetch()){ $st->close(); out_json(['success'=>false,'error'=>'User tidak ditemukan.']); }
    $st->close();

    $valid = false;
    if (strpos($pwd_db,'$2y$')===0) { // hashed
      $valid = password_verify($old,$pwd_db);
    } else {
      $valid = hash_equals($pwd_db, $old); // legacy plaintext
    }
    if(!$valid) out_json(['success'=>false,'error'=>'Password lama salah.']);

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $up = $conn->prepare("UPDATE account SET password=? WHERE id_user=?");
    $up->bind_param("si",$hash,$id_user);
    $ok = $up->execute();
    $up->close();
    out_json(['success'=>$ok]);
  }

  // default
  out_json(['error'=>'Invalid action']);
}

/* ====== Initial saldo untuk render nilai awal kartu ====== */
$saldo = 0.0;
if ($st=$conn->prepare("SELECT saldo FROM saldo WHERE id_user=? LIMIT 1")){
  $st->bind_param("i",$id_user);
  $st->execute(); $st->bind_result($s);
  if($st->fetch()) $saldo=(float)$s;
  $st->close();
}

/* ====== Render Halaman ====== */
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard User — Bank Sampah Karangsewu</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png"/>
  <link rel="stylesheet" href="user.css?v=11"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
</head>
<body>
  <!-- Header -->
  <header class="topbar">
    <div class="brand"><span class="logo">🌱</span> Bank Sampah Karangsewu</div>
    <div class="right">
      <span class="role-pill">👤 Role: User</span>
      <button id="btnChangePass" class="btn ghost">Ganti Password</button>
      <a class="btn danger" href="logout.php">Keluar</a>
    </div>
  </header>

  <!-- Welcome -->
  <div class="welcome">
    Halo, <b><?= h($_SESSION['nama']) ?></b> 👋 <span>selamat datang di dashboard Anda.</span>
  </div>

  <!-- Stat Cards -->
  <section class="stats">
    <div class="stat-card">
      <div class="icon">💰</div>
      <div>
        <div class="label">Saldo</div>
        <div class="value" id="statSaldo">Rp <?= number_format($saldo,0,',','.') ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="icon">🧾</div>
      <div>
        <div class="label">Total Transaksi</div>
        <div class="value" id="statTotal">0</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="icon">🏅</div>
      <div>
        <div class="label">Jenis Terbanyak</div>
        <div class="value" id="statTop">-</div>
      </div>
    </div>
  </section>

  <!-- Main Grid -->
  <section class="grid">
    <!-- Riwayat -->
    <div class="card">
      <div class="card-head">
        <h3>🗂️ Riwayat Penjualan Sampah</h3>
        <div class="tools">
          <div class="search">
            <input id="q" type="text" placeholder="Cari…" spellcheck="false">
            <button id="clearQ" class="x" title="Bersihkan">×</button>
          </div>
          <select id="jenisFilter" class="select"></select>
          <button id="btnCSV" class="btn ghost">⬇️ Unduh CSV</button>
          <button id="btnReset" class="btn ghost">⟲ Reset</button>
        </div>
      </div>
      <div class="table-wrap">
        <table id="tbl" aria-label="Tabel Riwayat">
          <thead>
            <tr>
              <th>ID Transaksi</th>
              <th>Nama</th>
              <th>Jenis Sampah</th>
              <th>Jumlah (kg)</th>
              <th>Tanggal</th>
            </tr>
          </thead>
          <tbody id="tbody">
            <tr><td colspan="5" class="empty">Memuat…</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Peta -->
    <div class="card">
      <div class="card-head"><h3>🗺️ Lokasi Pengepul</h3></div>
      <div id="map" class="map"></div>
      <div class="hint">Titik diambil dari <code>location_points</code> (Super Admin).</div>
    </div>
  </section>

  <footer class="footer">
    <div>📍 Desa Karangsewu</div>
    <div>🌐 @banksampahkarangsewu</div>
  </footer>

  <!-- Modal Ganti Password -->
  <div class="modal" id="modalPass" aria-hidden="true" role="dialog" aria-label="Ganti Password">
    <div class="modal__content">
      <h3>Ganti Password</h3>
      <form id="passForm" autocomplete="off">
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
            <!-- sengaja TANPA name agar tidak dikirim ke server -->
            <input type="password" id="confirm_password" minlength="6" autocomplete="new-password" required>
            <button type="button" class="pw-toggle" aria-label="Tampilkan/Sembunyikan">👁</button>
          </span>
        </label>

        <div class="pw-hint">Tips: pakai kombinasi huruf besar/kecil, angka, dan simbol.</div>

        <div class="actions">
          <button type="submit" class="btn" id="btnSavePw">Simpan</button>
          <button type="button" class="btn ghost" id="closePass">Batal</button>
        </div>
      </form>
    </div>
    <div class="modal__backdrop" id="backdrop"></div>
  </div>

  <!-- Toast -->
  <div id="toast" class="toast" aria-live="polite"></div>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="user.js?v=11" defer></script>
</body>
</html>
