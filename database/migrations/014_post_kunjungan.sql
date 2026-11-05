-- 014 — Penghitung "dilihat" berita: pencegah hitung ganda per hari (kolom post.views sudah ada sejak 002).
-- Tanpa data pribadi: `sidik` = HMAC(IP + browser) dengan garam acak yang berganti tiap hari, tidak bisa dibalik ke IP.
-- Baris hari sebelumnya tidak berguna lagi dan dihapus otomatis (DilihatService & database/scripts/rawat.php).

CREATE TABLE IF NOT EXISTS post_kunjungan (
  id_post INT UNSIGNED NOT NULL,
  sidik CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  tgl DATE NOT NULL,
  PRIMARY KEY (id_post, sidik),
  KEY idx_post_kunjungan_tgl (tgl),
  CONSTRAINT fk_post_kunjungan_post FOREIGN KEY (id_post) REFERENCES post (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
