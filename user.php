<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php"); exit();
}
$id_user = (int)$_SESSION['id_user'];

$saldo = 0;
if ($stmt = $conn->prepare("SELECT saldo FROM saldo WHERE id_user = ? LIMIT 1")) {
  $stmt->bind_param("i",$id_user); $stmt->execute(); $stmt->bind_result($sdb);
  if ($stmt->fetch()) $saldo = (float)$sdb; $stmt->close();
}

$riwayat = [];
$sql = "SELECT t.id_trans AS id_transaksi,a.nama AS nama_user,t.jenis_sampah,t.jumlah_setoran
        FROM `transaction` t JOIN account a ON t.id_user=a.id_user
        WHERE t.id_user=? ORDER BY t.id_trans DESC";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i",$id_user); $stmt->execute();
  $r = $stmt->get_result(); while($row=$r->fetch_assoc()) $riwayat[]=$row; $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Dashboard – Bank Sampah Karangsewu</title>
  <link rel="icon" href="../tabungansampahKU/img/logoKP.png"/>
  <link rel="stylesheet" href="user.css?v=7"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <style>
    /* Modal Ganti Password */
    .modal {
      position: fixed; inset: 0; background: rgba(0,0,0,.5);
      display: none; align-items: center; justify-content: center; z-index: 1000;
    }
    .modal__content {
      background: #fff; padding: 20px; border-radius: 16px;
      width: 320px; max-width: 90%; box-shadow: 0 4px 16px rgba(0,0,0,.2);
      animation: fadeIn .3s ease;
    }
    .modal__content h3 { margin-top: 0; }
    .modal__content input {
      width:100%; margin-bottom:10px; padding:8px; border:1px solid #ccc; border-radius:8px;
    }
    .modal__actions { display:flex; gap:10px; margin-top:10px; }
    @keyframes fadeIn { from{opacity:0; transform:scale(.95);} to{opacity:1; transform:scale(1);} }
  </style>
</head>
<body>

  <!-- TOPBAR -->
  <div class="header">
    <div class="header__inner">
      <div class="brand">
        <div class="brand__emoji">🌱</div>
        <div class="brand__title">Bank Sampah Karangsewu</div>
      </div>
      <a class="btn btn--danger" href="logout.php">🚪 Keluar</a>
    </div>
  </div>

  <div class="container">
    <!-- HERO -->
    <section class="hero">
      <div>
        <span class="hero__text">Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> 👋</span>
        <span class="hero__sub">selamat datang di dashboard Anda.</span>
      </div>
      <div class="hero__tools">
        <span class="badge">👤 Role: User</span>
        <button class="btn btn--ghost" id="btnOpenPassword">🔑 Ganti Password</button>
      </div>
    </section>

    <!-- STATS -->
    <section class="stats" aria-label="Statistik">
      <div class="stat">
        <div class="stat__icon">💰</div>
        <div class="stat__labels">
          <div class="stat__title">Saldo</div>
          <div class="stat__value" id="stat-saldo">Rp <?= number_format($saldo,0,',','.') ?></div>
        </div>
      </div>
      <div class="stat">
        <div class="stat__icon">🧾</div>
        <div class="stat__labels">
          <div class="stat__title">Total Transaksi</div>
          <div class="stat__value" id="stat-transaksi">0</div>
        </div>
      </div>
      <div class="stat">
        <div class="stat__icon">🥇</div>
        <div class="stat__labels">
          <div class="stat__title">Jenis Terbanyak</div>
          <div class="stat__value" id="stat-jenis">—</div>
        </div>
      </div>
    </section>

    <!-- GRID -->
    <section class="grid">
      <!-- TABEL -->
      <div class="card">
        <div class="card__header">
          <h3 class="card__title">📜 Riwayat Penjualan Sampah</h3>
          <div class="toolbar">
            <input class="input" type="search" id="searchInput" placeholder="Cari nama / jenis…" aria-label="Pencarian"/>
            <select class="select" id="filterJenis" aria-label="Filter jenis">
              <option value="">Semua Jenis</option>
            </select>
            <button class="btn btn--ghost" id="btnDownload">⬇️ Unduh CSV</button>
            <button class="btn btn--ghost" id="btnReset">🔄 Reset</button>
          </div>
        </div>

        <div class="table-wrap">
          <table id="tabelRiwayat" aria-label="Tabel riwayat transaksi">
            <thead>
              <tr>
                <th>ID Transaksi</th>
                <th>Nama</th>
                <th>Jenis Sampah</th>
                <th>Jumlah Setoran</th>
              </tr>
            </thead>
            <tbody>
            <?php if(!$riwayat): ?>
              <tr><td colspan="4" style="text-align:center; padding:22px; color:var(--muted);">Belum ada transaksi.</td></tr>
            <?php else: foreach($riwayat as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['id_transaksi']) ?></td>
                <td><?= htmlspecialchars($r['nama_user']) ?></td>
                <td><?= htmlspecialchars($r['jenis_sampah']) ?></td>
                <td><?= number_format((int)$r['jumlah_setoran'],0,',','.') ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- MAP -->
      <div class="card" style="padding:14px;">
        <div class="card__header" style="border-bottom:none; padding:0 0 10px 0;">
          <h3 class="card__title" style="margin:0;">🗺️ Lokasi Pengepul</h3>
        </div>
        <div id="map"></div>
      </div>
    </section>
  </div>

  <footer class="footer">
    <p>📍 Desa Karangsewu &nbsp;|&nbsp; 🌐 @banksampahkarangsewu</p>
    <p>© 2025 Bank Sampah Karangsewu</p>
  </footer>

  <!-- MODAL GANTI PASSWORD -->
  <div id="modalPassword" class="modal">
    <div class="modal__content">
      <h3>🔑 Ganti Password</h3>
      <form id="formPassword">
        <input type="password" name="old_password" placeholder="Password Sekarang" required>
        <input type="password" name="new_password" placeholder="Password Baru" required>
        <input type="password" name="confirm_password" placeholder="Konfirmasi Password Baru" required>
        <div id="msgPassword" style="margin:8px 0; font-size:14px;"></div>
        <div class="modal__actions">
          <button type="submit" class="btn">Simpan</button>
          <button type="button" class="btn btn--ghost" id="btnClosePassword">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // ===== STAT dari tabel =====
    const tbody = document.querySelector('#tabelRiwayat tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr')).filter(r => r.children.length >= 4);
    const statTransEl = document.getElementById('stat-transaksi');
    const statJenisEl = document.getElementById('stat-jenis');

    const jenisCount = {};
    let visibleCount = 0;
    rows.forEach(tr => {
      const jenis = tr.children[2]?.textContent.trim();
      if (!jenis) return;
      visibleCount++;
      jenisCount[jenis] = (jenisCount[jenis] || 0) + 1;
    });
    statTransEl.textContent = visibleCount || 0;
    statJenisEl.textContent = visibleCount ? Object.entries(jenisCount).sort((a,b)=>b[1]-a[1])[0][0] : '—';

    // ===== FILTER & SEARCH =====
    const filterJenis = document.getElementById('filterJenis');
    const searchInput = document.getElementById('searchInput');
    const btnReset    = document.getElementById('btnReset');

    const jenisSet = new Set(rows.map(r => r.children[2]?.textContent.trim()).filter(Boolean));
    [...jenisSet].sort().forEach(j => {
      const o = document.createElement('option'); o.value=j; o.textContent=j; filterJenis.appendChild(o);
    });

    function applyFilter(){
      const q  = searchInput.value.trim().toLowerCase();
      const jf = filterJenis.value;
      let showCount = 0;
      rows.forEach(tr => {
        const nama  = tr.children[1]?.textContent.toLowerCase() || '';
        const jenis = tr.children[2]?.textContent || '';
        const matchQ = !q || nama.includes(q) || jenis.toLowerCase().includes(q);
        const matchJ = !jf || jenis === jf;
        const show = matchQ && matchJ;
        tr.style.display = show ? '' : 'none';
        if (show) showCount++;
      });
      statTransEl.textContent = showCount;
    }
    searchInput.addEventListener('input', applyFilter);
    filterJenis.addEventListener('change', applyFilter);
    btnReset.addEventListener('click', () => { searchInput.value=''; filterJenis.value=''; applyFilter(); });

    // ===== CSV =====
    document.getElementById('btnDownload').addEventListener('click', () => {
      const vis = rows.filter(r => r.style.display !== 'none');
      if (!vis.length) { alert('Tidak ada data untuk diunduh.'); return; }
      const header = ['ID Transaksi','Nama','Jenis Sampah','Jumlah Setoran'];
      const csv = [header.join(',')].concat(
        vis.map(tr => Array.from(tr.children).slice(0,4).map(td => {
          const t = td.textContent.replace(/\s+/g,' ').trim().replace(/"/g,'""');
          return `"${t}"`;
        }).join(','))
      ).join('\r\n');
      const blob = new Blob([csv],{type:'text/csv;charset=utf-8;'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob); a.download = 'riwayat-transaksi.csv'; a.click();
      URL.revokeObjectURL(a.href);
    });

    // ===== MAP =====
    const map = L.map('map').setView([-7.9539772,110.1813977], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution:'© OpenStreetMap contributors'
    }).addTo(map);
    L.marker([-7.9490876,110.1975741]).addTo(map).bindPopup('Titik Bank Sampah Sorogaten');

    // ===== MODAL PASSWORD =====
    const modalPassword = document.getElementById('modalPassword');
    document.getElementById('btnOpenPassword').addEventListener('click', ()=> {
      modalPassword.style.display = 'flex';
    });
    document.getElementById('btnClosePassword').addEventListener('click', ()=> {
      modalPassword.style.display = 'none';
    });

    document.getElementById('formPassword').addEventListener('submit', async e => {
      e.preventDefault();
      const form = e.target;
      const data = new FormData(form);
      const msgEl = document.getElementById('msgPassword');
      msgEl.style.color = "black";
      msgEl.textContent = "⏳ Memproses...";

      try {
        const res = await fetch('ganti_password.php', { method:'POST', body:data });
        const result = await res.json();
        msgEl.style.color = result.success ? "green" : "red";
        msgEl.textContent = result.message;
        if(result.success){
          form.reset();
          setTimeout(()=> modalPassword.style.display='none', 1500);
        }
      } catch(err){
        msgEl.style.color = "red";
        msgEl.textContent = "❌ Terjadi kesalahan koneksi.";
      }
    });
  });
  </script>
</body>
</html>
