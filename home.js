// ===== SLIDER =====
document.addEventListener('DOMContentLoaded', () => {
  const slides = [...document.querySelectorAll('.slide')];
  if (!slides.length) return;

  // Aktifkan slide pertama jika belum
  if (!document.querySelector('.slide.active')) slides[0].classList.add('active');

  // Auto-play
  let i = slides.findIndex(s => s.classList.contains('active'));
  const next = () => {
    slides[i].classList.remove('active');
    i = (i + 1) % slides.length;
    slides[i].classList.add('active');
  };
  setInterval(next, 5000);
});

// ===== MOBILE MENU =====
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const mobileMenu       = document.getElementById('mobileMenu');
const mobileNavLinks   = document.querySelectorAll('.mobile-nav-links a');
const closeBtn         = document.getElementById('mobileMenuClose');

function openMenu(){
  mobileMenu.classList.add('active');
  mobileMenuToggle.classList.add('active');
  document.body.style.overflow = 'hidden';
  mobileMenuToggle.setAttribute('aria-expanded','true');
}
function closeMenu(){
  mobileMenu.classList.remove('active');
  mobileMenuToggle.classList.remove('active');
  document.body.style.overflow = 'auto';
  mobileMenuToggle.setAttribute('aria-expanded','false');
}

if (mobileMenuToggle && mobileMenu) {
  mobileMenuToggle.addEventListener('click', () => {
    mobileMenu.classList.contains('active') ? closeMenu() : openMenu();
  });

  // Tutup saat klik link
  mobileNavLinks.forEach(link => link.addEventListener('click', closeMenu));

  // Tombol X
  if (closeBtn) closeBtn.addEventListener('click', closeMenu);

  // Tutup dengan klik area gelap
  mobileMenu.addEventListener('click', (e) => {
    if (e.target === mobileMenu) closeMenu();
  });

  // Tutup dengan ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileMenu.classList.contains('active')) closeMenu();
  });
}

// ===== (Opsional) TRANSAKSI =====
// Hanya akan aktif bila section/form transaksi benar-benar ada di halaman.
document.addEventListener('DOMContentLoaded', () => {
  const form  = document.getElementById('transaksiForm');
  const tbody = document.querySelector('#riwayat tbody');
  if (!form || !tbody) return;

  const loadTransaksi = () => {
    fetch('transaksi.php?action=read')
      .then(res => res.json())
      .then(rows => {
        tbody.innerHTML = '';
        rows.forEach(row => {
          tbody.insertAdjacentHTML('beforeend', `
            <tr>
              <td>${row.nama}</td>
              <td>${row.jenis_sampah}</td>
              <td>${row.jumlah}</td>
              <td><button class="btn-hapus" data-id="${row.id_transaksi}">Hapus</button></td>
            </tr>
          `);
        });
      })
      .catch(() => { /* diamkan jika backend belum siap */ });
  };

  loadTransaksi();

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    fetch('transaksi.php?action=create', { method: 'POST', body: fd })
      .then(res => res.json())
      .then(res => {
        if (res.success) { form.reset(); loadTransaksi(); }
        else { alert(res.error || 'Gagal menyimpan.'); }
      })
      .catch(() => alert('Gagal terhubung ke server'));
  });

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-hapus');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    const fd = new FormData(); fd.append('id', id);
    fetch('transaksi.php?action=delete', { method: 'POST', body: fd })
      .then(() => loadTransaksi());
  });
});
