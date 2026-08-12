-- =====================================================================
-- ISU-Cauayan IAT Tracer Study - Database Schema v1.0
-- Engine: MySQL 8+ / MariaDB 10.6+  (InnoDB, utf8mb4)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------
-- AUTHENTICATION & USERS
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(60)  NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id`                 BIGINT UNSIGNED NOT NULL,
    `graduate_id`             BIGINT UNSIGNED NULL,
    `name`                    VARCHAR(191) NOT NULL,
    `email`                   VARCHAR(191) NOT NULL,
    `password`                VARCHAR(255) NOT NULL,
    `avatar`                  VARCHAR(255) NULL,
    `is_active`               TINYINT(1) NOT NULL DEFAULT 1,
    `must_change_password`    TINYINT(1) NOT NULL DEFAULT 0,
    `last_login_at`           TIMESTAMP NULL,
    `password_reset_token`    VARCHAR(100) NULL,
    `password_reset_expires`  TIMESTAMP NULL,
    `created_at`              TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`              TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role_id`),
    KEY `idx_users_graduate` (`graduate_id`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `token`      VARCHAR(100) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `used_at`    TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_password_resets_token` (`token`),
    KEY `idx_password_resets_user` (`user_id`),
    CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- ACADEMIC STRUCTURE
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `programs`;
CREATE TABLE `programs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`        VARCHAR(20) NOT NULL,
    `name`        VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_programs_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `batches`;
CREATE TABLE `batches` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `year`       SMALLINT UNSIGNED NOT NULL,
    `label`      VARCHAR(100) NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_batches_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `graduates`;
CREATE TABLE `graduates` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         BIGINT UNSIGNED NULL,
    `student_number`  VARCHAR(50) NOT NULL,
    `first_name`      VARCHAR(100) NOT NULL,
    `middle_name`     VARCHAR(100) NULL,
    `last_name`       VARCHAR(100) NOT NULL,
    `suffix`          VARCHAR(20) NULL,
    `email`           VARCHAR(191) NULL,
    `contact_number`  VARCHAR(30) NULL,
    `sex`             ENUM('Male','Female') NULL,
    `birth_date`      DATE NULL,
    `civil_status`    ENUM('Single','Married','Widowed','Separated') NULL,
    `address`         TEXT NULL,
    `municipality`    VARCHAR(100) NULL,
    `province`        VARCHAR(100) NULL,
    `program_id`      BIGINT UNSIGNED NOT NULL,
    `batch_id`        BIGINT UNSIGNED NOT NULL,
    `graduation_year` SMALLINT UNSIGNED NULL,
    `is_validated`    TINYINT(1) NOT NULL DEFAULT 0,
    `is_demo`         TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_graduates_student_number` (`student_number`),
    KEY `idx_graduates_program` (`program_id`),
    KEY `idx_graduates_batch` (`batch_id`),
    KEY `idx_graduates_user` (`user_id`),
    KEY `idx_graduates_name` (`last_name`, `first_name`),
    CONSTRAINT `fk_graduates_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_graduates_batch`   FOREIGN KEY (`batch_id`)   REFERENCES `batches` (`id`)   ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- SURVEY SYSTEM
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `surveys`;
CREATE TABLE `surveys` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `status`      ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
    `start_date`  DATE NULL,
    `end_date`    DATE NULL,
    `tracer_year` SMALLINT UNSIGNED NULL,
    `created_by`  BIGINT UNSIGNED NULL,
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_surveys_status` (`status`),
    CONSTRAINT `fk_surveys_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `survey_sections`;
CREATE TABLE `survey_sections` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `survey_id`   BIGINT UNSIGNED NOT NULL,
    `title`       VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_survey_sections_survey` (`survey_id`),
    CONSTRAINT `fk_survey_sections_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `survey_questions`;
CREATE TABLE `survey_questions` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `survey_id`     BIGINT UNSIGNED NOT NULL,
    `section_id`    BIGINT UNSIGNED NULL,
    `question_key`  VARCHAR(100) NULL,
    `question_text` TEXT NOT NULL,
    `help_text`     TEXT NULL,
    `type`          ENUM('text','long_text','number','date','single_choice','multiple_choice','dropdown','likert','yes_no') NOT NULL DEFAULT 'text',
    `likert_scale`  TINYINT UNSIGNED NULL,
    `is_required`   TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`    INT NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_survey_questions_survey` (`survey_id`),
    KEY `idx_survey_questions_section` (`section_id`),
    CONSTRAINT `fk_survey_questions_survey`  FOREIGN KEY (`survey_id`)  REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_survey_questions_section` FOREIGN KEY (`section_id`) REFERENCES `survey_sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `survey_options`;
CREATE TABLE `survey_options` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `question_id`  BIGINT UNSIGNED NOT NULL,
    `option_text`  VARCHAR(255) NOT NULL,
    `option_value` VARCHAR(100) NULL,
    `sort_order`   INT NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_survey_options_question` (`question_id`),
    CONSTRAINT `fk_survey_options_question` FOREIGN KEY (`question_id`) REFERENCES `survey_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `survey_responses`;
CREATE TABLE `survey_responses` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `survey_id`    BIGINT UNSIGNED NOT NULL,
    `graduate_id`  BIGINT UNSIGNED NOT NULL,
    `status`       ENUM('draft','submitted') NOT NULL DEFAULT 'submitted',
    `ip_address`   VARCHAR(45) NULL,
    `user_agent`   VARCHAR(255) NULL,
    `submitted_at` TIMESTAMP NULL,
    `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_survey_graduate` (`survey_id`, `graduate_id`),
    KEY `idx_survey_responses_graduate` (`graduate_id`),
    CONSTRAINT `fk_survey_responses_survey`   FOREIGN KEY (`survey_id`)   REFERENCES `surveys` (`id`)  ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_survey_responses_graduate` FOREIGN KEY (`graduate_id`) REFERENCES `graduates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `survey_answers`;
CREATE TABLE `survey_answers` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `response_id`    BIGINT UNSIGNED NOT NULL,
    `question_id`    BIGINT UNSIGNED NOT NULL,
    `answer_value`   TEXT NULL,
    `answer_options` TEXT NULL COMMENT 'JSON array for multiple-choice answers',
    `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_answer_response_question` (`response_id`, `question_id`),
    KEY `idx_survey_answers_question` (`question_id`),
    CONSTRAINT `fk_survey_answers_response` FOREIGN KEY (`response_id`) REFERENCES `survey_responses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_survey_answers_question` FOREIGN KEY (`question_id`) REFERENCES `survey_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- EMPLOYMENT
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `employment_sectors`;
CREATE TABLE `employment_sectors` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(191) NOT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_employment_sectors_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `employment_profiles`;
CREATE TABLE `employment_profiles` (
    `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `graduate_id`            BIGINT UNSIGNED NOT NULL,
    `status`                 ENUM('employed','self_employed','unemployed','further_studies') NOT NULL DEFAULT 'unemployed',
    `job_title`              VARCHAR(191) NULL,
    `employer`               VARCHAR(191) NULL,
    `sector_id`              BIGINT UNSIGNED NULL,
    `sector_other`           VARCHAR(191) NULL,
    `employment_type`        ENUM('regular','contractual','temporary','casual','part_time','self_employed') NULL,
    `work_location`          VARCHAR(191) NULL,
    `date_hired`             DATE NULL,
    `first_employment_date`  DATE NULL,
    `salary_range`           VARCHAR(100) NULL,
    `job_description`        TEXT NULL,
    `is_related_to_program`  TINYINT(1) NULL COMMENT '1 = yes, 0 = no, NULL = not indicated',
    `job_relevance_rating`   TINYINT UNSIGNED NULL COMMENT '1-5 Likert job relevance',
    `is_current`             TINYINT(1) NOT NULL DEFAULT 1,
    `is_demo`                TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`             TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_employment_profiles_graduate` (`graduate_id`),
    KEY `idx_employment_profiles_sector` (`sector_id`),
    KEY `idx_employment_profiles_status` (`status`),
    CONSTRAINT `fk_employment_profiles_graduate` FOREIGN KEY (`graduate_id`) REFERENCES `graduates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_employment_profiles_sector`   FOREIGN KEY (`sector_id`)   REFERENCES `employment_sectors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `employment_history`;
CREATE TABLE `employment_history` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `graduate_id`           BIGINT UNSIGNED NOT NULL,
    `job_title`             VARCHAR(191) NULL,
    `employer`              VARCHAR(191) NULL,
    `sector_id`             BIGINT UNSIGNED NULL,
    `employment_type`       VARCHAR(50) NULL,
    `start_date`            DATE NULL,
    `end_date`              DATE NULL,
    `is_current`            TINYINT(1) NOT NULL DEFAULT 0,
    `is_related_to_program` TINYINT(1) NULL,
    `notes`                 TEXT NULL,
    `created_at`            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`            TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_employment_history_graduate` (`graduate_id`),
    KEY `idx_employment_history_sector` (`sector_id`),
    CONSTRAINT `fk_employment_history_graduate` FOREIGN KEY (`graduate_id`) REFERENCES `graduates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_employment_history_sector`   FOREIGN KEY (`sector_id`)   REFERENCES `employment_sectors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- COMPETENCIES & CURRICULUM
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `competency_categories`;
CREATE TABLE `competency_categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_competency_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `competencies`;
CREATE TABLE `competencies` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `name`        VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_competencies_category` (`category_id`),
    CONSTRAINT `fk_competencies_category` FOREIGN KEY (`category_id`) REFERENCES `competency_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `competency_responses`;
CREATE TABLE `competency_responses` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `graduate_id`  BIGINT UNSIGNED NOT NULL,
    `competency_id` BIGINT UNSIGNED NOT NULL,
    `rating`       TINYINT UNSIGNED NOT NULL COMMENT '1-5 rating',
    `submitted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_competency_response_grad_comp` (`graduate_id`, `competency_id`),
    KEY `idx_competency_responses_competency` (`competency_id`),
    CONSTRAINT `fk_competency_responses_graduate`   FOREIGN KEY (`graduate_id`)   REFERENCES `graduates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_competency_responses_competency` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `curriculum_questions`;
CREATE TABLE `curriculum_questions` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `question_text` TEXT NOT NULL,
    `description`   TEXT NULL,
    `sort_order`    INT NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `curriculum_feedback`;
CREATE TABLE `curriculum_feedback` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `graduate_id`  BIGINT UNSIGNED NOT NULL,
    `question_id`  BIGINT UNSIGNED NOT NULL,
    `rating`       TINYINT UNSIGNED NOT NULL COMMENT '1-5 Likert rating',
    `comment`      TEXT NULL,
    `submitted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_curriculum_feedback_grad_q` (`graduate_id`, `question_id`),
    KEY `idx_curriculum_feedback_question` (`question_id`),
    CONSTRAINT `fk_curriculum_feedback_graduate` FOREIGN KEY (`graduate_id`) REFERENCES `graduates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_curriculum_feedback_question` FOREIGN KEY (`question_id`) REFERENCES `curriculum_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- FORECASTING
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `forecast_models`;
CREATE TABLE `forecast_models` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(191) NOT NULL,
    `method`            VARCHAR(50) NOT NULL DEFAULT 'linear_regression',
    `indicator`         VARCHAR(60) NOT NULL COMMENT 'employment_rate, job_relevance, time_to_employment, sector_distribution',
    `indicator_label`   VARCHAR(191) NULL,
    `historical_start`  SMALLINT UNSIGNED NULL,
    `historical_end`    SMALLINT UNSIGNED NULL,
    `forecast_start`    SMALLINT UNSIGNED NULL,
    `forecast_end`      SMALLINT UNSIGNED NULL,
    `observations`      INT NULL,
    `slope`             DOUBLE NULL,
    `intercept`         DOUBLE NULL,
    `r_squared`         DOUBLE NULL,
    `parameters`        JSON NULL,
    `warnings`          TEXT NULL,
    `is_demo`           TINYINT(1) NOT NULL DEFAULT 0,
    `created_by`        BIGINT UNSIGNED NULL,
    `created_at`        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_forecast_models_indicator` (`indicator`),
    CONSTRAINT `fk_forecast_models_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `forecast_results`;
CREATE TABLE `forecast_results` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `model_id`    BIGINT UNSIGNED NOT NULL,
    `year`        SMALLINT UNSIGNED NOT NULL,
    `is_forecast` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = historical, 1 = projected',
    `value`       DOUBLE NULL,
    `lower_bound` DOUBLE NULL,
    `upper_bound` DOUBLE NULL,
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_forecast_model_year` (`model_id`, `year`),
    CONSTRAINT `fk_forecast_results_model` FOREIGN KEY (`model_id`) REFERENCES `forecast_models` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- NOTIFICATIONS
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `notification_templates`;
CREATE TABLE `notification_templates` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type`       VARCHAR(60) NOT NULL,
    `channel`    ENUM('in_app','email','both') NOT NULL DEFAULT 'in_app',
    `subject`    VARCHAR(191) NULL,
    `body`       TEXT NOT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_notification_templates_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       BIGINT UNSIGNED NOT NULL,
    `type`          VARCHAR(60) NOT NULL,
    `title`         VARCHAR(191) NOT NULL,
    `message`       TEXT NOT NULL,
    `channel`       ENUM('in_app','email','both') NOT NULL DEFAULT 'in_app',
    `is_read`       TINYINT(1) NOT NULL DEFAULT 0,
    `read_at`       TIMESTAMP NULL,
    `email_sent`    TINYINT(1) NOT NULL DEFAULT 0,
    `email_sent_at` TIMESTAMP NULL,
    `link`          VARCHAR(255) NULL,
    `data`          JSON NULL,
    `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_read` (`user_id`, `is_read`),
    KEY `idx_notifications_created` (`created_at`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `notification_rules`;
CREATE TABLE `notification_rules` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rule_key`    VARCHAR(100) NOT NULL,
    `name`        VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_notification_rules_key` (`rule_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- REPORTS
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type`          VARCHAR(60) NOT NULL,
    `title`         VARCHAR(191) NOT NULL,
    `format`        ENUM('pdf','excel','csv') NOT NULL,
    `filters`       JSON NULL,
    `file_path`     VARCHAR(255) NULL,
    `generated_by`  BIGINT UNSIGNED NULL,
    `generated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reports_type` (`type`),
    CONSTRAINT `fk_reports_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- LOGS
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NULL,
    `action`      VARCHAR(100) NOT NULL,
    `module`      VARCHAR(100) NULL,
    `description` TEXT NULL,
    `ip_address`  VARCHAR(45) NULL,
    `user_agent`  VARCHAR(255) NULL,
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_user` (`user_id`),
    KEY `idx_audit_logs_module` (`module`),
    KEY `idx_audit_logs_created` (`created_at`),
    CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE `login_logs` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NULL,
    `ip_address`     VARCHAR(45) NULL,
    `user_agent`     VARCHAR(255) NULL,
    `status`         ENUM('success','failed') NOT NULL DEFAULT 'failed',
    `failure_reason` VARCHAR(191) NULL,
    `login_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `logout_at`      TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_login_logs_user` (`user_id`),
    KEY `idx_login_logs_ip` (`ip_address`),
    KEY `idx_login_logs_status` (`status`),
    CONSTRAINT `fk_login_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- SYSTEM SETTINGS & LIKERT SCALES
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`          VARCHAR(100) NOT NULL,
    `value`        TEXT NULL,
    `group_name`   VARCHAR(100) NULL,
    `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_system_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `likert_scales`;
CREATE TABLE `likert_scales` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `scale_type`    VARCHAR(60) NOT NULL DEFAULT 'job_relevance',
    `score`         TINYINT UNSIGNED NOT NULL,
    `label`         VARCHAR(100) NOT NULL,
    `interpretation` VARCHAR(191) NOT NULL,
    `lower_bound`   DECIMAL(4,2) NOT NULL,
    `upper_bound`   DECIMAL(4,2) NOT NULL,
    `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_likert_scale_type_score` (`scale_type`, `score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
