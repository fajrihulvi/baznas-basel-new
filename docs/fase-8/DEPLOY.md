# Panduan Deploy ke cPanel — baznasbasel.id

Untuk orang yang memindahkan situs baru ke hosting. Butuh: akses cPanel, PHP CLI + MySQL/MariaDB di komputer lokal (XAMPP), dan proyek ini.
Prinsip: **semua pengolahan data dikerjakan di komputer lokal**, server cukup menerima hasil jadi (tidak butuh Terminal/SSH di cPanel).
Situs & database lama **tidak diubah** sampai akhir, sehingga bisa kembali kapan saja (lihat [Rollback](#rollback)).

Perkiraan waktu: 2–3 jam. Pilih jam sepi (mis. malam hari kerja).

---

## A. Persiapan di cPanel (sekali)

1. **Backup penuh**: cPanel → *Backup* → *Download a Full Account Backup* (atau minimal: *Download a MySQL Database Backup* `baznasba_baznas` + folder `public_html`). Simpan di `~/Backups/baznas-basel/` — jangan di folder proyek.
2. **Versi PHP**: *MultiPHP Manager* (atau *Select PHP Version*) → domain baznasbasel.id → **PHP 8.2** atau lebih baru.
3. **Ekstensi PHP** (*Select PHP Version → Extensions*): `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `dom`, **`opcache`**.
4. **Pengaturan PHP** (*MultiPHP INI Editor* → baznasbasel.id):

   | Nama | Nilai |
   |---|---|
   | `upload_max_filesize` | `11M` |
   | `post_max_size` | `12M` |
   | `memory_limit` | `256M` |
   | `display_errors` | `Off` |
   | `expose_php` | `Off` (bila tersedia) |

5. **SSL**: *SSL/TLS Status* → pastikan baznasbasel.id & www.baznasbasel.id punya sertifikat (AutoSSL). Buka `https://baznasbasel.id` — harus bergembok.
6. **Database baru** (*MySQL Databases*): buat DB, mis. `baznasba_web`, dan user baru dengan password kuat → *Add User To Database* → **ALL PRIVILEGES**. DB lama `baznasba_baznas` dibiarkan untuk rollback.

## B. Siapkan data di komputer lokal (hari H)

> Mulai langkah ini, **admin lama berhenti dipakai** (kabar/pembayaran baru di situs lama setelah ekspor tidak akan ikut).

1. Ekspor DB produksi terbaru: cPanel → *phpMyAdmin* → `baznasba_baznas` → *Export* (Quick, SQL) → simpan ke `~/Backups/baznas-basel/db/`.
2. Unduh folder `public_html/file/` (*File Manager* → *Compress* → *Download*), ekstrak ke `~/Backups/baznas-basel/uploads-lama-HARIINI/`.
3. Buat DB lokal kosong `baznas_rilis` lalu impor dump tadi:
   ```sh
   /Applications/XAMPP/xamppfiles/bin/mysql -uroot -e "CREATE DATABASE baznas_rilis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   /Applications/XAMPP/xamppfiles/bin/mysql -uroot baznas_rilis < ~/Backups/baznas-basel/db/NAMA-DUMP.sql
   ```
4. Jalankan pengolahan (variabel `DB_NAME=` di depan perintah mengalahkan isi `.env`, jadi `.env` tidak perlu diubah):
   ```sh
   DB_NAME=baznas_rilis php database/scripts/migrate.php                     # 001–008
   php database/scripts/generate_image_variants.php --sumber=$HOME/Backups/baznas-basel/uploads-lama-HARIINI
   DB_NAME=baznas_rilis php database/scripts/migrate_posts.php
   DB_NAME=baznas_rilis php database/scripts/reset_admin_password.php        # password admin produksi (min. 8 karakter)
   ```
5. Isi pengaturan & konten yang wajib, lewat admin lokal yang terhubung ke `baznas_rilis`:
   ```sh
   DB_NAME=baznas_rilis composer serve      # lalu buka http://localhost:8000/admin/masuk
   ```
   - **Pengaturan → Zakat**: harga emas per gram, nishab bulanan, fitrah, fidyah. Lalu **Pengaturan → Umum/Kontak**: jam layanan, WhatsApp.
   - Halaman draf (Struktur, Kebijakan Privasi, S&K, FAQ) bila sudah siap.
   - Perubahan yang pernah dibuat di admin lokal pada DB `baznas_basel` **tidak ikut otomatis** — isi ulang di sini.
   - Dasbor tidak boleh lagi menampilkan "Pengaturan zakat belum lengkap".
6. Ekspor hasilnya (simpan di luar proyek):
   ```sh
   /Applications/XAMPP/xamppfiles/bin/mysqldump -uroot --single-transaction baznas_rilis > ~/Backups/baznas-basel/db/rilis-HARIINI.sql
   ```
7. Buat paket:
   ```sh
   ./build-rilis.sh --uploads
   ```
   Hasil di `rilis/`: `baznas-basel-WAKTU.zip` (kode), `uploads-WAKTU.zip` (gambar & PDF), `baznas-basel-WAKTU.integritas.json` (simpan sebagai pembanding).
   Paket otomatis ditolak bila berisi `.env`, `.sql`, atau log.

## C. Pasang di server

1. **Impor DB**: *phpMyAdmin* → pilih DB baru `baznasba_web` → *Import* → `rilis-HARIINI.sql`.
   Bila file > batas impor phpMyAdmin, kompres dulu (`gzip rilis-HARIINI.sql`) — phpMyAdmin menerima `.sql.gz`.
2. **Unggah kode**: *File Manager* → buka folder home (`/home/USERCPANEL/`, **bukan** `public_html`) → *Upload* `baznas-basel-WAKTU.zip` → *Extract*. Hasil: `/home/USERCPANEL/baznas-basel/`.
3. **Unggah gambar**: masuk `baznas-basel/public/` → *Upload* `uploads-WAKTU.zip` → *Extract* (menjadi `public/uploads/post/…` dst.).
4. **Buat `.env`**: di `baznas-basel/`, salin `.env.production.example` menjadi `.env`, *Edit*: isi `DB_NAME`, `DB_USER`, `DB_PASS` (DB baru dari langkah A6). Biarkan `APP_FORCE_HTTPS=true` bila langkah A5 sudah bergembok, `APP_HSTS=0` dulu.
   Lalu *Permissions* `.env` → **600**.
5. **Izin folder**: `storage/cache`, `storage/logs`, `storage/bukti`, `public/uploads` dan subfoldernya → **755** (file di dalamnya 644).
6. **Arahkan domain ke `public/`** — pilih satu:
   - **A (disarankan)**: *Domains* → baznasbasel.id → *Manage* → *Document Root* = `baznas-basel/public`.
   - **B (domain utama yang document root-nya terkunci di `public_html`)**: *File Manager* → ganti nama `public_html` menjadi `public_html_lama`, lalu minta hosting/Terminal membuat tautan: `ln -s /home/USERCPANEL/baznas-basel/public /home/USERCPANEL/public_html`.
   - **C (darurat, tanpa A/B)**: ekstrak isi paket langsung ke `public_html/` (setelah `public_html` lama diganti nama). `.htaccess` di akar paket meneruskan semua ke `public/` dan memblokir file rahasia — sudah diuji di Apache (`.env`, `app/`, `config/`, `vendor/`, `database/` → 404).
7. Bila memakai B/C, folder lama `file/` tidak lagi di document root — tidak masalah: URL `/file/...` lama otomatis dialihkan ke `/uploads/...`.

## D. Pemeriksaan setelah aktif (±15 menit)

1. Dari komputer lokal:
   ```sh
   php database/scripts/uji_rilis.php https://baznasbasel.id
   ```
   Harus **LULUS — 0 gagal** (memeriksa halaman, header keamanan, gzip & cache, file rahasia, http→https, URL lama, sitemap, robots, admin).
2. Masuk `https://baznasbasel.id/admin/masuk` → **Status Sistem**: semua "Baik" kecuali HSTS (dinyalakan nanti).
3. Uji alur zakat sungguhan: *Bayar Zakat* → isi data uji (nama "UJI") → halaman instruksi → *Konfirmasi* dengan gambar kecil → di admin *Pembayaran*: bukti tampil → **Tolak** → hapus/abaikan transaksi uji.
4. Buka beberapa URL lama dari Google (mis. `https://baznasbasel.id/home/kabar/berita/detail/…`) → harus pindah ke `/berita/…`.
5. Cek di HP (data seluler): beranda, menu, carousel, bayar zakat.

## E. Tugas terjadwal (cPanel → Cron Jobs)

Isi *Cron Email* dengan email pengelola. Ganti `USERCPANEL` dan jalur PHP sesuai hosting (lihat *MultiPHP Manager*; sering `/usr/local/bin/php` atau `/opt/cpanel/ea-php82/root/usr/bin/php`).

| Jadwal | Perintah | Guna |
|---|---|---|
| Harian 02.00 (`0 2 * * *`) | `/usr/local/bin/php /home/USERCPANEL/baznas-basel/database/scripts/rawat.php > /dev/null` | Hapus cache/log kedaluwarsa (log > 90 hari), catatan 404 lama |
| Harian 03.00 (`0 3 * * *`) | `/usr/local/bin/php /home/USERCPANEL/baznas-basel/database/scripts/cek_integritas.php` | Email **hanya bila** ada file kode berubah/baru atau skrip di folder unggahan |

Bila email cek integritas datang dan perubahan bukan dari Anda: ganti password cPanel & admin, unggah ulang paket rilis terakhir, periksa `storage/logs/`.

## F. Setelah 1 minggu stabil

1. **HSTS**: `.env` → `APP_HSTS=31536000`. Cek ulang `uji_rilis.php` (baris HSTS jadi OK). Jangan dimatikan lagi setelah aktif: browser mengingatnya 1 tahun.
2. **Google Search Console** (search.google.com/search-console): tambah properti *Domain* `baznasbasel.id` (verifikasi lewat DNS TXT di cPanel *Zone Editor*) → *Sitemaps* → kirim `https://baznasbasel.id/sitemap.xml`. Pantau *Halaman → Tidak diindeks* dan *Pengalaman halaman*.
3. **Tautan rusak**: tiap minggu buka admin → **Tautan Rusak**. Alamat yang sering dibuka → **Buat pengalihan** ke halaman yang sesuai.
4. Hapus `public_html_lama` & DB `baznasba_baznas` **setelah** yakin (simpan backup-nya dulu).

## Rollback

Situs lama tetap utuh selama langkah A–D:
- Kembalikan *Document Root* ke `public_html` (opsi A) atau ganti nama `public_html_lama` → `public_html` (opsi B/C).
- DB lama `baznasba_baznas` tidak disentuh. Pembayaran yang masuk lewat situs baru selama dicoba ada di DB `baznasba_web` — catat sebelum kembali.

## Memperbarui situs (rilis berikutnya)

1. Lokal: `./build-rilis.sh` → unggah zip ke `/home/USERCPANEL/`, ekstrak menimpa `baznas-basel/` (paket tidak berisi `.env`, gambar unggahan, bukti transfer, atau log — data aman).
2. Bila ada file baru di `database/migrations/`: jalankan sekali lewat Cron (*Once*) atau Terminal: `php database/scripts/migrate.php`. **Backup DB dulu.**
3. Hapus `storage/cache/*.cache` (*File Manager*), buka Status Sistem, jalankan `uji_rilis.php`.
4. Simpan `*.integritas.json` rilis baru; cek integritas harian otomatis memakai `integritas.json` dari paket.

## Catatan untuk penyedia hosting (opsional)
- `TraceEnable Off` dan `ServerTokens Prod` di konfigurasi Apache server (tidak bisa diatur dari `.htaccess`).
- Bila server di balik proxy/CDN, kirim header `X-Forwarded-Proto` agar aplikasi mengenali HTTPS.
