-- 002 — Tabel konten gabungan (artikel, berita, berkah_zakat, program, event).
-- Diisi oleh database/scripts/migrate_posts.php. Tabel lama TIDAK dihapus di Fase 1.

CREATE TABLE IF NOT EXISTS post (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis ENUM('berita','artikel','program','berkah_zakat','event') NOT NULL,
  judul VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  ringkasan VARCHAR(300) NULL,          -- otomatis dari konten bila kosong (strip_tags, 160 karakter)
  konten MEDIUMTEXT NOT NULL,           -- HTML yang sudah disanitasi
  foto VARCHAR(255) NULL,               -- nama file di public/uploads/post; varian: {nama}-480.webp dst.
  foto_w SMALLINT UNSIGNED NULL,        -- dimensi → atribut width/height (anti-CLS)
  foto_h SMALLINT UNSIGNED NULL,
  id_sub_program INT NULL,              -- terisi juga untuk berita/artikel yang termasuk kegiatan pilar
  penulis VARCHAR(100) NULL,
  tgl DATE NOT NULL,
  publish ENUM('Y','N') NOT NULL DEFAULT 'N',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  legacy_table VARCHAR(30) NULL,
  legacy_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slug (slug),
  KEY idx_list (publish, jenis, tgl),
  KEY idx_program (publish, id_sub_program, tgl),
  KEY idx_legacy (legacy_table, legacy_id),
  CONSTRAINT fk_post_subprogram FOREIGN KEY (id_sub_program)
    REFERENCES sub_program (id_sub_program) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
