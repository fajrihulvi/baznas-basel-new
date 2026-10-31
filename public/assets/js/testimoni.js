// Form testimoni di beranda: token CSRF baru diambil saat pengunjung mulai mengisi,
// sehingga beranda tetap tanpa cookie sesi (bisa di-cache) bagi pengunjung yang hanya membaca.
document.querySelectorAll('form[data-testimoni]').forEach((form) => {
  const token = form.querySelector('input[name="_token"]');
  let permintaan = null;

  const ambilToken = () => {
    permintaan ??= fetch(form.dataset.token, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
      .then((d) => { token.value = d.token; })
      .catch(() => { permintaan = null; }); // gagal (mis. offline): coba lagi saat kirim
    return permintaan;
  };

  form.addEventListener('focusin', () => { if (!token.value) ambilToken(); });
  form.addEventListener('submit', (e) => {
    if (token.value) return;
    e.preventDefault();
    ambilToken().then(() => { if (token.value) form.requestSubmit(); });
  });
});
