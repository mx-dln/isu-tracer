-- ====================================================================
-- Migration: 010_remove_graduates_2023_to_2025.sql
-- Description: Removes graduates of 2023, 2024, and 2025 and all their
--              associated child records cleanly.
-- ====================================================================

-- 1. Remove notifications for users belonging to 2023-2025 graduates
DELETE FROM `notifications`
WHERE `user_id` IN (
    SELECT `u`.`id` FROM `users` `u`
    JOIN `graduates` `g` ON `g`.`id` = `u`.`graduate_id`
    WHERE `g`.`graduation_year` IN (2023, 2024, 2025)
);

-- 2. Remove login logs for users belonging to 2023-2025 graduates
DELETE FROM `login_logs`
WHERE `user_id` IN (
    SELECT `u`.`id` FROM `users` `u`
    JOIN `graduates` `g` ON `g`.`id` = `u`.`graduate_id`
    WHERE `g`.`graduation_year` IN (2023, 2024, 2025)
);

-- 3. Remove audit logs for users belonging to 2023-2025 graduates
DELETE FROM `audit_logs`
WHERE `user_id` IN (
    SELECT `u`.`id` FROM `users` `u`
    JOIN `graduates` `g` ON `g`.`id` = `u`.`graduate_id`
    WHERE `g`.`graduation_year` IN (2023, 2024, 2025)
);

-- 4. Remove password resets for users belonging to 2023-2025 graduates
DELETE FROM `password_resets`
WHERE `user_id` IN (
    SELECT `u`.`id` FROM `users` `u`
    JOIN `graduates` `g` ON `g`.`id` = `u`.`graduate_id`
    WHERE `g`.`graduation_year` IN (2023, 2024, 2025)
);

-- 5. Remove users belonging to 2023-2025 graduates
DELETE FROM `users`
WHERE `graduate_id` IN (
    SELECT `id` FROM `graduates`
    WHERE `graduation_year` IN (2023, 2024, 2025)
);

-- 6. Remove OTP codes for 2023-2025 graduates
DELETE FROM `otp_codes`
WHERE `graduate_id` IN (
    SELECT `id` FROM `graduates`
    WHERE `graduation_year` IN (2023, 2024, 2025)
);

-- 7. Remove survey invitation targets for 2023-2025 graduates
DELETE FROM `survey_invitation_targets`
WHERE `graduate_id` IN (
    SELECT `id` FROM `graduates`
    WHERE `graduation_year` IN (2023, 2024, 2025)
);

-- 8. Remove survey invitations for 2023-2025 graduates
DELETE FROM `survey_invitations`
WHERE `graduate_id` IN (
    SELECT `id` FROM `graduates`
    WHERE `graduation_year` IN (2023, 2024, 2025)
);

-- 9. Remove survey answers for 2023-2025 graduates
DELETE FROM `survey_answers`
WHERE `response_id` IN (
    SELECT `id` FROM `survey_responses`
    WHERE `graduate_id` IN (
        SELECT `id` FROM `graduates`
        WHERE `graduation_year` IN (2023, 2024, 2025)
    )
);

-- 10. Remove survey responses for 2023-2025 graduates
DELETE FROM `survey_responses`
WHERE `graduate_id` IN (
    SELECT `id` FROM `graduates`
    WHERE `graduation_year` IN (2023, 2024, 2025)
);

-- 11. Remove employment profiles for 2023-2025 graduates
DELETE FROM `employment_profiles`
WHERE `graduate_id` IN (
    SELECT `id` FROM `graduates`
    WHERE `graduation_year` IN (2023, 2024, 2025)
);

-- 12. Remove employment history for 2023-2025 graduates
DELETE FROM `employment_history`
WHERE `graduate_id` IN (
    SELECT `id` FROM `graduates`
    WHERE `graduation_year` IN (2023, 2024, 2025)
);

-- 13. Remove competency responses for 2023-2025 graduates
DELETE FROM `competency_responses`
WHERE `graduate_id` IN (
    SELECT `id` FROM `graduates`
    WHERE `graduation_year` IN (2023, 2024, 2025)
);

-- 14. Remove curriculum feedback for 2023-2025 graduates
DELETE FROM `curriculum_feedback`
WHERE `graduate_id` IN (
    SELECT `id` FROM `graduates`
    WHERE `graduation_year` IN (2023, 2024, 2025)
);

-- 15. Finally, remove graduates of 2023, 2024, and 2025
DELETE FROM `graduates`
WHERE `graduation_year` IN (2023, 2024, 2025);
