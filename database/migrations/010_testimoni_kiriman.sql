-- 010 — Testimoni kiriman pengunjung (muzaki/mustahik mengisi sendiri di beranda), dimoderasi admin.
-- Hanya menambah kolom; aman untuk admin lama. no_hp untuk verifikasi admin — tidak pernah tampil di situs.

ALTER TABLE testimoni
  ADD COLUMN IF NOT EXISTS peran VARCHAR(10) NULL AFTER profesi,          -- muzaki | mustahik
  ADD COLUMN IF NOT EXISTS no_hp VARCHAR(20) NULL AFTER peran,
  ADD COLUMN IF NOT EXISTS sumber VARCHAR(10) NOT NULL DEFAULT 'admin' AFTER urutan, -- admin | pengunjung
  ADD COLUMN IF NOT EXISTS persetujuan DATETIME NULL AFTER sumber,        -- kapan pengirim setuju ditampilkan
  ADD INDEX IF NOT EXISTS idx_testimoni_publish (publish, urutan, created_at);
