-- Migration 011: Add job_position to graduates table

ALTER TABLE `graduates`
    ADD COLUMN `job_position` VARCHAR(191) NULL AFTER `address`;

-- Backfill job_position from active employment_profiles if available
UPDATE `graduates` g
JOIN `employment_profiles` ep ON ep.graduate_id = g.id AND ep.is_current = 1 AND ep.deleted_at IS NULL
SET g.job_position = ep.job_title
WHERE ep.job_title IS NOT NULL AND ep.job_title <> '' AND (g.job_position IS NULL OR g.job_position = '');
