-- 011 — Warna & transparansi kotak ikon 5 pilar + ikon bottom navigation yang bisa diganti admin.
-- Hanya menambah kolom/tabel; aman untuk admin lama.

ALTER TABLE kategori_program
  ADD COLUMN IF NOT EXISTS warna_latar VARCHAR(7) NULL AFTER ikon,              -- #rrggbb; NULL = warna bawaan
  ADD COLUMN IF NOT EXISTS opasitas_latar TINYINT UNSIGNED NULL AFTER warna_latar; -- 0–100 (%); NULL = 100

-- Ikon & label 5 item bottom navigation (HP). ikon NULL = ikon bawaan (SVG di partials/ikon-nav.php).
CREATE TABLE IF NOT EXISTS ikon_nav (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kunci VARCHAR(20) NOT NULL,          -- sama dengan 'svg' di config/menu.php → bawah
  label VARCHAR(20) NOT NULL,
  ikon VARCHAR(255) NULL,
  urutan TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_ikon_nav_kunci (kunci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO ikon_nav (kunci, label, urutan) VALUES
  ('beranda', 'Beranda', 1),
  ('program', 'Program', 2),
  ('bayar-zakat', 'Bayar Zakat', 3),
  ('kabar', 'Kabar', 4),
  ('menu', 'Menu', 5);
