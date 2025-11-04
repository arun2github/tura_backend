-- IMMEDIATE FIX: Update application_id directly in database
-- Run this SQL command in your production database

-- Step 1: Check current state
SELECT 'Before Update:' as status;
SELECT id, user_id, job_id, application_id, status, stage, payment_amount, payment_status
FROM tura_job_applied_status 
WHERE user_id = 20 AND job_id = 1;

-- Step 2: Update the specific record with application_id
UPDATE tura_job_applied_status 
SET application_id = 'TMB-2025-JOB1-0001'
WHERE user_id = 20 AND job_id = 1 AND application_id IS NULL;

-- Step 3: Verify the update
SELECT 'After Update:' as status;
SELECT id, user_id, job_id, application_id, status, stage, payment_amount, payment_status
FROM tura_job_applied_status 
WHERE user_id = 20 AND job_id = 1;

-- Step 4: Fix ALL records without application_id (recommended)
UPDATE tura_job_applied_status 
SET application_id = CONCAT('TMB-2025-JOB', job_id, '-', LPAD(ROW_NUMBER() OVER (PARTITION BY job_id ORDER BY id), 4, '0'))
WHERE application_id IS NULL;

-- Step 5: Final verification - check all records
SELECT 'Final Status:' as status;
SELECT 
    COUNT(*) as total_records,
    COUNT(application_id) as records_with_id,
    COUNT(*) - COUNT(application_id) as records_without_id
FROM tura_job_applied_status;

-- If you want to see the specific record after all fixes:
SELECT 'Specific Record Final State:' as status;
SELECT id, user_id, job_id, application_id, status, stage, payment_amount, payment_status
FROM tura_job_applied_status 
WHERE user_id = 20 AND job_id = 1;