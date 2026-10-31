# Fase 8 — Hardening & Rilis (29 Oktober 2026)

Panduan memindahkan situs ke hosting: **[DEPLOY.md](DEPLOY.md)**.

## Status Definition of Done (checklist §17)
Diuji di Apache 2.4 + PHP-FPM 8.4 mode produksi (`APP_ENV=production`, header dari `.htaccess` asli), kecuali disebut lain.

| Butir §17 | Status | Bukti |
|---|---|---|
| Lighthouse mobile ≥ 90 (4 halaman) | ✅ | Tabel di bawah, semua run ≥ 91 |
| LCP < 2,5 dtk, CLS < 0,1 | ✅ | LCP 1,3–2,4 dtk, CLS **0** di semua halaman |
| INP < 200 ms | ⏳ | Tidak bisa diukur di lab; TBT 0–345 ms (JS publik ±6 KB). Pastikan di Search Console → *Data Web Inti* setelah ±28 hari data pengunjung |
| Gambar WebP + srcset + width/height | ✅ | Helper `img()`, varian 240/480/800/1200 (Fase 2/4) |
| Gzip & cache header | ✅ | HTML/CSS/JS gzip; aset `max-age=31536000, immutable`; HTML `no-cache` + ETag → **304** |
| Tampil baik 360–1440px | ✅ | Fase 3–7 (360, 390, 768, 1024, 1280, 1440px, tanpa scroll horizontal) |
| Bottom nav aman (safe area) | ✅ | Fase 3 |
| Carousel geser jari, autoplay berhenti saat disentuh | ✅ | Fase 3–4 |
| Menu sheet tutup lewat tombol/backdrop/Esc | ✅ | Fase 3; diuji ulang di Chrome 360px |
| Semua route §6 & redirect URL lama | ✅ | `tests/http/uji_route.php` + 98 redirect + `/file/...` lama |
| Bayar zakat + konfirmasi end-to-end | ✅ | `tests/http/uji_bayar.php` (36 cek) |
| Admin CRUD semua modul + cache ter-invalidate | ✅ | `tests/http/uji_admin.php` (54 cek), 18 jenis konten |
| Password admin hash modern, login rate-limited | ✅ lokal / ⏳ produksi | bcrypt; di produksi saat deploy (DEPLOY.md B4) |
| CSRF semua form, upload PHP ditolak | ✅ | Ditolak aplikasi (finfo + re-encode) **dan** Apache: `.php`/`.phtml` di uploads → 403, `x.php.jpg` disajikan sebagai gambar, tidak dieksekusi |
| Security headers, `display_errors` off | ✅ | CSP, nosniff, X-Frame-Options, Referrer-Policy, Permissions-Policy, tanpa X-Powered-By; **0 pelanggaran CSP / error JS** di 23 halaman publik + 9 halaman admin (Chrome) |
| Tidak ada data pribadi terekspos | ✅ | `.env`, `app/`, `config/`, `vendor/`, `database/`, `storage/`, `composer.json`, `integritas.json` → 404 (dua tata letak: docroot `public/` & docroot akar) |
| Title & meta unik, OG, JSON-LD | ✅ | Fase 3/5; SEO Lighthouse **100** (mode produksi) |
| sitemap.xml di Search Console | ⏳ | Setelah deploy (DEPLOY.md F2) |

⏳ = hanya bisa diselesaikan setelah situs aktif di hosting.

### Lighthouse akhir (Apache, mode produksi, mobile 4G lambat tersimulasi)
| Halaman | Performa | Aksesibilitas · Best Practices · SEO | LCP | CLS | Berat · request |
|---|---|---|---|---|---|
| `/` (5 run) | 91–99, median **97** | 100 · 100 · 100 | 2,2–2,4 dtk | 0 | 384 KB · 17 |
| `/berita` | 95–98 | 100 · 100 · 100 | 2,3–2,4 dtk | 0 | 371 KB · 12 |
| `/berita/{slug}` | 91–99 | 100 · 100 · 100 | 2,0–2,1 dtk | 0 | 270 KB · 11 |
| `/bayar-zakat` | 94–100 | 100 · 100 · 100 | 1,3–1,4 dtk | 0 | 59 KB · 8 |

Variasi skor berasal dari TBT (beban Mac saat pengukuran), bukan dari halaman.

## Audit keamanan (§10) — temuan & perbaikan
| Temuan | Perbaikan |
|---|---|
| Isian tautan admin (banner, layanan) bisa berisi `javascript:` → XSS saat diklik pengunjung | `url()` hanya meneruskan `http(s)`, `mailto:`, `tel:`, path internal, `#…`; skema lain & `//host` → `#`. Validasi form: tautan wajib `/…`, `https://…`, `mailto:`, `tel:` |
| Cache berisi URL absolut (sitemap) terbawa saat APP_URL berubah (mis. http → https saat go-live) | Kunci cache disertai APP_URL |
| Teks ticker "Info BAZNAS" sesaat berkontras rendah (animasi pudar) | Ganti animasi geser-naik; item tersembunyi memakai `aria-hidden="true"` (bukan `""`) |
| Metode TRACE diterima Apache | Tidak bisa dimatikan dari `.htaccess` (diproses sebelum `.htaccess`) → catatan untuk hosting (`TraceEnable Off`). Risiko rendah: browser modern memblokir TRACE dari skrip, cookie `HttpOnly` |

Diperiksa, tidak perlu perubahan: semua query memakai parameter PDO (nama tabel/kolom hanya dari konfigurasi), output view di-escape, CSRF di semua POST, cookie sesi `Secure; HttpOnly; SameSite=Lax` di HTTPS, upload diencode ulang & nama acak, bukti transfer di luar `public/`, tidak ada rahasia/dump di repo, `/_komponen` hanya saat debug, halaman error produksi tanpa detail teknis.

## Fitur baru
| Fitur | Keterangan |
|---|---|
| **HTTPS & host kanonik** | `APP_FORCE_HTTPS=true`: `http://` dan `www.` → 301 ke APP_URL (query ikut); POST lewat http ditolak 403 |
| **HSTS** | `APP_HSTS=31536000` (hanya dikirim lewat HTTPS); nyalakan setelah HTTPS stabil |
| **Tautan Rusak (404)** | Setiap 404 dari pengunjung dicatat (alamat, jumlah, asal tautan, terakhir) — tanpa probe pemindai (`.php`, `.env`, `wp-…`), tanpa `/admin`, maks. 2.000 baris. `/admin/tautan-rusak` (tidak di menu) → **Buat pengalihan** |
| **Pengalihan URL** | Admin → *Pengalihan URL*: CRUD tabel `redirect`; alamat lama boleh ditempel lengkap (`https://baznasbasel.id/…?x`) → dinormalkan; tolak duplikat, `/admin`, tujuan = asal, tujuan `javascript:`; catatan 404 terkait otomatis hilang |
| **Status Sistem** | `/admin/status` (tidak di menu): mode & debug, HTTPS/HSTS, versi PHP, ekstensi, WebP, OPcache, batas unggah, memory, `display_errors`, folder bisa ditulis, blokir PHP di uploads, ruang disk, versi DB, migrasi tertunda, jumlah error 7 hari, tautan rusak |
| `database/scripts/rawat.php` | Cron harian: hapus cache/penghitung/`.tmp` kedaluwarsa, log > 90 hari, catatan 404 > 180 hari (`--coba` = tampilkan saja) |
| `database/scripts/cek_integritas.php` | Cron harian: bandingkan hash kode dengan `integritas.json` paket rilis (BERUBAH/HILANG/BARU) + pindai uploads & bukti untuk skrip; diam bila bersih (email cron hanya saat ada temuan) |
| `build-rilis.sh` | Zip rilis (kode + vendor + CSS minified + `integritas.json`), `--uploads` untuk zip gambar; batal bila ada `.env`/`.sql`/log |
| `database/scripts/uji_rilis.php` | Uji asap pasca-deploy dari komputer mana pun (±40 cek) |
| `.env.production.example` | Contoh `.env` produksi |
| `tests/` | Rangkaian uji regresi dipindah ke proyek (sebelumnya di folder sementara) — lihat `tests/README.md` |

Migrasi baru: `008_tautan_rusak.sql` (tabel baru saja, aman untuk admin lama).

## Hasil uji
- `tests/`: test_core, test_fase2, uji_route, uji_bayar (36), uji_admin (54), uji_fase8 (36) — **semua lulus**; data lokal kembali seperti semula.
- `uji_rilis.php` di Apache produksi: **LULUS 0 gagal** (docroot `public/` maupun docroot akar proyek).
- Chrome: 0 pelanggaran CSP / error JS (publik 360px, admin 1280px termasuk editor).
- Integritas: file `.php`/`.phtml` & WebP berisi `<?php` di uploads terdeteksi; perubahan 1 baris kode terdeteksi.

## Yang masih perlu dilakukan (oleh pengelola)
1. **Deploy** mengikuti [DEPLOY.md](DEPLOY.md) (butuh akses cPanel).
2. Uji di HP sungguhan (Android kelas menengah, data seluler) setelah situs aktif.
3. Setelah 1 minggu: `APP_HSTS=31536000`, daftarkan sitemap di Search Console, pantau *Tautan Rusak* tiap minggu.
4. Rapikan data (dari fase sebelumnya): nama bank "Sumesl" di rekening, "BRI Syariah" → BSI, "KEMENANG", banner dengan tautan alamat lama, teks nishab 2021 di penjelasan zakat.
5. XAMPP lokal masih PHP 8.0 (Apache) — tidak memengaruhi produksi; pengembangan memakai `composer serve` (PHP 8.4).
