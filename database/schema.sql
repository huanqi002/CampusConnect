CREATE DATABASE IF NOT EXISTS CampusConnect
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE CampusConnect;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS history;
DROP TABLE IF EXISTS feedbacks;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS volunteers;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(150) NOT NULL,
    full_name       VARCHAR(100) NOT NULL,
    university_name VARCHAR(100) NOT NULL,
      password_hash   VARCHAR(255) NOT NULL,
      picture_url     VARCHAR(255) NULL,
      cover_photo_url VARCHAR(255) NULL,
      volunteer_picture_url VARCHAR(255) NULL,
      volunteer_cover_photo_url VARCHAR(255) NULL,
    tfa_code        VARCHAR(255) NULL,     
    tfa_expiry      DATETIME     NULL,     
    is_volunteer    CHAR(1)      NOT NULL DEFAULT 'N',
    is_active       CHAR(1)      NOT NULL DEFAULT 'N',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email      UNIQUE (email),
    CONSTRAINT chk_users_volunteer CHECK (is_volunteer IN ('Y', 'N')),
    CONSTRAINT chk_users_active    CHECK (is_active IN ('Y', 'N'))
);

CREATE TABLE volunteers (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT         NOT NULL, 
    category       VARCHAR(50) NOT NULL,
    preferred_day  VARCHAR(10) NOT NULL,
    preferred_time TIME        NOT NULL,
    skills         VARCHAR(255) NOT NULL,
    support_mode   VARCHAR(20) NOT NULL,
    is_available   CHAR(1)     NOT NULL DEFAULT 'N',
    created_at     DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_volunteers_slot  UNIQUE (user_id, preferred_day, preferred_time),
    CONSTRAINT chk_volunteers_day  CHECK (preferred_day IN ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')),
    CONSTRAINT chk_volunteers_mode CHECK (support_mode IN ('Online', 'Face-to-face')),
    CONSTRAINT chk_volunteers_flag CHECK (is_available IN ('Y', 'N')),
    CONSTRAINT fk_volunteers_user  FOREIGN KEY (user_id) REFERENCES users (id)
);

CREATE TABLE requests (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT          NOT NULL,
    volunteer_id   INT          NULL,
    category       VARCHAR(50)  NOT NULL,
    description    VARCHAR(500) NOT NULL,
    preferred_day  VARCHAR(10)  NOT NULL,
    preferred_time VARCHAR(20)  NOT NULL, 
    support_mode   VARCHAR(20)  NOT NULL,
    status         VARCHAR(20)  NOT NULL DEFAULT 'Pending',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notified_at    DATETIME     NULL,
    responded_at   DATETIME     NULL,     
    INDEX idx_requests_status (status),
    CONSTRAINT chk_requests_day      CHECK (preferred_day IN ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')),
    CONSTRAINT chk_requests_mode     CHECK (support_mode IN ('Online', 'Face-to-face')),
    CONSTRAINT fk_requests_user      FOREIGN KEY (user_id)      REFERENCES users (id),
    CONSTRAINT fk_requests_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (id)
);

CREATE TABLE sessions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    request_id     INT          NOT NULL,
    user_id        INT          NOT NULL, 
    volunteer_id   INT          NOT NULL,
    category       VARCHAR(50)  NOT NULL,
    description    VARCHAR(500) NOT NULL,
    session_date   DATE         NULL,     
    start_time     TIME         NULL,     
    end_time       TIME         NULL,
    support_mode   VARCHAR(20)  NOT NULL,
    status         VARCHAR(20)  NOT NULL DEFAULT 'Pending',
    conf_student   CHAR(1)      NOT NULL DEFAULT 'N',   
    conf_volunteer CHAR(1)      NOT NULL DEFAULT 'N',  
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_sessions_conf_student   CHECK (conf_student IN ('Y', 'N')),
    CONSTRAINT chk_sessions_conf_volunteer CHECK (conf_volunteer IN ('Y', 'N')),
    CONSTRAINT chk_sessions_times          CHECK (end_time IS NULL OR end_time > start_time),
    CONSTRAINT chk_sessions_booked         CHECK (status IN ('Pending', 'Cancelled') OR (session_date IS NOT NULL AND start_time IS NOT NULL)),
    CONSTRAINT chk_sessions_mode           CHECK (support_mode IN ('Online', 'Face-to-face')),
    CONSTRAINT fk_sessions_request   FOREIGN KEY (request_id)   REFERENCES requests (id),
    CONSTRAINT fk_sessions_user      FOREIGN KEY (user_id)      REFERENCES users (id),
    CONSTRAINT fk_sessions_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (id)
);

CREATE TABLE feedbacks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    session_id   INT          NOT NULL,
    user_id      INT          NOT NULL, 
    volunteer_id INT          NOT NULL, 
    rating       TINYINT      NOT NULL, 
    comments     VARCHAR(500) NULL,
    submitted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_feedbacks_session   UNIQUE (session_id),
    CONSTRAINT chk_feedbacks_rating   CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_feedbacks_session   FOREIGN KEY (session_id)   REFERENCES sessions (id),
    CONSTRAINT fk_feedbacks_user      FOREIGN KEY (user_id)      REFERENCES users (id),
    CONSTRAINT fk_feedbacks_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (id)
);

CREATE TABLE history (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    session_id          INT          NOT NULL,
    user_id             INT          NOT NULL,
    volunteer_id        INT          NOT NULL,
    feedback_id         INT          NULL,
    final_status        VARCHAR(20)  NOT NULL,
    completion_time     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancellation_reason VARCHAR(500) NULL,
    CONSTRAINT uq_history_session   UNIQUE (session_id),
    CONSTRAINT fk_history_session   FOREIGN KEY (session_id)   REFERENCES sessions (id),
    CONSTRAINT fk_history_user      FOREIGN KEY (user_id)      REFERENCES users (id),
    CONSTRAINT fk_history_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (id),
    CONSTRAINT fk_history_feedback  FOREIGN KEY (feedback_id)  REFERENCES feedbacks (id)
);

CREATE TABLE notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    message     VARCHAR(500) NOT NULL,
    notice_type VARCHAR(50)  NOT NULL,
    is_read     CHAR(1)      NOT NULL DEFAULT 'N',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_unread (user_id, is_read),
    CONSTRAINT chk_notifications_read CHECK (is_read IN ('Y', 'N')),
    CONSTRAINT fk_notifications_user  FOREIGN KEY (user_id) REFERENCES users (id)
);

USE support_system;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS student_id VARCHAR(30) NULL AFTER id,
    ADD COLUMN IF NOT EXISTS cover_photo_url VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS education VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS skills VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS support_experience VARCHAR(1000) NULL,
    ADD COLUMN IF NOT EXISTS failed_attempts INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL,
    ADD COLUMN IF NOT EXISTS remember_token_hash CHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS remember_expires DATETIME NULL,
    ADD COLUMN IF NOT EXISTS reset_code VARCHAR(6) NULL,
    ADD COLUMN IF NOT EXISTS reset_expiry DATETIME NULL;

UPDATE users
SET student_id = CONCAT('USER', id)
WHERE student_id IS NULL OR student_id = '';

ALTER TABLE users
    MODIFY student_id VARCHAR(30) NOT NULL;

SET @index_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'uq_users_student_id'
);
SET @sql := IF(@index_exists = 0,
    'ALTER TABLE users ADD CONSTRAINT uq_users_student_id UNIQUE (student_id)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
