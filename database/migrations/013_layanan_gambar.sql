-- 013 — Kartu "Tunaikan Zakat" di beranda: gambar di atas judul (menggantikan ikon). Hanya menambah kolom; aman untuk admin lama.

ALTER TABLE layanan
  ADD COLUMN IF NOT EXISTS gambar VARCHAR(255) NULL AFTER ikon,          -- berkas di public/uploads/layanan (+ varian WebP)
  ADD COLUMN IF NOT EXISTS gambar_w SMALLINT UNSIGNED NULL AFTER gambar,
  ADD COLUMN IF NOT EXISTS gambar_h SMALLINT UNSIGNED NULL AFTER gambar_w;
