-- 019 — Deskripsi pilar program boleh panjang (sebelumnya VARCHAR(255)). Isi lama tetap utuh.

ALTER TABLE kategori_program
  MODIFY COLUMN deskripsi TEXT NULL;
