/* ================================
   admin.js — Data logic / API
================================== */

// Elements
const transaksiForm  = document.getElementById('transaksiForm');
const transaksiTbody = document.querySelector('#riwayat tbody');
const jenisSelect    = document.getElementById('jenis');
const elJumlah       = document.getElementById('jumlah');
const elHarga        = document.getElementById('hargaPerKg');
const elTotal        = document.getElementById('totalNominal');

const userForm  = document.getElementById('userForm');
const userTbody = document.querySelector('#userTable tbody');

const sampahForm     = document.getElementById('sampahForm');
const sampahTbody    = document.querySelector('#sampahTable tbody');
const kategoriSelect = document.getElementById('sampah_kategori');

let jenisList = [];      // {id, name, harga, catId}
let kategoriList = [];   // {id, name}

// Helpers
const IDR = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' });
const fmtDateTime = (s) => {
  if (!s) return '';
  try { const d = new Date(s.replace(' ', 'T')); return isNaN(d.getTime()) ? String(s) : d.toLocaleString('id-ID'); }
  catch { return String(s); }
};

/* ==== Fetch helper: baca body SEKALI, lalu parse JSON ==== */
async function api(url, opts = {}) {
  const r = await fetch(url, { credentials: 'same-origin', ...opts });
  if (r.status === 401 || r.status === 403) {
    alert('Sesi berakhir/unauthorized.');
    try { location.href = 'login.php'; } catch {}
    throw new Error('Unauthorized');
  }
  const txt = await r.text(); // baca sekali
  if (!r.ok) throw new Error(txt || `HTTP ${r.status}`);
  let data;
  try { data = JSON.parse(txt); } catch { throw new Error(txt || 'Respon bukan JSON'); }
  if (data && typeof data === 'object' && data.error) throw new Error(data.error);
  return data;
}
function escapeHtml(str){
  return (str===null||str===undefined) ? '' :
    String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;')
               .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// =================== Kalkulasi langsung ===================
function getHargaTerpilih() {
  const opt = jenisSelect?.selectedOptions?.[0];
  if (opt && opt.dataset.harga) return Number(opt.dataset.harga) || 0;
  const id = Number(jenisSelect?.value || 0);
  const f = jenisList.find(x => x.id === id);
  return f ? Number(f.harga) || 0 : 0;
}
function updateCalc() {
  if (!elHarga || !elTotal) return;
  const harga = getHargaTerpilih();
  const kg    = Number(elJumlah?.value || 0);
  elHarga.textContent = IDR.format(harga || 0);
  elTotal.textContent = IDR.format((harga > 0 && kg > 0) ? harga * kg : 0);
}
jenisSelect?.addEventListener('change', updateCalc);
elJumlah?.addEventListener('input', updateCalc);

// =================== Kategori ===================
async function loadKategori() {
  try {
    const data = await api('admin.php?action=readKategori');
    if (!Array.isArray(data)) return console.error('readKategori unexpected', data);

    kategoriList = data.map(k => ({ id:Number(k.id_kategori), name:(k.kategori ?? k.nama_kategori ?? '').toString() }));

    if (kategoriSelect) {
      kategoriSelect.innerHTML = '<option value="">-- Pilih Kategori --</option>';
      kategoriList.forEach(k => {
        const opt = document.createElement('option');
        opt.value = String(k.id);
        opt.textContent = k.name;
        kategoriSelect.appendChild(opt);
      });
    }
  } catch (err) { console.error('LoadKategori error:', err); }
}

// =================== Jenis Sampah ===================
function renderJenisDropdown(list){
  if (!jenisSelect) return;
  jenisSelect.innerHTML = '<option value="">-- Pilih Jenis Sampah --</option>';
  list.forEach(j => {
    const opt = document.createElement('option');
    opt.value = String(j.id);
    opt.textContent = `${j.name} (${IDR.format(j.harga)}/kg)`;
    opt.dataset.harga = String(j.harga);
    jenisSelect.appendChild(opt);
  });
}

async function loadSampah() {
  try {
    const data = await api('admin.php?action=readSampah');
    if (!Array.isArray(data)) return console.error('readSampah unexpected', data);

    jenisList = data.map(r => ({
      id: Number(r.id_jenis),
      name: (r.nama_jenis ?? r.jenis ?? r.nama ?? '').toString(),
      harga: Number(r.harga) || 0,
      catId: r.id_kategori ? Number(r.id_kategori) : null
    }));

    renderJenisDropdown(jenisList);
    updateCalc();

    if (sampahTbody) {
      sampahTbody.innerHTML = '';
      jenisList.forEach(j => {
        sampahTbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td>${escapeHtml(String(j.id))}</td>
            <td>${escapeHtml(j.name)}</td>
            <td>${escapeHtml(IDR.format(j.harga))}</td>
            <td>
              <button class="btn sm" onclick="editJenis(${j.id})">Edit</button>
              <button class="btn danger sm" onclick="deleteJenis(${j.id})">Hapus</button>
            </td>
          </tr>
        `);
      });
    }
  } catch (err) { console.error('LoadSampah error:', err); }
}

// =================== Transaksi ===================
async function loadData() {
  if (!transaksiTbody) return;
  try {
    const data = await api('admin.php?action=read');
    if (!Array.isArray(data)) return console.error('read(transactions) unexpected', data);

    transaksiTbody.innerHTML = '';
    data.forEach(row => {
      const displayJenis = (row.nama_jenis ?? row.jenis_sampah ?? row.jenis ?? '').toString();
      let resolvedId = (row.id_jenis !== undefined && row.id_jenis !== null) ? Number(row.id_jenis) : null;
      if (!resolvedId) { const f = jenisList.find(j => j.name === displayJenis); if (f) resolvedId = f.id; }

      transaksiTbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${escapeHtml(String(row.id_trans ?? ''))}</td>
          <td>${escapeHtml(row.nama ?? '')}</td>
          <td>${escapeHtml(displayJenis)}</td>
          <td>${escapeHtml(String(row.jumlah_setoran ?? ''))}</td>
          <td>${escapeHtml(fmtDateTime(row.tanggal))}</td>
          <td class="hide-mobile">
            <button class="btn sm" onclick="editData(${Number(row.id_trans)}, ${resolvedId === null ? 'null' : Number(resolvedId)}, ${Number(row.jumlah_setoran)})">Edit</button>
            <button class="btn danger sm" onclick="deleteData(${Number(row.id_trans)})">Hapus</button>
          </td>
        </tr>
      `);
    });
  } catch (err) { console.error('LoadData error:', err); alert('Gagal memuat transaksi: '+err.message); }
}

// submit transaksi
if (transaksiForm) {
  transaksiForm.addEventListener('submit', async e => {
    e.preventDefault();
    const nama   = (document.getElementById('nama')?.value || '').trim();
    const idJenis= (document.getElementById('jenis')?.value || '').trim();
    const jumlah = (document.getElementById('jumlah')?.value || '').trim();

    if (!nama || !idJenis || Number(idJenis) <= 0 || !jumlah || Number(jumlah) <= 0) {
      alert('Nama, jenis, dan jumlah harus diisi dengan benar.');
      return;
    }

    const formData = new FormData();
    formData.append('nama', nama);
    formData.append('id_jenis', idJenis);
    formData.append('jumlah', jumlah);

    try {
      const res = await api('admin.php?action=create', { method:'POST', body:formData });
      if (res?.success) {
        const harga = getHargaTerpilih();
        const total = Number(jumlah) * (harga || 0);
        alert(`Transaksi tersimpan.\n\nRincian:\nHarga/kg: ${IDR.format(harga)}\nJumlah: ${jumlah} kg\nTotal: ${IDR.format(total)}`);
        transaksiForm.reset();
        updateCalc();
        await loadData();
      } else {
        alert(res?.error || 'Gagal menambah transaksi');
      }
    } catch (err) { alert('Kesalahan saat menyimpan transaksi: '+err.message); }
  });

  transaksiForm.addEventListener('reset', () => setTimeout(updateCalc, 0));
}

// edit transaksi
window.editData = async function(idTrans, currentJenisId, currentJumlah){
  const options = jenisList.map(j => `${j.id} - ${j.name}`).join('\n');
  let jenisPrompt = `Pilih ID jenis sampah (kosong = tidak ubah):\n${options}\n\nMasukkan ID:`;
  let newJenisInput = prompt(jenisPrompt, currentJenisId !== null ? String(currentJenisId) : '');
  if (newJenisInput === null) return;
  newJenisInput = newJenisInput.trim();
  let newIdJenis = newJenisInput === '' ? currentJenisId : parseInt(newJenisInput, 10);
  if (isNaN(newIdJenis) || !jenisList.find(j => j.id === newIdJenis)) { alert('ID jenis tidak valid'); return; }

  let newJumlahInput = prompt('Jumlah (kg):', String(currentJumlah ?? ''));
  if (newJumlahInput === null) return;
  const newJumlah = parseInt(newJumlahInput.trim(), 10);
  if (isNaN(newJumlah) || newJumlah <= 0) { alert('Jumlah tidak valid'); return; }

  const fd = new FormData();
  fd.append('id', String(idTrans));
  fd.append('id_jenis', String(newIdJenis));
  fd.append('jumlah', String(newJumlah));

  try {
    const res = await api('admin.php?action=update', { method:'POST', body:fd });
    if (res?.success) await loadData();
    else alert(res?.error || 'Gagal update transaksi');
  } catch (err) { alert('Kesalahan saat update: '+err.message); }
};

// hapus transaksi
window.deleteData = async function(id){
  if (!confirm('Yakin ingin menghapus transaksi ini?')) return;
  const fd = new FormData(); fd.append('id', String(id));
  try {
    const res = await api('admin.php?action=delete', { method:'POST', body:fd });
    if (res?.success) await loadData();
    else alert(res?.error || 'Gagal menghapus transaksi');
  } catch (err) { alert('Kesalahan saat menghapus transaksi: '+err.message); }
};

// =================== User (read-only list + create) ===================
async function loadUser() {
  if (!userTbody) return;
  try {
    const data = await api('admin.php?action=readUser');
    if (!Array.isArray(data)) return console.error('readUser unexpected', data);

    userTbody.innerHTML = '';
    data.forEach(row => {
      userTbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${escapeHtml(String(row.id_user))}</td>
          <td>${escapeHtml(row.nama)}</td>
          <td>${escapeHtml(row.no_hp)}</td>
          <td>${escapeHtml(row.alamat ?? '')}</td>
          <td>${escapeHtml(row.role)}</td>
        </tr>
      `);
    });

    if (typeof window.applyUserFilters === 'function') window.applyUserFilters();
  } catch (err) { console.error('LoadUser error:', err); }
}

if (userForm) {
  userForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const nama = document.getElementById('user_nama').value.trim();
    const hp   = document.getElementById('user_hp').value.trim();
    const al   = document.getElementById('user_alamat').value.trim();
    if (!nama || !hp) return alert('Nama dan No HP wajib diisi.');

    const fd = new FormData();
    fd.append('nama', nama);
    fd.append('no_hp', hp);
    fd.append('alamat', al);
    try {
      const res = await api('admin.php?action=createUser', { method:'POST', body:fd });
      if (res?.success) {
        userForm.reset();
        await loadUser();
        alert('User berhasil ditambahkan (role: user).');
      } else alert(res?.error || 'Gagal menambah user.');
    } catch (err) { alert('Kesalahan jaringan: ' + err.message); }
  });
}

// =================== CRUD Jenis Sampah ===================
if (sampahForm) {
  sampahForm.addEventListener('submit', async e => {
    e.preventDefault();
    const idKategori = kategoriSelect?.value || '';
    const nama = document.getElementById('sampah_nama').value.trim();
    const harga = document.getElementById('sampah_harga').value.trim();

    if (!idKategori || !nama || !harga || Number(harga) <= 0) {
      alert('Semua field harus diisi dengan benar.');
      return;
    }

    const fd = new FormData();
    fd.append('id_kategori', idKategori);
    fd.append('nama_jenis', nama);
    fd.append('harga', harga);

    try {
      const res = await api('admin.php?action=createJenis', { method:'POST', body:fd });
      if (res?.success) {
        sampahForm.reset();
        await Promise.all([loadSampah(), loadKategori()]);
      } else alert(res?.error || 'Gagal menambah jenis sampah.');
    } catch (err) { alert('Kesalahan saat menambah jenis sampah: '+err.message); }
  });
}

window.editJenis = async function(id){
  const j = jenisList.find(x => x.id === id);
  if (!j) return alert('Jenis tidak ditemukan.');

  const namaBaru  = prompt('Nama jenis:', j.name);
  if (namaBaru === null) return;

  const hargaBaru = prompt('Harga/kg (angka):', String(j.harga));
  if (hargaBaru === null) return;
  const h = parseInt(String(hargaBaru).trim(), 10);
  if (isNaN(h) || h <= 0) return alert('Harga tidak valid.');

  const opts = kategoriList.map(k => `${k.id} - ${k.name}`).join('\n');
  let katInput = prompt(`Pilih ID Kategori (kosong = tetap ${j.catId ?? '-'})\n${opts}\n\nMasukkan ID:`, '');
  if (katInput === null) return;
  katInput = katInput.trim();
  let katId = j.catId;
  let sendCat = false;
  if (katInput !== '') {
    const parsed = parseInt(katInput,10);
    if (isNaN(parsed) || !kategoriList.find(k => k.id === parsed)) return alert('ID kategori tidak valid.');
    katId = parsed; sendCat = true;
  }

  const fd = new FormData();
  fd.append('id_jenis', String(id));
  fd.append('nama_jenis', namaBaru.trim());
  fd.append('harga', String(h));
  if (sendCat) fd.append('id_kategori', String(katId));

  try {
    const res = await api('admin.php?action=updateJenis', { method:'POST', body:fd });
    if (res?.success) await loadSampah();
    else alert(res?.error || 'Gagal update jenis.');
  } catch (e) { alert('Kesalahan saat update jenis: ' + e.message); }
};

window.deleteJenis = async function(id){
  if (!confirm('Hapus jenis ini? Jika sudah dipakai transaksi, penghapusan ditolak.')) return;
  const fd = new FormData(); fd.append('id_jenis', String(id));
  try {
    const res = await api('admin.php?action=deleteJenis', { method:'POST', body:fd });
    if (res?.success) await loadSampah();
    else alert(res?.error || 'Gagal menghapus jenis.');
  } catch (e) { alert('Kesalahan saat menghapus jenis: '+e.message); }
};

// =================== Ganti Password (Admin) ===================
(function(){
  const openBtn = document.getElementById('openPw');
  const modal   = document.getElementById('pwModal');
  if (!openBtn || !modal) return;

  const form    = document.getElementById('pwForm');
  const cancel  = document.getElementById('pwCancel');
  const back    = document.getElementById('pwBackdrop');
  const saveBtn = document.getElementById('pwSave');
  const oldI    = document.getElementById('old_password');
  const newI    = document.getElementById('new_password');
  const confI   = document.getElementById('confirm_password');
  const barWrap = document.getElementById('pwBar')?.parentElement;
  const txt     = document.getElementById('pwText');

  const show = ()=>{ modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); oldI?.focus(); updateMeter(''); };
  const hide = ()=>{ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); form.reset(); updateMeter(''); };

  openBtn.addEventListener('click', show);
  cancel.addEventListener('click', hide);
  back.addEventListener('click', hide);
  document.addEventListener('keydown', e=>{ if(e.key==='Escape') hide(); });

  // eye toggle
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

  function pwStrength(pw){
    let s = 0;
    if (pw.length >= 6) s++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) s++;
    if (/\d/.test(pw)) s++;
    if (/[^A-Za-z0-9]/.test(pw)) s++;
    return Math.min(s,4); // 0..4
  }
  function updateMeter(pw){
    const s = pwStrength(pw);
    const cls = ['','pw-meter--25','pw-meter--50','pw-meter--75','pw-meter--100'][s];
    ['pw-meter--25','pw-meter--50','pw-meter--75','pw-meter--100'].forEach(c=>barWrap?.classList.remove(c));
    if (cls) barWrap?.classList.add(cls);
    const label = ['Sangat lemah','Lemah','Sedang','Cukup','Kuat'][s];
    if (txt) txt.textContent = `Kekuatan: ${label ?? '-'}`;
  }
  newI?.addEventListener('input', e=> updateMeter(e.target.value));

  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const oldV = (oldI.value || '').trim();
    const newV = newI.value;            // jangan trim
    const cfV  = confI.value;

    if (!oldV){ alert('Password lama wajib diisi.'); return; }
    if ((newV || '').trim().length < 6){ alert('Minimal 6 karakter.'); return; }
    if (newV === oldV){ alert('Password baru tidak boleh sama dengan password lama.'); return; }
    if (newV !== cfV){ alert('Konfirmasi password tidak cocok.'); return; }

    try{
      saveBtn.disabled = true; saveBtn.textContent = 'Menyimpan...';
      const fd = new FormData(form); // old_password & new_password
      const res = await fetch('admin.php?action=changePassword', { method:'POST', body:fd, credentials:'same-origin' });
      const text = await res.text();
      let j; try{ j = JSON.parse(text); } catch{ j = {success:false, error:text||'Respon tidak valid'}; }

      if (j.success){ alert('Password berhasil diganti.'); hide(); }
      else{ alert(j.error || 'Gagal ganti password.'); }
    } catch(err){
      alert('Kesalahan jaringan: '+err.message);
    } finally {
      saveBtn.disabled = false; saveBtn.textContent = 'Simpan';
    }
  });
})();

// =================== Init ===================
window.addEventListener('DOMContentLoaded', async () => {
  await loadSampah();
  await Promise.all([loadKategori(), loadUser(), loadData()]);
  updateCalc();
});
