<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\JobAppliedStatus;
use App\Models\TuraJobPosting;
use App\Models\JobPersonalDetail;

/**
 * JobPaymentController
 * 
 * Handles payment processing for job applications using SBI ePay integration
 * Exact copy of PaymentController logic adapted for job applications
 */
class JobPaymentController extends Controller
{
    public function payment($id){
        // Sample key and request parameter (same as PaymentController)
        $key = $_ENV['PAYMENT_KEY'];
        
        // Get application record by application ID (instead of FormMasterTblModel)
        $data = JobAppliedStatus::where('application_id', $id)->first();

        // Check if the record exists (same logic as PaymentController)
        if (!$data) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid application ID.',
            ], 404); // Return 404 if record is not found
        }
        
        // Check if application is complete (equivalent to approved status in PaymentController)
        if ($data->stage < 6) { // Stage 6 is document upload complete
            return response()->json([
                'status' => 'failed',
                'message' => 'Application is not complete yet.',
            ], 404); // Return 404 if application is not complete
        }

        // Check if payment is already completed
        if ($data->payment_status === 'paid') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Payment already completed for this application.',
            ], 404);
        }

        // Get job details for fee calculation (equivalent to form entity logic)
        $jobPosting = TuraJobPosting::find($data->job_id);
        
        if (!$jobPosting) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Job posting not found.',
            ], 404);
        }

        // Get personal details for category-based fee (equivalent to form entity logic)
        $personalDetails = JobPersonalDetail::where([
            'user_id' => $data->user_id,
            'job_id' => $data->job_id
        ])->first();

        if (!$personalDetails) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Personal details not found.',
            ], 404);
        }

        // Calculate payment amount based on category (same variable name as PaymentController)
        // HARDCODED FOR TESTING: Set to ₹1 for production payment testing
       // $this->calculatePaymentAmount($jobPosting, $personalDetails->category);
       
        $amount = $this->calculatePaymentAmount($jobPosting, $personalDetails->category);

        
        // Generate order ID (same logic as PaymentController)
        $orderID = $id.rand(00000,100000);
        
        // Create request parameter (same format as PaymentController)
        $requestParameter  = "1003253|DOM|IN|INR|".$amount."|Other|https://laravelv2.turamunicipalboard.com/api/job-successData|https://laravelv2.turamunicipalboard.com/api/job-successData|SBIEPAY|".$orderID."|2|NB|ONLINE|ONLINE";
        
        Log::warning('Job payment encrypted data format', ['data' => $requestParameter]);
        
        // Update application status with payment details (equivalent to PaymentModel in PaymentController)
        $updateResult = $data->update([
            'payment_order_id' => $orderID,
            'payment_amount' => $amount,
            'payment_status' => 'pending',
            'payment_transaction_id' => null, // Will be set on success
            'payment_date' => null, // Will be set on success
            'payment_request_body' => $requestParameter, // Store request parameters like PaymentController
            'payment_response_body' => null, // Will be set when payment callback is received
            'updated_at' => now()
        ]);

        Log::info('Payment setup - Database update', [
            'application_id' => $id,
            'order_id' => $orderID,
            'amount' => $amount,
            'update_result' => $updateResult,
            'record_id' => $data->id,
            'before_update' => [
                'payment_order_id' => $data->payment_order_id,
                'payment_status' => $data->payment_status,
                'payment_amount' => $data->payment_amount
            ]
        ]);

        // Refresh the model to see if the update actually worked
        $data->refresh();
        
        Log::info('Payment setup - After database update', [
            'order_id' => $orderID,
            'after_update' => [
                'payment_order_id' => $data->payment_order_id,
                'payment_status' => $data->payment_status,
                'payment_amount' => $data->payment_amount
            ]
        ]);

        // Encrypt the data (same logic as PaymentController)
        $encryptedData = $this->encrypt($requestParameter, $key);

        // Return view (same as PaymentController but with job-payment-form view)
        // Pass applicationStatus data that the view template expects
        $applicationStatus = $data; // Use the JobAppliedStatus record
        return view('job-payment-form', compact('encryptedData', 'requestParameter', 'applicationStatus'));
    }

    /**
     * Calculate payment amount based on job and category (equivalent to PaymentController fee calculation)
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
    
    public function encrypt($data, $key)
    {
        // We will use openssl for AES encryption
        $iv = substr($key, 0, 16);  // Using the first 16 bytes of the key as the IV
        $algo = 'aes-128-cbc'; // AES algorithm with 128 bit key, CBC mode

        // Encrypt the data
        $cipherText = openssl_encrypt(
            $data,
            $algo,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Encode the encrypted data to base64
        return base64_encode($cipherText);
    }

    public function decrypt($cipherText, $key)
    {
        // We will use openssl for AES decryption
        $iv = substr($key, 0, 16);  // Using the first 16 bytes of the key as the IV
        $algo = 'aes-128-cbc'; // AES algorithm with 128 bit key, CBC mode

        // Decode the base64 encoded ciphertext
        $cipherText = base64_decode($cipherText);

        // Decrypt the data
        return openssl_decrypt(
            $cipherText,
            $algo,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
    
   public function successData(Request $request){
        // Define the encryption key
        $key = $_ENV['PAYMENT_KEY'];
        
        // Validate that the 'encData' is present in the request
        if (!$request->has('encData')) {
            Log::error('Missing encData parameter in the request');
            return redirect()->away('https://turamunicipalboard.com/failureJobPayment.html');
            
        }
    
        // Retrieve encrypted data
        $encData = $request->input('encData');
    
        // Decrypt the data
        try {
            $data = $this->decrypt($encData, $key);
        } catch (\Exception $e) {
            Log::error('Decryption failed', ['exception' => $e]);
             return redirect()->away('https://turamunicipalboard.com/failureJobPayment.html');
        }
    
        // Validate the decrypted data
        $explode = explode("|", $data);
    
        if (count($explode) < 3) {
            Log::warning('Invalid decrypted data format', ['data' => $data]);
            return redirect()->away('https://turamunicipalboard.com/failureJobPayment.html');
        }
    
        // Extract status from decrypted data
        $status = $explode[2];
        $amount = $explode[3];
        $orderId = $explode[0];
        Log::error('Job Payment Data', [
                'order_id' => $explode[0],
                'response' => $data,
                'status' => $status,
                'amount' => $amount,
                'exploded_data' => $explode
            ]);
        
        $doubleVerification = $this->doubleVerification($orderId, $amount);

        Log::info('Double Verification Response', [
            'order_id' => $orderId,
            'verification_status' => $doubleVerification['status'],
            'verification_data' => $doubleVerification['data']
        ]);

        // Check if the double verification failed (e.g., cURL or server error)
        if ($doubleVerification['status'] != "success") {
            // Update JobAppliedStatus instead of PaymentModel
            JobAppliedStatus::where('payment_order_id', $explode[0])->update([
                'payment_status' => 'failed',
                'payment_date' => now(), // Set failure date
                'payment_transaction_id' => isset($explode[1]) ? $explode[1] : null, // Store transaction ID even on failure
                'payment_response_body' => $data, // Store response data like PaymentController
                'updated_at' => now()
            ]);
        
            Log::error('Double verification failed', [
                'order_id' => $explode[0],
                'response' => $doubleVerification
            ]);
        
            return redirect()->away('https://turamunicipalboard.com/failureJobPayment.html');
        }
        
        // Safely explode and check the response format
        $explodeDoubleVeri = explode("|", $doubleVerification['data']);
        
        Log::info('Double Verification Parsed', [
            'verification_array' => $explodeDoubleVeri,
            'array_count' => count($explodeDoubleVeri),
            'status_element' => isset($explodeDoubleVeri[2]) ? trim($explodeDoubleVeri[2]) : 'NOT_SET'
        ]);
        
        // Ensure there are enough elements AND status is SUCCESS
        if (count($explodeDoubleVeri) >= 3 && trim($explodeDoubleVeri[2]) == "SUCCESS") {
            // Update JobAppliedStatus instead of PaymentModel
            JobAppliedStatus::where('payment_order_id', $explode[0])->update([
                'payment_status' => 'paid',
                'payment_date' => now(),
                'payment_transaction_id' => isset($explode[1]) ? $explode[1] : $explode[0],
                'payment_response_body' => $data, // Store response data like PaymentController
                'stage' => 7, // Final stage
                'updated_at' => now()
            ]);
    
            Log::info('Transaction successful via double verification', ['data' => $data]);
            return redirect()->away('https://turamunicipalboard.com/successJobPayment.html');
        }
    
        Log::info('Payment Status Check', [
            'status' => $status,
            'status_comparison_SUCCESS' => ($status == "SUCCESS"),
            'about_to_check_main_status_flow' => true
        ]);
    
        // Redirect based on the status
        if ($status == "SUCCESS") {
            // Update JobAppliedStatus instead of PaymentModel
            $updateResult = JobAppliedStatus::where('payment_order_id', $explode[0])->update([
                'payment_status' => 'paid',
                'payment_date' => now(),
                'payment_transaction_id' => isset($explode[1]) ? $explode[1] : $explode[0],
                'payment_response_body' => $data, // Store response data like PaymentController
                'stage' => 7, // Final stage
                'updated_at' => now()
            ]);
    
            Log::info('Transaction successful - Database update', [
                'data' => $data,
                'order_id' => $explode[0],
                'transaction_id' => isset($explode[1]) ? $explode[1] : $explode[0],
                'rows_updated' => $updateResult
            ]);
            return redirect()->away('https://turamunicipalboard.com/successJobPayment.html');
        } elseif ($status == "FAIL") {
            // Update JobAppliedStatus instead of PaymentModel
            JobAppliedStatus::where('payment_order_id', $explode[0])->update([
                'payment_status' => 'failed',
                'payment_date' => now(), // Set failure date
                'payment_transaction_id' => isset($explode[1]) ? $explode[1] : null, // Store transaction ID even on failure
                'payment_response_body' => $data, // Store response data like PaymentController
                'updated_at' => now()
            ]);
    
            Log::info('Transaction failed', ['data' => $data]);
            return redirect()->away('https://turamunicipalboard.com/failureJobPayment.html');
        } else {
            // Update JobAppliedStatus instead of PaymentModel
            JobAppliedStatus::where('payment_order_id', $explode[0])->update([
                'payment_status' => 'failed',
                'payment_date' => now(), // Set failure date
                'payment_transaction_id' => isset($explode[1]) ? $explode[1] : null, // Store transaction ID even on failure
                'payment_response_body' => $data, // Store response data like PaymentController
                'updated_at' => now()
            ]);
            Log::warning('Unknown status received', ['status' => $status, 'data' => $data]);
            return redirect()->away('https://turamunicipalboard.com/failureJobPayment.html');
        }
    }

    public function doubleVerification($orderId, $amount)
    {
        $merchantId = "1003253";
        $merchantOrderNo = $orderId; 
       	$url="https://www.sbiepay.sbi/payagg/statusQuery/getStatusQuery"; // double verification url
	    $queryRequest="|$merchantId|$merchantOrderNo|$amount"; 

		$queryRequest33=http_build_query(array('queryRequest' => $queryRequest,"aggregatorId"=>"SBIEPAY","merchantId"=>$merchantId));
		//echo "$url,$queryRequest33";exit;
		
		$ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_HTTPAUTH       => CURLAUTH_ANY,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $queryRequest33,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, 
            CURLOPT_SSL_VERIFYHOST => 0,     
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);			
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return array('status' => "error",'data' =>curl_error($ch));
        } else {
            return array('status' => "success",'data' =>$response);
        }
	}

    /**
     * Temporary method to handle old route calls to initiatePayment
     * Redirects to the correct payment method
     */
    public function initiatePayment(Request $request)
    {
        // Log the call to this deprecated method
        Log::warning('JobPaymentController - Deprecated initiatePayment called', [
            'request_data' => $request->all(),
            'application_id' => $request->input('application_id'),
            'redirect_to' => 'payment method'
        ]);

        // Extract application ID from request
        $applicationId = $request->input('application_id');
        
        if (!$applicationId) {
            return response()->json([
                'success' => false,
                'message' => 'Application ID is required'
            ], 400);
        }

        // Redirect to the correct payment method
        return $this->payment($applicationId);
    }

    /**
     * Handle old showPaymentFormByApplicationId calls
     * Redirects to the correct payment method
     */
    public function showPaymentFormByApplicationId($applicationId)
    {
        // Log the call to this deprecated method
        Log::warning('JobPaymentController - Deprecated showPaymentFormByApplicationId called', [
            'application_id' => $applicationId,
            'redirect_to' => 'payment method'
        ]);

        // Redirect to the correct payment method
        return $this->payment($applicationId);
    }
}