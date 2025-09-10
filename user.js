// user.js
(() => {
  const $ = (s, el=document) => el.querySelector(s);
  const $$ = (s, el=document) => [...el.querySelectorAll(s)];

  const fmtRupiah = n => 'Rp ' + (Number(n)||0).toLocaleString('id-ID');

  const state = { q:'', id_jenis: 0 };

  // ===== Fetch helpers
  async function jget(url){
    const r = await fetch(url, {credentials:'same-origin'});
    if(!r.ok) throw new Error(r.status);
    return r.json();
  }

  // ===== Stats
  async function loadStats(){
    try {
      const s = await jget(`${USER_API}?action=stats`);
      $('#saldoNum').textContent = fmtRupiah(s.saldo);
      $('#totalNum').textContent = s.total ?? 0;
      $('#topJenis').textContent = s.top ?? '—';
    } catch(e){ console.error(e); }
  }

  // ===== Jenis dropdown
  async function loadJenis(){
    try {
      const rows = await jget(`${USER_API}?action=jenisList`);
      const sel = $('#jenisFilter');
      const cur = sel.value;
      sel.innerHTML = `<option value="0">Semua Jenis</option>` + rows.map(r =>
        `<option value="${r.id_jenis}">${r.jenis}</option>`
      ).join('');
      if (cur) sel.value = cur;
    } catch(e){ console.error(e); }
  }

  // ===== Transactions table
  async function loadTrans(){
    const tbody = $('#tTrans tbody');
    tbody.innerHTML = `<tr><td colspan="5" class="empty">Memuat…</td></tr>`;
    const url = `${USER_API}?action=transactions&q=${encodeURIComponent(state.q)}&id_jenis=${state.id_jenis}`;
    try {
      const rows = await jget(url);
      if(!rows.length){
        tbody.innerHTML = `<tr><td colspan="5" class="empty">Tidak ada data</td></tr>`;
        return;
      }
      tbody.innerHTML = rows.map(r => `
        <tr>
          <td>${r.id_trans}</td>
          <td>${escapeHtml(r.nama)}</td>
          <td>${escapeHtml(r.jenis)}</td>
          <td>${r.jumlah_setoran}</td>
          <td>${r.tanggal}</td>
        </tr>
      `).join('');
    } catch(e){
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="5" class="empty">Gagal memuat</td></tr>`;
    }
  }

  // ===== CSV
  function exportCSV(){
    const url = `${USER_API}?action=exportCSV&q=${encodeURIComponent(state.q)}&id_jenis=${state.id_jenis}`;
    window.location.href = url;
  }

  // ===== Map
  let map, markers;
  async function initMap(){
    map = L.map('map', { zoomControl:true }).setView([-7.9539, 110.1813], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
      maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    markers = L.layerGroup().addTo(map);
    await loadPoints();
  }
  async function loadPoints(){
    try{
      markers.clearLayers();
      const pts = await jget(`${USER_API}?action=points`);
      if(!pts.length) return;
      const group = [];
      pts.forEach(p => {
        const m = L.marker([p.lat, p.lng]).bindPopup(
          `<b>${escapeHtml(p.name)}</b><br>${escapeHtml(p.type)}<br>${escapeHtml(p.address||'')}`
        );
        m.addTo(markers);
        group.push(m);
      });
      if(group.length){
        const fg = L.featureGroup(group);
        map.fitBounds(fg.getBounds().pad(0.15));
      }
    }catch(e){ console.error(e); }
  }

  // ===== Change password
  function bindPasswordModal(){
    const dlg = $('#pwdModal');
    $('#btnChangePwd').addEventListener('click', () => dlg.showModal());
    $('#closePwd').addEventListener('click', () => dlg.close());

    $('#pwdForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData();
      fd.append('old', $('#oldPwd').value);
      fd.append('new', $('#newPwd').value);
      try{
        const r = await fetch(`${USER_API}?action=changePassword`, { method:'POST', body:fd, credentials:'same-origin' });
        const j = await r.json();
        const msg = $('#pwdMsg');
        if(j.success){
          msg.textContent = 'Password berhasil diganti ✅';
          msg.className = 'msg ok';
          setTimeout(()=> dlg.close(), 800);
          e.target.reset();
        } else {
          msg.textContent = j.error || 'Gagal ganti password';
          msg.className = 'msg err';
        }
      }catch(err){
        const msg = $('#pwdMsg'); msg.textContent='Gagal jaringan'; msg.className='msg err';
      }
    });
  }

  // ===== Utils
  function escapeHtml(s){ return String(s ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

  function debounce(fn, ms=280){
    let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); };
  }

  // ===== Bind UI
  function bindFilters(){
    const onSearch = debounce(() => {
      state.q = $('#q').value.trim();
      loadTrans();
    }, 260);

    $('#q').addEventListener('input', onSearch);
    $('#clearQ').addEventListener('click', () => { $('#q').value=''; state.q=''; loadTrans(); });

    $('#jenisFilter').addEventListener('change', () => {
      state.id_jenis = parseInt($('#jenisFilter').value,10)||0;
      loadTrans();
    });

    $('#btnCSV').addEventListener('click', exportCSV);
    $('#btnReset').addEventListener('click', () => {
      state.q=''; state.id_jenis=0;
      $('#q').value=''; $('#jenisFilter').value='0';
      loadTrans();
    });
  }

  // ===== Init
  document.addEventListener('DOMContentLoaded', async () => {
    bindFilters();
    bindPasswordModal();
    await Promise.all([loadStats(), loadJenis()]);
    await loadTrans();
    await initMap();
  });
})();
