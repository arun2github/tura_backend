-- PRODUCTION DATABASE FIX - IMMEDIATE SOLUTION
-- Run these commands to fix the duplicate record issue

-- Check current state
SELECT id, user_id, job_id, application_id, status, stage 
FROM tura_job_applied_status 
WHERE user_id = 20 AND job_id = 1 
ORDER BY id;

-- Delete the bad record (ID 23 with NULL application_id)
DELETE FROM tura_job_applied_status 
WHERE id = 23 AND user_id = 20 AND job_id = 1 AND application_id IS NULL;

-- Update the good record to correct stage
UPDATE tura_job_applied_status 
SET stage = 7, status = 'submitted' 
WHERE id = 28 AND user_id = 20 AND job_id = 1;

-- Verify - should show only one record now
SELECT id, user_id, job_id, application_id, status, stage 
FROM tura_job_applied_status 
WHERE user_id = 20 AND job_id = 1;

-- Add unique constraint to prevent future duplicates
ALTER TABLE tura_job_applied_status 
ADD CONSTRAINT unique_user_job UNIQUE (user_id, job_id);