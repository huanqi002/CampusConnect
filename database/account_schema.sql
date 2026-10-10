-- Account and volunteer setup used by config.php (support_system).
-- Import this file on a fresh local XAMPP installation. It keeps existing rows.
CREATE DATABASE IF NOT EXISTS support_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE support_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    university_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    picture_url VARCHAR(255) NULL,
    cover_photo_url VARCHAR(255) NULL,
    volunteer_picture_url VARCHAR(255) NULL,
    volunteer_cover_photo_url VARCHAR(255) NULL,
    tfa_code VARCHAR(255) NULL,
    tfa_expiry DATETIME NULL,
    education VARCHAR(255) NULL,
    skills TEXT NULL,
    support_experience TEXT NULL,
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    remember_token_hash CHAR(64) NULL,
    remember_expires DATETIME NULL,
    reset_code VARCHAR(6) NULL,
    reset_expiry DATETIME NULL,
    is_volunteer CHAR(1) NOT NULL DEFAULT 'N',
    is_active CHAR(1) NOT NULL DEFAULT 'Y',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS volunteers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    preferred_day VARCHAR(10) NOT NULL,
    preferred_time TIME NOT NULL,
    support_mode VARCHAR(20) NOT NULL,
    is_available CHAR(1) NOT NULL DEFAULT 'Y',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_account_volunteer_user FOREIGN KEY (volunteer_id) REFERENCES users (id),
    CONSTRAINT uq_account_volunteer_slot UNIQUE (volunteer_id, preferred_day, preferred_time)
);
