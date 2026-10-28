// JS umum situs publik (ES module, otomatis defer). Tanpa library.
// Semua fitur bersifat peningkatan: tanpa JS, link & dropdown <details> tetap berfungsi.

// --- Bottom sheet "Menu" (<dialog>) -------------------------------------------
const sheet = document.getElementById('menu-sheet');

if (sheet instanceof HTMLDialogElement) {
  const pembuka = document.querySelectorAll('[data-sheet-open]');
  let pemicu = null;

  pembuka.forEach((btn) =>
    btn.addEventListener('click', () => {
      pemicu = btn;
      sheet.showModal();
      // Kunci gulir body (pengganti html:has(dialog[open]) yang mahal untuk perhitungan style)
      document.documentElement.classList.add('overflow-hidden');
      btn.setAttribute('aria-expanded', 'true');
    }),
  );

  // Pulihkan halaman. Dipanggil langsung saat menutup (event `close` bersifat asinkron),
  // serta lewat `cancel` (Esc) dan `close` sebagai cadangan — aman dipanggil berulang.
  let terkunci = false;
  const pulihkan = () => {
    if (!terkunci) return;
    terkunci = false;
    document.documentElement.classList.remove('overflow-hidden');
    sheet.style.transform = '';
    pembuka.forEach((btn) => btn.setAttribute('aria-expanded', 'false'));
    pemicu?.focus();
  };
  const tutup = () => {
    if (sheet.open) sheet.close();
    pulihkan();
  };

  pembuka.forEach((btn) => btn.addEventListener('click', () => { terkunci = true; }));
  sheet.addEventListener('cancel', pulihkan);
  sheet.addEventListener('close', pulihkan);

  // Tombol tutup, link anchor (#kontak), dan klik backdrop (target = elemen dialog itu sendiri)
  sheet.addEventListener('click', (e) => {
    const el = e.target instanceof Element ? e.target : null;
    if (e.target === sheet || el?.closest('[data-sheet-close]') || el?.closest('a[href^="#"]')) {
      tutup();
    }
  });

  // Geser ke bawah dari area pegangan untuk menutup
  const pegangan = sheet.querySelector('[data-sheet-handle]');
  let awalY = null;
  pegangan?.addEventListener('touchstart', (e) => { awalY = e.touches[0].clientY; }, { passive: true });
  pegangan?.addEventListener('touchmove', (e) => {
    if (awalY === null) return;
    const dy = Math.max(0, e.touches[0].clientY - awalY);
    sheet.style.transform = `translateY(${dy}px)`;
  }, { passive: true });
  pegangan?.addEventListener('touchend', (e) => {
    if (awalY === null) return;
    const dy = e.changedTouches[0].clientY - awalY;
    awalY = null;
    if (dy > 80) {
      tutup();
    } else {
      sheet.style.transform = '';
    }
  });
}

// --- Dropdown menu desktop (<details data-dropdown>) ----------------------------
const dropdowns = [...document.querySelectorAll('details[data-dropdown]')];

dropdowns.forEach((d) =>
  d.addEventListener('toggle', () => {
    if (d.open) dropdowns.forEach((lain) => { if (lain !== d) lain.open = false; });
  }),
);

document.addEventListener('click', (e) => {
  dropdowns.forEach((d) => { if (d.open && !d.contains(e.target)) d.open = false; });
});

document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  dropdowns.forEach((d) => {
    if (d.open) {
      d.open = false;
      d.querySelector('summary')?.focus();
    }
  });
});

// --- Teks terpotong yang bisa dibuka ("Selengkapnya") ---------------------------
document.querySelectorAll('[data-expand]').forEach((btn) => {
  const target = document.getElementById(btn.getAttribute('aria-controls'));
  if (!target) return;

  // Tampilkan tombol hanya bila teks memang terpotong
  if (target.scrollHeight <= target.clientHeight + 1) return;
  btn.hidden = false;

  btn.addEventListener('click', () => {
    const buka = btn.getAttribute('aria-expanded') !== 'true';
    target.classList.toggle('line-clamp-3', !buka);
    btn.setAttribute('aria-expanded', String(buka));
    btn.textContent = buka ? 'Tutup' : 'Selengkapnya';
  });
});

// --- Tombol salin ([data-salin]: nomor rekening, tautan) ------------------------
const pengumuman = document.getElementById('pengumuman');

document.querySelectorAll('button[data-salin]').forEach((btn) => {
  if (!navigator.clipboard) return; // tanpa Clipboard API tombol tetap tersembunyi
  btn.hidden = false;
  const asli = btn.innerHTML;
  btn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(btn.dataset.salin);
      btn.textContent = 'Tersalin ✓';
      if (pengumuman) pengumuman.textContent = btn.dataset.salinPesan || 'Disalin';
      setTimeout(() => { btn.innerHTML = asli; }, 2000);
    } catch {
      if (pengumuman) pengumuman.textContent = 'Gagal menyalin, silakan salin manual.';
    }
  });
});
