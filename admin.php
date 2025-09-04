<?php
session_start();
require 'koneksi.php';

// pastikan hanya admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

// helper: harga per kg sesuai option select di HTML
function harga_perkg(string $jenis): int {
    $map = [
        "Botol Plastik" => 5000,
        "Aluminium"     => 7000,
        "Kayu"          => 2000,
        "Kertas"        => 3000,
    ];
    return $map[$jenis] ?? 0;
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    // ================= CREATE TRANSAKSI =================
    if ($action === 'create') {
        $nama  = trim($_POST['nama'] ?? '');
        $jenis = trim($_POST['jenis'] ?? '');
        $jumlah= (int) ($_POST['jumlah'] ?? 0);

        if ($nama === '' || $jenis === '' || $jumlah <= 0) {
            echo json_encode(["error" => "Data transaksi tidak lengkap atau jumlah tidak valid."]);
            exit();
        }
        if (harga_perkg($jenis) === 0) {
            echo json_encode(["error" => "Jenis sampah tidak valid."]);
            exit();
        }

        // ambil user berdasarkan nama (sesuai implementasimu)
        $q = $conn->prepare("SELECT id_user, no_hp, nama FROM account WHERE nama=? LIMIT 1");
        $q->bind_param("s", $nama);
        $q->execute();
        $res = $q->get_result();
        if ($res->num_rows === 0) {
            echo json_encode(["error" => "User (nama) tidak ditemukan di account."]);
            exit();
        }
        $userRow = $res->fetch_assoc();
        $id_user = (int) $userRow['id_user'];
        $no_hp   = $userRow['no_hp'];
        $user_nama = $userRow['nama'];

        // mulai transaksi db supaya insert transaction + update saldo atomik
        $conn->begin_transaction();
        try {
            // insert ke transaction
            $insT = $conn->prepare("INSERT INTO `transaction` (id_user, no_hp, jenis_sampah, jumlah_setoran, tanggal) VALUES (?, ?, ?, ?, NOW())");
            $insT->bind_param("issi", $id_user, $no_hp, $jenis, $jumlah);
            $insT->execute();

            // hitung nominal rupiah
            $nominal = $jumlah * harga_perkg($jenis);

            // cek saldo existing (kunci baris untuk safety jika InnoDB)
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
                // insert baris saldo (sesuai struktur: id_saldo, id_user, nama, saldo)
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
    $sql = "SELECT t.id_trans, a.nama, t.jenis_sampah, t.tanggal, t.jumlah_setoran
            FROM `transaction` t
            JOIN account a ON t.id_user = a.id_user
            WHERE t.tanggal >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
            ORDER BY t.id_trans";
    $res = $conn->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode($data);
    exit();
}


    // ================= UPDATE TRANSAKSI =================
    elseif ($action === 'update') {
        $id      = (int) ($_POST['id'] ?? 0);
        // terima baik 'jenis' atau 'jenis_sampah' dari JS
        $jenis_baru = trim($_POST['jenis'] ?? ($_POST['jenis_sampah'] ?? ''));
        $jumlah_baru= (int)  ($_POST['jumlah'] ?? ($_POST['jumlah_setoran'] ?? 0));

        if ($id <= 0 || $jenis_baru === '' || $jumlah_baru <= 0) {
            echo json_encode(["error" => "Data update tidak lengkap atau tidak valid."]);
            exit();
        }
        if (harga_perkg($jenis_baru) === 0) {
            echo json_encode(["error" => "Jenis sampah update tidak valid."]);
            exit();
        }

        $conn->begin_transaction();
        try {
            // ambil transaksi lama
            $getOld = $conn->prepare("SELECT id_user, jenis_sampah, jumlah_setoran FROM `transaction` WHERE id_trans=?");
            $getOld->bind_param("i", $id);
            $getOld->execute();
            $oldRes = $getOld->get_result();
            if ($oldRes->num_rows === 0) throw new Exception("Transaksi tidak ditemukan.");

            $old = $oldRes->fetch_assoc();
            $id_user = (int)$old['id_user'];
            $jenis_lama = $old['jenis_sampah'];
            $jumlah_lama = (int)$old['jumlah_setoran'];

            // update transaksi
            $updT = $conn->prepare("UPDATE `transaction` SET jenis_sampah=?, jumlah_setoran=? WHERE id_trans=?");
            $updT->bind_param("sii", $jenis_baru, $jumlah_baru, $id);
            $updT->execute();

            // hitung delta nominal untuk koreksi saldo
            $nominal_lama = $jumlah_lama * harga_perkg($jenis_lama);
            $nominal_baru = $jumlah_baru * harga_perkg($jenis_baru);
            $delta = $nominal_baru - $nominal_lama; // bisa negatif

            if ($delta !== 0) {
                // ambil nama user agar bisa insert jika belum ada saldo
                $q = $conn->prepare("SELECT nama FROM account WHERE id_user=? LIMIT 1");
                $q->bind_param("i", $id_user);
                $q->execute();
                $r = $q->get_result();
                $user_name = ($r->num_rows>0) ? $r->fetch_assoc()['nama'] : '';

                $cekS = $conn->prepare("SELECT id_saldo, saldo FROM saldo WHERE id_user=?");
                $cekS->bind_param("i", $id_user);
                $cekS->execute();
                $resS = $cekS->get_result();

                if ($resS->num_rows > 0) {
                    $rowS = $resS->fetch_assoc();
                    $newSaldo = (int)$rowS['saldo'] + $delta;
                    if ($newSaldo < 0) $newSaldo = 0;
                    $updS = $conn->prepare("UPDATE saldo SET saldo=? WHERE id_saldo=?");
                    $updS->bind_param("ii", $newSaldo, $rowS['id_saldo']);
                    $updS->execute();
                } else {
                    $init = max(0, $delta);
                    $insS = $conn->prepare("INSERT INTO saldo (id_user, nama, saldo) VALUES (?, ?, ?)");
                    $insS->bind_param("isi", $id_user, $user_name, $init);
                    $insS->execute();
                }
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
            // ambil transaksi lama untuk koreksi saldo
            $getOld = $conn->prepare("SELECT id_user, jenis_sampah, jumlah_setoran FROM `transaction` WHERE id_trans=?");
            $getOld->bind_param("i", $id);
            $getOld->execute();
            $oldRes = $getOld->get_result();
            if ($oldRes->num_rows === 0) throw new Exception("Transaksi tidak ditemukan.");

            $old = $oldRes->fetch_assoc();
            $id_user = (int)$old['id_user'];
            $jenis_lama = $old['jenis_sampah'];
            $jumlah_lama = (int)$old['jumlah_setoran'];

            // hapus transaksi
            $del = $conn->prepare("DELETE FROM `transaction` WHERE id_trans=?");
            $del->bind_param("i", $id);
            $del->execute();

            // kurangi saldo
            $nominal_lama = $jumlah_lama * harga_perkg($jenis_lama);
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

        // cek duplikat no_hp
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


    // invalid action
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
  <style>
    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 15px;
    }
    table th, table td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: center;
    }
    table th {
      background: #f2f2f2;
    }
    .btn {
      padding: 5px 10px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .btn-edit { background: #2196F3; color: white; }
    .btn-delete { background: #f44336; color: white; }
  </style>
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

  <!-- Transaksi -->
  <section class="transaksi" id="transaksi">

    <form id="transaksiForm">
      <h2>Form Transaksi</h2>
      <input type="text" id="nama" placeholder="Nama penyetor" required>
      <select id="jenis">
        <option value="Botol Plastik">Botol Plastik</option>
        <option value="Aluminium">Aluminium</option>
        <option value="Kayu">Kayu</option>
        <option value="Kertas">Kertas</option>
      </select>
      <input type="number" id="jumlah" placeholder="Jumlah (kg)" required>
      <button type="submit">Tambah</button>
    </form>
  
          <!-- User -->
  <div class="users" id="users">
    <h2>Tambah User Baru</h2>
    <form id="userForm">
      <input type="text" id="user_nama" placeholder="Nama" required>
      <input type="text" id="user_hp" placeholder="No HP" required>
      <input type="text" id="user_alamat" placeholder="Alamat" required>
      <button type="submit">Tambah User</button>
    </form>
  </div>
  </section>

<section>
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
