ALTER TABLE `survey_questions`
    MODIFY COLUMN `type` ENUM('text','long_text','number','date','single_choice','multiple_choice','dropdown','likert','yes_no','linear_scale','rating','time') NOT NULL DEFAULT 'text';

ALTER TABLE `survey_questions`
    ADD COLUMN `validation` JSON NULL AFTER `likert_scale`;

ALTER TABLE `surveys`
    ADD COLUMN `require_invitation` TINYINT(1) NOT NULL DEFAULT 1 AFTER `tracer_year`,
    ADD COLUMN `confirmation_message` TEXT NULL AFTER `description`,
    ADD COLUMN `invitation_expiry_days` INT NOT NULL DEFAULT 30 AFTER `require_invitation`;

CREATE TABLE `survey_invitations` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `survey_id`     BIGINT UNSIGNED NOT NULL,
    `graduate_id`   BIGINT UNSIGNED NULL,
    `token_hash`    CHAR(64) NOT NULL,
    `status`        ENUM('pending','opened','completed','expired','revoked') NOT NULL DEFAULT 'pending',
    `expires_at`    DATETIME NULL,
    `opened_at`     DATETIME NULL,
    `completed_at`  DATETIME NULL,
    `sent_at`       DATETIME NULL,
    `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_survey_invitations_token` (`token_hash`),
    KEY `idx_survey_invitations_survey` (`survey_id`),
    KEY `idx_survey_invitations_graduate` (`graduate_id`),
    KEY `idx_survey_invitations_status` (`status`),
    KEY `idx_survey_invitations_expires` (`expires_at`),
    UNIQUE KEY `uq_survey_invitation_graduate` (`survey_id`, `graduate_id`),
    CONSTRAINT `fk_survey_invitations_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_survey_invitations_graduate` FOREIGN KEY (`graduate_id`) REFERENCES `graduates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `survey_responses`
    ADD COLUMN `invitation_id` BIGINT UNSIGNED NULL AFTER `graduate_id`,
    ADD KEY `idx_survey_responses_invitation` (`invitation_id`),
    ADD CONSTRAINT `fk_survey_responses_invitation` FOREIGN KEY (`invitation_id`) REFERENCES `survey_invitations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- Phase 13: survey invitations, new question types (linear_scale, rating, time), and invitation link on responses.