-- 005 — Kolom password siap untuk password_hash() (bcrypt/argon2id).
-- PERHATIAN PRODUKSI: admin lama memakai MD5 & kolom confirm_password. Jalankan file ini di server
-- hanya saat admin baru (Fase 7) menggantikan admin lama, lalu segera jalankan
-- database/scripts/reset_admin_password.php.

ALTER TABLE user MODIFY password VARCHAR(255) NOT NULL;
ALTER TABLE user DROP COLUMN IF EXISTS confirm_password;
