# Fase 5 — Halaman konten (28 Oktober 2026)

## Status Definition of Done
| Syarat | Status |
|---|---|
| Semua route §6 jalan | ✅ untuk semua route konten (40 pengujian status). ⏳ `/kalkulator-zakat`, `/bayar-zakat`, `/bayar-zakat/{kode}`, `/konfirmasi` = **Fase 6** (sesuai §14) |
| Tidak ada 404 untuk URL lama terdaftar | ✅ **96** URL lama → 301 → halaman 200. **5** URL lama → 301 → route Fase 6 (tujuan benar, halamannya belum dibuat) |

Uji lain: axe-core **0 pelanggaran** di 17 halaman × (360px, 1280px); tanpa scroll horizontal di 360–1440px; regresi Fase 1–2 lulus.

Lighthouse mobile (3 run per halaman, server uji meniru gzip Apache):

| Halaman | Performance | LCP | CLS | Berat |
|---|---|---|---|---|
| `/berita` | 93 – 96 | 2,3 – 2,4 s | 0 | 360 KB |
| `/berita/{slug}` | 99 – 100 | 1,8 – 1,9 s | 0 | 303 KB |
| `/program/basel-peduli` | 97 – 98 | 1,6 – 1,7 s | 0 | 180 KB |
| `/zakat/zakat-penghasilan` | 89 – 100 | 1,2 – 1,8 s | 0 | 47 KB |
| `/regulasi` | 94 – 99 | 1,0 – 1,1 s | 0 | 38 KB |

Accessibility & Best Practices 100 di semua halaman. **SEO 69 di lokal itu disengaja**: selain `APP_ENV=production`, `robots.txt` berisi `Disallow: /` (agar server dev/staging tidak terindeks) — satu-satunya audit SEO yang gagal adalah `is-crawlable`.

## Route
| URL | Controller | Catatan |
|---|---|---|
| `/berita` | PostController@index | Filter `?jenis=berita\|artikel\|berkah_zakat\|event`, `?hal=N` (12/halaman), kotak cari |
| `/berita/{slug}` | PostController@show | JSON-LD NewsArticle + Breadcrumb, og:type article, tautan pilar, 3 kabar terkait, bagikan WA/FB/X/salin tautan, `views` +1 |
| `/program` | ProgramController@index | 5 pilar + 6 kegiatan terbaru |
| `/program/{pilar}` | ProgramController@pilar | Filter `?sub={sub-program}`; memfilter `post.id_sub_program` |
| `/zakat`, `/zakat/{slug}` | ZakatController | Konten `deskripsi_zakat` disanitasi + di-cache (`ZakatService`); CTA `/bayar-zakat?jenis={id_jenis_zakat}` |
| `/rekening` | PageController@rekening | Peringatan penipuan, tombol salin nomor (angka saja) |
| `/regulasi` | RegulasiController@index | Filter `?kategori=`; ukuran PDF ditampilkan |
| `/laporan` | RegulasiController@laporan | Tabel masih kosong → empty state |
| `/video`, `/galeri` | MediaController | Galeri masih kosong → empty state |
| `/tentang`, `/tentang/{slug}`, `/halaman/{slug}` | PageController | Grup `tentang` hanya di `/tentang/…`, lainnya di `/halaman/…` (301 silang); draf = 404 |
| `/amil`, `/testimoni` | PageController | Tabel masih kosong → empty state |
| `/cari?q=` | CariController | Min. 3 karakter; judul & ringkasan post + jenis zakat; `noindex` |
| `/sitemap.xml`, `/robots.txt` | SeoController | Sitemap 79 URL (cache 1 jam) |

## Redirect URL lama (`RedirectService`, dipasang sebagai `Router::fallback`)
- Dipanggil hanya sebelum 404 — termasuk saat controller melempar 404 (mis. slug lama yang berubah).
- Sumber: tabel `redirect` (98 baris) + aturan dinamis:
  - `/home/pembayaran/keterangan?kode=X` → `/bayar-zakat/X`
  - `/file/{tabel}/{file}` (gambar/PDF lama) → `/uploads/{folder}/{file}` bila file ada
- Query lama ikut dibawa bila tujuan belum punya query (`?nominal=`, `?kode=`).
- Kolom `hit` bertambah setiap redirect — pantau URL lama yang masih ramai.
- **Migrasi 006** menetapkan keputusan yang tertunda: `/home/hubungi_kami` → `/#kontak`, `/home/mitra` → `/#mitra`, `/home/layanan/kurban` → `/halaman/kurban` (halaman baru berisi ajakan kurban + WhatsApp pendaftaran 0821-8441-4795, seperti situs lama).

## Komponen & pendukung baru
- `components/page-header` (breadcrumb + h1 + subjudul), `components/grid-post` (grid kartu; kartu ke-4 dst. `cv-kartu`).
- `.konten` — tipografi isi artikel/halaman (h2–h4, daftar, tabel bergulir, kutipan, gambar, iframe 16:9).
- Helper `ringkas()` (ringkasan/meta description), `ukuran_file()`.
- `app.js`: tombol `[data-salin]` (Clipboard API) + pengumuman `aria-live` (`#pengumuman` di layout).
- Model: `SubProgram`, `Perundangan`, `LaporanKeuangan`, `Galeri`, `Amil`, `Redirect`; `Post` & `Video` diperluas.
- Service: `RedirectService`, `ZakatService` (cache `zakat` — panggil `forget()` saat admin ubah penjelasan zakat), `SitemapService` (cache `sitemap`).

## Temuan konten
- Konten `deskripsi_zakat` masih menyebut **nishab tahun 2021** (Rp79.738.415/tahun) — perlu diperbarui admin (sudah ada di checklist §5 Fase 0).
- Tabel `laporan_keuangan`, `galeri`, `amil`, `testimoni` masih kosong — halamannya menampilkan empty state yang rapi.
- Kategori regulasi "Baznas Provinsi Kab/ Kota" belum punya dokumen.
