UPDATE likert_scales SET lower_bound = 4.20, upper_bound = 5.00 WHERE score = 5;
UPDATE likert_scales SET lower_bound = 3.40, upper_bound = 4.19 WHERE score = 4;
UPDATE likert_scales SET lower_bound = 2.60, upper_bound = 3.39 WHERE score = 3;
UPDATE likert_scales SET lower_bound = 1.80, upper_bound = 2.59 WHERE score = 2;
UPDATE likert_scales SET lower_bound = 1.00, upper_bound = 1.79 WHERE score = 1;
-- Standardize 5-point Likert interpretation ranges across all scale types.
-- 5 = Highly Aligned (4.20-5.00), 4 = Aligned (3.40-4.19),
-- 3 = Moderately Aligned (2.60-3.39), 2 = Weakly Aligned (1.80-2.59),
-- 1 = Not Aligned (1.00-1.79).
