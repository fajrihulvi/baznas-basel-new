// Mengubah chip-tabs (link) di dalam [data-tabs] menjadi tab ARIA tanpa memuat ulang halaman.
// Panel: [data-tab-panel="kunci"] — panel lain diberi atribut hidden.

const AKTIF = ['border-primary', 'bg-primary', 'text-white'];
const PASIF = ['border-gray-300', 'bg-white', 'text-ink', 'hover:border-primary', 'hover:text-primary'];

document.querySelectorAll('[data-tabs]').forEach((root, nomor) => {
  const tabs = [...root.querySelectorAll('[data-tab]')];
  const panel = (kunci) => root.querySelector(`[data-tab-panel="${kunci}"]`);
  if (tabs.length < 2) return;

  const daftar = tabs[0].closest('ul');
  daftar.setAttribute('role', 'tablist');
  daftar.setAttribute('aria-label', daftar.closest('nav')?.getAttribute('aria-label') ?? 'Tab');
  daftar.querySelectorAll(':scope > li').forEach((li) => li.setAttribute('role', 'presentation'));

  tabs.forEach((tab, i) => {
    const p = panel(tab.dataset.tab);
    tab.id ||= `tab-${nomor}-${i}`;
    tab.setAttribute('role', 'tab');
    if (p) {
      p.id ||= `panel-${nomor}-${i}`;
      p.setAttribute('role', 'tabpanel');
      p.setAttribute('aria-labelledby', tab.id);
      tab.setAttribute('aria-controls', p.id);
    }
  });

  function pilih(tab, fokus = false) {
    tabs.forEach((t) => {
      const aktif = t === tab;
      t.setAttribute('aria-selected', String(aktif));
      t.removeAttribute('aria-current');
      t.tabIndex = aktif ? 0 : -1;
      t.classList.remove(...(aktif ? PASIF : AKTIF));
      t.classList.add(...(aktif ? AKTIF : PASIF));
      const p = panel(t.dataset.tab);
      if (!p) return;
      // Isi panel yang disimpan di <template> baru dimasukkan ke DOM saat pertama kali dipilih
      const isi = aktif ? p.querySelector(':scope > template[data-tab-isi]') : null;
      if (isi) {
        isi.replaceWith(isi.content.cloneNode(true));
        document.dispatchEvent(new CustomEvent('carousel:pasang', { detail: p }));
      }
      p.hidden = !aktif;
    });
    if (fokus) tab.focus();
  }

  tabs.forEach((tab, i) => {
    tab.addEventListener('click', (e) => { e.preventDefault(); pilih(tab); });
    tab.addEventListener('keydown', (e) => {
      const tujuan = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[e.key];
      if (tujuan === undefined) return;
      e.preventDefault();
      pilih(tabs[(tujuan + tabs.length) % tabs.length], true);
    });
  });

  pilih(tabs.find((t) => t.getAttribute('aria-current') === 'page') ?? tabs[0]);
});
