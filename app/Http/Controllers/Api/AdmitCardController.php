<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TuraAdmitCard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class AdmitCardController extends Controller
{
    /**
     * Simple health check endpoint with CORS headers
     */
    public function healthCheck()
    {
        try {
            $dbCheck = \DB::connection()->getPdo() ? 'connected' : 'failed';
            
            $response = response()->json([
                'status' => true,
                'message' => 'Admit Card Controller is working',
                'database' => $dbCheck,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'timestamp' => now()->toDateTimeString(),
                'cors_headers' => 'enabled'
            ]);

            // Add explicit CORS headers
            return $response->header('Access-Control-Allow-Origin', '*')
                          ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
                          
        } catch (\Exception $e) {
            $errorResponse = response()->json([
                'status' => false,
                'message' => 'Health check failed',
                'error' => $e->getMessage()
            ], 500);

            // Add CORS headers to error response too
            return $errorResponse->header('Access-Control-Allow-Origin', '*')
                                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }
    }

    /**
     * NEW: Get consolidated exam schedule by email
     * Shows 1 General Awareness + N Core Papers with respective roll numbers
     */
    public function getExamSchedule(Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email format',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get all active admit cards for this user
            $admitCards = TuraAdmitCard::where('email', $request->email)
                                      ->where('status', 'active')
                                      ->orderBy('job_id')
                                      ->get();

            if ($admitCards->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No admit cards found for this email'
                ], 404);
            }

            // Build consolidated exam schedule
            $consolidatedSchedule = $this->buildConsolidatedSchedule($admitCards);
            $primaryCard = $admitCards->first();

            return response()->json([
                'status' => true,
                'message' => $consolidatedSchedule['has_conflicts'] 
                    ? 'Exam schedule retrieved with time conflicts detected' 
                    : 'Exam schedule retrieved successfully',
                'candidate_info' => [
                    'full_name' => $primaryCard->full_name,
                    'email' => $request->email,
                    'date_of_birth' => $primaryCard->date_of_birth ? $primaryCard->date_of_birth->format('d-m-Y') : '',
                    'gender' => $primaryCard->gender,
                    'category' => $primaryCard->category,
                    'total_jobs_applied' => $admitCards->count(),
                    'total_exams' => count($consolidatedSchedule['papers'])
                ],
                'exam_schedule' => $consolidatedSchedule,
                'warnings' => $consolidatedSchedule['has_conflicts'] ? [
                    'time_conflicts' => true,
                    'message' => 'Some exams have overlapping times. Please contact the examination authority.',
                    'conflicts_count' => count($consolidatedSchedule['time_conflicts'])
                ] : [],
                'individual_admit_cards' => $admitCards->map(function($card) {
                    return [
                        'job_title' => $card->job_title,
                        'application_id' => $card->application_id,
                        'admit_no' => $card->admit_no,
                        'roll_number' => $card->roll_number,
                        'individual_download_url' => url('/api/admit-card/download/' . $card->admit_no)
                    ];
                }),
                'consolidated_download_url' => $consolidatedSchedule['has_conflicts'] 
                    ? null 
                    : url('/api/admit-card/download-consolidated/' . urlencode($request->email))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error retrieving exam schedule', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve exam schedule. Please try again later.'
            ], 500);
        }
    }

    /**
     * NEW: Download consolidated admit card showing all exams with respective roll numbers
     */
    public function downloadConsolidated($encoded_email)
    {
        try {
            // Decode the URL-encoded email
            $email = urldecode($encoded_email);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::error('Invalid email parameter in downloadConsolidated', [
                    'encoded_email' => $encoded_email,
                    'decoded_email' => $email
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email parameter'
                ], 400);
            }

            // Get all active admit cards for this user
            $admitCards = TuraAdmitCard::where('email', $email)
                                      ->where('status', 'active')
                                      ->orderBy('job_id')
                                      ->get();

            if ($admitCards->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No admit cards found for this email'
                ], 404);
            }

            // Use primary admit card for basic info
            $primaryCard = $admitCards->first();

            // Update download timestamp for all cards
            $admitCards->each(function($card) {
                $card->update(['pdf_downloaded_at' => Carbon::now()]);
            });

            // Build consolidated schedule
            $consolidatedSchedule = $this->buildConsolidatedSchedule($admitCards);

            // Check for time conflicts
            if ($consolidatedSchedule['has_conflicts']) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot generate consolidated admit card due to exam time conflicts',
                    'conflicts' => $consolidatedSchedule['time_conflicts'],
                    'suggestion' => 'Please download individual admit cards or contact the examination authority to resolve conflicts.'
                ], 409); // 409 Conflict
            }

            // Prepare consolidated data for PDF
            $data = [
                'full_name' => $primaryCard->full_name,
                'email' => $email,
                'date_of_birth' => $primaryCard->date_of_birth ? $primaryCard->date_of_birth->format('d-m-Y') : '',
                'gender' => $primaryCard->gender,
                'category' => $primaryCard->category,
                'venue_name' => $primaryCard->venue_name,
                'venue_address' => $primaryCard->venue_address,
                'photo_base64' => $this->processBase64Image($primaryCard->photo_base64),
                'logo_path' => $this->getLogoPath(),
                
                // Required template variables
                'roll_number' => $primaryCard->roll_number,
                'application_id' => $primaryCard->application_id,
                'job_title' => 'Multiple Job Applications',
                
                // Consolidated exam data
                'is_consolidated' => true,
                'consolidated_schedule' => $consolidatedSchedule,
                'total_jobs_applied' => $admitCards->count(),
                
                // Primary identifiers for header display
                'primary_admit_no' => $primaryCard->admit_no,
                'primary_roll_number' => $primaryCard->roll_number,
                'primary_application_id' => $primaryCard->application_id,
            ];

            // Generate consolidated PDF with basic font settings
            $cleanEmail = str_replace(['@', '.'], ['_at_', '_'], $email);
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "{$cleanEmail}_admitcard_consolidated_{$timestamp}.pdf";
            
            $pdf = PDF::loadView('pdf.admit_card', $data);
            $pdf->setPaper('A4', 'portrait');
            
            // Set basic options to avoid font issues
            $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
            $pdf->getDomPDF()->set_option('isPhpEnabled', false);
            $pdf->getDomPDF()->set_option('defaultFont', 'DejaVu Sans');
            
            // Create response with proper download headers
            $response = response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->header('Access-Control-Expose-Headers', 'Content-Disposition');
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Error generating consolidated admit card', [
                'encoded_email' => $encoded_email ?? 'not_set',
                'decoded_email' => $email ?? 'not_decoded',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Temporary: Return detailed error for debugging
            return response()->json([
                'status' => false,
                'message' => 'Unable to generate consolidated admit card. Please try again or contact support.',
                'debug_error' => $e->getMessage(),
                'debug_file' => $e->getFile(),
                'debug_line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Build consolidated exam schedule (1 General + N Core papers)
     * Each paper maintains its specific roll number and admit card number
     */
    private function buildConsolidatedSchedule($admitCards)
    {
        $papers = [];
        $generalPaperAdded = false;

        foreach ($admitCards as $admitCard) {
            // Add General Awareness ONLY ONCE (from first admit card)
            // But collect all roll numbers for this common paper
            if (!$generalPaperAdded && $admitCard->hasSlot1()) {
                $allRollNumbers = $admitCards->pluck('roll_number')->toArray();
                
                $papers[] = [
                    'paper_type' => 'General Awareness',
                    'paper_number' => 1,
                    'subject' => $admitCard->subject_slot_1,
                    'exam_date' => $admitCard->exam_date_slot_1,
                    'exam_time' => date('h:i A', strtotime($admitCard->exam_start_time_slot_1)) . ' - ' . date('h:i A', strtotime($admitCard->exam_end_time_slot_1)),
                    'reporting_time' => $admitCard->reporting_time_slot_1,
                    'venue_name' => $admitCard->venue_name,
                    'venue_address' => $admitCard->venue_address,
                    'applicable_for' => 'All Job Applications',
                    'roll_numbers' => $allRollNumbers, // All roll numbers for common paper (API)
                    'roll_number' => implode(', ', $allRollNumbers), // Formatted for PDF template
                    'application_id' => 'COMMON', // Common for all applications
                    // Add time conflict detection fields
                    'exam_start_time' => $admitCard->exam_start_time_slot_1,
                    'exam_end_time' => $admitCard->exam_end_time_slot_1,
                    'is_common_paper' => true
                ];
                $generalPaperAdded = true;
            }

            // Add Core paper for EACH JOB with its specific roll number
            if ($admitCard->hasSlot2()) {
                $papers[] = [
                    'paper_type' => 'Core Paper',
                    'paper_number' => 2,
                    'subject' => $admitCard->subject_slot_2,
                    'exam_date' => $admitCard->exam_date_slot_2,
                    'exam_time' => date('h:i A', strtotime($admitCard->exam_start_time_slot_2)) . ' - ' . date('h:i A', strtotime($admitCard->exam_end_time_slot_2)),
                    'reporting_time' => $admitCard->reporting_time_slot_2,
                    'venue_name' => $admitCard->venue_name,
                    'venue_address' => $admitCard->venue_address,
                    'job_title' => $admitCard->job_title,
                    'job_id' => $admitCard->job_id,
                    'roll_number' => $admitCard->roll_number, // Job-specific roll number
                    'application_id' => $admitCard->application_id,
                    // Add time conflict detection fields
                    'exam_start_time' => $admitCard->exam_start_time_slot_2,
                    'exam_end_time' => $admitCard->exam_end_time_slot_2,
                    'is_common_paper' => false
                ];
            }
        }

        // Detect time conflicts (excluding common papers from conflict with themselves)
        $timeConflicts = $this->detectTimeConflicts($papers);

        return [
            'total_papers' => count($papers),
            'general_papers' => 1,
            'core_papers' => count($papers) - 1,
            'papers' => $papers,
            'time_conflicts' => $timeConflicts,
            'has_conflicts' => !empty($timeConflicts)
        ];
    }

    /**
     * Detect time conflicts between exam papers
     * Excludes conflicts between common papers (Paper 1) and themselves
     */
    private function detectTimeConflicts($papers)
    {
        $conflicts = [];
        
        for ($i = 0; $i < count($papers); $i++) {
            for ($j = $i + 1; $j < count($papers); $j++) {
                $paper1 = $papers[$i];
                $paper2 = $papers[$j];
                
                // Skip if both are common papers (Paper 1 - General Awareness)
                if (isset($paper1['is_common_paper']) && isset($paper2['is_common_paper']) && 
                    $paper1['is_common_paper'] && $paper2['is_common_paper']) {
                    continue; // Common papers don't conflict with each other
                }
                
                // Skip if different dates
                if ($paper1['exam_date'] !== $paper2['exam_date']) {
                    continue;
                }
                
                // Check for time overlap
                if ($this->timesOverlap(
                    $paper1['exam_start_time'], 
                    $paper1['exam_end_time'],
                    $paper2['exam_start_time'], 
                    $paper2['exam_end_time']
                )) {
                    $conflicts[] = [
                        'conflict_type' => 'time_overlap',
                        'exam_date' => $paper1['exam_date'],
                        'paper1' => [
                            'type' => $paper1['paper_type'],
                            'subject' => $paper1['subject'],
                            'time' => $paper1['exam_time'],
                            'roll_number' => $paper1['roll_number'] ?? 'Common Paper',
                            'job' => $paper1['job_title'] ?? $paper1['applicable_for'] ?? 'General',
                            'is_common' => $paper1['is_common_paper'] ?? false
                        ],
                        'paper2' => [
                            'type' => $paper2['paper_type'],
                            'subject' => $paper2['subject'],
                            'time' => $paper2['exam_time'],
                            'roll_number' => $paper2['roll_number'] ?? 'Common Paper',
                            'job' => $paper2['job_title'] ?? $paper2['applicable_for'] ?? 'General',
                            'is_common' => $paper2['is_common_paper'] ?? false
                        ],
                        'severity' => 'critical',
                        'message' => 'Exam times overlap - candidate cannot appear for both exams'
                    ];
                }
            }
        }
        
        return $conflicts;
    }

    /**
     * Check if two time ranges overlap
     */
    private function timesOverlap($start1, $end1, $start2, $end2)
    {
        if (!$start1 || !$end1 || !$start2 || !$end2) {
            return false;
        }
        
        try {
            $start1 = Carbon::parse($start1);
            $end1 = Carbon::parse($end1);
            $start2 = Carbon::parse($start2);
            $end2 = Carbon::parse($end2);
            
            // Check if ranges overlap
            return $start1->lt($end2) && $start2->lt($end1);
        } catch (\Exception $e) {
            // If parsing fails, assume no conflict
            return false;
        }
    }

    /**
     * Verify admit card by application_id and email
     */
    public function verify(Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|string',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if admit card exists
            $admitCard = TuraAdmitCard::where('application_id', $request->application_id)
                                      ->where('email', $request->email)
                                      ->where('status', 'active')
                                      ->first();

            if (!$admitCard) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Application ID or Email'
                ], 404);
            }

            // Generate download URL
            $downloadUrl = url('/api/admit-card/download/' . $admitCard->admit_no);

            return response()->json([
                'status' => true,
                'message' => 'Record Found',
                'application_id' => $admitCard->application_id,
                'roll_number' => $admitCard->roll_number,
                'full_name' => $admitCard->full_name,
                'download_url' => $downloadUrl
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server error occurred',
                'error' => app()->environment('local') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Download admit card PDF
     */
    public function download($admit_no)
    {
        // Find admit card by admit_no
        $admitCard = TuraAdmitCard::where('admit_no', $admit_no)
                                  ->where('status', 'active')
                                  ->first();

        if (!$admitCard) {
            return response()->json([
                'status' => false,
                'message' => 'Admit card not found'
            ], 404);
        }

        // Update download timestamp
        $admitCard->update(['pdf_downloaded_at' => Carbon::now()]);

        // Prepare data for PDF
        $data = [
            'full_name' => $admitCard->full_name,
            'roll_number' => $admitCard->roll_number,
            'application_id' => $admitCard->application_id,
            'job_title' => $admitCard->job_title,
            'venue_name' => $admitCard->venue_name,
            'venue_address' => $admitCard->venue_address,
            'photo_base64' => $this->processBase64Image($admitCard->photo_base64),
            'admit_no' => $admitCard->admit_no,
            'date_of_birth' => $admitCard->date_of_birth ? $admitCard->date_of_birth->format('d-m-Y') : '',
            'gender' => $admitCard->gender,
            'category' => $admitCard->category,
            'phone' => $admitCard->phone,
            'email' => $admitCard->email,
            'logo_path' => $this->getLogoPath(),
            // Slot 1 data
            'has_slot_1' => $admitCard->hasSlot1(),
            'subject_slot_1' => $admitCard->subject_slot_1,
            'exam_date_slot_1' => $admitCard->slot_1_exam_date,
            'exam_time_slot_1' => $admitCard->slot_1_exam_time,
            'reporting_time_slot_1' => $admitCard->slot_1_reporting_time,
            // Slot 2 data
            'has_slot_2' => $admitCard->hasSlot2(),
            'subject_slot_2' => $admitCard->subject_slot_2,
            'exam_date_slot_2' => $admitCard->slot_2_exam_date,
            'exam_time_slot_2' => $admitCard->slot_2_exam_time,
            'reporting_time_slot_2' => $admitCard->slot_2_reporting_time,
        ];

        try {
            $filename = "AdmitCard_{$admit_no}.pdf";
            $pdf = PDF::loadView('pdf.admit_card', $data);
            $pdf->setPaper('A4', 'portrait');
            
            // Set basic options to avoid font issues
            $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
            $pdf->getDomPDF()->set_option('isPhpEnabled', false);
            $pdf->getDomPDF()->set_option('defaultFont', 'DejaVu Sans');
            
            // Create response with proper download headers
            $response = response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->header('Access-Control-Expose-Headers', 'Content-Disposition');
            
            return $response;
            
        } catch (\Exception $e) {
            \Log::error('Error generating PDF admit card', [
                'admit_no' => $admit_no,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Unable to generate admit card PDF. Please try again or contact support.'
            ], 500);
        }
    }

    /**
     * Process base64 image data for PDF display
     */
    private function processBase64Image($base64Data)
    {
        if (!$base64Data) {
            return null;
        }

        // Remove data:image/jpeg;base64, prefix if present
        $base64Data = preg_replace('/^data:image\/[^;]+;base64,/', '', $base64Data);
        
        // Validate base64 data
        if (!base64_decode($base64Data, true)) {
            return null;
        }

        return $base64Data;
    }

    /**
     * Get logo path for PDF
     */
    private function getLogoPath()
    {
        // Try storage path first
        $storagePath = storage_path('app/public/email/turaLogo.png');
        
        if (file_exists($storagePath)) {
            return $storagePath;
        }
        
        // Try public path as fallback
        $publicPath = public_path('storage/email/turaLogo.png');
        
        if (file_exists($publicPath)) {
            return $publicPath;
        }
        
        // No logo found
        return null;
    }
}