-- 016 — Gambar kartu halaman "Tentang Kami" di halaman Profil Kami (/tentang). Hanya menambah kolom; aman untuk admin lama.

ALTER TABLE halaman
  ADD COLUMN IF NOT EXISTS gambar VARCHAR(255) NULL AFTER konten,          -- public/uploads/halaman/{acak} + varian WebP
  ADD COLUMN IF NOT EXISTS gambar_w SMALLINT UNSIGNED NULL AFTER gambar,
  ADD COLUMN IF NOT EXISTS gambar_h SMALLINT UNSIGNED NULL AFTER gambar_w;
