-- 003 — Tabel pendukung: setting, halaman, testimoni, layanan, amil, galeri, redirect.

CREATE TABLE IF NOT EXISTS setting (
  kunci VARCHAR(60) PRIMARY KEY,
  nilai TEXT NULL,
  grup VARCHAR(30) NOT NULL DEFAULT 'umum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profil, Visi Misi, Struktur, Kebijakan, S&K, FAQ, dll.
CREATE TABLE IF NOT EXISTS halaman (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grup ENUM('tentang','ketentuan','layanan','lainnya') NOT NULL DEFAULT 'lainnya',
  judul VARCHAR(150) NOT NULL,
  slug VARCHAR(170) NOT NULL UNIQUE,
  konten MEDIUMTEXT NOT NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  publish ENUM('Y','N') NOT NULL DEFAULT 'Y',
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimoni (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  profesi VARCHAR(100) NULL,
  foto VARCHAR(255) NULL,
  isi TEXT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  publish ENUM('Y','N') NOT NULL DEFAULT 'N',
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kartu "Tunaikan Zakat": ZIS Online, Transfer, Jemput Zakat, Zakat Fitrah
CREATE TABLE IF NOT EXISTS layanan (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(100) NOT NULL,
  deskripsi VARCHAR(200) NULL,
  ikon VARCHAR(255) NULL,
  link VARCHAR(255) NOT NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  publish ENUM('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amil (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  jabatan VARCHAR(100) NOT NULL,
  bidang ENUM('pimpinan','pelaksana') NOT NULL DEFAULT 'pelaksana',
  foto VARCHAR(255) NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  publish ENUM('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS galeri (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis ENUM('foto','poster','infografis') NOT NULL DEFAULT 'foto',
  judul VARCHAR(150) NOT NULL,
  foto VARCHAR(255) NOT NULL,
  foto_w SMALLINT UNSIGNED NULL,
  foto_h SMALLINT UNSIGNED NULL,
  tgl DATE NOT NULL,
  publish ENUM('Y','N') NOT NULL DEFAULT 'Y',
  KEY idx_galeri (publish, jenis, tgl)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Redirect 301 dari URL lama (path saja, tanpa domain)
CREATE TABLE IF NOT EXISTS redirect (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dari VARCHAR(255) NOT NULL UNIQUE,
  ke VARCHAR(255) NOT NULL,
  hit INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tambahan untuk tabel lama yang tetap dipakai
ALTER TABLE video
  ADD COLUMN IF NOT EXISTS youtube_id VARCHAR(20) NULL AFTER link,
  ADD INDEX IF NOT EXISTS idx_video_tgl (tgl);

ALTER TABLE laporan_keuangan
  ADD COLUMN IF NOT EXISTS tahun YEAR NULL,
  ADD COLUMN IF NOT EXISTS tgl DATE NULL;
