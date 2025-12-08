<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "🐕 Setting up Pet Dog Registration in Dynamic Forms System\n";
    echo "=========================================================\n\n";
    
    // 1. Add Pet Dog Registration to forms table
    $existingForm = DB::table('forms')->where('name', 'Pet Dog Registration')->first();
    
    if (!$existingForm) {
        $formId = DB::table('forms')->insertGetId([
            'name' => 'Pet Dog Registration',
            'status' => 'active',
            'inserted_at' => now()
        ]);
        echo "✅ Added Pet Dog Registration to forms table with ID: {$formId}\n";
    } else {
        echo "✅ Pet Dog Registration already exists in forms table with ID: {$existingForm->id}\n";
        $formId = $existingForm->id;
    }
    
    // 2. Display form structure
    echo "\n📋 Form Structure:\n";
    echo "- Form ID: {$formId}\n";
    echo "- Form Name: Pet Dog Registration\n";
    echo "- Application ID Pattern: PDR + timestamp\n";
    echo "- Uses dynamic form_entity table for data storage\n";
    
    // 3. Show API endpoints
    echo "\n🔗 API Endpoints:\n";
    echo "- Submit: POST /api/petDogRegistration\n";
    echo "- Get All: GET /api/getAllForms?stage=consumer&form_type={$formId}\n";
    echo "- Get Details: POST /api/getFormDetails (with application_id)\n";
    
    echo "\n📝 Required Fields:\n";
    $requiredFields = [
        'form_id' => $formId,
        'owner_name' => 'string(2-100)',
        'owner_phone' => 'string(10-15)',
        'owner_email' => 'email',
        'owner_address' => 'string(10-300)',
        'owner_aadhar_number' => 'string(12)',
        'dog_name' => 'string(2-50)',
        'dog_breed' => 'string(2-50)',
        'dog_age' => 'integer(1-20)',
        'dog_color' => 'string(2-50)',
        'dog_gender' => 'male|female',
        'dog_weight' => 'numeric(1-100)',
        'vaccination_status' => 'completed|pending',
        'vaccination_date' => 'date',
        'veterinarian_name' => 'string(2-100)',
        'veterinarian_license' => 'string(5-50)',
        'document_list' => 'array (base64 documents)',
        'declaration' => 'string(10-500)'
    ];
    
    foreach ($requiredFields as $field => $type) {
        echo "- {$field}: {$type}\n";
    }
    
    echo "\n📄 Sample Request Body:\n";
    $sampleRequest = [
        'form_id' => $formId,
        'owner_name' => 'John Doe',
        'owner_phone' => '9876543210',
        'owner_email' => 'john@example.com',
        'owner_address' => '123 Main Street, Tura, Meghalaya - 794001',
        'owner_aadhar_number' => '123456789012',
        'dog_name' => 'Buddy',
        'dog_breed' => 'Golden Retriever',
        'dog_age' => 3,
        'dog_color' => 'Golden',
        'dog_gender' => 'male',
        'dog_weight' => 30.5,
        'vaccination_status' => 'completed',
        'vaccination_date' => '2024-12-01',
        'veterinarian_name' => 'Dr. Smith',
        'veterinarian_license' => 'VET12345',
        'document_list' => [
            [
                'name' => 'vaccination_certificate.pdf',
                'data' => 'data:application/pdf;base64,JVBERi0xLjQ...'
            ]
        ],
        'declaration' => 'I hereby declare that all information provided is true and accurate.'
    ];
    
    echo json_encode($sampleRequest, JSON_PRETTY_PRINT);
    
    echo "\n\n✨ Benefits of Dynamic Approach:\n";
    echo "- ✅ No separate table needed\n";
    echo "- ✅ Uses existing getAllForms API\n";
    echo "- ✅ Same approval workflow as other forms\n";
    echo "- ✅ Automatic integration with payment system\n";
    echo "- ✅ Consistent with NAC Birth, Water Tanker, etc.\n";
    echo "- ✅ Easy to modify fields without database changes\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}