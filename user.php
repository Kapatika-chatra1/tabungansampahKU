<?php
session_start();

// cek apakah sudah login & role user
if (!isset($_SESSION['id_akun']) || $_SESSION['role'] !== 'user') {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard User</title>
</head>
<body>
    <h1>Halo <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p>Ini adalah halaman user biasa.</p>
    <a href="logout.php">Keluar</a>
</body>
</html>
