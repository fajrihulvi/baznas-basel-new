-- 018 — Kegiatan BAZNAS (tabel sub_program): deskripsi & foto untuk bagian "Kegiatan Kami" di halaman Profil Kami (/tentang).
-- Hanya menambah kolom; aman untuk admin lama.

ALTER TABLE sub_program
  ADD COLUMN IF NOT EXISTS deskripsi VARCHAR(300) NULL AFTER slug,
  ADD COLUMN IF NOT EXISTS foto VARCHAR(255) NULL AFTER deskripsi,         -- public/uploads/kegiatan/{acak} + varian WebP
  ADD COLUMN IF NOT EXISTS foto_w SMALLINT UNSIGNED NULL AFTER foto,
  ADD COLUMN IF NOT EXISTS foto_h SMALLINT UNSIGNED NULL AFTER foto_w;
