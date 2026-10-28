// Carousel scroll-snap: titik navigasi, tombol sebelumnya/berikutnya, autoplay.
// Autoplay berhenti saat disentuh/hover/fokus, saat di luar layar, saat tab disembunyikan,
// dan tidak berjalan sama sekali bila pengguna memilih prefers-reduced-motion.
//
// Performa: carousel baru dipasang saat mendekati layar, dan semua pengukuran (baca layout)
// dikumpulkan sebelum perubahan DOM (tulis) agar tidak memicu layout paksa berulang.

const kurangiGerak = matchMedia('(prefers-reduced-motion: reduce)').matches;
const terdaftar = new Map(); // track → carousel

// Satu ResizeObserver untuk semua carousel: ukur semua dulu, baru tulis
const pengamatUkuran = new ResizeObserver((entri) => {
  const daftar = entri.map((e) => terdaftar.get(e.target)).filter(Boolean);
  const hasil = daftar.map((c) => c.ukur());
  daftar.forEach((c, i) => c.tulis(hasil[i]));
});

function buat(root) {
  const track = root.querySelector('[data-carousel-track]');
  const kontrol = root.querySelector('[data-carousel-controls]');
  if (!track || !kontrol) return;

  const slides = [...track.children];
  const wadahTitik = root.querySelector('[data-carousel-dots]');
  const tombolPrev = root.querySelector('[data-carousel-prev]');
  const tombolNext = root.querySelector('[data-carousel-next]');
  const tombolJeda = root.querySelector('[data-carousel-pause]');

  let posisi = slides.map(() => 0);
  let maks = 0;
  let bisaGeser = false;

  const titik = slides.map((_, i) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'group grid size-6 place-items-center';
    b.setAttribute('aria-label', `Ke slide ${i + 1}`);
    b.innerHTML = '<span class="block h-2 w-2 rounded-full bg-gray-300 transition-all group-aria-[current=true]:w-5 group-aria-[current=true]:bg-primary"></span>';
    b.addEventListener('click', () => ke(i));
    return b;
  });
  wadahTitik?.append(...titik);

  const aktif = (x) => {
    let terbaik = 0;
    posisi.forEach((p, i) => { if (Math.abs(p - x) < Math.abs(posisi[terbaik] - x)) terbaik = i; });
    return terbaik;
  };

  const carousel = {
    // Baca layout saja
    ukur: () => ({
      x: track.scrollLeft,
      maks: track.scrollWidth - track.clientWidth,
      posisi: slides.map((s) => s.offsetLeft - slides[0].offsetLeft),
    }),
    // Tulis DOM saja
    tulis(m) {
      posisi = m.posisi;
      maks = m.maks;
      bisaGeser = m.maks > 1;
      // Ruang kontrol sudah dipesan (invisible) sejak server → menampilkannya tidak menggeser layout
      kontrol.classList.toggle('invisible', !bisaGeser);
      const sekarang = aktif(m.x);
      titik.forEach((b, i) => b.setAttribute('aria-current', String(i === sekarang)));
      if (tombolPrev) tombolPrev.disabled = m.x <= 1;
      if (tombolNext) tombolNext.disabled = m.x >= maks - 1;
    },
  };
  terdaftar.set(track, carousel);
  pengamatUkuran.observe(track); // callback pertama langsung mengukur & menulis

  function ke(i) {
    track.scrollTo({ left: posisi[i], behavior: kurangiGerak ? 'auto' : 'smooth' });
  }

  let antre = false;
  track.addEventListener('scroll', () => {
    if (antre) return;
    antre = true;
    requestAnimationFrame(() => {
      antre = false;
      // Posisi slide tidak berubah saat menggulir — cukup baca scrollLeft
      carousel.tulis({ x: track.scrollLeft, maks, posisi });
    });
  }, { passive: true });

  tombolPrev?.addEventListener('click', () => ke(Math.max(0, aktif(track.scrollLeft) - 1)));
  tombolNext?.addEventListener('click', () => ke(Math.min(slides.length - 1, aktif(track.scrollLeft) + 1)));

  // --- Autoplay ---------------------------------------------------------------
  const jeda = Number(root.dataset.autoplay || 0);
  if (!jeda || kurangiGerak || slides.length < 2) {
    tombolJeda?.remove();
    return;
  }

  let dijeda = false;      // oleh tombol / sentuhan
  let disentuh = false;    // hover / fokus
  let terlihat = true;

  setInterval(() => {
    if (dijeda || disentuh || !terlihat || document.hidden || !bisaGeser) return;
    const x = track.scrollLeft;
    const berikut = aktif(x) + 1;
    ke(berikut >= slides.length || x >= maks - 1 ? 0 : berikut);
  }, jeda);

  root.addEventListener('pointerenter', () => { disentuh = true; });
  root.addEventListener('pointerleave', () => { disentuh = false; });
  root.addEventListener('focusin', () => { disentuh = true; });
  root.addEventListener('focusout', (e) => { if (!root.contains(e.relatedTarget)) disentuh = false; });
  track.addEventListener('touchstart', () => { dijeda = true; perbaruiTombolJeda(); }, { passive: true });

  new IntersectionObserver(([e]) => { terlihat = e.isIntersecting; }).observe(root);

  function perbaruiTombolJeda() {
    if (!tombolJeda) return;
    tombolJeda.setAttribute('aria-pressed', String(dijeda));
    tombolJeda.setAttribute('aria-label', dijeda ? 'Lanjutkan putar otomatis' : 'Jeda putar otomatis');
    tombolJeda.querySelector('[data-ikon-jeda]')?.toggleAttribute('hidden', dijeda);
    tombolJeda.querySelector('[data-ikon-putar]')?.toggleAttribute('hidden', !dijeda);
  }

  tombolJeda?.addEventListener('click', () => { dijeda = !dijeda; perbaruiTombolJeda(); });
}

// Pasang saat mendekati layar. Carousel di panel tab tersembunyi (display:none) otomatis
// terpasang ketika panelnya ditampilkan.
const pengamatLayar = new IntersectionObserver((entri) => {
  entri.forEach((e) => {
    if (!e.isIntersecting) return;
    pengamatLayar.unobserve(e.target);
    buat(e.target);
  });
}, { rootMargin: '300px 0px' });

const amati = (wadah) => wadah.querySelectorAll('[data-carousel]').forEach((el) => pengamatLayar.observe(el));
amati(document);

// Carousel yang baru dimasukkan ke DOM (mis. isi <template> panel tab — lihat tabs.js)
document.addEventListener('carousel:pasang', (e) => amati(e.detail));
