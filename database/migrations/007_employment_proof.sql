ALTER TABLE `employment_profiles`
    ADD COLUMN `proof_image_path` VARCHAR(255) NULL AFTER `job_description`,
    ADD COLUMN `proof_image_url` VARCHAR(500) NULL AFTER `proof_image_path`;
