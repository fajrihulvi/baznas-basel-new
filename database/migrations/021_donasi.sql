-- 021 — Program donasi dari banner + formulir donatur (menu admin "Donasi Bantuan").
-- Menambah kolom banner & tabel baru; aman untuk admin lama. Data donatur = data pribadi: hanya untuk admin,
-- di situs hanya tampil total terkumpul, jumlah donatur, dan doa yang diizinkan (nama atau "Hamba Allah").

ALTER TABLE banner
  ADD COLUMN IF NOT EXISTS donasi TINYINT(1) NOT NULL DEFAULT 0 AFTER urutan,         -- 1 = banner program donasi
  ADD COLUMN IF NOT EXISTS donasi_nama VARCHAR(150) NULL AFTER donasi,
  ADD COLUMN IF NOT EXISTS donasi_slug VARCHAR(170) NULL AFTER donasi_nama,             -- /donasi/{slug}
  ADD COLUMN IF NOT EXISTS donasi_mulai DATE NULL AFTER donasi_slug,
  ADD COLUMN IF NOT EXISTS donasi_selesai DATE NULL AFTER donasi_mulai,
  ADD COLUMN IF NOT EXISTS donasi_target BIGINT UNSIGNED NULL AFTER donasi_selesai,
  ADD COLUMN IF NOT EXISTS donasi_minimal INT UNSIGNED NULL AFTER donasi_target,
  ADD COLUMN IF NOT EXISTS donasi_terkumpul_luar BIGINT UNSIGNED NULL AFTER donasi_minimal, -- dana di luar formulir situs (tunai, transfer langsung)
  ADD COLUMN IF NOT EXISTS donasi_deskripsi MEDIUMTEXT NULL AFTER donasi_terkumpul_luar;  -- HTML tersanitasi

-- MariaDB: IF NOT EXISTS untuk indeks
CREATE UNIQUE INDEX IF NOT EXISTS uniq_banner_donasi_slug ON banner (donasi_slug);

CREATE TABLE IF NOT EXISTS donasi (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode CHAR(8) NOT NULL,                          -- kode acak untuk berita transfer & halaman instruksi
  id_banner INT NOT NULL,                         -- program donasi (banner)
  nominal BIGINT UNSIGNED NOT NULL,
  nama VARCHAR(100) NOT NULL,
  anonim TINYINT(1) NOT NULL DEFAULT 0,           -- tampil sebagai "Hamba Allah"
  no_hp VARCHAR(20) NOT NULL,
  email VARCHAR(100) NULL,
  doa TEXT NULL,
  doa_tampil TINYINT(1) NOT NULL DEFAULT 1,       -- admin bisa menyembunyikan doa dari halaman program
  id_rekening INT NULL,
  bank_tujuan VARCHAR(100) NULL,                  -- salinan saat donasi dibuat (rekening bisa diubah/dihapus)
  norek_tujuan VARCHAR(50) NULL,
  status ENUM('menunggu','terverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
  catatan TEXT NULL,                              -- catatan internal admin
  petugas VARCHAR(100) NULL,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diverifikasi DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_donasi_kode (kode),
  KEY idx_donasi_program (id_banner, status),
  KEY idx_donasi_status (status, waktu),
  CONSTRAINT fk_donasi_banner FOREIGN KEY (id_banner) REFERENCES banner (id_banner) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
