# Fase 7 — Panel Admin (28 Oktober 2026)

## Status Definition of Done
| Syarat | Status |
|---|---|
| Admin bisa mengelola semua section beranda tanpa menyentuh kode | ✅ Banner, Layanan, 5 Pilar, Info BAZNAS (kabar "disorot"), Kabar, Video, Penjelasan Zakat, Testimoni, Mitra, serta footer (Pengaturan, Rekening, Halaman) — semuanya lewat `/admin`; perubahan langsung tampil (cache dihapus otomatis) |

Uji end-to-end **54/54 lulus** (`uji_admin.php`, dijalankan 2×; seluruh data uji dihapus dan data kembali persis seperti semula). axe-core **0 pelanggaran** pada dasbor, daftar, form + editor, pembayaran, pengaturan, akun — di 1280px dan 360px, tanpa scroll horizontal. Uji regresi Fase 1–6 tetap lulus.

## ⚠️ Untuk mulai memakai
Akun `admin` lokal **masih memakai hash MD5 ("admin") dan sengaja ditolak**. Jalankan sekali:
```sh
php database/scripts/reset_admin_password.php
```
lalu buka **http://localhost:8000/admin/masuk** (alamat ini tidak ditautkan dari situs publik).

## Fitur
| Menu | Isi |
|---|---|
| **Dasbor** | Konfirmasi baru, pembayaran menunggu, nominal selesai bulan ini, jumlah kabar; antrean konfirmasi; peringatan bila setting zakat belum diisi; pintasan |
| **Pembayaran** | Filter (konfirmasi baru / menunggu / selesai / dibatalkan) + cari kode, nama, no. HP. Detail: data muzaki, tombol WhatsApp, semua konfirmasi + **bukti transfer** (gambar/PDF dari `storage/bukti`, hanya lewat sesi admin), **Verifikasi** (→ pembayaran selesai) / **Tolak**, ubah status manual |
| **16 jenis konten** | Kabar & Kegiatan, Banner, Video, Galeri, Testimoni, Mitra, Rekening, Penjelasan Zakat, Layanan Beranda, 5 Pilar (ubah saja), Sub-program, Halaman, Regulasi, Kategori Regulasi, Laporan Keuangan, Amil — daftar (cari, pagination, thumbnail, "Lihat di situs"), tambah, ubah, hapus |
| **Pengaturan** | Umum, kontak (WA dinormalkan ke format wa.me), media sosial, **zakat** (harga emas, nishab, fitrah, fidyah), GA4 |
| **Akun Saya** | Ganti password (min. 8 karakter, bcrypt) |

### Perilaku otomatis
- **Gambar**: nama acak, encode ulang, sisi terpanjang ≤ 1600 px, varian WebP 240/480/800/1200; ganti/hapus gambar → file lama + varian ikut terhapus. Maks 5 MB.
- **PDF**: cek isi `%PDF-`, nama acak. Maks 10 MB.
- **Isi HTML** (kabar, halaman, penjelasan zakat): editor sederhana (tebal, miring, H2/H3, daftar, kutipan, tautan, mode HTML); **disanitasi server** (`HtmlSanitizer`) — script, style, `on*`, `javascript:` dibuang.
- **Kabar**: slug otomatis & unik (`-2`, `-3`, …); ringkasan otomatis; **slug diubah → alamat lama dialihkan 301**; "Sorot" → muncul di ticker Info BAZNAS.
- **Video**: ID YouTube diambil otomatis dari tautan (watch, youtu.be, shorts).
- **Cache**: setiap simpan/hapus menghapus cache terkait (`home`, `site`, `zakat`, `sitemap`) — tidak perlu menunggu 10 menit.
- Data yang masih dipakai data lain (mis. sub-program dengan kegiatan lama) tidak bisa dihapus → pesan jelas.

## Keamanan (§10)
| Kontrol | Implementasi |
|---|---|
| Password | bcrypt cost 12 (`password_hash`); **hash MD5 ditolak** dengan arahan menjalankan skrip reset (pesan khusus hanya muncul bila password MD5-nya benar → tidak membocorkan username) |
| Brute force | 5 percobaan / 15 menit / IP (`RateLimiter`) — terkunci walau password benar |
| Timing | `password_verify` tetap dijalankan dengan hash dummy bila username tidak ada |
| Sesi | ID sesi diganti saat masuk & keluar, token CSRF diganti, **keluar otomatis setelah 30 menit tidak aktif** |
| Open redirect | `?ke=` hanya diterima untuk path `/admin/...` |
| CSRF | Semua POST admin (termasuk hapus & keluar) |
| Header | `Cache-Control: no-store`, `X-Robots-Tag: noindex`, `<meta robots noindex>`; `/admin` di-Disallow di robots.txt |
| Bukti transfer | Disajikan PHP setelah cek sesi + `realpath` di dalam `storage/bukti` (anti path traversal), `nosniff` |
| SQL | Nama tabel/kolom hanya dari `config/admin.php` dan dicek pola `^[a-z_][a-z0-9_]*$`; nilai selalu parameter PDO |

## Arsitektur
- `config/admin.php` — definisi menu & konten (field, aturan validasi, kolom daftar, folder unggahan, cache).
  **Menambah jenis konten baru = menambah satu entri di sini**, tanpa controller/view baru.
- `App\Admin\Resource` (akses data generik), `App\Admin\Hook` + `PostHook`, `VideoHook` (aturan khusus).
- `App\Controllers\Admin\ResourceController` (CRUD generik), `PembayaranController`, `SettingController`, `AkunController`, `AuthController`, `DashboardController`.
- `App\Core\Auth`, `App\Middleware\AuthAdmin`, `Upload::gambarPublik/pdfPublik/hapusPublik`.
- Tampilan: `layouts/admin.php`, `admin/*.php`; JS `public/assets/js/admin.js` (editor, konfirmasi hapus, pratinjau gambar, format rupiah).

## Catatan & keterbatasan
- **Admin lama vs baru**: admin baru menulis kabar ke tabel `post`; admin lama masih ke tabel lama (`berita`, `artikel`, …). Setelah admin baru dipakai, **pensiunkan admin lama**. Di produksi, saat itulah migrasi 005 + `reset_admin_password.php` dijalankan (lihat docs/fase-2).
- **Konfirmasi lama tanpa pembayaran**: 3 konfirmasi 2022 kodenya tidak cocok dengan pembayaran mana pun (mis. "WHXXQ" vs "WHHXQ") → di dasbor ada tombol **Arsipkan**.
- **Bukti transfer situs lama** (folder `file/konfirmasi_pembayaran/` di server lama) belum dipindahkan → tampil "file bukti tidak tersedia". Bila perlu, salin dari backup cPanel ke `storage/bukti/` dan sesuaikan kolom `bukti_pembayaran`.
- Editor belum punya tombol sisip gambar di tengah artikel (bisa lewat mode HTML dengan URL gambar). Foto utama kabar diunggah lewat field "Foto utama".
- Satu tingkat akses (semua admin bisa semua menu).
- Batas unggah server: `upload_max_filesize` ≥ 11M dan `post_max_size` ≥ 12M (PDF laporan hingga 10 MB). `composer serve` sudah memakai nilai ini — **jalankan ulang** `composer serve`.
