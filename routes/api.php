<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobPaymentController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api'], function() {
    Route::get('getWardList', [UserController::class, 'getWardList']);
    Route::post('getLocalityList', [UserController::class, 'getLocalityList']);
    
    Route::post('register', [UserController::class, 'register']);
    Route::post('login', [UserController::class, 'login']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::post('profileUpdate', [UserController::class, 'profileUpdate']);
    Route::post('changePassword', [UserController::class, 'changePassword']);
    
    Route::get('/verifyEmail/{token}', [UserController::class, 'verifyEmail']);
    Route::post('testEmail', [UserController::class, 'testEmail']); // Test email functionality
    Route::post('manualVerifyEmail', [UserController::class, 'manualVerifyEmail']); // Manual email verification for admin
    
    Route::post('forgotpassword', [ForgotPasswordController::class, 'forgotPassword']);
    // Display the password reset form
    Route::get('resetpassword/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    
    // Handle the form submission
    Route::post('resetPass/{token}', [ForgotPasswordController::class, 'resetPassword']);

    Route::get('getForms', [FormController::class, 'getForms']);
    Route::post('nacBirth', [FormController::class, 'nacBirth']);
    Route::post('complaintForm', [FormController::class, 'complaintForm']);
    Route::post('waterTankerForm', [FormController::class, 'waterTankerForm']);
    Route::post('cesspoolTanker', [FormController::class, 'cesspoolTanker']);
    Route::post('tradeLicense', [FormController::class, 'tradeLicense']);
    Route::post('newTradeLicense', [FormController::class, 'newTradeLicense']);
    Route::post('nocTelORElc', [FormController::class, 'nocTelORElc']);
    Route::post('bannerAndPoster', [FormController::class, 'bannerAndPoster']);
    Route::post('nocEstablishment', [FormController::class, 'nocEstablishment']);
    
    Route::post('tradeLicenseFee', [FormController::class, 'tradeLicenseFee']);
    
    
    
    Route::post('deathCert', [FormController::class, 'deathCertificate']);
    Route::post('birthCert', [FormController::class, 'birthCertificate']);
    
    Route::post('submitForm', [FormController::class, 'approvedOrRejectForm']);
    
    Route::post('getAllForms', [FormController::class, 'getAllForms']);
    Route::post('getAllFormsPercentage', [FormController::class, 'getFormsPercentageBasedOnDate']);
    
    Route::get('/download/{filename}', [FormController::class, 'downloadFile']);
    
    Route::post('/successData', [PaymentController::class, 'successData']);
    
    // Job Posting APIs - All require JWT authentication
    Route::middleware('auth')->group(function () {
        Route::get('jobs', [JobController::class, 'getJobs']);
        Route::post('getApplicationProgress', [JobController::class, 'getApplicationProgress']);
        Route::post('saveSelectedJobs', [JobController::class, 'saveSelectedJobs']);
        Route::post('savePersonalDetails', [JobController::class, 'savePersonalDetails']);
        Route::post('saveEmploymentDetails', [JobController::class, 'saveEmploymentDetails']);
        Route::post('getEmploymentDetails', [JobController::class, 'getEmploymentDetails']);
        Route::post('saveQualificationDetails', [JobController::class, 'saveQualificationDetails']);
        Route::post('getQualificationDetails', [JobController::class, 'getQualificationDetails']);
        Route::get('getDocumentRequirements', [JobController::class, 'getDocumentRequirements']);
        Route::post('uploadDocuments', [JobController::class, 'uploadDocuments']);
        Route::post('updateDocument', [JobController::class, 'updateDocument']);
        Route::post('deleteDocument', [JobController::class, 'deleteDocument']);
        Route::post('getUploadedDocuments', [JobController::class, 'getUploadedDocuments']);
        Route::post('downloadDocument', [JobController::class, 'downloadDocument']);
        Route::post('getCompleteApplicationDetails', [JobController::class, 'getCompleteApplicationDetails']);
        
        // Job Selection APIs
        Route::get('getAvailableJobsForApplication/{user_id}', [JobController::class, 'getAvailableJobsForApplication']);
        Route::post('startJobApplication', [JobController::class, 'startJobApplication']);
        
        // Job Posting Management APIs
        Route::post('createJobPosting', [JobController::class, 'createJobPosting']);
        Route::get('getAllJobPostings', [JobController::class, 'getAllJobPostings']);
        Route::post('getJobPostingById', [JobController::class, 'getJobPostingById']);
        Route::post('updateJobPosting', [JobController::class, 'updateJobPosting']);
        Route::post('deleteJobPosting', [JobController::class, 'deleteJobPosting']);
        
        // Job Selection APIs (User selects which job to apply for)
        Route::get('getAvailableJobs', [JobController::class, 'getAvailableJobs']);
        Route::post('saveSelectedJob', [JobController::class, 'saveSelectedJob']);
        Route::get('getSelectedJobs', [JobController::class, 'getSelectedJobs']);
        
        // Application ID Generation and Email
        Route::post('generateApplicationIdAndSendEmail', [JobController::class, 'generateApplicationIdAndSendEmail']);
        Route::post('sendPaymentConfirmationEmail', [JobController::class, 'sendPaymentConfirmationEmail']);
        
        // Job Payment APIs
        Route::post('job-payment/initiate', [JobPaymentController::class, 'initiatePayment']);
        Route::get('job-payment/status/{application_id}', [JobPaymentController::class, 'getPaymentStatus']);
    });
    
    // Job Payment Gateway Routes (No authentication required for gateway callbacks)
    Route::get('job-payment-form', [JobPaymentController::class, 'showPaymentForm']);
    Route::post('job-payment/success', [JobPaymentController::class, 'handlePaymentSuccess']);
    Route::post('job-payment/failure', [JobPaymentController::class, 'handlePaymentFailure']);
    
    // File Serving Routes (No authentication required for file access)
    Route::get('files/{filename}', [JobController::class, 'serveFile'])->name('api.files.serve');
    Route::options('files/{filename}', [JobController::class, 'serveFileOptions'])->name('api.files.options');
});
Route::post('command', [UserController::class, 'commandRun']);

Route::get('/test-db', function () {
    echo env('APP_URL');
    try {
        DB::connection()->getPdo();
        return response()->json(['status' => 'Database connection successful']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'Database connection failed', 'error' => $e->getMessage()]);
    }
});

Route::get('/env', function () {
    return [
        'DB_HOST' => env('JWT_SECRET'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'DB_USERNAME' => env('DB_USERNAME'),
        'DB_PASSWORD' => env('DB_PASSWORD'),
    ];
});


