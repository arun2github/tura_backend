<?php

/**
 * Update existing personal details records to add default block values
 * Run this script once after adding block columns
 */

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Starting update of existing personal details records...\n";

    // Get all existing records that don't have block values
    $records = DB::table('tura_job_personal_details')
        ->whereNull('present_block')
        ->orWhereNull('permanent_block')
        ->get();

    echo "Found " . count($records) . " records to update.\n";

    $updated = 0;
    foreach ($records as $record) {
        // Update each record with default block values
        // You can customize these default values based on your requirements
        DB::table('tura_job_personal_details')
            ->where('id', $record->id)
            ->update([
                'present_block' => $record->present_block ?? 'Block 1', // Default value
                'permanent_block' => $record->permanent_block ?? 'Block 1', // Default value
                'updated_at' => now()
            ]);
        
        $updated++;
        echo "Updated record ID: {$record->id}\n";
    }

    echo "Successfully updated {$updated} records!\n";
    echo "All existing personal details now have block information.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}