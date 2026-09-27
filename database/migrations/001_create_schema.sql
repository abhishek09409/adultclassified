-- Phase 1 schema. Listing age is restricted to 21-99.

CREATE TABLE IF NOT EXISTS states (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_states_slug (slug),
    KEY idx_states_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cities (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    state_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cities_state_slug (state_id, slug),
    KEY idx_cities_state_id (state_id),
    KEY idx_cities_status (status),
    CONSTRAINT fk_cities_state FOREIGN KEY (state_id) REFERENCES states (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS locations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    city_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_locations_city_slug (city_id, slug),
    KEY idx_locations_city_id (city_id),
    KEY idx_locations_status (status),
    CONSTRAINT fk_locations_city FOREIGN KEY (city_id) REFERENCES cities (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator', 'editor') NOT NULL DEFAULT 'editor',
    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    failed_login_count INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email),
    KEY idx_admins_role (role),
    KEY idx_admins_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_sessions_token (token_hash),
    KEY idx_admin_sessions_admin_id (admin_id),
    KEY idx_admin_sessions_expires (expires_at),
    CONSTRAINT fk_admin_sessions_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS automation_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    run_date DATE NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    requested_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('running', 'completed', 'failed', 'locked') NOT NULL DEFAULT 'running',
    lock_token CHAR(64) NULL,
    error_message VARCHAR(500) NULL,
    trigger_source ENUM('cron', 'manual') NOT NULL DEFAULT 'cron',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_automation_runs_date (run_date),
    KEY idx_automation_runs_status (status),
    KEY idx_automation_runs_lock (lock_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS listings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED NOT NULL,
    state_id BIGINT UNSIGNED NOT NULL,
    city_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    age TINYINT UNSIGNED NOT NULL,
    status ENUM('draft', 'pending', 'published', 'suspended', 'deleted') NOT NULL DEFAULT 'draft',
    moderation_status ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    is_indexable TINYINT(1) NOT NULL DEFAULT 0,
    is_automated TINYINT(1) NOT NULL DEFAULT 0,
    automation_run_id BIGINT UNSIGNED NULL,
    content_fingerprint CHAR(64) NULL,
    title_fingerprint CHAR(64) NULL,
    description_fingerprint CHAR(64) NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_listings_location_slug (location_id, slug),
    KEY idx_listings_state_id (state_id),
    KEY idx_listings_city_id (city_id),
    KEY idx_listings_location_id (location_id),
    KEY idx_listings_category_id (category_id),
    KEY idx_listings_status (status),
    KEY idx_listings_published_at (published_at),
    KEY idx_listings_slug (slug),
    KEY idx_listings_is_featured (is_featured),
    KEY idx_listings_is_indexable (is_indexable),
    KEY idx_listings_public (status, is_indexable, published_at),
    KEY idx_listings_search (status, category_id, state_id, city_id, location_id),
    KEY idx_listings_automated_day (is_automated, published_at),
    KEY idx_listings_content_fingerprint (content_fingerprint),
    CONSTRAINT chk_listings_age CHECK (age >= 21 AND age <= 99),
    CONSTRAINT fk_listings_category FOREIGN KEY (category_id) REFERENCES categories (id),
    CONSTRAINT fk_listings_state FOREIGN KEY (state_id) REFERENCES states (id),
    CONSTRAINT fk_listings_city FOREIGN KEY (city_id) REFERENCES cities (id),
    CONSTRAINT fk_listings_location FOREIGN KEY (location_id) REFERENCES locations (id),
    CONSTRAINT fk_listings_automation_run FOREIGN KEY (automation_run_id) REFERENCES automation_runs (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS listing_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    listing_id BIGINT UNSIGNED NOT NULL,
    filename VARCHAR(191) NOT NULL,
    path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255) NULL,
    alt_text VARCHAR(180) NOT NULL DEFAULT '',
    mime_type VARCHAR(64) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_listing_images_listing (listing_id, sort_order),
    CONSTRAINT fk_listing_images_listing FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS listing_variations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    variation_type VARCHAR(64) NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    content TEXT NOT NULL,
    fingerprint CHAR(64) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    usage_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_listing_variations_fingerprint (fingerprint),
    KEY idx_listing_variations_lookup (variation_type, category_id, status),
    KEY idx_listing_variations_last_used (last_used_at),
    CONSTRAINT fk_listing_variations_category FOREIGN KEY (category_id) REFERENCES categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS listing_variation_usage (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    variation_type VARCHAR(64) NOT NULL,
    variation_id BIGINT UNSIGNED NOT NULL,
    listing_id BIGINT UNSIGNED NOT NULL,
    usage_count INT UNSIGNED NOT NULL DEFAULT 1,
    last_used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_variation_usage (variation_id, listing_id),
    KEY idx_variation_usage_listing (listing_id),
    KEY idx_variation_usage_type_last (variation_type, last_used_at),
    CONSTRAINT fk_variation_usage_variation FOREIGN KEY (variation_id) REFERENCES listing_variations (id),
    CONSTRAINT fk_variation_usage_listing FOREIGN KEY (listing_id) REFERENCES listings (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS automation_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    run_id BIGINT UNSIGNED NOT NULL,
    listing_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    action VARCHAR(64) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_automation_logs_run (run_id),
    KEY idx_automation_logs_listing (listing_id),
    KEY idx_automation_logs_action (action),
    KEY idx_automation_logs_created (created_at),
    CONSTRAINT fk_automation_logs_run FOREIGN KEY (run_id) REFERENCES automation_runs (id),
    CONSTRAINT fk_automation_logs_listing FOREIGN KEY (listing_id) REFERENCES listings (id),
    CONSTRAINT fk_automation_logs_location FOREIGN KEY (location_id) REFERENCES locations (id),
    CONSTRAINT fk_automation_logs_category FOREIGN KEY (category_id) REFERENCES categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    listing_id BIGINT UNSIGNED NOT NULL,
    reason ENUM(
        'illegal_content',
        'minor_age_concern',
        'fraud',
        'harassment',
        'copyright',
        'other'
    ) NOT NULL,
    email VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'reviewing', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
    kind ENUM('report', 'takedown') NOT NULL DEFAULT 'report',
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_reports_listing_id (listing_id),
    KEY idx_reports_status (status),
    KEY idx_reports_reason (reason),
    KEY idx_reports_created (created_at),
    CONSTRAINT fk_reports_listing FOREIGN KEY (listing_id) REFERENCES listings (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seo_metadata (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    page_type VARCHAR(64) NOT NULL,
    target_type VARCHAR(64) NULL,
    target_id BIGINT UNSIGNED NULL,
    path VARCHAR(255) NOT NULL,
    title VARCHAR(180) NOT NULL,
    meta_description VARCHAR(320) NOT NULL,
    canonical_url VARCHAR(255) NULL,
    robots VARCHAR(64) NOT NULL DEFAULT 'index,follow',
    og_title VARCHAR(180) NULL,
    og_description VARCHAR(320) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_seo_metadata_path (path),
    KEY idx_seo_metadata_target (page_type, target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    target_type VARCHAR(64) NULL,
    target_id BIGINT UNSIGNED NULL,
    previous_status VARCHAR(64) NULL,
    new_status VARCHAR(64) NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_logs_admin_id (admin_id),
    KEY idx_audit_logs_action (action),
    KEY idx_audit_logs_target (target_type, target_id),
    KEY idx_audit_logs_created (created_at),
    CONSTRAINT fk_audit_logs_admin FOREIGN KEY (admin_id) REFERENCES admins (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
