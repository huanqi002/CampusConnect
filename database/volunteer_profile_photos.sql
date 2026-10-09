-- Add role-specific photos to existing Campus Connect installations.
USE support_system;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS volunteer_picture_url VARCHAR(255) NULL AFTER picture_url,
    ADD COLUMN IF NOT EXISTS volunteer_cover_photo_url VARCHAR(255) NULL AFTER cover_photo_url;
