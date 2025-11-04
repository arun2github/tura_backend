<?php
require_once 'vendor/autoload.php';

// Load Laravel environment
try {
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
} catch (Exception $e) {
    echo "❌ Laravel bootstrap error: " . $e->getMessage() . "\n";
    exit(1);
}

use App\Models\JobAppliedStatus;
use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\DB;

echo "=== PRODUCTION DATABASE FIX FOR APPLICATION_ID ===\n";
echo "🔄 Fixing application_id for user_id=20, job_id=1...\n";
echo "--------------------------------------------------\n";

try {
    // Step 1: Check the specific record
    echo "📊 Checking current state of record (user_id=20, job_id=1):\n";
    
    $record = JobAppliedStatus::where(['user_id' => 20, 'job_id' => 1])->first();
    
    if (!$record) {
        echo "❌ Record not found for user_id=20, job_id=1\n";
        exit(1);
    }
    
    echo "✅ Record found:\n";
    echo "- ID: {$record->id}\n";
    echo "- User ID: {$record->user_id}\n";
    echo "- Job ID: {$record->job_id}\n";
    echo "- Application ID: " . ($record->application_id ?: "NULL") . "\n";
    echo "- Status: {$record->status}\n";
    echo "- Stage: {$record->stage}\n";
    
    // Step 2: Generate and update application_id if missing
    if (!$record->application_id) {
        echo "\n🔧 Generating application_id...\n";
        
        $controller = new JobController();
        $newApplicationId = $controller->generateApplicationId($record->job_id);
        
        echo "Generated ID: {$newApplicationId}\n";
        
        // Update the record
        $record->application_id = $newApplicationId;
        $record->save();
        
        echo "✅ Record updated successfully!\n";
        
        // Verify the update
        $updatedRecord = JobAppliedStatus::find($record->id);
        echo "✅ Verification - New application_id: {$updatedRecord->application_id}\n";
        
    } else {
        echo "✅ Record already has application_id: {$record->application_id}\n";
    }
    
    // Step 3: Check all records without application_id and fix them
    echo "\n🔍 Checking all records without application_id...\n";
    
    $recordsWithoutId = JobAppliedStatus::whereNull('application_id')->get();
    echo "Found {$recordsWithoutId->count()} records without application_id\n";
    
    if ($recordsWithoutId->count() > 0) {
        echo "\n🔧 Fixing all records without application_id...\n";
        
        foreach ($recordsWithoutId as $rec) {
            if ($rec->job_id) {
                $applicationId = $controller->generateApplicationId($rec->job_id);
                $rec->application_id = $applicationId;
                $rec->save();
                echo "✅ Fixed record ID #{$rec->id} (user_id: {$rec->user_id}, job_id: {$rec->job_id}): {$applicationId}\n";
            }
        }
    }
    
    // Step 4: Final verification
    echo "\n🎯 Final verification for user_id=20, job_id=1:\n";
    $finalRecord = JobAppliedStatus::where(['user_id' => 20, 'job_id' => 1])->first();
    
    echo "- Record ID: {$finalRecord->id}\n";
    echo "- Application ID: {$finalRecord->application_id}\n";
    echo "- Status: {$finalRecord->status}\n";
    echo "- Stage: {$finalRecord->stage}\n";
    
    // Step 5: Test the payment details query
    echo "\n💰 Testing payment details query:\n";
    
    $paymentQuery = "
        SELECT 
            jas.id,
            jas.application_id,
            jas.user_id,
            jas.job_id,
            jas.status,
            jas.stage,
            jas.payment_amount,
            jas.payment_status,
            jas.payment_transaction_id,
            jas.payment_date,
            jpd.category as user_category,
            tjp.fee_general,
            tjp.fee_sc_st,
            tjp.fee_obc,
            tjp.job_title_department
        FROM tura_job_applied_status jas
        LEFT JOIN tura_job_personal_details jpd ON jas.user_id = jpd.user_id AND jas.job_id = jpd.job_id
        LEFT JOIN tura_job_postings tjp ON jas.job_id = tjp.id
        WHERE jas.user_id = 20 AND jas.job_id = 1
    ";
    
    $paymentData = DB::select($paymentQuery);
    
    if ($paymentData) {
        $data = $paymentData[0];
        echo "✅ Payment data found:\n";
        echo "- Application ID: " . ($data->application_id ?: "NULL") . "\n";
        echo "- Payment Amount: " . ($data->payment_amount ?: "NULL") . "\n";
        echo "- Payment Status: " . ($data->payment_status ?: "NULL") . "\n";
        echo "- User Category: " . ($data->user_category ?: "NULL") . "\n";
        echo "- Job Title: " . ($data->job_title_department ?: "NULL") . "\n";
    } else {
        echo "❌ No payment data found\n";
    }
    
    echo "\n============================================================\n";
    echo "🎯 PRODUCTION FIX COMPLETE!\n";
    echo "✅ Application ID has been generated and saved\n";
    echo "✅ All records without application_id have been fixed\n";
    echo "🚀 The getApplicationProgress API should now return application_id\n";
    echo "\n📋 NEXT STEP: Test the API again!\n";

} catch (Exception $e) {
    echo "❌ Error during production fix: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}