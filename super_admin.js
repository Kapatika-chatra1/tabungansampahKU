// super_admin.js v5 — Bank Sampah Karangsewu Super Admin
(() => {
  const API = 'super_admin.php';

  // ===== Helpers =====
  const $ = (s, el=document) => el.querySelector(s);
  const $$ = (s, el=document) => [...el.querySelectorAll(s)];

  function escapeHtml(str = '') {
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function toast(msg, good=true) {
    const t = $('#toast');
    if (!t) return alert(msg);
    t.textContent = msg;
    t.classList.toggle('bad', !good);
    t.style.display = 'block';
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => {
      t.classList.remove('show');
      setTimeout(() => t.style.display = 'none', 220);
    }, 2200);
  }

  async function jget(url) {
    const r = await fetch(url, {credentials:'same-origin'});
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json();
  }
  async function jpost(url, data) {
    const r = await fetch(url, {method:'POST', body:data, credentials:'same-origin'});
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json();
  }

  // ====== Overview Stats ======
  async function loadStats() {
    try {
      const [a, p, role] = await Promise.all([
        jget(`${API}?action=countAdmins`),
        jget(`${API}?action=countActivePoints`),
        jget(`${API}?action=roleCounts`),
      ]);
      $('#totalAdmins').textContent = a.count ?? 0;
      $('#totalPoints').textContent = p.count ?? 0;
      $('#rc_admin').textContent = role.admin ?? 0;
      $('#rc_user').textContent = role.user ?? 0;
      $('#rc_sa').textContent = role.super_admin ?? 0;
    } catch (e) {
      toast('Gagal memuat statistik', false);
      console.error(e);
    }
  }

  // ====== Admins Directory (unchanged) ======
  const adminState = { page:1, perPage:10, q:'' };

  function bindAdminControls() {
    $('#adminPerPage').addEventListener('change', () => {
      adminState.perPage = parseInt($('#adminPerPage').value,10) || 10;
      adminState.page = 1;
      loadAdmins();
    });
    $('#adminSearch').addEventListener('input', (e) => {
      adminState.q = e.target.value.trim();
      adminState.page = 1;
      loadAdmins();
    });
    $('#reloadAdmins').addEventListener('click', () => loadAdmins());
    $('#prevPage').addEventListener('click', () => {
      if (adminState.page>1){ adminState.page--; loadAdmins(); }
    });
    $('#nextPage').addEventListener('click', () => {
      adminState.page++; loadAdmins();
    });

    $('#exportAdmins').addEventListener('click', () => {
      window.location.href = `${API}?action=exportAdmins`;
    });

    $('#addAdminForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData();
      fd.append('nama', $('#ad_nama').value.trim());
      fd.append('no_hp', $('#adding_hp').value.trim());
      fd.append('alamat', $('#ad_alamat').value.trim());
      try {
        const res = await jpost(`${API}?action=createAdmin`, fd);
        if (res.success) {
          toast('Admin ditambahkan');
          e.target.reset();
          loadStats();
          loadAdmins();
        } else {
          toast(res.error || 'Gagal menambah admin', false);
        }
      } catch(err) {
        toast('Gagal menambah admin', false);
      }
    });
  }

  async function loadAdmins() {
    const tbody = $('#adminsTable tbody');
    tbody.innerHTML = `<tr><td colspan="6"><div class="skeleton">Memuat…</div></td></tr>`;
    try {
      const { rows, total, page, per_page } = await jget(`${API}?action=listAdmins&q=${encodeURIComponent(adminState.q)}&page=${adminState.page}&per_page=${adminState.perPage}`);
      if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="empty">Belum ada data</td></tr>`;
      } else {
        tbody.innerHTML = rows.map(r => `
          <tr>
            <td>${r.id_user}</td>
            <td>${escapeHtml(r.nama)}</td>
            <td>${escapeHtml(r.no_hp)}</td>
            <td>${escapeHtml(r.alamat || '-')}</td>
            <td>${escapeHtml(r.role)}</td>
            <td class="actions">
              <button class="btn tiny danger" data-reset="${r.id_user}" title="Reset password ke default">Reset PW</button>
            </td>
          </tr>
        `).join('');
      }
      $('#pageInfo').textContent = `Hal. ${page}`;
      // bind reset buttons
      $$('#adminsTable [data-reset]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = btn.getAttribute('data-reset');
          if (!confirm('Reset password admin ini ke default?')) return;
          const fd = new FormData(); fd.append('id_user', id);
          try {
            const res = await jpost(`${API}?action=resetAdminPassword`, fd);
            if (res.success) toast('Password direset');
            else toast(res.error || 'Gagal reset password', false);
          } catch(e) {
            toast('Gagal reset password', false);
          }
        });
      });
      if (!rows.length && adminState.page>1) {
        adminState.page = Math.max(1, page-1);
      }
    } catch (e) {
      tbody.innerHTML = `<tr><td colspan="6" class="empty">Gagal memuat data</td></tr>`;
      console.error(e);
    }
  }

  // ====== Map & Points (unchanged) ======
  let map, markersLayer, markers = new Map();
  let pendingMarker = null;

  function initMap() {
    map = L.map('adminMap', { zoomControl: true }).setView([-7.8715, 110.1150], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 20,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);

    map.on('click', (e) => {
      const { lat, lng } = e.latlng;
      $('#point_id').value = '';
      $('#point_lat').value = lat.toFixed(7);
      $('#point_lng').value = lng.toFixed(7);
      if (pendingMarker) { markersLayer.removeLayer(pendingMarker); }
      pendingMarker = L.marker([lat,lng], {draggable:true}).addTo(markersLayer);
      pendingMarker.on('dragend', () => {
        const p = pendingMarker.getLatLng();
        $('#point_lat').value = p.lat.toFixed(7);
        $('#point_lng').value = p.lng.toFixed(7);
      });
      toast('Koordinat terisi dari klik peta');
    });

    $('#locateMe').addEventListener('click', () => {
      map.locate({ setView:true, maxZoom:16 });
    });

    $('#fitAll').addEventListener('click', () => {
      const group = L.featureGroup([...markers.values()]);
      if (group.getLayers().length) map.fitBounds(group.getBounds().pad(0.2));
      else toast('Belum ada marker', false);
    });

    $('#applyFilter').addEventListener('click', () => loadPoints());
    $('#exportPoints').addEventListener('click', () => {
      window.location.href = `${API}?action=exportPoints`;
    });

    $('#resetPoint').addEventListener('click', () => {
      $('#pointForm').reset();
      $('#point_id').value = '';
      if (pendingMarker) { markersLayer.removeLayer(pendingMarker); pendingMarker=null; }
    });

    $('#savePoint').addEventListener('click', savePoint);
  }

  function readPointForm() {
    return {
      id: $('#point_id').value.trim(),
      name: $('#point_name').value.trim(),
      type: $('#point_type').value,
      phone: $('#point_phone').value.trim(),
      address: $('#point_address').value.trim(),
      lat: $('#point_lat').value.trim(),
      lng: $('#point_lng').value.trim(),
      active: $('#point_active').checked ? 1 : 0,
    };
  }

  async function savePoint() {
    const d = readPointForm();
    if (!d.name || !d.lat || !d.lng) { toast('Nama, lat, lng wajib diisi', false); return; }
    const fd = new FormData();
    for (const k of ['name','type','phone','address','lat','lng','active']) fd.append(k, d[k]);
    try {
      if (d.id) {
        fd.append('id', d.id);
        const res = await jpost(`${API}?action=updatePoint`, fd);
        if (res.success) { toast('Titik diperbarui'); loadPoints(); }
        else toast(res.error || 'Gagal update titik', false);
      } else {
        const res = await jpost(`${API}?action=createPoint`, fd);
        if (res.success) { toast('Titik ditambahkan'); $('#pointForm').reset(); loadPoints(); }
        else toast(res.error || 'Gagal menambah titik', false);
      }
    } catch (e) {
      toast('Operasi titik gagal', false);
    }
  }

  async function loadPoints() {
    markersLayer.clearLayers(); markers.clear(); if (pendingMarker) { pendingMarker=null; }

    const only = $('#fltActive').checked ? 1 : 0;
    const type = $('#fltType').value;
    const q = $('#fltQ').value.trim();
    const url = `${API}?action=getPoints&only_active=${only}&type=${encodeURIComponent(type)}&q=${encodeURIComponent(q)}`;

    const tbody = $('#pointsTable tbody');
    tbody.innerHTML = `<tr><td colspan="7"><div class="skeleton">Memuat…</div></td></tr>`;

    try {
      const rows = await jget(url);
      if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="empty">Tidak ada titik</td></tr>`;
        return;
      }
      rows.forEach(r => {
        const m = L.marker([r.lat, r.lng], { draggable:false })
          .bindPopup(`<b>${escapeHtml(r.name)}</b><br>${escapeHtml(r.type)}<br>${escapeHtml(r.address ?? '')}`)
          .addTo(markersLayer);
        markers.set(r.id, m);
      });

      tbody.innerHTML = rows.map(r => `
        <tr>
          <td>${r.id}</td>
          <td>${escapeHtml(r.name)}</td>
          <td>${escapeHtml(r.type)}</td>
          <td>${Number(r.lat).toFixed(6)}</td>
          <td>${Number(r.lng).toFixed(6)}</td>
          <td>${r.active ? 'Ya' : 'Tidak'}</td>
          <td class="actions">
            <button class="btn tiny" data-edit="${r.id}">Edit</button>
            <button class="btn tiny danger" data-del="${r.id}">Hapus</button>
          </td>
        </tr>
      `).join('');

      $$('#pointsTable [data-edit]').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = +btn.getAttribute('data-edit');
          const row = rows.find(x => +x.id===id);
          if (!row) return;
          $('#point_id').value = row.id;
          $('#point_name').value = row.name;
          $('#point_type').value = row.type;
          $('#point_phone').value = row.phone ?? '';
          $('#point_address').value = row.address ?? '';
          $('#point_lat').value = Number(row.lat).toFixed(7);
          $('#point_lng').value = Number(row.lng).toFixed(7);
          $('#point_active').checked = !!row.active;
          map.setView([row.lat,row.lng], 17);
          if (pendingMarker) { markersLayer.removeLayer(pendingMarker); pendingMarker=null; }
          const ex = markers.get(row.id);
          if (ex) ex.openPopup();
          toast('Form terisi dari tabel');
        });
      });
      $$('#pointsTable [data-del]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = +btn.getAttribute('data-del');
          if (!confirm('Hapus titik ini?')) return;
          const fd = new FormData(); fd.append('id', id);
          try {
            const res = await jpost(`${API}?action=deletePoint`, fd);
            if (res.success) { toast('Titik dihapus'); loadPoints(); }
            else toast(res.error || 'Gagal hapus titik', false);
          } catch(e) {
            toast('Gagal hapus titik', false);
          }
        });
      });
    } catch (e) {
      tbody.innerHTML = `<tr><td colspan="7" class="empty">Gagal memuat titik</td></tr>`;
      console.error(e);
    }
  }

  // ===== Nav Active State =====
  function bindNav() {
    const links = $$('.sa-side nav a');
    function setActive(hash) {
      links.forEach(a => a.classList.toggle('active', a.getAttribute('href')===hash));
    }
    links.forEach(a => {
      a.addEventListener('click', () => setActive(a.getAttribute('href')));
    });
    window.addEventListener('hashchange', () => setActive(location.hash || '#overview'));
    setActive(location.hash || '#overview');
  }

  // ====== Jenis Sampah (BARU/Perbaikan) ======
  const sampahTbody = document.querySelector('#sampahTable tbody');
  const sampahForm = document.getElementById('sampahForm');
  const sampahId = document.getElementById('sampah_id');
  const sampahJenis = document.getElementById('sampah_jenis');
  const sampahHarga = document.getElementById('sampah_harga');
  const sampahReset = document.getElementById('sampahReset');

  async function loadSampah(){
    if (!sampahTbody) return;
    sampahTbody.innerHTML = `<tr><td colspan="4"><div class="skeleton">Memuat…</div></td></tr>`;
    try {
      const rows = await jget(`${API}?action=readSampah`);
      if (!rows.length) {
        sampahTbody.innerHTML = `<tr><td colspan="4" class="empty">Belum ada data</td></tr>`;
        return;
      }
      sampahTbody.innerHTML = rows.map(r => `
  <tr>
    <td>${r.id_jenis}</td>
    <td>${escapeHtml(r.jenis)}</td>
    <td>${escapeHtml(r.kategori || '-')}</td>
    <td>Rp ${Number(r.harga).toLocaleString('id-ID')}</td>
    <td class="actions">
      <button class="btn tiny" data-edit="${r.id_jenis}">Edit</button>
      <button class="btn tiny danger" data-del="${r.id_jenis}">Hapus</button>
    </td>
  </tr>
`).join('');



      // bind edit buttons
      $$('#sampahTable [data-edit]').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-edit');
          const row = rows.find(r => String(r.id_jenis) === String(id));
          if (!row) return;
          sampahId.value = row.id_jenis;
          sampahJenis.value = row.jenis;
          sampahHarga.value = row.harga;
          window.location.hash = '#sampah';
          toast('Form jenis sampah terisi (edit)');
        });
      });

      // bind delete buttons
      $$('#sampahTable [data-del]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = btn.getAttribute('data-del');
          if (!confirm('Yakin hapus jenis sampah ini?')) return;
          const fd = new FormData();
          fd.append('id_jenis', id);
          try {
            const res = await jpost(`${API}?action=deleteSampah`, fd);
            if (res.success) { toast('Jenis sampah dihapus'); loadSampah(); }
            else toast(res.error || 'Gagal hapus jenis sampah', false);
          } catch (e) {
            toast('Gagal hapus jenis sampah', false);
            console.error(e);
          }
        });
      });

    } catch (e) {
      sampahTbody.innerHTML = `<tr><td colspan="4" class="empty">Gagal memuat data</td></tr>`;
      console.error(e);
    }
  }

  sampahForm?.addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const jenis = sampahJenis.value.trim();
  const harga = parseInt(sampahHarga.value, 10) || 0;
  const id_kategori = parseInt(sampahKategori.value, 10) || 0;

  if (!jenis || harga <= 0 || id_kategori <= 0) {
    toast('Jenis, harga & kategori wajib diisi', false);
    return;
  }

  const fd = new FormData();
  fd.append('jenis', jenis);
  fd.append('harga', harga);
  fd.append('id_kategori', id_kategori);
  const id = sampahId.value.trim();
  const action = id ? 'updateSampah' : 'createSampah';
  if (id) fd.append('id_jenis', id);

  try {
    const res = await jpost(`${API}?action=${action}`, fd);
    if (res.success) {
      toast(id ? 'Jenis sampah diperbarui' : 'Jenis sampah ditambahkan');
      sampahForm.reset();
      sampahId.value = '';
      loadSampah();
    } else {
      toast(res.error || 'Gagal menyimpan jenis sampah', false);
    }
  } catch (e) {
    toast('Gagal menyimpan jenis sampah', false);
    console.error(e);
  }
});


  sampahReset?.addEventListener('click', () => {
    sampahForm.reset();
    sampahId.value = '';
  });

  const sampahKategori = document.getElementById('sampah_kategori');

// Load kategori ke dropdown
async function loadKategoriDropdown() {
  try {
    const rows = await jget(`${API}?action=readKategori`);
    sampahKategori.innerHTML = rows.map(r => `
      <option value="${r.id_kategori}">${escapeHtml(r.kategori)}</option>
    `).join('');
  } catch (e) {
    sampahKategori.innerHTML = `<option value="">[Gagal memuat kategori]</option>`;
    console.error(e);
  }
}


  // ===== Init =====
  document.addEventListener('DOMContentLoaded', async () => {
    bindNav();
    bindAdminControls();
    initMap();

    await loadStats();
    await loadAdmins();
    await loadPoints();
    await loadSampah();
    await loadKategoriDropdown();
  });
})();
