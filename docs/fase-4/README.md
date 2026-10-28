# Fase 4 — Beranda (28 Oktober 2026)

## Status Definition of Done
Diukur dengan Lighthouse 12.2.1, mode mobile (Moto G Power, 4G lambat tersimulasi, CPU 4×), 5 kali berturut-turut:

| Metrik | Hasil (5 run) | Target | Status |
|---|---|---|---|
| Performance | 75 · 92 · 98 · 97 · 98 (median **97**) | ≥ 90 | ✅ |
| LCP | 1,9 – 2,4 s | < 2,5 s | ✅ |
| CLS | **0** di semua run | < 0,1 | ✅ |
| TBT | 0 – 300 ms (840 ms pada run pertama) | — | |
| Accessibility · Best Practices · SEO | 100 · 100 · 100 | — | ✅ |
| Berat halaman | **405 KB** | < 1 MB | ✅ |
| Request | **18** | < 25 | ✅ |
| CSS / JS publik | 7 KB / ±6 KB gzip | < 30 KB | ✅ |

axe-core 4.10.2: **0 pelanggaran** di 360px & 1280px. Tanpa scroll horizontal di 360–1440px.

**Tentang variasi skor:** run pertama tiap batch selalu terendah. `benchmarkIndex` Lighthouse turun ke 50–900 saat Mac sedang sibuk (Firefox/Safari/VS Code; load average sempat 57), dan Lighthouse mengalikan waktu CPU teramati. Pada kondisi normal skor konsisten 92–99. Untuk cek ulang: Chrome DevTools → Lighthouse → Mobile → Performance, jalankan beberapa kali dan ambil median.

**Catatan lingkungan uji:** `php -S` (PHP 8.4) dengan router uji yang meniru Apache produksi (gzip HTML/CSS/JS, cache 1 tahun). Belum diuji lewat Apache XAMPP karena masih PHP 8.0.

## Section (§7)
| # | Section | Implementasi |
|---|---|---|
| 1 | Hero banner | Carousel scroll-snap, autoplay 6 dtk + tombol jeda; berhenti saat hover/fokus/sentuh, di luar layar, tab tersembunyi; mati bila `prefers-reduced-motion`. Slide 1 = LCP: `fetchpriority=high`, `<link rel=preload imagesrcset>`; slide lain `fetchpriority=low` |
| 2 | Aksi cepat | 4 ikon (HP saja): Bayar Zakat, Kalkulator, Konfirmasi, Rekening |
| 3 | Tunaikan Zakat | `layanan`: carousel di HP, 4 kolom di desktop |
| 4 | Program Kami | 5 pilar: 2+2+1 di HP, 5 kolom di desktop |
| 5 | Info BAZNAS | Ticker fade 5 dtk dari `post` (is_featured lalu terbaru) + tombol jeda |
| 6 | Kabar BAZNAS | Tab ARIA (Semua/Berita/Artikel/Berkah Zakat) tanpa reload; tab non-aktif di `<template>`; tanpa JS chip = link ke `/berita?jenis=…` |
| 7 | Video | Facade: thumbnail + tombol play; iframe `youtube-nocookie` dimuat saat diklik |
| 8 | Kenali Zakat | 10 jenis zakat → `/zakat/{slug}` + CTA kalkulator |
| 9 | Testimoni | Tampil otomatis bila ada data terbit (tabel masih kosong → section disembunyikan) |
| 10 | Mitra | Logo grayscale → berwarna saat hover; geser di HP, wrap di desktop |

Data: `App\Services\HomeService::get()` — satu cache `home` (10 menit). **Admin wajib memanggil `HomeService::forget()`** setelah menyimpan banner, layanan, post, video, testimoni, mitra, atau penjelasan zakat (Fase 7).

## Optimasi yang dilakukan (dan dampaknya)
| Langkah | Dampak |
|---|---|
| Varian WebP **240px** baru (`Image::WIDTHS = [240, 480, 800, 1200]`) | Logo mitra 380 KB → 159 KB; `img_srcset()` hanya memakai varian yang ada |
| `content-visibility: auto` (utility `cv-auto`) pada section di bawah layar | Berat awal 946 KB → **405 KB**, request 39 → **18**, Speed Index ±2,5 → ±1,2 s |
| Carousel dipasang saat mendekati layar + 1 ResizeObserver bersama (baca dulu, tulis kemudian) | Biaya `carousel.js` ±750 ms → 60–200 ms (tanpa layout paksa berulang) |
| Panel tab non-aktif di `<template>` | DOM 1.214 → **913** elemen |
| `html:has(dialog[open])` diganti class lewat JS | Mengurangi biaya perhitungan style |
| Module script beranda `fetchpriority=low` | Tidak berebut bandwidth dengan gambar LCP |
| Ikon PNG aplikasi 256 warna | 184 KB → 48 KB |
| Kontrol carousel `invisible` (ruang dipesan sejak server) | CLS tetap 0 saat JS aktif |

`cv-auto` sengaja **tidak** dipasang di footer: axe/Lighthouse salah membaca latar footer yang belum dirender (false positive kontras).

## Keputusan
- **Rasio hero 16:7** (bukan 16:6 / 4:3 seperti §7): semua banner yang ada berupa poster penuh teks ±2,3:1 — dipotong ke 4:3 akan memotong teks. Begitu **semua** banner punya `foto_mobile` (4:3), HP otomatis memakai rasio 4:3 lewat `<picture>`.
- **Tanpa h1 visual** di beranda; h1 `sr-only` berisi nama + tagline (satu h1 per halaman tetap terpenuhi).
- **Track carousel & daftar mitra bisa difokus** (`tabindex=0`) agar bisa digeser dengan keyboard walau isinya tanpa link (WCAG 2.1.1).

## Temuan konten (untuk admin)
- Banner masih menampilkan URL lama (`baznasbasel.id/home/pembayaran/zakat`, `/home/layanan/rekening`) dan rekening **BRI** (sudah BSI). Perlu desain ulang banner sebelum rilis — URL lama tetap dialihkan (Fase 5), tapi informasinya sebaiknya diperbarui.
- `mitra#12` "KEMENANG" → "Kemenag" (dipakai sebagai teks alt logo).
- Tabel `testimoni` kosong — section Testimoni muncul otomatis setelah ada data terbit.
