<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\FormMasterTblModel;
use App\Models\FormEntityModel;
use App\Models\PaymentModel;
use App\Models\User;
use App\Exceptions\MunicipalBoardException;

class DynamicPetDogController extends Controller
{
    /**
     * Submit Pet Dog Registration using dynamic form approach
     * Following the same pattern as nacBirth(), waterTankerForm(), etc.
     */
    public function submitRegistration(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'form_id' => 'required|integer', // form_id = 0 for Pet Dog Registration
                'owner_name' => 'required|string|between:2,100',
                'owner_phone' => 'required|string|min:10|max:15',
                'owner_email' => 'required|email|max:100',
                'owner_address' => 'required|string|between:10,300',
                'ward_no' => 'required|string|between:2,100',
                'district' => 'nullable|string|max:100', // Optional, will default to "West Garo Hills"
                'pincode' => 'required|string|size:6',
                'owner_aadhar_number' => 'required|string',
                'dog_name' => 'nullable|string|between:2,50',
                'dog_breed' => 'nullable|string|between:2,50',
                'dog_age' => 'required|integer|min:1|max:300', // Allow up to 300 for months
                'dog_age_unit' => 'required|in:months,years,Months,Years', // Age unit selector
                'dog_color' => 'required|string|between:2,50',
                'dog_gender' => 'required|in:male,female,Male,Female', // Accept both cases
                'dog_weight' => 'required|numeric|min:1|max:100',
                'vaccination_status' => 'required|in:completed,pending,Completed,Pending', // Accept both cases
                'vaccination_date' => 'nullable|date', // Make optional since it might be future date
                'veterinarian_name' => 'nullable|string|between:2,100', // Make optional
                'veterinarian_license' => 'nullable|string|between:5,50', // Make optional
                'upload_files' => 'sometimes|array', // Make optional
                'document_list' => 'sometimes|array', // Make optional
                'pet_photo' => 'required|string', // base64 string required
                'declaration' => 'required|string|min:1', // Accept any non-empty string
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => "failed",
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }

            // Generate application ID following the pattern
            $app_id = "PDR" . date("Ymdhis");
            
            // Create master record (following exact pattern from existing forms)
            $form_data = [
                'form_id' => $request->form_id, // Form type (0 for Pet Dog Registration)
                'form_type_id' => $request->form_id, // Explicit form type reference
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data); // This is the unique submission ID

            // Auto-approve Pet Dog Registration (using exact ENUM values from database)
            FormMasterTblModel::where('id', $form_id)->update([
                'status' => 'CEO Approved',      // Main status: CEO Approved (from ENUM)
                'employee_status' => 'Approved', // Employee status: Approved (from ENUM)
                'ceo_status' => 'Approved'       // CEO status: Approved (from ENUM)
            ]);

            // Generate pet tag number (TMB-1, TMB-2, ...)
            // Count existing pet tag numbers from form_entity table
            $petTagCount = FormEntityModel::where('parameter', 'pet_tag_number')->count();
            $pet_tag_number = 'TMB-' . ($petTagCount + 1);

            $formData = [];
            $fileName = date('Ymdhis');
            $file_path = 'uploads/' . $fileName;
            $pet_photo_path = null;

            // Auto-generate registration date as today's date
            $registration_date = date('Y-m-d'); // Store in YYYY-MM-DD format for database

            // Set default district if not provided
            $district = $request->district ?: 'West Garo Hills';

            Log::info('Pet Dog Registration - New fields processing', [
                'registration_date' => $registration_date,
                'ward_no' => $request->ward_no,
                'district' => $district,
                'pincode' => $request->pincode
            ]);

            // Save all fields except pet_photo
            foreach($request->all() as $key => $req) {
                if ($key === 'pet_photo') {
                    // Process and save pet photo file (for backup)
                    $pet_photo_path = $this->processBase64Document($req, $file_path, 'pet_photo.jpg');
                    
                    // Also store the original base64 string (for PDF generation)
                    $pet_photo_base64 = $req; // Store original base64 string
                } else if ($key == "upload_files" || $key == "form_id") {
                    if ($request->hasFile('upload_files')) {
                        $files = $request->file('upload_files');
                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $fileType = $file->getClientOriginalName();
                                $filePath = $file->store($file_path, 'public');
                                $formData[] = [
                                    'parameter' => $fileType,
                                    'value' => $filePath,
                                    'form_id' => $form_id,
                                    'form_type_id' => $request->form_id
                                ];
                            }
                        } else {
                            $fileType = $files->getClientOriginalName();
                            $filePath = $files->store($file_path, 'public');
                            $formData[] = [
                                'parameter' => $fileType,
                                'value' => $filePath,
                                'form_id' => $form_id,
                                'form_type_id' => $request->form_id
                            ];
                        }
                    }
                } else {
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id,
                        'form_type_id' => $request->form_id // Add form type ID
                    ];
                }
            }

            // Add registration date
            $formData[] = [
                'parameter' => 'registration_date',
                'value' => $registration_date,
                'form_id' => $form_id,
                'form_type_id' => $request->form_id
            ];

            // Add district (with default value)
            $formData[] = [
                'parameter' => 'district',
                'value' => $district,
                'form_id' => $form_id,
                'form_type_id' => $request->form_id
            ];

            // Add file path parameter
            $formData[] = [
                'parameter' => 'upload_file_path',
                'value' => $fileName,
                'form_id' => $form_id,
                'form_type_id' => $request->form_id
            ];
            // Add pet tag number
            $formData[] = [
                'parameter' => 'pet_tag_number',
                'value' => $pet_tag_number,
                'form_id' => $form_id,
                'form_type_id' => $request->form_id
            ];
            // Add pet photo path (for backup/file storage)
            $formData[] = [
                'parameter' => 'pet_photo_path',
                'value' => $pet_photo_path ?? '',
                'form_id' => $form_id,
                'form_type_id' => $request->form_id
            ];
            // Add pet photo base64 (for PDF generation)
            $formData[] = [
                'parameter' => 'pet_photo_base64',
                'value' => $pet_photo_base64 ?? '',
                'form_id' => $form_id,
                'form_type_id' => $request->form_id
            ];

            // Process base64 documents if present
            if(isset($request->document_list) && is_array($request->document_list)) {
                foreach($request->document_list as $index => $doc) {
                    if(isset($doc['data']) && isset($doc['name']) && isset($doc['type'])) {
                        $savedPath = $this->processBase64Document($doc['data'], $file_path, $doc['name']);
                        $formData[] = [
                            'parameter' => $doc['type'],
                            'value' => $savedPath,
                            'form_id' => $form_id,
                            'form_type_id' => $request->form_id
                        ];
                    }
                }
            }

            // Insert all form data
            FormEntityModel::insert($formData);

            return response()->json([
                'status' => 'success',
                'message' => 'Pet Dog Registration submitted successfully',
                'application_id' => $app_id,
                'form_id' => $form_id, // Database generated unique ID (back to original format)
                'pet_tag_number' => $pet_tag_number
            ], 200);

        } catch (MunicipalBoardException $exception) {
            Log::error('Pet Dog Registration Error: ' . $exception->getMessage());
            Log::error('Stack trace: ' . $exception->getTraceAsString());
            return $exception->message();
        } catch (\Exception $exception) {
            Log::error('Pet Dog Registration Error: ' . $exception->getMessage());
            Log::error('Stack trace: ' . $exception->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            return response()->json([
                'status' => 'failed',
                'message' => 'Registration submission failed',
                'error' => $exception->getMessage(), // Add actual error message for debugging
                'line' => $exception->getLine(),
                'file' => $exception->getFile()
            ], 500);
        }
    }

    /**
     * Process base64 document and save to storage
     * Following the same pattern as existing base64 processing
     */
    private function processBase64Document($base64Data, $path, $fileName = null)
    {
        try {
            // Remove data URI prefix if present
            if (strpos($base64Data, 'data:') === 0) {
                $base64Data = explode(',', $base64Data, 2)[1];
            }

            // Decode base64 data
            $fileData = base64_decode($base64Data);
            
            // Detect MIME type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($fileData);
            
            // Get file extension
            $extension = $this->getExtensionFromMimeType($mimeType);
            
            // Generate filename if not provided
            if (!$fileName) {
                $fileName = 'document_' . time() . '_' . uniqid();
            }
            
            // Remove existing extension and add detected one
            $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.' . $extension;
            
            // Save file
            $fullPath = $path . '/' . $fileName;
            Storage::disk('public')->put($fullPath, $fileData);
            
            return $fullPath;
            
        } catch (\Exception $e) {
            Log::error('Base64 document processing failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType($mimeType)
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg', 
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        return $mimeMap[$mimeType] ?? 'bin';
    }

    /**
     * Get all Pet Dog Registration applications (for CEO/Employee)
     * POST /api/pet-dog/applications
     */
    public function getAllApplications(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user || !in_array($user->role, ['ceo', 'editor'])) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Unauthorized access. CEO/Employee access required.',
                ], 403);
            }

            // Validate JSON request
            $validator = Validator::make($request->all(), [
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
                'status' => 'string|nullable',
                'payment_status' => 'string|nullable|in:pending,success,failed', // Updated to match actual DB values
                'search' => 'string|nullable|max:100',
                'pet_tag_search' => 'string|nullable|max:20' // Search by pet tag number
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }

            // Get parameters from JSON body
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            $status = $request->input('status'); // Filter by status
            $paymentStatus = $request->input('payment_status'); // Filter by payment status: success, pending, failed
            $search = $request->input('search'); // Search by application_id, owner_name, dog_name
            $petTagSearch = $request->input('pet_tag_search'); // Search specifically by pet tag number

            // Base query
            $query = FormMasterTblModel::select([
                'form_master_tbl.id',
                'form_master_tbl.application_id',
                'form_master_tbl.status',
                'form_master_tbl.employee_status',
                'form_master_tbl.ceo_status',
                'form_master_tbl.inserted_at',
                'users.firstname',
                'users.lastname',
                'users.email',
                'users.phone_no',
                'users.ward_id',
                'users.locality'
            ])
            ->leftJoin('users', 'form_master_tbl.inserted_by', '=', 'users.id')
            ->leftJoin('payment_details', 'form_master_tbl.application_id', '=', 'payment_details.form_id') // Fixed: use application_id
            ->where('form_master_tbl.form_id', 0); // Pet Dog Registration

            // Apply filters
            if ($status) {
                $query->where('form_master_tbl.status', $status);
            }

            if ($paymentStatus) {
                if ($paymentStatus === 'pending') {
                    // No payment record exists OR payment status is pending/failed
                    $query->where(function($q) {
                        $q->whereNull('payment_details.id')
                          ->orWhere('payment_details.status', '!=', 'success');
                    });
                } elseif ($paymentStatus === 'success') {
                    // Payment exists and is successful
                    $query->where('payment_details.status', 'success');
                } elseif ($paymentStatus === 'failed') {
                    // Payment exists but failed
                    $query->where('payment_details.status', 'failed');
                }
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('form_master_tbl.application_id', 'LIKE', "%{$search}%")
                      ->orWhereExists(function($subQ) use ($search) {
                          $subQ->select(\DB::raw(1))
                               ->from('form_entity')
                               ->whereColumn('form_entity.form_id', 'form_master_tbl.id')
                               ->where(function($entityQ) use ($search) {
                                   $entityQ->where('form_entity.parameter', 'owner_name')
                                           ->where('form_entity.value', 'LIKE', "%{$search}%");
                               })
                               ->orWhere(function($entityQ) use ($search) {
                                   $entityQ->where('form_entity.parameter', 'dog_name')
                                           ->where('form_entity.value', 'LIKE', "%{$search}%");
                               });
                      });
                });
            }

            // Separate filter for pet tag search
            if ($petTagSearch) {
                $query->whereExists(function($subQ) use ($petTagSearch) {
                    $subQ->select(\DB::raw(1))
                         ->from('form_entity')
                         ->whereColumn('form_entity.form_id', 'form_master_tbl.id')
                         ->where('form_entity.parameter', 'pet_tag_number')
                         ->where('form_entity.value', 'LIKE', "%{$petTagSearch}%");
                });
            }

            // Get total count (clone query before applying pagination)
            $countQuery = clone $query;
            $total = $countQuery->distinct('form_master_tbl.id')->count('form_master_tbl.id');

            // Apply pagination and ensure distinct results (due to LEFT JOIN)
            $applications = $query->orderBy('form_master_tbl.inserted_at', 'desc')
                                 ->distinct()
                                 ->offset(($page - 1) * $perPage)
                                 ->limit($perPage)
                                 ->get();

            // Fetch additional details for each application
            $result = [];
            foreach ($applications as $app) {
                // Get form entity data
                $formData = FormEntityModel::where('form_id', $app->id)
                    ->whereIn('parameter', ['owner_name', 'owner_email', 'owner_phone', 'dog_name', 'dog_breed', 'pet_tag_number', 'vaccination_status'])
                    ->get()
                    ->keyBy('parameter');

                // Get payment status - use application_id since form_id in payment_details stores application_id
                $payment = PaymentModel::where('form_id', $app->application_id)->first();
                
                // Log payment debug info
                Log::info("Payment Debug for {$app->application_id}:", [
                    'app_id' => $app->id,
                    'app_application_id' => $app->application_id,
                    'payment_found' => $payment ? 'YES' : 'NO',
                    'payment_status' => $payment ? $payment->status : 'N/A',
                    'payment_amount' => $payment ? $payment->amount : 'N/A',
                    'payment_record' => $payment ? $payment->toArray() : 'NULL'
                ]);

                $result[] = [
                    'id' => $app->id,
                    'application_id' => $app->application_id,
                    'status' => $app->status,
                    'employee_status' => $app->employee_status,
                    'ceo_status' => $app->ceo_status,
                    'submitted_date' => $app->inserted_at,
                    'user_details' => [
                        'name' => trim($app->firstname . ' ' . $app->lastname),
                        'email' => $app->email,
                        'phone' => $app->phone_no,
                        'ward_id' => $app->ward_id,
                        'locality' => $app->locality
                    ],
                    'pet_details' => [
                        'owner_name' => $formData->get('owner_name')->value ?? 'N/A',
                        'owner_email' => $formData->get('owner_email')->value ?? 'N/A',
                        'owner_phone' => $formData->get('owner_phone')->value ?? 'N/A',
                        'owner_address' => $formData->get('owner_address')->value ?? 'N/A',
                        'ward_no' => $formData->get('ward_no')->value ?? 'N/A',
                        'district' => $formData->get('district')->value ?? 'West Garo Hills',
                        'pincode' => $formData->get('pincode')->value ?? 'N/A',
                        'registration_date' => $formData->get('registration_date')->value ?? date('Y-m-d'),
                        'dog_name' => $formData->get('dog_name')->value ?? 'N/A',
                        'dog_breed' => $formData->get('dog_breed')->value ?? 'N/A',
                        'pet_tag_number' => $formData->get('pet_tag_number')->value ?? 'N/A',
                        'vaccination_status' => $formData->get('vaccination_status')->value ?? 'N/A'
                    ],
                    'payment_status' => $payment ? $payment->status : 'pending',
                    'payment_amount' => $payment ? $payment->amount : 250.00
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pet Dog Registration applications retrieved successfully',
                'data' => [
                    'applications' => $result,
                    'pagination' => [
                        'current_page' => (int)$page,
                        'per_page' => (int)$perPage,
                        'total' => $total,
                        'total_pages' => ceil($total / $perPage)
                    ]
                ]
            ], 200);

        } catch (\Exception $exception) {
            Log::error('Pet Dog Applications List Error: ' . $exception->getMessage());
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to retrieve applications',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed information for a specific Pet Dog Registration (for CEO/Employee)
     * POST /api/pet-dog/application-details
     */
    public function getApplicationDetails(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user || !in_array($user->role, ['ceo', 'editor'])) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Unauthorized access. CEO/Employee access required.',
                ], 403);
            }

            // Validate JSON request
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }

            $id = $request->input('application_id');

            // Get application details
            $application = FormMasterTblModel::select([
                'form_master_tbl.*',
                'users.firstname',
                'users.lastname',
                'users.email',
                'users.phone_no',
                'users.ward_id',
                'users.locality',
                'users.dob',
                'users.verifyemail'
            ])
            ->leftJoin('users', 'form_master_tbl.inserted_by', '=', 'users.id')
            ->where('form_master_tbl.id', $id)
            ->where('form_master_tbl.form_id', 0) // Pet Dog Registration
            ->first();

            if (!$application) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Pet Dog Registration application not found',
                ], 404);
            }

            // Get all form entity data
            $formEntities = FormEntityModel::where('form_id', $id)->get()->keyBy('parameter');

            // Get payment details - use application_id since form_id in payment_details stores application_id
            $payment = PaymentModel::where('form_id', $application->application_id)->first();

            // Structure the response
            $response = [
                'application_info' => [
                    'id' => $application->id,
                    'application_id' => $application->application_id,
                    'form_id' => $application->form_id,
                    'status' => $application->status,
                    'employee_status' => $application->employee_status,
                    'ceo_status' => $application->ceo_status,
                    'submitted_date' => $application->inserted_at,
                    'updated_date' => $application->updated_at
                ],
                'user_account_details' => [
                    'user_id' => $application->inserted_by,
                    'full_name' => trim($application->firstname . ' ' . $application->lastname),
                    'firstname' => $application->firstname,
                    'lastname' => $application->lastname,
                    'email' => $application->email,
                    'phone_no' => $application->phone_no,
                    'ward_id' => $application->ward_id,
                    'locality' => $application->locality,
                    'date_of_birth' => $application->dob,
                    'email_verified' => $application->verifyemail
                ],
                'owner_details' => [
                    'owner_name' => $formEntities->get('owner_name')->value ?? 'N/A',
                    'owner_email' => $formEntities->get('owner_email')->value ?? 'N/A',
                    'owner_phone' => $formEntities->get('owner_phone')->value ?? 'N/A',
                    'owner_address' => $formEntities->get('owner_address')->value ?? 'N/A',
                    'owner_aadhar_number' => $formEntities->get('owner_aadhar_number')->value ?? 'N/A'
                ],
                'dog_details' => [
                    'dog_name' => $formEntities->get('dog_name')->value ?? 'N/A',
                    'dog_breed' => $formEntities->get('dog_breed')->value ?? 'N/A',
                    'dog_age' => $formEntities->get('dog_age')->value ?? 'N/A',
                    'dog_age_unit' => $formEntities->get('dog_age_unit')->value ?? 'N/A',
                    'dog_color' => $formEntities->get('dog_color')->value ?? 'N/A',
                    'dog_gender' => $formEntities->get('dog_gender')->value ?? 'N/A',
                    'dog_weight' => $formEntities->get('dog_weight')->value ?? 'N/A',
                    'pet_tag_number' => $formEntities->get('pet_tag_number')->value ?? 'N/A'
                ],
                'vaccination_details' => [
                    'vaccination_status' => $formEntities->get('vaccination_status')->value ?? 'N/A',
                    'vaccination_date' => $formEntities->get('vaccination_date')->value ?? 'N/A',
                    'veterinarian_name' => $formEntities->get('veterinarian_name')->value ?? 'N/A',
                    'veterinarian_license' => $formEntities->get('veterinarian_license')->value ?? 'N/A'
                ],
                'documents' => [
                    'pet_photo_path' => $formEntities->get('pet_photo_path')->value ?? null,
                    'pet_photo_base64' => $formEntities->get('pet_photo_base64')->value ?? null,
                    'owner_photo_with_pet_path' => $formEntities->get('owner_photo_with_pet_path')->value ?? null,
                    'upload_file_path' => $formEntities->get('upload_file_path')->value ?? null,
                    'vaccination_certificate' => $formEntities->get('vaccination_certificate')->value ?? null,
                    'identity_proof' => $formEntities->get('identity_proof')->value ?? null
                ],
                'payment_details' => $payment ? [
                    'payment_id' => $payment->payment_id,
                    'order_id' => $payment->order_id,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'payment_date' => $payment->created_at,
                    'gateway_response' => $payment->response_body ? json_decode($payment->response_body, true) : null
                ] : [
                    'status' => 'pending',
                    'amount' => 250.00,
                    'message' => 'Payment not initiated yet'
                ],
                'declaration' => $formEntities->get('declaration')->value ?? 'N/A',
                'all_form_data' => $formEntities->map(function($entity) {
                    return [
                        'parameter' => $entity->parameter,
                        'value' => $entity->value
                    ];
                })->values()
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Pet Dog Registration details retrieved successfully',
                'data' => $response
            ], 200);

        } catch (\Exception $exception) {
            Log::error('Pet Dog Application Details Error: ' . $exception->getMessage());
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to retrieve application details',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Update Payment Status for Pet Dog Registration (for CEO/Employee)
     * POST /api/pet-dog/update-payment-status
     */
    public function updatePaymentStatus(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user || !in_array($user->role, ['ceo', 'editor'])) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Unauthorized access. CEO/Employee access required.',
                ], 403);
            }

            // Validate JSON request
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|string',
                'payment_status' => 'required|in:pending,success,failed',
                'payment_remarks' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()->toArray(),
                ], 400);
            }

            $applicationId = $request->input('application_id');
            $paymentStatus = $request->input('payment_status');
            $paymentAmount = 250.00; // Fixed amount for pet dog registration
            $paymentRemarks = $request->input('payment_remarks', '');

            // Get application details
            $application = FormMasterTblModel::where('application_id', $applicationId)
                ->where('form_id', 0) // Pet Dog Registration
                ->first();

            if (!$application) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Pet Dog Registration application not found'
                ], 404);
            }

            // Check if payment record exists - use application_id since form_id in payment_details stores application_id
            $payment = PaymentModel::where('form_id', $application->application_id)->first();

            if ($payment) {
                // Update existing payment record
                $payment->status = $paymentStatus;
                $payment->amount = $paymentAmount;
                $payment->payment_remarks = $paymentRemarks;
                $payment->updated_by = $user->id;
                $payment->request_body = 'Paid at office cash';
                $payment->response_body = 'Paid at office cash';
                $payment->save();
                $action = 'updated';
            } else {
                // Create new payment record
                $payment = new PaymentModel();
                $payment->form_id = $application->application_id; // Use application_id for manual payment
                $payment->form_type_id = 0; // Pet Dog Registration
                $payment->payment_id = 'MANUAL_' . $applicationId . '_' . time();
                $payment->order_id = 'ORDER_' . $applicationId . '_' . time();
                $payment->amount = $paymentAmount;
                $payment->status = $paymentStatus;
                $payment->payment_remarks = $paymentRemarks;
                $payment->created_by = $user->id;
                $payment->updated_by = $user->id;
                $payment->request_body = 'Paid at office cash';
                $payment->response_body = 'Paid at office cash';
                $payment->save();
                $action = 'created';
            }

            // Log the payment status change
            Log::info("Pet Dog Registration Payment Status {$action}", [
                'application_id' => $applicationId,
                'form_id' => $application->id,
                'payment_status' => $paymentStatus,
                'amount' => $paymentAmount,
                'updated_by' => $user->email,
                'remarks' => $paymentRemarks
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Payment status {$action} successfully",
                'data' => [
                    'application_id' => $applicationId,
                    'payment_status' => $paymentStatus,
                    'payment_amount' => $paymentAmount,
                    'payment_id' => $payment->payment_id,
                    'updated_by' => $user->firstname . ' ' . $user->lastname,
                    'updated_at' => $payment->updated_at
                ]
            ], 200);

        } catch (\Exception $exception) {
            Log::error('Pet Dog Payment Status Update Error: ' . $exception->getMessage());
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to update payment status',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Note: Pet Dog Registration will automatically work with existing getAllForms API
     * because it follows the same dynamic structure:
     * 
     * GET /api/getAllForms?stage=consumer&form_type=12
     * 
     * This will return Pet Dog Registration applications in the same format as other forms
     */
}