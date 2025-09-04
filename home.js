// Smooth scroll
function smoothScrollTo(hash) {
  const el = document.querySelector(hash);
  if (!el) return;
  const top = el.getBoundingClientRect().top + window.scrollY - 64;
  window.scrollTo({ top, behavior: 'smooth' });
}

// Header + toTop
const header = document.getElementById('siteHeader');
const toTop  = document.getElementById('toTop');
window.addEventListener('scroll', () => {
  header.classList.toggle('scrolled', window.scrollY > 12);
  toTop.classList.toggle('show', window.scrollY > 480);
});
toTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

// Mobile menu (FIX: jangan andalkan display:none)
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const mobileMenu       = document.getElementById('mobileMenu');
const mobileClose      = document.getElementById('mobileMenuClose');

function openMenu(){
  mobileMenu.classList.add('active');
  mobileMenuToggle.classList.add('active');
  document.body.style.overflow = 'hidden';
  mobileMenuToggle.setAttribute('aria-expanded','true');
}
function closeMenu(){
  mobileMenu.classList.remove('active');
  mobileMenuToggle.classList.remove('active');
  document.body.style.overflow = '';
  mobileMenuToggle.setAttribute('aria-expanded','false');
}
mobileMenuToggle?.addEventListener('click', () => mobileMenu.classList.contains('active') ? closeMenu() : openMenu());
mobileClose?.addEventListener('click', closeMenu);
mobileMenu?.addEventListener('click', (e) => { if (e.target === mobileMenu) closeMenu(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && mobileMenu?.classList.contains('active')) closeMenu(); });

// Close menu & smooth scroll on anchor (internal)
document.querySelectorAll('.mobile-links a, .nav-desktop a[href^="#"]').forEach(a => {
  a.addEventListener('click', (e) => {
    const href = a.getAttribute('href');
    if (href && href.startsWith('#')) {
      e.preventDefault();
      closeMenu();
      smoothScrollTo(href);
    }
  });
});

// Slider (auto-play + dots + kenburns)
(function initSlider(){
  const slider = document.getElementById('slider');
  if (!slider) return;
  const slides = [...slider.querySelectorAll('.slide')];
  if (!slides.length) return;

  if (!slider.querySelector('.slide.active')) slides[0].classList.add('active');

  const dotsWrap = document.getElementById('sliderDots');
  slides.forEach((_, idx) => {
    const b = document.createElement('button');
    b.setAttribute('aria-label', 'Slide ' + (idx+1));
    if (idx === 0) b.classList.add('active');
    b.addEventListener('click', () => go(idx));
    dotsWrap.appendChild(b);
  });

  let i = slides.findIndex(s => s.classList.contains('active'));
  let timer = setInterval(next, 6000);

  function next(){ go((i + 1) % slides.length); }
  function go(n){
    slides[i].classList.remove('active');
    dotsWrap.children[i].classList.remove('active');
    i = n;
    slides[i].classList.add('active');
    dotsWrap.children[i].classList.add('active');
    clearInterval(timer);
    timer = setInterval(next, 6000);
  }
})();

// Reveal on scroll (stagger via CSS var --d sudah dipakai)
const io = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('in'); });
}, { threshold:.12 });
document.querySelectorAll('.reveal').forEach(el => io.observe(el));

// Counter animation
function animateCounter(el){
  const target = +el.dataset.count || 0;
  const dur = 1500, start = performance.now();
  function tick(now){
    const p = Math.min(1, (now - start)/dur);
    el.textContent = Math.floor(target * (0.2 + 0.8*p)).toLocaleString('id-ID');
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}
document.querySelectorAll('.stat .num').forEach(el => {
  const obs = new IntersectionObserver((e,o)=>{ if(e[0].isIntersecting){ animateCounter(el); o.disconnect(); } }, {threshold:.6});
  obs.observe(el);
});

// Leaflet Map
(function initMap(){
  const mapEl = document.getElementById('map');
  if (!mapEl) return;

  const map = L.map('map', { scrollWheelZoom:false }).setView([-7.9539772, 110.1813977], 11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  const points = [
    { name:'Bank Sampah Sorogaten',  coord:[-7.9490876,110.1975741] },
    { name:'Pengepul Karangsewu',    coord:[-7.965,110.170] },
    { name:'TPS Terpadu (contoh)',   coord:[-7.941,110.190] },
  ];
  points.forEach(p => L.marker(p.coord).addTo(map).bindPopup(p.name));
})();

// Smooth scroll untuk anchor biasa
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click', (e)=>{
    const href = a.getAttribute('href');
    if (href === "#") return;
    const el = document.querySelector(href);
    if (!el) return;
    e.preventDefault();
    smoothScrollTo(href);
  });
});
