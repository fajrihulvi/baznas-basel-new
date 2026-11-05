// Penghitung "dilihat" berita: sekali per perangkat per berita, setelah halaman benar-benar terlihat ±2 detik.
// Hanya peningkatan — tanpa JS, angka dari server tetap tampil (hanya tidak bertambah).

const wadah = document.querySelector('[data-dilihat]');

if (wadah && !navigator.webdriver) {
  const kunci = `dilihat:${wadah.dataset.id}`;
  const JEDA = 2000;

  const sudah = () => {
    try { return localStorage.getItem(kunci) !== null; } catch { return false; } // penyimpanan diblokir: server tetap mencegah hitung ganda
  };
  const tandai = () => {
    try { localStorage.setItem(kunci, '1'); } catch { /* lihat di atas */ }
  };

  let timer = 0;
  let terkirim = false;

  const kirim = async () => {
    terkirim = true;
    try {
      const res = await fetch(wadah.dataset.url, { method: 'POST', headers: { Accept: 'application/json' }, keepalive: true });
      if (!res.ok) return;
      tandai();
      if (res.status === 200) {
        const { dilihat } = await res.json();
        if (Number.isInteger(dilihat) && dilihat > 0) {
          wadah.querySelector('[data-dilihat-jumlah]').textContent = new Intl.NumberFormat('id-ID').format(dilihat);
          wadah.hidden = false;
        }
      }
    } catch { /* jaringan gagal: dicoba lagi saat berita dibuka kembali */ }
  };

  const atur = () => {
    if (terkirim) return;
    clearTimeout(timer);
    if (document.visibilityState === 'visible') timer = setTimeout(kirim, JEDA);
  };

  if (!sudah()) {
    document.addEventListener('visibilitychange', atur);
    atur();
  }
}
