# Fase 0 — Persiapan (laporan, 28 Oktober 2026)

## Status Definition of Done
| Syarat | Status |
|---|---|
| DB lokal jalan | ✅ `baznasba_baznas` di XAMPP (28 tabel, jumlah baris sama dengan dump produksi 26 Okt 2026) |
| Daftar URL lama tersedia | ✅ `url-lama.csv` (100 URL + usulan tujuan baru) |
| Backup DB + upload lama | ✅ DB. ⚠️ Upload: hanya file konten publik (lihat §2) — backup penuh folder dari cPanel tetap perlu |
| Keputusan terbuka §16 | ⏳ 2 dari 8 diputuskan (lihat §7) |

## 1. Backup (di luar proyek, tidak masuk git)
```
~/Backups/baznas-basel/
├── db/2026-10-26_prod_baznasba_baznas.sql   # dump phpMyAdmin dari server (MariaDB 10.6)
├── db/2026-10-28_lokal_baznas_basel.sql     # salinan DB lokal lama sebelum Fase 0
└── uploads-lama/{tabel}/...                 # 137 file konten publik, 61 MB
```
Dump asli juga masih ada di `~/Downloads/baznasba_baznas.sql`.

## 2. File upload lama
- Lokasi di server lama: `/file/{nama_tabel}/{file}` (logo situs: `/images/{file}`).
- 137 file publik (banner, berita, artikel, berkah_zakat, program, deskripsi_zakat, mitra, rekening, perundangan, logo) — **semua ada**, tidak ada yang hilang. Rincian: `file-lama.csv`.
- 97 gambar: rata-rata **389 KB**, 39 gambar > 500 KB, 20 gambar lebar > 1600px (terbesar: banner 2312×1000, 1,7 MB). Varian WebP di Fase 2 akan berdampak besar.
- **Belum dibackup (sengaja):** `bukti_pembayaran` dan foto user — berisi data pribadi. Ambil lewat backup penuh cPanel (File Manager → compress folder `file/` → download), simpan di `~/Backups/baznas-basel/`, bukan di proyek.

## 3. URL lama → URL baru
Situs lama tidak punya `sitemap.xml` dan `robots.txt` (keduanya 404). Daftar disusun dari crawl + slug di database.

| Pola lama | Usulan baru |
|---|---|
| `/home/kabar/{berita,artikel,berkah_zakat,event}/detail/{slug}` | `/berita/{slug}` |
| `/home/program/detail/{slug}` | `/berita/{slug}` |
| `/home/kabar/{jenis}` | `/berita?jenis={jenis}` |
| `/home/program/{sub-program}` | `/program/{pilar}` (via `sub_program.id_kategori_program`) |
| `/home/zakat/{id_jenis_zakat}` | `/zakat/{slug deskripsi_zakat}` |
| `/home/detail_perundangan/{id_kategori}` | `/regulasi?kategori={id}` |
| `/home/pembayaran/zakat` · `/konfirmasi` · `/keterangan?kode=X` | `/bayar-zakat` · `/konfirmasi` · `/bayar-zakat/X` |
| `/home/profil` · `/home/perundangan` · `/home/laporan_keuangan` | `/tentang/profil` · `/regulasi` · `/laporan` |
| `/home/layanan/rekening` · `/home/layanan/kalkulator` | `/rekening` · `/kalkulator-zakat` |

**Perlu keputusan** (halaman lama yang belum ada di peta route §6):
- `/home/hubungi_kami` → usulan `/halaman/kontak`
- `/home/mitra` → usulan section mitra di beranda, atau halaman `/mitra`
- `/home/layanan/kurban` → layanan kurban belum ada di rencana

## 4. Konten ganda (untuk `migrate_posts.php`)
`duplikat.csv`: **11 pasang** konten yang sama persis (slug & judul) antara `berita`/`artikel` dan `program` — mis. `berita#49` = `program#53`. Saat konsolidasi, simpan satu (usulan: versi `program` agar tetap terhubung ke pilar) dan catat yang lain ke `redirect`.

Kasus **Z-ICE** tidak terdeteksi otomatis (judul berbeda): `artikel#24`, `artikel#25`, `program#77` — cek manual.

## 5. Checklist pembersihan konten (§5.5) — oleh admin
- [ ] `deskripsi_zakat#9` (Zakat Fitrah): hapus ajakan ke "BAZNAS Kota Semarang"
- [ ] `artikel#25`: perbaiki "BAZNAS Bulukumba" dan "Z-Chicken"
- [ ] `artikel#23`: "Kemenang" → "Kemenag"
- [ ] `artikel#32`: judul "akernas" → "Rakernas"
- [ ] "Sumesl" → "Sumsel": ada di `rekening#3` dan `rekening#4` (nama bank; ditemukan di Fase 3 — Fase 0 hanya memeriksa tabel konten). Tampil di footer setiap halaman
- [ ] `rekening#5`, `rekening#6` "Bank BRI Syariah": bank ini sudah bergabung ke BSI sejak 2021 — pastikan nomor rekening masih aktif & nama bank diperbarui
- [ ] Angka nishab 2021 di `deskripsi_zakat` → nanti diambil dari `setting`
- [ ] Data uji: `pembayaran#36`, `mustahik` dengan card_number `20812195163` & `2147483647` → nonaktifkan/hapus
- [ ] Typo data master: `jenis_zakat#11` "Zakat Perusahan" → "Zakat Perusahaan"
- [ ] `mitra#12` "KEMENANG" → "Kemenag" (jadi teks alt logo di beranda) — ditemukan Fase 4
- [ ] Banner beranda masih memuat URL lama (`/home/pembayaran/zakat`, `/home/layanan/rekening`) & rekening BRI — perbarui desain banner — ditemukan Fase 4

## 6. Temuan penting
- 🔴 **Password admin di situs yang sedang tayang = MD5("admin")** (terkonfirmasi di dump). Siapa pun bisa login. **Ganti password admin di baznasbasel.id sekarang**, tidak perlu menunggu Fase 2.
- 🟠 `pengambilan_beras.card_number`: **ke-108 baris** bernilai 2147483647 (overflow INT) — nomor kartu asli sudah hilang di DB; perlu dicocokkan ulang dari sumber lain bila dibutuhkan.
- Situs lama memuat Bootstrap, Font Awesome penuh, Owl Carousel, animate.css, dan Google Fonts Poppins (6 weight) — sumber utama lambatnya situs lama.

## 7. Keputusan terbuka (§16)
| # | Pertanyaan | Status |
|---|---|---|
| — | Versi PHP | ✅ **PHP 8.2** |
| — | Nama folder | ✅ **`baznas-basel`** |
| 1 | Warna & logo | ⏳ usulan: hijau BAZNAS standar (§13.1) |
| 2 | Admin lama tetap jalan? | ⏳ usulan: ya, sampai Fase 7 |
| 3 | Document root cPanel → `public/`? | ⏳ cek di cPanel → Domains |
| 4 | Domain | ⏳ asumsi tetap `baznasbasel.id` |
| 5 | Akun muzakki | ⏳ usulan: tanpa akun di Fase 1 |
| 6 | Pembayaran | ⏳ usulan: transfer manual dulu, QRIS Fase 2 |
| 7 | Penyedia konten baru | ⏳ |
| 8 | Font | ⏳ usulan: Plus Jakarta Sans (400/700) atau system font |

## 8. Lingkungan dev
- ⚠️ **Apache XAMPP masih PHP 8.0.28** — harus diganti XAMPP versi PHP 8.2 sebelum Fase 1 diuji di browser. Sebelum reinstall: backup seluruh `htdocs/` dan export semua database (ada ±30 DB proyek lain di XAMPP ini).
- PHP CLI: 8.4.7 (`/usr/local/bin/php`) — dipakai untuk skrip; kode tetap ditulis kompatibel 8.2.
- MariaDB lokal 10.4.28 (produksi 10.6.28) — dump terimpor tanpa error.
- Tabel sistem `mysql.proc` XAMPP perlu `mysql_upgrade` (mysqldump `--routines` gagal); tidak memengaruhi proyek.
- Tailwind CLI v4.3.3 di `bin/tailwindcss` (checksum SHA-256 terverifikasi dari rilis GitHub).

## Menjalankan ulang inventaris
```sh
php database/scripts/inventaris_legacy.php           # hanya CSV
php database/scripts/inventaris_legacy.php --unduh   # + unduh file yang belum ada ke ~/Backups
```
