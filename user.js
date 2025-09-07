document.addEventListener('DOMContentLoaded', () => {
    // ===== STAT dari tabel =====
    const tbody = document.querySelector('#tabelRiwayat tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr')).filter(r => r.children.length >= 4);
    const statTransEl = document.getElementById('stat-transaksi');
    const statJenisEl = document.getElementById('stat-jenis');

    const jenisCount = {};
    let visibleCount = 0;
    rows.forEach(tr => {
      const jenis = tr.children[2]?.textContent.trim();
      if (!jenis) return;
      visibleCount++;
      jenisCount[jenis] = (jenisCount[jenis] || 0) + 1;
    });
    statTransEl.textContent = visibleCount || 0;
    statJenisEl.textContent = visibleCount ? Object.entries(jenisCount).sort((a,b)=>b[1]-a[1])[0][0] : '—';

    // ===== FILTER & SEARCH =====
    const filterJenis = document.getElementById('filterJenis');
    const searchInput = document.getElementById('searchInput');
    const btnReset    = document.getElementById('btnReset');

    const jenisSet = new Set(rows.map(r => r.children[2]?.textContent.trim()).filter(Boolean));
    [...jenisSet].sort().forEach(j => {
      const o = document.createElement('option'); o.value=j; o.textContent=j; filterJenis.appendChild(o);
    });

    function applyFilter(){
      const q  = searchInput.value.trim().toLowerCase();
      const jf = filterJenis.value;
      let showCount = 0;
      rows.forEach(tr => {
        const nama  = tr.children[1]?.textContent.toLowerCase() || '';
        const jenis = tr.children[2]?.textContent || '';
        const matchQ = !q || nama.includes(q) || jenis.toLowerCase().includes(q);
        const matchJ = !jf || jenis === jf;
        const show = matchQ && matchJ;
        tr.style.display = show ? '' : 'none';
        if (show) showCount++;
      });
      statTransEl.textContent = showCount;
    }
    searchInput.addEventListener('input', applyFilter);
    filterJenis.addEventListener('change', applyFilter);
    btnReset.addEventListener('click', () => { searchInput.value=''; filterJenis.value=''; applyFilter(); });

    // ===== CSV =====
    document.getElementById('btnDownload').addEventListener('click', () => {
      const vis = rows.filter(r => r.style.display !== 'none');
      if (!vis.length) { alert('Tidak ada data untuk diunduh.'); return; }
      const header = ['ID Transaksi','Nama','Jenis Sampah','Jumlah Setoran'];
      const csv = [header.join(',')].concat(
        vis.map(tr => Array.from(tr.children).slice(0,4).map(td => {
          const t = td.textContent.replace(/\s+/g,' ').trim().replace(/"/g,'""');
          return `"${t}"`;
        }).join(','))
      ).join('\r\n');
      const blob = new Blob([csv],{type:'text/csv;charset=utf-8;'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob); a.download = 'riwayat-transaksi.csv'; a.click();
      URL.revokeObjectURL(a.href);
    });

    // ===== MAP =====
    const map = L.map('map').setView([-7.9539772,110.1813977], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution:'© OpenStreetMap contributors'
    }).addTo(map);
    L.marker([-7.9490876,110.1975741]).addTo(map).bindPopup('Titik Bank Sampah Sorogaten');

    // ===== MODAL PASSWORD =====
    const modalPassword = document.getElementById('modalPassword');
    document.getElementById('btnOpenPassword').addEventListener('click', ()=> {
      modalPassword.style.display = 'flex';
    });
    document.getElementById('btnClosePassword').addEventListener('click', ()=> {
      modalPassword.style.display = 'none';
    });

    document.getElementById('formPassword').addEventListener('submit', async e => {
      e.preventDefault();
      const form = e.target;
      const data = new FormData(form);
      const msgEl = document.getElementById('msgPassword');
      msgEl.style.color = "black";
      msgEl.textContent = "⏳ Memproses...";

      try {
        const res = await fetch('ganti_password.php', { method:'POST', body:data });
        const result = await res.json();
        msgEl.style.color = result.success ? "green" : "red";
        msgEl.textContent = result.message;
        if(result.success){
          form.reset();
          setTimeout(()=> modalPassword.style.display='none', 1500);
        }
      } catch(err){
        msgEl.style.color = "red";
        msgEl.textContent = "❌ Terjadi kesalahan koneksi.";
      }
    });
  });