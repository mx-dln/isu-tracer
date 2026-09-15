ALTER TABLE `survey_questions`
    MODIFY COLUMN `type` ENUM('text','long_text','number','date','single_choice','multiple_choice','dropdown','likert','yes_no','linear_scale','rating','time','image_upload') NOT NULL DEFAULT 'text';
