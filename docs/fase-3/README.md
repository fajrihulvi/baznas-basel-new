# Fase 3 — Layout & komponen (28 Oktober 2026)

## Status Definition of Done
| Syarat | Status |
|---|---|
| Layout responsif 360–1440px | ✅ Tanpa scroll horizontal di 360, 390, 768, 1024, 1440px (beranda, katalog komponen, 404). Dicek lewat Chrome headless dengan iframe selebar viewport asli (jendela Chrome desktop minimal ±500px, jadi screenshot biasa tidak valid untuk 360px) |
| Bottom nav aktif sesuai route | ✅ Ditentukan server dari path (`aria-current="page"`); diuji `/`, `/program/…`, `/bayar-zakat/…`, `/berita/…`; `/programku` tidak ikut aktif |
| Lulus cek aksesibilitas dasar | ✅ axe-core 4.10.2 (WCAG 2.0/2.1/2.2 A–AA + best practice): **0 pelanggaran** di beranda, katalog komponen, dan 404 pada 360px & 1280px |

Budget (§1): CSS 6,9 KB gzip · JS 1,2 KB gzip · HTML beranda 5,3 KB gzip · ±7 request awal · 0 font eksternal.

## Yang dibuat
**Layout** `app/Views/layouts/site.php`
- `partials/seo-meta` — title, description, canonical, Open Graph, Twitter card, `og:locale=id_ID`, JSON-LD (`$jsonLd`), `noindex`.
- `partials/header` — HP: bar 56px sticky (logo + "BAZNAS Basel" + cari). Desktop (≥ lg): logo + nama, 5 dropdown `<details>` (berfungsi tanpa JS), tombol Konfirmasi & Bayar Zakat.
- `partials/bottom-nav` — 5 item, tombol tengah Bayar Zakat menonjol, tinggi 64px + safe area, area sentuh ≥ 48px.
- `partials/menu-sheet` — `<dialog>` native: tutup lewat tombol, backdrop, Esc, atau geser ke bawah; body tidak ikut tergulir.
- `partials/footer` — kontak (tel/WA/email), tautan (halaman terbit grup ketentuan & layanan + Regulasi/Laporan/Kalkulator), 3 rekening resmi, tombol Google Maps (tanpa Maps API), media sosial. `id="kontak"` jadi tujuan menu "Kontak".

**Komponen** `app/Views/components/` (dipakai dengan `$this->insert('components/…', [...])`):
`button` · `section-title` · `badge` · `card-post` · `card-program` · `card-layanan` · `card-video` (facade) · `card-testimoni` · `carousel` · `chip-tabs` · `breadcrumb` (+ JSON-LD) · `pagination` · `empty-state` · `alert` · `form-field`.
Contoh pemakaian lengkap: `app/Views/site/komponen.php` → **http://localhost:8000/_komponen** (hanya saat `APP_DEBUG=true`).

**Pendukung**
- Helper: `img()` (srcset WebP 480/800/1200 + width/height + lazy; `eager` untuk gambar LCP), `icon()` (48 ikon SVG inline buatan sendiri di `app/Views/icons.php`), `setting()`, `current_path()`; `url()` kini meloloskan URL eksternal & `#anchor`.
- `App\Core\Paginator` (`?hal=N`, parameter lain dipertahankan; HP: "Halaman 5 dari 12").
- `App\Services\SiteService` — setting, pilar, rekening, halaman terbit dalam **satu** cache (`site`, 10 menit). Admin wajib memanggil `SiteService::forget()` setelah menyimpan data terkait (Fase 7).
- `App\Services\Navigation` + `config/menu.php` — menu statis + pilar/halaman dinamis + status aktif.
- Model: `Setting`, `KategoriProgram`, `Rekening`, `Halaman`, `Post`, `Video`, `Layanan`.
- Aset: logo Garuda (dipotong dari logo lama, WebP 5 KB), `favicon.ico`, `apple-touch-icon.png`, ikon manifest 192/512, `manifest.webmanifest`, `placeholder.svg`.
- `public/assets/js/app.js` — menu sheet, dropdown (tutup saat klik di luar / Esc), tombol "Selengkapnya" testimoni.

## Keputusan
- **Fokus keyboard**: outline hijau di latar terang, emas di latar gelap (`.on-dark`) — emas di atas putih hanya 1,8:1.
- **Pagination di HP** diringkas jadi "‹ Halaman X dari Y ›" (9 tombol tidak muat di 360px).
- **Menu "Tentang"** memakai halaman grup `tentang` yang terbit; halaman draf otomatis tidak muncul di menu/footer.
- **Carousel & video facade** sudah punya markup lengkap dan berfungsi tanpa JS (geser / link ke YouTube). Autoplay, titik navigasi, dan iframe saat diklik dikerjakan di Fase 4 sesuai §14.

## Catatan
- Tampilan di Apache XAMPP belum diuji (XAMPP masih PHP 8.0); semua uji memakai `composer serve` (PHP 8.4).
- Gambar OG default 1200×630 belum ada — `og:image` diisi per halaman mulai Fase 4/5.
- Temuan data baru (dicatat di `docs/fase-0/README.md` §5): typo "Sumesl" di `rekening#3–4` dan "BRI Syariah" (sudah menjadi BSI) di `rekening#5–6` — terlihat di footer.
