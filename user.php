<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php"); exit();
}
$id_user = (int)$_SESSION['id_user'];

$saldo = 0;
if ($stmt = $conn->prepare("SELECT saldo FROM saldo WHERE id_user = ? LIMIT 1")) {
  $stmt->bind_param("i",$id_user); $stmt->execute(); $stmt->bind_result($sdb);
  if ($stmt->fetch()) $saldo = (float)$sdb; $stmt->close();
}

$riwayat = [];
$sql = "SELECT t.id_trans AS id_transaksi,
       a.nama AS nama_user,
       j.jenis AS jenis_sampah,
       t.jumlah_setoran
FROM `transaction` t
JOIN account a ON t.id_user = a.id_user
JOIN jenis_sampah j ON t.id_jenis = j.id_jenis
WHERE t.id_user = ?
ORDER BY t.id_trans DESC;
";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i",$id_user); $stmt->execute();
  $r = $stmt->get_result(); while($row=$r->fetch_assoc()) $riwayat[]=$row; $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Dashboard – Bank Sampah Karangsewu</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png"/>
  <link rel="stylesheet" href="user.css?v=7"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  
</head>
<body>

  <!-- TOPBAR -->
  <div class="header">
    <div class="header__inner">
      <div class="brand">
        <div class="brand__emoji">🌱</div>
        <div class="brand__title">Bank Sampah Karangsewu</div>
      </div>
      <a class="btn btn--danger" href="logout.php">🚪 Keluar</a>
    </div>
  </div>

  <div class="container">
    <!-- HERO -->
    <section class="hero">
      <div>
        <span class="hero__text">Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> 👋</span>
        <span class="hero__sub">selamat datang di dashboard Anda.</span>
      </div>
      <div class="hero__tools">
        <span class="badge">👤 Role: User</span>
        <button class="btn btn--ghost" id="btnOpenPassword">🔑 Ganti Password</button>
      </div>
    </section>

    <!-- STATS -->
    <section class="stats" aria-label="Statistik">
      <div class="stat">
        <div class="stat__icon">💰</div>
        <div class="stat__labels">
          <div class="stat__title">Saldo</div>
          <div class="stat__value" id="stat-saldo">Rp <?= number_format($saldo,0,',','.') ?></div>
        </div>
      </div>
      <div class="stat">
        <div class="stat__icon">🧾</div>
        <div class="stat__labels">
          <div class="stat__title">Total Transaksi</div>
          <div class="stat__value" id="stat-transaksi">0</div>
        </div>
      </div>
      <div class="stat">
        <div class="stat__icon">🥇</div>
        <div class="stat__labels">
          <div class="stat__title">Jenis Terbanyak</div>
          <div class="stat__value" id="stat-jenis">—</div>
        </div>
      </div>
    </section>

    <!-- GRID -->
    <section class="grid">
      <!-- TABEL -->
      <div class="card">
        <div class="card__header">
          <h3 class="card__title">📜 Riwayat Penjualan Sampah</h3>
          <div class="toolbar">
            <input class="input" type="search" id="searchInput" placeholder="Cari nama / jenis…" aria-label="Pencarian"/>
            <select class="select" id="filterJenis" aria-label="Filter jenis">
              <option value="">Semua Jenis</option>
            </select>
            <button class="btn btn--ghost" id="btnDownload">⬇️ Unduh CSV</button>
            <button class="btn btn--ghost" id="btnReset">🔄 Reset</button>
          </div>
        </div>

        <div class="table-wrap">
          <table id="tabelRiwayat" aria-label="Tabel riwayat transaksi">
            <thead>
              <tr>
                <th>ID Transaksi</th>
                <th>Nama</th>
                <th>Jenis Sampah</th>
                <th>Jumlah Setoran</th>
              </tr>
            </thead>
            <tbody>
            <?php if(!$riwayat): ?>
              <tr><td colspan="4" style="text-align:center; padding:22px; color:var(--muted);">Belum ada transaksi.</td></tr>
            <?php else: foreach($riwayat as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['id_transaksi']) ?></td>
                <td><?= htmlspecialchars($r['nama_user']) ?></td>
                <td><?= htmlspecialchars($r['jenis_sampah']) ?></td>
                <td><?= number_format((int)$r['jumlah_setoran'],0,',','.') ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- MAP -->
      <div class="card" style="padding:14px;">
        <div class="card__header" style="border-bottom:none; padding:0 0 10px 0;">
          <h3 class="card__title" style="margin:0;">🗺️ Lokasi Pengepul</h3>
        </div>
        <div id="map"></div>
      </div>
    </section>
  </div>

  <footer class="footer">
    <p>📍 Desa Karangsewu &nbsp;|&nbsp; 🌐 @banksampahkarangsewu</p>
    <p>© 2025 Bank Sampah Karangsewu</p>
  </footer>

  <!-- MODAL GANTI PASSWORD -->
  <div id="modalPassword" class="modal">
    <div class="modal__content">
      <h3>🔑 Ganti Password</h3>
      <form id="formPassword">
        <input type="password" name="old_password" placeholder="Password Sekarang" required>
        <input type="password" name="new_password" placeholder="Password Baru" required>
        <input type="password" name="confirm_password" placeholder="Konfirmasi Password Baru" required>
        <div id="msgPassword" style="margin:8px 0; font-size:14px;"></div>
        <div class="modal__actions">
          <button type="submit" class="btn">Simpan</button>
          <button type="button" class="btn btn--ghost" id="btnClosePassword">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="user.js"></script>
</body>
</html>
