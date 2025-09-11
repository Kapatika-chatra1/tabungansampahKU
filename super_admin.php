<?php
/* ===== Session cookie: path "/" agar session berlaku di seluruh situs ===== */
if (PHP_VERSION_ID >= 70300) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false, // set true jika sudah HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
} else {
  session_set_cookie_params(0, '/');
}
session_start();

require 'koneksi.php';

/* ===== Helper umum ===== */
function is_api() { return isset($_GET['action']); }
function j($data, $code=200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function deny($msg='Hanya super admin yang boleh mengakses halaman ini.', $code=403){
  if (is_api()) return j(['error'=>$msg], $code);
  http_response_code($code);
  ?>
  <!DOCTYPE html>
  <html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses ditolak</title>
    <style>
      body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;display:grid;place-items:center;min-height:100svh;background:#f6fbf6;color:#142218;margin:0}
      .box{background:#fff;border:1px solid #e4efe6;border-radius:12px;padding:20px;max-width:520px;box-shadow:0 14px 38px rgba(16,24,20,.14);text-align:center}
      .box h1{margin:.2rem 0 0;font-size:1.2rem}
      a.btn{display:inline-block;margin-top:12px;padding:10px 12px;border-radius:10px;text-decoration:none;color:#fff;background:linear-gradient(135deg,#2e7d32,#1b5e20)}
    </style>
  </head>
  <body>
    <div class="box">
      <h1>🚫 Akses ditolak</h1>
      <p><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
      <a class="btn" href="login.php?next=super_admin.php">Masuk sebagai Super Admin</a>
    </div>
  </body>
  </html>
  <?php
  exit;
}

/* ===== Guard: hanya super_admin ===== */
if (!isset($_SESSION['id_user']) || (($_SESSION['role'] ?? '') !== 'super_admin')) {
  deny();
}

/* ===== Router API ===== */
$action = $_GET['action'] ?? '';

/* ----- Statistik & distribusi role ----- */
if ($action === 'countAdmins') {
  $row = $conn->query("SELECT COUNT(*) c FROM account WHERE role='admin'")->fetch_assoc();
  j(['count'=>(int)($row['c'] ?? 0)]);
}
if ($action === 'countActivePoints') {
  $row = $conn->query("SELECT COUNT(*) c FROM location_points WHERE active=1")->fetch_assoc();
  j(['count'=>(int)($row['c'] ?? 0)]);
}
if ($action === 'roleCounts') {
  $out=['admin'=>0,'user'=>0,'super_admin'=>0];
  if ($res=$conn->query("SELECT role, COUNT(*) c FROM account GROUP BY role")) {
    while($r=$res->fetch_assoc()) $out[$r['role']] = (int)$r['c'];
  }
  j($out);
}

/* ----- Util list akun by role, dengan status aktif_90d (ada transaksi < 90 hari) ----- */
function list_accounts($conn, $role) {
  $q   = trim($_GET['q'] ?? '');
  $pg  = max(1, (int)($_GET['page'] ?? 1));
  $pp  = min(100, max(5, (int)($_GET['per_page'] ?? 10)));
  $off = ($pg-1)*$pp;
  $only_inactive = (int)($_GET['only_inactive'] ?? 0);

  $where = "a.role=?";
  $types = "s";
  $args  = [$role];

  if ($q !== '') {
    $where .= " AND (a.nama LIKE ? OR a.no_hp LIKE ? OR a.alamat LIKE ?)";
    $like = "%{$q}%";
    $types .= "sss";
    array_push($args, $like, $like, $like);
  }

  // Subquery last_tx per user
  $sql = "
    SELECT SQL_CALC_FOUND_ROWS
      a.id_user, a.nama, a.no_hp, a.alamat, a.role,
      MAX(t.tanggal) AS last_tx,
      CASE
        WHEN MAX(t.tanggal) IS NOT NULL
             AND MAX(t.tanggal) >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1
        ELSE 0
      END AS active_90d
    FROM account a
    LEFT JOIN `transaction` t ON t.id_user = a.id_user
    WHERE $where
    GROUP BY a.id_user
  ";

  if ($only_inactive) $sql .= " HAVING active_90d = 0";
  $sql .= " ORDER BY a.id_user DESC LIMIT ?,?";

  $types .= "ii";
  array_push($args, $off, $pp);

  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$args);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  $tot = 0;
  if ($fr=$conn->query("SELECT FOUND_ROWS() t")) {
    $tmp = $fr->fetch_assoc(); $tot = (int)($tmp['t'] ?? 0);
  }
  j(['rows'=>$rows, 'total'=>$tot, 'page'=>$pg, 'per_page'=>$pp]);
}

/* ----- List Admins / Users ----- */
if ($action === 'listAdmins') list_accounts($conn, 'admin');
if ($action === 'listUsers')  list_accounts($conn, 'user');

/* ----- Tambah / reset admin (sama seperti sebelumnya) ----- */
if ($action === 'createAdmin' && $_SERVER['REQUEST_METHOD']==='POST') {
  $nama   = trim($_POST['nama'] ?? '');
  $no_hp  = trim($_POST['no_hp'] ?? '');
  $alamat = trim($_POST['alamat'] ?? '');
  if ($nama===''||$no_hp==='') j(['success'=>false,'error'=>'Nama & No HP wajib diisi']);

  $cek=$conn->prepare("SELECT id_user FROM account WHERE no_hp=? LIMIT 1");
  $cek->bind_param("s",$no_hp); $cek->execute();
  if ($cek->get_result()->num_rows>0) j(['success'=>false,'error'=>'No HP sudah terdaftar']);

  $password = password_hash("Karangsewu777", PASSWORD_DEFAULT);
  $role="admin";
  $ins=$conn->prepare("INSERT INTO account (nama,no_hp,alamat,password,role) VALUES (?,?,?,?,?)");
  $ins->bind_param("sssss",$nama,$no_hp,$alamat,$password,$role);
  j(['success'=>$ins->execute(), 'error'=>$ins->error]);
}
if ($action === 'resetAdminPassword' && $_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id_user'] ?? 0);
  if ($id<=0) j(['success'=>false,'error'=>'ID tidak valid']);
  if ($id == (int)($_SESSION['id_user'])) j(['success'=>false,'error'=>'Tidak bisa reset password diri sendiri.']);
  $r=$conn->query("SELECT role FROM account WHERE id_user={$id}")->fetch_assoc();
  if (($r['role'] ?? '')!=='admin') j(['success'=>false,'error'=>'Hanya untuk admin']);
  $pwd=password_hash("Karangsewu777", PASSWORD_DEFAULT);
  $u=$conn->prepare("UPDATE account SET password=? WHERE id_user=?");
  $u->bind_param("si",$pwd,$id);
  j(['success'=>$u->execute(), 'error'=>$u->error]);
}

/* ----- Hapus Admin (hanya jika non-aktif 90 hari & bukan diri sendiri) ----- */
if ($action === 'deleteAdmin' && $_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id_user'] ?? 0);
  if ($id<=0) j(['success'=>false,'error'=>'ID tidak valid']);
  if ($id == (int)($_SESSION['id_user'])) j(['success'=>false,'error'=>'Tidak bisa menghapus diri sendiri.']);


  $r=$conn->query("SELECT role FROM account WHERE id_user={$id}")->fetch_assoc();
  if (($r['role'] ?? '')!=='admin') j(['success'=>false,'error'=>'Target bukan admin']);

  $row = $conn->query("SELECT MAX(tanggal) m FROM `transaction` WHERE id_user={$id}")->fetch_assoc();
  $last = $row['m'] ?? null;
  if ($last && (strtotime($last) >= strtotime('-90 days'))) {
    j(['success'=>false,'error'=>'Tidak bisa hapus: akun masih aktif (≤90 hari).']);
  }

  $conn->begin_transaction();
  try {
    $conn->query("DELETE FROM saldo WHERE id_user={$id}");
    $del=$conn->prepare("DELETE FROM account WHERE id_user=?");
    $del->bind_param("i",$id);
    $ok=$del->execute();
    $conn->commit();
    j(['success'=>$ok, 'error'=>$del->error]);
  } catch(Throwable $e){
    $conn->rollback();
    j(['success'=>false,'error'=>$e->getMessage()],500);
  }
}

/* ----- Hapus User (hanya jika non-aktif 90 hari) ----- */
if ($action === 'deleteUser' && $_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id_user'] ?? 0);
  if ($id<=0) j(['success'=>false,'error'=>'ID tidak valid']);
  $r=$conn->query("SELECT role FROM account WHERE id_user={$id}")->fetch_assoc();
  if (($r['role'] ?? '')!=='user') j(['success'=>false,'error'=>'Target bukan user']);

  $row = $conn->query("SELECT MAX(tanggal) m FROM `transaction` WHERE id_user={$id}")->fetch_assoc();
  $last = $row['m'] ?? null;
  if ($last && (strtotime($last) >= strtotime('-90 days'))) {
    j(['success'=>false,'error'=>'Tidak bisa hapus: akun masih aktif (≤90 hari).']);
  }

  $conn->begin_transaction();
  try {
    $conn->query("DELETE FROM saldo WHERE id_user={$id}");
    $del=$conn->prepare("DELETE FROM account WHERE id_user=?");
    $del->bind_param("i",$id);
    $ok=$del->execute();
    $conn->commit();
    j(['success'=>$ok, 'error'=>$del->error]);
  } catch(Throwable $e){
    $conn->rollback();
    j(['success'=>false,'error'=>$e->getMessage()],500);
  }
}

/* ----- Map Points (tetap) ----- */
if ($action === 'getPoints') {
  $only=(int)($_GET['only_active'] ?? 0);
  $type=trim($_GET['type'] ?? '');
  $q   =trim($_GET['q'] ?? '');
  $sql="SELECT * FROM location_points WHERE 1=1";
  $P=[]; $T='';
  if ($only){ $sql.=" AND active=1"; }
  if ($type!=='' && in_array($type,['Pengepul','TPS','Bank Sampah','Lainnya'])){ $sql.=" AND type=?"; $P[]=$type; $T.='s'; }
  if ($q!==''){ $like="%{$q}%"; $sql.=" AND (name LIKE ? OR phone LIKE ? OR address LIKE ?)"; array_push($P,$like,$like,$like); $T.='sss'; }
  $sql.=" ORDER BY id DESC";
  if ($P){ $st=$conn->prepare($sql); $st->bind_param($T,...$P); $st->execute(); $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC); }
  else { $rows=$conn->query($sql)->fetch_all(MYSQLI_ASSOC); }
  j($rows);
}
if ($action === 'createPoint' && $_SERVER['REQUEST_METHOD']==='POST') {
  $name=trim($_POST['name']??''); $type=trim($_POST['type']??'Pengepul');
  $phone=trim($_POST['phone']??''); $addr=trim($_POST['address']??'');
  $lat=(float)($_POST['lat']??0); $lng=(float)($_POST['lng']??0);
  $active=(int)($_POST['active']??1);
  if ($name===''||!$lat||!$lng) j(['error'=>'Nama, lat, lng wajib diisi.']);
  $st=$conn->prepare("INSERT INTO location_points (name,type,phone,address,lat,lng,active) VALUES (?,?,?,?,?,?,?)");
  $st->bind_param("ssssddi",$name,$type,$phone,$addr,$lat,$lng,$active);
  j(['success'=>$st->execute(), 'error'=>$st->error]);
}
if ($action === 'updatePoint' && $_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id']??0);
  $name=trim($_POST['name']??''); $type=trim($_POST['type']??'Pengepul');
  $phone=trim($_POST['phone']??''); $addr=trim($_POST['address']??'');
  $lat=(float)($_POST['lat']??0); $lng=(float)($_POST['lng']??0);
  $active=(int)($_POST['active']??1);
  if ($id<=0||$name===''||!$lat||!$lng) j(['error'=>'Data tidak valid.']);
  $st=$conn->prepare("UPDATE location_points SET name=?,type=?,phone=?,address=?,lat=?,lng=?,active=? WHERE id=?");
  $st->bind_param("ssssddii",$name,$type,$phone,$addr,$lat,$lng,$active,$id);
  j(['success'=>$st->execute(), 'error'=>$st->error]);
}
if ($action === 'deletePoint' && $_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id']??0);
  if ($id<=0) j(['error'=>'ID tidak valid']);
  $st=$conn->prepare("DELETE FROM location_points WHERE id=?");
  $st->bind_param("i",$id);
  j(['success'=>$st->execute(), 'error'=>$st->error]);
}

/* ----- Jenis Sampah (tetap) ----- */
if ($action === 'readKategori') {
  $res = $conn->query("SELECT id_kategori, kategori FROM kategori ORDER BY kategori ASC");
  j($res->fetch_all(MYSQLI_ASSOC));
}
if ($action === 'readSampah') {
  $rows = [];
  $rs = $conn->query("
    SELECT js.id_jenis, js.jenis, js.id_kategori, k.kategori, js.harga
    FROM jenis_sampah js
    LEFT JOIN kategori k ON js.id_kategori = k.id_kategori
    ORDER BY js.id_jenis ASC
  ");
  while($r=$rs->fetch_assoc()) $rows[]=$r;
  j($rows);
}
if ($action === 'createSampah' && $_SERVER['REQUEST_METHOD']==='POST') {
  $jenis=trim($_POST['jenis']??''); $harga=(int)($_POST['harga']??0); $id_kategori=(int)($_POST['id_kategori']??0);
  if ($jenis===''||$harga<=0||$id_kategori<=0) j(['success'=>false,'error'=>'Jenis, harga, kategori wajib diisi']);
  $st=$conn->prepare("INSERT INTO jenis_sampah (jenis,harga,id_kategori) VALUES (?,?,?)");
  $st->bind_param("sii",$jenis,$harga,$id_kategori);
  j(['success'=>$st->execute(), 'error'=>$st->error]);
}
if ($action === 'updateSampah' && $_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id_jenis']??0); $jenis=trim($_POST['jenis']??''); $harga=(int)($_POST['harga']??0); $id_kategori=(int)($_POST['id_kategori']??0);
  if ($id<=0||$jenis===''||$harga<=0||$id_kategori<=0) j(['success'=>false,'error'=>'Data tidak valid']);
  $st=$conn->prepare("UPDATE jenis_sampah SET jenis=?, harga=?, id_kategori=? WHERE id_jenis=?");
  $st->bind_param("siii",$jenis,$harga,$id_kategori,$id);
  j(['success'=>$st->execute(), 'error'=>$st->error]);
}
if ($action === 'deleteSampah' && $_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id_jenis']??0); if ($id<=0) j(['success'=>false,'error'=>'ID tidak valid']);
  $st=$conn->prepare("DELETE FROM jenis_sampah WHERE id_jenis=?");
  $st->bind_param("i",$id);
  j(['success'=>$st->execute(), 'error'=>$st->error]);
}

/* ----- Ekspor CSV (opsional, sama) ----- */
if ($action === 'exportAdmins') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=admins.csv');
  $out=fopen('php://output','w');
  fputcsv($out,['id_user','nama','no_hp','alamat','role','last_tx','active_90d']);
  $rs=$conn->query("
    SELECT a.id_user,a.nama,a.no_hp,a.alamat,a.role,MAX(t.tanggal) last_tx,
      CASE WHEN MAX(t.tanggal) IS NOT NULL AND MAX(t.tanggal)>=DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 ELSE 0 END active_90d
    FROM account a LEFT JOIN `transaction` t ON t.id_user=a.id_user
    WHERE a.role='admin' GROUP BY a.id_user ORDER BY a.id_user DESC
  ");
  while($r=$rs->fetch_assoc()) fputcsv($out,$r);
  fclose($out); exit;
}
if ($action === 'exportUsers') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=users.csv');
  $out=fopen('php://output','w');
  fputcsv($out,['id_user','nama','no_hp','alamat','role','last_tx','active_90d']);
  $rs=$conn->query("
    SELECT a.id_user,a.nama,a.no_hp,a.alamat,a.role,MAX(t.tanggal) last_tx,
      CASE WHEN MAX(t.tanggal) IS NOT NULL AND MAX(t.tanggal)>=DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 ELSE 0 END active_90d
    FROM account a LEFT JOIN `transaction` t ON t.id_user=a.id_user
    WHERE a.role='user' GROUP BY a.id_user ORDER BY a.id_user DESC
  ");
  while($r=$rs->fetch_assoc()) fputcsv($out,$r);
  fclose($out); exit;
}

/* ====== Jika bukan API: render halaman ====== */
$me = htmlspecialchars($_SESSION['nama'] ?? 'Super Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Dashboard Super Admin - Bank Sampah Karangsewu</title>
  <link rel="stylesheet" href="super_admin.css?v=5"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script defer src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <meta name="theme-color" content="#2e7d32"/>
</head>
<body>
  <aside class="sa-side" role="navigation" aria-label="Sidebar utama">
    <div class="side-brand">🌱 Karangsewu</div>
    <nav class="side-nav" aria-label="Menu">
      <a href="#overview" class="active" data-panel="overview" aria-current="page">Ikhtisar</a>
      <a href="#admins" data-panel="admins">Admin</a>
      <a href="#users"  data-panel="users">Users</a>
      <a href="#map"    data-panel="map">Peta</a>
      <a href="#sampah" data-panel="sampah">Jenis Sampah</a>
      <a href="#export" data-panel="export">Ekspor</a>
    </nav>
    <div class="side-foot">
      <div class="who"><?= $me; ?> <span>Super Admin</span></div>
      <a href="logout.php" class="btn-logout">Keluar</a>
    </div>
  </aside>

  <main class="sa-main" role="main">
    <!-- Overview -->
    <section id="overview" class="section" data-panel="overview">
      <header class="section-head">
        <h1>Ikhtisar</h1>
        <p class="muted">Statistik ringkas sistem.</p>
      </header>
      <div class="stats-grid">
        <article class="stat elevated"><div class="num" id="totalAdmins">0</div><div class="label">Total Admin</div></article>
        <article class="stat elevated"><div class="num" id="totalPoints">0</div><div class="label">Titik Aktif</div></article>
        <article class="stat elevated">
          <div class="sub">Distribusi Role</div>
          <ul class="roles">
            <li><b>Admin</b> <span id="rc_admin">0</span></li>
            <li><b>User</b> <span id="rc_user">0</span></li>
            <li><b>Super Admin</b> <span id="rc_sa">0</span></li>
          </ul>
        </article>
      </div>
    </section>

    <!-- Admins -->
    <section id="admins" class="section" data-panel="admins">
      <header class="section-head"><h2>Direktori Admin</h2><p class="muted">Kelola admin.</p></header>
      <div class="sa-card">
        <div class="sa-card__head card-row">
          <div class="inline">
            <input id="adminSearch" type="text" placeholder="Cari nama / HP / alamat…"/>
            <select id="adminPerPage">
              <option value="10">10 / halaman</option>
              <option value="20">20 / halaman</option>
              <option value="50">50 / halaman</option>
            </select>
            <button id="reloadAdmins" class="btn info">Muat Ulang</button>
          </div>
          <div class="inline">
            <button id="exportAdmins" class="btn ghost">Ekspor CSV</button>
          </div>
        </div>

        <div class="grid-2">
          <div class="table-wrap">
            <table id="adminsTable" class="sa-table">
              <thead>
                <tr><th>ID</th><th>Nama</th><th>No HP</th><th>Alamat</th><th>Aktif ≤90h</th><th>Aksi</th></tr>
              </thead>
              <tbody></tbody>
            </table>
            <div class="pager">
              <button class="btn tiny" id="prevPage">⟵</button>
              <span id="pageInfo">Hal. 1</span>
              <button class="btn tiny" id="nextPage">⟶</button>
            </div>
          </div>

          <form id="addAdminForm" class="inline-form" autocomplete="off">
            <h3>Tambah Admin Baru</h3>
            <label>Nama <input id="ad_nama" type="text" required/></label>
            <label>No HP <input id="adding_hp" type="text" required inputmode="numeric"/></label>
            <label>Alamat <input id="ad_alamat" type="text"/></label>
            <div class="hint">Password default: <code>Karangsewu777</code></div>
            <div class="form-actions">
              <button type="submit" class="btn success">Tambah</button>
              <button type="reset" class="btn ghost">Reset</button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- Users (baru) -->
    <section id="users" class="section" data-panel="users">
      <header class="section-head"><h2>Direktori User</h2><p class="muted">Kelola user (hapus hanya jika non-aktif 90 hari).</p></header>
      <div class="sa-card">
        <div class="sa-card__head card-row">
          <div class="inline">
            <input id="userSearch" type="text" placeholder="Cari nama / HP / alamat…"/>
            <select id="userPerPage">
              <option value="10">10 / halaman</option>
              <option value="20">20 / halaman</option>
              <option value="50">50 / halaman</option>
            </select>
            <label class="chk"><input type="checkbox" id="onlyInactive"/> Hanya non-aktif</label>
            <button id="reloadUsers" class="btn info">Muat Ulang</button>
          </div>
          <div class="inline">
            <button id="exportUsers" class="btn ghost">Ekspor CSV</button>
          </div>
        </div>

        <div class="table-wrap">
          <table id="usersTable" class="sa-table">
            <thead>
              <tr><th>ID</th><th>Nama</th><th>No HP</th><th>Alamat</th><th>Aktif ≤90h</th><th>Aksi</th></tr>
            </thead>
            <tbody></tbody>
          </table>
          <div class="pager">
            <button class="btn tiny" id="uPrev">⟵</button>
            <span id="uPageInfo">Hal. 1</span>
            <button class="btn tiny" id="uNext">⟶</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Map -->
    <section id="map" class="section" data-panel="map">
      <header class="section-head"><h2>Kelola Peta Titik</h2><p class="muted">Klik peta untuk membuat titik.</p></header>
      <div class="sa-card">
        <div class="sa-card__head card-row">
          <div class="inline">
            <select id="fltType"><option value="">Semua Jenis</option><option>Pengepul</option><option>TPS</option><option>Bank Sampah</option><option>Lainnya</option></select>
            <label class="chk"><input type="checkbox" id="fltActive" checked/> Hanya Aktif</label>
            <input id="fltQ" type="text" placeholder="Cari…"/>
            <button id="applyFilter" class="btn">Terapkan</button>
          </div>
          <div class="inline">
            <button id="fitAll" class="btn ghost">Fit to Markers</button>
            <button id="locateMe" class="btn ghost">Lokasi Saya</button>
            <button id="exportPoints" class="btn ghost">Ekspor Titik CSV</button>
          </div>
        </div>

        <div class="grid-2">
          <div id="adminMap" class="map" role="application" aria-label="Peta titik lokasi"></div>

          <form id="pointForm" class="point-form" autocomplete="off">
            <input type="hidden" id="point_id"/>
            <label>Nama Titik <input id="point_name" type="text" required/></label>
            <label>Jenis <select id="point_type"><option>Pengepul</option><option>TPS</option><option>Bank Sampah</option><option>Lainnya</option></select></label>
            <label>No HP <input id="point_phone" type="text"/></label>
            <label>Alamat <input id="point_address" type="text"/></label>
            <div class="grid-2 tight">
              <label>Lat <input id="point_lat" type="number" step="0.0000001" required/></label>
              <label>Lng <input id="point_lng" type="number" step="0.0000001" required/></label>
            </div>
            <label class="switch"><input id="point_active" type="checkbox" checked/><span>Aktif</span></label>
            <div class="form-actions">
              <button type="button" id="savePoint" class="btn success">Simpan</button>
              <button type="button" id="resetPoint" class="btn ghost">Reset</button>
            </div>
          </form>
        </div>

        <div class="table-wrap mt16">
          <table id="pointsTable" class="sa-table">
            <thead><tr><th>ID</th><th>Nama</th><th>Jenis</th><th>Lat</th><th>Lng</th><th>Aktif</th><th>Aksi</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Jenis Sampah -->
    <section id="sampah" class="section" data-panel="sampah">
      <header class="section-head"><h2>Daftar Jenis Sampah</h2><p class="muted">Kelola jenis sampah.</p></header>
      <div class="sa-card">
        <div class="grid-2">
          <div class="table-wrap">
            <table id="sampahTable" class="sa-table">
              <thead><tr><th>ID</th><th>Jenis</th><th>Kategori</th><th>Harga</th><th>Aksi</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>

          <form id="sampahForm" class="inline-form">
            <h3>Form Jenis Sampah</h3>
            <input type="hidden" id="sampah_id">
            <label>Jenis <input id="sampah_jenis" required></label>
            <label>Harga <input id="sampah_harga" type="number" required></label>
            <label>Kategori
              <select id="sampah_kategori" required></select>
            </label>
            <div class="form-actions">
              <button type="submit" class="btn">Simpan</button>
              <button type="reset" class="btn ghost" id="sampahReset">Reset</button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <footer class="sa-footer">
      <p>© <?= date('Y'); ?> Bank Sampah Karangsewu</p>
      <a href="https://github.com/Kapatika-chatra1/tabungansampahKU/activity">By : Informatika KKN UII Angkatan 71 2025</a>
    </footer>
  </main>

  <div id="toast" class="toast" aria-live="polite" aria-atomic="true"></div>

  <script src="super_admin.js?v=6" defer></script>
</body>
</html>
