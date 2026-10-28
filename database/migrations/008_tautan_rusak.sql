-- 008 — Pemantauan 404 (Fase 8): alamat yang dibuka pengunjung tetapi tidak ditemukan.
-- Tabel baru saja; aman untuk admin lama. Admin → Tautan Rusak → "Buat pengalihan".

CREATE TABLE IF NOT EXISTS tautan_rusak (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  path VARCHAR(255) NOT NULL,
  hit INT UNSIGNED NOT NULL DEFAULT 1,
  referer VARCHAR(255) NULL,            -- halaman asal terakhir (tautan rusak dari situs lain / situs sendiri)
  pertama DATETIME NOT NULL,
  terakhir DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_tautan_rusak_path (path),
  KEY idx_tautan_rusak_terakhir (terakhir)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
