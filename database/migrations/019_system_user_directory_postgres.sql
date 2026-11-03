ALTER TABLE system_users ADD COLUMN IF NOT EXISTS registered_at TIMESTAMP NULL;
ALTER TABLE system_users ADD COLUMN IF NOT EXISTS referral_count INTEGER NOT NULL DEFAULT 0 CHECK (referral_count >= 0);
CREATE INDEX IF NOT EXISTS system_users_directory_idx ON system_users (system_id, registered_at, last_seen_at);
