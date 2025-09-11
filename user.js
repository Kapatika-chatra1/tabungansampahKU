// user.js — Dashboard User (lengkap)
(() => {
  const $  = (s, el=document) => el.querySelector(s);
  const $$ = (s, el=document) => [...el.querySelectorAll(s)];
  const rupiah = n => `Rp ${Intl.NumberFormat('id-ID').format(Math.round(n||0))}`;

  function toast(msg, ok=true){
    const t = $('#toast'); if (!t) return alert(msg);
    t.textContent = msg; t.classList.toggle('bad', !ok);
    t.style.display='block';
    requestAnimationFrame(()=>t.classList.add('show'));
    setTimeout(()=>{ t.classList.remove('show'); setTimeout(()=>t.style.display='none',200); }, 2200);
  }
  const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');

  // ===== Stats =====
  async function loadStats(){
    try{
      const r = await fetch('user.php?action=getStats',{credentials:'same-origin'});
      const j = await r.json();
      $('#statSaldo').textContent = rupiah(j.saldo||0);
      $('#statTotal').textContent = j.total ?? 0;
      $('#statTop').textContent   = j.top ?? '-';
    }catch(e){ console.error(e); }
  }

  // ===== Jenis options =====
  async function loadJenis(){
    const res = await fetch('user.php?action=jenisOptions');
    const rows = await res.json();
    const sel = $('#jenisFilter');
    sel.innerHTML = `<option value="0">Semua Jenis</option>` +
      rows.map(r=>`<option value="${r.id_jenis}">${esc(r.jenis)}</option>`).join('');
  }

  // ===== Transaksi (search + filter + CSV) =====
  const state = { q:'', jenis:0 };
  const tbody = $('#tbody');

  async function loadRows(){
    tbody.innerHTML = `<tr><td colspan="5" class="empty">Memuat…</td></tr>`;
    const url = `user.php?action=listTransactions&q=${encodeURIComponent(state.q)}&jenis=${state.jenis}`;
    try{
      const r = await fetch(url,{credentials:'same-origin'});
      const rows = await r.json();
      if (!Array.isArray(rows) || rows.length===0){
        tbody.innerHTML = `<tr><td colspan="5" class="empty">Tidak ada data</td></tr>`;
        return;
      }
      tbody.innerHTML = rows.map(r=>`
        <tr>
          <td>${esc(r.id_transaksi)}</td>
          <td>${esc(r.nama_user)}</td>
          <td>${esc(r.jenis_sampah)}</td>
          <td>${esc(r.jumlah_setoran)}</td>
          <td>${esc(r.tanggal)}</td>
        </tr>
      `).join('');
    }catch(e){
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="5" class="empty">Gagal memuat</td></tr>`;
    }
  }

  function bindToolbar(){
    const q = $('#q'), clearQ = $('#clearQ'), jenis = $('#jenisFilter');
    let timer=null;
    q.addEventListener('input', e=>{
      state.q = e.target.value.trim();
      clearTimeout(timer); timer = setTimeout(loadRows, 250);
    });
    clearQ.addEventListener('click', ()=>{ q.value=''; state.q=''; loadRows(); });
    jenis.addEventListener('change', ()=>{ state.jenis = parseInt(jenis.value||'0',10)||0; loadRows(); });

    $('#btnReset').addEventListener('click', ()=>{ state.q=''; state.jenis=0; q.value=''; jenis.value='0'; loadRows(); });
    $('#btnCSV').addEventListener('click', ()=>{ window.location.href = `user.php?action=exportCSV&q=${encodeURIComponent(state.q)}&jenis=${state.jenis}`; });
  }

  // ===== Map =====
  function initMap(){
    const el = $('#map'); if (!el) return;
    const map = L.map('map',{zoomControl:true}).setView([-7.9539772,110.1813977],12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:20, attribution:'&copy; OpenStreetMap' }).addTo(map);
    const layer = L.layerGroup().addTo(map);
    const bounds = L.latLngBounds();

    fetch('user.php?action=getPoints',{credentials:'same-origin'})
      .then(r=>r.json())
      .then(rows=>{
        if(!Array.isArray(rows)||!rows.length) return;
        rows.forEach(p=>{
          const lat=+p.lat, lng=+p.lng; if(isNaN(lat)||isNaN(lng)) return;
          const html = `
            <b>${esc(p.name||'')}</b><br/>
            ${esc(p.type||'')}${p.address?'<br/>'+esc(p.address):''}${p.phone?'<br/><small>☎ '+esc(p.phone)+'</small>':''}
            <br/><a target="_blank" rel="noopener" href="https://www.google.com/maps?q=${lat},${lng}">Buka di Google Maps</a>`;
          L.marker([lat,lng]).bindPopup(html).addTo(layer);
          bounds.extend([lat,lng]);
        });
        if(bounds.isValid()) map.fitBounds(bounds.pad(0.2));
      })
      .catch(console.error);
  }

  // ===== Password helpers =====
  function pwStrength(pw){
    let score = 0;
    if (pw.length >= 6) score += 1;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score += 1;
    if (/\d/.test(pw)) score += 1;
    if (/[^A-Za-z0-9]/.test(pw)) score += 1;
    return Math.min(score, 4); // 0..4
  }
  function updateMeter(pw){
    const bar = $('#pwBar')?.parentElement;
    const txt = $('#pwText');
    const s = pwStrength(pw);
    const cls = ['','pw-meter--25','pw-meter--50','pw-meter--75','pw-meter--100'][s];
    ['pw-meter--25','pw-meter--50','pw-meter--75','pw-meter--100'].forEach(c=>bar?.classList.remove(c));
    if (cls) bar?.classList.add(cls);
    const label = ['Sangat lemah','Lemah','Sedang','Cukup','Kuat'][s];
    if (txt) txt.textContent = `Kekuatan: ${label ?? '-'}`;
  }

  // ===== Change password modal =====
  function bindPasswordModal(){
    const modal = $('#modalPass'); const form = $('#passForm');
    const open  = $('#btnChangePass'); const close = $('#closePass'); const back = $('#backdrop');
    const btnSave = $('#btnSavePw');
    const newInput = $('#new_password'); const confInput = $('#confirm_password');

    const show = ()=>{ modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); $('#old_password')?.focus(); updateMeter(''); };
    const hide = ()=>{ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); form.reset(); updateMeter(''); };

    open.addEventListener('click', show);
    close.addEventListener('click', hide);
    back.addEventListener('click', hide);
    document.addEventListener('keydown', e=>{ if(e.key==='Escape') hide(); });

    // toggle show/hide
    modal.querySelectorAll('.pw-toggle').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const inp = btn.previousElementSibling;
        if (!inp) return;
        const showPw = (inp.type === 'password');
        inp.type = showPw ? 'text' : 'password';
        btn.classList.toggle('active', showPw);
        btn.setAttribute('aria-pressed', showPw ? 'true' : 'false');
      });
    });

    // strength meter live
    newInput?.addEventListener('input', e=> updateMeter(e.target.value));

    // submit
    form.addEventListener('submit', async e=>{
      e.preventDefault();
      const oldVal = form.old_password.value.trim();
      const newVal = form.new_password.value; // jangan trim
      const confVal = confInput.value;

      if (!oldVal){ toast('Password lama wajib diisi', false); return; }
      if ((newVal || '').trim().length < 6){ toast('Minimal 6 karakter.', false); return; }
      if (newVal === oldVal){ toast('Password baru tidak boleh sama dengan password lama.', false); return; }
      if (newVal !== confVal){ toast('Konfirmasi password tidak cocok.', false); return; }

      try{
        btnSave.disabled = true;
        btnSave.textContent = 'Menyimpan...';

        const fd = new FormData(form); // berisi old_password & new_password
        const r = await fetch('user.php?action=changePassword',{ method:'POST', body:fd, credentials:'same-origin' });
        let j;
        try { j = await r.json(); }
        catch { j = { success:false, error:'Respon tidak valid' }; }

        if(j.success){ toast('Password berhasil diganti'); hide(); }
        else{ toast(j.error || 'Gagal ganti password', false); }
      }catch(err){
        toast('Jaringan bermasalah', false);
      }finally{
        btnSave.disabled = false;
        btnSave.textContent = 'Simpan';
      }
    });
  }

  // ===== Init =====
  document.addEventListener('DOMContentLoaded', async ()=>{
    bindToolbar();
    bindPasswordModal();
    await loadJenis();
    await loadStats();
    await loadRows();
    initMap();
  });
})();
