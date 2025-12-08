<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TuraAdmitCard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AdmitCardController extends Controller
{
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
            'exam_date' => $admitCard->formatted_exam_date,
            'exam_time' => $admitCard->exam_time,
            'reporting_time' => $admitCard->formatted_reporting_time,
            'venue_name' => $admitCard->venue_name,
            'venue_address' => $admitCard->venue_address,
            'photo_base64' => $this->processBase64Image($admitCard->photo_base64),
            'admit_no' => $admitCard->admit_no,
            'date_of_birth' => $admitCard->date_of_birth ? $admitCard->date_of_birth->format('d-m-Y') : '',
            'gender' => $admitCard->gender,
            'category' => $admitCard->category,
            'phone' => $admitCard->phone,
            'email' => $admitCard->email,
            'logo_path' => $this->getLogoPath()
        ];

        try {
            // Generate PDF using dompdf
            $pdf = Pdf::loadView('pdf.admit_card', $data);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = "AdmitCard_{$admit_no}.pdf";
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
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