-- APPLICATION ID FIX - Production SQL Commands
-- Run these commands on the production database

-- Step 1: Check current state
SELECT 'Current Status' as info;
SELECT 
    COUNT(*) as total_records,
    COUNT(application_id) as records_with_id,
    COUNT(*) - COUNT(application_id) as records_without_id
FROM tura_job_applied_status;

-- Step 2: List records without application_id
SELECT 'Records without application_id:' as info;
SELECT id, user_id, job_id, application_id, status, stage, inserted_at
FROM tura_job_applied_status 
WHERE application_id IS NULL
ORDER BY id;

-- Step 3: Check the specific record mentioned in the issue
SELECT 'Specific record (user_id=20, job_id=1):' as info;
SELECT id, user_id, job_id, application_id, status, stage, inserted_at
FROM tura_job_applied_status 
WHERE user_id = 20 AND job_id = 1;

-- Step 4: Generate application_id for records without one
-- Note: This is a manual approach since we can't call PHP functions in pure SQL
-- The format is: TMB-YYYY-JOB{job_id}-{sequence}

-- For records without application_id, we need to generate them manually
-- or use the PHP fix script

-- You can update specific records manually like this (example):
-- UPDATE tura_job_applied_status 
-- SET application_id = 'TMB-2025-JOB1-0001'
-- WHERE id = 23 AND user_id = 20 AND job_id = 1 AND application_id IS NULL;

-- Step 5: Verify after fix
SELECT 'After fix verification:' as info;
SELECT 
    COUNT(*) as total_records,
    COUNT(application_id) as records_with_id,
    COUNT(*) - COUNT(application_id) as records_without_id
FROM tura_job_applied_status;

-- Step 6: Sample of recent records to verify format
SELECT 'Sample of recent records:' as info;
SELECT id, user_id, job_id, application_id, status, stage
FROM tura_job_applied_status 
WHERE application_id IS NOT NULL
ORDER BY id DESC
LIMIT 10;