<?php
session_start();
require 'koneksi.php';

/* ========== GUARD: hanya super_admin ========== */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

/* (Opsional) Helper lama – disimpan kalau suatu saat dipakai */
function harga_perkg(string $jenis): int {
    $map = [
        "Botol Plastik" => 5000,
        "Aluminium"     => 7000,
        "Kayu"          => 2000,
        "Kertas"        => 3000,
    ];
    return $map[$jenis] ?? 0;
}

/* ===== Util singkat ===== */
function is_post(){ return $_SERVER['REQUEST_METHOD']==='POST'; }
function j($data){ header('Content-Type: application/json; charset=utf-8'); echo json_encode($data); exit(); }
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

/* ====== Tidak ada action: render halaman (HTML) ====== */
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Super Admin - Bank Sampah Karangsewu</title>

  <!-- Leaflet Map -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script defer src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

  <!-- Theme -->
  <link rel="stylesheet" href="super_admin.css?v=3"/>

  <meta name="theme-color" content="#2e7d32"/>
  <meta name="description" content="Panel Super Admin untuk mengelola admin dan titik lokasi Bank Sampah Karangsewu."/>
</head>
<body>
  <!-- Sidebar -->
  <aside class="sa-side" role="navigation" aria-label="Sidebar utama">
    <div class="side-brand" aria-label="Brand">🌱 Karangsewu</div>

    <nav class="side-nav" aria-label="Menu">
      <a href="#overview" class="active" data-panel="overview" aria-current="page">Ikhtisar</a>
      <a href="#admins" data-panel="admins">Admin</a>
      <a href="#map" data-panel="map">Peta</a>
      <a href="#export" data-panel="export">Ekspor</a>
    </nav>

    <div class="side-foot">
      <div class="who">
        <?= htmlspecialchars($_SESSION['nama']); ?> <span>Super Admin</span>
      </div>
      <a href="logout.php" class="btn-logout" title="Keluar dari sesi">Keluar</a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="sa-main" role="main">
    <!-- ================= OVERVIEW ================= -->
    <section id="overview" class="section" aria-labelledby="ov-title" data-panel="overview">
      <header class="section-head">
        <h1 id="ov-title">Ikhtisar</h1>
        <p class="muted">Statistik ringkas sistem untuk membantu pengambilan keputusan cepat.</p>
      </header>

      <div class="stats-grid" role="region" aria-label="Statistik ringkas">
        <article class="stat elevated" aria-live="polite" aria-label="Total admin">
          <div class="num" id="totalAdmins">0</div>
          <div class="label">Total Admin</div>
        </article>

        <article class="stat elevated" aria-live="polite" aria-label="Titik aktif">
          <div class="num" id="totalPoints">0</div>
          <div class="label">Titik Aktif</div>
        </article>

        <article class="stat elevated" aria-label="Distribusi role">
          <div class="sub">Distribusi Role</div>
          <ul class="roles" id="roleDist">
            <li><b>Admin</b> <span id="rc_admin">0</span></li>
            <li><b>User</b> <span id="rc_user">0</span></li>
            <li><b>Super Admin</b> <span id="rc_sa">0</span></li>
          </ul>
        </article>
      </div>
    </section>

    <!-- ================= ADMINS ================= -->
    <section id="admins" class="section" aria-labelledby="ad-title" data-panel="admins">
      <header class="section-head">
        <h2 id="ad-title">Direktori Admin</h2>
        <p class="muted">Kelola daftar admin: pencarian, paging, tambah admin baru, dan reset password default.</p>
      </header>

      <div class="sa-card">
        <div class="sa-card__head card-row">
          <div class="inline" role="group" aria-label="Kontrol pencarian admin">
            <input id="adminSearch" type="text" placeholder="Cari nama / HP / alamat…" aria-label="Cari admin"/>
            <select id="adminPerPage" aria-label="Jumlah per halaman">
              <option value="10">10 / halaman</option>
              <option value="20">20 / halaman</option>
              <option value="50">50 / halaman</option>
            </select>
            <button id="reloadAdmins" class="btn info" type="button" aria-label="Muat ulang daftar admin">Muat Ulang</button>
          </div>
          <div class="inline">
            <button id="exportAdmins" class="btn ghost" type="button" aria-label="Ekspor admin ke CSV">Ekspor CSV</button>
          </div>
        </div>

        <div class="grid-2">
          <!-- TABEL ADMIN -->
          <div class="table-wrap" role="region" aria-label="Tabel admin">
            <table class="sa-table" id="adminsTable" aria-describedby="ad-table-desc">
              <caption id="ad-table-desc" class="visually-hidden">Daftar admin dengan aksi reset password</caption>
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Nama</th>
                  <th scope="col">No HP</th>
                  <th scope="col">Alamat</th>
                  <th scope="col">Role</th>
                  <th scope="col">Aksi</th>
                </tr>
              </thead>
              <tbody><!-- diisi lewat JS --></tbody>
            </table>

            <div class="pager" role="navigation" aria-label="Pagination">
              <button class="btn tiny" id="prevPage" type="button" aria-label="Halaman sebelumnya">⟵</button>
              <span id="pageInfo" aria-live="polite">Hal. 1</span>
              <button class="btn tiny" id="nextPage" type="button" aria-label="Halaman berikutnya">⟶</button>
            </div>
          </div>

          <!-- FORM TAMBAH ADMIN -->
          <form id="addAdminForm" class="inline-form" autocomplete="off" aria-label="Form tambah admin baru">
            <h3>Tambah Admin Baru</h3>

            <label for="ad_nama">Nama
              <input type="text" id="ad_nama" required placeholder="Nama lengkap"/>
            </label>

            <label for="ad_hp">No HP
              <input type="text" id="ad_hp" required placeholder="08xxxxxxxxxx" inputmode="numeric"/>
            </label>

            <label for="ad_alamat">Alamat
              <input type="text" id="ad_alamat" placeholder="Opsional"/>
            </label>

            <div class="hint">Password default: <code>Karangsewu777</code> (dapat diubah oleh desa melalui prosedur internal)</div>

            <div class="form-actions">
              <button type="submit" class="btn success" aria-label="Tambah admin">Tambah</button>
              <button type="reset" class="btn ghost" aria-label="Reset form">Reset</button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- ================= MAP ================= -->
    <section id="map" class="section" aria-labelledby="map-title" data-panel="map">
      <header class="section-head">
        <h2 id="map-title">Kelola Peta Titik</h2>
        <p class="muted">Klik peta untuk membuat titik. Seret marker untuk mengubah posisi. Simpan untuk merekam ke database.</p>
      </header>

      <div class="sa-card">
        <div class="sa-card__head card-row">
          <div class="inline" role="group" aria-label="Filter titik peta">
            <select id="fltType" aria-label="Filter jenis">
              <option value="">Semua Jenis</option>
              <option>Pengepul</option>
              <option>TPS</option>
              <option>Bank Sampah</option>
              <option>Lainnya</option>
            </select>

            <label class="chk" for="fltActive">
              <input type="checkbox" id="fltActive" checked/> Hanya Aktif
            </label>

            <input id="fltQ" type="text" placeholder="Cari nama / HP / alamat…" aria-label="Kata kunci filter"/>

            <button id="applyFilter" class="btn" type="button" aria-label="Terapkan filter">Terapkan</button>
          </div>

          <div class="inline" role="group" aria-label="Aksi peta">
            <button id="fitAll" class="btn ghost" type="button">Fit to Markers</button>
            <button id="locateMe" class="btn ghost" type="button">Lokasi Saya</button>
            <button id="exportPoints" class="btn ghost" type="button">Ekspor Titik CSV</button>
          </div>
        </div>

        <div class="grid-2">
          <!-- MAP -->
          <div class="map" id="adminMap" role="application" aria-label="Peta titik lokasi"></div>

          <!-- FORM TITIK -->
          <form id="pointForm" class="point-form" autocomplete="off" aria-label="Form titik peta">
            <input type="hidden" id="point_id"/>

            <label for="point_name">Nama Titik
              <input id="point_name" type="text" required placeholder="Contoh: Pengepul Pak Budi"/>
            </label>

            <label for="point_type">Jenis
              <select id="point_type">
                <option>Pengepul</option>
                <option>TPS</option>
                <option>Bank Sampah</option>
                <option>Lainnya</option>
              </select>
            </label>

            <label for="point_phone">No HP (opsional)
              <input id="point_phone" type="text" placeholder="08xxxxxxxxxx"/>
            </label>

            <label for="point_address">Alamat (opsional)
              <input id="point_address" type="text" placeholder="Dusun/RT/RW, patokan lokasi…"/>
            </label>

            <div class="grid-2 tight">
              <label for="point_lat">Lat
                <input id="point_lat" type="number" step="0.0000001" required placeholder="-7.xxxxxx"/>
              </label>
              <label for="point_lng">Lng
                <input id="point_lng" type="number" step="0.0000001" required placeholder="110.xxxxxx"/>
              </label>
            </div>

            <label class="switch" for="point_active">
              <input id="point_active" type="checkbox" checked/>
              <span>Aktif</span>
            </label>

            <div class="form-actions">
              <button type="button" id="savePoint" class="btn success" aria-label="Simpan titik">Simpan</button>
              <button type="button" id="resetPoint" class="btn ghost" aria-label="Reset form titik">Reset</button>
            </div>
          </form>
        </div>

        <!-- TABEL TITIK -->
        <div class="table-wrap mt16" role="region" aria-label="Daftar titik">
          <table class="sa-table" id="pointsTable">
            <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Nama</th>
                <th scope="col">Jenis</th>
                <th scope="col">Lat</th>
                <th scope="col">Lng</th>
                <th scope="col">Aktif</th>
                <th scope="col">Aksi</th>
              </tr>
            </thead>
            <tbody><!-- diisi lewat JS --></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ================= EXPORT ================= -->
    <section id="export" class="section" aria-labelledby="ex-title" data-panel="export">
      <header class="section-head">
        <h2 id="ex-title">Ekspor</h2>
        <p class="muted">Unduh data admin & titik untuk arsip atau laporan desa.</p>
      </header>

      <div class="sa-card">
        <div class="inline">
          <a class="btn info"  href="super_admin.php?action=exportAdmins"  aria-label="Unduh admin CSV">Unduh Admin CSV</a>
          <a class="btn info"  href="super_admin.php?action=exportPoints"  aria-label="Unduh titik CSV">Unduh Titik CSV</a>
        </div>
        <p class="mt-3 text-muted">Catatan: berkas CSV menggunakan urutan kolom yang konsisten agar mudah diolah di Excel/Spreadsheet.</p>
      </div>
    </section>

    <footer class="sa-footer" role="contentinfo">© 2025 Bank Sampah Karangsewu</footer>
  </main>

  <!-- Toast -->
  <div id="toast" class="toast" aria-live="polite" aria-atomic="true"></div>

  <!-- Scripts -->
  <script src="super_admin.js?v=3" defer></script>
</body>
</html>
