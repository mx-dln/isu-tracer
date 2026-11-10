-- Migration 012: Backfill major for BAT graduates where major is NULL or empty

UPDATE `graduates` g
JOIN `programs` p ON p.id = g.program_id
LEFT JOIN `employment_profiles` ep ON ep.graduate_id = g.id AND ep.is_current = 1 AND ep.deleted_at IS NULL
SET g.major = CASE
    WHEN LOWER(COALESCE(ep.employer, '')) LIKE '%new hope%'
      OR LOWER(COALESCE(ep.job_title, g.job_position, '')) LIKE '%animal%'
      OR LOWER(COALESCE(ep.job_title, g.job_position, '')) LIKE '%livestock%'
      OR LOWER(COALESCE(ep.job_title, g.job_position, '')) LIKE '%poultry%'
      OR LOWER(COALESCE(ep.job_title, g.job_position, '')) LIKE '%swine%'
    THEN 'Animal Production'
    ELSE 'Crop Production'
END
WHERE p.code = 'BAT' AND (g.major IS NULL OR TRIM(g.major) = '');
