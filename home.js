document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("transaksiForm");
  const tbody = document.querySelector("#riwayat tbody");

  // Load transaksi saat halaman dibuka
  loadTransaksi();

  function loadTransaksi() {
    fetch("transaksi.php?action=read")
      .then(res => res.json())
      .then(data => {
        tbody.innerHTML = "";
        data.forEach(row => {
          tbody.innerHTML += `
            <tr>
              <td>${row.nama}</td>
              <td>${row.jenis_sampah}</td>
              <td>${row.jumlah}</td>
              <td>
                <button onclick="hapus(${row.id_transaksi})">Hapus</button>
              </td>
            </tr>
          `;
        });
      });
  }

  form.addEventListener("submit", e => {
    e.preventDefault();
    const fd = new FormData(form);
    fetch("transaksi.php?action=create", {
      method: "POST",
      body: fd
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        form.reset();
        loadTransaksi();
      } else {
        alert(res.error);
      }
    });
  });

  window.hapus = (id) => {
    let fd = new FormData();
    fd.append("id", id);
    fetch("transaksi.php?action=delete", {
      method: "POST",
      body: fd
    }).then(() => loadTransaksi());
  };
});
