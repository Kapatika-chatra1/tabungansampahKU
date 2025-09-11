/* ==================================
   admin.ui.js — UI / UX behaviors
==================================== */

// Fade-in reveal
window.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.fade-in').forEach(el => el.classList.add('in'));
});

// Sticky header shadow
(function(){
  const header = document.getElementById('topHeader');
  if (!header) return;
  const onScroll = () => { (window.scrollY > 6) ? header.classList.add('scrolled') : header.classList.remove('scrolled'); };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

// Mobile nav toggle (burger hanya muncul di mobile via CSS)
(function(){
  const toggle = document.getElementById('navToggle');
  const nav    = document.getElementById('mainNav');
  if (!toggle || !nav) return;

  function closeOnOutside(e){
    if (!nav.contains(e.target) && e.target !== toggle) {
      nav.classList.remove('open');
      toggle.setAttribute('aria-expanded','false');
      document.removeEventListener('click', closeOnOutside);
    }
  }
  toggle.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(isOpen));
    if (isOpen) document.addEventListener('click', closeOnOutside);
  });
})();

// Button ripple
(function(){
  function addRipple(e){
    const btn = e.currentTarget;
    const rect = btn.getBoundingClientRect();
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    const size = Math.max(rect.width, rect.height);
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
    ripple.style.top  = (e.clientY - rect.top  - size/2) + 'px';
    btn.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
  }
  const attach = () => {
    document.querySelectorAll('.btn, .icon-btn').forEach(el => {
      el.removeEventListener('click', addRipple);
      el.addEventListener('click', addRipple);
    });
  };
  window.addEventListener('DOMContentLoaded', attach);
  const mo = new MutationObserver(attach);
  mo.observe(document.body, { childList:true, subtree:true });
})();

// User table filter & counter (client-side)
(function(){
  const searchInput = document.getElementById('userSearch');
  const clearBtn    = document.getElementById('clearSearch');
  const onlyUsersCb = document.getElementById('onlyUsers');
  const userTbody   = document.querySelector('#userTable tbody');
  const userCount   = document.getElementById('userCount');

  function getRows(){ return Array.from(userTbody?.querySelectorAll('tr') || []); }
  function matchRow(row, q, onlyUsers){
    const cells = Array.from(row.children).map(td => td.textContent.toLowerCase());
    const role  = (cells[4] || '').trim();
    const text  = cells.join(' ');
    if (onlyUsers && role !== 'user') return false;
    if (!q) return true;
    return text.includes(q);
  }
  function applyUserFilters(){
    const q = (searchInput?.value || '').trim().toLowerCase();
    const onlyUsers = !!onlyUsersCb?.checked;
    let visible = 0;
    getRows().forEach(row => {
      const ok = matchRow(row, q, onlyUsers);
      row.style.display = ok ? '' : 'none';
      if (ok) visible++;
    });
    if (userCount) userCount.textContent = `${visible} user${onlyUsers ? '' : ' (termasuk non-user bila ada)'}`;
  }
  window.applyUserFilters = applyUserFilters;

  if (searchInput)  searchInput.addEventListener('input', applyUserFilters);
  if (onlyUsersCb)  onlyUsersCb.addEventListener('change', applyUserFilters);
  if (clearBtn && searchInput) clearBtn.addEventListener('click', () => { searchInput.value=''; applyUserFilters(); });

  window.addEventListener('DOMContentLoaded', applyUserFilters);
})();
