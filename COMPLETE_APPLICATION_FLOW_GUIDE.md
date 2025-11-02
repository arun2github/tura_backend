# Complete Job Application Flow with Payment Integration

## 🎯 Overview
Complete end-to-end job application system with stage management, form persistence, payment calculation, and gateway integration.

## 📋 Application Flow Structure

### **Complete User Journey:**
```
1. User clicks "Apply" → Login Required
2. After Login → Stage 0: Job Selection (Dropdown)
3. Stage 1: Personal Details → Auto-save & Progress
4. Stage 2: Employment Details → Auto-save & Progress
5. Stage 3: Qualification Details → Auto-save & Progress
6. Stage 4: Document Upload → Auto-save & Progress
7. Stage 5: Summary & Acknowledgment → Show all details
8. Stage 6: Payment Calculation → Based on category
9. Stage 7: Payment Gateway → Process payment
10. Stage 8: Success → Application completed
```

### **Resume Functionality:**
- User can logout after any stage
- On re-login → Resume from last completed stage
- Skip completed sections automatically
- Maintain all saved data

---

## 🔧 Backend Implementation

### **1. Enhanced JobController Methods**

#### **Stage 0: Get Jobs for Dropdown**
```php
/**
 * Get jobs for dropdown selection in application form
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function getJobsForApplication(Request $request)
{
    try {
        // Get authenticated user from JWT middleware
        $authenticatedUser = $request->input('authenticated_user');
        $userId = $authenticatedUser->id;

        // Get all active jobs with application status for this user
        $jobs = DB::table('tura_job_posting as jp')
            ->leftJoin('tura_job_applied_status as jas', function($join) use ($userId) {
                $join->on('jp.id', '=', 'jas.job_id')
                     ->where('jas.user_id', '=', $userId);
            })
            ->select(
                'jp.id',
                'jp.post_name',
                'jp.department',
                'jp.total_posts',
                'jp.last_date_to_apply',
                'jp.application_fee_general',
                'jp.application_fee_obc',
                'jp.application_fee_sc_st',
                'jas.status as application_status',
                'jas.stage as current_stage',
                'jas.payment_status',
                DB::raw('CASE 
                    WHEN jas.id IS NULL THEN "not_applied"
                    WHEN jas.status = "completed" THEN "completed"
                    WHEN jas.payment_status = "paid" THEN "payment_completed"
                    ELSE "in_progress"
                END as status')
            )
            ->where('jp.status', 'active')
            ->where('jp.last_date_to_apply', '>=', now())
            ->orderBy('jp.created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Jobs retrieved successfully',
            'data' => $jobs
        ], 200);

    } catch (\Exception $e) {
        Log::error('Error retrieving jobs for application: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving jobs'
        ], 500);
    }
}
```

#### **Enhanced Application Progress with Resume Logic**
```php
/**
 * Get application progress with resume capability
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function getApplicationProgressWithResume(Request $request)
{
    try {
        // Get authenticated user from JWT middleware
        $authenticatedUser = $request->input('authenticated_user');
        $userId = $authenticatedUser->id;

        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $jobId = $request->job_id;

        // Get application status
        $applicationStatus = JobAppliedStatus::where([
            'user_id' => $userId,
            'job_id' => $jobId
        ])->first();

        if (!$applicationStatus) {
            // No application started, begin from stage 1
            $applicationStatus = JobAppliedStatus::create([
                'user_id' => $userId,
                'job_id' => $jobId,
                'status' => JobAppliedStatus::STATUSES['draft'],
                'stage' => JobAppliedStatus::STAGES['personal_details'],
                'inserted_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Check completion status of each stage
        $stageCompletion = [
            'personal_details' => $this->isPersonalDetailsCompleted($userId, $jobId),
            'employment_details' => $this->isEmploymentDetailsCompleted($userId, $jobId),
            'qualification_details' => $this->isQualificationDetailsCompleted($userId, $jobId),
            'document_upload' => $this->isDocumentUploadCompleted($userId, $jobId),
        ];

        // Determine next stage to show
        $nextStage = $this->determineNextStage($stageCompletion, $applicationStatus->stage);

        // Get existing data for pre-filling
        $existingData = $this->getExistingApplicationData($userId, $jobId);

        return response()->json([
            'success' => true,
            'message' => 'Application progress retrieved successfully',
            'user_id' => $userId,
            'job_id' => $jobId,
            'application_status' => [
                'status' => $applicationStatus->status,
                'current_stage' => $nextStage,
                'current_stage_name' => array_search($nextStage, JobAppliedStatus::STAGES),
                'payment_status' => $applicationStatus->payment_status ?? 'pending',
                'is_completed' => $applicationStatus->status === JobAppliedStatus::STATUSES['completed'],
            ],
            'stage_completion' => $stageCompletion,
            'existing_data' => $existingData,
            'redirect_to' => [
                'stage' => $nextStage,
                'stage_name' => array_search($nextStage, JobAppliedStatus::STAGES),
                'message' => $this->getStageMessage($nextStage, $stageCompletion)
            ]
        ], 200);

    } catch (\Exception $e) {
        Log::error('Error getting application progress: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving application progress'
        ], 500);
    }
}

/**
 * Check if personal details are completed
 */
private function isPersonalDetailsCompleted($userId, $jobId)
{
    return JobPersonalDetail::where([
        'user_id' => $userId,
        'job_id' => $jobId
    ])->exists();
}

/**
 * Check if employment details are completed
 */
private function isEmploymentDetailsCompleted($userId, $jobId)
{
    return JobEmploymentDetail::where([
        'user_id' => $userId,
        'job_id' => $jobId
    ])->exists();
}

/**
 * Check if qualification details are completed
 */
private function isQualificationDetailsCompleted($userId, $jobId)
{
    return JobQualification::where([
        'user_id' => $userId,
        'job_id' => $jobId
    ])->exists();
}

/**
 * Check if document upload is completed
 */
private function isDocumentUploadCompleted($userId, $jobId)
{
    return JobDocumentUpload::checkMandatoryComplete($userId, $jobId);
}

/**
 * Determine next stage based on completion status
 */
private function determineNextStage($stageCompletion, $currentStage)
{
    if (!$stageCompletion['personal_details']) {
        return JobAppliedStatus::STAGES['personal_details'];
    }
    
    if (!$stageCompletion['employment_details']) {
        return JobAppliedStatus::STAGES['employment_details'];
    }
    
    if (!$stageCompletion['qualification_details']) {
        return JobAppliedStatus::STAGES['qualification_details'];
    }
    
    if (!$stageCompletion['document_upload']) {
        return JobAppliedStatus::STAGES['document_upload'];
    }
    
    // All stages completed, ready for summary
    return 5; // Summary stage
}

/**
 * Get existing application data for pre-filling
 */
private function getExistingApplicationData($userId, $jobId)
{
    return [
        'personal_details' => JobPersonalDetail::where(['user_id' => $userId, 'job_id' => $jobId])->first(),
        'employment_details' => JobEmploymentDetail::where(['user_id' => $userId, 'job_id' => $jobId])->get(),
        'qualification_details' => JobQualification::where(['user_id' => $userId, 'job_id' => $jobId])->get(),
        'document_upload' => JobDocumentUpload::where(['user_id' => $userId, 'job_id' => $jobId])->get(),
    ];
}

/**
 * Get appropriate message for current stage
 */
private function getStageMessage($stage, $stageCompletion)
{
    switch ($stage) {
        case 1:
            return 'Please fill your personal details to continue';
        case 2:
            return 'Please add your employment history';
        case 3:
            return 'Please add your qualification details';
        case 4:
            return 'Please upload required documents';
        case 5:
            return 'Review your application and proceed to payment';
        default:
            return 'Continue with your application';
    }
}
```

#### **Application Summary API**
```php
/**
 * Get complete application summary for acknowledgment
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function getApplicationSummary(Request $request)
{
    try {
        // Get authenticated user from JWT middleware
        $authenticatedUser = $request->input('authenticated_user');
        $userId = $authenticatedUser->id;

        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $jobId = $request->job_id;

        // Get job details
        $job = JobPosting::find($jobId);
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ], 404);
        }

        // Get all application data
        $personalDetails = JobPersonalDetail::where(['user_id' => $userId, 'job_id' => $jobId])->first();
        $employmentDetails = JobEmploymentDetail::where(['user_id' => $userId, 'job_id' => $jobId])->get();
        $qualificationDetails = JobQualification::where(['user_id' => $userId, 'job_id' => $jobId])->get();
        $documentDetails = JobDocumentUpload::where(['user_id' => $userId, 'job_id' => $jobId])->get();

        // Validate all sections are completed
        if (!$personalDetails || $employmentDetails->isEmpty() || $qualificationDetails->isEmpty() || $documentDetails->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Application is incomplete. Please complete all sections.',
                'missing_sections' => [
                    'personal_details' => !$personalDetails,
                    'employment_details' => $employmentDetails->isEmpty(),
                    'qualification_details' => $qualificationDetails->isEmpty(),
                    'document_upload' => $documentDetails->isEmpty(),
                ]
            ], 400);
        }

        // Calculate application fee based on category
        $applicationFee = $this->calculateApplicationFee($job, $personalDetails->category);

        return response()->json([
            'success' => true,
            'message' => 'Application summary retrieved successfully',
            'data' => [
                'job_details' => [
                    'id' => $job->id,
                    'post_name' => $job->post_name,
                    'department' => $job->department,
                    'total_posts' => $job->total_posts,
                    'last_date_to_apply' => $job->last_date_to_apply,
                ],
                'personal_details' => $personalDetails,
                'employment_details' => $employmentDetails,
                'qualification_details' => $qualificationDetails,
                'document_details' => $documentDetails->map(function($doc) {
                    return [
                        'document_type' => $doc->document_type,
                        'is_mandatory' => $doc->is_mandatory,
                        'uploaded_at' => $doc->inserted_at,
                    ];
                }),
                'payment_details' => [
                    'category' => $personalDetails->category,
                    'application_fee' => $applicationFee,
                    'currency' => 'INR',
                ],
                'application_id' => $this->generateApplicationId($userId, $jobId),
            ]
        ], 200);

    } catch (\Exception $e) {
        Log::error('Error getting application summary: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving application summary'
        ], 500);
    }
}

/**
 * Calculate application fee based on job and user category
 */
private function calculateApplicationFee($job, $category)
{
    switch (strtolower($category)) {
        case 'general':
            return $job->application_fee_general ?? 500;
        case 'obc':
            return $job->application_fee_obc ?? 300;
        case 'sc':
        case 'st':
            return $job->application_fee_sc_st ?? 100;
        default:
            return $job->application_fee_general ?? 500;
    }
}

/**
 * Generate unique application ID
 */
private function generateApplicationId($userId, $jobId)
{
    return 'APP' . date('Y') . str_pad($jobId, 3, '0', STR_PAD_LEFT) . str_pad($userId, 4, '0', STR_PAD_LEFT) . rand(100, 999);
}
```

#### **Payment Integration API**
```php
/**
 * Initiate payment for job application
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function initiateApplicationPayment(Request $request)
{
    try {
        // Get authenticated user from JWT middleware
        $authenticatedUser = $request->input('authenticated_user');
        $userId = $authenticatedUser->id;

        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $jobId = $request->job_id;

        // Get job and personal details
        $job = JobPosting::find($jobId);
        $personalDetails = JobPersonalDetail::where(['user_id' => $userId, 'job_id' => $jobId])->first();

        if (!$job || !$personalDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Job or personal details not found'
            ], 404);
        }

        // Calculate fee
        $applicationFee = $this->calculateApplicationFee($job, $personalDetails->category);
        
        // Generate unique transaction ID
        $transactionId = 'TXN' . time() . rand(1000, 9999);
        
        // Create payment record
        $paymentData = [
            'user_id' => $userId,
            'job_id' => $jobId,
            'transaction_id' => $transactionId,
            'amount' => $applicationFee,
            'currency' => 'INR',
            'payment_status' => 'pending',
            'payment_method' => 'razorpay', // or your preferred gateway
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Insert payment record (assuming you have a payments table)
        DB::table('tura_job_payments')->insert($paymentData);

        // Prepare payment gateway data (example for Razorpay)
        $paymentGatewayData = [
            'key' => env('RAZORPAY_KEY_ID'),
            'amount' => $applicationFee * 100, // Convert to paise
            'currency' => 'INR',
            'order_id' => $transactionId,
            'name' => 'Municipal Board Job Application',
            'description' => "Application fee for {$job->post_name}",
            'prefill' => [
                'name' => $personalDetails->full_name,
                'email' => $authenticatedUser->email,
                'contact' => $authenticatedUser->phone_no,
            ],
            'notes' => [
                'user_id' => $userId,
                'job_id' => $jobId,
                'application_type' => 'job_application',
            ],
            'callback_url' => env('APP_URL') . '/api/payment/callback',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated successfully',
            'data' => [
                'transaction_id' => $transactionId,
                'amount' => $applicationFee,
                'currency' => 'INR',
                'payment_gateway_data' => $paymentGatewayData,
                'redirect_url' => env('APP_URL') . '/payment/process/' . $transactionId,
            ]
        ], 200);

    } catch (\Exception $e) {
        Log::error('Error initiating payment: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error initiating payment'
        ], 500);
    }
}

/**
 * Handle payment callback/webhook
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function handlePaymentCallback(Request $request)
{
    try {
        // Verify payment signature (implement based on your gateway)
        $paymentData = $request->all();
        
        // Update payment status
        $transactionId = $paymentData['order_id'] ?? $paymentData['transaction_id'];
        $paymentStatus = $paymentData['status'] === 'success' ? 'paid' : 'failed';
        
        DB::table('tura_job_payments')
            ->where('transaction_id', $transactionId)
            ->update([
                'payment_status' => $paymentStatus,
                'gateway_transaction_id' => $paymentData['payment_id'] ?? null,
                'gateway_response' => json_encode($paymentData),
                'updated_at' => now(),
            ]);

        if ($paymentStatus === 'paid') {
            // Update application status to completed
            $payment = DB::table('tura_job_payments')->where('transaction_id', $transactionId)->first();
            
            JobAppliedStatus::where([
                'user_id' => $payment->user_id,
                'job_id' => $payment->job_id
            ])->update([
                'status' => JobAppliedStatus::STATUSES['completed'],
                'stage' => 6, // Completed stage
                'payment_status' => 'paid',
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully',
                'data' => [
                    'transaction_id' => $transactionId,
                    'status' => 'completed',
                    'redirect_url' => env('FRONTEND_URL') . '/application/success/' . $transactionId,
                ]
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Payment failed',
                'data' => [
                    'transaction_id' => $transactionId,
                    'status' => 'failed',
                ]
            ], 400);
        }

    } catch (\Exception $e) {
        Log::error('Error handling payment callback: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error processing payment callback'
        ], 500);
    }
}
```

---

## 🗃️ Database Schema Updates

### **1. Create Payment Table**
```php
// Migration: create_tura_job_payments_table.php

Schema::create('tura_job_payments', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('job_id');
    $table->string('transaction_id')->unique();
    $table->string('gateway_transaction_id')->nullable();
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('INR');
    $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
    $table->string('payment_method')->nullable();
    $table->json('gateway_response')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
    
    $table->foreign('user_id')->references('id')->on('users');
    $table->foreign('job_id')->references('id')->on('tura_job_posting');
});
```

### **2. Update JobAppliedStatus Model**
```php
// Add to JobAppliedStatus model

const STAGES = [
    'form_selection' => 0,
    'personal_details' => 1,
    'employment_details' => 2,
    'qualification_details' => 3,
    'document_upload' => 4,
    'summary' => 5,
    'payment' => 6,
    'completed' => 7,
];

const PAYMENT_STATUSES = [
    'pending' => 'pending',
    'paid' => 'paid',
    'failed' => 'failed',
    'refunded' => 'refunded',
];

protected $fillable = [
    'user_id',
    'job_id',
    'status',
    'stage',
    'payment_status',
    'inserted_at',
    'updated_at',
];
```

---

## 🛣️ Routes Updates

### **Add New Routes**
```php
// routes/api.php

Route::middleware('auth')->group(function () {
    // Existing job APIs...
    
    // Enhanced application flow APIs
    Route::get('getJobsForApplication', [JobController::class, 'getJobsForApplication']);
    Route::post('getApplicationProgressWithResume', [JobController::class, 'getApplicationProgressWithResume']);
    Route::post('getApplicationSummary', [JobController::class, 'getApplicationSummary']);
    
    // Payment APIs
    Route::post('initiateApplicationPayment', [JobController::class, 'initiateApplicationPayment']);
    Route::post('payment/callback', [JobController::class, 'handlePaymentCallback']);
});
```

---

## 📱 Flutter Web Implementation

### **1. Enhanced API Service**
```dart
// Add to api_service.dart

class ApiService {
  // ... existing methods ...

  // Get jobs for dropdown
  static Future<Map<String, dynamic>> getJobsForApplication() async {
    try {
      final headers = await _getHeaders();
      final response = await http.get(
        Uri.parse('$baseUrl/getJobsForApplication'),
        headers: headers,
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Get application progress with resume capability
  static Future<Map<String, dynamic>> getApplicationProgressWithResume(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/getApplicationProgressWithResume'),
        headers: headers,
        body: json.encode({'job_id': jobId}),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Get application summary
  static Future<Map<String, dynamic>> getApplicationSummary(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/getApplicationSummary'),
        headers: headers,
        body: json.encode({'job_id': jobId}),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Initiate payment
  static Future<Map<String, dynamic>> initiateApplicationPayment(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/initiateApplicationPayment'),
        headers: headers,
        body: json.encode({'job_id': jobId}),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
}
```

### **2. Job Selection Screen**
```dart
// job_selection_screen.dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'application_form_screen.dart';

class JobSelectionScreen extends StatefulWidget {
  const JobSelectionScreen({Key? key}) : super(key: key);

  @override
  _JobSelectionScreenState createState() => _JobSelectionScreenState();
}

class _JobSelectionScreenState extends State<JobSelectionScreen> {
  List<dynamic> jobs = [];
  bool isLoading = true;
  int? selectedJobId;

  @override
  void initState() {
    super.initState();
    _loadJobs();
  }

  Future<void> _loadJobs() async {
    setState(() => isLoading = true);

    final result = await ApiService.getJobsForApplication();

    if (result['success']) {
      setState(() {
        jobs = result['data'];
        isLoading = false;
      });
    } else {
      setState(() => isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Error loading jobs'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Select Job to Apply'),
        backgroundColor: Colors.blue[800],
        foregroundColor: Colors.white,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Available Job Positions',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.blue,
                    ),
                  ),
                  const SizedBox(height: 20),
                  
                  if (jobs.isEmpty)
                    const Center(
                      child: Text(
                        'No jobs available at the moment',
                        style: TextStyle(fontSize: 16),
                      ),
                    )
                  else
                    ...jobs.map((job) => _buildJobCard(job)).toList(),
                ],
              ),
            ),
    );
  }

  Widget _buildJobCard(dynamic job) {
    final status = job['status'];
    final isApplied = status != 'not_applied';
    final canContinue = isApplied && status != 'completed';

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 4,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    job['post_name'] ?? '',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                _buildStatusChip(status),
              ],
            ),
            const SizedBox(height: 8),
            
            Text(
              'Department: ${job['department'] ?? 'N/A'}',
              style: const TextStyle(fontSize: 14, color: Colors.grey),
            ),
            
            Text(
              'Total Posts: ${job['total_posts'] ?? 'N/A'}',
              style: const TextStyle(fontSize: 14, color: Colors.grey),
            ),
            
            Text(
              'Last Date: ${job['last_date_to_apply'] ?? 'N/A'}',
              style: const TextStyle(fontSize: 14, color: Colors.grey),
            ),
            
            const SizedBox(height: 12),
            
            Row(
              children: [
                Text(
                  'Application Fee: ',
                  style: const TextStyle(fontWeight: FontWeight.w500),
                ),
                Text(
                  'General: ₹${job['application_fee_general']}, ',
                  style: const TextStyle(fontSize: 12),
                ),
                Text(
                  'OBC: ₹${job['application_fee_obc']}, ',
                  style: const TextStyle(fontSize: 12),
                ),
                Text(
                  'SC/ST: ₹${job['application_fee_sc_st']}',
                  style: const TextStyle(fontSize: 12),
                ),
              ],
            ),
            
            const SizedBox(height: 16),
            
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (status == 'completed')
                  ElevatedButton(
                    onPressed: () => _viewApplication(job['id']),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green,
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('View Application'),
                  )
                else if (canContinue)
                  ElevatedButton(
                    onPressed: () => _continueApplication(job['id'], job['post_name']),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.orange,
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('Continue Application'),
                  )
                else
                  ElevatedButton(
                    onPressed: () => _startApplication(job['id'], job['post_name']),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue[800],
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('Apply Now'),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusChip(String status) {
    Color color;
    String text;
    
    switch (status) {
      case 'completed':
        color = Colors.green;
        text = 'Completed';
        break;
      case 'payment_completed':
        color = Colors.blue;
        text = 'Payment Done';
        break;
      case 'in_progress':
        color = Colors.orange;
        text = 'In Progress';
        break;
      default:
        color = Colors.grey;
        text = 'Not Applied';
    }

    return Chip(
      label: Text(
        text,
        style: const TextStyle(color: Colors.white, fontSize: 12),
      ),
      backgroundColor: color,
    );
  }

  void _startApplication(int jobId, String jobTitle) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ApplicationFormScreen(
          jobId: jobId,
          jobTitle: jobTitle,
          isNewApplication: true,
        ),
      ),
    );
  }

  void _continueApplication(int jobId, String jobTitle) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ApplicationFormScreen(
          jobId: jobId,
          jobTitle: jobTitle,
          isNewApplication: false,
        ),
      ),
    );
  }

  void _viewApplication(int jobId) {
    // Navigate to application view screen
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ApplicationSummaryScreen(jobId: jobId),
      ),
    );
  }
}
```

### **3. Enhanced Application Form Screen**
```dart
// Enhanced application_form_screen.dart with resume logic

class ApplicationFormScreen extends StatefulWidget {
  final int jobId;
  final String jobTitle;
  final bool isNewApplication;
  
  const ApplicationFormScreen({
    Key? key,
    required this.jobId,
    required this.jobTitle,
    required this.isNewApplication,
  }) : super(key: key);
  
  @override
  _ApplicationFormScreenState createState() => _ApplicationFormScreenState();
}

class _ApplicationFormScreenState extends State<ApplicationFormScreen> {
  int currentStep = 0;
  bool isLoading = false;
  Map<String, bool> stageCompletion = {};
  Map<String, dynamic> existingData = {};
  
  @override
  void initState() {
    super.initState();
    _loadApplicationProgress();
  }
  
  Future<void> _loadApplicationProgress() async {
    setState(() => isLoading = true);
    
    final result = await ApiService.getApplicationProgressWithResume(widget.jobId);
    
    if (result['success']) {
      setState(() {
        currentStep = result['application_status']['current_stage'] - 1; // Convert to 0-based
        stageCompletion = Map<String, bool>.from(result['stage_completion']);
        existingData = result['existing_data'] ?? {};
      });
      
      // Show resume message if continuing application
      if (!widget.isNewApplication && currentStep > 0) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['redirect_to']['message']),
            backgroundColor: Colors.blue,
            duration: const Duration(seconds: 3),
          ),
        );
      }
    }
    
    setState(() => isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Job Application - ${widget.jobTitle}'),
        backgroundColor: Colors.blue[800],
        foregroundColor: Colors.white,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                // Enhanced Progress Indicator with completion status
                _buildEnhancedProgressIndicator(),
                
                // Form Content
                Expanded(
                  child: _buildCurrentStepContent(),
                ),
                
                // Navigation Buttons
                _buildNavigationButtons(),
              ],
            ),
    );
  }

  Widget _buildEnhancedProgressIndicator() {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Stage completion summary
          if (stageCompletion.isNotEmpty)
            Container(
              padding: const EdgeInsets.all(12),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: Colors.blue[50],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.blue[200]!),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _buildCompletionStatus('Personal', stageCompletion['personal_details'] ?? false),
                  _buildCompletionStatus('Employment', stageCompletion['employment_details'] ?? false),
                  _buildCompletionStatus('Qualification', stageCompletion['qualification_details'] ?? false),
                  _buildCompletionStatus('Documents', stageCompletion['document_upload'] ?? false),
                ],
              ),
            ),
          
          // Step indicators
          Row(
            children: [
              _buildStepIndicator(0, 'Personal\nDetails', currentStep >= 0, stageCompletion['personal_details'] ?? false),
              _buildStepConnector(stageCompletion['personal_details'] ?? false),
              _buildStepIndicator(1, 'Employment\nDetails', currentStep >= 1, stageCompletion['employment_details'] ?? false),
              _buildStepConnector(stageCompletion['employment_details'] ?? false),
              _buildStepIndicator(2, 'Qualification\nDetails', currentStep >= 2, stageCompletion['qualification_details'] ?? false),
              _buildStepConnector(stageCompletion['qualification_details'] ?? false),
              _buildStepIndicator(3, 'Document\nUpload', currentStep >= 3, stageCompletion['document_upload'] ?? false),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildCompletionStatus(String label, bool isCompleted) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(
          isCompleted ? Icons.check_circle : Icons.radio_button_unchecked,
          color: isCompleted ? Colors.green : Colors.grey,
          size: 16,
        ),
        const SizedBox(width: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: isCompleted ? Colors.green : Colors.grey,
            fontWeight: isCompleted ? FontWeight.bold : FontWeight.normal,
          ),
        ),
      ],
    );
  }

  Widget _buildStepIndicator(int step, String label, bool isActive, bool isCompleted) {
    return Expanded(
      child: Column(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isCompleted
                  ? Colors.green
                  : isActive
                      ? Colors.blue
                      : Colors.grey[300],
            ),
            child: Center(
              child: isCompleted
                  ? const Icon(Icons.check, color: Colors.white)
                  : Text(
                      '${step + 1}',
                      style: TextStyle(
                        color: isActive ? Colors.white : Colors.grey[600],
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 12,
              color: isActive ? Colors.blue : Colors.grey[600],
              fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
            ),
          ),
        ],
      ),
    );
  }

  // When all sections are complete, show summary instead of next button
  Widget _buildNavigationButtons() {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Previous Button
          currentStep > 0
              ? ElevatedButton(
                  onPressed: () {
                    setState(() => currentStep--);
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.grey[600],
                    foregroundColor: Colors.white,
                  ),
                  child: const Text('Previous'),
                )
              : const SizedBox.shrink(),
          
          // Next/Summary/Submit Button
          ElevatedButton(
            onPressed: isLoading ? null : _handleNextStep,
            style: ElevatedButton.styleFrom(
              backgroundColor: _getAllSectionsCompleted() 
                  ? Colors.green 
                  : Colors.blue[800],
              foregroundColor: Colors.white,
            ),
            child: isLoading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                : Text(_getButtonText()),
          ),
        ],
      ),
    );
  }

  String _getButtonText() {
    if (_getAllSectionsCompleted()) {
      return 'View Summary & Pay';
    } else if (currentStep == 3) {
      return 'Complete Application';
    } else {
      return 'Save & Continue';
    }
  }

  bool _getAllSectionsCompleted() {
    return (stageCompletion['personal_details'] ?? false) &&
           (stageCompletion['employment_details'] ?? false) &&
           (stageCompletion['qualification_details'] ?? false) &&
           (stageCompletion['document_upload'] ?? false);
  }

  Future<void> _handleNextStep() async {
    if (_getAllSectionsCompleted()) {
      // Navigate to summary screen
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => ApplicationSummaryScreen(jobId: widget.jobId),
        ),
      );
      return;
    }

    // Continue with existing save logic...
    // [Previous save logic here]
  }
}
```

### **4. Application Summary Screen**
```dart
// application_summary_screen.dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'payment_screen.dart';

class ApplicationSummaryScreen extends StatefulWidget {
  final int jobId;
  
  const ApplicationSummaryScreen({
    Key? key,
    required this.jobId,
  }) : super(key: key);
  
  @override
  _ApplicationSummaryScreenState createState() => _ApplicationSummaryScreenState();
}

class _ApplicationSummaryScreenState extends State<ApplicationSummaryScreen> {
  bool isLoading = true;
  Map<String, dynamic> summaryData = {};

  @override
  void initState() {
    super.initState();
    _loadSummary();
  }

  Future<void> _loadSummary() async {
    setState(() => isLoading = true);

    final result = await ApiService.getApplicationSummary(widget.jobId);

    if (result['success']) {
      setState(() {
        summaryData = result['data'];
        isLoading = false;
      });
    } else {
      setState(() => isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Error loading summary'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Application Summary'),
        backgroundColor: Colors.blue[800],
        foregroundColor: Colors.white,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Application ID and Job Details
                  _buildJobDetailsCard(),
                  
                  const SizedBox(height: 16),
                  
                  // Personal Details Summary
                  _buildPersonalDetailsCard(),
                  
                  const SizedBox(height: 16),
                  
                  // Employment Details Summary
                  _buildEmploymentDetailsCard(),
                  
                  const SizedBox(height: 16),
                  
                  // Qualification Details Summary
                  _buildQualificationDetailsCard(),
                  
                  const SizedBox(height: 16),
                  
                  // Document Upload Summary
                  _buildDocumentDetailsCard(),
                  
                  const SizedBox(height: 16),
                  
                  // Payment Details
                  _buildPaymentDetailsCard(),
                  
                  const SizedBox(height: 32),
                  
                  // Declaration and Submit
                  _buildDeclarationAndSubmit(),
                ],
              ),
            ),
    );
  }

  Widget _buildJobDetailsCard() {
    final jobDetails = summaryData['job_details'] ?? {};
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Job Application Details',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.blue,
                  ),
                ),
                Text(
                  'App ID: ${summaryData['application_id'] ?? 'N/A'}',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: Colors.green,
                  ),
                ),
              ],
            ),
            const Divider(),
            _buildDetailRow('Position', jobDetails['post_name'] ?? 'N/A'),
            _buildDetailRow('Department', jobDetails['department'] ?? 'N/A'),
            _buildDetailRow('Total Posts', jobDetails['total_posts']?.toString() ?? 'N/A'),
            _buildDetailRow('Last Date to Apply', jobDetails['last_date_to_apply'] ?? 'N/A'),
          ],
        ),
      ),
    );
  }

  Widget _buildPersonalDetailsCard() {
    final personalDetails = summaryData['personal_details'] ?? {};
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Personal Details',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const Divider(),
            _buildDetailRow('Full Name', personalDetails['full_name'] ?? 'N/A'),
            _buildDetailRow('Date of Birth', personalDetails['date_of_birth'] ?? 'N/A'),
            _buildDetailRow('Gender', personalDetails['gender'] ?? 'N/A'),
            _buildDetailRow('Category', personalDetails['category'] ?? 'N/A'),
            _buildDetailRow('Marital Status', personalDetails['marital_status'] ?? 'N/A'),
            // Add more fields as needed
          ],
        ),
      ),
    );
  }

  Widget _buildEmploymentDetailsCard() {
    final employmentDetails = summaryData['employment_details'] ?? [];
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Employment Details (${employmentDetails.length} records)',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const Divider(),
            if (employmentDetails.isEmpty)
              const Text('No employment records found')
            else
              ...employmentDetails.asMap().entries.map((entry) {
                final index = entry.key;
                final employment = entry.value;
                return ExpansionTile(
                  title: Text('Employment ${index + 1}'),
                  subtitle: Text(employment['name_of_organization'] ?? 'N/A'),
                  children: [
                    _buildDetailRow('Organization', employment['name_of_organization'] ?? 'N/A'),
                    _buildDetailRow('Designation', employment['designation'] ?? 'N/A'),
                    _buildDetailRow('Duration', '${employment['duration_in_months']} months'),
                    _buildDetailRow('Salary', '₹${employment['monthly_salary']}'),
                  ],
                );
              }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildQualificationDetailsCard() {
    final qualificationDetails = summaryData['qualification_details'] ?? [];
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Qualification Details (${qualificationDetails.length} records)',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const Divider(),
            if (qualificationDetails.isEmpty)
              const Text('No qualification records found')
            else
              ...qualificationDetails.asMap().entries.map((entry) {
                final index = entry.key;
                final qualification = entry.value;
                return ExpansionTile(
                  title: Text('Qualification ${index + 1}'),
                  subtitle: Text(qualification['additional_qualification'] ?? 'N/A'),
                  children: [
                    _buildDetailRow('Qualification', qualification['additional_qualification'] ?? 'N/A'),
                    _buildDetailRow('Institution', qualification['institution_name'] ?? 'N/A'),
                    _buildDetailRow('Year of Passing', qualification['year_of_passing']?.toString() ?? 'N/A'),
                    _buildDetailRow('Percentage', '${qualification['percentage_obtained']}%'),
                  ],
                );
              }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildDocumentDetailsCard() {
    final documentDetails = summaryData['document_details'] ?? [];
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Uploaded Documents (${documentDetails.length} files)',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const Divider(),
            if (documentDetails.isEmpty)
              const Text('No documents found')
            else
              ...documentDetails.map((doc) {
                return ListTile(
                  leading: Icon(
                    Icons.description,
                    color: doc['is_mandatory'] ? Colors.red : Colors.blue,
                  ),
                  title: Text(doc['document_type'] ?? 'N/A'),
                  subtitle: Text('Uploaded: ${doc['uploaded_at'] ?? 'N/A'}'),
                  trailing: doc['is_mandatory']
                      ? const Chip(
                          label: Text('Mandatory', style: TextStyle(fontSize: 10)),
                          backgroundColor: Colors.red,
                          labelStyle: TextStyle(color: Colors.white),
                        )
                      : const Chip(
                          label: Text('Optional', style: TextStyle(fontSize: 10)),
                          backgroundColor: Colors.blue,
                          labelStyle: TextStyle(color: Colors.white),
                        ),
                );
              }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildPaymentDetailsCard() {
    final paymentDetails = summaryData['payment_details'] ?? {};
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Payment Details',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const Divider(),
            _buildDetailRow('Category', paymentDetails['category'] ?? 'N/A'),
            _buildDetailRow('Application Fee', '₹${paymentDetails['application_fee']} ${paymentDetails['currency'] ?? 'INR'}'),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.orange[50],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.orange[200]!),
              ),
              child: const Row(
                children: [
                  Icon(Icons.info, color: Colors.orange),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Payment is required to complete your application. The fee is non-refundable.',
                      style: TextStyle(fontSize: 12),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDeclarationAndSubmit() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Declaration',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.grey[50],
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey[300]!),
          ),
          child: const Text(
            'I hereby declare that all the information provided by me in this application is true and correct to the best of my knowledge. I understand that any false information may lead to the rejection of my application.',
            style: TextStyle(fontSize: 12),
          ),
        ),
        const SizedBox(height: 24),
        
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _proceedToPayment,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.green,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: const Text(
              'Proceed to Payment',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              '$label:',
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
          Expanded(
            child: Text(value),
          ),
        ],
      ),
    );
  }

  void _proceedToPayment() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PaymentScreen(
          jobId: widget.jobId,
          summaryData: summaryData,
        ),
      ),
    );
  }
}
```

This comprehensive guide provides:

1. **Complete Backend Flow** - Enhanced APIs with stage management and payment integration
2. **Resume Functionality** - Users can logout and resume from where they left off
3. **Job Selection** - Dropdown in Stage 0 with application status
4. **Payment Integration** - Fee calculation based on category and payment gateway
5. **Summary Screen** - Complete acknowledgment page with all details
6. **Flutter Integration** - Complete UI flow with all backend API calls

The system now handles the complete end-to-end journey from login to payment completion! 🚀