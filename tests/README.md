# Uji regresi (lokal)

Untuk pengembang. Uji ini **mengubah lalu memulihkan** data di DB lokal `baznas_basel` (akun admin uji sementara, pembayaran uji, pengaturan zakat dikosongkan sementara). Jangan arahkan ke server produksi; untuk produksi pakai `database/scripts/uji_rilis.php`.
Folder ini tidak ikut paket rilis (`build-rilis.sh`).

## Prasyarat
- MySQL XAMPP menyala, DB `baznas_basel` sudah dimigrasi (`php database/scripts/migrate.php`).
- Server uji di port **8002** (bukan 8000 milik `composer serve`). Untuk `uji_fase8.php` juga port **8003** dalam mode produksi:
  ```sh
  APP_URL=http://localhost:8002 php -d upload_max_filesize=11M -d post_max_size=12M -S localhost:8002 -t public tests/http/router.php
  APP_ENV=production APP_DEBUG=false APP_URL=https://baznas.test APP_FORCE_HTTPS=true APP_HSTS=31536000 \
    php -S localhost:8003 -t public tests/http/router.php
  ```
  `router.php` meniru Apache produksi (gzip & cache aset).

## Menjalankan
```sh
php tests/test_core.php                                   # inti: router, validator, helper, sanitizer
php tests/test_fase2.php                                  # hasil migrasi DB
php tests/http/uji_route.php  http://localhost:8002       # semua halaman publik & redirect URL lama
php tests/http/uji_bayar.php  http://localhost:8002       # bayar zakat, konfirmasi, unggahan berbahaya, kalkulator
php tests/http/uji_admin.php  http://localhost:8002       # login, CRUD semua konten, pembayaran, pengaturan
php tests/http/uji_fase8.php  http://localhost:8002 http://localhost:8003   # tautan rusak, pengalihan, status, HTTPS/HSTS
php tests/http/uji_pengguna.php http://localhost:8002     # menu Pengguna: peran, validasi, sesi berakhir saat akun diubah
php tests/http/uji_distribusi.php http://localhost:8002   # menu Distribusi: CRUD, validasi, statistik dasbor, akses Kepala
php tests/http/uji_testimoni.php http://localhost:8002    # form testimoni pengunjung: validasi, anti-spam, moderasi admin
php tests/http/uji_tampilan.php http://localhost:8002     # warna kotak ikon pilar, Ikon Navigasi bawah, kartu Tunaikan Zakat
php tests/http/uji_pengumpulan.php http://localhost:8002  # menu Pengumpulan & Perangkat Daerah, impor Excel, statistik dasbor, akses Kepala
php tests/http/uji_dilihat.php http://localhost:8002      # penghitung dilihat berita: asal permintaan, hitung ganda, bot, petugas, batas laju
php tests/http/uji_laporan.php http://localhost:8002      # laporan keuangan: satu isian PDF atau Excel, validasi isi file, halaman /laporan
```
Setiap skrip diakhiri `SEMUA LULUS` atau daftar `GAGAL`. Berkas sementara di `tests/tmp/` (diabaikan git).

## Uji browser (opsional)
`tests/browser/uji-csp.mjs` membuka halaman di Chrome headless dan mencatat pelanggaran CSP / error JS. Butuh Node 18+ dan `npm install puppeteer-core` di folder kerja terpisah, lalu server Apache yang mengirim header `.htaccess` sungguhan:
```sh
UJI_USER=... UJI_PASS=... node uji-csp.mjs http://localhost:8090
```
