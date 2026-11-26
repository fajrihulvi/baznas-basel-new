-- 017 — Foto pilar program (selain ikon) untuk daftar "Program Kami" di halaman Profil Kami (/tentang).
-- Hanya menambah kolom; aman untuk admin lama.

ALTER TABLE kategori_program
  ADD COLUMN IF NOT EXISTS foto VARCHAR(255) NULL AFTER ikon,              -- public/uploads/pilar/{acak} + varian WebP
  ADD COLUMN IF NOT EXISTS foto_w SMALLINT UNSIGNED NULL AFTER foto,
  ADD COLUMN IF NOT EXISTS foto_h SMALLINT UNSIGNED NULL AFTER foto_w;
