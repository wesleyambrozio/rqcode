ALTER TABLE system_users ADD COLUMN registered_at DATETIME NULL AFTER status;
ALTER TABLE system_users ADD COLUMN referral_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER last_seen_at;
CREATE INDEX system_users_directory_idx ON system_users (system_id, registered_at, last_seen_at);
