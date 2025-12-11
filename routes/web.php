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
        'registration_number' => 'PDREG2025123456',
        'registration_date' => '2025-12-09',
        'owner_name' => 'John Doe',
        'owner_phone' => '9876543210',
        'owner_email' => 'john@example.com',
        'owner_aadhar_number' => '123456789012',
        'owner_address' => '123 Main Street, City',
        'dog_name' => 'Tiger',
        'dog_breed' => 'German Shepherd',
        'dog_age' => '2',
        'dog_gender' => 'Male',
        'dog_color' => 'Black',
        'dog_weight' => '30',
        'veterinarian_name' => 'Dr. Smith',
        'veterinarian_license' => 'VET12345',
        'vaccination_status' => 'Completed',
        'vaccination_date' => '2025-12-01',
        'fee_paid' => '250',
        'payment_receipt_number' => 'PAY123456',
        'arv_issue_date' => '2025-12-01',
        'valid_upto' => '2026-12-09',
         'logo_path' => null, 
        // 'dog_photo_path' => null,
        // 'owner_photo_path' => null,
    ]);
});

