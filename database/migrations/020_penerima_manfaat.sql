-- 020 — Penerima manfaat per bulan untuk 5 pilar program (admin → Penyaluran → Penerima Manfaat).
-- Hanya angka agregat (jumlah jiwa), tanpa data pribadi mustahik → boleh tampil di situs (/tentang/sistem-pelaporan-zakat) & dasbor.
-- Tabel baru saja; aman untuk admin lama. Satu baris = satu bulan. Persentase tidak disimpan (selalu dihitung dari jumlah jiwa).

CREATE TABLE IF NOT EXISTS penerima_manfaat (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  periode DATE NOT NULL,                       -- tanggal 1 bulan laporan
  peduli INT UNSIGNED NOT NULL DEFAULT 0,      -- jiwa; nama kolom = pilar (slug basel-peduli, dst.)
  sehat INT UNSIGNED NOT NULL DEFAULT 0,
  makmur INT UNSIGNED NOT NULL DEFAULT 0,
  cerdas INT UNSIGNED NOT NULL DEFAULT 0,
  taqwa INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL DEFAULT 0,       -- jumlah kelimanya, dihitung server
  penghimpunan BIGINT UNSIGNED NULL,           -- opsional: total penghimpunan ZIS bulan itu yang boleh diumumkan
  keterangan TEXT NULL,
  petugas VARCHAR(100) NULL,
  dibuat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diubah DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_penerima_manfaat_periode (periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
