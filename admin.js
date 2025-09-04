// admin.js (versi aman)

const form = document.getElementById('transaksiForm');
const tbody = document.querySelector('#riwayat tbody');

// Load data transaksi
function loadData() {
  fetch('admin.php?action=read')
    .then(res => res.json())
    .then(data => {
      tbody.innerHTML = '';
      data.forEach(row => {
        tbody.innerHTML += `
          <tr>
            <td>${row.id_trans}</td>
            <td>${row.nama}</td>
            <td>${row.jenis_sampah}</td>
            <td>${row.jumlah_setoran}</td>
            <td>${row.tanggal}</td>
            <td>
              <button class="btn btn-edit" onclick="editData(${row.id_trans}, '${row.jenis_sampah}', ${row.jumlah_setoran})">Edit</button>
              <button class="btn btn-delete" onclick="deleteData(${row.id_trans})">Hapus</button>
            </td>
          </tr>
        `;
      });
    });
}

// Tambah transaksi
form.addEventListener('submit', e => {
  e.preventDefault();
  const formData = new FormData();
  formData.append('nama', document.getElementById('nama').value);
  formData.append('jenis', document.getElementById('jenis').value);
  formData.append('jumlah', document.getElementById('jumlah').value);

  fetch('admin.php?action=create', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        form.reset();
        loadData();
      } else {
        alert(res.error);
      }
    });
});

// Edit transaksi
function editData(id, jenis, jumlah) {
  const newJenis = prompt("Jenis Sampah:", jenis);
  const newJumlah = prompt("Jumlah (kg):", jumlah);

  if (newJenis && newJumlah) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('jenis', newJenis);
    formData.append('jumlah', newJumlah);

    fetch('admin.php?action=update', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(res => {
        if (res.success) loadData();
      });
  }
}

// Hapus transaksi
function deleteData(id) {
  if (confirm("Yakin ingin menghapus transaksi ini?")) {
    const formData = new FormData();
    formData.append('id', id);

    fetch('admin.php?action=delete', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(res => {
        if (res.success) loadData();
      });
  }
}

// ====================== USER ======================
const userForm = document.getElementById('userForm');
const userTbody = document.querySelector('#userTable tbody');

// Load user (aman, hanya jalan kalau tabel ada)
function loadUser() {
  if (!userTbody) return; // biar gak error kalau tabel belum ada
  fetch('admin.php?action=readUser')
    .then(res => res.json())
    .then(data => {
      userTbody.innerHTML = '';
      data.forEach(row => {
        userTbody.innerHTML += `
          <tr>
            <td>${row.id_user}</td>
            <td>${row.nama}</td>
            <td>${row.no_hp}</td>
            <td>${row.alamat}</td>
            <td>${row.role}</td>
          </tr>
        `;
      });
    });
}

// Tambah user (aman, hanya jalan kalau form ada)
if (userForm) {
  userForm.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('nama', document.getElementById('user_nama').value);
    formData.append('no_hp', document.getElementById('user_hp').value);
    formData.append('alamat', document.getElementById('user_alamat').value);

    fetch('admin.php?action=createUser', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          userForm.reset();
          loadUser();
        } else {
          alert(res.error);
        }
      });
  });
}

// Jalankan saat halaman dibuka
loadData();
loadUser();
