<?php
session_start();
require 'koneksi.php';

// Harus login & role user
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];

// --- Ambil saldo (prepared) ---
$saldo = 0;
if ($stmt = $conn->prepare("SELECT saldo FROM saldo WHERE id_user = ? LIMIT 1")) {
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $stmt->bind_result($saldo_db);
    if ($stmt->fetch()) $saldo = (float)$saldo_db;
    $stmt->close();
}

// --- Ambil riwayat transaksi (prepared) ---
$riwayat = [];
$sql = "
  SELECT t.id_trans AS id_transaksi,
         a.nama     AS nama_user,
         t.jenis_sampah,
         t.jumlah_setoran
  FROM `transaction` t
  JOIN account a ON t.id_user = a.id_user
  WHERE t.id_user = ?
  ORDER BY t.id_trans DESC
";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $riwayat[] = $row;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Dashboard – Bank Sampah Karangsewu</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png" />
  <link rel="stylesheet" href="user.css?v=3" />

  <!-- Leaflet (peta) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
</head>
<body>
  <header>
    <h1>🌱 Bank Sampah Karangsewu</h1>
  </header>

  <div class="container">
    <div class="welcome">
      Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> 👋
      <br/>Selamat datang di dashboard Anda.
    </div>

    <div class="saldo-card">
      <h2>Total Saldo Anda</h2>
      <p>Rp <?= number_format($saldo, 0, ',', '.') ?></p>
    </div>

    <div class="table-card">
      <h3>📜 Riwayat Penjualan Sampah</h3>
      <table aria-label="Tabel riwayat transaksi">
        <thead>
          <tr>
            <th>ID Transaksi</th>
            <th>Nama</th>
            <th>Jenis Sampah</th>
            <th>Jumlah Setoran</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$riwayat): ?>
          <tr><td colspan="4" style="text-align:center; opacity:.7;">Belum ada transaksi.</td></tr>
        <?php else: ?>
          <?php foreach ($riwayat as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['id_transaksi']) ?></td>
              <td><?= htmlspecialchars($r['nama_user']) ?></td>
              <td><?= htmlspecialchars($r['jenis_sampah']) ?></td>
              <td><?= htmlspecialchars($r['jumlah_setoran']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Peta (Leaflet) -->
    <section class="maps" id="maps" aria-label="Lokasi Pengepul">
      <h2>Lokasi Pengepul</h2>
      <div id="map"></div>
    </section>

    <div class="actions">
      <a href="logout.php" class="btn btn-danger">🚪 Keluar</a>
    </div>
  </div>

  <footer class="footer">
    <p>📍 Desa Karangsewu | 🌐 @banksampahkarangsewu</p>
    <p>© 2025 Bank Sampah Karangsewu</p>
  </footer>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

  <!-- Inisialisasi peta -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Center area Karangsewu
      const map = L.map('map').setView([-7.9539772, 110.1813977], 11);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      // Marker contoh (bisa tambah array marker kalau perlu)
      L.marker([-7.9490876, 110.1975741])
        .addTo(map)
        .bindPopup('Titik Bank Sampah Sorogaten');

      // Contoh titik lain (opsional)
      // L.marker([-7.9605, 110.1702]).addTo(map).bindPopup('Pengepul A');
    });
  </script>
</body>
</html>
