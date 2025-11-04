<?php
// Simple database check and manual SQL commands
echo "=== SIMPLE DATABASE FIX FOR APPLICATION_ID ===\n";
echo "🔄 Manual SQL commands to fix the issue...\n";
echo "--------------------------------------------------\n";

echo "📋 Run these SQL commands directly in your database:\n\n";

echo "1️⃣ Check current state:\n";
echo "SELECT id, user_id, job_id, application_id, status, stage \n";
echo "FROM tura_job_applied_status \n";
echo "WHERE user_id = 20 AND job_id = 1;\n\n";

echo "2️⃣ Update application_id for the specific record:\n";
echo "UPDATE tura_job_applied_status \n";
echo "SET application_id = 'TMB-2025-JOB1-0001' \n";
echo "WHERE user_id = 20 AND job_id = 1 AND application_id IS NULL;\n\n";

echo "3️⃣ Verify the update:\n";
echo "SELECT id, user_id, job_id, application_id, status, stage \n";
echo "FROM tura_job_applied_status \n";
echo "WHERE user_id = 20 AND job_id = 1;\n\n";

echo "4️⃣ Fix all records without application_id (optional):\n";
echo "UPDATE tura_job_applied_status \n";
echo "SET application_id = CONCAT('TMB-2025-JOB', job_id, '-', LPAD(id, 4, '0')) \n";
echo "WHERE application_id IS NULL;\n\n";

echo "5️⃣ Verify all records have application_id:\n";
echo "SELECT COUNT(*) as total_records, \n";
echo "       COUNT(application_id) as records_with_id, \n";
echo "       COUNT(*) - COUNT(application_id) as records_without_id \n";
echo "FROM tura_job_applied_status;\n\n";

echo "============================================================\n";
echo "🎯 AFTER RUNNING THESE SQL COMMANDS:\n";
echo "✅ The database will have application_id set\n";
echo "🚀 Test your Postman API again\n";
echo "\n📋 IF STILL SHOWING NULL IN API RESPONSE:\n";
echo "1. Check if code changes were deployed to production\n";
echo "2. Clear any application cache\n";
echo "3. Verify you're testing against the correct database\n";
echo "4. Check if there are multiple environments (staging vs production)\n";

// Also let's create a test to check if our code changes are active
echo "\n🔧 QUICK TEST: Check if code changes are deployed\n";
echo "Look for this line in your JobController.php getApplicationProgress method:\n";
echo "if (!\$applicationStatus->application_id) {\n";
echo "    \$applicationStatus->application_id = \$this->generateApplicationId(\$jobId);\n";
echo "    \$applicationStatus->save();\n";
echo "}\n\n";

echo "If this code is NOT in your production file, that's why it's still returning null!\n";
?>