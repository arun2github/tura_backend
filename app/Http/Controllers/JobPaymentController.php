<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\JobAppliedStatus;
use App\Models\TuraJobPosting;
use App\Models\JobPersonalDetail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * JobPaymentController
 * 
 * Handles payment processing for job applications using SBI ePay integration
 */
class JobPaymentController extends Controller
{
    /**
     * Initiate payment for job application
     */
    public function initiatePayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'job_id' => 'required|integer',
                'application_id' => 'required|string',
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
            $applicationId = $request->application_id;

            // Get application status
            $applicationStatus = JobAppliedStatus::where([
                'user_id' => $userId,
                'job_id' => $jobId,
                'application_id' => $applicationId
            ])->first();

            if (!$applicationStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            // Check if payment is already completed
            if ($applicationStatus->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already completed for this application'
                ], 400);
            }

            // Get job details for fee calculation
            $jobPosting = TuraJobPosting::find($jobId);
            if (!$jobPosting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job posting not found'
                ], 404);
            }

            // Get personal details for category-based fee
            $personalDetails = JobPersonalDetail::where([
                'user_id' => $userId,
                'job_id' => $jobId
            ])->first();

            if (!$personalDetails) {
                return response()->json([
                    'success' => false,
                    'message' => 'Personal details not found. Please complete your application first.'
                ], 400);
            }

            // Calculate payment amount based on category
            $paymentAmount = $this->calculatePaymentAmount($jobPosting, $personalDetails->category);

            if ($paymentAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment amount calculated'
                ], 400);
            }

            // Generate unique order ID
            $orderID = $applicationId . '-' . time() . rand(1000, 9999);

            // Prepare payment parameters for SBI ePay
            $key = config('services.sbiepay.key', env('PAYMENT_KEY'));
            $merchantId = config('services.sbiepay.merchant_id', '1003253');
            $successUrl = config('app.url') . '/api/job-payment/success';
            $failureUrl = config('app.url') . '/api/job-payment/failure';

            $requestParameter = "{$merchantId}|DOM|IN|INR|{$paymentAmount}|Other|{$successUrl}|{$failureUrl}|SBIEPAY|{$orderID}|2|NB|ONLINE|ONLINE";

            Log::info('Job Payment Request', [
                'application_id' => $applicationId,
                'order_id' => $orderID,
                'amount' => $paymentAmount,
                'request_parameter' => $requestParameter
            ]);

            // Update application status with payment details
            $applicationStatus->update([
                'payment_order_id' => $orderID,
                'payment_amount' => $paymentAmount,
                'payment_status' => 'pending', // Use 'pending' as per enum values
                'updated_at' => now()
            ]);

            // Encrypt the data for SBI ePay
            $encryptedData = $this->encrypt($requestParameter, $key);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => [
                    'order_id' => $orderID,
                    'amount' => $paymentAmount,
                    'encrypted_data' => $encryptedData,
                    'payment_url' => config('app.url') . '/job-payment-form',
                    'application_id' => $applicationId
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error initiating job payment: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error initiating payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show payment form
     */
    public function showPaymentForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'Invalid payment request');
        }

        $orderId = $request->order_id;

        // Get application record by order ID
        $applicationStatus = JobAppliedStatus::where('payment_order_id', $orderId)->first();

        if (!$applicationStatus || $applicationStatus->payment_status !== 'pending') {
            return redirect()->back()->with('error', 'Payment session expired or invalid');
        }

        // Reconstruct request parameter for payment gateway
        $key = config('services.sbiepay.key', env('PAYMENT_KEY'));
        $merchantId = config('services.sbiepay.merchant_id', '1003253');
        $successUrl = config('app.url') . '/api/job-payment/success';
        $failureUrl = config('app.url') . '/api/job-payment/failure';

        $requestParameter = "{$merchantId}|DOM|IN|INR|{$applicationStatus->payment_amount}|Other|{$successUrl}|{$failureUrl}|SBIEPAY|{$orderId}|2|NB|ONLINE|ONLINE";
        $encryptedData = $this->encrypt($requestParameter, $key);

        return view('job-payment-form', compact('encryptedData', 'applicationStatus'));
    }

    /**
     * Handle successful payment callback
     */
    public function handlePaymentSuccess(Request $request)
    {
        try {
            $key = config('services.sbiepay.key', env('PAYMENT_KEY'));

            // Validate that the 'encData' is present in the request
            if (!$request->has('encData')) {
                Log::error('Missing encData parameter in job payment success callback');
                return $this->redirectToFailure('Missing payment data');
            }

            // Retrieve encrypted data
            $encData = $request->input('encData');

            // Decrypt the data
            try {
                $data = $this->decrypt($encData, $key);
            } catch (\Exception $e) {
                Log::error('Job payment decryption failed', ['exception' => $e]);
                return $this->redirectToFailure('Payment data decryption failed');
            }

            // Validate the decrypted data
            $explode = explode("|", $data);

            if (count($explode) < 4) {
                Log::warning('Invalid job payment decrypted data format', ['data' => $data]);
                return $this->redirectToFailure('Invalid payment response format');
            }

            // Extract payment details from decrypted data
            $orderId = $explode[0];
            $status = $explode[2];
            $amount = $explode[3];

            Log::info('Job Payment Callback Data', [
                'order_id' => $orderId,
                'status' => $status,
                'amount' => $amount,
                'response' => $data
            ]);

            // Get application record by order ID
            $applicationStatus = JobAppliedStatus::where('payment_order_id', $orderId)->first();

            if (!$applicationStatus) {
                Log::error('Job application record not found', ['order_id' => $orderId]);
                return $this->redirectToFailure('Payment record not found');
            }

            // Perform double verification
            $doubleVerification = $this->doubleVerification($orderId, $amount);

            // Check if the double verification failed
            if ($doubleVerification['status'] != "success") {
                $this->updatePaymentStatus($applicationStatus, 'failed', $data, null);

                Log::error('Job payment double verification failed', [
                    'order_id' => $orderId,
                    'response' => $doubleVerification
                ]);

                return $this->redirectToFailure('Payment verification failed');
            }

            // Parse double verification response
            $explodeDoubleVeri = explode("|", $doubleVerification['data']);

            // Check payment status
            if ($status == "SUCCESS" || (count($explodeDoubleVeri) >= 3 && trim($explodeDoubleVeri[2]) == "SUCCESS")) {
                // Payment successful
                $transactionId = isset($explode[1]) ? $explode[1] : $orderId;
                
                $this->updatePaymentStatus($applicationStatus, 'paid', $data, $transactionId);

                // Send payment confirmation email
                $this->sendPaymentConfirmationEmail($applicationStatus);

                Log::info('Job payment successful', [
                    'order_id' => $orderId,
                    'application_id' => $applicationStatus->application_id,
                    'transaction_id' => $transactionId
                ]);

                return $this->redirectToSuccess($applicationStatus->application_id);

            } else {
                // Payment failed
                $this->updatePaymentStatus($applicationStatus, 'failed', $data, null);

                Log::info('Job payment failed', [
                    'order_id' => $orderId,
                    'status' => $status,
                    'data' => $data
                ]);

                return $this->redirectToFailure('Payment was not successful');
            }

        } catch (\Exception $e) {
            Log::error('Error handling job payment success: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return $this->redirectToFailure('Payment processing error');
        }
    }

    /**
     * Handle failed payment callback
     */
    public function handlePaymentFailure(Request $request)
    {
        try {
            $key = config('services.sbiepay.key', env('PAYMENT_KEY'));

            if ($request->has('encData')) {
                $encData = $request->input('encData');
                $data = $this->decrypt($encData, $key);
                $explode = explode("|", $data);

                if (count($explode) >= 1) {
                    $orderId = $explode[0];
                    
                    $applicationStatus = JobAppliedStatus::where('payment_order_id', $orderId)->first();
                    if ($applicationStatus) {
                        $this->updatePaymentStatus($applicationStatus, 'failed', $data, null);
                    }

                    Log::info('Job payment failure callback', [
                        'order_id' => $orderId,
                        'data' => $data
                    ]);
                }
            }

            return $this->redirectToFailure('Payment was cancelled or failed');

        } catch (\Exception $e) {
            Log::error('Error handling job payment failure: ' . $e->getMessage());
            return $this->redirectToFailure('Payment processing error');
        }
    }

    /**
     * Get payment status for an application
     */
    public function getPaymentStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $applicationId = $request->application_id;

            // Get application status
            $applicationStatus = JobAppliedStatus::where('application_id', $applicationId)->first();

            if (!$applicationStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment status retrieved successfully',
                'data' => [
                    'application_id' => $applicationId,
                    'payment_status' => $applicationStatus->payment_status,
                    'payment_amount' => $applicationStatus->payment_amount,
                    'payment_date' => $applicationStatus->payment_date,
                    'transaction_id' => $applicationStatus->payment_transaction_id,
                    'order_id' => $applicationStatus->payment_order_id,
                    'payment_confirmation_email_sent' => $applicationStatus->payment_confirmation_email_sent
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting job payment status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate payment amount based on job and category
     */
    private function calculatePaymentAmount($jobPosting, $category)
    {
        $category = strtolower($category);

        switch ($category) {
            case 'sc':
            case 'st':
                return (float) $jobPosting->fee_sc_st;
            case 'obc':
                return (float) ($jobPosting->fee_obc ?: $jobPosting->fee_general);
            case 'ur':
            case 'general':
            default:
                return (float) $jobPosting->fee_general;
        }
    }

    /**
     * Update payment status in tura_job_applied_status table
     */
    private function updatePaymentStatus($applicationStatus, $status, $responseData, $transactionId = null)
    {
        $updateData = [
            'payment_status' => $status,
            'updated_at' => now()
        ];

        if ($status === 'paid') {
            $updateData['payment_date'] = now();
            $updateData['payment_transaction_id'] = $transactionId;
            $updateData['stage'] = JobAppliedStatus::STAGES['print_application'];
            $updateData['payment_confirmation_email_sent'] = 0; // Reset flag to send confirmation
        }

        $applicationStatus->update($updateData);

        Log::info('Payment status updated', [
            'application_id' => $applicationStatus->application_id,
            'status' => $status,
            'transaction_id' => $transactionId
        ]);
    }

    /**
     * Send payment confirmation email
     */
    private function sendPaymentConfirmationEmail($applicationStatus)
    {
        try {
            // Get application details
            $personalDetails = JobPersonalDetail::where([
                'user_id' => $applicationStatus->user_id,
                'job_id' => $applicationStatus->job_id
            ])->first();
            $jobPosting = TuraJobPosting::find($applicationStatus->job_id);

            if (!$personalDetails || !$jobPosting) {
                Log::warning('Missing data for payment confirmation email', [
                    'application_id' => $applicationStatus->application_id
                ]);
                return false;
            }

            // Check if email already sent
            if ($applicationStatus->payment_confirmation_email_sent) {
                Log::info('Payment confirmation email already sent', [
                    'application_id' => $applicationStatus->application_id
                ]);
                return true;
            }

            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = config('mail.mailers.smtp.host');
            $mail->SMTPAuth = true;
            $mail->Username = config('mail.mailers.smtp.username');
            $mail->Password = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = config('mail.mailers.smtp.port');

            // Recipients
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($personalDetails->email, $personalDetails->full_name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Payment Confirmation - Application ID: {$applicationStatus->application_id}";
            $mail->Body = $this->generatePaymentConfirmationEmailTemplate(
                $personalDetails->full_name,
                $applicationStatus->application_id,
                $jobPosting,
                $applicationStatus->payment_amount,
                $applicationStatus->payment_transaction_id
            );

            $mail->send();

            // Mark email as sent
            $applicationStatus->update(['payment_confirmation_email_sent' => 1]);

            Log::info('Payment confirmation email sent successfully', [
                'application_id' => $applicationStatus->application_id,
                'email' => $personalDetails->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email: ' . $e->getMessage(), [
                'application_id' => $applicationStatus->application_id
            ]);
            return false;
        }
    }

    /**
     * Generate payment confirmation email template
     */
    private function generatePaymentConfirmationEmailTemplate($fullName, $applicationId, $jobPosting, $amount, $transactionId)
    {
        $logoBase64 = $this->getEmbeddedLogo();
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Payment Confirmation</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: \"Times New Roman\", serif; background-color: #f8f9fa;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #dee2e6;'>
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 20px; text-align: center;'>
                    <img src='{$logoBase64}' alt='Tura Municipal Board Logo' style='height: 60px; margin-bottom: 10px;'>
                    <h1 style='margin: 0; font-size: 24px; font-weight: bold;'>TURA MUNICIPAL BOARD</h1>
                    <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Payment Confirmation</p>
                </div>

                <!-- Content -->
                <div style='padding: 30px;'>
                    <h2 style='color: #28a745; margin-bottom: 20px; font-size: 20px;'>
                        ✅ Payment Successful!
                    </h2>
                    
                    <p style='margin-bottom: 20px; line-height: 1.6;'>
                        Dear <strong>{$fullName}</strong>,
                    </p>
                    
                    <p style='margin-bottom: 20px; line-height: 1.6;'>
                        Your payment for the job application has been successfully processed. Here are the details:
                    </p>

                    <!-- Payment Details -->
                    <div style='background-color: #f8f9fa; border-left: 4px solid #28a745; padding: 20px; margin: 20px 0;'>
                        <h3 style='margin: 0 0 15px 0; color: #333; font-size: 16px;'>Payment Details</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; width: 40%;'>Application ID:</td>
                                <td style='padding: 8px 0;'>{$applicationId}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold;'>Job Position:</td>
                                <td style='padding: 8px 0;'>{$jobPosting->job_title_department}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold;'>Payment Amount:</td>
                                <td style='padding: 8px 0;'>₹{$amount}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold;'>Transaction ID:</td>
                                <td style='padding: 8px 0;'>{$transactionId}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold;'>Payment Date:</td>
                                <td style='padding: 8px 0;'>" . date('d-m-Y H:i:s') . "</td>
                            </tr>
                        </table>
                    </div>

                    <div style='background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0;'>
                        <p style='margin: 0; color: #1976d2;'>
                            <strong>📋 Next Steps:</strong><br>
                            Your application is now complete. You can download and print your application form from your dashboard.
                        </p>
                    </div>

                    <p style='margin-bottom: 20px; line-height: 1.6;'>
                        Thank you for applying with Tura Municipal Board. If you have any questions, please contact our office.
                    </p>

                    <div style='text-align: center; margin: 30px 0;'>
                        <p style='color: #666; font-size: 14px; margin: 0;'>
                            This is an automated email. Please do not reply to this message.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;'>
                    <p style='margin: 0; color: #666; font-size: 12px;'>
                        © " . date('Y') . " Tura Municipal Board. All rights reserved.<br>
                        East Garo Hills, Meghalaya
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Get embedded logo for email
     */
    private function getEmbeddedLogo()
    {
        try {
            $logoPath = public_path('images/tura_municipal_board_logo.png');
            
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                return 'data:image/png;base64,' . $logoData;
            } else {
                // Return a placeholder or default logo
                return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
            }
        } catch (\Exception $e) {
            Log::warning('Failed to load logo for email: ' . $e->getMessage());
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
        }
    }

    /**
     * Redirect to success page
     */
    private function redirectToSuccess($applicationId)
    {
        $successUrl = config('app.frontend_url', 'https://turamunicipalboard.com') . '/job-payment-success?application_id=' . $applicationId;
        return redirect()->away($successUrl);
    }

    /**
     * Redirect to failure page
     */
    private function redirectToFailure($reason = 'Payment failed')
    {
        $failureUrl = config('app.frontend_url', 'https://turamunicipalboard.com') . '/job-payment-failure?reason=' . urlencode($reason);
        return redirect()->away($failureUrl);
    }

    /**
     * Encrypt data for SBI ePay
     */
    public function encrypt($data, $key)
    {
        $iv = substr($key, 0, 16);
        $algo = 'aes-128-cbc';

        $cipherText = openssl_encrypt(
            $data,
            $algo,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($cipherText);
    }

    /**
     * Decrypt data from SBI ePay
     */
    public function decrypt($cipherText, $key)
    {
        $iv = substr($key, 0, 16);
        $algo = 'aes-128-cbc';

        $cipherText = base64_decode($cipherText);

        return openssl_decrypt(
            $cipherText,
            $algo,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    /**
     * Double verification with SBI ePay
     */
    public function doubleVerification($orderId, $amount)
    {
        $merchantId = config('services.sbiepay.merchant_id', '1003253');
        $merchantOrderNo = $orderId;
        $url = "https://www.sbiepay.sbi/payagg/statusQuery/getStatusQuery";
        $queryRequest = "|$merchantId|$merchantOrderNo|$amount";

        $queryRequest33 = http_build_query(array(
            'queryRequest' => $queryRequest,
            "aggregatorId" => "SBIEPAY",
            "merchantId" => $merchantId
        ));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $queryRequest33,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return array('status' => "error", 'data' => curl_error($ch));
        } else {
            curl_close($ch);
            return array('status' => "success", 'data' => $response);
        }
    }
}