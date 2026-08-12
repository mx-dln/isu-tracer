CREATE TABLE `survey_invitation_targets` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `survey_id`   BIGINT UNSIGNED NOT NULL,
    `graduate_id` BIGINT UNSIGNED NOT NULL,
    `selected_by` BIGINT UNSIGNED NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_invitation_targets_survey_grad` (`survey_id`, `graduate_id`),
    KEY `idx_invitation_targets_survey` (`survey_id`),
    KEY `idx_invitation_targets_graduate` (`graduate_id`),
    CONSTRAINT `fk_invitation_targets_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_invitation_targets_graduate` FOREIGN KEY (`graduate_id`) REFERENCES `graduates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_invitation_targets_user` FOREIGN KEY (`selected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_logs` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `survey_id`           BIGINT UNSIGNED NOT NULL,
    `invitation_id`       BIGINT UNSIGNED NULL,
    `graduate_id`         BIGINT UNSIGNED NULL,
    `channel`             ENUM('email','sms') NOT NULL,
    `recipient`           VARCHAR(191) NOT NULL,
    `status`              ENUM('sent','failed','skipped','simulated') NOT NULL DEFAULT 'sent',
    `provider_message_id` VARCHAR(255) NULL,
    `error_message`       VARCHAR(500) NULL,
    `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notification_logs_survey` (`survey_id`),
    KEY `idx_notification_logs_graduate` (`graduate_id`),
    KEY `idx_notification_logs_invitation` (`invitation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;