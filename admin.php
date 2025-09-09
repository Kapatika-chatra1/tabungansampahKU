<?php
session_start();
require 'koneksi.php';

// pastikan hanya admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    // ================= CREATE TRANSAKSI =================
    if ($action === 'create') {
        $nama    = trim($_POST['nama'] ?? '');
        $id_jenis= (int) ($_POST['id_jenis'] ?? 0);
        $jumlah  = (int) ($_POST['jumlah'] ?? 0);

        if ($nama === '' || $id_jenis <= 0 || $jumlah <= 0) {
            echo json_encode(["error" => "Data transaksi tidak lengkap atau jumlah tidak valid."]);
            exit();
        }

        // ambil user
        $q = $conn->prepare("SELECT id_user, no_hp, nama FROM account WHERE nama=? LIMIT 1");
        $q->bind_param("s", $nama);
        $q->execute();
        $res = $q->get_result();
        if ($res->num_rows === 0) {
            echo json_encode(["error" => "User tidak ditemukan"]);
            exit();
        }
        $userRow = $res->fetch_assoc();
        $id_user = (int)$userRow['id_user'];
        $no_hp   = $userRow['no_hp'];
        $user_nama = $userRow['nama'];

        // ambil harga sampah
        $qh = $conn->prepare("SELECT harga FROM jenis_sampah WHERE id_jenis=? LIMIT 1");
        $qh->bind_param("i", $id_jenis);
        $qh->execute();
        $resH = $qh->get_result();
        if ($resH->num_rows === 0) {
            echo json_encode(["error" => "Jenis sampah tidak valid"]);
            exit();
        }
        $harga = (int)$resH->fetch_assoc()['harga'];
        $nominal = $jumlah * $harga;

        $conn->begin_transaction();
        try {
            $insT = $conn->prepare("INSERT INTO `transaction` (id_user, no_hp, id_jenis, jumlah_setoran, tanggal) VALUES (?, ?, ?, ?, NOW())");
            $insT->bind_param("isii", $id_user, $no_hp, $id_jenis, $jumlah);
            $insT->execute();

            $cekS = $conn->prepare("SELECT id_saldo, saldo FROM saldo WHERE id_user=?");
            $cekS->bind_param("i", $id_user);
            $cekS->execute();
            $resS = $cekS->get_result();

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
            echo json_encode(["success" => true]);
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(["error" => "Gagal menyimpan transaksi: " . $e->getMessage()]);
        }
        exit();
    }

    // ================= READ TRANSAKSI =================
    elseif ($action === 'read') {
        $sql = "SELECT 
                    t.id_trans, 
                    a.nama, 
                    j.jenis AS jenis_sampah, 
                    j.harga, 
                    t.jumlah_setoran, 
                    (t.jumlah_setoran * j.harga) AS nominal,
                    t.tanggal
                FROM `transaction` t
                JOIN account a ON t.id_user = a.id_user
                JOIN jenis_sampah j ON t.id_jenis = j.id_jenis
                WHERE t.tanggal >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
                ORDER BY t.id_trans ASC";
        $res = $conn->query($sql);
        $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode($data);
        exit();
    }

    // ================= UPDATE TRANSAKSI =================
    elseif ($action === 'update') {
        $id        = (int) ($_POST['id'] ?? 0);
        $id_jenis  = (int) ($_POST['id_jenis'] ?? 0);
        $jumlah    = (int) ($_POST['jumlah'] ?? 0);

        if ($id <= 0 || $id_jenis <= 0 || $jumlah <= 0) {
            echo json_encode(["error" => "Data update tidak lengkap atau tidak valid."]);
            exit();
        }

        $getOld = $conn->prepare("SELECT id_user, id_jenis, jumlah_setoran FROM `transaction` WHERE id_trans=?");
        $getOld->bind_param("i", $id);
        $getOld->execute();
        $oldRes = $getOld->get_result();
        if ($oldRes->num_rows === 0) {
            echo json_encode(["error" => "Transaksi tidak ditemukan"]);
            exit();
        }
        $old = $oldRes->fetch_assoc();
        $id_user = (int)$old['id_user'];
        $id_jenis_lama = (int)$old['id_jenis'];
        $jumlah_lama = (int)$old['jumlah_setoran'];

        $hL = $conn->query("SELECT harga FROM jenis_sampah WHERE id_jenis=$id_jenis_lama")->fetch_assoc()['harga'] ?? 0;
        $nominal_lama = $jumlah_lama * $hL;

        $hB = $conn->query("SELECT harga FROM jenis_sampah WHERE id_jenis=$id_jenis")->fetch_assoc()['harga'] ?? 0;
        if ($hB <= 0) {
            echo json_encode(["error" => "Jenis sampah baru tidak valid"]);
            exit();
        }
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
            echo json_encode(["success" => true]);
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(["error" => "Gagal update transaksi: " . $e->getMessage()]);
        }
        exit();
    }

    // ================= DELETE TRANSAKSI =================
    elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(["error" => "ID tidak valid."]);
            exit();
        }

        $conn->begin_transaction();
        try {
            $getOld = $conn->prepare("SELECT id_user, id_jenis, jumlah_setoran FROM `transaction` WHERE id_trans=?");
            $getOld->bind_param("i", $id);
            $getOld->execute();
            $oldRes = $getOld->get_result();
            if ($oldRes->num_rows === 0) throw new Exception("Transaksi tidak ditemukan.");

            $old = $oldRes->fetch_assoc();
            $id_user = (int)$old['id_user'];
            $id_jenis = (int)$old['id_jenis'];
            $jumlah_lama = (int)$old['jumlah_setoran'];

            $harga = $conn->query("SELECT harga FROM jenis_sampah WHERE id_jenis=$id_jenis")->fetch_assoc()['harga'] ?? 0;
            $nominal_lama = $jumlah_lama * $harga;

            $del = $conn->prepare("DELETE FROM `transaction` WHERE id_trans=?");
            $del->bind_param("i", $id);
            $del->execute();

            if ($nominal_lama > 0) {
                $cekS = $conn->prepare("SELECT id_saldo, saldo FROM saldo WHERE id_user=?");
                $cekS->bind_param("i", $id_user);
                $cekS->execute();
                $resS = $cekS->get_result();
                if ($resS->num_rows > 0) {
                    $rowS = $resS->fetch_assoc();
                    $newSaldo = max(0, (int)$rowS['saldo'] - $nominal_lama);
                    $updS = $conn->prepare("UPDATE saldo SET saldo=? WHERE id_saldo=?");
                    $updS->bind_param("ii", $newSaldo, $rowS['id_saldo']);
                    $updS->execute();
                }
            }

            $conn->commit();
            echo json_encode(["success" => true]);
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(["error" => "Gagal menghapus transaksi: " . $e->getMessage()]);
        }
        exit();
    }

    // ================= CREATE USER =================
    elseif ($action === 'createUser') {
        $nama   = trim($_POST['nama'] ?? '');
        $no_hp  = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');

        if ($nama === '' || $no_hp === '') {
            echo json_encode(["error" => "Data user tidak lengkap."]);
            exit();
        }

        $cek = $conn->prepare("SELECT id_user FROM account WHERE no_hp=? LIMIT 1");
        $cek->bind_param("s", $no_hp);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            echo json_encode(["error" => "No HP sudah terdaftar."]);
            exit();
        }

        $password = password_hash("user123", PASSWORD_DEFAULT);
        $role = "user";

        $ins = $conn->prepare("INSERT INTO account (nama, no_hp, alamat, password, role) VALUES (?, ?, ?, ?, ?)");
        $ins->bind_param("sssss", $nama, $no_hp, $alamat, $password, $role);
        if ($ins->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["error" => "Gagal menambah user: " . $ins->error]);
        }
        exit();
    }

    // ================= READ KATEGORI =================
    elseif ($action === 'readKategori') {
        $sql = "SELECT id_kategori, kategori FROM kategori ORDER BY id_kategori ASC";
        $res = $conn->query($sql);
        if ($res === false) {
            echo json_encode(["error" => "Query failed: " . $conn->error]);
            exit();
        }
        $data = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
        exit();
    }

    // ================= READ USER =================
    elseif ($action === 'readUser') {
        $sql = "SELECT id_user, nama, no_hp, alamat, role FROM account ORDER BY id_user ASC";
        $res = $conn->query($sql);
        $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode($data);
        exit();
    }

    // ================= READ JENIS SAMPAH =================
    elseif ($action === 'readSampah') {
        $sql = "SELECT id_jenis, jenis, harga FROM jenis_sampah ORDER BY id_jenis ASC";
        $res = $conn->query($sql);
        if ($res === false) {
            echo json_encode(["error" => "Query failed: " . $conn->error]);
            exit();
        }
        $data = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
        exit();
    }

    else {
        echo json_encode(["error" => "Invalid action"]);
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Bank Sampah Karangsewu</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <!-- Header -->
  <header>
    <div class="logo">🌱 Bank Sampah Karangsewu</div>
    <div class="user" ><h3><?php if(isset($_SESSION['id_user'])): ?>
      <!-- <div class="user-info"> -->
          <h3><?= htmlspecialchars($_SESSION['nama']); ?></h3>
        <!-- </div> --></h3></div>
      <nav>
        <?php else: ?>
          <a href="login.php" class="login-btn">Masuk</a>
          <a href="register.php" class="register-btn">Daftar</a>
          <?php endif; ?>
          <a href="admin.php#home">Home</a>
          <a href="admin.php#transaksi">Transaksi</a>
          <a href="admin.php#users">User</a>
          <a href="admin.php#maps">Peta</a>
          <a href="admin.php#kontak">Kontak</a>
          <a href="logout.php" class="btn-logout">Keluar</a>
    </nav>
  </header>

  <!-- Hero -->
  <section class="hero" id="home">
    <h1>Selamat Datang Admin, <?= htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p>Ini adalah halaman dashboard khusus admin. Anda dapat memantau dan mengelola transaksi serta user di sini.</p>
  </section>

<!-- Form Section -->
<div class="forms-container">
  <!-- Form Transaksi -->
  <form id="transaksiForm">
    <h2>Form Transaksi</h2>
    <input type="text" id="nama" placeholder="Nama penyetor" required>
    <select id="jenis" required></select>
    <input type="number" id="jumlah" placeholder="Jumlah (kg)" required>
    <button type="submit">Tambah</button>
  </form>

  <!-- Tambah User -->
  <form id="userForm">
    <h2>Tambah User Baru</h2>
    <input type="text" id="user_nama" placeholder="Nama" required>
    <input type="text" id="user_hp" placeholder="No HP" required>
    <input type="text" id="user_alamat" placeholder="Alamat" required>
    <button type="submit">Tambah User</button>
  </form>

</div>


<!-- History -->
<section class="tables">
  <!-- Tables Section -->
    <div>
      <h2>Daftar User</h2>
      <table id="userTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>No HP</th>
            <th>Alamat</th>
            <th>Role</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  
    <div>
      <h2>Daftar Jenis Sampah</h2>
      <table id="sampahTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Jenis</th>
            <th>Harga/kg</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

  <h3>Riwayat Transaksi</h3>
  <table id="riwayat">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nama</th>
        <th>Jenis Sampah</th>
        <th>Jumlah (kg)</th>
        <th>Tanggal</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</section>

  <footer id="kontak">
    <p>📍 Desa Karangsewu | 🌐 @banksampahkarangsewu</p>
    <p>© 2025 Bank Sampah Karangsewu</p>
  </footer>
      
<script src="admin.js" defer></script>

</body>
</html>
