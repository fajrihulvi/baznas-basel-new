# VIBE PLANNING — Redesign Website BAZNAS Kabupaten Bangka Selatan

> Blueprint pengerjaan bertahap di Claude Code.
> Stack: **PHP 8.x native + PDO + MySQL/MariaDB + Tailwind CSS + JavaScript (vanilla, jQuery opsional)**
> Prinsip utama: **Mobile-first · Cepat (performance budget ketat) · Aman · Mudah dirawat**
> Referensi layout: beranda BAZNAS Kab. Sumedang (struktur section saja — **jangan menyalin kode/aset**, situs tersebut terindikasi disusupi spam link).

---

## 0. Ringkasan

| Item | Keputusan |
|---|---|
| Tujuan | Redesign situs publik BAZNAS Basel dengan layout ala BAZNAS Sumedang, mobile-first dengan bottom navigation, skor Lighthouse mobile ≥ 90 |
| Database | Tetap memakai DB lama `baznasba_baznas` (MariaDB 10.6) + migrasi bertahap (tabel baru, kolom tambahan, konsolidasi konten) |
| Hosting | cPanel shared hosting (PHP 8.4 tersedia), dev lokal XAMPP macOS |
| Arsitektur | MVC ringan tanpa framework, front controller, autoload PSR-4 via Composer |
| CSS | Tailwind v4 via **Standalone CLI** (tanpa Node.js), output 1 file minified |
| JS | Vanilla JS (ES modules, `defer`) untuk situs publik; jQuery hanya di admin bila butuh plugin |
| Ruang lingkup Fase 1 | Situs publik + modul admin untuk tabel baru. Admin lama tetap jalan sampai admin baru siap |

---

## 1. Target Performa (Performance Budget)

| Metrik | Target (mobile, 4G lambat) |
|---|---|
| Lighthouse Performance | ≥ 90 |
| LCP (Largest Contentful Paint) | < 2,5 dtk |
| CLS (Cumulative Layout Shift) | < 0,1 |
| INP (Interaction to Next Paint) | < 200 ms |
| TTFB | < 600 ms (dengan cache) |
| CSS total | < 30 KB gzip |
| JS publik total | < 30 KB gzip (tanpa jQuery) |
| Berat halaman beranda (first load) | < 1 MB |
| Jumlah request awal | < 25 |
| Font | Maks 1 family, 2 weight, WOFF2, self-hosted |

Aturan: **setiap PR/fase yang melanggar budget wajib diperbaiki sebelum lanjut.**

---

## 2. Tech Stack & Prinsip Teknis

### 2.1 Backend
- PHP 8.2+ (kompatibel 8.4), `declare(strict_types=1);` di semua file.
- PDO MySQL: `ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false`, `FETCH_ASSOC`, charset `utf8mb4`.
- Composer hanya untuk autoload PSR-4 + library kecil yang benar-benar perlu (mis. `vlucas/phpdotenv`). Tidak ada framework.
- Coding style PSR-12. Tidak ada SQL di View. Tidak ada `echo` tanpa escape.
- OPcache aktif di production.

### 2.2 Frontend
- Tailwind CSS v4 Standalone CLI (binary macOS untuk dev, build di lokal, hasil `app.css` di-commit/di-upload).
- Tidak memakai Tailwind Play CDN di production.
- Vanilla JS modular (`/assets/js/app.js` + modul kecil per fitur). Semua script `defer`/`type="module"`.
- jQuery 3.7 (lokal, bukan CDN) **hanya** di admin panel jika memakai plugin seperti DataTables. Situs publik tidak memuat jQuery.
- Tidak memakai library carousel berat (Swiper/Slick). Carousel memakai **CSS scroll-snap** + JS kecil untuk autoplay/tombol.

### 2.3 Lingkungan
- Dev: XAMPP macOS → `/Applications/XAMPP/xamppfiles/htdocs/baznas-basel/`
- Prod: cPanel. Document root diarahkan ke `public/`. Bila tidak bisa, pakai `.htaccess` di root yang me-rewrite ke `public/` dan memblokir akses folder lain.

---

## 3. Struktur Folder

```
baznas-basel/
├── app/
│   ├── Core/            # Router, Database, View, Request, Response, Session, Csrf,
│   │                    # Validator, Cache, Image, Slug, Paginator, Auth, RateLimiter
│   ├── Controllers/
│   │   ├── Site/        # HomeController, PostController, ProgramController, ZakatController,
│   │   │                # PaymentController, PageController, RegulasiController, ...
│   │   └── Admin/       # Dashboard, Post, Banner, Testimoni, Halaman, Amil, Galeri, Setting, ...
│   ├── Models/          # Satu class per tabel (query via PDO prepared statement)
│   ├── Services/        # HomeService (agregasi data beranda + cache), PaymentService,
│   │                    # ZakatCalculator, ImageService, SeoService, SitemapService
│   ├── Views/
│   │   ├── layouts/     # site.php, admin.php
│   │   ├── partials/    # header, bottom-nav, footer, menu-sheet, seo-meta
│   │   ├── components/  # card-post, card-program, card-video, card-testimoni, section-title,
│   │   │                # carousel, button, badge, pagination, breadcrumb
│   │   ├── site/        # home, post-list, post-detail, program, zakat, bayar, konfirmasi, ...
│   │   └── admin/
│   └── helpers.php      # e(), url(), asset(), img(), csrf_field(), old(), format_rupiah(), tgl_id()
├── config/              # app.php, database.php, cache.php (membaca .env)
├── database/
│   ├── migrations/      # 001_*.sql, 002_*.sql ... (berurutan, idempoten bila memungkinkan)
│   └── scripts/         # migrate_posts.php, generate_image_variants.php, rehash_notice.php
├── public/              # DOCUMENT ROOT
│   ├── index.php        # front controller
│   ├── .htaccess
│   ├── assets/
│   │   ├── css/app.css  # hasil build Tailwind (minified)
│   │   ├── js/          # app.js, carousel.js, lite-yt.js, calculator.js, menu-sheet.js
│   │   ├── fonts/       # *.woff2
│   │   └── img/         # logo, ikon SVG, placeholder
│   ├── uploads/         # gambar konten + varian WebP (eksekusi PHP diblokir)
│   ├── robots.txt
│   └── favicon / manifest.webmanifest
├── resources/css/app.css # input Tailwind (@import "tailwindcss"; @theme {...}; @source ...)
├── routes/web.php, routes/admin.php
├── storage/cache/, storage/logs/  # tidak bisa diakses publik
├── .env (di luar public), .env.example
├── composer.json
└── CLAUDE.md            # aturan singkat untuk Claude Code (lihat §15)
```

---

## 4. Komponen Inti (app/Core)

| Komponen | Tanggung jawab |
|---|---|
| `Router` | Routing GET/POST, parameter `{slug}`, grup `/admin` dengan middleware auth + csrf |
| `Database` | Singleton PDO, helper `fetch()`, `fetchAll()`, `execute()`, `transaction()` |
| `View` | Render PHP template + layout + section; data di-escape lewat `e()` |
| `Request` / `Response` | Input tersanitasi, redirect, JSON, header cache, 404/500 |
| `Session` | Cookie `HttpOnly`, `Secure`, `SameSite=Lax`, regenerate ID saat login |
| `Csrf` | Token per sesi, verifikasi semua POST |
| `Validator` | Rule sederhana: required, email, numeric, min, max, in, image |
| `Cache` | File cache (`storage/cache`) dengan TTL + `forget()`; dipakai untuk data beranda, menu, setting |
| `Image` | Upload aman (cek MIME via `finfo`, re-encode GD), buat varian WebP 480/800/1200 |
| `Slug` | Slug Indonesia, unik per tabel |
| `Paginator` | Pagination dengan `LIMIT/OFFSET` + link SEO-friendly `?hal=2` |
| `Auth` | Login admin, `password_hash`/`password_verify`, rate limit percobaan login |
| `RateLimiter` | Batasi form publik (bayar zakat, konfirmasi) per IP |

---

## 5. Database

### 5.1 Prinsip migrasi
1. **Backup penuh** DB lama + folder upload lama sebelum apa pun.
2. Migrasi berupa file SQL bernomor di `database/migrations/`, dijalankan berurutan.
3. Tabel lama **tidak dihapus** di Fase 1; konsolidasi memakai tabel baru + kolom `legacy_*` untuk jejak dan redirect 301.
4. Semua tabel diseragamkan ke `utf8mb4_unicode_ci`.

### 5.2 Perbaikan tabel lama

```sql
-- 001_fix_charset_and_types.sql
ALTER TABLE kategori_zakat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE laporan_keuangan CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mitra CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE perundangan CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- (ulangi untuk semua tabel latin1 / utf8mb3: icon, konfig, level, log, menu, mustahik,
--  pengambilan_beras, sisa_beras, user)

-- Bug: card_number INT overflow (semua tercatat 2147483647)
ALTER TABLE pengambilan_beras MODIFY card_number VARCHAR(20) NOT NULL;

-- Keamanan akun admin
ALTER TABLE user MODIFY password VARCHAR(255) NOT NULL;
ALTER TABLE user DROP COLUMN confirm_password;
-- Password admin WAJIB di-reset dan di-hash ulang dengan password_hash() (bcrypt/argon2id)

-- Banner: link tujuan, urutan, gambar khusus mobile
ALTER TABLE banner
  ADD link VARCHAR(255) NULL AFTER foto,
  ADD foto_mobile VARCHAR(255) NULL AFTER foto,
  ADD urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ADD INDEX idx_banner_pub (publish, urutan);

-- Kategori program → 5 pilar "Basel Peduli/Cerdas/Sehat/Taqwa/Makmur"
ALTER TABLE kategori_program
  ADD slug VARCHAR(100) NULL,
  ADD label VARCHAR(100) NULL,          -- mis. "Basel Sehat"
  ADD deskripsi VARCHAR(255) NULL,
  ADD ikon VARCHAR(255) NULL,           -- path SVG
  ADD urutan TINYINT UNSIGNED NOT NULL DEFAULT 0;

-- Pembayaran: kode lebih panjang & unik, relasi ke konfirmasi
ALTER TABLE pembayaran MODIFY kode_pembayaran VARCHAR(12) NOT NULL,
  ADD UNIQUE KEY uq_kode (kode_pembayaran);   -- bersihkan duplikat dulu bila ada
ALTER TABLE konfirmasi_pembayaran
  ADD id_pembayaran INT NULL AFTER id_konfirmasi_pembayaran,
  ADD status ENUM('baru','diverifikasi','ditolak') NOT NULL DEFAULT 'baru';
```

Data awal 5 pilar:

| id | kategori_program | label | slug |
|---|---|---|---|
| 1 | Kemanusiaan | Basel Peduli | basel-peduli |
| 2 | Pendidikan | Basel Cerdas | basel-cerdas |
| 3 | Kesehatan | Basel Sehat | basel-sehat |
| 4 | Dakwah | Basel Taqwa | basel-taqwa |
| 5 | Ekonomi | Basel Makmur | basel-makmur |

### 5.3 Tabel baru

```sql
-- 002_create_post.sql  (konsolidasi artikel, berita, berkah_zakat, program, event)
CREATE TABLE post (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis ENUM('berita','artikel','program','berkah_zakat','event') NOT NULL,
  judul VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  ringkasan VARCHAR(300) NULL,          -- auto dari konten bila kosong (strip_tags, 160 char)
  konten MEDIUMTEXT NOT NULL,
  foto VARCHAR(255) NULL,               -- nama file dasar; varian: {nama}-480.webp dst.
  foto_w SMALLINT UNSIGNED NULL,        -- dimensi asli → atribut width/height (anti-CLS)
  foto_h SMALLINT UNSIGNED NULL,
  id_sub_program INT NULL,
  penulis VARCHAR(100) NULL,
  tgl DATE NOT NULL,
  publish ENUM('Y','N') NOT NULL DEFAULT 'N',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,   -- tampil di ticker / sorotan
  views INT UNSIGNED NOT NULL DEFAULT 0,
  legacy_table VARCHAR(30) NULL,
  legacy_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slug (slug),
  KEY idx_list (publish, jenis, tgl),
  KEY idx_program (publish, id_sub_program, tgl),
  CONSTRAINT fk_post_subprogram FOREIGN KEY (id_sub_program) REFERENCES sub_program(id_sub_program)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 003_create_supporting.sql
CREATE TABLE setting (
  kunci VARCHAR(60) PRIMARY KEY,
  nilai TEXT NULL,
  grup VARCHAR(30) NOT NULL DEFAULT 'umum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- kunci: site_name, tagline, alamat, email, telepon, whatsapp, instagram, facebook, youtube,
-- tiktok, maps_lat, maps_lng, maps_url, harga_emas_per_gram, nishab_bulanan, nilai_fitrah,
-- nilai_fidyah, jam_layanan, ga4_id

CREATE TABLE halaman (                 -- Profil, Visi Misi, Struktur, Kebijakan, S&K, FAQ, dll.
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grup ENUM('tentang','ketentuan','layanan','lainnya') NOT NULL DEFAULT 'lainnya',
  judul VARCHAR(150) NOT NULL,
  slug VARCHAR(170) NOT NULL UNIQUE,
  konten MEDIUMTEXT NOT NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  publish ENUM('Y','N') NOT NULL DEFAULT 'Y',
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE testimoni (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  profesi VARCHAR(100) NULL,
  foto VARCHAR(255) NULL,
  isi TEXT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  publish ENUM('Y','N') NOT NULL DEFAULT 'N',
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE layanan (                 -- kartu "Tunaikan Zakat": ZIS Online, Transfer, Jemput Zakat, Zakat Fitrah
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(100) NOT NULL,
  deskripsi VARCHAR(200) NULL,
  ikon VARCHAR(255) NULL,
  link VARCHAR(255) NOT NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  publish ENUM('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE amil (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  jabatan VARCHAR(100) NOT NULL,
  bidang ENUM('pimpinan','pelaksana') NOT NULL DEFAULT 'pelaksana',
  foto VARCHAR(255) NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  publish ENUM('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE galeri (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis ENUM('foto','poster','infografis') NOT NULL DEFAULT 'foto',
  judul VARCHAR(150) NOT NULL,
  foto VARCHAR(255) NOT NULL,
  foto_w SMALLINT UNSIGNED NULL,
  foto_h SMALLINT UNSIGNED NULL,
  tgl DATE NOT NULL,
  publish ENUM('Y','N') NOT NULL DEFAULT 'Y',
  KEY idx_galeri (publish, jenis, tgl)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE redirect (                -- 301 dari URL lama
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dari VARCHAR(255) NOT NULL UNIQUE,
  ke VARCHAR(255) NOT NULL,
  hit INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tambahan indeks untuk tabel lama yang tetap dipakai
ALTER TABLE video ADD youtube_id VARCHAR(20) NULL AFTER link, ADD KEY idx_video_tgl (tgl);
ALTER TABLE laporan_keuangan ADD tahun YEAR NULL, ADD tgl DATE NULL;
```

### 5.4 Script konsolidasi `database/scripts/migrate_posts.php`
1. Ambil semua baris dari `artikel`, `berita`, `berkah_zakat`, `program`, `event` → insert ke `post` dengan `jenis` sesuai asal, `legacy_table`, `legacy_id`.
2. `program.id_sub_program` ikut dibawa.
3. **Deteksi duplikat** (slug sama atau judul sama di beberapa tabel, contoh kasus Z-ICE): simpan satu, sisanya dicatat ke `redirect`.
4. Isi `ringkasan` otomatis, isi `foto_w/foto_h` dari file gambar.
5. Ekstrak `youtube_id` dari `video.link` (format `watch?v=`, `youtu.be/`, buang `&t=`).
6. Laporan hasil: jumlah migrasi per jenis, duplikat, gambar hilang.

### 5.5 Pembersihan konten (manual, oleh admin — buat checklist)
- `deskripsi_zakat` Zakat Fitrah: hapus ajakan ke "BAZNAS Kota Semarang".
- `artikel` #25: perbaiki "BAZNAS Bulukumba" dan "Z-Chicken".
- Perbarui angka nishab (2021) → ambil dari `setting` agar tidak hardcode.
- Typo: "Sumesl" → "Sumsel", "Kemenang" → "Kemenag", "akernas" → "Rakernas".
- Data testing di `mustahik` / `pembayaran` (mis. "KARTU TESTING", "SASAASSASA") → nonaktifkan/hapus.

### 5.6 Data yang TIDAK boleh tampil di publik
`mustahik`, `pengambilan_beras`, `sisa_beras`, `pembayaran` (detail muzakki), `konfirmasi_pembayaran`, `user`, `log`. Model publik tidak boleh punya akses query ke tabel ini.

---

## 6. Peta Halaman & Routing

| URL | Halaman | Sumber data |
|---|---|---|
| `/` | Beranda | HomeService (cache) |
| `/program` | 5 pilar program | `kategori_program` |
| `/program/{pilar-slug}` | Daftar kegiatan per pilar | `post` jenis program via `sub_program` |
| `/berita` | Kabar (filter tab: Berita, Artikel, Berkah Zakat, Event) | `post` |
| `/berita/{slug}` | Detail post | `post` (+ terkait 3 item) |
| `/zakat` | Penjelasan jenis zakat | `deskripsi_zakat` + `jenis_zakat` |
| `/zakat/{slug}` | Detail jenis zakat + CTA bayar | `deskripsi_zakat` |
| `/kalkulator-zakat` | Kalkulator penghasilan, emas, perdagangan, fidyah | `setting` (nishab, harga emas, fidyah) |
| `/bayar-zakat` | Form pembayaran (multi-step) | `jenis_zakat`, `rekening` |
| `/bayar-zakat/{kode}` | Instruksi transfer + status | `pembayaran`, `rekening` |
| `/konfirmasi` | Upload bukti transfer | `konfirmasi_pembayaran` |
| `/rekening` | Daftar rekening resmi | `rekening` |
| `/regulasi` | Regulasi per kategori (filter) | `perundangan`, `kategori_perundangan` |
| `/laporan` | Laporan keuangan (PDF) | `laporan_keuangan` |
| `/video` | Galeri video | `video` |
| `/galeri` | Foto & poster | `galeri` |
| `/tentang/{slug}` | Profil, Visi Misi, Struktur, dll. | `halaman` grup tentang |
| `/amil` | Amil BAZNAS | `amil` |
| `/testimoni` | Semua testimoni | `testimoni` |
| `/halaman/{slug}` | Kebijakan, S&K, FAQ | `halaman` |
| `/cari?q=` | Pencarian post | `post` (LIKE pada judul; FULLTEXT bila perlu) |
| `/sitemap.xml`, `/robots.txt` | SEO | dinamis |
| `/admin/*` | Panel admin | — |

URL lama → cek tabel `redirect` sebelum 404 (redirect 301).

---

## 7. Layout Beranda (Mobile-first)

Urutan section dari atas ke bawah (desktop memakai urutan yang sama dengan grid lebih lebar):

| # | Section | Isi & aturan | Data / query |
|---|---|---|---|
| H | **Header** | Mobile: bar 56px sticky — logo + nama singkat "BAZNAS Basel" + ikon cari. Desktop (≥lg): logo + nama + tagline, nav dropdown (Tentang, Layanan, Program, Kabar, Regulasi), tombol "Bayar Zakat" & "Konfirmasi" | `setting`, menu statis di config |
| 1 | **Hero banner** | Carousel scroll-snap, autoplay 6 dtk (berhenti saat disentuh/hover, hormati `prefers-reduced-motion`). Slide pertama = LCP: `fetchpriority="high"`, tanpa lazy. `<picture>` dengan `foto_mobile` (rasio 4:3) & `foto` (rasio 16:6) | `banner` publish ORDER BY urutan LIMIT 5 |
| 2 | **Aksi cepat** (tambahan, khas mobile) | Grid 4 ikon: Bayar Zakat, Kalkulator, Konfirmasi, Rekening | statis |
| 3 | **Tunaikan Zakat (Layanan)** | Judul + subjudul, carousel kartu layanan (mobile 1,2 kartu terlihat → mengundang geser; desktop 4 kolom) | `layanan` |
| 4 | **Program Kami** | 5 pilar: mobile = grid 2 kolom + 1 (atau scroll horizontal ikon), desktop = 5 kartu. Tanpa efek flip (berat & tidak ramah sentuh); pakai kartu ikon + label + deskripsi singkat | `kategori_program` |
| 5 | **Info BAZNAS (ticker)** | Satu baris teks berganti (fade, CSS + JS kecil), ikon info | `post` is_featured/terbaru LIMIT 5 |
| 6 | **Kabar BAZNAS** | Tab chip (Semua · Berita · Artikel · Berkah Zakat), carousel kartu: gambar 16:9, judul 2 baris, tanggal format Indonesia. Tombol "Lihat semua" | `post` LIMIT 8 |
| 7 | **Video** | Kartu video dengan **facade** (thumbnail + tombol play; iframe `youtube-nocookie.com` dimuat hanya saat diklik) | `video` LIMIT 6 |
| 8 | **Penjelasan Zakat** (konten khas Basel) | Chip/kartu jenis zakat → `/zakat/{slug}` + CTA kalkulator | `jenis_zakat` + `deskripsi_zakat` |
| 9 | **Testimoni** | Carousel kartu foto bulat + nama + profesi + kutipan (3 baris, expandable) + bintang | `testimoni` publish LIMIT 8 |
| 10 | **Mitra** | Strip logo grayscale → berwarna saat hover, scroll horizontal (tanpa marquee JS) | `mitra` |
| F | **Footer** | Logo + alamat + email + telepon + WA; Tautan (Kebijakan, S&K, FAQ, Regulasi, Laporan); Rekening resmi ringkas; Lokasi = gambar peta statis/link Google Maps (**bukan** Google Maps JS API); media sosial; copyright. Di mobile footer diberi `padding-bottom` setinggi bottom nav | `setting`, `halaman` grup ketentuan |
| N | **Bottom navigation** | Lihat §8 | statis |

Semua data beranda diambil oleh `HomeService::get()` dalam **satu fungsi ber-cache** (TTL 10 menit, dihapus otomatis saat admin menyimpan konten terkait).

---

## 8. Spesifikasi Bottom Navigation

- Tampil di layar < 1024px (`lg:hidden`), `position: fixed; bottom: 0`, tinggi 64px + `env(safe-area-inset-bottom)`.
- 5 item, ikon SVG inline (bukan icon font) + label 11–12px:

| Posisi | Item | Tujuan |
|---|---|---|
| 1 | Beranda | `/` |
| 2 | Program | `/program` |
| 3 | **Bayar Zakat** (tombol tengah menonjol, lingkaran warna primer, sedikit naik) | `/bayar-zakat` |
| 4 | Kabar | `/berita` |
| 5 | Menu | membuka **bottom sheet** berisi semua tautan: Kalkulator, Konfirmasi, Rekening, Zakat, Regulasi, Laporan, Video, Galeri, Tentang (Profil, Visi Misi, Struktur, Amil), Kontak |

- Item aktif: warna primer + `aria-current="page"`; ditentukan di server dari route aktif (tanpa JS).
- Area sentuh minimal 48×48px; kontras warna lulus WCAG AA.
- Bottom sheet: `<dialog>` native atau `div[role=dialog]` dengan focus trap, tutup dengan tombol, swipe-down sederhana, atau tap backdrop; `body` tidak bisa scroll saat sheet terbuka.
- Tidak memakai efek "sembunyi saat scroll" di Fase 1 (menghindari layout jank); bisa dievaluasi nanti.
- `<main>` diberi `pb-[calc(64px+env(safe-area-inset-bottom))] lg:pb-0`.

---

## 9. Strategi Kecepatan

### 9.1 CSS & Font
- Tailwind v4 build: `./tailwindcss -i resources/css/app.css -o public/assets/css/app.css --minify`.
  `@source "../../app/Views";` agar class terdeteksi.
- Satu file CSS, di-link biasa di `<head>` (kecil, cukup tanpa critical CSS terpisah). Cache-busting: `app.css?v={filemtime}`.
- Font: 1 family (usulan **Plus Jakarta Sans** atau **Inter**) — subset Latin, WOFF2, weight 400 & 700, `font-display: swap`, `<link rel="preload">` untuk weight 400. Alternatif paling cepat: system font stack.

### 9.2 Gambar (dampak terbesar)
- Saat upload: re-encode → simpan original terkompres + varian WebP `-480`, `-800`, `-1200`.
- Script `generate_image_variants.php` untuk semua gambar lama.
- Helper `img()` menghasilkan `<img srcset sizes width height loading="lazy" decoding="async" alt>`.
- Hero slide pertama: `loading="eager" fetchpriority="high"` + `<link rel="preload" as="image" imagesrcset=...>`.
- Batas ukuran upload admin 5 MB; dimensi maksimal disimpan 1600px.

### 9.3 JavaScript
- Situs publik tanpa jQuery. Modul: `carousel.js` (scroll-snap + autoplay + dot), `lite-yt.js` (video facade), `menu-sheet.js`, `tabs.js`, `calculator.js` (hanya dimuat di halaman kalkulator/bayar).
- Semua `defer`/`type="module"`, tidak ada inline script besar, tidak ada script pihak ketiga yang memblokir render.
- GA4 (bila dipakai): dimuat setelah `load`/interaksi pertama.

### 9.4 Server & Cache
- **Fragment cache** file-based untuk data beranda, menu, setting, sitemap.
- **Header HTTP** (`.htaccess`):
  - Aset statis (`/assets`, `/uploads`): `Cache-Control: public, max-age=31536000, immutable`.
  - HTML: `Cache-Control: no-cache` + `ETag`/`Last-Modified` sederhana.
  - Kompresi `mod_deflate` (gzip) untuk HTML/CSS/JS/SVG/JSON.
- OPcache on, `display_errors=Off` di production, log ke `storage/logs`.
- Query: hanya ambil kolom yang dipakai, selalu `LIMIT`, indeks sesuai §5.3, hindari N+1 (JOIN sub_program/kategori).

### 9.5 Embed pihak ketiga
- YouTube: facade (thumbnail `i.ytimg.com/vi/{id}/hqdefault.jpg`, `loading="lazy"`).
- Peta: gambar statis + tautan "Buka di Google Maps", atau iframe `loading="lazy"` di halaman kontak saja.
- Tidak memakai Google Maps JS API, Font Awesome penuh, atau library animasi.

---

## 10. Keamanan

- Semua query PDO prepared statement; tidak ada konkatenasi input ke SQL.
- Output selalu lewat `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`). Konten HTML dari editor admin disanitasi (whitelist tag) sebelum disimpan — gunakan HTMLPurifier atau sanitizer sederhana berbasis `DOMDocument`.
- CSRF token di semua form POST (publik & admin).
- Admin: `password_hash` (argon2id/bcrypt), **reset password admin lama (saat ini MD5 "admin")**, rate limit login (5x/15 menit), regenerate session, timeout idle 30 menit, URL admin tidak ditautkan di publik.
- Upload: validasi MIME via `finfo`, ekstensi whitelist (jpg/png/webp/pdf), re-encode gambar, nama file acak, `public/uploads/.htaccess` mematikan eksekusi PHP.
- Form publik (bayar zakat, konfirmasi): rate limit per IP + honeypot field; kode pembayaran 8–10 karakter acak `random_bytes`, unik.
- Security headers:
  `Content-Security-Policy` (self + `i.ytimg.com`, `www.youtube-nocookie.com`, `www.google.com/maps` bila perlu), `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`, `Permissions-Policy`, HSTS (setelah HTTPS stabil).
- `.env`, `storage/`, `database/`, `vendor/` tidak bisa diakses dari web.
- File dump `.sql` berisi data pribadi tidak pernah masuk repo / folder publik (`.gitignore`).
- Pemantauan integritas: cek berkala file tema/template dari injeksi link spam (pelajaran dari situs referensi).

---

## 11. SEO & Aksesibilitas

- `<html lang="id">`, `og:locale` = `id_ID`.
- Meta per halaman: title (≤ 60 char), description (≤ 160 char), canonical, Open Graph + Twitter card (gambar 1200×630).
- JSON-LD: `Organization` (beranda), `NewsArticle` (detail post), `BreadcrumbList`.
- `sitemap.xml` dinamis (post, program, zakat, halaman) + `robots.txt`.
- Redirect 301 URL lama (tabel `redirect`).
- Heading berurutan (satu `h1` per halaman), `alt` wajib di form admin, fokus keyboard terlihat, kontras AA, `prefers-reduced-motion` dihormati di carousel/ticker.

---

## 12. Fitur Zakat (Alur Bayar)

1. **Pilih jenis** (kartu: Zakat Penghasilan, Zakat Fitrah, Fidyah, Infaq, Sedekah, Zakat Maal lainnya).
2. **Nominal**: input rupiah dengan format otomatis; untuk penghasilan/emas tersedia tombol "Hitung" (kalkulator inline); fidyah = jumlah hari × `nilai_fidyah`; fitrah = jumlah jiwa × `nilai_fitrah`.
3. **Data muzakki**: sapaan, nama, no HP (WA), email (opsional), NPWZ (opsional), checkbox "sembunyikan nama" (opsional, Fase 2).
4. **Ringkasan & kirim** → buat `pembayaran` status 0 + kode unik.
5. **Instruksi transfer**: daftar rekening dari `rekening` dengan tombol salin nomor, nominal + kode, tombol "Konfirmasi via WA" (template pesan) dan "Upload bukti".
6. **Konfirmasi**: form upload bukti → `konfirmasi_pembayaran` dengan `id_pembayaran`; admin memverifikasi → status 1.
7. Fase 2 (opsional): QRIS / payment gateway, notifikasi WA otomatis, kuitansi PDF.

Kalkulator zakat berjalan di sisi klien (JS) dengan parameter dari `setting` yang di-render ke `data-*` attribute; hasil tetap divalidasi ulang di server saat submit.

---

## 13. Design System

### 13.1 Warna (usulan — sesuaikan dengan logo resmi BAZNAS)
| Token | Nilai usulan | Pemakaian |
|---|---|---|
| `--color-primary` | `#0B6B3A` (hijau BAZNAS) | tombol utama, nav aktif, aksen judul |
| `--color-primary-dark` | `#084F2B` | hover, footer |
| `--color-accent` | `#F2B705` (emas) | badge, garis aksen, bintang |
| `--color-ink` | `#1F2937` | teks utama |
| `--color-muted` | `#6B7280` | teks sekunder |
| `--color-surface` | `#F7F8F6` | latar section selang-seling |
| `--color-white` | `#FFFFFF` | kartu |

Didefinisikan di `@theme` Tailwind v4 agar menjadi utility (`bg-primary`, `text-accent`, dst.). Kontras teks putih di atas primer harus ≥ 4,5:1.

### 13.2 Tipografi & spasi
- Base 16px, line-height 1,6. Skala: 12 · 14 · 16 · 18 · 20 · 24 · 30 (mobile h1 maks 28–30px).
- Container `max-w-6xl px-4`, jarak antar section `py-10 lg:py-16`.
- Radius kartu 16px, bayangan halus 1 level.

### 13.3 Komponen (dibuat sebagai partial PHP reusable)
`section-title` · `btn` (primary/outline/ghost, ukuran sm/md/lg) · `card-post` · `card-program` · `card-layanan` · `card-video` (facade) · `card-testimoni` · `carousel` (wrapper + dots + tombol) · `chip-tabs` · `badge` · `breadcrumb` · `pagination` · `empty-state` · `alert` · `bottom-nav` · `menu-sheet` · `form-field` (label, error, hint).

---

## 14. Roadmap Pengerjaan (per fase, untuk Claude Code)

> Setiap fase: kerjakan → uji di XAMPP → cek Lighthouse mobile (fase UI) → commit. Jangan lanjut sebelum Definition of Done terpenuhi.

### Fase 0 — Persiapan
- [ ] Backup DB + folder upload lama; import ke MySQL lokal XAMPP.
- [ ] Inventaris URL lama (untuk redirect) & lokasi folder gambar lama.
- [ ] Putuskan open questions di §16.
- **DoD:** DB lokal jalan, daftar URL lama tersedia.

### Fase 1 — Fondasi
- [ ] Struktur folder §3, `composer.json` (PSR-4 `App\`), `.env`, `.htaccess` (rewrite, deflate, cache header, security header).
- [ ] Core: Router, Database, View, Request/Response, Session, Csrf, Cache, helpers.
- [ ] Error handler 404/500 + logging.
- [ ] Tailwind standalone CLI + `@theme` design token + script build (`build-css.sh`).
- **DoD:** route `/` merender layout kosong; CSS ter-build < 10 KB.

### Fase 2 — Database
- [ ] Jalankan migrasi 001–003.
- [ ] Script `migrate_posts.php` + laporan duplikat; isi `redirect`.
- [ ] Seed `setting`, 5 pilar `kategori_program`, `layanan`, `halaman` (draft).
- [ ] Script varian gambar WebP untuk gambar lama.
- [ ] Reset password admin ke hash modern.
- **DoD:** semua konten lama muncul di `post`; tidak ada error charset; gambar varian tersedia.

### Fase 3 — Layout & komponen
- [ ] Layout `site.php`: header (mobile & desktop), bottom nav, menu sheet, footer, SEO meta partial.
- [ ] Semua komponen §13.3.
- **DoD:** layout responsif 360px–1440px, bottom nav aktif sesuai route, lulus cek aksesibilitas dasar.

### Fase 4 — Beranda
- [ ] HomeService + cache; seluruh section §7.
- [ ] Carousel scroll-snap, ticker, video facade.
- **DoD:** Lighthouse mobile ≥ 90, LCP < 2,5 dtk, CLS < 0,1 di halaman beranda.

### Fase 5 — Halaman konten
- [ ] Kabar (list + filter + detail + terkait), Program (pilar + list), Zakat (list + detail), Regulasi, Laporan, Video, Galeri, Tentang/Halaman, Amil, Testimoni, Pencarian.
- [ ] Sitemap, robots, JSON-LD, redirect 301.
- **DoD:** semua route §6 jalan, tidak ada 404 untuk URL lama yang terdaftar.

### Fase 6 — Zakat & Pembayaran
- [ ] Kalkulator, alur bayar multi-step, instruksi transfer, konfirmasi upload, rate limit + honeypot.
- **DoD:** alur end-to-end teruji (data uji dihapus setelahnya), validasi server berjalan.

### Fase 7 — Admin
- [ ] Login aman, dashboard ringkas (jumlah pembayaran menunggu, konfirmasi baru).
- [ ] CRUD: Post, Banner, Layanan, Program/Pilar, Testimoni, Halaman, Amil, Galeri, Video, Mitra, Rekening, Regulasi, Laporan, Setting; verifikasi Pembayaran & Konfirmasi.
- [ ] Upload gambar → varian WebP otomatis; simpan → `Cache::forget()` terkait.
- **DoD:** admin bisa mengelola semua section beranda tanpa menyentuh kode.

### Fase 8 — Hardening & Rilis
- [ ] Audit keamanan (§10), uji header, uji upload berbahaya.
- [ ] Uji performa di HP kelas menengah + jaringan 4G lambat.
- [ ] Deploy ke cPanel (document root → `public/`), aktifkan HTTPS + HSTS, OPcache.
- [ ] Monitoring: Google Search Console, cek 404 dari log.
- **DoD:** checklist §17 lulus semua.

---

## 15. Aturan untuk Claude Code (isi `CLAUDE.md`)

```
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
```

---

## 16. Keputusan Terbuka (perlu dijawab sebelum/saat Fase 0)

1. Warna & logo resmi yang dipakai (hijau BAZNAS standar atau identitas Basel khusus)?
2. Admin lama (yang sekarang) tetap dipakai sementara, atau langsung diganti admin baru di Fase 7?
3. Apakah document root cPanel bisa diarahkan ke `public/`?
4. Domain tetap sama? Daftar URL lama untuk redirect?
5. Perlu akun muzakki (Daftar/Login) seperti di Sumedang, atau cukup bayar tanpa akun? (Rekomendasi: tanpa akun di Fase 1.)
6. Pembayaran: hanya transfer manual, atau perlu QRIS/payment gateway?
7. Siapa yang menyiapkan konten baru: profil, visi misi, struktur, amil, testimoni, alamat & kontak, laporan keuangan?
8. Font: Plus Jakarta Sans / Inter / system font?

---

## 17. Checklist QA Sebelum Rilis

**Performa**
- [ ] Lighthouse mobile ≥ 90 (beranda, list berita, detail berita, bayar zakat)
- [ ] LCP < 2,5 dtk, CLS < 0,1, INP < 200 ms
- [ ] Semua gambar WebP + srcset + width/height
- [ ] Gzip & cache header aktif (cek via DevTools)

**Mobile & UI**
- [ ] Tampil baik di 360, 390, 768, 1024, 1280px
- [ ] Bottom nav tidak menutupi konten/footer, aman di iPhone (safe area)
- [ ] Carousel bisa digeser jari, autoplay berhenti saat disentuh
- [ ] Menu sheet bisa ditutup dengan tombol, backdrop, dan Esc

**Fungsional**
- [ ] Semua route §6 & redirect URL lama berjalan
- [ ] Alur bayar zakat + konfirmasi end-to-end
- [ ] Admin CRUD semua modul + cache ter-invalidate

**Keamanan**
- [ ] Password admin sudah hash modern, login rate-limited
- [ ] CSRF di semua form, upload PHP ditolak
- [ ] Security headers aktif, `display_errors` off
- [ ] Tidak ada data pribadi terekspos di publik / repo

**SEO**
- [ ] Title & meta unik, OG image, JSON-LD valid
- [ ] sitemap.xml terdaftar di Search Console
