// JS panel admin (tanpa library). Semua fitur bersifat peningkatan:
// tanpa JS, editor tetap berupa textarea HTML dan form tetap bisa dikirim.

// --- Konfirmasi sebelum aksi berisiko (form[data-konfirmasi]) ------------------------
document.addEventListener('submit', (e) => {
  const form = e.target;
  if (form instanceof HTMLFormElement && form.dataset.konfirmasi && !window.confirm(form.dataset.konfirmasi)) {
    e.preventDefault();
  }
});

// --- Format rupiah ([data-rupiah]) ---------------------------------------------------
const fmt = new Intl.NumberFormat('id-ID');
document.addEventListener('input', (e) => {
  const input = e.target;
  if (!(input instanceof HTMLInputElement) || !input.hasAttribute('data-rupiah')) return;
  const n = Number(input.value.replace(/\D/g, '').slice(0, 15)) || 0;
  input.value = n ? fmt.format(n) : '';
});

// --- Total otomatis beberapa isian rupiah (output[data-jumlah="zakat infaq dskl"]) ---
document.querySelectorAll('output[data-jumlah]').forEach((keluaran) => {
  const form = keluaran.closest('form');
  const isian = keluaran.dataset.jumlah.split(' ').map((nama) => form.elements[nama]).filter(Boolean);
  const hitung = () => {
    const total = isian.reduce((jml, el) => jml + (Number(el.value.replace(/\D/g, '').slice(0, 15)) || 0), 0);
    keluaran.textContent = keluaran.dataset.satuan ? `${fmt.format(total)} ${keluaran.dataset.satuan}` : `Rp${fmt.format(total)}`;
  };
  isian.forEach((el) => el.addEventListener('input', hitung));
  hitung();
});

// --- Persentase otomatis satu isian terhadap jumlah beberapa isian (output[data-persen]) ---
const fmtPersen = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
document.querySelectorAll('output[data-persen]').forEach((keluaran) => {
  const form = keluaran.closest('form');
  const nilai = (el) => Number(el.value.replace(/\D/g, '').slice(0, 15)) || 0;
  const isian = keluaran.dataset.persenDari.split(' ').map((nama) => form.elements[nama]).filter(Boolean);
  const hitung = () => {
    const total = isian.reduce((jml, el) => jml + nilai(el), 0);
    keluaran.textContent = `${fmtPersen.format(total ? (nilai(form.elements[keluaran.dataset.persen]) / total) * 100 : 0)}%`;
  };
  isian.forEach((el) => el.addEventListener('input', hitung));
  hitung();
});

// --- Grup isian yang hanya relevan bila sebuah centang aktif (fieldset[data-tampil-jika="nama"]) ---
document.querySelectorAll('fieldset[data-tampil-jika]').forEach((grup) => {
  const centang = grup.form?.elements[grup.dataset.tampilJika];
  if (!(centang instanceof HTMLInputElement)) return;
  const perbarui = () => { grup.hidden = !centang.checked; };
  centang.addEventListener('change', perbarui);
  perbarui();
});

// --- Penggeser (input range) menampilkan nilainya di <output for> --------------------
document.querySelectorAll('input[type="range"]').forEach((geser) => {
  const keluaran = geser.form?.querySelector(`output[for="${geser.id}"]`);
  if (keluaran) geser.addEventListener('input', () => { keluaran.textContent = `${geser.value}%`; });
});

// --- Pratinjau warna + transparansi kotak ([data-pratinjau-warna]) -------------------
document.querySelectorAll('[data-pratinjau-warna]').forEach((kotak) => {
  const form = kotak.closest('form');
  const warna = form.elements[kotak.dataset.pratinjauWarna];
  const bawaan = form.elements[`bawaan_${kotak.dataset.pratinjauWarna}`];
  const opasitas = kotak.dataset.pratinjauOpasitas ? form.elements[kotak.dataset.pratinjauOpasitas] : null;
  const perbarui = () => {
    const pakaiBawaan = bawaan?.checked;
    warna.disabled = false; // tetap terkirim; server mengabaikannya bila "bawaan" dicentang
    warna.style.opacity = pakaiBawaan ? '0.4' : '';
    if (opasitas) opasitas.closest('div').style.opacity = pakaiBawaan ? '0.4' : '';
    kotak.classList.toggle('bg-primary/10', Boolean(pakaiBawaan));
    if (pakaiBawaan) {
      kotak.style.backgroundColor = '';
      return;
    }
    const [r, g, b] = [1, 3, 5].map((i) => parseInt(warna.value.slice(i, i + 2), 16));
    kotak.style.backgroundColor = `rgb(${r} ${g} ${b} / ${opasitas ? opasitas.value : 100}%)`;
  };
  form.addEventListener('input', perbarui);
  form.addEventListener('change', perbarui);
  perbarui();
});

// --- Pratinjau gambar sebelum diunggah (data: URL — diizinkan CSP) --------------------
document.querySelectorAll('input[type="file"][data-pratinjau]').forEach((input) => {
  const img = document.createElement('img');
  img.alt = 'Pratinjau gambar yang dipilih';
  img.className = 'mt-3 hidden max-h-48 rounded-xl border border-gray-200 object-contain';
  input.after(img);
  input.addEventListener('change', () => {
    const file = input.files?.[0];
    if (!file || !file.type.startsWith('image/')) {
      img.classList.add('hidden');
      return;
    }
    const baca = new FileReader();
    baca.onload = () => { img.src = String(baca.result); img.classList.remove('hidden'); };
    baca.readAsDataURL(file);
  });
});

// --- Editor teks (textarea[data-editor]) ---------------------------------------------
const TOMBOL = [
  ['bold', 'B', 'Tebal', 'font-bold'],
  ['italic', 'I', 'Miring', 'italic'],
  ['h2', 'H2', 'Judul bagian', 'font-bold'],
  ['h3', 'H3', 'Subjudul', 'font-bold'],
  ['p', '¶', 'Paragraf biasa', ''],
  ['ul', '• Daftar', 'Daftar berpoin', ''],
  ['ol', '1. Daftar', 'Daftar bernomor', ''],
  ['blockquote', '“ ”', 'Kutipan', ''],
  ['link', 'Tautan', 'Sisipkan tautan', 'underline'],
  ['unlink', 'Lepas tautan', 'Hapus tautan', ''],
  ['image', 'Gambar', 'Sisipkan gambar (bisa juga ditempel atau diseret ke editor)', ''],
  ['removeFormat', 'Bersihkan', 'Hapus format', ''],
  ['html', '</>', 'Sunting HTML', 'font-mono'],
];

document.execCommand('defaultParagraphSeparator', false, 'p');

document.querySelectorAll('textarea[data-editor]').forEach((ta) => {
  const label = document.querySelector(`label[for="${ta.id}"]`)?.textContent.trim() ?? 'Isi';
  const wadah = document.createElement('div');
  wadah.className = 'overflow-hidden rounded-xl border border-gray-300 bg-white focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/30';

  const toolbar = document.createElement('div');
  toolbar.setAttribute('role', 'toolbar');
  toolbar.setAttribute('aria-label', `Format ${label}`);
  toolbar.className = 'flex flex-wrap gap-1 border-b border-gray-200 bg-surface p-2';

  const area = document.createElement('div');
  area.contentEditable = 'true';
  area.setAttribute('role', 'textbox');
  area.setAttribute('aria-multiline', 'true');
  area.setAttribute('aria-label', label);
  if (ta.getAttribute('aria-describedby')) area.setAttribute('aria-describedby', ta.getAttribute('aria-describedby'));
  area.className = 'konten min-h-72 px-4 py-3 text-ink focus:outline-none';
  area.innerHTML = ta.value || '<p><br></p>';

  let modeHtml = false;
  const sinkron = () => { if (!modeHtml) ta.value = area.innerHTML; };

  // --- Gambar: unggah ke /admin/unggah-gambar lalu sisipkan di posisi kursor ---------
  const pilihFile = document.createElement('input');
  pilihFile.type = 'file';
  pilihFile.accept = 'image/jpeg,image/png,image/webp';
  pilihFile.hidden = true;
  let posisi = null; // seleksi terakhir di editor (hilang saat dialog file terbuka)
  const simpanPosisi = () => {
    const sel = window.getSelection();
    if (sel.rangeCount && area.contains(sel.getRangeAt(0).commonAncestorContainer)) posisi = sel.getRangeAt(0).cloneRange();
  };
  area.addEventListener('keyup', simpanPosisi);
  area.addEventListener('mouseup', simpanPosisi);
  area.addEventListener('input', simpanPosisi);

  const tombolGambar = () => toolbar.querySelector('[data-perintah="image"]');
  const unggahGambar = async (file) => {
    if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type)) {
      window.alert('Format gambar harus JPG, PNG, atau WebP.');
      return;
    }
    const alt = window.prompt('Keterangan singkat gambar (dibacakan pembaca layar & mesin pencari):', '');
    if (alt === null) return; // dibatalkan
    const tombol = tombolGambar();
    tombol.disabled = true;
    tombol.textContent = 'Mengunggah…';
    try {
      const data = new FormData();
      data.append('gambar', file);
      const res = await fetch(ta.dataset.unggah, {
        method: 'POST', body: data, credentials: 'same-origin',
        headers: { 'X-CSRF-Token': ta.form?.elements._token?.value ?? '', Accept: 'application/json' },
      });
      const hasil = await res.json().catch(() => ({}));
      if (!res.ok || !hasil.src) {
        throw new Error(hasil.galat || (res.status === 413 ? 'Ukuran gambar terlalu besar.' : res.status === 419 ? 'Sesi kedaluwarsa. Muat ulang halaman (isi yang belum disimpan akan hilang — salin dulu).' : 'Gambar gagal diunggah.'));
      }
      const img = document.createElement('img');
      img.src = hasil.src;
      img.alt = alt.trim();
      img.width = hasil.width;
      img.height = hasil.height;
      area.focus();
      const sel = window.getSelection();
      if (posisi) { sel.removeAllRanges(); sel.addRange(posisi); }
      if (sel.rangeCount && area.contains(sel.getRangeAt(0).commonAncestorContainer)) {
        const range = sel.getRangeAt(0);
        range.deleteContents();
        range.insertNode(img);
        range.setStartAfter(img);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
      } else {
        const p = document.createElement('p');
        p.append(img);
        area.append(p);
      }
      sinkron();
    } catch (err) {
      window.alert(err.message);
    } finally {
      tombol.disabled = false;
      tombol.textContent = 'Gambar';
    }
  };
  pilihFile.addEventListener('change', () => {
    unggahGambar(pilihFile.files?.[0]);
    pilihFile.value = '';
  });
  // Tempel (Ctrl+V) atau seret file gambar langsung ke editor
  area.addEventListener('paste', (e) => {
    const file = [...(e.clipboardData?.files ?? [])].find((f) => f.type.startsWith('image/'));
    if (file) { e.preventDefault(); simpanPosisi(); unggahGambar(file); }
  });
  area.addEventListener('drop', (e) => {
    const file = [...(e.dataTransfer?.files ?? [])].find((f) => f.type.startsWith('image/'));
    if (!file) return;
    e.preventDefault();
    const range = document.caretRangeFromPoint?.(e.clientX, e.clientY);
    if (range) posisi = range;
    unggahGambar(file);
  });

  TOMBOL.forEach(([perintah, teks, judul, kelas]) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.textContent = teks;
    b.title = judul;
    b.setAttribute('aria-label', judul);
    b.className = `inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm hover:bg-white hover:text-primary disabled:opacity-60 ${kelas}`;
    b.dataset.perintah = perintah;
    b.addEventListener('mousedown', (e) => e.preventDefault()); // jaga seleksi teks
    b.addEventListener('click', () => {
      if (perintah === 'image') {
        if (modeHtml) return;
        simpanPosisi();
        pilihFile.click();
        return;
      }
      if (perintah === 'html') {
        modeHtml = !modeHtml;
        if (modeHtml) {
          ta.value = area.innerHTML;
        } else {
          area.innerHTML = ta.value;
        }
        ta.hidden = !modeHtml;
        area.hidden = modeHtml;
        b.setAttribute('aria-pressed', String(modeHtml));
        (modeHtml ? ta : area).focus();
        return;
      }
      if (modeHtml) return;
      area.focus();
      switch (perintah) {
        case 'h2': case 'h3': case 'p': case 'blockquote':
          document.execCommand('formatBlock', false, `<${perintah}>`);
          break;
        case 'ul':
          document.execCommand('insertUnorderedList');
          break;
        case 'ol':
          document.execCommand('insertOrderedList');
          break;
        case 'link': {
          const url = window.prompt('Alamat tautan (https://… atau /halaman):', 'https://');
          if (url && /^(https?:\/\/|\/|mailto:|tel:)/i.test(url.trim())) document.execCommand('createLink', false, url.trim());
          break;
        }
        case 'removeFormat':
          document.execCommand('removeFormat');
          document.execCommand('formatBlock', false, '<p>');
          break;
        default:
          document.execCommand(perintah);
      }
      sinkron();
    });
    toolbar.append(b);
  });

  area.addEventListener('input', sinkron);
  ta.hidden = true;
  ta.before(wadah);
  wadah.append(toolbar, area, ta, pilihFile);
  if (!ta.dataset.unggah) tombolGambar()?.remove();
  ta.classList.add('rounded-none', 'border-0', 'min-h-72');
  ta.form?.addEventListener('submit', sinkron);
});

// --- Kirim isian teks terkode heksadesimal (lolos firewall hosting) ----------------------
// ModSecurity/WAF di hosting kadang menolak (403) isi wajar, mis. "Al-Qur'an", "2,5 % x (…)" atau kutipan
// pasal, karena mirip pola serangan. Nilai textarea & isian teks dikodekan saat FormData dibentuk (tampilan
// form tidak berubah) dan dibuka kembali oleh App\Core\Request (hanya /admin). Hex dipilih (bukan base64)
// karena hanya berisi 0-9a-f: tidak ada '+', '=', '/' yang bisa dihitung WAF sebagai karakter khusus.
// Tanpa JS, form tetap terkirim biasa.
const KODE_TIPE = new Set(['textarea', 'text', 'password', 'email', 'url', 'search', 'tel']);
const HEX = Array.from({ length: 256 }, (_, i) => i.toString(16).padStart(2, '0'));
const keHex = (teks) => {
  const bita = new TextEncoder().encode(teks);
  let hasil = '';
  for (let i = 0; i < bita.length; i++) hasil += HEX[bita[i]];
  return hasil;
};
document.addEventListener('formdata', (e) => {
  const form = e.target;
  if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') return;
  const fd = e.formData;
  for (const el of form.elements) {
    const tipe = el instanceof HTMLTextAreaElement ? 'textarea' : el instanceof HTMLInputElement ? el.type : '';
    if (!KODE_TIPE.has(tipe) || !/^[A-Za-z0-9_-]+$/.test(el.name) || el.disabled) continue;
    const nilai = fd.get(el.name);
    if (typeof nilai !== 'string' || nilai === '' || fd.getAll(el.name).length !== 1) continue;
    fd.set(el.name, keHex(nilai));
    fd.append('_hex[]', el.name);
  }
}, true);
