const form = document.getElementById("transaksiForm");
const riwayat = document.querySelector("#riwayat tbody");

form.addEventListener("submit", function (e) {
  e.preventDefault();

  const nama = document.getElementById("nama").value;
  const jenis = document.getElementById("jenis").value;
  const jumlah = document.getElementById("jumlah").value;

  // Tambah ke tabel
  const row = document.createElement("tr");
  row.innerHTML = `
    <td>${nama}</td>
    <td>${jenis}</td>
    <td>${jumlah}</td>
  `;
  riwayat.appendChild(row);

  // Reset form
  form.reset();
});
