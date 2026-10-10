-- Non-destructive account-flow patch for the support_system database.
-- schema.sql also rebuilds the legacy CampusConnect demo tables. On an
-- existing installation, use this patch instead of re-importing schema.sql.
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

ALTER TABLE users MODIFY student_id VARCHAR(30) NOT NULL;

SET @student_id_index_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'uq_users_student_id'
);
SET @student_id_index_sql := IF(@student_id_index_exists = 0,
    'ALTER TABLE users ADD CONSTRAINT uq_users_student_id UNIQUE (student_id)',
    'SELECT 1');
PREPARE student_id_index_stmt FROM @student_id_index_sql;
EXECUTE student_id_index_stmt;
DEALLOCATE PREPARE student_id_index_stmt;
