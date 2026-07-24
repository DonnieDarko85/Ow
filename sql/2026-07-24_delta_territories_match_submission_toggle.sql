USE my_mondodelleleggende;

ALTER TABLE territories
  ADD COLUMN is_match_submission_enabled TINYINT(1) NOT NULL DEFAULT 1
  AFTER is_active;
