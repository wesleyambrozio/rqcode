CREATE TABLE IF NOT EXISTS product_referral_state (
 system_id BIGINT UNSIGNED PRIMARY KEY,
 summary_json JSON NOT NULL,
 segments_json JSON NOT NULL,
 last_sync_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS product_referrers (
 system_id BIGINT UNSIGNED NOT NULL,
 external_id VARCHAR(80) NOT NULL,
 code VARCHAR(24) NOT NULL,
 name VARCHAR(180) NOT NULL,
 registered INT UNSIGNED NOT NULL DEFAULT 0,
 confirmed INT UNSIGNED NOT NULL DEFAULT 0,
 activated INT UNSIGNED NOT NULL DEFAULT 0,
 last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(system_id, external_id),
 UNIQUE KEY referral_product_code(system_id, code)
);
