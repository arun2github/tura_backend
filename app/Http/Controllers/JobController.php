<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Models\TuraJobPosting;
use App\Models\JobPersonalDetail;
use App\Models\JobEmploymentDetail;
use App\Models\JobQualification;
use App\Models\JobAppliedStatus;
use App\Models\JobDocumentUpload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * JobController
 * 
 * This controller handles job posting related API endpoints
 */
class JobController extends Controller
{
    /**
     * Simple jobs API - Basic GET endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobs()
    {
        try {
            $jobs = JobPosting::all();
            
            return response()->json([
                'success' => true,
                'jobs' => $jobs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching jobs'
            ], 500);
        }
    }

    /**
     * Save personal details for job application
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePersonalDetails(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            
            $validator = Validator::make($request->all(), [
                'salutation' => 'required|string|max:10',
                'full_name' => 'required|string|max:100',
                'date_of_birth' => 'required|date',
                'marital_status' => 'required|string|max:20',
                'gender' => 'required|string|max:10',
                'category' => 'required|string|max:20',
                'caste' => 'required|string|max:50',
                'religion' => 'required|string|max:50',
                'identification_mark' => 'required|string|max:255',
                'permanent_address1' => 'required|string|max:255',
                'permanent_address2' => 'required|string|max:255',
                'permanent_landmark' => 'nullable|string|max:255',
                'permanent_village' => 'required|string|max:100',
                'permanent_state' => 'required|string|max:100',
                'permanent_district' => 'required|string|max:100',
                'permanent_block' => 'nullable|string|max:100',
                'permanent_pincode' => 'required|string|max:10',
                'present_address1' => 'required|string|max:255',
                'present_address2' => 'required|string|max:255',
                'present_landmark' => 'nullable|string|max:255',
                'present_village' => 'required|string|max:100',
                'present_state' => 'required|string|max:100',
                'present_district' => 'required|string|max:100',
                'present_block' => 'nullable|string|max:100',
                'present_pincode' => 'required|string|max:10',
                'job_id' => 'required|integer',
                'email' => 'required|email|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $authenticatedUser->id;

            // Check for existing record by user_id and job_id combination
            $existingRecord = JobPersonalDetail::where([
                'user_id' => $userId,
                'job_id' => $request->job_id
            ])->first();

            // Prepare data for insertion/update
            $data = $request->only([
                'salutation',
                'full_name',
                'date_of_birth',
                'marital_status',
                'gender',
                'category',
                'caste',
                'religion',
                'identification_mark',
                'permanent_address1',
                'permanent_address2',
                'permanent_landmark',
                'permanent_village',
                'permanent_state',
                'permanent_district',
                'permanent_block',
                'permanent_pincode',
                'present_address1',
                'present_address2',
                'present_landmark',
                'present_village',
                'present_state',
                'present_district',
                'present_block',
                'present_pincode',
                'job_id',
                'email'
            ]);

            // Add user_id from authenticated user
            $data['user_id'] = $userId;
            $data['updated_at'] = now();
            
            // Set default values for optional landmark fields
            $data['permanent_landmark'] = $data['permanent_landmark'] ?? null;
            $data['present_landmark'] = $data['present_landmark'] ?? null;

            if ($existingRecord) {
                // Update existing personal details record
                $existingRecord->update($data);
                
                // Check if application status exists, if not create it
                $applicationStatus = $this->getOrCreateApplicationStatus($userId, $request->job_id, $request->email);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Personal details updated successfully',
                    'data' => $existingRecord->fresh(),
                    'action' => 'updated',
                    'record_id' => $existingRecord->id,
                    'application_id' => $applicationStatus['application_id'],
                    'payment_amount' => $applicationStatus['payment_amount'],
                    'email_sent' => $applicationStatus['email_sent']
                ], 200);
            } else {
                // Create new personal details record
                $data['inserted_at'] = now();
                $personalDetails = JobPersonalDetail::create($data);

                // Generate Application ID and create application status
                $applicationStatus = $this->createApplicationStatus($userId, $request->job_id, $request->email);
                
                // Send application confirmation email
                $emailResult = $this->sendApplicationConfirmationEmail(
                    $request->email,
                    $applicationStatus['application_id'],
                    $request->job_id,
                    $authenticatedUser->firstname . ' ' . $authenticatedUser->lastname
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Personal details saved successfully',
                    'data' => $personalDetails,
                    'action' => 'created',
                    'record_id' => $personalDetails->id,
                    'application_id' => $applicationStatus['application_id'],
                    'payment_amount' => $applicationStatus['payment_amount'],
                    'email_sent' => $emailResult['success'],
                    'email_message' => $emailResult['message']
                ], 201);
            }

        } catch (\Exception $e) {
            Log::error('Error saving personal details: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving personal details',
                'error' => $e->getMessage() // Add this for debugging
            ], 500);
        }
    }

    /**
     * Create new application status with generated application ID
     */
    private function createApplicationStatus($userId, $jobId, $email)
    {
        // Generate unique Application ID
        $applicationId = $this->generateApplicationId($jobId);
        
        // Get job details for payment calculation
        $jobPosting = TuraJobPosting::find($jobId);
        if (!$jobPosting) {
            throw new \Exception("Job posting not found for ID: {$jobId}");
        }
        
        // Calculate payment amount based on user's personal details category
        $paymentAmount = $this->calculatePaymentAmount($userId, $jobId);
        
        // Create application status record
        $applicationStatus = JobAppliedStatus::create([
            'user_id' => $userId,
            'job_id' => $jobId,
            'application_id' => $applicationId,
            'email' => $email,
            'status' => 'personal_details_submitted',
            'stage' => JobAppliedStatus::STAGES['personal_details'], // Stage 1 - personal details
            'payment_amount' => $paymentAmount,
            'payment_status' => 'pending',
            'job_applied_email_sent' => false,
            'inserted_at' => now(),
            'updated_at' => now()
        ]);
        
        return [
            'application_id' => $applicationId,
            'payment_amount' => $paymentAmount,
            'email_sent' => false,
            'status' => 'created'
        ];
    }
    
    /**
     * Get existing application status or create new one
     */
    private function getOrCreateApplicationStatus($userId, $jobId, $email)
    {
        $existingStatus = JobAppliedStatus::where([
            'user_id' => $userId,
            'job_id' => $jobId
        ])->first();
        
        if ($existingStatus) {
            // Check if application_id is missing and generate it
            if (!$existingStatus->application_id) {
                $existingStatus->application_id = $this->generateApplicationId($jobId);
            }
            
            // Check if payment_amount is missing and calculate it
            if (!$existingStatus->payment_amount || $existingStatus->payment_amount == 0) {
                $existingStatus->payment_amount = $this->calculatePaymentAmount($userId, $jobId);
            }
            
            // Update email if provided
            if ($email) {
                $existingStatus->email = $email;
            }
            
            // Save the updates
            $existingStatus->save();
            
            // Send email if not sent yet
            if (!$existingStatus->job_applied_email_sent && $existingStatus->email) {
                $user = User::find($userId);
                $emailResult = $this->sendApplicationConfirmationEmail(
                    $existingStatus->email,
                    $existingStatus->application_id,
                    $jobId,
                    $user->firstname . ' ' . $user->lastname
                );
                
                if ($emailResult['success']) {
                    $existingStatus->job_applied_email_sent = 1;
                    $existingStatus->save();
                }
            }
            
            return [
                'application_id' => $existingStatus->application_id,
                'payment_amount' => $existingStatus->payment_amount,
                'email_sent' => $existingStatus->job_applied_email_sent,
                'status' => 'updated'
            ];
        }
        
        return $this->createApplicationStatus($userId, $jobId, $email);
    }
    
    /**
     * Generate unique Application ID format: TMB-YYYY-JOB{job_id}-{sequence}
     */
    private function generateApplicationId($jobId)
    {
        $year = date('Y');
        $prefix = "TMB-{$year}-JOB{$jobId}-";
        
        // Get the latest application ID for this job to determine sequence
        $latestApplication = JobAppliedStatus::where('job_id', $jobId)
            ->where('application_id', 'LIKE', $prefix . '%')
            ->orderBy('application_id', 'desc')
            ->first();
        
        if ($latestApplication) {
            // Extract sequence number and increment
            $lastSequence = (int) str_replace($prefix, '', $latestApplication->application_id);
            $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // First application for this job
            $sequence = '0001';
        }
        
        return $prefix . $sequence;
    }
    
    /**
     * Calculate payment amount based on user category and job posting fees
     */
    private function calculatePaymentAmount($userId, $jobId)
    {
        // Get job posting details
        $jobPosting = TuraJobPosting::find($jobId);
        if (!$jobPosting) {
            return 0;
        }
        
        // Get user's personal details to find category
        $personalDetails = JobPersonalDetail::where([
            'user_id' => $userId,
            'job_id' => $jobId
        ])->first();
        
        if (!$personalDetails) {
            return $jobPosting->fee_general; // Default to general category
        }
        
        $userCategory = strtoupper($personalDetails->category);
        
        switch ($userCategory) {
            case 'SC':
            case 'ST':
                return $jobPosting->fee_sc_st ?? 0;
            case 'OBC':
                return $jobPosting->fee_obc ?? $jobPosting->fee_general;
            case 'UR':
            case 'GENERAL':
            default:
                return $jobPosting->fee_general ?? 0;
        }
    }
    
    /**
     * Send application confirmation email with application ID
     */
    private function sendApplicationConfirmationEmail($email, $applicationId, $jobId, $fullName)
    {
        try {
            // Get job details
            $jobPosting = TuraJobPosting::find($jobId);
            if (!$jobPosting) {
                return ['success' => false, 'message' => 'Job posting not found'];
            }
            
            // Initialize PHPMailer
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = config('mail.mailers.smtp.host');
            $mail->SMTPAuth = true;
            $mail->Username = config('mail.mailers.smtp.username');
            $mail->Password = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
            $mail->Port = config('mail.mailers.smtp.port');
            
            // Recipients
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Subject = 'Personal Details Submitted Successfully - Application ID: ' . $applicationId;
            
            // Add additional headers for better email client compatibility
            $mail->addCustomHeader('X-Mailer', 'Tura Municipal Board System');
            $mail->addCustomHeader('Content-Type', 'text/html; charset=UTF-8');
            
            // Email body with application details
            $mail->Body = $this->generateApplicationConfirmationEmailTemplate($fullName, $applicationId, $jobPosting);
            
            $mail->send();
            
            // Update email sent flag
            JobAppliedStatus::where('application_id', $applicationId)->update([
                'job_applied_email_sent' => true
            ]);
            
            return ['success' => true, 'message' => 'Application confirmation email sent successfully'];
            
        } catch (\Exception $e) {
            Log::error('Error sending application confirmation email: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send confirmation email: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate application confirmation email template
     */
    private function generateApplicationConfirmationEmailTemplate($fullName, $applicationId, $jobPosting)
    {
        $applicationUrl = config('app.url') . "/application-status/" . $applicationId;
        $paymentUrl = config('app.url') . "/payment/" . $applicationId;
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Application Confirmation</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: \"Times New Roman\", Times, serif; background-color: #f8f9fa; line-height: 1.6;'>
            <div style='max-width: 700px; margin: 0 auto; background-color: #ffffff; border: 2px solid #1a365d; box-shadow: 0 4px 20px rgba(0,0,0,0.15);'>
                <!-- Official Header -->
                <div style='background: linear-gradient(135deg, #1a365d 0%, #2d5a87 100%); padding: 30px; text-align: center;'>
                    <div style='margin-bottom: 15px;'>
                        <img src='" . $this->getEmbeddedLogo() . "' alt='Tura Municipal Board Official Seal' style='max-width: 100px; height: auto; border: 3px solid #ffffff; border-radius: 50%; box-shadow: 0 6px 20px rgba(0,0,0,0.3);'>
                    </div>
                    
                    <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.5); letter-spacing: 1px;'>
                        Tura Municipal Board
                    </h1>
                    <div style='border-bottom: 2px solid #ffffff; width: 60%; margin: 10px auto;'></div>
                    <p style='color: #e2e8f0; margin: 8px 0 5px 0; font-size: 16px; font-weight: 500;'>
                        Government of Meghalaya
                    </p>
                    <p style='color: #cbd5e0; margin: 0; font-size: 14px; font-style: italic;'>
                        Established: 12th September, 1979
                    </p>
                </div>
                
                <!-- Application Confirmation Notice -->
                <div style='padding: 35px 40px;'>
                    <div style='text-align: center; margin-bottom: 30px; border-bottom: 3px double #1a365d; padding-bottom: 20px;'>
                        <div style='background: #22c55e; color: #ffffff; padding: 12px 25px; display: inline-block; border-radius: 25px; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);'>
                            <span style='font-size: 20px; margin-right: 8px;'>✅</span>
                            <span style='font-weight: bold; font-size: 16px;'>PERSONAL DETAILS RECEIVED</span>
                        </div>
                        <h2 style='color: #1a365d; margin: 0 0 8px 0; font-size: 24px; font-weight: bold;'>
                            PERSONAL DETAILS CONFIRMATION
                        </h2>
                        <p style='color: #2d5a87; font-size: 16px; margin: 0; font-weight: 500;'>
                            Personal Details Successfully Recorded
                        </p>
                    </div>
                    
                    <!-- Application Details -->
                    <div style='background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 25px; margin: 25px 0;'>
                        <div style='text-align: right; margin-bottom: 20px; color: #1e40af; font-size: 12px; font-family: monospace;'>
                            <strong>Ref No:</strong> " . $applicationId . "<br>
                            <strong>Date:</strong> " . date('d/m/Y H:i:s') . "
                        </div>
                        
                        <h3 style='color: #1a365d; margin: 0 0 20px 0; font-size: 18px; font-weight: bold; text-align: center;'>
                            📋 APPLICATION DETAILS
                        </h3>
                        
                        <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; background: #f8fafc; font-weight: bold;'>Application ID:</td>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; font-family: monospace; color: #dc2626; font-weight: bold;'>" . $applicationId . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; background: #f8fafc; font-weight: bold;'>Applicant Name:</td>
                                <td style='padding: 8px; border: 1px solid #cbd5e0;'>" . htmlspecialchars($fullName) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; background: #f8fafc; font-weight: bold;'>Job Position:</td>
                                <td style='padding: 8px; border: 1px solid #cbd5e0;'>" . htmlspecialchars($jobPosting->job_title_department) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; background: #f8fafc; font-weight: bold;'>Category:</td>
                                <td style='padding: 8px; border: 1px solid #cbd5e0;'>" . $jobPosting->category . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; background: #f8fafc; font-weight: bold;'>Submission Date:</td>
                                <td style='padding: 8px; border: 1px solid #cbd5e0;'>" . date('d-m-Y H:i:s') . "</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Next Steps -->
                    <div style='background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 25px; margin: 25px 0;'>
                        <h4 style='color: #92400e; margin: 0 0 15px 0; font-size: 16px; font-weight: bold; text-align: center;'>
                            📝 NEXT STEPS TO COMPLETE YOUR APPLICATION
                        </h4>
                        <div style='background: #ffffff; padding: 20px; border-radius: 6px; border: 1px solid #f59e0b;'>
                            <ol style='color: #92400e; line-height: 1.8; margin: 0; padding-left: 25px; font-size: 14px;'>
                                <li><strong>Complete Employment Details:</strong> Submit your employment history and work experience</li>
                                <li><strong>Add Educational Qualifications:</strong> Upload details of your academic qualifications</li>
                                <li><strong>Upload Required Documents:</strong> Submit all mandatory documents (Photo, Signature, Certificates, etc.)</li>
                                <li><strong>Make Application Payment:</strong> Pay the required application fee to finalize submission</li>
                                <li><strong>Submit Final Application:</strong> Review and submit your complete application</li>
                            </ol>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . $applicationUrl . "' style='display: inline-block; background: linear-gradient(135deg, #1a365d 0%, #2d5a87 100%); color: #ffffff; text-decoration: none; padding: 15px 30px; border-radius: 6px; font-weight: bold; font-size: 16px; margin: 10px; box-shadow: 0 4px 15px rgba(26, 54, 93, 0.3);'>
                            📊 Continue Application
                        </a>
                    </div>
                    
                    <!-- Important Notice -->
                    <div style='background: #fee2e2; border: 2px solid #dc2626; border-radius: 8px; padding: 20px; margin: 25px 0;'>
                        <h4 style='color: #dc2626; margin: 0 0 10px 0; font-size: 16px; font-weight: bold; text-align: center;'>
                            ⚠️ IMPORTANT NOTICE
                        </h4>
                        <p style='color: #dc2626; margin: 0; font-size: 14px; line-height: 1.6; text-align: justify;'>
                            This is only the <strong>first step</strong> of your job application process. Your personal details have been successfully recorded. You still need to complete employment details, educational qualifications, document uploads, and payment to finalize your application. Please save your Application ID <strong>" . $applicationId . "</strong> for future reference.
                        </p>
                    </div>
                </div>
                
                <!-- Official Footer -->
                <div style='background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); padding: 25px; text-align: center; border-top: 4px solid #d69e2e;'>
                    <p style='color: #e2e8f0; margin: 0 0 10px 0; font-size: 16px; font-weight: bold;'>
                        🏛️ Tura Municipal Board
                    </p>
                    <p style='color: #a0aec0; margin: 0; font-size: 13px;'>
                        This is an official communication. Please do not reply to this email.
                    </p>
                    <div style='margin-top: 15px;'>
                        <p style='color: #718096; margin: 0; font-size: 12px;'>
                            © " . date('Y') . " Government of Meghalaya | Digital India Initiative
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Get embedded logo as base64 data URL for email compatibility
     */
    private function getEmbeddedLogo()
    {
        // Prefer using the hosted file URL for emails to avoid embedding very large base64 strings
        $logoPath = public_path('images/email/logo.png');

        if (file_exists($logoPath)) {
            try {
                // Return a fully qualified asset URL (email clients may still block external images,
                // but this avoids injecting massive base64 inline data into the HTML body)
                return asset('images/email/logo.png');
            } catch (\Exception $e) {
                Log::error('Error generating asset URL for logo: ' . $e->getMessage());
            }
        }

        // Fallback: Create a compact SVG data URL so we always have a logo
        $placeholderSvg = '<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">'
            . '<defs><linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">'
            . '<stop offset="0%" style="stop-color:#1a365d;stop-opacity:1" />'
            . '<stop offset="100%" style="stop-color:#2d5a87;stop-opacity:1" />'
            . '</linearGradient></defs>'
            . '<circle cx="50" cy="50" r="45" fill="url(#grad1)" stroke="#ffffff" stroke-width="3"/>'
            . '<text x="50" y="56" text-anchor="middle" fill="#ffffff" font-family="serif" font-size="16" font-weight="bold">TMB</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($placeholderSvg);
    }

    /**
     * Save selected jobs for a user with application ID
     * Links jobs to applications and manages application status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSelectedJobs(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            
            $validator = Validator::make($request->all(), [
                'job_ids' => 'required|array|min:1',
                'job_ids.*' => 'required|integer|exists:tura_job_postings,id',
                'application_preferences' => 'nullable|array',
                'application_preferences.*.job_id' => 'integer',
                'application_preferences.*.priority' => 'integer|min:1|max:10',
                'application_preferences.*.category_applied' => 'string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $authenticatedUser->id;
            $selectedJobs = [];
            $existingApplications = [];
            $errors = [];

            // Process each selected job
            foreach ($request->job_ids as $jobId) {
                try {
                    // Check if application already exists
                    $existingApplication = JobAppliedStatus::where([
                        'user_id' => $userId,
                        'job_id' => $jobId
                    ])->first();

                    if ($existingApplication) {
                        $existingApplications[] = [
                            'job_id' => $jobId,
                            'application_id' => $existingApplication->application_id,
                            'status' => $existingApplication->status,
                            'created_at' => $existingApplication->created_at,
                            'message' => 'Application already exists for this job'
                        ];
                        continue;
                    }

                    // Get job details
                    $jobPosting = TuraJobPosting::find($jobId);
                    if (!$jobPosting) {
                        $errors[] = [
                            'job_id' => $jobId,
                            'error' => 'Job posting not found'
                        ];
                        continue;
                    }

                    // Check if job applications are open
                    if (!$jobPosting->isApplicationOpen()) {
                        $errors[] = [
                            'job_id' => $jobId,
                            'job_title' => $jobPosting->job_title_department,
                            'error' => 'Applications are not open for this job',
                            'application_start_date' => $jobPosting->application_start_date,
                            'application_end_date' => $jobPosting->application_end_date
                        ];
                        continue;
                    }

                    // Generate Application ID
                    $applicationId = $this->generateApplicationId($jobId);
                    
                    // Calculate payment amount based on user category
                    $paymentAmount = $this->calculatePaymentAmount($userId, $jobId);
                    
                    // Get application preferences for this job
                    $preferences = collect($request->application_preferences ?? [])->firstWhere('job_id', $jobId);
                    
                    // Create application status record
                    $applicationData = [
                        'user_id' => $userId,
                        'job_id' => $jobId,
                        'application_id' => $applicationId,
                        'email' => $authenticatedUser->email,
                        'status' => 'job_selected',
                        'stage' => JobAppliedStatus::STAGES['job_selection'], // Stage 0 - job selection
                        'payment_amount' => $paymentAmount,
                        'payment_status' => 'pending',
                        'job_applied_email_sent' => false,
                        'priority' => $preferences['priority'] ?? 1,
                        'category_applied' => $preferences['category_applied'] ?? $authenticatedUser->category ?? 'UR',
                        'inserted_at' => now(),
                        'updated_at' => now()
                    ];

                    $applicationStatus = JobAppliedStatus::create($applicationData);
                    
                    // Send job selection confirmation email
                    $emailResult = $this->sendJobSelectionConfirmationEmail(
                        $authenticatedUser->email,
                        $applicationId,
                        $jobPosting,
                        $authenticatedUser->firstname . ' ' . $authenticatedUser->lastname
                    );

                    // Update email sent flag if successful
                    if ($emailResult['success']) {
                        $applicationStatus->update(['job_applied_email_sent' => true]);
                    }

                    $selectedJobs[] = [
                        'job_id' => $jobId,
                        'application_id' => $applicationId,
                        'job_title' => $jobPosting->job_title_department,
                        'category' => $jobPosting->category,
                        'payment_amount' => $paymentAmount,
                        'priority' => $applicationData['priority'],
                        'category_applied' => $applicationData['category_applied'],
                        'email_sent' => $emailResult['success'],
                        'created_at' => $applicationStatus->created_at
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'job_id' => $jobId,
                        'error' => $e->getMessage()
                    ];
                    Log::error("Error processing job selection for job {$jobId}: " . $e->getMessage());
                }
            }

            // Prepare response
            $response = [
                'success' => true,
                'message' => 'Job selection processing completed',
                'summary' => [
                    'total_jobs_submitted' => count($request->job_ids),
                    'successfully_selected' => count($selectedJobs),
                    'existing_applications' => count($existingApplications),
                    'errors_occurred' => count($errors)
                ],
                'data' => [
                    'selected_jobs' => $selectedJobs,
                ]
            ];

            // Add existing applications and errors if any
            if (!empty($existingApplications)) {
                $response['data']['existing_applications'] = $existingApplications;
            }

            if (!empty($errors)) {
                $response['data']['error_jobs'] = $errors;
            }

            // Determine HTTP status code
            if (count($selectedJobs) > 0) {
                $statusCode = 201; // Created
            } else if (count($existingApplications) > 0) {
                $statusCode = 200; // OK - existing applications found
            } else {
                $statusCode = 400; // Bad Request - errors occurred
                $response['success'] = false;
                $response['message'] = 'Failed to select jobs';
            }

            return response()->json($response, $statusCode);

        } catch (\Exception $e) {
            Log::error('Error in saveSelectedJobs: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing job selection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send job selection confirmation email
     */
    private function sendJobSelectionConfirmationEmail($email, $applicationId, $jobPosting, $fullName)
    {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = config('mail.mailers.smtp.host');
            $mail->SMTPAuth = true;
            $mail->Username = config('mail.mailers.smtp.username');
            $mail->Password = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
            $mail->Port = config('mail.mailers.smtp.port');
            
            // Recipients
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Subject = 'Job Selected Successfully - Application ID: ' . $applicationId;
            
            // Add additional headers for better email client compatibility
            $mail->addCustomHeader('X-Mailer', 'Tura Municipal Board System');
            $mail->addCustomHeader('Content-Type', 'text/html; charset=UTF-8');
            
            // Email body
            $mail->Body = $this->generateJobSelectionEmailTemplate($fullName, $applicationId, $jobPosting);
            
            $mail->send();
            
            return ['success' => true, 'message' => 'Job selection confirmation email sent successfully'];
            
        } catch (\Exception $e) {
            Log::error('Error sending job selection email: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send confirmation email: ' . $e->getMessage()];
        }
    }

    /**
     * Generate job selection email template
     */
    private function generateJobSelectionEmailTemplate($fullName, $applicationId, $jobPosting)
    {
        $applicationUrl = config('app.url') . "/application/" . $applicationId;
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Job Selection Confirmation</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: \"Times New Roman\", serif; background-color: #f8f9fa;'>
            <div style='max-width: 700px; margin: 0 auto; background: #ffffff; border: 2px solid #1a365d;'>
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #1a365d 0%, #2d5a87 100%); padding: 30px; text-align: center;'>
                    <img src='" . $this->getEmbeddedLogo() . "' alt='Tura Municipal Board' style='max-width: 100px; height: auto; border: 3px solid #ffffff; border-radius: 50%;'>
                    <h1 style='color: #ffffff; margin: 10px 0; font-size: 28px; font-weight: bold;'>Tura Municipal Board</h1>
                    <p style='color: #e2e8f0; margin: 0; font-size: 16px;'>Government of Meghalaya</p>
                </div>
                
                <!-- Content -->
                <div style='padding: 35px 40px;'>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        <div style='background: #10b981; color: #ffffff; padding: 12px 25px; display: inline-block; border-radius: 25px; margin-bottom: 15px;'>
                            <span style='font-size: 20px; margin-right: 8px;'>✅</span>
                            <span style='font-weight: bold;'>JOB SELECTED</span>
                        </div>
                        <h2 style='color: #1a365d; margin: 0; font-size: 24px;'>Job Selection Confirmed</h2>
                    </div>
                    
                    <!-- Job Details -->
                    <div style='background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 25px; margin: 25px 0;'>
                        <h3 style='color: #1a365d; margin: 0 0 15px 0; text-align: center;'>📋 JOB DETAILS</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; background: #f8fafc; font-weight: bold;'>Application ID:</td>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; font-family: monospace; color: #dc2626; font-weight: bold;'>" . $applicationId . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; background: #f8fafc; font-weight: bold;'>Job Title:</td>
                                <td style='padding: 8px; border: 1px solid #cbd5e0;'>" . htmlspecialchars($jobPosting->job_title_department) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; background: #f8fafc; font-weight: bold;'>Category:</td>
                                <td style='padding: 8px; border: 1px solid #cbd5e0;'>" . $jobPosting->category . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #cbd5e0; background: #f8fafc; font-weight: bold;'>Pay Scale:</td>
                                <td style='padding: 8px; border: 1px solid #cbd5e0;'>" . htmlspecialchars($jobPosting->pay_scale) . "</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Next Steps -->
                    <div style='background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin: 25px 0;'>
                        <h4 style='color: #92400e; margin: 0 0 15px 0; text-align: center;'>📝 COMPLETE YOUR APPLICATION</h4>
                        <ol style='color: #92400e; line-height: 1.8; margin: 0; padding-left: 25px;'>
                            <li>Submit Personal Details</li>
                            <li>Add Employment History</li>
                            <li>Upload Educational Qualifications</li>
                            <li>Submit Required Documents</li>
                            <li>Make Payment & Final Submission</li>
                        </ol>
                    </div>
                    
                    <!-- Action Button -->
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . $applicationUrl . "' style='background: #1a365d; color: #ffffff; text-decoration: none; padding: 15px 30px; border-radius: 6px; font-weight: bold; font-size: 16px;'>
                            📊 Continue Application
                        </a>
                    </div>
                </div>
                
                <!-- Footer -->
                <div style='background: #1a202c; padding: 25px; text-align: center;'>
                    <p style='color: #e2e8f0; margin: 0; font-size: 16px; font-weight: bold;'>🏛️ Tura Municipal Board</p>
                    <p style='color: #a0aec0; margin: 5px 0 0 0; font-size: 13px;'>This is an official communication. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Save multiple employment details for job application in a single request
     * Accepts array of employment records
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveEmploymentDetails(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            
            $validator = Validator::make($request->all(), [
                'job_id' => 'required|integer',
                'employment_records' => 'required|array|min:1',
                'employment_records.*.occupation_status' => 'required|string|max:50',
                'employment_records.*.is_government_employee' => 'required|boolean',
                'employment_records.*.state_where_employed' => 'required|string|max:100',
                'employment_records.*.appointment_type' => 'required|string|max:50',
                'employment_records.*.name_of_organization' => 'required|string|max:255',
                'employment_records.*.designation' => 'required|string|max:100',
                'employment_records.*.date_of_joining' => 'required|date',
                'employment_records.*.duration_in_months' => 'required|integer|min:1',
                'employment_records.*.job_description' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $authenticatedUser->id;
            $savedRecords = [];
            $duplicateRecords = [];
            $errors = [];

            // Process each employment record
            foreach ($request->employment_records as $index => $employment) {
                try {
                    // Check for existing employment record
                    $existingEmployment = JobEmploymentDetail::where([
                        'user_id' => $userId,
                        'job_id' => $request->job_id,
                        'name_of_organization' => $employment['name_of_organization'],
                        'designation' => $employment['designation'],
                        'date_of_joining' => $employment['date_of_joining'],
                    ])->first();

                    // Prepare data for insertion/update
                    $data = [
                        'user_id' => $userId,
                        'job_id' => $request->job_id,
                        'occupation_status' => $employment['occupation_status'],
                        'is_government_employee' => $employment['is_government_employee'],
                        'state_where_employed' => $employment['state_where_employed'],
                        'appointment_type' => $employment['appointment_type'],
                        'name_of_organization' => $employment['name_of_organization'],
                        'designation' => $employment['designation'],
                        'date_of_joining' => $employment['date_of_joining'],
                        'duration_in_months' => $employment['duration_in_months'],
                        'job_description' => $employment['job_description'],
                        'updated_at' => now(),
                    ];

                    if ($existingEmployment) {
                        // Update existing employment record
                        $existingEmployment->update($data);
                        $duplicateRecords[] = [
                            'index' => $index + 1,
                            'organization' => $employment['name_of_organization'],
                            'designation' => $employment['designation'],
                            'date_of_joining' => $employment['date_of_joining'],
                            'message' => 'Employment record updated successfully (replaced existing record)',
                            'action' => 'updated',
                            'record_id' => $existingEmployment->id
                        ];
                    } else {
                        // Create new employment record
                        $data['inserted_at'] = now();
                        $employmentDetails = JobEmploymentDetail::create($data);
                        $savedRecords[] = $employmentDetails;
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index + 1,
                        'organization' => $employment['name_of_organization'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Prepare response
            $response = [
                'success' => true,
                'message' => 'Employment details processing completed',
                'summary' => [
                    'total_submitted' => count($request->employment_records),
                    'successfully_saved' => count($savedRecords),
                    'records_updated' => count($duplicateRecords),
                    'errors_occurred' => count($errors)
                ],
                'data' => [
                    'saved_records' => $savedRecords,
                ]
            ];

            // Add updated and error information if any
            if (!empty($duplicateRecords)) {
                $response['data']['updated_records'] = $duplicateRecords;
            }

            if (!empty($errors)) {
                $response['data']['error_records'] = $errors;
            }

            // Determine HTTP status code
            if (count($savedRecords) > 0 || count($duplicateRecords) > 0) {
                $statusCode = 201; // Created/Updated - records were saved or updated
            } else {
                $statusCode = 400; // Bad Request - errors occurred
                $response['success'] = false;
                $response['message'] = 'Failed to save employment records';
            }

            return response()->json($response, $statusCode);

        } catch (\Exception $e) {
            Log::error('Error saving employment details: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving employment details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all employment details for a specific user and job
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmploymentDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $employmentDetails = JobEmploymentDetail::where([
                'user_id' => $request->user_id,
                'job_id' => $request->job_id,
            ])->orderBy('date_of_joining', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Employment details retrieved successfully',
                'data' => $employmentDetails,
                'total_count' => $employmentDetails->count()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving employment details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving employment details'
            ], 500);
        }
    }

    /**
     * Create a new job posting
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createJobPosting(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'job_title_department' => 'required|string|max:255',
                'vacancy_count' => 'required|integer|min:1',
                'category' => 'required|string|in:UR,OBC,SC,ST,EWS',
                'pay_scale' => 'required|string|max:100',
                'qualification' => 'required|string',
                'fee_general' => 'required|numeric|min:0',
                'fee_sc_st' => 'required|numeric|min:0',
                'fee_obc' => 'nullable|numeric|min:0',
                'status' => 'nullable|string|in:active,inactive,draft',
                'application_start_date' => 'nullable|date|after_or_equal:today',
                'application_end_date' => 'nullable|date|after:application_start_date',
                'additional_info' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Check for duplicate job posting
            $existingJob = TuraJobPosting::where([
                'job_title_department' => $request->job_title_department,
                'category' => $request->category,
            ])->first();

            if ($existingJob) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job posting already exists for this title and category',
                    'existing_job' => [
                        'id' => $existingJob->id,
                        'job_title_department' => $existingJob->job_title_department,
                        'category' => $existingJob->category,
                        'status' => $existingJob->status,
                    ]
                ], 409);
            }

            // Prepare data for insertion
            $data = $request->only([
                'job_title_department',
                'vacancy_count',
                'category',
                'pay_scale',
                'qualification',
                'fee_general',
                'fee_sc_st',
                'fee_obc',
                'application_start_date',
                'application_end_date',
                'additional_info',
            ]);

            // Set default status if not provided
            $data['status'] = $request->status ?? 'active';

            // Set default OBC fee if not provided
            if (!$data['fee_obc']) {
                $data['fee_obc'] = $data['fee_general'];
            }

            // Create job posting
            $jobPosting = TuraJobPosting::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Job posting created successfully',
                'data' => [
                    'id' => $jobPosting->id,
                    'job_title_department' => $jobPosting->job_title_department,
                    'vacancy_count' => $jobPosting->vacancy_count,
                    'category' => $jobPosting->category,
                    'pay_scale' => $jobPosting->pay_scale,
                    'qualification' => $jobPosting->qualification,
                    'fee_general' => $jobPosting->fee_general,
                    'fee_sc_st' => $jobPosting->fee_sc_st,
                    'fee_obc' => $jobPosting->fee_obc,
                    'status' => $jobPosting->status,
                    'application_start_date' => $jobPosting->application_start_date,
                    'application_end_date' => $jobPosting->application_end_date,
                    'additional_info' => $jobPosting->additional_info,
                    'is_application_open' => $jobPosting->isApplicationOpen(),
                    'created_at' => $jobPosting->created_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating job posting: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating job posting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all job postings with filters
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllJobPostings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:active,inactive,draft',
                'category' => 'nullable|string|in:UR,OBC,SC,ST,EWS',
                'application_open_only' => 'nullable|boolean',
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $query = TuraJobPosting::query();

            // Apply filters
            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->category) {
                $query->where('category', $request->category);
            }

            if ($request->application_open_only) {
                $query->applicationOpen();
            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('job_title_department', 'LIKE', '%' . $request->search . '%')
                      ->orWhere('qualification', 'LIKE', '%' . $request->search . '%')
                      ->orWhere('pay_scale', 'LIKE', '%' . $request->search . '%');
                });
            }

            // Pagination
            $perPage = $request->per_page ?? 10;
            $jobPostings = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Format data
            $formattedJobs = $jobPostings->getCollection()->map(function ($job) {
                return [
                    'id' => $job->id,
                    'job_title_department' => $job->job_title_department,
                    'vacancy_count' => $job->vacancy_count,
                    'category' => $job->category,
                    'category_name' => TuraJobPosting::CATEGORIES[$job->category] ?? $job->category,
                    'pay_scale' => $job->pay_scale,
                    'qualification' => $job->qualification,
                    'fee_general' => $job->fee_general,
                    'fee_sc_st' => $job->fee_sc_st,
                    'fee_obc' => $job->fee_obc,
                    'status' => $job->status,
                    'status_name' => TuraJobPosting::STATUSES[$job->status] ?? $job->status,
                    'application_start_date' => $job->application_start_date,
                    'application_end_date' => $job->application_end_date,
                    'additional_info' => $job->additional_info,
                    'is_application_open' => $job->isApplicationOpen(),
                    'created_at' => $job->created_at,
                    'updated_at' => $job->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Job postings retrieved successfully',
                'data' => $formattedJobs,
                'pagination' => [
                    'current_page' => $jobPostings->currentPage(),
                    'per_page' => $jobPostings->perPage(),
                    'total' => $jobPostings->total(),
                    'last_page' => $jobPostings->lastPage(),
                    'from' => $jobPostings->firstItem(),
                    'to' => $jobPostings->lastItem(),
                ],
                'filters_applied' => [
                    'status' => $request->status,
                    'category' => $request->category,
                    'application_open_only' => $request->application_open_only,
                    'search' => $request->search,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving job postings: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving job postings'
            ], 500);
        }
    }

    /**
     * Get a specific job posting by ID
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobPostingById(Request $request)
    {
        try {
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

            $jobPosting = TuraJobPosting::find($request->job_id);

            if (!$jobPosting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job posting not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job posting retrieved successfully',
                'data' => [
                    'id' => $jobPosting->id,
                    'job_title_department' => $jobPosting->job_title_department,
                    'vacancy_count' => $jobPosting->vacancy_count,
                    'category' => $jobPosting->category,
                    'category_name' => TuraJobPosting::CATEGORIES[$jobPosting->category] ?? $jobPosting->category,
                    'pay_scale' => $jobPosting->pay_scale,
                    'qualification' => $jobPosting->qualification,
                    'fee_general' => $jobPosting->fee_general,
                    'fee_sc_st' => $jobPosting->fee_sc_st,
                    'fee_obc' => $jobPosting->fee_obc,
                    'status' => $jobPosting->status,
                    'status_name' => TuraJobPosting::STATUSES[$jobPosting->status] ?? $jobPosting->status,
                    'application_start_date' => $jobPosting->application_start_date,
                    'application_end_date' => $jobPosting->application_end_date,
                    'additional_info' => $jobPosting->additional_info,
                    'is_application_open' => $jobPosting->isApplicationOpen(),
                    'created_at' => $jobPosting->created_at,
                    'updated_at' => $jobPosting->updated_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving job posting: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving job posting'
            ], 500);
        }
    }

    /**
     * Update an existing job posting
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateJobPosting(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'job_id' => 'required|integer',
                'job_title_department' => 'nullable|string|max:255',
                'vacancy_count' => 'nullable|integer|min:1',
                'category' => 'nullable|string|in:UR,OBC,SC,ST,EWS',
                'pay_scale' => 'nullable|string|max:100',
                'qualification' => 'nullable|string',
                'fee_general' => 'nullable|numeric|min:0',
                'fee_sc_st' => 'nullable|numeric|min:0',
                'fee_obc' => 'nullable|numeric|min:0',
                'status' => 'nullable|string|in:active,inactive,draft',
                'application_start_date' => 'nullable|date',
                'application_end_date' => 'nullable|date|after:application_start_date',
                'additional_info' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $jobPosting = TuraJobPosting::find($request->job_id);

            if (!$jobPosting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job posting not found'
                ], 404);
            }

            // Update only provided fields
            $updateData = array_filter($request->only([
                'job_title_department',
                'vacancy_count',
                'category',
                'pay_scale',
                'qualification',
                'fee_general',
                'fee_sc_st',
                'fee_obc',
                'status',
                'application_start_date',
                'application_end_date',
                'additional_info',
            ]), function ($value) {
                return $value !== null;
            });

            $jobPosting->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Job posting updated successfully',
                'data' => [
                    'id' => $jobPosting->id,
                    'job_title_department' => $jobPosting->job_title_department,
                    'vacancy_count' => $jobPosting->vacancy_count,
                    'category' => $jobPosting->category,
                    'category_name' => TuraJobPosting::CATEGORIES[$jobPosting->category] ?? $jobPosting->category,
                    'pay_scale' => $jobPosting->pay_scale,
                    'qualification' => $jobPosting->qualification,
                    'fee_general' => $jobPosting->fee_general,
                    'fee_sc_st' => $jobPosting->fee_sc_st,
                    'fee_obc' => $jobPosting->fee_obc,
                    'status' => $jobPosting->status,
                    'status_name' => TuraJobPosting::STATUSES[$jobPosting->status] ?? $jobPosting->status,
                    'application_start_date' => $jobPosting->application_start_date,
                    'application_end_date' => $jobPosting->application_end_date,
                    'additional_info' => $jobPosting->additional_info,
                    'is_application_open' => $jobPosting->isApplicationOpen(),
                    'updated_at' => $jobPosting->updated_at,
                ],
                'updated_fields' => array_keys($updateData)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating job posting: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating job posting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a job posting
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteJobPosting(Request $request)
    {
        try {
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

            $jobPosting = TuraJobPosting::find($request->job_id);

            if (!$jobPosting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job posting not found'
                ], 404);
            }

            // Check if there are any applications for this job
            $hasApplications = JobAppliedStatus::where('job_id', $request->job_id)->exists();

            if ($hasApplications) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete job posting. Applications exist for this job.',
                    'suggestion' => 'Consider updating the status to inactive instead.'
                ], 400);
            }

            $jobPosting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job posting deleted successfully',
                'deleted_job' => [
                    'id' => $jobPosting->id,
                    'job_title_department' => $jobPosting->job_title_department,
                    'category' => $jobPosting->category,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting job posting: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting job posting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save multiple qualification details for job application in a single request
     * Accepts array of qualification records
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveQualificationDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',  
                'qualification_records' => 'required|array|min:1',
                'qualification_records.*.additional_qualification' => 'nullable|string|max:255',
                'qualification_records.*.additional__qualification_details' => 'nullable|string|max:1000',
                'qualification_records.*.institution_name' => 'required|string|max:255',
                'qualification_records.*.board_university' => 'required|string|max:255',
                'qualification_records.*.examination_passed' => 'required|string|max:255',
                'qualification_records.*.honors_specialization' => 'nullable|string|max:255',
                'qualification_records.*.general_elective_subjects' => 'nullable|string|max:500',
                'qualification_records.*.year_of_passing' => 'required|integer|min:1980|max:' . date('Y'),
                'qualification_records.*.month_of_passing' => 'nullable|string|max:20',
                'qualification_records.*.division' => 'nullable|string|max:50',
                'qualification_records.*.percentage_obtained' => 'required|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $savedRecords = [];
            $duplicateRecords = [];
            $errors = [];

            // Process each qualification record
            foreach ($request->qualification_records as $index => $qualification) {
                try {
                    // Check for existing qualification record
                    $existingQualification = JobQualification::where([
                        'user_id' => $request->user_id,
                        'job_id' => $request->job_id,
                        'additional_qualification' => $qualification['additional_qualification'],
                        'institution_name' => $qualification['institution_name'],
                        'year_of_passing' => $qualification['year_of_passing'],
                        'examination_passed' => $qualification['examination_passed'],
                    ])->first();

                    // Prepare data for insertion/update
                    $data = [
                        'user_id' => $request->user_id,
                        'job_id' => $request->job_id,
                        'additional_qualification' => !empty($qualification['additional_qualification']) ? $qualification['additional_qualification'] : null,
                        'additional__qualification_details' => !empty($qualification['additional__qualification_details']) ? $qualification['additional__qualification_details'] : null,
                        'institution_name' => $qualification['institution_name'],
                        'board_university' => $qualification['board_university'],
                        'examination_passed' => $qualification['examination_passed'],
                        'honors_specialization' => !empty($qualification['honors_specialization']) ? $qualification['honors_specialization'] : null,
                        'general_elective_subjects' => !empty($qualification['general_elective_subjects']) ? $qualification['general_elective_subjects'] : null,
                        'year_of_passing' => $qualification['year_of_passing'],
                        'month_of_passing' => !empty($qualification['month_of_passing']) ? $qualification['month_of_passing'] : null,
                        'division' => !empty($qualification['division']) ? $qualification['division'] : null,
                        'percentage_obtained' => $qualification['percentage_obtained'],
                        'updated_at' => now(),
                    ];

                    if ($existingQualification) {
                        // Update existing qualification record
                        $existingQualification->update($data);
                        $duplicateRecords[] = [
                            'index' => $index + 1,
                            'qualification' => $qualification['additional_qualification'],
                            'institution' => $qualification['institution_name'],
                            'year' => $qualification['year_of_passing'],
                            'message' => 'Qualification record updated successfully (replaced existing record)',
                            'action' => 'updated',
                            'record_id' => $existingQualification->id
                        ];
                    } else {
                        // Create new qualification record
                        $data['inserted_at'] = now();
                        $qualificationDetails = JobQualification::create($data);
                        $savedRecords[] = $qualificationDetails;
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index + 1,
                        'qualification' => $qualification['additional_qualification'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Prepare response
            $response = [
                'success' => true,
                'message' => 'Qualification details processing completed',
                'summary' => [
                    'total_submitted' => count($request->qualification_records),
                    'successfully_saved' => count($savedRecords),
                    'records_updated' => count($duplicateRecords),
                    'errors_occurred' => count($errors)
                ],
                'data' => [
                    'saved_records' => $savedRecords,
                ]
            ];

            // Add updated and error information if any
            if (!empty($duplicateRecords)) {
                $response['data']['updated_records'] = $duplicateRecords;
            }

            if (!empty($errors)) {
                $response['data']['error_records'] = $errors;
            }

            // Determine HTTP status code
            if (count($savedRecords) > 0 || count($duplicateRecords) > 0) {
                $statusCode = 201; // Created/Updated - records were saved or updated
            } else {
                $statusCode = 400; // Bad Request - errors occurred
                $response['success'] = false;
                $response['message'] = 'Failed to save qualification records';
            }

            return response()->json($response, $statusCode);

        } catch (\Exception $e) {
            Log::error('Error saving qualification details: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving qualification details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all qualification details for a specific user and job
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQualificationDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $qualificationDetails = JobQualification::where([
                'user_id' => $request->user_id,
                'job_id' => $request->job_id,
            ])->orderBy('year_of_passing', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Qualification details retrieved successfully',
                'data' => $qualificationDetails,
                'total_count' => $qualificationDetails->count()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving qualification details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving qualification details'
            ], 500);
        }
    }

    /**
     * Upload multiple documents for job application
     * Handles specific document types with custom validation rules
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocuments(Request $request)
    {
        try {
            // Get document requirements from model
            $documentRequirements = JobDocumentUpload::getDocumentRequirements();

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',
                'documents' => 'required|array|min:1',
                'documents.*' => 'required|file',
                'document_types' => 'required|array|min:1',
                'document_types.*' => 'required|string|in:' . implode(',', array_keys($documentRequirements)),
            ], [
                'document_types.*.in' => 'Invalid document type. Allowed types: ' . implode(', ', array_keys($documentRequirements)),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Check if documents and document_types arrays have same count
            if (count($request->file('documents')) !== count($request->document_types)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Number of documents and document types must match',
                    'error' => 'Mismatch between files and types count'
                ], 400);
            }

            // Get all mandatory document types
            $mandatoryDocuments = JobDocumentUpload::getMandatoryDocumentTypes();

            // Check if all mandatory documents are provided
            $providedDocumentTypes = $request->document_types;
            $missingMandatoryDocuments = array_diff($mandatoryDocuments, $providedDocumentTypes);

            if (!empty($missingMandatoryDocuments)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing mandatory documents',
                    'error' => 'The following mandatory documents are required: ' . implode(', ', $missingMandatoryDocuments),
                    'mandatory_documents' => $mandatoryDocuments,
                    'missing_documents' => $missingMandatoryDocuments,
                    'provided_documents' => $providedDocumentTypes
                ], 400);
            }

            $uploadedDocuments = [];
            $duplicateDocuments = [];
            $errors = [];

            // Process each document with its corresponding type
            foreach ($request->file('documents') as $index => $file) {
                try {
                    $documentType = $request->document_types[$index];
                    $originalFileName = $file->getClientOriginalName();
                    $fileExtension = strtolower($file->getClientOriginalExtension());
                    $fileSizeKB = round($file->getSize() / 1024, 2);
                    
                    // Get requirements for this document type
                    $requirements = $documentRequirements[$documentType];
                    
                    // Validate file type
                    if (!in_array($fileExtension, $requirements['types'])) {
                        $errors[] = [
                            'index' => $index + 1,
                            'document_type' => $documentType,
                            'original_filename' => $originalFileName,
                            'error' => "Invalid file type. Allowed types for {$documentType}: " . implode(', ', $requirements['types'])
                        ];
                        continue;
                    }
                    
                    // Validate file size
                    if ($fileSizeKB > $requirements['max_size']) {
                        $maxSizeMB = $requirements['max_size'] / 1024;
                        $errors[] = [
                            'index' => $index + 1,
                            'document_type' => $documentType,
                            'original_filename' => $originalFileName,
                            'error' => "File size ({$fileSizeKB}KB) exceeds maximum allowed size ({$maxSizeMB}MB) for {$documentType}"
                        ];
                        continue;
                    }
                    
                    // Check for duplicate document (same type for same user and job)
                    $existingDocument = JobDocumentUpload::where([
                        'user_id' => $request->user_id,
                        'job_id' => $request->job_id,
                        'document_type' => $documentType,
                    ])->first();

                    if ($existingDocument) {
                        // Update existing document instead of creating duplicate
                        $fileContent = file_get_contents($file->getRealPath());
                        $base64 = base64_encode($fileContent);
                        $mimeType = $file->getMimeType();
                        $base64WithPrefix = 'data:' . $mimeType . ';base64,' . $base64;

                        $existingDocument->update([
                            'file_name' => $originalFileName,
                            'file_extension' => $fileExtension,
                            'file_size' => $file->getSize(),
                            'file_base64' => $base64WithPrefix,
                            'updated_at' => now(),
                        ]);

                        $duplicateDocuments[] = [
                            'index' => $index + 1,
                            'document_type' => $documentType,
                            'original_filename' => $originalFileName,
                            'message' => "Document of type '{$documentType}' updated successfully (replaced existing document)",
                            'action' => 'updated'
                        ];
                        continue;
                    }

                    // Convert file to base64
                    $fileContent = file_get_contents($file->getRealPath());
                    $base64 = base64_encode($fileContent);
                    $mimeType = $file->getMimeType();
                    $base64WithPrefix = 'data:' . $mimeType . ';base64,' . $base64;

                    // Prepare data for insertion
                    $data = [
                        'user_id' => $request->user_id,
                        'job_id' => $request->job_id,
                        'document_type' => $documentType,
                        'file_name' => $originalFileName,
                        'file_extension' => $fileExtension,
                        'file_size' => $file->getSize(),
                        'is_mandatory' => $requirements['mandatory'],
                        'file_base64' => $base64WithPrefix,
                        'uploaded_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Save document record
                    $documentRecord = JobDocumentUpload::create($data);
                    
                    // Add file info to response (without base64 for readability)
                    $uploadedDocuments[] = [
                        'id' => $documentRecord->id,
                        'document_type' => $documentRecord->document_type,
                        'file_name' => $documentRecord->file_name,
                        'file_extension' => $documentRecord->file_extension,
                        'file_size_bytes' => $documentRecord->file_size,
                        'file_size_kb' => round($documentRecord->file_size / 1024, 2),
                        'is_mandatory' => $documentRecord->is_mandatory,
                        'uploaded_at' => $documentRecord->uploaded_at,
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index + 1,
                        'document_type' => $request->document_types[$index] ?? 'Unknown',
                        'original_filename' => $file->getClientOriginalName() ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Get application completion status
            $completionStatus = JobDocumentUpload::checkMandatoryComplete($request->user_id, $request->job_id);
            $uploadSummary = JobDocumentUpload::getUploadSummary($request->user_id, $request->job_id);

            // Prepare response
            $response = [
                'success' => true,
                'message' => 'Document upload processing completed',
                'summary' => [
                    'total_submitted' => count($request->file('documents')),
                    'successfully_uploaded' => count($uploadedDocuments),
                    'duplicates_updated' => count($duplicateDocuments),
                    'validation_errors' => count($errors),
                    'application_complete' => $completionStatus['is_complete']
                ],
                'application_status' => [
                    'is_complete' => $completionStatus['is_complete'],
                    'mandatory_uploaded' => $uploadSummary['mandatory_uploaded'],
                    'total_mandatory_required' => $completionStatus['total_mandatory'],
                    'missing_mandatory' => $completionStatus['missing_documents'],
                    'total_documents' => $uploadSummary['total_documents']
                ],
                'data' => [
                    'uploaded_documents' => $uploadedDocuments,
                ]
            ];

            // Add duplicate and error information if any
            if (!empty($duplicateDocuments)) {
                $response['data']['updated_documents'] = $duplicateDocuments;
            }

            if (!empty($errors)) {
                $response['data']['error_documents'] = $errors;
            }

            // Determine HTTP status code
            if (count($uploadedDocuments) > 0 || count($duplicateDocuments) > 0) {
                $statusCode = 201; // Created/Updated
            } else {
                $statusCode = 400; // Bad Request - errors occurred
                $response['success'] = false;
                $response['message'] = 'Failed to upload documents';
            }

            return response()->json($response, $statusCode);

        } catch (\Exception $e) {
            Log::error('Error uploading documents: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a single document by document ID or document type
     * Allows users to replace/update any existing document
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDocument(Request $request)
    {
        try {
            // Get document requirements from model
            $documentRequirements = JobDocumentUpload::getDocumentRequirements();

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',
                'document' => 'required|file',
                'document_type' => 'required|string|in:' . implode(',', array_keys($documentRequirements)),
                'document_id' => 'nullable|integer', // Optional: specific document ID to update
            ], [
                'document_type.in' => 'Invalid document type. Allowed types: ' . implode(', ', array_keys($documentRequirements)),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $file = $request->file('document');
            $documentType = $request->document_type;
            $documentId = $request->document_id;
            $originalFileName = $file->getClientOriginalName();
            $fileExtension = strtolower($file->getClientOriginalExtension());
            $fileSizeKB = round($file->getSize() / 1024, 2);
            
            // Get requirements for this document type
            $requirements = $documentRequirements[$documentType];
            
            // Validate file type
            if (!in_array($fileExtension, $requirements['types'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type',
                    'error' => "Invalid file type. Allowed types for {$documentType}: " . implode(', ', $requirements['types']),
                    'allowed_types' => $requirements['types']
                ], 400);
            }
            
            // Validate file size
            if ($fileSizeKB > $requirements['max_size']) {
                $maxSizeMB = $requirements['max_size'] / 1024;
                return response()->json([
                    'success' => false,
                    'message' => 'File size exceeds limit',
                    'error' => "File size ({$fileSizeKB}KB) exceeds maximum allowed size ({$maxSizeMB}MB) for {$documentType}",
                    'max_size_kb' => $requirements['max_size'],
                    'current_size_kb' => $fileSizeKB
                ], 400);
            }

            // Find the document to update
            $query = JobDocumentUpload::where([
                'user_id' => $request->user_id,
                'job_id' => $request->job_id,
                'document_type' => $documentType,
            ]);

            // If document_id is provided, add it to the query for more specific targeting
            if ($documentId) {
                $query->where('id', $documentId);
            }

            $existingDocument = $query->first();

            if (!$existingDocument) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                    'error' => "No existing document of type '{$documentType}' found for this user and job" . ($documentId ? " with ID {$documentId}" : ''),
                    'suggestion' => 'Use uploadDocuments API to upload a new document'
                ], 404);
            }

            // Convert file to base64
            $fileContent = file_get_contents($file->getRealPath());
            $base64 = base64_encode($fileContent);
            $mimeType = $file->getMimeType();
            $base64WithPrefix = 'data:' . $mimeType . ';base64,' . $base64;

            // Store old document info for response
            $oldDocumentInfo = [
                'id' => $existingDocument->id,
                'file_name' => $existingDocument->file_name,
                'file_extension' => $existingDocument->file_extension,
                'file_size_kb' => round($existingDocument->file_size / 1024, 2),
                'uploaded_at' => $existingDocument->uploaded_at
            ];

            // Update the existing document
            $existingDocument->update([
                'file_name' => $originalFileName,
                'file_extension' => $fileExtension,
                'file_size' => $file->getSize(),
                'file_base64' => $base64WithPrefix,
                'updated_at' => now(),
            ]);

            // Refresh the model to get updated data
            $existingDocument->refresh();

            // Get application completion status after update
            $completionStatus = JobDocumentUpload::checkMandatoryComplete($request->user_id, $request->job_id);
            $uploadSummary = JobDocumentUpload::getUploadSummary($request->user_id, $request->job_id);

            $response = [
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => [
                    'updated_document' => [
                        'id' => $existingDocument->id,
                        'document_type' => $existingDocument->document_type,
                        'file_name' => $existingDocument->file_name,
                        'file_extension' => $existingDocument->file_extension,
                        'file_size_bytes' => $existingDocument->file_size,
                        'file_size_kb' => round($existingDocument->file_size / 1024, 2),
                        'is_mandatory' => $existingDocument->is_mandatory,
                        'updated_at' => $existingDocument->updated_at,
                    ],
                    'previous_document' => $oldDocumentInfo,
                    'application_status' => [
                        'is_complete' => $completionStatus['is_complete'],
                        'mandatory_uploaded' => $uploadSummary['mandatory_uploaded'],
                        'total_mandatory_required' => $completionStatus['total_mandatory'],
                        'missing_mandatory' => $completionStatus['missing_documents'],
                        'total_documents' => $uploadSummary['total_documents']
                    ]
                ]
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Error updating document: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific document
     * Allows users to remove any uploaded document
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',
                'document_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Find the document
            $document = JobDocumentUpload::where([
                'id' => $request->document_id,
                'user_id' => $request->user_id,
                'job_id' => $request->job_id,
            ])->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                    'error' => "No document found with ID {$request->document_id} for this user and job"
                ], 404);
            }

            // Store document info before deletion
            $deletedDocumentInfo = [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'file_name' => $document->file_name,
                'file_extension' => $document->file_extension,
                'file_size_kb' => round($document->file_size / 1024, 2),
                'is_mandatory' => $document->is_mandatory,
                'uploaded_at' => $document->uploaded_at
            ];

            // Check if this is a mandatory document
            $isMandatory = $document->is_mandatory;
            
            // Delete the document
            $document->delete();

            // Get updated application status after deletion
            $completionStatus = JobDocumentUpload::checkMandatoryComplete($request->user_id, $request->job_id);
            $uploadSummary = JobDocumentUpload::getUploadSummary($request->user_id, $request->job_id);

            $response = [
                'success' => true,
                'message' => 'Document deleted successfully',
                'data' => [
                    'deleted_document' => $deletedDocumentInfo,
                    'application_status' => [
                        'is_complete' => $completionStatus['is_complete'],
                        'mandatory_uploaded' => $uploadSummary['mandatory_uploaded'],
                        'total_mandatory_required' => $completionStatus['total_mandatory'],
                        'missing_mandatory' => $completionStatus['missing_documents'],
                        'total_documents' => $uploadSummary['total_documents']
                    ]
                ]
            ];

            // Add warning if mandatory document was deleted
            if ($isMandatory) {
                $response['warning'] = 'You have deleted a mandatory document. Your application will be incomplete until you re-upload this document type.';
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all uploaded documents for a specific user and job
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUploadedDocuments(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',
                'include_base64' => 'boolean', // Optional: whether to include base64 data
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $query = JobDocumentUpload::where([
                'user_id' => $request->user_id,
                'job_id' => $request->job_id,
            ]);

            // If include_base64 is false, exclude base64 field for performance
            if (!$request->get('include_base64', false)) {
                $query->select([
                    'id', 'user_id', 'job_id', 'document_type', 'file_name', 
                    'file_extension', 'file_size', 'is_mandatory', 'uploaded_at'
                ]);
            }

            $documents = $query->orderBy('uploaded_at', 'desc')->get();

            // Get application status
            $completionStatus = JobDocumentUpload::checkMandatoryComplete($request->user_id, $request->job_id);
            $uploadSummary = JobDocumentUpload::getUploadSummary($request->user_id, $request->job_id);

            // Format documents for response
            $formattedDocuments = $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'document_type' => $doc->document_type,
                    'file_name' => $doc->file_name,
                    'file_extension' => $doc->file_extension,
                    'file_size_bytes' => $doc->file_size,
                    'file_size_kb' => round($doc->file_size / 1024, 2),
                    'is_mandatory' => $doc->is_mandatory,
                    'uploaded_at' => $doc->uploaded_at,
                    'file_base64' => $request->get('include_base64', false) ? $doc->file_base64 : null
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Documents retrieved successfully',
                'application_status' => [
                    'is_complete' => $completionStatus['is_complete'],
                    'mandatory_uploaded' => $uploadSummary['mandatory_uploaded'],
                    'total_mandatory_required' => $completionStatus['total_mandatory'],
                    'missing_mandatory' => $completionStatus['missing_documents'],
                    'total_documents' => $uploadSummary['total_documents']
                ],
                'data' => $formattedDocuments,
                'total_count' => $documents->count()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving documents: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving documents'
            ], 500);
        }
    }

    /**
     * Download a specific document by ID
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function downloadDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'document_id' => 'required|integer',
                'user_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $document = JobDocumentUpload::where([
                'id' => $request->document_id,
                'user_id' => $request->user_id,
            ])->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found or access denied'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document retrieved successfully',
                'data' => [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'file_name' => $document->file_name,
                    'file_extension' => $document->file_extension,
                    'file_size' => $document->file_size,
                    'is_mandatory' => $document->is_mandatory,
                    'file_base64' => $document->file_base64,
                    'uploaded_at' => $document->uploaded_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error downloading document: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error downloading document'
            ], 500);
        }
    }

    /**
     * Get document requirements and mandatory documents list
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDocumentRequirements()
    {
        try {
            // Get document requirements from model
            $documentRequirements = JobDocumentUpload::getDocumentRequirements();

            // Separate mandatory and optional documents
            $mandatoryDocuments = [];
            $optionalDocuments = [];

            foreach ($documentRequirements as $docType => $requirements) {
                if ($requirements['mandatory']) {
                    $mandatoryDocuments[$docType] = $requirements;
                } else {
                    $optionalDocuments[$docType] = $requirements;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Document requirements retrieved successfully',
                'data' => [
                    'all_documents' => $documentRequirements,
                    'mandatory_documents' => $mandatoryDocuments,
                    'optional_documents' => $optionalDocuments,
                    'mandatory_count' => count($mandatoryDocuments),
                    'optional_count' => count($optionalDocuments),
                    'total_document_types' => count($documentRequirements)
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting document requirements: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving document requirements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get complete job application details for a user
     * Includes: Personal Details, Employment History, Qualifications, and Documents
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCompleteApplicationDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',
                'include_base64' => 'boolean', // Optional: whether to include document base64 data
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $request->user_id;
            $jobId = $request->job_id;
            $includeBase64 = $request->get('include_base64', true); // Default to true - always include base64

            // 1. Get Personal Details
            $personalDetails = JobPersonalDetail::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();

            // 2. Get Employment Details
            $employmentDetails = JobEmploymentDetail::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->orderBy('date_of_joining', 'desc')->get();

            // 3. Get Qualification Details
            $qualificationDetails = JobQualification::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->orderBy('year_of_passing', 'desc')->get();

            // 4. Get Document Details with base64 by default
            $documentsQuery = JobDocumentUpload::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ]);

            // Always include all fields including base64 by default
            // Only exclude base64 if explicitly requested not to include
            if (!$includeBase64) {
                $documentsQuery->select([
                    'id', 'user_id', 'job_id', 'document_type', 'file_name', 
                    'file_extension', 'file_size', 'is_mandatory', 'uploaded_at'
                ]);
            }

            $documents = $documentsQuery->orderBy('is_mandatory', 'desc')
                                       ->orderBy('uploaded_at', 'desc')
                                       ->get();

            // 5. Get Application Status
            $documentStatus = JobDocumentUpload::checkMandatoryComplete($userId, $jobId);
            $uploadSummary = JobDocumentUpload::getUploadSummary($userId, $jobId);

            // 6. Calculate Application Completion Percentage
            $completionData = [
                'personal_details' => $personalDetails ? 1 : 0,
                'employment_details' => $employmentDetails->count() > 0 ? 1 : 0,
                'qualification_details' => $qualificationDetails->count() > 0 ? 1 : 0,
                'mandatory_documents' => $documentStatus['is_complete'] ? 1 : 0,
            ];

            $totalSections = 4;
            $completedSections = array_sum($completionData);
            $completionPercentage = round(($completedSections / $totalSections) * 100, 2);

            // 7. Format Documents for Response
            $formattedDocuments = $documents->map(function ($doc) use ($includeBase64) {
                $docData = [
                    'id' => $doc->id,
                    'document_type' => $doc->document_type,
                    'file_name' => $doc->file_name,
                    'file_extension' => $doc->file_extension,
                    'file_size_bytes' => $doc->file_size,
                    'file_size_kb' => round($doc->file_size / 1024, 2),
                    'is_mandatory' => $doc->is_mandatory,
                    'uploaded_at' => $doc->uploaded_at,
                ];

                if ($includeBase64) {
                    $docData['file_base64'] = $doc->file_base64;
                }

                return $docData;
            });

            // 8. Separate Mandatory and Optional Documents
            $mandatoryDocs = $formattedDocuments->where('is_mandatory', true)->values();
            $optionalDocs = $formattedDocuments->where('is_mandatory', false)->values();

            // 9. Get Job Details
            $jobDetails = JobPosting::find($jobId);

            // 10. Build Response with organized structure
            $response = [
                'success' => true,
                'message' => 'Complete application details retrieved successfully',
                'user_id' => $userId,
                'job_id' => $jobId,
                'application_summary' => [
                    'completion_percentage' => $completionPercentage,
                    'is_application_complete' => $completionPercentage == 100,
                    'completed_sections' => $completedSections,
                    'total_sections' => $totalSections,
                    'sections_status' => [
                        'personal_details_completed' => $completionData['personal_details'] == 1,
                        'employment_details_completed' => $completionData['employment_details'] == 1,
                        'qualification_details_completed' => $completionData['qualification_details'] == 1,
                        'mandatory_documents_completed' => $completionData['mandatory_documents'] == 1,
                    ]
                ],
                'personal_details' => $personalDetails ? [
                    'status' => 'completed',
                    'data' => $personalDetails
                ] : [
                    'status' => 'not_completed',
                    'data' => null
                ],
                'employment_details' => [
                    'status' => $employmentDetails->count() > 0 ? 'completed' : 'not_completed',
                    'total_records' => $employmentDetails->count(),
                    'data' => $employmentDetails
                ],
                'qualification_details' => [
                    'status' => $qualificationDetails->count() > 0 ? 'completed' : 'not_completed',
                    'total_records' => $qualificationDetails->count(),
                    'data' => $qualificationDetails
                ],
                'documents' => [
                    'status' => $documentStatus['is_complete'] ? 'completed' : 'pending',
                    'mandatory_documents' => [
                        'status' => $documentStatus['is_complete'] ? 'completed' : 'pending',
                        'uploaded_count' => $uploadSummary['mandatory_uploaded'],
                        'required_count' => $documentStatus['total_mandatory'],
                        'missing_documents' => $documentStatus['missing_documents'],
                        'data' => $mandatoryDocs
                    ],
                    'optional_documents' => [
                        'status' => $uploadSummary['optional_uploaded'] > 0 ? 'uploaded' : 'not_uploaded',
                        'uploaded_count' => $uploadSummary['optional_uploaded'],
                        'data' => $optionalDocs
                    ],
                    'total_documents_uploaded' => $uploadSummary['total_documents']
                ]
            ];

            // Add job details if found
            if ($jobDetails) {
                $response['job_details'] = [
                    'status' => 'found',
                    'data' => [
                        'id' => $jobDetails->id,
                        'title' => $jobDetails->title,
                        'description' => $jobDetails->description,
                        'location' => $jobDetails->location,
                        'salary_range' => $jobDetails->salary_range,
                        'employment_type' => $jobDetails->employment_type,
                        'application_deadline' => $jobDetails->application_deadline,
                    ]
                ];
            } else {
                $response['job_details'] = [
                    'status' => 'not_found',
                    'data' => null
                ];
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving complete application details: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving complete application details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get application progress and redirect to next section
     * This API determines what section user should work on next
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApplicationProgress(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'nullable|integer', // job_id is optional for initial stage
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $request->user_id;
            $jobId = $request->job_id;

            // If no job_id provided, user is in Stage 0 (form selection)
            if (!$jobId) {
                return response()->json([
                    'success' => true,
                    'message' => 'User needs to select a job first',
                    'user_id' => $userId,
                    'job_id' => null,
                    'application_status' => [
                        'status' => 'not_started',
                        'current_stage' => 0,
                        'current_stage_name' => 'job_selection',
                        'is_completed' => false,
                    ],
                    'progress' => [
                        'completion_percentage' => 0,
                        'completed_sections' => [],
                        'next_section' => 'job_selection',
                        'sections_status' => [
                            'job_selection' => false,
                            'personal_details' => false,
                            'qualification' => false,
                            'employment' => false,
                            'file_upload' => false,
                            'application_summary' => false,
                            'payment' => false,
                            'print_application' => false
                        ]
                    ],
                    'redirect_to' => [
                        'section' => 'job_selection',
                        'action' => 'select_job',
                        'message' => 'Please select a job to start your application'
                    ],
                    'existing_data' => [
                        'selected_job' => null,
                        'personal_details' => null,
                        'employment_details' => [],
                        'qualification_details' => [],
                        'uploaded_documents' => [],
                        'payment_details' => null
                    ]
                ], 200);
            }

            // Continue with existing logic for job-specific progress
            $applicationStatus = JobAppliedStatus::firstOrCreate(
                [
                    'user_id' => $userId,
                    'job_id' => $jobId
                ],
                [
                    'status' => JobAppliedStatus::STATUSES['draft'],
                    'stage' => JobAppliedStatus::STAGES['personal_details'],
                    'inserted_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Check what sections are completed
            $completedSections = $this->checkCompletedSections($userId, $jobId);
            
            // Determine current stage based on completed sections
            $calculatedStage = $this->calculateCurrentStage($completedSections);
            
            // Update stage if calculated stage is higher
            if ($calculatedStage > $applicationStatus->stage) {
                $applicationStatus->updateStage($calculatedStage);
            }

            // Determine next section to work on
            $nextSection = $this->getNextSection($completedSections);
            
            // Get existing data for pre-filling forms
            $existingData = $this->getExistingApplicationData($userId, $jobId);

            $response = [
                'success' => true,
                'message' => 'Application progress retrieved successfully',
                'user_id' => $userId,
                'job_id' => $jobId,
                'application_status' => [
                    'id' => $applicationStatus->id,
                    'application_id' => $applicationStatus->application_id,
                    'status' => $applicationStatus->status,
                    'current_stage' => $applicationStatus->stage,
                    'current_stage_name' => $applicationStatus->getCurrentStageName(),
                    'is_completed' => $applicationStatus->isCompleted(),
                ],
                'progress' => [
                    'completion_percentage' => $this->calculateCompletionPercentage($completedSections, $applicationStatus),
                    'completed_sections' => array_keys(array_filter($completedSections)),
                    'next_section' => $nextSection,
                    'sections_status' => $this->buildCompleteSectionsStatus($completedSections, $applicationStatus->stage, $applicationStatus)
                ],
                'redirect_to' => [
                    'section' => $nextSection,
                    'action' => $nextSection === 'application_summary' ? 'review_application' : 'fill_section',
                    'message' => $this->getRedirectMessage($nextSection)
                ],
                'existing_data' => $existingData
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Error getting application progress: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error getting application progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check which sections are completed
     */
    private function checkCompletedSections($userId, $jobId)
    {
        try {
            // Check personal details
            $personalDetails = false;
            try {
                $personalDetails = JobPersonalDetail::where([
                    'user_id' => $userId,
                    'job_id' => $jobId
                ])->exists();
            } catch (\Exception $e) {
                \Log::warning('Personal details check failed: ' . $e->getMessage());
            }

            // Check employment details
            $employmentDetails = false;
            try {
                $employmentDetails = JobEmploymentDetail::where([
                    'user_id' => $userId,
                    'job_id' => $jobId
                ])->exists();
            } catch (\Exception $e) {
                \Log::warning('Employment details check failed: ' . $e->getMessage());
            }

            // Check qualification details
            $qualificationDetails = false;
            try {
                $qualificationDetails = JobQualification::where([
                    'user_id' => $userId,
                    'job_id' => $jobId
                ])->exists();
            } catch (\Exception $e) {
                \Log::warning('Qualification details check failed: ' . $e->getMessage());
            }

            // Check mandatory documents - with fallback if table doesn't exist
            $isDocumentComplete = false;
            try {
                // Check if table exists first
                if (\Schema::hasTable('tura_job_documents_upload')) {
                    $documentStatus = JobDocumentUpload::checkMandatoryComplete($userId, $jobId);
                    if (is_array($documentStatus) && isset($documentStatus['is_complete'])) {
                        $isDocumentComplete = $documentStatus['is_complete'];
                    }
                } else {
                    \Log::info('Document upload table does not exist yet');
                }
            } catch (\Exception $docException) {
                \Log::warning('Document check failed: ' . $docException->getMessage());
            }

            return [
                'personal_details' => $personalDetails,
                'employment_details' => $employmentDetails,
                'qualification_details' => $qualificationDetails,
                'document_upload' => $isDocumentComplete
            ];
        } catch (\Exception $e) {
            \Log::error('Error in checkCompletedSections: ' . $e->getMessage());
            \Log::error('UserId: ' . $userId . ', JobId: ' . $jobId);
            
            // Return default values if error occurs
            return [
                'personal_details' => false,
                'employment_details' => false,
                'qualification_details' => false,
                'document_upload' => false
            ];
        }
    }

    /**
     * Calculate current stage based on completed sections
     */
    private function calculateCurrentStage($completedSections)
    {
        if ($completedSections['document_upload']) {
            return JobAppliedStatus::STAGES['print_application'];
        } elseif ($completedSections['employment_details']) {
            return JobAppliedStatus::STAGES['file_upload'];
        } elseif ($completedSections['qualification_details']) {
            return JobAppliedStatus::STAGES['employment'];
        } elseif ($completedSections['personal_details']) {
            return JobAppliedStatus::STAGES['qualification'];
        } else {
            return JobAppliedStatus::STAGES['personal_details'];
        }
    }

    /**
     * Get next section to work on
     */
    private function getNextSection($completedSections)
    {
        if (!$completedSections['personal_details']) {
            return 'personal_details';
        } elseif (!$completedSections['qualification_details']) {
            return 'qualification_details';
        } elseif (!$completedSections['employment_details']) {
            return 'employment_details';
        } elseif (!$completedSections['document_upload']) {
            return 'file_upload';
        } else {
            return 'application_summary';
        }
    }

    /**
     * Calculate completion percentage based on JobAppliedStatus 8-stage system
     */
    private function calculateCompletionPercentage($completedSections, $applicationStatus = null)
    {
        // JobAppliedStatus has 8 stages (0-7), so total stages = 8
        $totalStages = count(JobAppliedStatus::STAGES);
        
        // Count completed stages based on the correct flow: personal -> qualification -> employment -> documents
        $completedStages = 1; // Stage 0 (job_selection) is completed when we reach this method
        
        if ($completedSections['personal_details']) {
            $completedStages = 2; // Stage 1 (personal_details) completed
        }
        
        if ($completedSections['qualification_details']) {
            $completedStages = 3; // Stage 2 (qualification) completed
        }
        
        if ($completedSections['employment_details']) {
            $completedStages = 4; // Stage 3 (employment) completed  
        }
        
        if ($completedSections['document_upload']) {
            $completedStages = 5; // Stage 4 (file_upload) completed
        }
        
        // Check if application summary is ready (all previous sections completed)
        if ($completedSections['document_upload']) {
            $completedStages = 6; // Stage 5 (application_summary) completed
        }
        
        // Check if payment is actually completed (payment_status = 'paid')
        if ($applicationStatus && $applicationStatus->payment_status === 'paid') {
            $completedStages = 7; // Stage 6 (payment) completed
        }
        
        // Print application is only completed after payment
        if ($applicationStatus && $applicationStatus->payment_status === 'paid' && 
            $applicationStatus->stage >= JobAppliedStatus::STAGES['print_application']) {
            $completedStages = 8; // Stage 7 (print_application) completed
        }
        
        return round(($completedStages / $totalStages) * 100, 2);
    }

    /**
     * Build complete sections status including all 8 stages from JobAppliedStatus
     */
    private function buildCompleteSectionsStatus($completedSections, $currentStage, $applicationStatus = null)
    {
        // Check if payment is actually completed (payment_status = 'paid')
        $paymentCompleted = false;
        if ($applicationStatus && $applicationStatus->payment_status === 'paid') {
            $paymentCompleted = true;
        }

        // Map the 4-section system to all 8 stages in correct order: personal -> qualification -> employment -> documents
        $allSectionsStatus = [
            'job_selection' => true, // Always true when we reach this method with job_id
            'personal_details' => $completedSections['personal_details'],
            'qualification' => $completedSections['qualification_details'],
            'employment' => $completedSections['employment_details'],
            'file_upload' => $completedSections['document_upload'],
            'application_summary' => $currentStage >= JobAppliedStatus::STAGES['application_summary'],
            'payment' => $paymentCompleted, // Only completed when payment_status = 'paid'
            'print_application' => $paymentCompleted && $currentStage >= JobAppliedStatus::STAGES['print_application']
        ];

        return $allSectionsStatus;
    }

    /**
     * Get existing application data for pre-filling
     */
    private function getExistingApplicationData($userId, $jobId)
    {
        // Get selected job details
        $selectedJob = TuraJobPosting::find($jobId);

        // Get personal details
        $personalDetails = null;
        try {
            $personalDetails = JobPersonalDetail::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();
        } catch (\Exception $e) {
            \Log::warning('Failed to get personal details: ' . $e->getMessage());
        }

        // Get payment details from tura_job_applied_status
        $paymentDetails = null;
        try {
            $applicationStatus = JobAppliedStatus::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();
            
            if ($applicationStatus) {
                $paymentDetails = [
                    'applicable_fee' => $applicationStatus->payment_amount,
                    'payment_status' => $applicationStatus->payment_status,
                    'payment_transaction_id' => $applicationStatus->payment_transaction_id ?? null,
                    'payment_date' => $applicationStatus->payment_date ?? null,
                    'job_applied_email_sent' => $applicationStatus->job_applied_email_sent,
                    'payment_confirmation_email_sent' => $applicationStatus->payment_confirmation_email_sent ?? false,
                ];
                
                // Add additional context if personal details exist
                if ($personalDetails) {
                    $paymentDetails['category'] = strtolower($personalDetails->category);
                    $paymentDetails['fee_type'] = $this->getCategoryFeeType($personalDetails->category);
                }
                
                if ($selectedJob) {
                    $paymentDetails['pay_scale'] = $selectedJob->pay_scale;
                    $paymentDetails['fee_breakdown'] = [
                        'general_fee' => $selectedJob->fee_general,
                        'sc_st_fee' => $selectedJob->fee_sc_st,
                        'obc_fee' => $selectedJob->fee_obc
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to get payment details from application status: ' . $e->getMessage());
        }

        // Get employment details
        $employmentDetails = [];
        try {
            $employmentDetails = JobEmploymentDetail::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->get();
        } catch (\Exception $e) {
            \Log::warning('Failed to get employment details: ' . $e->getMessage());
        }

        // Get qualification details
        $qualificationDetails = [];
        try {
            $qualificationDetails = JobQualification::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->get();
        } catch (\Exception $e) {
            \Log::warning('Failed to get qualification details: ' . $e->getMessage());
        }

        // Get uploaded documents
        $uploadedDocuments = [];
        try {
            if (\Schema::hasTable('tura_job_documents_upload')) {
                $uploadedDocuments = JobDocumentUpload::where([
                    'user_id' => $userId,
                    'job_id' => $jobId
                ])->select(['document_type', 'file_name', 'uploaded_at'])->get();
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to get uploaded documents: ' . $e->getMessage());
        }

        return [
            'selected_job' => $selectedJob ? [
                'id' => $selectedJob->id,
                'job_title_department' => $selectedJob->job_title_department,
                'vacancy_count' => $selectedJob->vacancy_count,
                'category' => $selectedJob->category,
                'pay_scale' => $selectedJob->pay_scale,
                'qualification' => $selectedJob->qualification,
                'fee_general' => $selectedJob->fee_general,
                'fee_sc_st' => $selectedJob->fee_sc_st,
                'fee_obc' => $selectedJob->fee_obc,
                'application_start_date' => $selectedJob->application_start_date,
                'application_end_date' => $selectedJob->application_end_date,
                'additional_info' => $selectedJob->additional_info,
                'created_at' => $selectedJob->created_at,
                'updated_at' => $selectedJob->updated_at,
            ] : null,
            'personal_details' => $personalDetails,
            'employment_details' => $employmentDetails,
            'qualification_details' => $qualificationDetails,
            'uploaded_documents' => $uploadedDocuments,
            'payment_details' => $paymentDetails
        ];
    }

    /**
     * Get redirect message based on next section
     */
    private function getRedirectMessage($nextSection)
    {
        $messages = [
            'personal_details' => 'Please fill your personal details to start the application',
            'qualification_details' => 'Personal details saved! Now add your educational qualifications',
            'employment_details' => 'Qualification details saved! Now add your employment history',
            'file_upload' => 'Employment details saved! Now upload required documents',
            'application_summary' => 'Documents uploaded! Review your application summary',
            'payment' => 'Application summary complete! Proceed to payment',
            'print_application' => 'Payment successful! Print your application',
            'completed' => 'Congratulations! Your application is complete and ready for review'
        ];

        return $messages[$nextSection] ?? 'Continue with your application';
    }

    /**
     * Get fee type display name based on category
     */
    private function getCategoryFeeType($category)
    {
        $feeTypes = [
            'SC' => 'SC/ST',
            'ST' => 'SC/ST', 
            'OBC' => 'OBC',
            'UR' => 'General',
            'General' => 'General'
        ];

        return $feeTypes[$category] ?? 'General';
    }

    /**
     * Get available jobs for selection (Stage 0)
     * This is the first step where user selects which job to apply for
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableJobsForApplication(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $request->user_id;

            // Get all active jobs
            $availableJobs = JobPosting::where('status', 'active')
                ->where('application_deadline', '>=', now())
                ->orderBy('created_at', 'desc')
                ->get();

            // Get user's application status for each job
            $userApplications = JobAppliedStatus::where('user_id', $userId)
                ->get()
                ->keyBy('job_id');

            // Format jobs with application status
            $formattedJobs = $availableJobs->map(function ($job) use ($userApplications) {
                $applicationStatus = $userApplications->get($job->id);
                
                return [
                    'job_id' => $job->id,
                    'title' => $job->title,
                    'description' => $job->description,
                    'location' => $job->location,
                    'salary_range' => $job->salary_range,
                    'employment_type' => $job->employment_type,
                    'application_deadline' => $job->application_deadline,
                    'created_at' => $job->created_at,
                    'application_status' => $applicationStatus ? [
                        'status' => $applicationStatus->status,
                        'stage' => $applicationStatus->stage,
                        'stage_name' => $applicationStatus->getCurrentStageName(),
                        'completion_percentage' => $this->calculateJobCompletionPercentage($applicationStatus->user_id, $job->id),
                        'can_continue' => !$applicationStatus->isCompleted(),
                        'last_updated' => $applicationStatus->updated_at
                    ] : null,
                    'can_apply' => !$applicationStatus || !$applicationStatus->isCompleted()
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Available jobs retrieved successfully',
                'user_id' => $userId,
                'data' => [
                    'available_jobs' => $formattedJobs,
                    'total_jobs' => $availableJobs->count(),
                    'applied_jobs_count' => $userApplications->count(),
                    'completed_applications' => $userApplications->where('stage', '>=', JobAppliedStatus::STAGES['completed'])->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting available jobs: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving available jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start application for a selected job (Stage 0 -> Stage 1)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startJobApplication(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $request->user_id;
            $jobId = $request->job_id;

            // Check if job exists and is available
            $job = JobPosting::where('id', $jobId)
                ->where('status', 'active')
                ->where('application_deadline', '>=', now())
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or application deadline has passed'
                ], 404);
            }

            // Get or create application status
            $applicationStatus = JobAppliedStatus::firstOrCreate(
                [
                    'user_id' => $userId,
                    'job_id' => $jobId
                ],
                [
                    'status' => JobAppliedStatus::STATUSES['draft'],
                    'stage' => JobAppliedStatus::STAGES['personal_details'], // Start with personal details
                    'inserted_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // If already exists, update to at least personal_details stage
            if ($applicationStatus->stage < JobAppliedStatus::STAGES['personal_details']) {
                $applicationStatus->updateStage(JobAppliedStatus::STAGES['personal_details']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application started successfully',
                'user_id' => $userId,
                'job_id' => $jobId,
                'job_details' => [
                    'title' => $job->title,
                    'description' => $job->description,
                    'location' => $job->location,
                    'application_deadline' => $job->application_deadline
                ],
                'application_status' => [
                    'id' => $applicationStatus->id,
                    'status' => $applicationStatus->status,
                    'current_stage' => $applicationStatus->stage,
                    'current_stage_name' => $applicationStatus->getCurrentStageName(),
                ],
                'redirect_to' => [
                    'section' => 'personal_details',
                    'action' => 'fill_section',
                    'message' => 'Application started! Please fill your personal details to continue'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error starting job application: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error starting job application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save selected job and update application status/stage
     * This API handles both:
     * 1. Initial job selection (creates new record with stage 0 -> 1)
     * 2. Status updates for existing applications (updates stage)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSelectedJob(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            $userId = $authenticatedUser->id;

            $validator = Validator::make($request->all(), [
                'job_id' => 'required|integer|exists:tura_job_postings,id',
                'stage' => 'nullable|integer|min:0|max:7', // Optional stage update
                'status' => 'nullable|string|in:draft,in_progress,submitted,under_review,approved,rejected', // Optional status update
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $jobId = $request->job_id;
            $newStage = $request->input('stage', null);
            $newStatus = $request->input('status', null);

            // Get job details from tura_job_postings table
            $job = TuraJobPosting::find($jobId);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            // Check if user has already selected/applied for this job
            $existingApplication = JobAppliedStatus::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();

            if ($existingApplication) {
                // If application exists, update stage/status
                $originalStage = $existingApplication->stage;
                $originalStatus = $existingApplication->status;

                // Update stage if provided and it's higher than current stage
                if ($newStage !== null && $newStage > $existingApplication->stage) {
                    $existingApplication->stage = $newStage;
                }

                // Update status if provided
                if ($newStatus !== null) {
                    $existingApplication->status = $newStatus;
                }

                // Always update timestamp
                $existingApplication->updated_at = now();
                $existingApplication->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Job application status updated successfully',
                    'data' => [
                        'id' => $existingApplication->id,
                        'job_id' => (int) $existingApplication->job_id,
                        'user_id' => (int) $existingApplication->user_id,
                        'status' => $existingApplication->status,
                        'stage' => (int) $existingApplication->stage,
                        'stage_name' => $existingApplication->getCurrentStageName(),
                        'application_id' => $existingApplication->application_id,
                        'job_applied_email_sent' => $existingApplication->job_applied_email_sent,
                        'payment_confirmation_email_sent' => $existingApplication->payment_confirmation_email_sent,
                        'inserted_at' => $existingApplication->inserted_at,
                        'updated_at' => $existingApplication->updated_at,
                        'changes_made' => [
                            'stage_updated' => $originalStage !== $existingApplication->stage,
                            'status_updated' => $originalStatus !== $existingApplication->status,
                            'previous_stage' => $originalStage,
                            'previous_status' => $originalStatus,
                        ],
                    ]
                ], 200);
            }

            // Check if job is available for new applications only
            if (!$job->isApplicationOpen()) {
                $now = now()->toDateString();
                return response()->json([
                    'success' => false,
                    'message' => 'This job is not available for new applications',
                    'debug_info' => [
                        'job_status' => $job->status,
                        'application_start_date' => $job->application_start_date,
                        'application_end_date' => $job->application_end_date,
                        'current_date' => $now,
                        'status_check' => $job->status === 'active' ? 'PASS' : 'FAIL',
                        'start_date_check' => $job->application_start_date ? ($now >= $job->application_start_date ? 'PASS' : 'FAIL') : 'N/A',
                        'end_date_check' => $job->application_end_date ? ($now <= $job->application_end_date ? 'PASS' : 'FAIL') : 'N/A',
                    ]
                ], 400);
            }

            // Create new job application record (initial job selection)
            // Start with stage 0 (job_selection) and status 'in_progress'
            $jobApplicationStatus = JobAppliedStatus::create([
                'user_id' => $userId,
                'job_id' => $jobId,
                'email' => $authenticatedUser->email, // Add required email field
                'status' => $newStatus ?? JobAppliedStatus::STATUSES['in_progress'],
                'stage' => $newStage ?? JobAppliedStatus::STAGES['job_selection'], // Start with stage 0
                'application_id' => null, // Will be generated after personal details
                'job_applied_email_sent' => false,
                'payment_confirmation_email_sent' => false,
                'inserted_at' => now(),
                'updated_at' => now(),
            ]);

            // Respond with the stored fields and a compact payload
            return response()->json([
                'success' => true,
                'message' => 'Job application created successfully',
                'data' => [
                    'id' => $jobApplicationStatus->id,
                    'job_id' => (int) $jobApplicationStatus->job_id,
                    'user_id' => (int) $jobApplicationStatus->user_id,
                    'status' => $jobApplicationStatus->status,
                    'stage' => (int) $jobApplicationStatus->stage,
                    'stage_name' => $jobApplicationStatus->getCurrentStageName(),
                    'application_id' => $jobApplicationStatus->application_id,
                    'job_applied_email_sent' => $jobApplicationStatus->job_applied_email_sent,
                    'payment_confirmation_email_sent' => $jobApplicationStatus->payment_confirmation_email_sent,
                    'inserted_at' => $jobApplicationStatus->inserted_at,
                    'updated_at' => $jobApplicationStatus->updated_at,
                    'is_new_application' => true,
                    'selected_job' => [
                        'id' => $job->id,
                        'job_title_department' => $job->job_title_department,
                        'vacancy_count' => $job->vacancy_count,
                        'category' => $job->category,
                        'pay_scale' => $job->pay_scale,
                        'qualification' => $job->qualification,
                        'fee_general' => $job->fee_general,
                        'fee_sc_st' => $job->fee_sc_st,
                        'fee_obc' => $job->fee_obc,
                        'application_start_date' => $job->application_start_date,
                        'application_end_date' => $job->application_end_date,
                    ],
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error saving selected job: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving selected job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate application ID and send job application email
     * This should be called after personal details are successfully saved
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateApplicationIdAndSendEmail(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            $userId = $authenticatedUser->id;

            $validator = Validator::make($request->all(), [
                'job_id' => 'required|integer|exists:tura_job_postings,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $jobId = $request->job_id;

            // Find the job application
            $jobApplication = JobAppliedStatus::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();

            if (!$jobApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job application not found. Please select a job first.'
                ], 404);
            }

            // Check if application ID already exists
            if ($jobApplication->application_id) {
                return response()->json([
                    'success' => true,
                    'message' => 'Application ID already exists',
                    'data' => [
                        'application_id' => $jobApplication->application_id,
                        'job_applied_email_sent' => $jobApplication->job_applied_email_sent,
                        'message' => 'Application ID was already generated for this application.'
                    ]
                ], 200);
            }

            // Get job details for application ID generation
            $job = TuraJobPosting::find($jobId);
            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            // Generate application ID
            $applicationId = $jobApplication->generateApplicationId($job->job_title_department);
            $jobApplication->application_id = $applicationId;
            $jobApplication->save();

            // Send job application email if not already sent
            if (!$jobApplication->isJobApplicationEmailSent()) {
                try {
                    $this->sendJobApplicationEmail($authenticatedUser, $job, $applicationId);
                    $jobApplication->markJobApplicationEmailSent();
                    $emailSent = true;
                    $emailMessage = 'Job application email sent successfully';
                } catch (\Exception $e) {
                    Log::error('Error sending job application email: ' . $e->getMessage());
                    $emailSent = false;
                    $emailMessage = 'Application ID generated but email sending failed: ' . $e->getMessage();
                }
            } else {
                $emailSent = false;
                $emailMessage = 'Email was already sent for this application';
            }

            return response()->json([
                'success' => true,
                'message' => 'Application ID generated successfully',
                'data' => [
                    'application_id' => $applicationId,
                    'job_applied_email_sent' => $jobApplication->job_applied_email_sent,
                    'email_sent_now' => $emailSent,
                    'email_message' => $emailMessage,
                    'job_details' => [
                        'id' => $job->id,
                        'job_title_department' => $job->job_title_department,
                        'category' => $job->category
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error generating application ID: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating application ID',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send job application confirmation email
     *
     * @param User $user
     * @param TuraJobPosting $job
     * @param string $applicationId
     * @return bool
     */
    private function sendJobApplicationEmail($user, $job, $applicationId)
    {
        // Initialize PHPMailer
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = 0; // Disable debug output
            $mail->isSMTP(); // Set mailer to use SMTP
            $mail->Host = config('mail.mailers.smtp.host'); // SMTP server
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = config('mail.mailers.smtp.username'); // SMTP username
            $mail->Password = config('mail.mailers.smtp.password'); // SMTP password
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption'); // Encryption type (tls/ssl)
            $mail->Port = config('mail.mailers.smtp.port'); // SMTP port

            // Sender and recipient
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($user->email); // Add recipient email
            
            // Subject
            $mail->Subject = 'Job Application Confirmation - Application ID: ' . $applicationId;

            // Email content
            $mail->isHTML(true); // Set email format to HTML
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // Add additional headers for better email client compatibility
            $mail->addCustomHeader('X-Mailer', 'Tura Municipal Board System');
            $mail->addCustomHeader('Content-Type', 'text/html; charset=UTF-8');
            
            // HTML body content - Creative Professional Template
            $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='utf-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Job Application Confirmation</title>
                </head>
                <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f7fa;'>
                    <div style='max-width: 650px; margin: 0 auto; background-color: #ffffff;'>
                        <!-- Header -->
                        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;'>
                            <h1 style='color: #ffffff; margin: 0; font-size: 32px; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.3);'>
                                🏛️ Tura Municipal Board
                            </h1>
                            <p style='color: #e8f2ff; margin: 10px 0 0 0; font-size: 16px; font-weight: 300;'>
                                Government of Meghalaya
                            </p>
                        </div>
                        
                        <!-- Main Content -->
                        <div style='padding: 40px 30px;'>
                            <!-- Greeting -->
                            <div style='margin-bottom: 30px;'>
                                <h2 style='color: #2d3748; margin: 0 0 15px 0; font-size: 24px;'>
                                    Dear {$user->firstname} {$user->lastname},
                                </h2>
                                <p style='color: #4a5568; font-size: 16px; line-height: 1.6; margin: 0;'>
                                    We are pleased to confirm that your job application has been successfully received and is now under review.
                                </p>
                            </div>
                            
                            <!-- Application Details Card -->
                            <div style='background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 15px; padding: 25px; margin: 30px 0; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);'>
                                <h3 style='color: #ffffff; margin: 0 0 20px 0; font-size: 20px; text-align: center;'>
                                    📋 Application Summary
                                </h3>
                                <div style='background: rgba(255,255,255,0.9); border-radius: 10px; padding: 20px;'>
                                    <table style='width: 100%; border-collapse: collapse;'>
                                        <tr>
                                            <td style='padding: 8px 0; font-weight: bold; color: #2d3748; width: 40%;'>Application ID:</td>
                                            <td style='padding: 8px 0; color: #4a5568; font-family: monospace; font-size: 16px; background: #f7fafc; padding: 5px 10px; border-radius: 5px;'>{$applicationId}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 8px 0; font-weight: bold; color: #2d3748;'>Position:</td>
                                            <td style='padding: 8px 0; color: #4a5568;'>{$job->job_title_department}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 8px 0; font-weight: bold; color: #2d3748;'>Category:</td>
                                            <td style='padding: 8px 0; color: #4a5568;'>{$job->category}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 8px 0; font-weight: bold; color: #2d3748;'>Pay Scale:</td>
                                            <td style='padding: 8px 0; color: #4a5568;'>{$job->pay_scale}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 8px 0; font-weight: bold; color: #2d3748;'>Application Date:</td>
                                            <td style='padding: 8px 0; color: #4a5568;'>" . now()->format('F j, Y \a\t g:i A') . "</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 8px 0; font-weight: bold; color: #2d3748;'>Status:</td>
                                            <td style='padding: 8px 0;'>
                                                <span style='background: #48bb78; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: bold;'>
                                                    ✓ Received
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Next Steps -->
                            <div style='background: #f7fafc; border-left: 4px solid #4299e1; padding: 20px; margin: 30px 0; border-radius: 0 8px 8px 0;'>
                                <h4 style='color: #2d3748; margin: 0 0 15px 0; font-size: 18px;'>
                                    📌 What's Next?
                                </h4>
                                <ol style='color: #4a5568; line-height: 1.8; margin: 0; padding-left: 20px;'>
                                    <li>Complete all required sections of your application</li>
                                    <li>Upload all mandatory documents</li>
                                    <li>Review your application summary carefully</li>
                                    <li>Process the application fee payment</li>
                                    <li>Print your final application form</li>
                                </ol>
                            </div>
                            
                            <!-- Application Deadline -->
                            " . ($job->application_end_date ? "
                            <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 30px 0;'>
                                <h4 style='color: #b45309; margin: 0 0 10px 0; font-size: 16px;'>
                                    ⏰ Important Deadline
                                </h4>
                                <p style='color: #92400e; margin: 0; font-size: 14px; line-height: 1.5;'>
                                    <strong>Application Deadline:</strong> " . date('F j, Y', strtotime($job->application_end_date)) . "
                                </p>
                            </div>
                            " : "") . "
                            
                            <!-- Important Notice -->
                            <div style='background: #fed7d7; border: 1px solid #feb2b2; border-radius: 8px; padding: 20px; margin: 30px 0;'>
                                <h4 style='color: #c53030; margin: 0 0 10px 0; font-size: 16px;'>
                                    ⚠️ Important Notice
                                </h4>
                                <p style='color: #742a2a; margin: 0; font-size: 14px; line-height: 1.5;'>
                                    Please quote your <strong>Application ID</strong> in all future correspondence. 
                                    Keep this information confidential and do not share with unauthorized persons.
                                </p>
                            </div>
                            
                            <!-- Contact Information -->
                            <div style='text-align: center; margin-top: 40px;'>
                                <p style='color: #718096; font-size: 14px; margin: 0 0 10px 0;'>
                                    Need assistance? Contact us at:
                                </p>
                                <p style='color: #4a5568; font-weight: bold; margin: 0;'>
                                    📧 " . config('mail.from.address') . "
                                </p>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div style='background: #2d3748; padding: 30px; text-align: center;'>
                            <p style='color: #a0aec0; margin: 0 0 10px 0; font-size: 16px; font-weight: 600;'>
                                Thank you for your interest in Tura Municipal Board
                            </p>
                            <p style='color: #718096; margin: 0; font-size: 13px;'>
                                This is an automated message. Please do not reply directly to this email.
                            </p>
                            <div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #4a5568;'>
                                <p style='color: #718096; margin: 0; font-size: 12px;'>
                                    © " . date('Y') . " Tura Municipal Board, Government of Meghalaya. All rights reserved.
                                </p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // Attempt to send the email
            if (!$mail->send()) {
                throw new \Exception('Email not sent. Error: ' . $mail->ErrorInfo);
            }

            Log::info('Job application email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'application_id' => $applicationId,
                'job_id' => $job->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Job application email sending failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send payment confirmation email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPaymentConfirmationEmail(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            $userId = $authenticatedUser->id;

            $validator = Validator::make($request->all(), [
                'job_id' => 'required|integer|exists:tura_job_postings,id',
                'payment_amount' => 'required|numeric|min:0',
                'payment_reference' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $jobId = $request->job_id;
            $paymentAmount = $request->payment_amount;
            $paymentReference = $request->payment_reference;

            // Find the job application
            $jobApplication = JobAppliedStatus::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();

            if (!$jobApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job application not found.'
                ], 404);
            }

            // Check if payment confirmation email already sent
            if ($jobApplication->isPaymentConfirmationEmailSent()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment confirmation email was already sent',
                    'data' => [
                        'application_id' => $jobApplication->application_id,
                        'payment_confirmation_email_sent' => true,
                        'message' => 'Email was already sent for this payment.'
                    ]
                ], 200);
            }

            // Get job details
            $job = TuraJobPosting::find($jobId);
            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            // Send payment confirmation email
            try {
                $this->sendPaymentConfirmationEmailContent($authenticatedUser, $job, $jobApplication->application_id, $paymentAmount, $paymentReference);
                $jobApplication->markPaymentConfirmationEmailSent();
                $emailSent = true;
                $emailMessage = 'Payment confirmation email sent successfully';
            } catch (\Exception $e) {
                Log::error('Error sending payment confirmation email: ' . $e->getMessage());
                $emailSent = false;
                $emailMessage = 'Email sending failed: ' . $e->getMessage();
            }

            return response()->json([
                'success' => $emailSent,
                'message' => $emailMessage,
                'data' => [
                    'application_id' => $jobApplication->application_id,
                    'payment_confirmation_email_sent' => $jobApplication->payment_confirmation_email_sent,
                    'email_sent_now' => $emailSent,
                    'payment_amount' => $paymentAmount,
                    'payment_reference' => $paymentReference
                ]
            ], $emailSent ? 200 : 500);

        } catch (\Exception $e) {
            Log::error('Error sending payment confirmation email: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error sending payment confirmation email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send payment confirmation email content
     *
     * @param User $user
     * @param TuraJobPosting $job
     * @param string $applicationId
     * @param float $paymentAmount
     * @param string $paymentReference
     * @return bool
     */
    private function sendPaymentConfirmationEmailContent($user, $job, $applicationId, $paymentAmount, $paymentReference = null)
    {
        // Initialize PHPMailer
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = 0; // Disable debug output
            $mail->isSMTP(); // Set mailer to use SMTP
            $mail->Host = config('mail.mailers.smtp.host'); // SMTP server
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = config('mail.mailers.smtp.username'); // SMTP username
            $mail->Password = config('mail.mailers.smtp.password'); // SMTP password
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption'); // Encryption type (tls/ssl)
            $mail->Port = config('mail.mailers.smtp.port'); // SMTP port

            // Sender and recipient
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($user->email); // Add recipient email
            
            // Subject
            $mail->Subject = 'Payment Confirmation - Application ID: ' . $applicationId;

            // Email content
            $mail->isHTML(true); // Set email format to HTML
            
            // HTML body content - Creative Payment Confirmation Template
            $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='utf-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Payment Confirmation</title>
                </head>
                <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f0f8ff;'>
                    <div style='max-width: 650px; margin: 0 auto; background-color: #ffffff;'>
                        <!-- Header -->
                        <div style='background: linear-gradient(135deg, #4ade80 0%, #22c55e 50%, #16a34a 100%); padding: 40px 30px; text-align: center; position: relative;'>
                            <div style='position: absolute; top: 20px; right: 30px; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px;'>
                                <span style='color: #ffffff; font-size: 14px; font-weight: bold;'>✅ PAID</span>
                            </div>
                            <h1 style='color: #ffffff; margin: 0; font-size: 32px; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.2);'>
                                🏛️ Tura Municipal Board
                            </h1>
                            <p style='color: #dcfce7; margin: 10px 0 0 0; font-size: 16px; font-weight: 300;'>
                                Government of Meghalaya
                            </p>
                            <div style='margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 10px; backdrop-filter: blur(10px);'>
                                <h2 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;'>
                                    💳 Payment Confirmation
                                </h2>
                            </div>
                        </div>
                        
                        <!-- Success Message -->
                        <div style='padding: 30px 30px 20px 30px;'>
                            <div style='text-align: center; margin-bottom: 30px;'>
                                <div style='display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 20px; border-radius: 50%; margin-bottom: 20px; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);'>
                                    <span style='color: #ffffff; font-size: 32px;'>✓</span>
                                </div>
                                <h2 style='color: #065f46; margin: 0 0 10px 0; font-size: 28px; font-weight: bold;'>
                                    Payment Successful!
                                </h2>
                                <p style='color: #047857; font-size: 18px; margin: 0; font-weight: 600;'>
                                    ₹{$paymentAmount} received successfully
                                </p>
                            </div>
                            
                            <!-- Greeting -->
                            <div style='margin-bottom: 30px;'>
                                <h3 style='color: #1f2937; margin: 0 0 15px 0; font-size: 22px;'>
                                    Dear {$user->firstname} {$user->lastname},
                                </h3>
                                <p style='color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0;'>
                                    Your payment has been processed successfully. Your application is now complete and ready for review.
                                </p>
                            </div>
                            
                            <!-- Payment Receipt Card -->
                            <div style='background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); border-radius: 15px; padding: 25px; margin: 30px 0; box-shadow: 0 8px 25px rgba(167, 139, 250, 0.3);'>
                                <h3 style='color: #ffffff; margin: 0 0 20px 0; font-size: 20px; text-align: center;'>
                                    🧾 Payment Receipt
                                </h3>
                                <div style='background: rgba(255,255,255,0.95); border-radius: 10px; padding: 20px;'>
                                    <table style='width: 100%; border-collapse: collapse;'>
                                        <tr>
                                            <td style='padding: 10px 0; font-weight: bold; color: #1f2937; width: 40%; border-bottom: 1px solid #e5e7eb;'>Application ID:</td>
                                            <td style='padding: 10px 0; color: #374151; font-family: monospace; font-size: 16px; border-bottom: 1px solid #e5e7eb;'>{$applicationId}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 0; font-weight: bold; color: #1f2937; border-bottom: 1px solid #e5e7eb;'>Position Applied:</td>
                                            <td style='padding: 10px 0; color: #374151; border-bottom: 1px solid #e5e7eb;'>{$job->job_title_department}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 0; font-weight: bold; color: #1f2937; border-bottom: 1px solid #e5e7eb;'>Category:</td>
                                            <td style='padding: 10px 0; color: #374151; border-bottom: 1px solid #e5e7eb;'>{$job->category}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 0; font-weight: bold; color: #1f2937; border-bottom: 1px solid #e5e7eb;'>Payment Amount:</td>
                                            <td style='padding: 10px 0; color: #065f46; font-weight: bold; font-size: 18px; border-bottom: 1px solid #e5e7eb;'>₹{$paymentAmount}</td>
                                        </tr>
                                        " . ($paymentReference ? "
                                        <tr>
                                            <td style='padding: 10px 0; font-weight: bold; color: #1f2937; border-bottom: 1px solid #e5e7eb;'>Payment Reference:</td>
                                            <td style='padding: 10px 0; color: #374151; font-family: monospace; border-bottom: 1px solid #e5e7eb;'>{$paymentReference}</td>
                                        </tr>
                                        " : "") . "
                                        <tr>
                                            <td style='padding: 10px 0; font-weight: bold; color: #1f2937;'>Payment Date:</td>
                                            <td style='padding: 10px 0; color: #374151;'>" . now()->format('F j, Y \a\t g:i A') . "</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- What's Next Section -->
                            <div style='background: #ecfdf5; border-left: 4px solid #10b981; padding: 20px; margin: 30px 0; border-radius: 0 8px 8px 0;'>
                                <h4 style='color: #065f46; margin: 0 0 15px 0; font-size: 18px;'>
                                    🎯 What Happens Next?
                                </h4>
                                <ul style='color: #047857; line-height: 1.8; margin: 0; padding-left: 20px;'>
                                    <li><strong>Application Review:</strong> Your complete application is now under review</li>
                                    <li><strong>Print Form:</strong> You can now download and print your final application form</li>
                                    <li><strong>Status Updates:</strong> Check your email regularly for application status updates</li>
                                    <li><strong>Selection Process:</strong> Wait for further communication regarding the selection process</li>
                                </ul>
                            </div>
                            
                            <!-- Important Information -->
                            <div style='background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 20px; margin: 30px 0;'>
                                <h4 style='color: #92400e; margin: 0 0 15px 0; font-size: 16px;'>
                                    📋 Important Information
                                </h4>
                                <ul style='color: #78350f; line-height: 1.6; margin: 0; padding-left: 20px; font-size: 14px;'>
                                    <li><strong>Keep this email</strong> as proof of payment for your records</li>
                                    <li><strong>Payment is non-refundable</strong> as per terms and conditions</li>
                                    <li><strong>Quote your Application ID</strong> in all future correspondence</li>
                                    <li><strong>Contact support</strong> if you have any questions about your application</li>
                                </ul>
                            </div>
                            
                            <!-- Contact Section -->
                            <div style='text-align: center; margin-top: 40px;'>
                                <div style='background: #f3f4f6; padding: 20px; border-radius: 10px;'>
                                    <p style='color: #6b7280; font-size: 14px; margin: 0 0 10px 0;'>
                                        Questions about your application?
                                    </p>
                                    <p style='color: #374151; font-weight: bold; margin: 0; font-size: 16px;'>
                                        📧 " . config('mail.from.address') . "
                                    </p>
                                    <p style='color: #6b7280; font-size: 12px; margin: 10px 0 0 0;'>
                                        Please include your Application ID when contacting us
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div style='background: #1f2937; padding: 30px; text-align: center;'>
                            <p style='color: #9ca3af; margin: 0 0 10px 0; font-size: 16px; font-weight: 600;'>
                                🙏 Thank you for choosing Tura Municipal Board
                            </p>
                            <p style='color: #6b7280; margin: 0; font-size: 13px;'>
                                This is an automated payment confirmation. Please do not reply to this email.
                            </p>
                            <div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #374151;'>
                                <p style='color: #6b7280; margin: 0; font-size: 12px;'>
                                    © " . date('Y') . " Tura Municipal Board, Government of Meghalaya. All rights reserved.
                                </p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // Attempt to send the email
            if (!$mail->send()) {
                throw new \Exception('Email not sent. Error: ' . $mail->ErrorInfo);
            }

            Log::info('Payment confirmation email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'application_id' => $applicationId,
                'job_id' => $job->id,
                'payment_amount' => $paymentAmount
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Payment confirmation email sending failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all jobs selected by a user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSelectedJobs(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            $userId = $authenticatedUser->id;

            // Get all job applications for this user
            $selectedJobs = JobAppliedStatus::where('user_id', $userId)
                ->with('job') // Assuming you have a relationship defined
                ->orderBy('inserted_at', 'desc')
                ->get();

            if ($selectedJobs->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No jobs selected yet',
                    'data' => [],
                    'total_count' => 0
                ], 200);
            }

            // Format the response with job details and application status
            $formattedJobs = $selectedJobs->map(function ($application) {
                // Get job details from tura_job_postings
                $job = TuraJobPosting::find($application->job_id);
                
                return [
                    'application_id' => $application->id,
                    'job_id' => $application->job_id,
                    'application_status' => [
                        'status' => $application->status,
                        'current_stage' => $application->stage,
                        'current_stage_name' => $application->getCurrentStageName(),
                        'is_completed' => $application->isCompleted(),
                        'created_at' => $application->inserted_at,
                        'updated_at' => $application->updated_at,
                    ],
                    'job_details' => $job ? [
                        'id' => $job->id,
                        'job_title_department' => $job->job_title_department,
                        'vacancy_count' => $job->vacancy_count,
                        'category' => $job->category,
                        'pay_scale' => $job->pay_scale,
                        'qualification' => $job->qualification,
                        'fee_general' => $job->fee_general,
                        'fee_sc_st' => $job->fee_sc_st,
                        'fee_obc' => $job->fee_obc,
                        'status' => $job->status,
                        'application_start_date' => $job->application_start_date,
                        'application_end_date' => $job->application_end_date,
                        'is_application_open' => $job->isApplicationOpen(),
                    ] : null,
                    'completion_percentage' => $this->calculateJobCompletionPercentage($application->user_id, $application->job_id),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Selected jobs retrieved successfully',
                'user_id' => $userId,
                'data' => $formattedJobs,
                'total_count' => $selectedJobs->count(),
                'summary' => [
                    'total_selected' => $selectedJobs->count(),
                    'completed_applications' => $selectedJobs->where('status', JobAppliedStatus::STATUSES['completed'])->count(),
                    'draft_applications' => $selectedJobs->where('status', JobAppliedStatus::STATUSES['draft'])->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving selected jobs: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving selected jobs'
            ], 500);
        }
    }

    /**
     * Get all available jobs for selection from tura_job_postings table
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableJobs(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            $userId = $authenticatedUser->id;

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:active,inactive,draft',
                'category' => 'nullable|string|in:UR,OBC,SC,ST,EWS',
                'application_open_only' => 'nullable|boolean',
                'search' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $query = TuraJobPosting::query();

            // Apply filters
            if ($request->status) {
                $query->where('status', $request->status);
            } else {
                // Default to active jobs only
                $query->where('status', 'active');
            }

            if ($request->category) {
                $query->where('category', $request->category);
            }

            if ($request->application_open_only) {
                $query->applicationOpen();
            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('job_title_department', 'LIKE', '%' . $request->search . '%')
                      ->orWhere('qualification', 'LIKE', '%' . $request->search . '%')
                      ->orWhere('pay_scale', 'LIKE', '%' . $request->search . '%');
                });
            }

            $jobs = $query->orderBy('created_at', 'desc')->get();

            // Get user's selected/applied jobs to show application status
            $userApplications = JobAppliedStatus::where('user_id', $userId)
                ->get()
                ->keyBy('job_id');

            // Format jobs with application status
            $formattedJobs = $jobs->map(function ($job) use ($userApplications) {
                $application = $userApplications->get($job->id);
                
                return [
                    'id' => $job->id,
                    'job_title_department' => $job->job_title_department,
                    'vacancy_count' => $job->vacancy_count,
                    'category' => $job->category,
                    'category_name' => TuraJobPosting::CATEGORIES[$job->category] ?? $job->category,
                    'pay_scale' => $job->pay_scale,
                    'qualification' => $job->qualification,
                    'fee_general' => $job->fee_general,
                    'fee_sc_st' => $job->fee_sc_st,
                    'fee_obc' => $job->fee_obc,
                    'status' => $job->status,
                    'application_start_date' => $job->application_start_date,
                    'application_end_date' => $job->application_end_date,
                    'additional_info' => $job->additional_info,
                    'is_application_open' => $job->isApplicationOpen(),
                    'user_application_status' => $application ? [
                        'applied' => true,
                        'application_id' => $application->id,
                        'status' => $application->status,
                        'stage' => $application->stage,
                        'stage_name' => $application->getCurrentStageName(),
                        'is_completed' => $application->isCompleted(),
                        'applied_at' => $application->inserted_at,
                    ] : [
                        'applied' => false,
                        'can_apply' => $job->isApplicationOpen(),
                    ],
                    'created_at' => $job->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Available jobs retrieved successfully',
                'user_id' => $userId,
                'data' => $formattedJobs,
                'total_count' => $jobs->count(),
                'filters_applied' => [
                    'status' => $request->status ?? 'active',
                    'category' => $request->category,
                    'application_open_only' => $request->application_open_only,
                    'search' => $request->search,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving available jobs: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving available jobs'
            ], 500);
        }
    }

    /**
     * Calculate completion percentage for a specific job application
     */
    private function calculateJobCompletionPercentage($userId, $jobId)
    {
        $completedSections = $this->checkCompletedSections($userId, $jobId);
        return $this->calculateCompletionPercentage($completedSections);
    }

    /**
     * Get application summary for review (Stage 5)
     * This shows complete application data for final review before payment
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
                'job_id' => 'required|integer|exists:tura_job_postings,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $jobId = $request->job_id;

            // Check if application exists
            $applicationStatus = JobAppliedStatus::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();

            if (!$applicationStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            // Check if previous stages are completed
            $completedSections = $this->checkCompletedSections($userId, $jobId);
            $allPreviousCompleted = $completedSections['personal_details'] && 
                                  $completedSections['employment_details'] && 
                                  $completedSections['qualification_details'] && 
                                  $completedSections['document_upload'];

            if (!$allPreviousCompleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete all previous sections before accessing application summary',
                    'missing_sections' => array_keys(array_filter($completedSections, function($completed) {
                        return !$completed;
                    }))
                ], 400);
            }

            // Get complete application data
            $existingData = $this->getExistingApplicationData($userId, $jobId);

            // Calculate payment details
            $paymentDetails = $this->calculateApplicationFee($existingData['personal_details'], $existingData['selected_job']);

            // Update stage to application_summary if not already
            if ($applicationStatus->stage < JobAppliedStatus::STAGES['application_summary']) {
                $applicationStatus->updateStage(JobAppliedStatus::STAGES['application_summary']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application summary retrieved successfully',
                'user_id' => $userId,
                'job_id' => $jobId,
                'application_status' => [
                    'id' => $applicationStatus->id,
                    'status' => $applicationStatus->status,
                    'current_stage' => $applicationStatus->stage,
                    'current_stage_name' => $applicationStatus->getCurrentStageName(),
                    'is_completed' => $applicationStatus->isCompleted(),
                ],
                'application_summary' => [
                    'completion_percentage' => 85, // 85% complete after summary
                    'ready_for_payment' => true,
                    'all_sections_completed' => $allPreviousCompleted
                ],
                'payment_details' => $paymentDetails,
                'complete_application_data' => $existingData,
                'next_action' => [
                    'section' => 'payment',
                    'action' => 'proceed_to_payment',
                    'message' => 'Review your application and proceed to payment'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting application summary: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving application summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate application fee based on category and pay scale
     */
    private function calculateApplicationFee($personalDetails, $selectedJob)
    {
        if (!$personalDetails || !$selectedJob) {
            return [
                'applicable_fee' => 0,
                'category' => 'unknown',
                'fee_type' => 'unknown',
                'calculation_details' => 'Missing personal details or job information'
            ];
        }

        // Handle both object and array formats
        $category = '';
        $payScale = '';
        $feeGeneral = 0;
        $feeScSt = 0;
        $feeObc = 0;

        // Extract personal details (handle both object and array)
        if (is_object($personalDetails)) {
            $category = strtolower($personalDetails->category ?? 'general');
        } elseif (is_array($personalDetails)) {
            $category = strtolower($personalDetails['category'] ?? 'general');
        }

        // Extract job details (handle both object and array)
        if (is_object($selectedJob)) {
            $payScale = $selectedJob->pay_scale ?? '';
            $feeGeneral = (float) ($selectedJob->fee_general ?? 0);
            $feeScSt = (float) ($selectedJob->fee_sc_st ?? 0);
            $feeObc = (float) ($selectedJob->fee_obc ?? $feeGeneral);
        } elseif (is_array($selectedJob)) {
            $payScale = $selectedJob['pay_scale'] ?? '';
            $feeGeneral = (float) ($selectedJob['fee_general'] ?? 0);
            $feeScSt = (float) ($selectedJob['fee_sc_st'] ?? 0);
            $feeObc = (float) ($selectedJob['fee_obc'] ?? $feeGeneral);
        }

        // Determine fee based on category
        $applicableFee = 0;
        $feeType = '';

        switch ($category) {
            case 'sc':
            case 'st':
            case 'sc/st':
                $applicableFee = $feeScSt;
                $feeType = 'SC/ST';
                break;
            case 'obc':
                $applicableFee = $feeObc;
                $feeType = 'OBC';
                break;
            case 'general':
            case 'ur':
            default:
                $applicableFee = $feeGeneral;
                $feeType = 'General';
                break;
        }

        return [
            'applicable_fee' => $applicableFee,
            'category' => $category,
            'fee_type' => $feeType,
            'pay_scale' => $payScale,
            'fee_breakdown' => [
                'general_fee' => $feeGeneral,
                'sc_st_fee' => $feeScSt,
                'obc_fee' => $feeObc
            ],
            'calculation_details' => "Fee calculated based on category: {$feeType}, Pay Scale: {$payScale}"
        ];
    }

    /**
     * Process payment for job application (Stage 6)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processApplicationPayment(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            $userId = $authenticatedUser->id;

            $validator = Validator::make($request->all(), [
                'job_id' => 'required|integer|exists:tura_job_postings,id',
                'payment_method' => 'required|string|in:online,offline,bank_transfer',
                'transaction_id' => 'nullable|string',
                'payment_amount' => 'required|numeric|min:0',
                'payment_reference' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $jobId = $request->job_id;

            // Check if application exists and is in correct stage
            $applicationStatus = JobAppliedStatus::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();

            if (!$applicationStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            if ($applicationStatus->stage < JobAppliedStatus::STAGES['application_summary']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete application summary before payment'
                ], 400);
            }

            // Get payment calculation
            $existingData = $this->getExistingApplicationData($userId, $jobId);
            $paymentDetails = $this->calculateApplicationFee($existingData['personal_details'], $existingData['selected_job']);

            // Verify payment amount
            if (abs((float)$request->payment_amount - $paymentDetails['applicable_fee']) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount does not match calculated fee',
                    'expected_amount' => $paymentDetails['applicable_fee'],
                    'provided_amount' => (float)$request->payment_amount
                ], 400);
            }

            // Create payment record (you might want to create a separate Payment model)
            $paymentData = [
                'user_id' => $userId,
                'job_id' => $jobId,
                'payment_method' => $request->payment_method,
                'payment_amount' => $request->payment_amount,
                'transaction_id' => $request->transaction_id,
                'payment_reference' => $request->payment_reference,
                'payment_status' => 'completed',
                'payment_date' => now(),
                'fee_type' => $paymentDetails['fee_type'],
                'category' => $paymentDetails['category']
            ];

            // Update application stage to payment
            $applicationStatus->updateStage(JobAppliedStatus::STAGES['payment']);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'user_id' => $userId,
                'job_id' => $jobId,
                'payment_details' => $paymentData,
                'application_status' => [
                    'id' => $applicationStatus->id,
                    'status' => $applicationStatus->status,
                    'current_stage' => $applicationStatus->stage,
                    'current_stage_name' => $applicationStatus->getCurrentStageName(),
                    'is_completed' => $applicationStatus->isCompleted(),
                ],
                'next_action' => [
                    'section' => 'print_application',
                    'action' => 'print_final_application',
                    'message' => 'Payment successful! You can now print your application'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate final application for printing (Stage 7)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generatePrintableApplication(Request $request)
    {
        try {
            // Get authenticated user from JWT middleware
            $authenticatedUser = $request->input('authenticated_user');
            $userId = $authenticatedUser->id;

            $validator = Validator::make($request->all(), [
                'job_id' => 'required|integer|exists:tura_job_postings,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $jobId = $request->job_id;

            // Check if application exists and payment is completed
            $applicationStatus = JobAppliedStatus::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();

            if (!$applicationStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            if ($applicationStatus->stage < JobAppliedStatus::STAGES['payment']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment must be completed before generating printable application'
                ], 400);
            }

            // Get complete application data
            $existingData = $this->getExistingApplicationData($userId, $jobId);
            $paymentDetails = $this->calculateApplicationFee($existingData['personal_details'], $existingData['selected_job']);

            // Generate application number
            $applicationNumber = 'Tura-' . $jobId . '-' . $userId . '-' . date('Y');

            // Update stage to print_application (final stage)
            if ($applicationStatus->stage < JobAppliedStatus::STAGES['print_application']) {
                $applicationStatus->updateStage(JobAppliedStatus::STAGES['print_application']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Printable application generated successfully',
                'user_id' => $userId,
                'job_id' => $jobId,
                'application_number' => $applicationNumber,
                'application_status' => [
                    'id' => $applicationStatus->id,
                    'status' => $applicationStatus->status,
                    'current_stage' => $applicationStatus->stage,
                    'current_stage_name' => $applicationStatus->getCurrentStageName(),
                    'is_completed' => $applicationStatus->isCompleted(),
                ],
                'printable_data' => [
                    'application_number' => $applicationNumber,
                    'submission_date' => now()->format('Y-m-d H:i:s'),
                    'job_details' => $existingData['selected_job'],
                    'personal_details' => $existingData['personal_details'],
                    'employment_details' => $existingData['employment_details'],
                    'qualification_details' => $existingData['qualification_details'],
                    'documents' => $existingData['uploaded_documents'],
                    'payment_details' => $paymentDetails,
                    'completion_percentage' => 100
                ],
                'final_status' => [
                    'application_completed' => true,
                    'ready_for_submission' => true,
                    'message' => 'Congratulations! Your application has been successfully completed and is ready for submission.'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error generating printable application: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating printable application',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}