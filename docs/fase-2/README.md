# Fase 2 — Database (28 Oktober 2026)

## Status Definition of Done
| Syarat | Status |
|---|---|
| Semua konten lama muncul di `post` | ✅ 58 baris lama → 47 post (11 duplikat digabung); ke-58 URL lama punya redirect ke post yang ada |
| Tidak ada error charset | ✅ 37 tabel `utf8mb4_unicode_ci`; tabel latin1 dicek bebas byte non-ASCII sebelum dikonversi; kutip lengkung & aksen utuh |
| Gambar varian tersedia | ✅ 96 gambar → original terkompres (36,3 MB → 12,2 MB) + WebP 480/800/1200; 40 PDF regulasi disalin |
| Reset password admin ke hash modern | ⏳ skrip siap & teruji — **jalankan sendiri** (lihat bawah) |

Database kerja: `baznas_basel` (sesuai `.env`). Backup sebelum migrasi: `~/Backups/baznas-basel/db/2026-10-28_0933_sebelum-fase2_baznas_basel.sql`.

## Urutan menjalankan (lokal maupun produksi)
```sh
php database/scripts/migrate.php                  # 001–005 (riwayat di tabel `migrasi`)
php database/scripts/generate_image_variants.php  # ~1,5 menit; sumber default ~/Backups/baznas-basel/uploads-lama
php database/scripts/migrate_posts.php            # --ulang untuk mengulang
php database/scripts/reset_admin_password.php     # password diketik, tidak tampil
```
Semua file migrasi aman diulang (`IF NOT EXISTS`, `INSERT IGNORE`) — sudah diuji dijalankan dua kali.

## Isi migrasi
| File | Isi |
|---|---|
| `001_fix_charset_and_types.sql` | Charset semua tabel → utf8mb4_unicode_ci; `pengambilan_beras.card_number` → VARCHAR(20); banner (`link`, `foto_mobile`, `urutan`); kolom 5 pilar di `kategori_program`; kode pembayaran VARCHAR(12) + UNIQUE; `konfirmasi_pembayaran.id_pembayaran` & `status` (6 dari 7 konfirmasi lama berhasil ditautkan lewat kode; 3 yang pembayarannya SELESAI → `diverifikasi`) |
| `002_create_post.sql` | Tabel `post` (+ indeks `idx_legacy`; FK sub_program `ON DELETE SET NULL`) |
| `003_create_supporting.sql` | `setting`, `halaman`, `testimoni`, `layanan`, `amil`, `galeri`, `redirect`; `video.youtube_id`; `laporan_keuangan.tahun/tgl` |
| `004_seed_data.sql` | 20 `setting` (kontak, sosmed, koordinat dari situs lama), 5 pilar, 4 `layanan`, 6 `halaman` |
| `005_user_password.sql` | `user.password` VARCHAR(255), hapus `confirm_password` |

## Keputusan yang diambil saat migrasi
- **Duplikat**: 11 pasang `berita`/`artikel` ↔ `program` dengan isi identik. Versi berita/artikel disimpan dan **mewarisi `id_sub_program`** dari kembarannya, jadi tetap muncul di halaman pilar. Konsekuensi untuk Fase 5: halaman `/program/{pilar}` harus memfilter `post.id_sub_program`, **bukan** `jenis = 'program'`. Sebaran per pilar: Peduli 7 · Cerdas 1 · Sehat 3 · Taqwa 2 · Makmur 3.
- **Z-ICE** (`artikel#24`, `artikel#25`, `program#77`) bukan duplikat — tiga liputan berbeda, dibiarkan.
- **Konten disanitasi** (`App\Services\HtmlSanitizer`): atribut `style/border/align` dibuang, paragraf kosong `&nbsp;` dibuang, link `target=_blank` diberi `rel="noopener noreferrer"`. Teks tidak diubah.
- **Folder upload baru**: `public/uploads/{post,banner,zakat,mitra,rekening,perundangan}/`. Kolom foto tetap berisi nama file lama (hash), varian `{nama}-480.webp` dst.
- **Halaman**: Profil & Visi Misi diisi dari situs lama (typo dibakukan: "semakinmengukuhkan", "nonstructural", "social", "system", "mensejahterakan") dan langsung terbit. Struktur, Kebijakan Privasi, S&K, FAQ = draf (`publish = N`).
- **Deskripsi 5 pilar** ditulis singkat berdasarkan sub-program yang ada — usulan, silakan diubah admin.
- **Password**: bcrypt cost 12 (bukan argon2id) agar pasti didukung PHP di hosting.

## Wajib diisi admin sebelum rilis
- `setting`: `harga_emas_per_gram`, `nishab_bulanan`, `nilai_fitrah`, `jam_layanan` — sengaja kosong; angka di situs lama masih tahun 2021. `nilai_fidyah` = 20000 (dari `jenis_zakat` lama), cek ulang.
- Konten halaman draf (struktur, kebijakan, S&K, FAQ).

## ⚠️ Catatan untuk produksi
- **Jangan jalankan `005` di server selama admin lama masih dipakai** — admin lama login dengan MD5 dan memakai `confirm_password`. Pakai `migrate.php --sampai=004`, lalu 005 + reset password saat admin baru rilis (Fase 7).
- 001–004 aman untuk admin lama (hanya menambah kolom/tabel dan memperlebar tipe).
- `migrate_posts.php` membaca dimensi foto dari `public/uploads/post` → jalankan `generate_image_variants.php` lebih dulu, dengan sumber folder `file/` dari backup cPanel.

## Belum dikerjakan / untuk fase berikutnya
- 11 foto milik `program` yang digabung tidak dipakai (yatim di `public/uploads/post`) — bisa dibersihkan saat Fase 7.
- URL gambar lama (`/file/{tabel}/{file}`) belum dialihkan ke `/uploads/...` — tambahkan aturan rewrite di Fase 5.
- Password admin lokal masih MD5("admin") sampai skrip reset dijalankan.
- Detail per baris: `laporan-migrasi.md`.
