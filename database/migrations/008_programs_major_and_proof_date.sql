ALTER TABLE `graduates` ADD COLUMN `major` VARCHAR(100) NULL AFTER `program_id`;

UPDATE `programs` SET `code` = 'BAT', `name` = 'Bachelor of Agricultural Technology', `description` = 'Bachelor of Agricultural Technology with majors in Animal Production and Crop Production' WHERE `id` = 1;

UPDATE `programs` SET `code` = 'DAT', `name` = 'Diploma in Agricultural Technology', `description` = 'Diploma in Agricultural Technology under the Institute of Agricultural Technology (IAT)' WHERE `id` = 4;

UPDATE `graduates` SET `major` = 'Crop Production' WHERE `program_id` = 1;

UPDATE `graduates` SET `program_id` = 1, `major` = 'Animal Production' WHERE `program_id` = 2;

UPDATE `graduates` SET `program_id` = 1, `major` = 'Crop Production' WHERE `program_id` = 3;

UPDATE `graduates` SET `major` = NULL WHERE `program_id` = 4;

DELETE FROM `programs` WHERE `id` IN (2, 3);

ALTER TABLE `employment_profiles` ADD COLUMN `proof_uploaded_at` TIMESTAMP NULL AFTER `proof_image_url`;

UPDATE `employment_profiles` SET `proof_uploaded_at` = `updated_at` WHERE (`proof_image_path` IS NOT NULL AND `proof_image_path` <> '') OR (`proof_image_url` IS NOT NULL AND `proof_image_url` <> '');

DELETE FROM `employment_profiles` WHERE `created_at` = '2026-10-21 08:14:52';
