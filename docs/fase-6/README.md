# Fase 6 — Zakat & Pembayaran (28 Oktober 2026)

## Status Definition of Done
| Syarat | Status |
|---|---|
| Alur end-to-end teruji, data uji dihapus | ✅ 41 pengujian HTTP (`uji_bayar.php`) + uji browser (JS) — dijalankan berulang; setiap kali pembayaran/konfirmasi uji & file bukti dihapus, jumlah data kembali ke semula (24 pembayaran, 7 konfirmasi) |
| Validasi server berjalan | ✅ Semua isian divalidasi ulang di server; nominal fidyah/fitrah dihitung ulang server (nominal kiriman browser diabaikan bila nilai ditetapkan) |

Lighthouse mobile: `/bayar-zakat` 94–100 (CLS **0**), `/kalkulator-zakat` 99–100, `/konfirmasi` 97–100. axe-core: **0 pelanggaran** (360px & 1280px) di bayar, kalkulator, konfirmasi, instruksi, rekening. URL lama `/home/layanan/kalkulator`, `/home/pembayaran/zakat|konfirmasi` kini mendarat di halaman 200 (100 dari 101 URL lama; sisanya `keterangan?kode=` dengan kode yang memang tidak ada → 404, benar).

## Alur (§12)
1. **`/bayar-zakat`** — 4 langkah: jenis → nominal → data diri → ringkasan.
   - Jenis utama (Penghasilan, Fitrah, Fidyah, Infaq, Sedekah) + "Zakat maal lainnya".
   - Fitrah: jumlah jiwa; Fidyah: jumlah hari × nilai fidyah; lainnya: nominal + tombol cepat.
   - Penghasilan/Emas/Perdagangan: kalkulator mini → "Gunakan nominal ini".
   - `?jenis=ID&nominal=N&jumlah=J` mengisi otomatis (dari kalkulator / halaman zakat) dan langsung ke langkah 2.
   - **Tanpa JS** keempat langkah tampil sebagai satu form panjang (`<noscript><style>`); server memvalidasi semua.
2. **POST** → `pembayaran` status `0` + kode unik 8 karakter (`A–Z` tanpa I/O, `2–9`; `random_int`) → 303 ke instruksi.
3. **`/bayar-zakat/{kode}`** — nominal & kode (tombol salin), rekening tujuan (zakat → rekening ZAKAT, infaq/sedekah → INFAQ), langkah transfer, tombol unggah bukti & WhatsApp (pesan otomatis berisi kode). Status: menunggu transfer / menunggu verifikasi / terverifikasi / dibatalkan. `noindex` + `Cache-Control: private, no-store` (berisi nama muzaki).
4. **`/konfirmasi`** — kode, bank pengirim, rekening tujuan, tanggal (≤ hari ini), nominal, bukti (JPG/PNG/WebP/PDF) → `konfirmasi_pembayaran` (`status = baru`, terhubung `id_pembayaran`) → kembali ke instruksi. Verifikasi oleh admin (Fase 7).
5. **`/kalkulator-zakat`** — Penghasilan, Emas, Perdagangan, Fitrah, Fidyah; dihitung langsung di browser; tanpa JS form GET dihitung di server (`?hitung=1`). Tombol "Tunaikan sekarang" membawa jenis & nominal ke form bayar.

## Keamanan (§10)
| Kontrol | Implementasi |
|---|---|
| CSRF | Semua POST (Router); body > `post_max_size` → **413** (bukan 419 yang membingungkan) |
| Honeypot | Field `website` tersembunyi; terisi → ditolak tanpa menyimpan |
| Rate limit | `App\Core\RateLimiter` (file): bayar & konfirmasi masing-masing 10 percobaan / 10 menit / IP → 429 |
| Unggahan | `App\Core\Upload`: jenis dari isi file (`finfo`), gambar **di-encode ulang ke WebP** (membuang EXIF/GPS & payload), PDF wajib berawalan `%PDF-`, nama acak 32 hex, maks 5 MB (mengikuti batas php.ini bila lebih kecil) |
| Penyimpanan bukti | `storage/bukti/YYYY/MM/` — **di luar `public/`**, tidak bisa diakses lewat URL; masuk `.gitignore` |
| Data pribadi | Halaman konfirmasi hanya menampilkan jenis & nominal untuk kode yang dimasukkan; nama hanya di halaman instruksi milik kode itu |
| Escape | Ringkasan di browser dibangun dengan `textContent` (isian tidak pernah dirender sebagai HTML) |

Diuji: file PHP berkedok `.jpg`, GIF polyglot berisi PHP, teks berkedok `.pdf`, file 5,5 MB, body 9 MB, kode tak dikenal, tanggal masa depan, jenis palsu, nominal fidyah dimanipulasi, tanpa token CSRF, honeypot, 11× POST beruntun.

## Perubahan basis data — migrasi 007
- `pembayaran.nominal` → `BIGINT UNSIGNED` (INT maks ±Rp2,1 miliar), `pembayaran.jml_jiwa` (fitrah), indeks `(status, waktu)`.
- `konfirmasi_pembayaran.nominal_transfer`, `dibuat`.
- Aman untuk admin lama (hanya menambah/memperlebar).

## Komponen baru
- Core: `Validator` (pesan Bahasa Indonesia), `RateLimiter`, `Upload` + `UploadException`, `Image::simpanWebp()`, `Request::file()` & `melebihiBatasPost()`.
- Service: `ZakatCalculator` (rumus + parameter dari `setting`), `PembayaranService` (jenis, buat, konfirmasi, rekening yang disarankan, normalisasi HP).
- Model: `JenisZakat`, `Pembayaran`, `KonfirmasiPembayaran`.
- View: `bayar`, `bayar-instruksi`, `konfirmasi`, `kalkulator`; komponen `card-rekening`, `hasil-zakat`; `form-field` kini menerima `id`, `atribut`, `nameAttr`.
- JS: `zakat.js` — format rupiah, rumus (sama persis dengan PHP; 2,5% dihitung `(harta + 39) / 40` bulat agar tidak ada selisih pembulatan), form 4 langkah, kalkulator.
- Helper: `angka()`, `galat()`, `ukuran_ini()`, `batas_unggah()`.
- `composer serve` kini memakai `upload_max_filesize=6M` dan `process-timeout: 0` (sebelumnya Composer menghentikan server setelah 5 menit).

## Keputusan
- **Angka zakat tidak ditebak.** `harga_emas_per_gram`, `nishab_bulanan`, `nilai_fitrah` masih kosong di `setting`:
  - kalkulator meminta pengguna mengisi harga emas hari ini (nishab dihitung 85 g × harga);
  - bayar fitrah: muzaki mengisi total nominal sendiri + jumlah jiwa dicatat. **Begitu admin mengisi `nilai_fitrah`, nominal fitrah otomatis dihitung server** (jiwa × nilai) dan isian nominal disembunyikan.
  - fidyah sudah memakai Rp20.000/hari.
- **Kode pembayaran 8 karakter** (±40 bit) — cukup sulit ditebak karena halaman instruksi memuat nama muzaki. Kode lama 5 karakter tetap bisa dibuka & dikonfirmasi.
- **Tanpa nominal unik** (mis. +123): nominal zakat ditransfer apa adanya; pencocokan memakai kode di berita transfer + konfirmasi.
- Bukti selalu disimpan sebagai WebP/PDF baru — file asli dari ponsel tidak pernah disimpan.

## Wajib sebelum rilis (admin)
- Isi `setting`: `harga_emas_per_gram` (atau `nishab_bulanan`), `nilai_fitrah` sesuai SK tahun berjalan.
- Pastikan `upload_max_filesize` ≥ 6M dan `post_max_size` ≥ 8M di cPanel (MultiPHP INI Editor) — bila lebih kecil, batas di form otomatis mengikuti.
- Verifikasi pembayaran & lihat bukti → Fase 7 (route admin terautentikasi untuk membaca `storage/bukti`).
