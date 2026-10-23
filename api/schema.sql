CREATE DATABASE IF NOT EXISTS samchuksfoundation
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE samchuksfoundation;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    category VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NULL,
    programme_year VARCHAR(120) NOT NULL DEFAULT '',
    participants VARCHAR(120) NOT NULL DEFAULT '',
    duration VARCHAR(120) NOT NULL DEFAULT '',
    cover_image VARCHAR(500) NOT NULL DEFAULT '',
    gallery_json JSON NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_events_slug (slug),
    KEY idx_events_public (status, featured, event_date),
    CONSTRAINT fk_events_admin FOREIGN KEY (created_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB;
