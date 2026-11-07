-- ====================================================================
-- Migration: 009_validation_otp_and_security.sql
-- Description: Adds graduate validation metadata, OTP verification table,
--              phone verification tracking, and security settings.
-- ====================================================================

-- 1. Add validation and phone verification columns to graduates table
ALTER TABLE `graduates`
    ADD COLUMN `phone_verified_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_validated`,
    ADD COLUMN `validated_at` TIMESTAMP NULL DEFAULT NULL AFTER `phone_verified_at`,
    ADD COLUMN `validated_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `validated_at`;

-- Add foreign key for validated_by
ALTER TABLE `graduates`
    ADD CONSTRAINT `fk_graduates_validated_by`
        FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- 2. Create otp_codes table for Semaphore SMS OTP verification
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `graduate_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `action` VARCHAR(50) NOT NULL DEFAULT 'verification',
    `is_used` TINYINT(1) NOT NULL DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_otp_phone` (`phone`),
    KEY `idx_otp_code` (`code`),
    KEY `idx_otp_action` (`action`),
    KEY `idx_otp_expires_at` (`expires_at`),
    CONSTRAINT `fk_otp_codes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_otp_codes_graduate` FOREIGN KEY (`graduate_id`) REFERENCES `graduates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. System settings for auto logout and verification
INSERT INTO `system_settings` (`key`, `value`, `group_name`, `is_encrypted`)
VALUES
    ('auto_logout_minutes', '15', 'security', 0),
    ('require_phone_verification', 'false', 'security', 0)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;
