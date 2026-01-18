<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\JobPaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/payment/{id}', [PaymentController::class, 'payment']);

// Job Payment Route (same pattern as PaymentController)

// Test route for Pet Dog Certificate preview
Route::get('/test-pet-dog-certificate', function () {
    return view('pdf.pet_dog_certificate', [
        'registration_number' => 'PDR-2025-001',
        'pet_tag_number' => 'TMB-001',
        'registration_date' => '2025-12-15',
        'owner_name' => 'John Doe',
        'owner_phone' => '9876543210',
        'owner_email' => 'johndoe@example.com',
        'owner_aadhar_number' => '123456789012',
        'owner_address' => '123 Main Street, Riverside Colony',
        'ward_no' => 'Ward 5',
        'district' => 'West Garo Hills',
        'pincode' => '794001',
        'dog_name' => 'Bruno',
        'dog_breed' => 'German Shepherd',
        'dog_age' => '2',
        'dog_age_unit' => 'years',
        'dog_gender' => 'Male',
        'dog_color' => 'Brown & Black',
        'dog_weight' => '28',
        'vaccination_status' => 'Completed',
        'vaccination_date' => '2025-12-01',
        'total_fee' => '250',
        'pet_photo_path' => null,
        'owner_photo_path' => null,
    ]);
});

