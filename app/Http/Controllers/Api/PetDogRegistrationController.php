<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PetDogRegistration;
use App\Models\Forms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;

class PetDogRegistrationController extends Controller
{
    /**
     * Submit Pet Dog Registration Application
     */
    public function submitRegistration(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'owner_name' => 'required|string|max:255',
                'identity_proof_type' => 'required|in:passport,pan,voter_id,aadhar',
                'identity_proof_number' => 'required|string|max:255',
                'identity_proof_document' => 'required|string', // base64 encoded document
                'phone_number' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'dog_name' => 'required|string|max:255',
                'dog_breed' => 'required|string|max:255',
                'address' => 'required|string',
                'vaccination_card_document' => 'required|string', // base64 encoded document
                'dog_photo_document' => 'required|string', // base64 encoded document
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // Generate unique application ID
            $applicationId = PetDogRegistration::generateApplicationId();

            // Process and store base64 documents
            $identityProofPath = $this->processBase64Document($request->identity_proof_document, $applicationId, 'identity_proof');
            $vaccinationCardPath = $this->processBase64Document($request->vaccination_card_document, $applicationId, 'vaccination_card');
            $dogPhotoPath = $this->processBase64Document($request->dog_photo_document, $applicationId, 'dog_photo');

            // Create registration record
            $registration = PetDogRegistration::create([
                'application_id' => $applicationId,
                'owner_name' => $request->owner_name,
                'identity_proof_type' => $request->identity_proof_type,
                'identity_proof_number' => $request->identity_proof_number,
                'identity_proof_document' => $identityProofPath,
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'dog_name' => $request->dog_name,
                'dog_breed' => $request->dog_breed,
                'address' => $request->address,
                'vaccination_card_document' => $vaccinationCardPath,
                'dog_photo_document' => $dogPhotoPath,
                'user_id' => $user->id,
                'status' => 'pending',
                'payment_status' => 'pending'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Pet Dog Registration application submitted successfully',
                'application_id' => $applicationId,
                'registration_fee' => 50.00,
                'metal_tag_fee' => 200.00,
                'total_fee' => 250.00,
                'stipulated_time' => '2 Days',
                'data' => [
                    'application_id' => $registration->application_id,
                    'owner_name' => $registration->owner_name,
                    'dog_name' => $registration->dog_name,
                    'dog_breed' => $registration->dog_breed,
                    'status' => $registration->status,
                    'payment_status' => $registration->payment_status,
                    'submitted_at' => $registration->created_at->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to submit registration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get All Pet Dog Registration Applications (similar to getAllForm)
     */
    public function getAllApplications(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'stage' => 'required|in:ceo,employee,consumer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);

            $query = PetDogRegistration::query();

            // Apply stage-based filtering
            if ($request->stage == 'consumer') {
                $query->where('user_id', $user->id);
            }

            // Apply search filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('application_id', 'like', "%{$search}%")
                      ->orWhere('owner_name', 'like', "%{$search}%")
                      ->orWhere('dog_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Get results
            $applications = $query->orderBy('created_at', 'desc')->get();

            // Format response to match getAllForm structure
            $formattedApplications = $applications->map(function ($app) {
                return [
                    'application_id' => $app->application_id,
                    'application_submited_at' => $app->created_at->format('Y-m-d H:i:s'),
                    'application_for' => 'Pet Dog Registration',
                    'status' => $app->status,
                    'formNumber' => 'PDR', // Pet Dog Registration form number
                    'form_id' => $app->id,
                    'payment' => 'Yes',
                    'payment_status' => $app->payment_status,
                    'form' => [
                        'owner_name' => $app->owner_name,
                        'dog_name' => $app->dog_name,
                        'dog_breed' => $app->dog_breed,
                        'phone_number' => $app->phone_number,
                        'email' => $app->email,
                        'address' => $app->address,
                        'registration_fee' => $app->registration_fee,
                        'metal_tag_fee' => $app->metal_tag_fee,
                        'total_fee' => $app->total_fee,
                        'form_id' => $app->id
                    ]
                ];
            });

            // Implement pagination
            $total = $formattedApplications->count();
            $paginatedData = $formattedApplications->slice(($page - 1) * $limit, $limit)->values();

            return response()->json([
                'status' => true,
                'message' => 'Pet Dog Registration applications retrieved successfully',
                'data' => $paginatedData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ],
                'counts' => [
                    'pending' => PetDogRegistration::where('status', 'pending')->count(),
                    'approved' => PetDogRegistration::where('status', 'approved')->count(),
                    'rejected' => PetDogRegistration::where('status', 'rejected')->count(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve applications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific application details
     */
    public function getApplicationDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application ID is required'
                ], 400);
            }

            $application = PetDogRegistration::where('application_id', $request->application_id)->first();

            if (!$application) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Application details retrieved successfully',
                'data' => [
                    'application_id' => $application->application_id,
                    'owner_name' => $application->owner_name,
                    'identity_proof_type' => $application->identity_proof_type,
                    'identity_proof_number' => $application->identity_proof_number,
                    'phone_number' => $application->phone_number,
                    'email' => $application->email,
                    'dog_name' => $application->dog_name,
                    'dog_breed' => $application->dog_breed,
                    'address' => $application->address,
                    'registration_fee' => $application->registration_fee,
                    'metal_tag_fee' => $application->metal_tag_fee,
                    'total_fee' => $application->total_fee,
                    'status' => $application->status,
                    'payment_status' => $application->payment_status,
                    'metal_tag_number' => $application->metal_tag_number,
                    'submitted_at' => $application->created_at->format('Y-m-d H:i:s'),
                    'approved_at' => $application->approved_at ? $application->approved_at->format('Y-m-d H:i:s') : null,
                    'documents' => [
                        'identity_proof' => $application->identity_proof_document ? Storage::url($application->identity_proof_document) : null,
                        'vaccination_card' => $application->vaccination_card_document ? Storage::url($application->vaccination_card_document) : null,
                        'dog_photo' => $application->dog_photo_document ? Storage::url($application->dog_photo_document) : null,
                        'registration_certificate' => $application->registration_certificate_path ? Storage::url($application->registration_certificate_path) : null,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve application details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update payment status (to be called by payment gateway)
     */
    public function updatePaymentStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|string',
                'payment_status' => 'required|in:paid,failed',
                'transaction_id' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $application = PetDogRegistration::where('application_id', $request->application_id)->first();

            if (!$application) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            $application->update([
                'payment_status' => $request->payment_status
            ]);

            // If payment is successful, auto-approve the registration
            if ($request->payment_status == 'paid') {
                $application->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'metal_tag_number' => PetDogRegistration::generateMetalTagNumber()
                ]);

                // TODO: Generate and store registration certificate PDF
                // TODO: Send SMS/Email notification
            }

            return response()->json([
                'status' => true,
                'message' => 'Payment status updated successfully',
                'data' => [
                    'application_id' => $application->application_id,
                    'payment_status' => $application->payment_status,
                    'status' => $application->status,
                    'metal_tag_number' => $application->metal_tag_number
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update payment status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process base64 encoded document and save to storage
     */
    private function processBase64Document($base64Data, $applicationId, $documentType)
    {
        try {
            // Remove data URL prefix if present (data:image/jpeg;base64, etc.)
            if (strpos($base64Data, 'data:') === 0) {
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            }

            // Decode base64 data
            $decodedData = base64_decode($base64Data);
            
            if ($decodedData === false) {
                throw new Exception('Invalid base64 data for ' . $documentType);
            }

            // Determine file extension from decoded data
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $decodedData);
            finfo_close($finfo);

            $extension = 'jpg'; // default
            switch ($mimeType) {
                case 'image/jpeg':
                    $extension = 'jpg';
                    break;
                case 'image/png':
                    $extension = 'png';
                    break;
                case 'application/pdf':
                    $extension = 'pdf';
                    break;
                default:
                    $extension = 'jpg';
            }

            // Create directory if it doesn't exist
            $directory = 'pet_dog_registrations/' . $applicationId;
            $fullDirectory = storage_path('app/public/' . $directory);
            
            if (!file_exists($fullDirectory)) {
                mkdir($fullDirectory, 0755, true);
            }

            // Generate filename
            $filename = $documentType . '.' . $extension;
            $filePath = $directory . '/' . $filename;
            $fullPath = storage_path('app/public/' . $filePath);

            // Save file
            if (file_put_contents($fullPath, $decodedData) === false) {
                throw new Exception('Failed to save ' . $documentType . ' document');
            }

            return $filePath;

        } catch (Exception $e) {
            throw new Exception('Document processing failed for ' . $documentType . ': ' . $e->getMessage());
        }
    }
}
