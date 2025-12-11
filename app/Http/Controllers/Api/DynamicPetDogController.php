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
                'owner_aadhar_number' => 'required|string|size:12',
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
                'owner_photo_with_pet' => 'required|string', // base64 string required
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
                'form_id' => $request->form_id,
                'application_id' => $app_id,
                'inserted_by' => $user->id
            ];
            $form_id = FormMasterTblModel::insertGetId($form_data);

            // Auto-approve Pet Dog Registration (using exact ENUM values from database)
            FormMasterTblModel::where('id', $form_id)->update([
                'status' => 'CEO Approved',      // Main status: CEO Approved (from ENUM)
                'employee_status' => 'Approved', // Employee status: Approved (from ENUM)
                'ceo_status' => 'Approved'       // CEO status: Approved (from ENUM)
            ]);

            // Generate pet tag number (TMB-1, TMB-2, ...)
            $petTagCount = \App\Models\PetDogRegistration::whereNotNull('metal_tag_number')->count();
            $pet_tag_number = 'TMB-' . ($petTagCount + 1);

            $formData = [];
            $fileName = date('Ymdhis');
            $file_path = 'uploads/' . $fileName;
            $pet_photo_path = null;
            $owner_photo_with_pet_path = null;

            // Save all fields except pet_photo and owner_photo_with_pet
            foreach($request->all() as $key => $req) {
                if ($key === 'pet_photo') {
                    // Process pet photo base64
                    $pet_photo_path = $this->processBase64Document($req, $file_path, 'pet_photo.jpg');
                } else if ($key === 'owner_photo_with_pet') {
                    // Process owner photo with pet base64
                    $owner_photo_with_pet_path = $this->processBase64Document($req, $file_path, 'owner_photo_with_pet.jpg');
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
                                    'form_id' => $form_id
                                ];
                            }
                        } else {
                            $fileType = $files->getClientOriginalName();
                            $filePath = $files->store($file_path, 'public');
                            $formData[] = [
                                'parameter' => $fileType,
                                'value' => $filePath,
                                'form_id' => $form_id
                            ];
                        }
                    }
                } else {
                    $value = is_array($req) ? json_encode($req) : $req;
                    $formData[] = [
                        'parameter' => $key,
                        'value' => $value,
                        'form_id' => $form_id
                    ];
                }
            }

            // Add file path parameter
            $formData[] = [
                'parameter' => 'upload_file_path',
                'value' => $fileName,
                'form_id' => $form_id
            ];
            // Add pet tag number
            $formData[] = [
                'parameter' => 'pet_tag_number',
                'value' => $pet_tag_number,
                'form_id' => $form_id
            ];
            // Add pet photo path
            $formData[] = [
                'parameter' => 'pet_photo_path',
                'value' => $pet_photo_path ?? '',
                'form_id' => $form_id
            ];
            // Add owner photo with pet path
            $formData[] = [
                'parameter' => 'owner_photo_with_pet_path',
                'value' => $owner_photo_with_pet_path ?? '',
                'form_id' => $form_id
            ];

            // Process base64 documents if present
            if(isset($request->document_list) && is_array($request->document_list)) {
                foreach($request->document_list as $index => $doc) {
                    if(isset($doc['data']) && isset($doc['name']) && isset($doc['type'])) {
                        $savedPath = $this->processBase64Document($doc['data'], $file_path, $doc['name']);
                        $formData[] = [
                            'parameter' => $doc['type'],
                            'value' => $savedPath,
                            'form_id' => $form_id
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
                'form_id' => $form_id,
                'pet_tag_number' => $pet_tag_number,
                'pet_photo_path' => $pet_photo_path,
                'owner_photo_with_pet_path' => $owner_photo_with_pet_path
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
     * Note: Pet Dog Registration will automatically work with existing getAllForms API
     * because it follows the same dynamic structure:
     * 
     * GET /api/getAllForms?stage=consumer&form_type=12
     * 
     * This will return Pet Dog Registration applications in the same format as other forms
     */
}