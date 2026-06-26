USE my_mondodelleleggende;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS email_encrypted TEXT NULL AFTER email,
  ADD COLUMN IF NOT EXISTS email_hash CHAR(64) NULL AFTER email_encrypted;

ALTER TABLE users
  ADD UNIQUE KEY uq_users_email_hash (email_hash);
