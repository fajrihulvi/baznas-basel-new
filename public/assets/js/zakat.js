// Kalkulator zakat, form bayar 4 langkah, dan format input rupiah.
// Rumus = App\Services\ZakatCalculator (PHP). Server selalu menghitung & memvalidasi ulang.

const fmt = new Intl.NumberFormat('id-ID');
const rp = (n) => 'Rp' + fmt.format(Math.max(0, Math.round(n)));
const angka = (s) => Number(String(s ?? '').replace(/\D/g, '').slice(0, 15)) || 0;
const desimal = (s) => Number(String(s ?? '').replace(/\./g, '').replace(',', '.')) || 0;
const NISHAB_GRAM = 85;
const kadar = (harta) => Math.floor((harta + 39) / 40); // 2,5% dibulatkan ke atas, aritmetika bulat

function param(el) {
  const d = el.closest('[data-param-zakat]')?.dataset ?? {};
  const n = (k) => Number(d[k] || 0) || null;
  return {
    hargaEmas: n('hargaEmas'),
    nishabBulanan: n('nishabBulanan'),
    nishabTahunan: n('nishabTahunan'),
    nilaiFitrah: n('nilaiFitrah'),
    nilaiFidyah: n('nilaiFidyah'),
  };
}

function maal(rincian, harta, nishab) {
  const wajib = harta >= nishab;
  return {
    rincian,
    wajib,
    zakat: wajib ? kadar(harta) : 0,
    pesan: wajib ? 'Wajib zakat (2,5%)' : 'Belum mencapai nishab — belum wajib zakat, namun dapat berinfaq.',
  };
}

/** @returns {{rincian: string[][], wajib: boolean|null, zakat: number, pesan: string}|null} */
function hitung(jenis, v, p) {
  const hargaEmas = p.hargaEmas || angka(v.harga_emas) || null;
  switch (jenis) {
    case 'penghasilan': {
      const total = angka(v.gaji) + angka(v.bonus);
      const nishab = p.nishabBulanan || (hargaEmas ? Math.round((NISHAB_GRAM * hargaEmas) / 12) : null);
      if (!total || !nishab) return null;
      return maal([['Total penghasilan per bulan', rp(total)], ['Nishab per bulan', rp(nishab)]], total, nishab);
    }
    case 'emas': {
      const gram = desimal(v.gram);
      if (gram <= 0 || !hargaEmas) return null;
      const nilai = Math.round(gram * hargaEmas);
      return maal([['Nilai emas', rp(nilai)], ['Nishab', `${NISHAB_GRAM} gram (${rp(NISHAB_GRAM * hargaEmas)})`]], nilai, NISHAB_GRAM * hargaEmas);
    }
    case 'perdagangan': {
      const harta = angka(v.modal) + angka(v.untung) + angka(v.piutang) - angka(v.utang);
      const nishab = p.nishabTahunan || (hargaEmas ? NISHAB_GRAM * hargaEmas : null);
      if (!nishab || harta === 0) return null;
      if (harta < 0) return maal([['Harta bersih', rp(0)]], 0, nishab);
      return maal([['Harta perdagangan bersih', rp(harta)], ['Nishab per tahun', rp(nishab)]], harta, nishab);
    }
    case 'fitrah': {
      const jiwa = angka(v.jiwa);
      const nilai = p.nilaiFitrah || angka(v.nilai);
      if (!jiwa || !nilai) return null;
      return { rincian: [['Jumlah jiwa', String(jiwa)], ['Nilai per jiwa', rp(nilai)]], wajib: null, zakat: jiwa * nilai, pesan: 'Zakat fitrah yang ditunaikan' };
    }
    case 'fidyah': {
      const hari = angka(v.hari);
      const nilai = p.nilaiFidyah || angka(v.nilai);
      if (!hari || !nilai) return null;
      return { rincian: [['Jumlah hari', String(hari)], ['Fidyah per hari', rp(nilai)]], wajib: null, zakat: hari * nilai, pesan: 'Fidyah yang ditunaikan' };
    }
  }
  return null;
}

// Elemen dibangun dengan textContent — tidak ada HTML dari isian pengguna
function el(tag, kelas, teks) {
  const e = document.createElement(tag);
  if (kelas) e.className = kelas;
  if (teks !== undefined) e.textContent = teks;
  return e;
}

// --- Format input rupiah ([data-rupiah]) -------------------------------------------
document.addEventListener('input', (e) => {
  const input = e.target;
  if (!(input instanceof HTMLInputElement) || !input.hasAttribute('data-rupiah')) return;
  const dariKanan = input.value.length - (input.selectionEnd ?? input.value.length);
  const n = angka(input.value);
  input.value = n ? fmt.format(n) : '';
  const pos = Math.max(0, input.value.length - dariKanan);
  input.setSelectionRange(pos, pos);
});

// --- Halaman kalkulator ----------------------------------------------------------------
document.querySelectorAll('form[data-kalkulator]').forEach((form) => {
  const jenis = form.dataset.kalkulator;
  const idJenis = Number(form.dataset.idJenis || 0);
  const keluaran = form.querySelector('[data-hasil]');
  const urlBayar = form.closest('[data-url-bayar]')?.dataset.urlBayar ?? '/bayar-zakat';
  form.querySelector('[data-tombol-hitung]')?.setAttribute('hidden', '');

  const render = () => {
    const v = Object.fromEntries(new FormData(form));
    const h = hitung(jenis, v, param(form));
    keluaran.replaceChildren(el('p', 'text-sm font-semibold uppercase tracking-wide text-muted', 'Hasil perhitungan'));
    if (!h) {
      keluaran.append(el('p', 'mt-2 text-muted', 'Isi data di samping untuk melihat besaran zakat Anda.'));
      return;
    }
    const dl = el('dl', 'mt-3 space-y-2 text-sm');
    h.rincian.forEach(([label, nilai]) => {
      const baris = el('div', 'flex justify-between gap-4');
      baris.append(el('dt', 'text-muted', label), el('dd', 'text-right font-semibold text-ink', nilai));
      dl.append(baris);
    });
    keluaran.append(
      dl,
      el('p', `mt-4 text-sm font-semibold ${h.wajib === false ? 'text-amber-800' : 'text-primary'}`, h.pesan),
      el('p', 'mt-1 text-3xl font-bold text-ink', rp(h.zakat)),
    );
    const q = new URLSearchParams();
    if (h.zakat > 0 && idJenis) {
      q.set('jenis', String(idJenis));
      if (jenis === 'fitrah' || jenis === 'fidyah') q.set('jumlah', String(angka(jenis === 'fitrah' ? v.jiwa : v.hari)));
      q.set('nominal', String(h.zakat));
      const a = el('a', 'mt-5 inline-flex min-h-12 items-center gap-2 rounded-full bg-primary px-5 font-bold text-white hover:bg-primary-dark', 'Tunaikan sekarang');
      a.href = `${urlBayar}?${q}`;
      keluaran.append(a);
    } else if (h.wajib === false) {
      const a = el('a', 'mt-5 inline-flex min-h-12 items-center gap-2 rounded-full border-2 border-primary px-5 font-bold text-primary hover:bg-primary hover:text-white', 'Berinfaq / bersedekah');
      a.href = urlBayar;
      keluaran.append(a);
    }
  };

  form.addEventListener('input', render);
  form.addEventListener('submit', (e) => { e.preventDefault(); render(); });
});

// --- Form bayar 4 langkah ------------------------------------------------------------
const form = document.querySelector('form[data-bayar]');

if (form) {
  const MIN = 10000;
  const langkah = [...form.querySelectorAll('fieldset[data-langkah]')];
  const indikator = form.querySelector('[data-indikator]');
  const tombolKembali = form.querySelector('[data-kembali]');
  const tombolLanjut = form.querySelector('[data-lanjut]');
  const ringkasan = form.querySelector('[data-ringkasan]');
  const total = form.querySelector('[data-total]');
  const inputNominal = form.querySelector('input[name="nominal"]');
  const p = param(form);
  // Langkah awal dari server (sudah memperhitungkan langkah yang berisi error validasi).
  // Server juga merender indikator, navigasi, & grup isian dalam keadaan akhir → tanpa CLS.
  let aktif = Number(form.dataset.mulai || 1);
  form.querySelectorAll('[data-js-sembunyi]').forEach((x) => {
    x.hidden = true;
    x.removeAttribute('data-js-sembunyi');
  });

  const jenis = () => form.querySelector('input[name="jenis"]:checked');
  const mode = () => jenis()?.dataset.mode || 'nominal';

  function nominalFinal() {
    const m = mode();
    if (m === 'fitrah' && p.nilaiFitrah) return angka(form.elements.jiwa.value) * p.nilaiFitrah;
    if (m === 'fidyah' && p.nilaiFidyah) return angka(form.elements.hari.value) * p.nilaiFidyah;
    return angka(inputNominal.value);
  }

  function aturMode() {
    const m = mode();
    const kalkulator = jenis()?.dataset.kalkulator || '';
    const tampil = {
      jiwa: m === 'fitrah',
      hari: m === 'fidyah',
      nominal: m === 'nominal' || (m === 'fitrah' && !p.nilaiFitrah) || (m === 'fidyah' && !p.nilaiFidyah),
    };
    for (const [grup, aktifkan] of Object.entries(tampil)) {
      const wadah = form.querySelector(`[data-grup="${grup}"]`);
      wadah.hidden = !aktifkan;
      // Field grup tersembunyi dinonaktifkan: tidak divalidasi & tidak terkirim
      wadah.querySelectorAll('input[name]').forEach((i) => {
        i.disabled = !aktifkan;
        if (i.hasAttribute('data-wajib')) i.required = aktifkan;
      });
    }
    form.querySelectorAll('details[data-hitung]').forEach((d) => { d.hidden = d.dataset.hitung !== kalkulator; });
    form.querySelector('[data-nominal-cepat-grup]').hidden = m !== 'nominal' || kalkulator !== '';
    perbaruiTotal();
  }

  function perbaruiTotal() {
    const n = nominalFinal();
    const otomatis = (mode() === 'fitrah' && p.nilaiFitrah) || (mode() === 'fidyah' && p.nilaiFidyah);
    total.classList.toggle('hidden', !otomatis || !n);
    total.textContent = otomatis && n ? `Total yang ditunaikan: ${rp(n)}` : '';
    if (!inputNominal.disabled) {
      inputNominal.setCustomValidity(inputNominal.value && angka(inputNominal.value) < MIN ? `Nominal minimal ${rp(MIN)}.` : '');
    }
  }

  function tampilkan(n, fokus = false) {
    aktif = n;
    langkah.forEach((f) => { f.hidden = Number(f.dataset.langkah) !== n; });
    indikator.querySelectorAll('[data-indikator-langkah]').forEach((li) => {
      const ke = Number(li.dataset.indikatorLangkah);
      const bulatan = li.querySelector('span');
      li.classList.toggle('text-primary', ke <= n);
      if (ke === n) {
        li.setAttribute('aria-current', 'step');
      } else {
        li.removeAttribute('aria-current');
      }
      bulatan.className = 'grid size-9 place-items-center rounded-full border-2 text-sm '
        + (ke < n ? 'border-primary bg-primary text-white' : ke === n ? 'border-primary bg-white text-primary' : 'border-gray-300 bg-white');
    });
    tombolKembali.hidden = n === 1;
    tombolLanjut.hidden = n === langkah.length;
    if (n === langkah.length) isiRingkasan();
    if (fokus) {
      const legend = langkah[n - 1].querySelector('legend');
      legend.tabIndex = -1;
      legend.focus({ preventScroll: true });
      form.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    }
  }

  function validLangkah(n) {
    const f = langkah[n - 1];
    if (n === 1 && !jenis()) {
      f.querySelector('input[name="jenis"]').setCustomValidity('Pilih jenis zakat.');
      f.querySelector('input[name="jenis"]').reportValidity();
      f.querySelector('input[name="jenis"]').setCustomValidity('');
      return false;
    }
    for (const isian of f.querySelectorAll('input[name], select[name], textarea[name]')) {
      if (!isian.disabled && !isian.checkValidity()) {
        isian.reportValidity();
        return false;
      }
    }
    return true;
  }

  function isiRingkasan() {
    const baris = [
      ['Jenis', jenis()?.dataset.nama ?? '-'],
    ];
    if (mode() === 'fitrah') baris.push(['Jumlah jiwa', form.elements.jiwa.value]);
    if (mode() === 'fidyah') baris.push(['Jumlah hari', form.elements.hari.value]);
    baris.push(['Nominal', rp(nominalFinal())]);
    const sapaan = form.querySelector('input[name="sapaan"]:checked')?.value === 'IBU' ? 'Ibu' : 'Bapak';
    baris.push(['Nama', `${sapaan} ${form.elements.nama_lengkap.value}`], ['WhatsApp', form.elements.no_hp.value]);
    if (form.elements.email.value) baris.push(['Email', form.elements.email.value]);
    if (form.elements.npwz.value) baris.push(['NPWZ', form.elements.npwz.value]);

    ringkasan.replaceChildren(...baris.map(([label, nilai]) => {
      const div = el('div', 'flex justify-between gap-4 px-5 py-3 text-sm');
      div.append(el('dt', 'text-muted', label), el('dd', 'text-right font-semibold text-ink break-all', nilai));
      return div;
    }));
  }

  tombolLanjut.addEventListener('click', () => { if (validLangkah(aktif)) tampilkan(aktif + 1, true); });
  tombolKembali.addEventListener('click', () => tampilkan(aktif - 1, true));
  form.addEventListener('change', (e) => { if (e.target.name === 'jenis') aturMode(); });
  form.addEventListener('input', perbaruiTotal);

  form.querySelectorAll('[data-nominal-cepat]').forEach((b) => b.addEventListener('click', () => {
    inputNominal.value = fmt.format(Number(b.dataset.nominalCepat));
    perbaruiTotal();
  }));

  // Kalkulator mini di langkah 2
  form.querySelectorAll('details[data-hitung]').forEach((d) => {
    const keluaran = d.querySelector('[data-hasil-mini]');
    const pakai = d.querySelector('[data-pakai-hasil]');
    let hasil = 0;
    d.addEventListener('input', () => {
      const v = {};
      d.querySelectorAll('[data-isian]').forEach((i) => { v[i.dataset.isian] = i.value; });
      const h = hitung(d.dataset.hitung, v, p);
      hasil = h?.zakat ?? 0;
      keluaran.textContent = h ? `${h.pesan}: ${rp(h.zakat)}` : 'Isi data di atas untuk menghitung.';
      pakai.hidden = hasil <= 0;
    });
    pakai.addEventListener('click', () => {
      inputNominal.value = fmt.format(hasil);
      perbaruiTotal();
      d.open = false;
      inputNominal.focus();
    });
  });

  // Kirim: pastikan semua langkah valid, cegah kirim ganda
  form.addEventListener('submit', (e) => {
    // Pesan validasi hanya bisa tampil pada langkah yang terlihat → periksa satu per satu
    for (let n = 1; n <= langkah.length; n++) {
      tampilkan(n);
      if (!validLangkah(n)) {
        e.preventDefault();
        return;
      }
    }
    const kirim = form.querySelector('button[type="submit"]');
    kirim.disabled = true;
    kirim.textContent = 'Mengirim…';
  });

  aturMode();
  tampilkan(aktif);
}
