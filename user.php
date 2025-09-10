<?php
session_start();
require 'koneksi.php';

/* ==== Guard: hanya user yang login ==== */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];

/* ==== API ==== */
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    // ---- 1) Stats: saldo, total transaksi, jenis terbanyak
    if ($action === 'stats') {
        // saldo
        $saldo = 0;
        if ($st = $conn->prepare("SELECT saldo FROM saldo WHERE id_user=? LIMIT 1")) {
            $st->bind_param("i", $id_user);
            $st->execute();
            $st->bind_result($s);
            if ($st->fetch()) $saldo = (int)$s;
            $st->close();
        }
        // total transaksi
        $total = 0;
        if ($st = $conn->prepare("SELECT COUNT(*) FROM `transaction` WHERE id_user=?")) {
            $st->bind_param("i", $id_user);
            $st->execute();
            $st->bind_result($t);
            if ($st->fetch()) $total = (int)$t;
            $st->close();
        }
        // jenis terbanyak
        $top = null;
        $sqlTop = "
          SELECT js.jenis, COUNT(*) c
          FROM `transaction` t
          JOIN jenis_sampah js ON js.id_jenis=t.id_jenis
          WHERE t.id_user=?
          GROUP BY js.jenis
          ORDER BY c DESC
          LIMIT 1";
        if ($st = $conn->prepare($sqlTop)) {
            $st->bind_param("i", $id_user);
            $st->execute();
            $r = $st->get_result()->fetch_assoc();
            if ($r) $top = $r['jenis'];
            $st->close();
        }
        echo json_encode([
            'nama' => $_SESSION['nama'] ?? 'User',
            'saldo' => $saldo,
            'total' => $total,
            'top'   => $top ?: '—'
        ]);
        exit();
    }

    // ---- 2) List jenis untuk filter dropdown
    if ($action === 'jenisList') {
        $rows = [];
        $res = $conn->query("SELECT id_jenis, jenis FROM jenis_sampah ORDER BY jenis ASC");
        if ($res) $rows = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode($rows); exit();
    }

    // ---- 3) Riwayat transaksi user (filter & search)
    if ($action === 'transactions') {
        $q = trim($_GET['q'] ?? '');
        $id_jenis = (int)($_GET['id_jenis'] ?? 0);

        $sql = "
          SELECT t.id_trans, a.nama, js.jenis, t.jumlah_setoran, t.tanggal
          FROM `transaction` t
          JOIN account a ON a.id_user=t.id_user
          JOIN jenis_sampah js ON js.id_jenis=t.id_jenis
          WHERE t.id_user=?
        ";

        $types = "i"; $params = [$id_user];

        if ($id_jenis > 0) {
            $sql .= " AND t.id_jenis=? ";
            $types .= "i"; $params[] = $id_jenis;
        }
        if ($q !== '') {
            $like = "%{$q}%";
            $sql .= " AND (js.jenis LIKE ? OR a.nama LIKE ? OR CAST(t.id_trans AS CHAR) LIKE ?)";
            $types .= "sss"; array_push($params, $like, $like, $like);
        }
        $sql .= " ORDER BY t.id_trans DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode($rows); exit();
    }

    // ---- 4) Ekspor CSV (mengikuti filter yang sama)
    if ($action === 'exportCSV') {
        header_remove('Content-Type');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=riwayat_user.csv');

        $q = trim($_GET['q'] ?? '');
        $id_jenis = (int)($_GET['id_jenis'] ?? 0);

        $sql = "
          SELECT t.id_trans, a.nama, js.jenis, t.jumlah_setoran, t.tanggal
          FROM `transaction` t
          JOIN account a ON a.id_user=t.id_user
          JOIN jenis_sampah js ON js.id_jenis=t.id_jenis
          WHERE t.id_user=?
        ";
        $types = "i"; $params = [$id_user];
        if ($id_jenis > 0) { $sql.=" AND t.id_jenis=? "; $types.="i"; $params[]=$id_jenis; }
        if ($q !== '') {
            $like = "%{$q}%";
            $sql .= " AND (js.jenis LIKE ? OR a.nama LIKE ? OR CAST(t.id_trans AS CHAR) LIKE ?)";
            $types .= "sss"; array_push($params, $like, $like, $like);
        }
        $sql .= " ORDER BY t.id_trans DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID Transaksi','Nama','Jenis Sampah','Jumlah (kg)','Tanggal']);
        while ($r = $res->fetch_assoc()) fputcsv($out, $r);
        fclose($out); exit();
    }

    // ---- 5) Titik peta aktif (dari super admin)
    if ($action === 'points') {
        $rows = [];
        $res = $conn->query("SELECT id,name,type,address,lat,lng FROM location_points WHERE active=1 ORDER BY id DESC");
        if ($res) $rows = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode($rows); exit();
    }

    // ---- 6) Ganti password user
    if ($action === 'changePassword' && $_SERVER['REQUEST_METHOD']==='POST') {
        $old = $_POST['old'] ?? '';
        $new = $_POST['new'] ?? '';

        if (strlen($new) < 6) {
            echo json_encode(['success'=>false,'error'=>'Minimal 6 karakter']); exit();
        }

        $stmt = $conn->prepare("SELECT password FROM account WHERE id_user=? LIMIT 1");
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $hash = $stmt->get_result()->fetch_assoc()['password'] ?? '';
        $stmt->close();

        // jika hash kosong, anggap belum diset (boleh langsung ganti)
        $ok = ($hash === '') ? true : password_verify($old, $hash);
        if (!$ok) { echo json_encode(['success'=>false,'error'=>'Password lama salah']); exit(); }

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE account SET password=? WHERE id_user=?");
        $up->bind_param("si", $newHash, $id_user);
        $ok = $up->execute();

        echo json_encode(['success'=>$ok]); exit();
    }

    echo json_encode(['error'=>'Invalid action']); exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Dashboard – Bank Sampah Karangsewu</title>

  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

  <!-- Styles -->
  <link rel="stylesheet" href="user.css?v=5"/>
</head>
<body>
  <!-- Header -->
  <header class="u-header">
    <div class="brand">🌿 Bank Sampah Karangsewu</div>
    <nav class="u-nav">
      <span class="badge">🔒 Role: User</span>
      <button id="btnChangePwd" class="btn ghost">Ganti Password</button>
      <a href="logout.php" class="btn danger">Keluar</a>
    </nav>
  </header>

  <main class="u-main">
    <!-- Welcome -->
    <div class="welcome glass">
      Halo, <strong><?= htmlspecialchars($_SESSION['nama']); ?></strong> 👋
      <span>selamat datang di dashboard Anda.</span>
    </div>

    <!-- Stats -->
    <section class="stats">
      <div class="stat">
        <div class="ic">💰</div>
        <div>
          <div class="label">Saldo</div>
          <div class="num" id="saldoNum">Rp 0</div>
        </div>
      </div>
      <div class="stat">
        <div class="ic">🧾</div>
        <div>
          <div class="label">Total Transaksi</div>
          <div class="num" id="totalNum">0</div>
        </div>
      </div>
      <div class="stat">
        <div class="ic">🏅</div>
        <div>
          <div class="label">Jenis Terbanyak</div>
          <div class="num" id="topJenis">—</div>
        </div>
      </div>
    </section>

    <section class="grid">
      <!-- Riwayat -->
      <div class="card">
        <div class="card-head">
          <h2>📋 Riwayat Penjualan Sampah</h2>
          <div class="filters">
            <div class="search">
              <input id="q" type="text" placeholder="Cari id/nama/jenis…"/>
              <button id="clearQ" title="Bersihkan">✕</button>
            </div>
            <select id="jenisFilter">
              <option value="0">Semua Jenis</option>
            </select>
            <button id="btnCSV" class="btn">Unduh CSV</button>
            <button id="btnReset" class="btn ghost">Reset</button>
          </div>
        </div>

        <div class="table-wrap">
          <table class="u-table" id="tTrans">
            <thead>
              <tr>
                <th>ID Transaksi</th>
                <th>Nama</th>
                <th>Jenis Sampah</th>
                <th>Jumlah (kg)</th>
                <th>Tanggal</th>
              </tr>
            </thead>
            <tbody><tr><td colspan="5" class="empty">Memuat…</td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- Peta -->
      <div class="card">
        <div class="card-head">
          <h2>🗺️ Lokasi Pengepul</h2>
        </div>
        <div id="map" class="map"></div>
        <p class="note">Titik diambil dari <code>location_points</code> (Super Admin).</p>
      </div>
    </section>
  </main>

  <footer class="u-footer">
    <div>📍 Desa Karangsewu &nbsp;|&nbsp; 🌐 @banksampahkarangsewu</div>
    <div>© 2025 Bank Sampah Karangsewu</div>
  </footer>

  <!-- Modal ganti password -->
  <dialog id="pwdModal" class="modal">
    <form id="pwdForm" method="dialog" class="modal-body">
      <h3>Ganti Password</h3>
      <label>Password lama
        <input type="password" id="oldPwd" autocomplete="current-password" required/>
      </label>
      <label>Password baru
        <input type="password" id="newPwd" autocomplete="new-password" minlength="6" required/>
      </label>
      <div class="actions">
        <button type="button" id="closePwd" class="btn ghost">Batal</button>
        <button type="submit" class="btn">Simpan</button>
      </div>
      <p id="pwdMsg" class="msg"></p>
    </form>
  </dialog>

  <!-- JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
    const USER_API = 'user.php';
  </script>
  <script src="user.js?v=5" defer></script>
</body>
</html>
