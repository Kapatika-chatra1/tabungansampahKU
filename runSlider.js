// Memilih semua elemen slide dan memulai dari slide pertama (indeks 0)
const slides = document.querySelectorAll('.slide');
let currentIndex = 0;

// --- FUNGSI BARU UNTUK MENAMPILKAN SLIDE ---
// Fungsi ini bertugas untuk menyembunyikan semua slide,
// lalu hanya menampilkan slide yang sedang aktif.
function showSlide() {
  // 1. Sembunyikan semua slide terlebih dahulu
  slides.forEach((slide) => {
    slide.classList.remove('active');
  });

  // 2. Tampilkan slide yang saat ini aktif (berdasarkan currentIndex)
  slides[currentIndex].classList.add('active');
}

// --- FUNGSI UNTUK PINDAH KE SLIDE BERIKUTNYA ---
function nextSlide() {
  // Pindahkan indeks ke slide berikutnya
  currentIndex++;

  // Jika sudah mencapai slide terakhir, kembali ke awal (indeks 0)
  if (currentIndex >= slides.length) {
    currentIndex = 0;
  }

  // Panggil fungsi untuk menampilkan slide yang baru
  showSlide();
}

// --- INI BAGIAN KUNCINYA ---

// 1. Tampilkan gambar pertama SEGERA saat halaman dimuat
// Anda tidak perlu lagi menambahkan 'active' di HTML
showSlide();

// 2. Atur interval untuk menggeser ke slide berikutnya setiap 3 detik
// setInterval lebih cocok untuk tugas yang berulang terus-menerus
setInterval(nextSlide, 3000); // 3000 ms = 3 detik
