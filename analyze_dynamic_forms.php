<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "🔍 DYNAMIC FORM SYSTEM ANALYSIS\n";
    echo "================================\n\n";
    
    // 1. Check forms master table
    echo "📋 FORMS MASTER TABLE (Available Forms)\n";
    echo "----------------------------------------\n";
    $forms = DB::table('forms')->get();
    foreach ($forms as $form) {
        echo "ID: {$form->id} | Name: {$form->name} | Status: {$form->status}\n";
    }
    
    // 2. Check form_master_tbl structure and data
    echo "\n📊 FORM MASTER TABLE (Applications)\n";
    echo "------------------------------------\n";
    $applications = DB::table('form_master_tbl')
        ->join('forms', 'form_master_tbl.form_id', '=', 'forms.id')
        ->select('form_master_tbl.*', 'forms.name as form_name')
        ->orderBy('form_master_tbl.id', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($applications as $app) {
        echo "App ID: {$app->application_id} | Form: {$app->form_name} | Status: {$app->ceo_status}\n";
    }
    
    // 3. Check form_entity table (Dynamic data storage)
    echo "\n🗃️ FORM ENTITY TABLE (Dynamic Data Storage)\n";
    echo "---------------------------------------------\n";
    
    if (Schema::hasTable('form_entity')) {
        $columns = Schema::getColumnListing('form_entity');
        echo "Columns: " . implode(', ', $columns) . "\n\n";
        
        // Get unique parameters used across all forms
        $parameters = DB::table('form_entity')
            ->select('parameter')
            ->distinct()
            ->orderBy('parameter')
            ->get();
            
        echo "Dynamic Parameters Used:\n";
        foreach ($parameters as $param) {
            $count = DB::table('form_entity')->where('parameter', $param->parameter)->count();
            echo "- {$param->parameter} (used {$count} times)\n";
        }
        
        // Show sample data for each form type
        echo "\n📝 SAMPLE DATA BY FORM TYPE\n";
        echo "----------------------------\n";
        
        $formTypes = [1, 6, 8]; // NAC Birth, Cesspool Tanker, Water Tanker
        
        foreach ($formTypes as $formId) {
            $formName = DB::table('forms')->where('id', $formId)->value('name');
            echo "\n{$formName} (Form ID: {$formId}):\n";
            
            $sampleApp = DB::table('form_master_tbl')->where('form_id', $formId)->first();
            if ($sampleApp) {
                $entities = DB::table('form_entity')
                    ->where('form_id', $sampleApp->id)
                    ->get();
                    
                foreach ($entities as $entity) {
                    $value = strlen($entity->value) > 50 ? substr($entity->value, 0, 50) . '...' : $entity->value;
                    echo "  • {$entity->parameter}: {$value}\n";
                }
            } else {
                echo "  No applications found for this form type.\n";
            }
        }
    } else {
        echo "❌ form_entity table does not exist!\n";
    }
    
    // 4. Analyze the dynamic approach
    echo "\n🏗️ DYNAMIC APPROACH ANALYSIS\n";
    echo "-----------------------------\n";
    echo "✅ Single table approach using:\n";
    echo "   • forms: Form definitions/types\n";
    echo "   • form_master_tbl: Application instances\n";
    echo "   • form_entity: Dynamic key-value storage\n\n";
    echo "✅ Benefits:\n";
    echo "   • No need for separate tables per form type\n";
    echo "   • Flexible parameter storage\n";
    echo "   • Easy to add new form types\n";
    echo "   • Unified approval workflow\n\n";
    echo "✅ Key Pattern:\n";
    echo "   • Each form submission creates one form_master_tbl record\n";
    echo "   • Multiple form_entity records store all field data\n";
    echo "   • JSON encoding for array/object data\n";
    echo "   • File paths stored as parameters\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}