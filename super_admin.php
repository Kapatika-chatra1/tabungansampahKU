<?php
session_start();
require 'koneksi.php';

/* ========== GUARD: hanya super_admin ========== */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

/* ===== Util singkat ===== */
function is_post(){ return $_SERVER['REQUEST_METHOD']==='POST'; }
function j($data){ header('Content-Type: application/json; charset=utf-8'); 
  echo json_encode($data); 
  exit(); }
function csv_open($filename){
  header('Content-Type: text/csv; charset=utf-8');
  header("Content-Disposition: attachment; filename=$filename");
  return fopen('php://output', 'w');
}

/* ===== Router mini (API) ===== */
$action = $_GET['action'] ?? '';

/* 1) Hitung jumlah admin */
if ($action === 'countAdmins') {
  $res = $conn->query("SELECT COUNT(*) AS c FROM account WHERE role='admin'");
  $row = $res ? $res->fetch_assoc() : ['c'=>0];
  j(['count'=>(int)$row['c']]);
}

/* 2) Hitung titik aktif */
if ($action === 'countActivePoints') {
  $res = $conn->query("SELECT COUNT(*) AS c FROM location_points WHERE active=1");
  $row = $res ? $res->fetch_assoc() : ['c'=>0];
  j(['count'=>(int)$row['c']]);
}

/* 3) Distribusi role */
if ($action === 'roleCounts') {
  $out=['admin'=>0,'user'=>0,'super_admin'=>0];
  if ($res=$conn->query("SELECT role, COUNT(*) c FROM account GROUP BY role")) {
    while($r=$res->fetch_assoc()){ $out[$r['role']] = (int)$r['c']; }
  }
  j($out);
}

/* 4) List admin (search + paging) */
if ($action === 'listAdmins') {
  $q   = trim($_GET['q'] ?? '');
  $pg  = max(1, (int)($_GET['page'] ?? 1));
  $pp  = min(100, max(5, (int)($_GET['per_page'] ?? 10)));
  $off = ($pg-1)*$pp;

  if ($q !== '') {
    $like="%{$q}%";
    $stmt=$conn->prepare(
      "SELECT SQL_CALC_FOUND_ROWS id_user,nama,no_hp,alamat,role
       FROM account
       WHERE role='admin' AND (nama LIKE ? OR no_hp LIKE ? OR alamat LIKE ?)
       ORDER BY id_user DESC
       LIMIT ?,?"
    );
    $stmt->bind_param("sssii",$like,$like,$like,$off,$pp);
  } else {
    $stmt=$conn->prepare(
      "SELECT SQL_CALC_FOUND_ROWS id_user,nama,no_hp,alamat,role
       FROM account
       WHERE role='admin'
       ORDER BY id_user DESC
       LIMIT ?,?"
    );
    $stmt->bind_param("ii",$off,$pp);
  }
  $stmt->execute();
  $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $total=0;
  if ($fr=$conn->query("SELECT FOUND_ROWS() AS t")) {
    $tmp=$fr->fetch_assoc(); $total=(int)($tmp['t'] ?? 0);
  }
  j(['rows'=>$rows,'total'=>$total,'page'=>$pg,'per_page'=>$pp]);
}

/* 5) Tambah admin (password default terkunci) */
if ($action === 'createAdmin' && is_post()) {
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
  j(['success'=>$ins->execute()]);
}

/* 6) Reset password admin ke default */
if ($action === 'resetAdminPassword' && is_post()) {
  $id=(int)($_POST['id_user'] ?? 0);
  if ($id<=0) j(['success'=>false,'error'=>'ID tidak valid']);
  $r=$conn->query("SELECT role FROM account WHERE id_user={$id}")->fetch_assoc();
  if (($r['role'] ?? '')!=='admin') j(['success'=>false,'error'=>'Hanya untuk admin']);
  $pwd=password_hash("Karangsewu777", PASSWORD_DEFAULT);
  $u=$conn->prepare("UPDATE account SET password=? WHERE id_user=?");
  $u->bind_param("si",$pwd,$id);
  j(['success'=>$u->execute()]);
}

/* 7) Ambil titik (filter: only_active, type, q) */
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

/* 8) Tambah titik */
if ($action === 'createPoint' && is_post()) {
  $name=trim($_POST['name']??'');
  $type=trim($_POST['type']??'Pengepul');
  $phone=trim($_POST['phone']??''); $addr=trim($_POST['address']??'');
  $lat=(float)($_POST['lat']??0); $lng=(float)($_POST['lng']??0);
  $active=(int)($_POST['active']??1);
  if ($name===''||!$lat||!$lng) j(['error'=>'Nama, lat, lng wajib diisi.']);
  $st=$conn->prepare("INSERT INTO location_points (name,type,phone,address,lat,lng,active) VALUES (?,?,?,?,?,?,?)");
  $st->bind_param("ssssddi",$name,$type,$phone,$addr,$lat,$lng,$active);
  j(['success'=>$st->execute()]);
}

/* 9) Update titik */
if ($action === 'updatePoint' && is_post()) {
  $id=(int)($_POST['id']??0);
  $name=trim($_POST['name']??'');
  $type=trim($_POST['type']??'Pengepul');
  $phone=trim($_POST['phone']??''); $addr=trim($_POST['address']??'');
  $lat=(float)($_POST['lat']??0); $lng=(float)($_POST['lng']??0);
  $active=(int)($_POST['active']??1);
  if ($id<=0||$name===''||!$lat||!$lng) j(['error'=>'Data tidak valid.']);
  $st=$conn->prepare("UPDATE location_points SET name=?,type=?,phone=?,address=?,lat=?,lng=?,active=? WHERE id=?");
  $st->bind_param("ssssddii",$name,$type,$phone,$addr,$lat,$lng,$active,$id);
  j(['success'=>$st->execute()]);
}

/* 10) Hapus titik */
if ($action === 'deletePoint' && is_post()) {
  $id=(int)($_POST['id']??0);
  if ($id<=0) j(['error'=>'ID tidak valid']);
  $st=$conn->prepare("DELETE FROM location_points WHERE id=?");
  $st->bind_param("i",$id);
  j(['success'=>$st->execute()]);
}

/* 11) Ekspor admin (CSV) */
if ($action === 'exportAdmins') {
  $out=csv_open('admins.csv');
  fputcsv($out,['id_user','nama','no_hp','alamat','role']);
  if ($res=$conn->query("SELECT id_user,nama,no_hp,alamat,role FROM account WHERE role='admin' ORDER BY id_user DESC")){
    while($r=$res->fetch_assoc()) fputcsv($out,$r);
  }
  fclose($out); exit();
}

/* 12) Ekspor titik (CSV) */
if ($action === 'exportPoints') {
  $out=csv_open('points.csv');
  fputcsv($out,['id','name','type','phone','address','lat','lng','active','updated_at']);
  if ($res=$conn->query("SELECT id,name,type,phone,address,lat,lng,active,updated_at FROM location_points ORDER BY id DESC")){
    while($r=$res->fetch_assoc()) fputcsv($out,$r);
  }
  fclose($out); exit();
}


/* ===== Tambah jenis ===== */
if ($action === 'createSampah' && is_post()) {
  $jenis = trim($_POST['jenis'] ?? '');
  $harga = (int)($_POST['harga'] ?? 0);
  $id_kategori = (int)($_POST['id_kategori'] ?? 0);

  if ($jenis === '' || $harga <= 0 || $id_kategori <= 0) {
    j(['success'=>false,'error'=>'Jenis, harga & kategori wajib diisi']);
  }

  $st = $conn->prepare("INSERT INTO jenis_sampah (jenis, harga, id_kategori) VALUES (?,?,?)");
  $st->bind_param("sii", $jenis, $harga, $id_kategori);

  if ($st->execute()) {
    j(['success'=>true]);
  } else {
    j(['success'=>false,'error'=>$st->error]);
  }
}

if ($action === 'readKategori') {
  $res = $conn->query("SELECT id_kategori, kategori FROM kategori ORDER BY kategori ASC");
  j($res->fetch_all(MYSQLI_ASSOC));
}

/* ===== Baca semua jenis ===== */
if ($action === 'readSampah') {
  $rows = [];
  $rs = $conn->query("
    SELECT js.id_jenis, js.jenis, js.id_kategori, k.kategori, js.harga
    FROM jenis_sampah js
    LEFT JOIN kategori k ON js.id_kategori = k.id_kategori
    ORDER BY js.id_jenis Asc
  ");
  while ($r = $rs->fetch_assoc()) $rows[] = $r;
  j($rows);
}



/* ===== Update jenis ===== */
if ($action === 'updateSampah' && is_post()) {
  $id = (int)($_POST['id_jenis'] ?? 0);
  $jenis = trim($_POST['jenis'] ?? '');
  $harga = (int)($_POST['harga'] ?? 0);
  $id_kategori = (int)($_POST['id_kategori'] ?? 0);

  if ($id<=0 || $jenis==='' || $harga<=0 || $id_kategori<=0) {
    j(['success'=>false,'error'=>'Data tidak valid']);
  }

  $st = $conn->prepare("UPDATE jenis_sampah SET jenis=?, harga=?, id_kategori=? WHERE id_jenis=?");
  $st->bind_param("siii", $jenis, $harga, $id_kategori, $id);
  j(['success'=>$st->execute(), 'error'=>$st->error]);
}


/* Hapus jenis */
if ($action === 'deleteSampah' && is_post()) {
  $id_jenis = (int)($_POST['id_jenis'] ?? $_POST['id'] ?? 0);
  if ($id_jenis<=0) j(['success'=>false,'error'=>'ID tidak valid']);
  $st = $conn->prepare("DELETE FROM jenis_sampah WHERE id_jenis=?");
  $st->bind_param("i",$id_jenis);
  j(['success'=>$st->execute()]);
}



/* ====== Tidak ada action: render halaman (HTML) ====== */
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Dashboard Super Admin - Bank Sampah Karangsewu</title>
  <link rel="stylesheet" href="super_admin.css?v=3"/>
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
      <a href="#map" data-panel="map">Peta</a>
      <a href="#sampah" data-panel="sampah">Jenis Sampah</a>
      <a href="#export" data-panel="export">Ekspor</a>
    </nav>
    <div class="side-foot">
      <div class="who"><?= htmlspecialchars($_SESSION['nama']); ?> <span>Super Admin</span></div>
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
          <ul class="roles"><li><b>Admin</b> <span id="rc_admin">0</span></li><li><b>User</b> <span id="rc_user">0</span></li><li><b>Super Admin</b> <span id="rc_sa">0</span></li></ul>
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
            <select id="adminPerPage"><option value="10">10 / halaman</option><option value="20">20 / halaman</option><option value="50">50 / halaman</option></select>
            <button id="reloadAdmins" class="btn info">Muat Ulang</button>
          </div>
          <div class="inline">
            <button id="exportAdmins" class="btn ghost">Ekspor CSV</button>
          </div>
        </div>

        <div class="grid-2">
          <div class="table-wrap">
            <table id="adminsTable" class="sa-table"><thead><tr><th>ID</th><th>Nama</th><th>No HP</th><th>Alamat</th><th>Role</th><th>Aksi</th></tr></thead><tbody></tbody></table>
            <div class="pager"><button class="btn tiny" id="prevPage">⟵</button><span id="pageInfo">Hal. 1</span><button class="btn tiny" id="nextPage">⟶</button></div>
          </div>
          <form id="addAdminForm" class="inline-form" autocomplete="off">
            <h3>Tambah Admin Baru</h3>
            <label>Nama <input id="ad_nama" type="text" required/></label>
            <label>No HP <input id="adding_hp" type="text" required inputmode="numeric"/></label>
            <label>Alamat <input id="ad_alamat" type="text"/></label>
            <div class="hint">Password default: <code>Karangsewu777</code></div>
            <div class="form-actions"><button type="submit" class="btn success">Tambah</button><button type="reset" class="btn ghost">Reset</button></div>
          </form>
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
            <div class="grid-2 tight"><label>Lat <input id="point_lat" type="number" step="0.0000001" required/></label><label>Lng <input id="point_lng" type="number" step="0.0000001" required/></label></div>
            <label class="switch"><input id="point_active" type="checkbox" checked/><span>Aktif</span></label>
            <div class="form-actions"><button type="button" id="savePoint" class="btn success">Simpan</button><button type="button" id="resetPoint" class="btn ghost">Reset</button></div>
          </form>
        </div>

        <div class="table-wrap mt16">
          <table id="pointsTable" class="sa-table"><thead><tr><th>ID</th><th>Nama</th><th>Jenis</th><th>Lat</th><th>Lng</th><th>Aktif</th><th>Aksi</th></tr></thead><tbody></tbody></table>
        </div>
      </div>
    </section>

    <!-- Jenis Sampah -->
    <section id="sampah" class="section" data-panel="sampah">
      <header class="section-head"><h2>Daftar Jenis Sampah</h2><p class="muted">Kelola daftar jenis sampah (CRUD).</p></header>
      <div class="sa-card">
        <div class="grid-2">
          <div class="table-wrap">
            <table id="sampahTable">
  <thead>
    <tr>
      <th>ID</th>
      <th>Jenis</th>
      <th>Kategori</th>
      <th>Harga</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

          </div>

          <form id="sampahForm">
  <input type="hidden" id="sampah_id" name="sampah_id">
  
  <label for="sampah_jenis">Jenis Sampah</label>
  <input type="text" id="sampah_jenis" name="jenis" required>

  <label for="sampah_harga">Harga</label>
  <input type="number" id="sampah_harga" name="harga" required>

  <label for="sampah_kategori">Kategori</label>
  <select id="sampah_kategori" name="id_kategori" required></select>

  <button type="submit" class="btn">Simpan</button>
  <button type="reset" class="btn" id="sampahReset">Reset</button>
</form>

        </div>
      </div>
    </section>


    <footer class="sa-footer">

    <p>© 2025 Bank Sampah Karangsewu</p>
    <a href="https://github.com/Kapatika-chatra1/tabungansampahKU/activity">By : Informatika KKN UII Angkatan 71 2025</a>
    </footer>
  </main>

  <div id="toast" class="toast" aria-live="polite" aria-atomic="true"></div>

  <script src="super_admin.js?v=5" defer></script>
</body>
</html>
