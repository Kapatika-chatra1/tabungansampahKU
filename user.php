<?php
session_start();
require 'koneksi.php';

// cek apakah sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

// cek role user
if ($_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Ambil data saldo user
$query = "SELECT saldo FROM saldo WHERE id_user = '$id_user' LIMIT 1";
$result = mysqli_query($conn, $query);

$saldo = 0;
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $saldo = $row['saldo'];
}

// Dummy riwayat transaksi
$riwayat = [
    ["tanggal" => "20/01/2024", "jenis" => "Botol Plastik", "berat" => "2.5", "harga" => 3000, "total" => 7500, "status" => "Selesai"],
    ["tanggal" => "18/01/2024", "jenis" => "Kertas", "berat" => "3", "harga" => 2000, "total" => 6000, "status" => "Selesai"],
    ["tanggal" => "15/01/2024", "jenis" => "Kardus", "berat" => "1.8", "harga" => 2500, "total" => 4500, "status" => "Pending"],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard - Bank Sampah Karangsewu</title>
  <link rel="stylesheet" href="user.css">
</head>
<body>
  <header>
    <h1>🌱 Bank Sampah Karangsewu</h1>
  </header>

  <div class="container">
    <div class="welcome">
      Halo, <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong> 👋  
      <br>Selamat datang di dashboard user Anda.
    </div>

    <div class="saldo-card">
      <h2>Total Saldo Anda</h2>
      <p>Rp <?php echo number_format($saldo, 0, ',', '.'); ?></p>
    </div>

    <!-- Riwayat Transaksi -->
    <div class="table-card">
      <h3>📜 Riwayat Penjualan Sampah</h3>
      <table>
        <tr>
          <th>Tanggal</th>
          <th>Jenis Sampah</th>
          <th>Berat (kg)</th>
          <th>Harga</th>
          <th>Total</th>
          <th>Status</th>
        </tr>
        <?php foreach ($riwayat as $r): ?>
          <tr>
            <td><?php echo $r['tanggal']; ?></td>
            <td><?php echo $r['jenis']; ?></td>
            <td><?php echo $r['berat']; ?></td>
            <td>Rp <?php echo number_format($r['harga'], 0, ',', '.'); ?></td>
            <td class="total">Rp <?php echo number_format($r['total'], 0, ',', '.'); ?></td>
            <td>
              <?php if ($r['status'] == "Selesai"): ?>
                <span class="status success">Selesai</span>
              <?php else: ?>
                <span class="status pending">Pending</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <div class="actions">
      <a href="logout.php" class="btn btn-danger">🚪 Keluar</a>
    </div>
  </div>
  <script>
    
  setInterval(() => {
    document.body.style.backgroundImage = 
      "url('https://picsum.photos/1920/1080?random&t=" + new Date().getTime() + "')";
  }, 1000); // ganti setiap 1 detik
</script>

</body>
</html>
