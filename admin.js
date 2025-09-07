// ---------------------- ELEMENTS ----------------------
const transaksiForm = document.getElementById('transaksiForm');
const transaksiTbody = document.querySelector('#riwayat tbody');
const jenisSelect = document.getElementById('jenis');

const userForm = document.getElementById('userForm');
const userTbody = document.querySelector('#userTable tbody');

const sampahForm = document.getElementById('sampahForm');
const sampahTbody = document.querySelector('#sampahTable tbody');
const kategoriSelect = document.getElementById('sampah_kategori');

let jenisList = []; // cache daftar jenis {id, name, harga}

// ---------------------- UTIL ----------------------
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;');
}

// ---------------------- LOAD JENIS SAMPAH ----------------------
function loadSampah() {
  fetch('admin.php?action=readSampah')
    .then(res => res.json())
    .then(data => {
      if (!Array.isArray(data)) { console.error('readSampah unexpected', data); return; }

      jenisList = data.map(r => ({
        id: r.id_jenis,
        name: (r.nama_jenis ?? r.jenis ?? r.nama ?? '').toString(),
        harga: Number(r.harga) || 0
      }));

      // dropdown transaksi
      if (jenisSelect) {
        jenisSelect.innerHTML = '<option value="">-- Pilih Jenis Sampah --</option>';
        jenisList.forEach(j => {
          const opt = document.createElement('option');
          opt.value = j.id;
          opt.textContent = `${j.name} (Rp ${j.harga}/kg)`;
          jenisSelect.appendChild(opt);
        });
      }

      // tabel daftar jenis sampah
      if (sampahTbody) {
        sampahTbody.innerHTML = '';
        jenisList.forEach(j => {
          sampahTbody.insertAdjacentHTML('beforeend', `
            <tr>
              <td>${escapeHtml(String(j.id))}</td>
              <td>${escapeHtml(j.name)}</td>
              <td>Rp ${escapeHtml(String(j.harga))}</td>
            </tr>
          `);
        });
      }
    })
    .catch(err => console.error('LoadSampah error:', err));
}

// ---------------------- LOAD TRANSAKSI ----------------------
function loadData() {
  if (!transaksiTbody) return;

  fetch('admin.php?action=read')
    .then(res => res.json())
    .then(data => {
      if (!Array.isArray(data)) { console.error('read(transactions) unexpected', data); return; }
      transaksiTbody.innerHTML = '';

      data.forEach(row => {
        const displayJenis = (row.nama_jenis ?? row.jenis_sampah ?? row.jenis ?? '').toString();
        let resolvedId = (row.id_jenis !== undefined && row.id_jenis !== null) ? Number(row.id_jenis) : null;
        if (!resolvedId) {
          const found = jenisList.find(j => j.name === displayJenis);
          if (found) resolvedId = found.id;
        }

        transaksiTbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td>${escapeHtml(String(row.id_trans ?? ''))}</td>
            <td>${escapeHtml(row.nama ?? '')}</td>
            <td>${escapeHtml(displayJenis)}</td>
            <td>${escapeHtml(String(row.jumlah_setoran ?? ''))}</td>
            <td>${escapeHtml(String(row.tanggal ?? ''))}</td>
            <td>
              <button class="btn btn-edit" onclick="editData(${Number(row.id_trans)}, ${resolvedId === null ? 'null' : Number(resolvedId)}, ${Number(row.jumlah_setoran)})">Edit</button>
              <button class="btn btn-delete" onclick="deleteData(${Number(row.id_trans)})">Hapus</button>
            </td>
          </tr>
        `);
      });
    })
    .catch(err => console.error('LoadData error:', err));
}

// ---------------------- CREATE TRANSAKSI ----------------------
if (transaksiForm) {
  transaksiForm.addEventListener('submit', e => {
    e.preventDefault();
    const nama = (document.getElementById('nama')?.value || '').trim();
    const idJenis = (document.getElementById('jenis')?.value || '').trim();
    const jumlah = (document.getElementById('jumlah')?.value || '').trim();

    if (!nama || !idJenis || Number(idJenis) <= 0 || !jumlah || Number(jumlah) <= 0) {
      alert('Nama, jenis dan jumlah harus diisi dengan benar.');
      return;
    }

    const formData = new FormData();
    formData.append('nama', nama);
    formData.append('id_jenis', idJenis);
    formData.append('jumlah', jumlah);

    fetch('admin.php?action=create', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(res => { if (res?.success) { transaksiForm.reset(); loadData(); } else alert(res?.error || 'Gagal menambah transaksi'); })
      .catch(err => { console.error(err); alert('Kesalahan jaringan saat menyimpan transaksi.'); });
  });
}

// ---------------------- EDIT TRANSAKSI ----------------------
window.editData = function(idTrans, currentJenisId, currentJumlah) {
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

  const formData = new FormData();
  formData.append('id', String(idTrans));
  formData.append('id_jenis', String(newIdJenis));
  formData.append('jumlah', String(newJumlah));

  fetch('admin.php?action=update', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => { if (res?.success) loadData(); else alert(res?.error || 'Gagal update transaksi'); })
    .catch(err => { console.error(err); alert('Kesalahan jaringan saat update.'); });
};

// ---------------------- DELETE TRANSAKSI ----------------------
function deleteData(id) {
  if (!confirm('Yakin ingin menghapus transaksi ini?')) return;
  const formData = new FormData();
  formData.append('id', String(id));

  fetch('admin.php?action=delete', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => { if (res?.success) loadData(); else alert(res?.error || 'Gagal menghapus transaksi'); })
    .catch(err => { console.error(err); alert('Kesalahan jaringan saat menghapus.'); });
}
window.deleteData = deleteData;

// ---------------------- LOAD USER ----------------------
function loadUser() {
  if (!userTbody) return;
  fetch('admin.php?action=readUser')
    .then(res => res.json())
    .then(data => {
      if (!Array.isArray(data)) { console.error('readUser unexpected', data); return; }
      userTbody.innerHTML = '';
      data.forEach(row => {
        userTbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td>${escapeHtml(String(row.id_user))}</td>
            <td>${escapeHtml(row.nama)}</td>
            <td>${escapeHtml(row.no_hp)}</td>
            <td>${escapeHtml(row.alamat)}</td>
            <td>${escapeHtml(row.role)}</td>
          </tr>
        `);
      });
    })
    .catch(err => console.error('LoadUser error:', err));
}

// ---------------------- LOAD KATEGORI ----------------------
function loadKategori() {
  fetch('admin.php?action=readKategori')
    .then(res => res.json())
    .then(data => {
      if (!Array.isArray(data)) {
        console.error('readKategori unexpected', data);
        return;
      }

      if (kategoriSelect) {
        kategoriSelect.innerHTML = '<option value="">-- Pilih Kategori --</option>';
        data.forEach(k => {
          const opt = document.createElement('option');
          opt.value = k.id_kategori;
          // sesuaikan properti nama
          opt.textContent = k.kategori ?? k.nama_kategori ?? '';
          kategoriSelect.appendChild(opt);
        });
      }
    })
    .catch(err => console.error('LoadKategori error:', err));
}

// ---------------------- CREATE USER ----------------------
if (userForm) {
  userForm.addEventListener('submit', e => {
    e.preventDefault();
    const nama = (document.getElementById('user_nama')?.value || '').trim();
    const noHp = (document.getElementById('user_hp')?.value || '').trim();
    const alamat = (document.getElementById('user_alamat')?.value || '').trim();

    if (!nama || !noHp) { alert('Nama dan No HP harus diisi.'); return; }

    const formData = new FormData();
    formData.append('nama', nama);
    formData.append('no_hp', noHp);
    formData.append('alamat', alamat);

    fetch('admin.php?action=createUser', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(res => {
        if (res?.success) { userForm.reset(); loadUser(); }
        else alert(res?.error || 'Gagal menambah user.');
      })
      .catch(err => { console.error('CreateUser error:', err); alert('Kesalahan jaringan saat menambah user.'); });
  });
}

// ---------------------- TAMBAH JENIS SAMPAH ----------------------
if (sampahForm) {
  sampahForm.addEventListener('submit', e => {
    e.preventDefault();
    const idKategori = kategoriSelect.value;
    const nama = document.getElementById('sampah_nama').value.trim();
    const harga = document.getElementById('sampah_harga').value.trim();

    if (!idKategori || !nama || !harga || Number(harga) <= 0) {
      alert('Semua field harus diisi dengan benar.');
      return;
    }

    const formData = new FormData();
    formData.append('id_kategori', idKategori);
    formData.append('nama_jenis', nama);
    formData.append('harga', harga);

    fetch('admin.php?action=createJenis', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(res => {
        if (res?.success) { sampahForm.reset(); loadSampah(); }
        else alert(res?.error || 'Gagal menambah jenis sampah.');
      })
      .catch(err => { console.error('CreateJenis error:', err); alert('Kesalahan jaringan saat menambah jenis sampah.'); });
  });
}

// ---------------------- INIT ----------------------
loadKategori();
loadSampah();
loadUser();
loadData();
