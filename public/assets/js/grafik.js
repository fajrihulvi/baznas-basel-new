// Grafik dasbor admin (SVG dari server): tooltip saat hover/fokus, garis bantu pada grafik garis,
// panah kiri/kanan untuk berpindah periode lewat keyboard. Nilai yang sama selalu ada di tabel — tooltip hanya pelengkap.

const tooltip = document.createElement('div');
tooltip.setAttribute('role', 'status');
tooltip.className = 'pointer-events-none absolute z-10 hidden min-w-44 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-lg';

const isiTooltip = (rect) => {
  tooltip.replaceChildren();
  const judul = document.createElement('p');
  judul.className = 'mb-1 text-xs font-semibold text-muted';
  judul.textContent = rect.dataset.judul ?? '';
  tooltip.append(judul);
  for (const [label, warna, nilai] of JSON.parse(rect.dataset.baris || '[]')) {
    const baris = document.createElement('p');
    baris.className = 'flex items-center gap-2 py-0.5';
    const kunci = document.createElement('span');
    kunci.className = 'h-0.5 w-3 shrink-0 rounded';
    kunci.style.background = warna;
    const angka = document.createElement('strong');
    angka.className = 'font-semibold text-ink';
    angka.textContent = nilai;
    const nama = document.createElement('span');
    nama.className = 'text-muted';
    nama.textContent = label;
    baris.append(kunci, angka, nama);
    tooltip.append(baris);
  }
  if (rect.dataset.total || rect.dataset.catatan) {
    const kaki = document.createElement('p');
    kaki.className = 'mt-1 border-t border-gray-200 pt-1 font-bold text-ink';
    kaki.textContent = rect.dataset.total ? `Total ${rect.dataset.total}` : rect.dataset.catatan;
    tooltip.append(kaki);
  }
};

// data-grafik: <svg> (kolom per periode) atau daftar HTML (baris per item, mis. per Perangkat Daerah)
document.querySelectorAll('[data-grafik]').forEach((svg) => {
  const wadah = svg.closest('[data-grafik-wadah]');
  const sentuh = [...svg.querySelectorAll('.grafik-sentuh')];
  if (sentuh.length === 0) return;
  const silang = svg.querySelector('.grafik-silang');
  const html = !(svg instanceof SVGElement);
  // Titik awal saat difokus: data-grafik-awal (mis. bulan yang dipilih), selain itu periode terbaru
  const awal = Math.min(sentuh.length - 1, Number(svg.dataset.grafikAwal ?? sentuh.length - 1));
  let aktif = -1;

  // Layar sempit: grafik bisa digeser; mulai dari titik awal (bawaan ujung kanan = periode terbaru)
  if (wadah.scrollWidth > wadah.clientWidth) {
    if (svg.dataset.grafikAwal === undefined) {
      wadah.scrollLeft = wadah.scrollWidth;
    } else {
      const kotak = sentuh[awal].getBoundingClientRect();
      const dasar = wadah.getBoundingClientRect();
      wadah.scrollLeft = kotak.left - dasar.left - (wadah.clientWidth - kotak.width) / 2;
    }
  }

  const sorot = (r, ya) => {
    if (html) r.classList.toggle('bg-surface', ya);
    else r.setAttribute('fill-opacity', ya ? '0.06' : '0');
  };

  const tampil = (i) => {
    sentuh.forEach((r, j) => sorot(r, j === i));
    aktif = i;
    const rect = sentuh[i];
    if (silang && rect.dataset.silang) {
      silang.setAttribute('x1', rect.dataset.silang);
      silang.setAttribute('x2', rect.dataset.silang);
      silang.setAttribute('visibility', 'visible');
    }
    isiTooltip(rect);
    wadah.append(tooltip);
    tooltip.classList.remove('hidden');
    // Posisikan di samping kolom aktif, tetap di dalam wadah
    const kotak = rect.getBoundingClientRect();
    const dasar = wadah.getBoundingClientRect();
    const lebar = tooltip.offsetWidth;
    if (html) {
      // Baris: tooltip tepat di bawah baris aktif, rata kanan baris (tidak menutupi labelnya)
      const kiri = Math.min(kotak.right - dasar.left - lebar, wadah.clientWidth - lebar);
      tooltip.style.left = `${Math.max(0, kiri)}px`;
      tooltip.style.top = `${kotak.bottom - dasar.top + 4}px`;
      return;
    }
    let kiri = kotak.right - dasar.left + wadah.scrollLeft + 8;
    if (kiri + lebar > wadah.scrollLeft + wadah.clientWidth) kiri = kotak.left - dasar.left + wadah.scrollLeft - lebar - 8;
    tooltip.style.left = `${Math.max(wadah.scrollLeft, kiri)}px`;
    tooltip.style.top = '8px';
  };
  const sembunyi = () => {
    sentuh.forEach((r) => sorot(r, false));
    silang?.setAttribute('visibility', 'hidden');
    tooltip.classList.add('hidden');
    aktif = -1;
  };

  sentuh.forEach((r, i) => r.addEventListener('pointerenter', () => tampil(i)));
  svg.addEventListener('pointerleave', sembunyi);
  svg.addEventListener('focus', () => tampil(aktif < 0 ? awal : aktif));
  svg.addEventListener('blur', sembunyi);
  svg.addEventListener('keydown', (e) => {
    const maju = html ? 'ArrowDown' : 'ArrowRight';
    const mundur = html ? 'ArrowUp' : 'ArrowLeft';
    if (e.key === maju || e.key === mundur) {
      e.preventDefault();
      tampil(Math.min(sentuh.length - 1, Math.max(0, aktif + (e.key === maju ? 1 : -1))));
      if (html) sentuh[aktif].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Escape') {
      sembunyi();
    }
  });
});

// Filter: memilih periode langsung diterapkan (rentang tanggal tetap lewat tombol Terapkan)
document.querySelectorAll('form[data-filter-statistik]').forEach((form) => {
  form.elements.periode?.addEventListener('change', () => {
    form.elements.dari.value = '';
    form.elements.sampai.value = '';
    form.requestSubmit();
  });
});

// Filter pengumpulan (bulan/tahun): pilihan langsung diterapkan; tombol Terapkan tetap untuk tanpa JS
document.querySelectorAll('form[data-filter-otomatis]').forEach((form) => {
  form.querySelectorAll('select').forEach((s) => s.addEventListener('change', () => form.requestSubmit()));
});
