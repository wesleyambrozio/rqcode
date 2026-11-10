CREATE TABLE IF NOT EXISTS product_referral_state (
 system_id BIGINT PRIMARY KEY,
 summary_json JSONB NOT NULL,
 segments_json JSONB NOT NULL,
 last_sync_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS product_referrers (
 system_id BIGINT NOT NULL,
 external_id VARCHAR(80) NOT NULL,
 code VARCHAR(24) NOT NULL,
 name VARCHAR(180) NOT NULL,
 registered INTEGER NOT NULL DEFAULT 0 CHECK(registered >= 0),
 confirmed INTEGER NOT NULL DEFAULT 0 CHECK(confirmed >= 0),
 activated INTEGER NOT NULL DEFAULT 0 CHECK(activated >= 0),
 last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(system_id,external_id),
 UNIQUE(system_id,code)
);
