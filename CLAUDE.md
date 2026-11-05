# CLAUDE.md — Redesign Website BAZNAS Kabupaten Bangka Selatan

Rencana lengkap: `VIBE_PLANNING_BAZNAS_BASEL.md`. Kerjakan per fase (§14); jangan lanjut sebelum Definition of Done terpenuhi.
Laporan & keputusan per fase: `docs/fase-{0,2,3,4,5,6,7,8}/README.md`. Deploy cPanel: `docs/fase-8/DEPLOY.md`.

## Konteks proyek
- Situs lama: https://baznasbasel.id (pola URL `/home/...`). Daftar URL lama → baru: `docs/fase-0/url-lama.csv`; sudah dimuat ke tabel `redirect`.
- DB kerja lokal: `baznas_basel` (lihat `.env`), sudah dimigrasi 001–014. `baznasba_baznas` = salinan murni dump produksi untuk referensi — jangan diubah.
- Konten berita/artikel/berkah_zakat/program/event ada di tabel `post`; tabel lama tetap ada tapi jangan dipakai untuk situs baru.
- Halaman pilar memfilter `post.id_sub_program` (bukan `jenis = 'program'`) — lihat docs/fase-2.
- Upload: `public/uploads/{post,banner,zakat,mitra,rekening,perundangan}/`, varian WebP `{nama}-240|480|800|1200.webp` (App\Core\Image). `img_srcset()` hanya memakai varian yang ada.
- HTML dari editor/konten lama selalu lewat `App\Services\HtmlSanitizer` sebelum disimpan.
- Target PHP 8.2 (kompatibel 8.4). Jangan pakai fitur di atas 8.2.
- Tailwind CLI: `bin/tailwindcss` (v4.3.3, macOS x64, tidak di-commit).
- Backup DB & upload lama ada di luar proyek: `~/Backups/baznas-basel/`.

## Menjalankan (dev)
```sh
composer dump-autoload        # pertama kali / setelah clone (vendor/ tidak di-commit)
./build-css.sh                # build CSS minified (./build-css.sh --watch saat mengerjakan View)
composer serve                # http://localhost:8000 (APP_URL di .env harus sama)
```
Uji regresi: `tests/README.md` (server uji port 8002/8003, jangan matikan server 8000 milik user). Paket rilis: `./build-rilis.sh [--uploads]` → `rilis/` (tidak di-commit). Uji pasca-deploy: `php database/scripts/uji_rilis.php <url>`.
Variabel lingkungan asli mengalahkan `.env` — mis. `DB_NAME=baznas_rilis php database/scripts/migrate.php`.
Lewat Apache XAMPP: http://localhost/baznas-basel (ubah APP_URL) — butuh XAMPP PHP 8.2.

## Arsitektur singkat
- `public/index.php` → `App\Core\App::run()` → `routes/web.php` → Controller → `View::make()` → layout `app/Views/layouts/site.php`.
- Request selain GET/HEAD otomatis dicek CSRF oleh Router (field `_token` / header `X-CSRF-Token`).
- Sesi hanya dimulai bila dibutuhkan (csrf_token(), flash, login) — halaman tanpa form tidak membuat cookie.
- Error: `HttpException(404|405|419|429)`; exception lain → 500 + log `storage/logs/app-YYYY-MM-DD.log`.
- URL lama → 301 lewat `RedirectService` (Router::fallback, juga saat controller melempar 404). Controller cukup `throw new HttpException(404)` bila data tidak ada.
- Setiap respons 404 dicatat ke tabel `tautan_rusak` (`TautanRusakService`, abaikan probe & /admin); admin membuat pengalihan dari situ (resource `redirect` + `RedirectHook`).
- Produksi: `APP_FORCE_HTTPS` (http/www → 301 ke APP_URL, POST http → 403) dan `APP_HSTS` diatur di `App::kanonik()`/`finalize()`; HSTS tidak di `.htaccess`.
- Tautan dari isian admin selalu lewat `url()` (skema selain http/https/mailto/tel → `#`) + aturan `$polaTautan` di config/admin.php.
- Kunci file cache disertai APP_URL (cache berisi URL absolut).
- Dasbor admin: statistik zakat masuk (`StatistikZakatService`, view `admin/statistik` + `admin/grafik/*`): grafik SVG dirender server tanpa library, tooltip/keyboard di `assets/js/grafik.js`. "Zakat masuk" = pembayaran status Selesai menurut `waktu`. Warna 4 kelompok di `StatistikZakatService::KELOMPOK` sudah divalidasi aman buta warna — jangan ubah urutannya.
- Pengguna admin (`/admin/pengguna`, `PenggunaController` + model `Pengguna`): peran `user.level` 1 = Administrator (semua menu + Pengguna), 3 = Kepala (hanya-baca: Dasbor statistik + menu di `config/admin.php` → `akses_kepala`; selain itu 403 di middleware `AuthAdmin`), lainnya = Operator. Menu khusus Administrator didaftarkan di `config/admin.php` → `khusus_administrator` dan halamannya wajib `throw HttpException(403)` untuk Operator. Akun sendiri tidak bisa dinonaktifkan/diturunkan/dihapus.
- `Auth::aktif()` mengecek ulang akun di DB setiap request admin (ada, aktif, sidik hash password sama) → menonaktifkan/menghapus/mengganti password mengakhiri sesi pemiliknya. Setelah mengganti password sendiri panggil `Auth::perbaruiSidik()`.
- Distribusi (penyaluran) = resource `distribusi` di config/admin.php (tabel `distribusi`, migrasi 009; pilar lewat `sub_program`). Pilihan sumber dana/asnaf/sifat/kecamatan di `App\Models\Distribusi`; aturan asnaf wajib untuk dana zakat di `DistribusiHook`. Statistik dasbor: `StatistikDistribusiService` (periode & potongan waktu sama dengan statistik zakat; ACR = disalurkan ÷ zakat masuk). Data internal — jangan tampilkan di situs publik.
- Berkas di document root selalu lewat `public_path()`, **bukan** `base_path('public/…')`: di hosting, isi `public/` bisa dipindah ke `public_html` sementara kode di `/home/USER/baznas-basel/` (`public/index.php` mendefinisikan PUBLIC_PATH & mencari BASE_PATH; skrip CLI memakai `PUBLIC_PATH` di .env).
- Testimoni pengunjung: form `components/form-testimoni` di beranda (#kirim-testimoni, dilipat) & `/testimoni/kirim` → `TestimoniService` (honeypot, maks 3 kiriman tersimpan/jam/IP, tanpa tautan) → tabel `testimoni` publish N, sumber `pengunjung` (migrasi 010). Di beranda token CSRF diambil JS dari `/testimoni/token` saat mulai mengisi (`assets/js/testimoni.js`) agar beranda tetap tanpa cookie sesi. `no_hp` hanya untuk admin — jangan pernah dipilih di query publik.
- Tampilan dari admin (migrasi 011): warna & kepekatan kotak ikon pilar (`kategori_program.warna_latar/opasitas_latar`, dirender di `components/card-program`) dan Ikon Navigasi bawah (tabel `ikon_nav`, resource `ikon-navigasi`, cache `site` → `SiteService::ikonNav()` → `Navigation::bawah()` → `partials/ikon-nav` dengan `gambar`). Model memakai `SELECT *`/try-catch agar situs tidak error bila kode terunggah sebelum migrasi. Jenis isian admin baru: `warna` (+ centang bawaan, pratinjau) dan `angka` + `geser`.
- "Tunaikan Zakat" beranda (migrasi 013): resource `layanan` (menu "Tunaikan Zakat (Beranda)"), maks. 4 kartu (`Layanan::MAKS_BERANDA`), grid 2/4 kolom; `components/card-layanan` = gambar persegi 1:1 (800×800) (`uploads/layanan`, kosong → bidang polos) di atas judul, tanpa ikon. Tautan `wa.me` selalu diganti nomor `setting('whatsapp')` dan nomornya tampil (`nomor_hp()`). Bottom nav: bilah `h-20` (body `pb-[calc(5rem+…)]`), ikon `size-12`, Bayar Zakat `size-20` + label dua baris.
- Pengumpulan (migrasi 012): setoran ZIS Perangkat Daerah per bulan = resource `pengumpulan` (kolom `periode` = tanggal 1 bulan setoran, `zakat`/`infaq`/`dskl` NULL bila tidak ada, `total` dihitung `PengumpulanHook`) + resource `perangkat-daerah` (34 OPD & kecamatan awal; PD terpakai tidak bisa dihapus → nonaktifkan). Warna/label jenis di `App\Models\Pengumpulan::JENIS` (hijau–biru–kuning, tervalidasi buta warna). Dasbor: `StatistikPengumpulanService` + `admin/statistik-pengumpulan` dengan filter sendiri `?pg_tahun=&pg_bulan=` (filter zakat & pengumpulan saling membawa parameter lewat input hidden); grafik `admin/grafik/tren` (umum: `seri`, `sorot`) & `admin/grafik/batang-tumpuk` (HTML, tooltip `grafik.js`). Data internal — jangan tampilkan di situs publik.
- Impor Excel pengumpulan: `/admin/pengumpulan/impor` (+ `/format-impor` = .xlsx semua PD aktif) → `PengumpulanImporController` → `ImporPengumpulanService` (cocokkan PD via ID lalu nama/singkatan; seluruh file divalidasi dulu, simpan dalam satu transaksi; mode lewati/ganti/tambah bila PD sudah punya data bulan itu). Baca/tulis .xlsx tanpa library: `App\Core\Xlsx` (ext-zip + SimpleXML); unggahan dicek `Upload::excel()` (tidak disimpan). Opsi resource `tombol` = tombol tambahan di atas daftar.
- Jenis isian admin: `bulan` (dua select → `YYYY-MM-01`, kolom daftar "Oktober 2026"), `jumlah` (total otomatis dari `dari`, hanya tampilan). Opsi resource: `grup` (fieldset; field ber-`grup` sama dan berurutan), `kelas` per field, `label_kolom`, `sumber_kolom` (label pilihan khusus daftar), `cari_sql` (kondisi cari tepercaya, tiap `?` diisi pola). Kelas Tailwind di `config/admin.php` ikut dipindai (`@source`).
- `config/admin.php` memakai konstanta kelas → skrip yang me-`require` file itu langsung wajib memuat `vendor/autoload.php` dulu.
- `config/admin.php` mendukung `aksi_baris` (tombol per baris), `keterangan`, field `dari_query` (isi awal dari ?query), `aksi` terbatas (mis. hanya `hapus`).
- Gambar di isi editor HTML (Halaman/Kabar/Penjelasan Zakat): tombol "Gambar" + tempel/seret di `admin.js` → `POST /admin/unggah-gambar` (`UnggahGambarController`, header `X-CSRF-Token`, alamat dari `data-unggah` textarea) → `uploads/konten/` + varian; yang disisipkan varian `-1200.webp` dengan path tanpa domain + alt/width/height (lolos `HtmlSanitizer`). Gambar yang dihapus dari isi tidak ikut terhapus dari disk.
- Laporan keuangan: satu isian `file` tipe `dokumen` = PDF **atau** Excel (`Upload::dokumenPublik()`: `%PDF-` → `pdfPublik()`, selain itu `excelPublik()` — .xlsx wajib berisi `xl/workbook.xml` & tanpa makro, .xls dicek tanda OLE). Label/format tampil lewat `format_dokumen()`; kolom daftar tipe `format`. Tipe file unggahan generik: `ResourceController::TIPE_FILE`.
- Penghitung dilihat berita (`post.views`, migrasi 014 tabel `post_kunjungan`): **jangan** menghitung saat GET halaman. `assets/js/dilihat.js` mengirim `POST /berita/{slug}/dilihat` sekali per perangkat (localStorage) setelah halaman terlihat ±2 detik → `Router::postTanpaCsrf()` + `DilihatService` (same-origin via Origin/Sec-Fetch-Site, saring bot, batas 120/jam/IP, sidik HMAC IP+browser dengan garam acak harian di cache, tanpa simpan IP, petugas admin tidak dihitung). `UPDATE post` untuk views wajib `updated_at = updated_at` (kolom ON UPDATE dipakai dateModified & sitemap). Sidik lama dibersihkan `rawat.php`.
- Halaman dalam: `components/page-header` + `components/grid-post`; isi HTML editor dibungkus `<div class="konten">`.
- Cache konten: `site` (SiteService), `home` (HomeService), `zakat` (ZakatService), `sitemap` (SitemapService) — admin wajib `forget()` yang terkait setelah menyimpan.
- `robots.txt` hanya membuka akses bila `APP_ENV=production` (SEO Lighthouse lokal 69 = wajar).
- Form publik: `csrf_field()` + honeypot `website` + `RateLimiter::terlalu()`; validasi lewat `App\Core\Validator`; error → `Session::flash('errors')` + `_old` lalu redirect 303 (PRG). Error field non-form-field: `galat('nama')`.
- Unggahan: hanya lewat `App\Core\Upload::simpan()`. Data pribadi (bukti transfer) di `storage/bukti/` — jangan pernah di `public/`.
- Rumus zakat ada di dua tempat yang harus sama: `App\Services\ZakatCalculator` (PHP) & `public/assets/js/zakat.js`. Nominal fitrah/fidyah selalu dihitung ulang server.
- Admin: `/admin/masuk`; route di `routes/admin.php` (grup `AuthAdmin`). Konten admin didefinisikan di `config/admin.php` — tambah jenis konten baru di sana (CRUD generik `ResourceController` + `App\Admin\Resource`), aturan khusus lewat `App\Admin\Hook`. Unggahan publik: `Upload::gambarPublik()` / `pdfPublik()` / `hapusPublik()`.
- Kontainer `overflow-x-auto` yang berisi teks `sr-only` wajib `relative` (elemen absolut lolos dari clipping → scroll horizontal di HP).
- Elemen yang disembunyikan sampai JS aktif tapi harus tampil tanpa JS: pakai `data-js-sembunyi` (bukan `hidden` — preflight Tailwind `!important` di layer tidak bisa ditimpa `<noscript>`).
- Komponen UI: `app/Views/components/*` via `$this->insert('components/nama', [...])`; katalog hidup di `/_komponen` (APP_DEBUG). Pakai komponen yang ada sebelum membuat markup baru.
- Data layout (setting, pilar, rekening, halaman) lewat `SiteService` (satu cache `site`); menu di `config/menu.php` + `App\Services\Navigation`.
- Ikon: `icon('nama')` dari `app/Views/icons.php` (SVG garis 24×24). Jangan tambah icon font.
- Uji tampilan 360px: jendela Chrome desktop minimal ±500px — pakai iframe selebar 360px, bukan `--window-size=360`.
- Beranda: data dari `HomeService::get()` (cache `home`). Setelah admin mengubah konten beranda → `HomeService::forget()`; setting/pilar/rekening/halaman → `SiteService::forget()`.
- JS halaman dimuat per halaman lewat `$this->start('scripts')` sebagai `<script type="module" src="asset(...)">` (cache-busting ?v=). Jangan pakai `import` antar-modul (tidak ikut ?v=).
- Performa: section di bawah layar beri class `cv-auto` (bukan footer); konten tab tersembunyi di `<template>`; carousel baru bergaya `invisible` sampai JS aktif (anti-CLS).

## Aturan
- PHP 8.x native, strict_types, PSR-12, namespace App\ (PSR-4).
- Akses DB hanya lewat App\Core\Database (PDO prepared). Tidak ada SQL di View/Controller; letakkan di Model/Service.
- Semua output di View di-escape dengan e(). Konten HTML editor hanya dari kolom yang sudah disanitasi.
- Semua form POST wajib csrf_field() + verifikasi.
- Tailwind v4 (standalone CLI). Jangan pakai CDN Tailwind. Jangan tambah library JS/CSS tanpa alasan dan izin.
- Situs publik: vanilla JS, tanpa jQuery. jQuery hanya di admin.
- Mobile-first: tulis class dasar untuk mobile, tambah sm:/md:/lg: untuk layar besar.
- Gambar selalu lewat helper img() (srcset, width/height, lazy).
- Jangan pernah menampilkan data tabel mustahik/pembayaran/user di halaman publik.
- Setiap selesai fitur UI: cek tampilan 360px & 1280px, dan pastikan budget performa (§1) tidak terlampaui.
- Bahasa UI: Bahasa Indonesia baku, format tanggal "7 September 2026", format uang "Rp1.500.000".
- File `.sql` berisi data dan `.env` tidak boleh masuk repo atau folder `public/`.
