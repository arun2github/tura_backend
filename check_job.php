<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking table structure:\n";
$columns = DB::select("DESCRIBE tura_job_postings");
foreach ($columns as $column) {
    echo "Column: {$column->Field}, Type: {$column->Type}, Null: {$column->Null}, Default: {$column->Default}\n";
}

echo "\nChecking job with ID 2:\n";
$job = DB::table('tura_job_postings')->where('id', 2)->first();

if ($job) {
    echo "Job found:\n";
    foreach ((array)$job as $key => $value) {
        echo "$key: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "Job with ID 2 not found!\n";
}

echo "\nAll jobs in table:\n";
$jobs = DB::table('tura_job_postings')->get();
foreach ($jobs as $job) {
    echo "ID: {$job->id}, Title: " . ($job->job_title_department ?? 'NULL') . "\n";
}