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

// Ambil id_user dari session
$id_user = $_SESSION['id_user'];

// Ambil data saldo user
$query = "SELECT saldo FROM saldo WHERE id_user = '$id_user' LIMIT 1";
$result = mysqli_query($conn, $query);

$saldo = 0;
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $saldo = $row['saldo'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background: #f9f9f9;
        }
        h1 {
            color: #333;
        }
        .saldo-box {
            margin-top: 20px;
            padding: 15px;
            background: #e6ffe6;
            border: 1px solid #8bc34a;
            border-radius: 8px;
        }
        .logout {
            margin-top: 20px;
            display: inline-block;
            padding: 10px 15px;
            background: #f44336;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
        <p>Selamat datang di dashboard user.</p>

        <div class="saldo-box">
            <h2>Saldo Anda</h2>
            <p><strong>Rp <?php echo number_format($saldo, 0, ',', '.'); ?></strong></p>
        </div>

        <a class="logout" href="logout.php">Keluar</a>
    </div>
</body>
</html>
