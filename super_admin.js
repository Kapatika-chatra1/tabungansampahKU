// super_admin.js v6 — Super Admin Karangsewu
(() => {
  const API = 'super_admin.php';

  // ===== Helpers =====
  const $  = (s, el=document) => el.querySelector(s);
  const $$ = (s, el=document) => [...el.querySelectorAll(s)];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  function toast(msg, ok=true){
    const t = $('#toast');
    if (!t) return alert(msg);
    t.textContent = msg;
    t.classList.toggle('bad', !ok);
    t.style.display = 'block';
    requestAnimationFrame(()=> t.classList.add('show'));
    setTimeout(()=>{ t.classList.remove('show'); setTimeout(()=> t.style.display='none', 220); }, 2200);
  }
  const jget  = async url  => (await fetch(url,  {credentials:'same-origin'})).json();
  const jpost = async (url, body) => (await fetch(url,{method:'POST', body, credentials:'same-origin'})).json();

  // ===== Nav active (styling) =====
  function bindNav(){
    const links = $$('.sa-side nav a');
    const setActive = hash => links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === hash));
    links.forEach(a => a.addEventListener('click', () => setActive(a.getAttribute('href'))));
    window.addEventListener('hashchange', () => setActive(location.hash || '#overview'));
    setActive(location.hash || '#overview');
  }

  // ===== Overview stats =====
  async function loadStats(){
    try {
      const [a,p,r] = await Promise.all([
        jget(`${API}?action=countAdmins`),
        jget(`${API}?action=countActivePoints`),
        jget(`${API}?action=roleCounts`),
      ]);
      $('#totalAdmins').textContent = a.count ?? 0;
      $('#totalPoints').textContent = p.count ?? 0;
      $('#rc_admin').textContent = r.admin ?? 0;
      $('#rc_user').textContent  = r.user ?? 0;
      $('#rc_sa').textContent    = r.super_admin ?? 0;
    } catch(e){ console.error(e); toast('Gagal memuat statistik', false); }
  }

  // ===== Admins =====
  const adminState = { page:1, perPage:10, q:'' };

  function bindAdminControls(){
    $('#adminPerPage').addEventListener('change', ()=>{ adminState.perPage = +$('#adminPerPage').value || 10; adminState.page=1; loadAdmins(); });
    $('#adminSearch').addEventListener('input', e=>{ adminState.q = e.target.value.trim(); adminState.page=1; loadAdmins(); });
    $('#reloadAdmins').addEventListener('click', ()=> loadAdmins());
    $('#prevPage').addEventListener('click', ()=>{ if (adminState.page>1){ adminState.page--; loadAdmins(); }});
    $('#nextPage').addEventListener('click', ()=>{ adminState.page++; loadAdmins(); });
    $('#exportAdmins').addEventListener('click', ()=> window.location.href = `${API}?action=exportAdmins`);

    $('#addAdminForm').addEventListener('submit', async ev=>{
      ev.preventDefault();
      const fd = new FormData();
      fd.append('nama',   $('#ad_nama').value.trim());
      fd.append('no_hp',  $('#adding_hp').value.trim());
      fd.append('alamat', $('#ad_alamat').value.trim());
      try {
        const r = await jpost(`${API}?action=createAdmin`, fd);
        if (r.success){ toast('Admin ditambahkan'); ev.target.reset(); loadStats(); loadAdmins(); }
        else toast(r.error || 'Gagal menambah admin', false);
      } catch(e){ toast('Gagal menambah admin', false); }
    });
  }

  async function loadAdmins(){
    const tb = $('#adminsTable tbody');
    tb.innerHTML = `<tr><td colspan="6"><div class="skeleton">Memuat…</div></td></tr>`;
    try{
      const {rows,page,per_page} = await jget(`${API}?action=listAdmins&q=${encodeURIComponent(adminState.q)}&page=${adminState.page}&per_page=${adminState.perPage}`);
      if (!rows.length){
        tb.innerHTML = `<tr><td colspan="6" class="empty">Belum ada data</td></tr>`;
      } else {
        tb.innerHTML = rows.map(r => `
          <tr>
            <td>${r.id_user}</td>
            <td>${esc(r.nama)}</td>
            <td>${esc(r.no_hp)}</td>
            <td>${esc(r.alamat || '-')}</td>
            <td>${r.active_90d ? 'Ya' : 'Tidak'}</td>
            <td class="actions">
              <button class="btn tiny" data-reset="${r.id_user}" title="Reset password">Reset PW</button>
              <button class="btn tiny danger" data-del="${r.id_user}" ${r.active_90d ? 'disabled title="Tidak bisa hapus: masih aktif"' : ''}>Hapus</button>
            </td>
          </tr>
        `).join('');
      }
      $('#pageInfo').textContent = `Hal. ${page}`;
      $$('#adminsTable [data-reset]').forEach(b => b.addEventListener('click', async ()=>{
        const id = b.getAttribute('data-reset'); if (!confirm('Reset password admin ini ke default?')) return;
        const fd = new FormData(); fd.append('id_user', id);
        const r = await jpost(`${API}?action=resetAdminPassword`, fd);
        r.success ? toast('Password direset') : toast(r.error || 'Gagal reset password', false);
      }));
      $$('#adminsTable [data-del]').forEach(b => b.addEventListener('click', async ()=>{
        if (b.disabled) return;
        const id = b.getAttribute('data-del'); if (!confirm('Hapus admin ini?')) return;
        const fd = new FormData(); fd.append('id_user', id);
        const r = await jpost(`${API}?action=deleteAdmin`, fd);
        if (r.success){ toast('Admin dihapus'); loadStats(); loadAdmins(); }
        else toast(r.error || 'Gagal hapus admin', false);
      }));
    }catch(e){ console.error(e); tb.innerHTML = `<tr><td colspan="6" class="empty">Gagal memuat data</td></tr>`; }
  }

  // ===== Users (baru) =====
  const userState = { page:1, perPage:10, q:'', onlyInactive:false };

  function bindUserControls(){
    $('#userPerPage').addEventListener('change', ()=>{ userState.perPage = +$('#userPerPage').value || 10; userState.page=1; loadUsers(); });
    $('#userSearch').addEventListener('input', e=>{ userState.q=e.target.value.trim(); userState.page=1; loadUsers(); });
    $('#onlyInactive').addEventListener('change', ()=>{ userState.onlyInactive = $('#onlyInactive').checked; userState.page=1; loadUsers(); });
    $('#reloadUsers').addEventListener('click', ()=> loadUsers());
    $('#uPrev').addEventListener('click', ()=>{ if (userState.page>1){ userState.page--; loadUsers(); }});
    $('#uNext').addEventListener('click', ()=>{ userState.page++; loadUsers(); });
    $('#exportUsers').addEventListener('click', ()=> window.location.href = `${API}?action=exportUsers`);
  }

  async function loadUsers(){
    const tb = $('#usersTable tbody');
    tb.innerHTML = `<tr><td colspan="6"><div class="skeleton">Memuat…</div></td></tr>`;
    try{
      const url = `${API}?action=listUsers&q=${encodeURIComponent(userState.q)}&page=${userState.page}&per_page=${userState.perPage}&only_inactive=${userState.onlyInactive?1:0}`;
      const {rows,page} = await jget(url);
      if (!rows.length){
        tb.innerHTML = `<tr><td colspan="6" class="empty">Belum ada data</td></tr>`;
      } else {
        tb.innerHTML = rows.map(r => `
          <tr>
            <td>${r.id_user}</td>
            <td>${esc(r.nama)}</td>
            <td>${esc(r.no_hp)}</td>
            <td>${esc(r.alamat || '-')}</td>
            <td>${r.active_90d ? 'Ya' : 'Tidak'}</td>
            <td class="actions">
              <button class="btn tiny danger" data-del="${r.id_user}" ${r.active_90d ? 'disabled title="Tidak bisa hapus: masih aktif"' : ''}>Hapus</button>
            </td>
          </tr>
        `).join('');
      }
      $('#uPageInfo').textContent = `Hal. ${page}`;
      $$('#usersTable [data-del]').forEach(b => b.addEventListener('click', async ()=>{
        if (b.disabled) return;
        const id = b.getAttribute('data-del'); if (!confirm('Hapus user ini?')) return;
        const fd = new FormData(); fd.append('id_user', id);
        const r = await jpost(`${API}?action=deleteUser`, fd);
        if (r.success){ toast('User dihapus'); loadStats(); loadUsers(); }
        else toast(r.error || 'Gagal hapus user', false);
      }));
    }catch(e){ console.error(e); tb.innerHTML = `<tr><td colspan="6" class="empty">Gagal memuat data</td></tr>`; }
  }

  // ===== Map & Points =====
  let map, markersLayer, markers = new Map(), pendingMarker=null;
  function initMap(){
    map = L.map('adminMap', { zoomControl:true }).setView([-7.8715,110.1150], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:20, attribution:'&copy; OpenStreetMap'}).addTo(map);
    markersLayer = L.layerGroup().addTo(map);

    map.on('click', e=>{
      const {lat,lng} = e.latlng;
      $('#point_id').value = '';
      $('#point_lat').value = lat.toFixed(7);
      $('#point_lng').value = lng.toFixed(7);
      if (pendingMarker) markersLayer.removeLayer(pendingMarker);
      pendingMarker = L.marker([lat,lng],{draggable:true}).addTo(markersLayer);
      pendingMarker.on('dragend', ()=>{
        const p = pendingMarker.getLatLng();
        $('#point_lat').value = p.lat.toFixed(7);
        $('#point_lng').value = p.lng.toFixed(7);
      });
      toast('Koordinat terisi dari klik peta');
    });

    $('#locateMe').addEventListener('click', ()=> map.locate({setView:true,maxZoom:16}));
    $('#fitAll').addEventListener('click', ()=>{
      const group = L.featureGroup([...markers.values()]);
      if (group.getLayers().length) map.fitBounds(group.getBounds().pad(0.2));
      else toast('Belum ada marker', false);
    });
    $('#applyFilter').addEventListener('click', ()=> loadPoints());
    $('#exportPoints').addEventListener('click', ()=> window.location.href = `${API}?action=exportPoints`);
    $('#resetPoint').addEventListener('click', ()=>{
      $('#pointForm').reset(); $('#point_id').value = '';
      if (pendingMarker){ markersLayer.removeLayer(pendingMarker); pendingMarker=null; }
    });
    $('#savePoint').addEventListener('click', savePoint);
  }
  function readPointForm(){
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
  async function savePoint(){
    const d = readPointForm();
    if (!d.name || !d.lat || !d.lng) return toast('Nama, lat, lng wajib diisi', false);
    const fd = new FormData(); for (const k of Object.keys(d)) if (k!=='id') fd.append(k,d[k]);
    try{
      if (d.id){
        fd.append('id', d.id);
        const r = await jpost(`${API}?action=updatePoint`, fd);
        r.success ? (toast('Titik diperbarui'), loadPoints()) : toast(r.error || 'Gagal update titik', false);
      } else {
        const r = await jpost(`${API}?action=createPoint`, fd);
        r.success ? (toast('Titik ditambahkan'), $('#pointForm').reset(), loadPoints()) : toast(r.error || 'Gagal menambah titik', false);
      }
    }catch(e){ toast('Operasi titik gagal', false); }
  }
  async function loadPoints(){
    markersLayer.clearLayers(); markers.clear(); if (pendingMarker){ pendingMarker=null; }
    const only = $('#fltActive').checked ? 1 : 0;
    const type = $('#fltType').value;
    const q    = $('#fltQ').value.trim();
    const url  = `${API}?action=getPoints&only_active=${only}&type=${encodeURIComponent(type)}&q=${encodeURIComponent(q)}`;

    const tb = $('#pointsTable tbody');
    tb.innerHTML = `<tr><td colspan="7"><div class="skeleton">Memuat…</div></td></tr>`;
    try{
      const rows = await jget(url);
      if (!rows.length){ tb.innerHTML = `<tr><td colspan="7" class="empty">Tidak ada titik</td></tr>`; return; }
      tb.innerHTML = rows.map(r => `
        <tr>
          <td>${r.id}</td>
          <td>${esc(r.name)}</td>
          <td>${esc(r.type)}</td>
          <td>${Number(r.lat).toFixed(6)}</td>
          <td>${Number(r.lng).toFixed(6)}</td>
          <td>${r.active ? 'Ya' : 'Tidak'}</td>
          <td class="actions">
            <button class="btn tiny" data-edit="${r.id}">Edit</button>
            <button class="btn tiny danger" data-del="${r.id}">Hapus</button>
          </td>
        </tr>
      `).join('');
      rows.forEach(r=>{
        const m = L.marker([r.lat,r.lng]).bindPopup(`<b>${esc(r.name)}</b><br>${esc(r.type)}<br>${esc(r.address||'')}`).addTo(markersLayer);
        markers.set(r.id, m);
      });
      $$('#pointsTable [data-edit]').forEach(b=> b.addEventListener('click', ()=>{
        const id = +b.getAttribute('data-edit'); const row = rows.find(x=> +x.id===id); if (!row) return;
        $('#point_id').value = row.id; $('#point_name').value = row.name; $('#point_type').value = row.type;
        $('#point_phone').value = row.phone || ''; $('#point_address').value = row.address || '';
        $('#point_lat').value = Number(row.lat).toFixed(7); $('#point_lng').value = Number(row.lng).toFixed(7);
        $('#point_active').checked = !!row.active; map.setView([row.lat,row.lng], 17);
        const ex = markers.get(row.id); if (ex) ex.openPopup(); toast('Form terisi dari tabel');
      }));
      $$('#pointsTable [data-del]').forEach(b=> b.addEventListener('click', async ()=>{
        const id = +b.getAttribute('data-del'); if (!confirm('Hapus titik ini?')) return;
        const fd = new FormData(); fd.append('id', id);
        const r = await jpost(`${API}?action=deletePoint`, fd);
        r.success ? (toast('Titik dihapus'), loadPoints()) : toast(r.error || 'Gagal hapus titik', false);
      }));
    }catch(e){ console.error(e); tb.innerHTML = `<tr><td colspan="7" class="empty">Gagal memuat titik</td></tr>`; }
  }

  // ===== Sampah =====
  const sTbody = $('#sampahTable tbody');
  const sForm  = $('#sampahForm');
  const sId    = $('#sampah_id');
  const sJenis = $('#sampah_jenis');
  const sHarga = $('#sampah_harga');
  const sKat   = $('#sampah_kategori');

  async function loadKategoriDropdown(){
    try {
      const rows = await jget(`${API}?action=readKategori`);
      sKat.innerHTML = rows.map(r => `<option value="${r.id_kategori}">${esc(r.kategori)}</option>`).join('');
    } catch(e){ sKat.innerHTML = `<option value="">[Gagal memuat kategori]</option>`; }
  }
  async function loadSampah(){
    sTbody.innerHTML = `<tr><td colspan="5"><div class="skeleton">Memuat…</div></td></tr>`;
    try {
      const rows = await jget(`${API}?action=readSampah`);
      if (!rows.length){ sTbody.innerHTML = `<tr><td colspan="5" class="empty">Belum ada data</td></tr>`; return; }
      sTbody.innerHTML = rows.map(r => `
        <tr>
          <td>${r.id_jenis}</td>
          <td>${esc(r.jenis)}</td>
          <td>${esc(r.kategori || '-')}</td>
          <td>Rp ${Number(r.harga).toLocaleString('id-ID')}</td>
          <td class="actions">
            <button class="btn tiny" data-edit="${r.id_jenis}">Edit</button>
            <button class="btn tiny danger" data-del="${r.id_jenis}">Hapus</button>
          </td>
        </tr>
      `).join('');
      $$('#sampahTable [data-edit]').forEach(b=> b.addEventListener('click', ()=>{
        const id = +b.getAttribute('data-edit'); const row = rows.find(x=> +x.id_jenis===id); if (!row) return;
        sId.value = row.id_jenis; sJenis.value = row.jenis; sHarga.value = row.harga; sKat.value = row.id_kategori;
        toast('Form jenis sampah terisi (edit)');
      }));
      $$('#sampahTable [data-del]').forEach(b=> b.addEventListener('click', async ()=>{
        const id = +b.getAttribute('data-del'); if (!confirm('Hapus jenis sampah ini?')) return;
        const fd = new FormData(); fd.append('id_jenis', id);
        const r = await jpost(`${API}?action=deleteSampah`, fd);
        r.success ? (toast('Jenis dihapus'), loadSampah()) : toast(r.error || 'Gagal hapus jenis', false);
      }));
    } catch(e){ console.error(e); sTbody.innerHTML = `<tr><td colspan="5" class="empty">Gagal memuat data</td></tr>`; }
  }
  sForm?.addEventListener('submit', async ev=>{
    ev.preventDefault();
    const jenis = sJenis.value.trim();
    const harga = parseInt(sHarga.value,10) || 0;
    const kat   = parseInt(sKat.value,10) || 0;
    if (!jenis || harga<=0 || kat<=0) return toast('Jenis, harga & kategori wajib diisi', false);
    const fd = new FormData(); fd.append('jenis', jenis); fd.append('harga', harga); fd.append('id_kategori', kat);
    const id = sId.value.trim();
    if (id) fd.append('id_jenis', id);
    const action = id ? 'updateSampah' : 'createSampah';
    const r = await jpost(`${API}?action=${action}`, fd);
    if (r.success){ toast(id ? 'Jenis diperbarui' : 'Jenis ditambahkan'); sForm.reset(); sId.value=''; loadSampah(); }
    else toast(r.error || 'Gagal menyimpan jenis sampah', false);
  });
  $('#sampahReset')?.addEventListener('click', ()=>{ sForm.reset(); sId.value=''; });

  // ===== Init =====
  document.addEventListener('DOMContentLoaded', async ()=>{
    bindNav();
    bindAdminControls();
    bindUserControls();
    initMap();

    await loadStats();
    await loadAdmins();
    await loadUsers();
    await loadPoints();
    await loadKategoriDropdown();
    await loadSampah();
  });
})();
