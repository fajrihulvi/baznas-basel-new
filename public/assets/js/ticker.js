// Ticker "Info BAZNAS": satu judul tampil bergantian (geser naik singkat) tiap 5 detik.
// Berhenti saat hover/fokus atau lewat tombol jeda; tidak berputar bila prefers-reduced-motion.

const kurangiGerak = matchMedia('(prefers-reduced-motion: reduce)').matches;

document.querySelectorAll('[data-ticker]').forEach((root) => {
  const item = [...root.querySelectorAll('[data-ticker-item]')];
  const tombolJeda = root.querySelector('[data-ticker-pause]');
  if (item.length < 2 || kurangiGerak) {
    tombolJeda?.remove();
    return;
  }
  if (tombolJeda) tombolJeda.hidden = false;

  let sekarang = 0;
  let dijeda = false;
  let disentuh = false;

  const tampilkan = (n) => {
    item.forEach((el, i) => {
      const aktif = i === n;
      el.classList.toggle('invisible', !aktif);
      el.classList.toggle('translate-y-3', !aktif);
      if (aktif) el.removeAttribute('aria-hidden');
      else el.setAttribute('aria-hidden', 'true'); // aria-hidden="" (toggleAttribute) tidak dianggap tersembunyi
      el.querySelector('a')?.setAttribute('tabindex', aktif ? '0' : '-1');
    });
    sekarang = n;
  };

  setInterval(() => {
    if (!dijeda && !disentuh && !document.hidden) tampilkan((sekarang + 1) % item.length);
  }, 5000);

  root.addEventListener('pointerenter', () => { disentuh = true; });
  root.addEventListener('pointerleave', () => { disentuh = false; });
  root.addEventListener('focusin', () => { disentuh = true; });
  root.addEventListener('focusout', (e) => { if (!root.contains(e.relatedTarget)) disentuh = false; });

  tombolJeda?.addEventListener('click', () => {
    dijeda = !dijeda;
    tombolJeda.setAttribute('aria-pressed', String(dijeda));
    tombolJeda.setAttribute('aria-label', dijeda ? 'Lanjutkan info bergulir' : 'Jeda info bergulir');
    tombolJeda.querySelector('[data-ikon-jeda]')?.toggleAttribute('hidden', dijeda);
    tombolJeda.querySelector('[data-ikon-putar]')?.toggleAttribute('hidden', !dijeda);
  });
});
