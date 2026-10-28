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

  TOMBOL.forEach(([perintah, teks, judul, kelas]) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.textContent = teks;
    b.title = judul;
    b.setAttribute('aria-label', judul);
    b.className = `inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm hover:bg-white hover:text-primary ${kelas}`;
    b.addEventListener('mousedown', (e) => e.preventDefault()); // jaga seleksi teks
    b.addEventListener('click', () => {
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
  wadah.append(toolbar, area, ta);
  ta.classList.add('rounded-none', 'border-0', 'min-h-72');
  ta.form?.addEventListener('submit', sinkron);
});
