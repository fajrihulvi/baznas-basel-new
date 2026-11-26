// Halaman donasi: format rupiah saat mengetik, tombol nominal cepat mengisi kolom, rincian total ikut berubah,
// dan deskripsi panjang diringkas dengan tombol "Baca selengkapnya". Tanpa JS semuanya tetap berfungsi.
const fmt = new Intl.NumberFormat('id-ID');
const angka = (v) => Number(String(v).replace(/\D/g, '').slice(0, 13)) || 0;

document.querySelectorAll('input[data-rupiah]').forEach((input) => {
  const form = input.form;
  const perbarui = () => {
    const n = angka(input.value);
    input.value = n ? fmt.format(n) : '';
    form?.querySelectorAll('[data-rincian-nominal], [data-rincian-total]').forEach((el) => { el.textContent = `Rp${fmt.format(n)}`; });
  };
  input.addEventListener('input', perbarui);
  // Tombol nominal cepat: isi kolom (pengunjung bisa mengubahnya lagi) alih-alih langsung lanjut
  form?.querySelectorAll('[data-nominal-cepat]').forEach((tombol) => {
    tombol.type = 'button';
    tombol.addEventListener('click', () => {
      input.value = fmt.format(Number(tombol.dataset.nominalCepat));
      input.focus();
    });
  });
});

document.querySelectorAll('[data-ringkas]').forEach((wadah) => {
  const isi = wadah.querySelector('[data-ringkas-isi]');
  if (!isi || isi.scrollHeight < 320) return;
  isi.style.maxHeight = '14rem';
  isi.style.overflow = 'hidden';
  isi.style.maskImage = 'linear-gradient(#000 65%, transparent)';
  const tombol = document.createElement('button');
  tombol.type = 'button';
  tombol.className = 'mx-auto mt-3 flex h-11 items-center gap-2 rounded-full border-2 border-primary px-6 text-sm font-bold text-primary hover:bg-primary hover:text-white';
  tombol.setAttribute('aria-expanded', 'false');
  tombol.textContent = 'Baca selengkapnya';
  tombol.addEventListener('click', () => {
    const buka = tombol.getAttribute('aria-expanded') !== 'true';
    isi.style.maxHeight = buka ? '' : '14rem';
    isi.style.maskImage = buka ? '' : 'linear-gradient(#000 65%, transparent)';
    tombol.setAttribute('aria-expanded', String(buka));
    tombol.textContent = buka ? 'Tutup' : 'Baca selengkapnya';
  });
  wadah.append(tombol);
});
