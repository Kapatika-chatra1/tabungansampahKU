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

// Ambil riwayat transaksi dari database
$query_riwayat = "SELECT * FROM `transaction` WHERE id_user = '$id_user'";
$result_riwayat = mysqli_query($conn, $query_riwayat);

$riwayat = [];
if ($result_riwayat && mysqli_num_rows($result_riwayat) > 0) {
    while ($row = mysqli_fetch_assoc($result_riwayat)) {
        $riwayat[] = $row;
    }
}
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
          <th>ID Transaksi</th>
          <th>ID User</th>
          <th>No. HP</th>
          <th>Jenis Sampah</th>
          <th>Jumlah Setoran</th>
        </tr>
        <?php foreach ($riwayat as $r): ?>
          <tr>
            <td><?php echo $r['id_trans']; ?></td>
            <td><?php echo $r['id_user']; ?></td>
            <td><?php echo $r['no_hp']; ?></td>
            <td><?php echo $r['jenis_sampah']; ?></td>
            <td><?php echo $r['jumlah_setoran']; ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <div class="actions">
      <a href="logout.php" class="btn btn-danger">🚪 Keluar</a>
    </div>
  </div>
  <script>
    
  //setInterval(() => {
    //document.body.style.backgroundImage = 
      //"url('https://picsum.photos/1920/1080?random&t=" + new Date().getTime() + "')";
  //}, 10000); // ganti setiap 1 detik
</script>

</body>
</html>
