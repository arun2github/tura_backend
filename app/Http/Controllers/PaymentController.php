<?php

namespace App\Http\Controllers;

use App\Exceptions\MunicipalBoardException;
use App\Models\User;
use App\Models\LocalityModel;
use App\Models\WardList;
use App\Notifications\VerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Artisan;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Hash;
use App\Models\FormMasterTblModel;
use App\Models\TradeLicenseFee;
use App\Models\FormEntityModel;
use App\Models\PaymentModel;

/**
 * @since 11-Aug-2024
 *
 * This is the main controller that is responsible for user registration,login,user-profile
 * refresh and logout API's.
 */
class PaymentController extends Controller
{
    public function payment($id){
        // Sample key and request parameter (You can dynamically set these as needed)
        $key = $_ENV['PAYMENT_KEY'];
        
        // List of valid form IDs for payment
    $payment_form_ids = [0, 5, 6, 7, 8, 10]; // Added 0 for Pet Dog Registration

    // Retrieve the record with the specified application ID
    $data = FormMasterTblModel::where('application_id', $id)->first();

    // Check if the record exists
    if (!$data) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Invalid application ID.',
        ], 404); // Return 404 if record is not found
    }
    
    if($data->employee_status != "Approved" && $data->ceo_status != "Approved"){
        return response()->json([
            'status' => 'failed',
            'message' => 'Form is not approved yet.',
        ], 404); // Return 404 if record is not found
    }

    // Check if the form_id is in the allowed payment form IDs
    if (!in_array($data->form_id, $payment_form_ids)) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Payment is not required for this form.',
        ], 404); // Return 404 if form_id is not in the valid list
    }
    
    $amount = "";

        // Handling specific cases for form_id 5 or 7
        if (in_array($data->form_id, [5, 7])) {
            $formEntity = FormEntityModel::where('parameter', 'type_of_trade')
                                          ->where('form_id', $data->id)
                                          ->first();
    
            if (!$formEntity) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Form entity not found.',
                ], 404); // Return 404 if the form entity is not found
            }
    
            // Find fees associated with the trade license fee
            $fees = TradeLicenseFee::where('trade_type', $formEntity->value)->first();
    
            // If necessary, you can log or debug the $formEntity for analysis.
            Log::info('Form Entity Found', ['formEntity' => $formEntity]);
    
            // Example debug: Removing dd() for production
            //dd($fees); // Avoid using dd() in production. Use logging instead if needed.
            $amount = $fees->license_fee;
        }else if($data->form_id == 8){
             $formEntity = FormEntityModel::where('parameter', 'water_tanker_list')
                                          ->where('form_id', $data->id)
                                          ->first();
    
            if (!$formEntity) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Form entity not found.',
                ], 404); // Return 404 if the form entity is not found
            }
            $feeArray = $formEntity->value;
            // Decode the string to get the actual array
            $fees = json_decode($feeArray, true); // true to get an associative array
            
            // Check if the fees array has at least one item
            if (isset($fees[0])) {
                // Extract the string to match the fee amount (e.g., 1,000)
                preg_match('/Rs\s([\d,]+)/', $fees[0], $matches);
            
                // If a match is found, remove commas and convert to integer
                if (isset($matches[1])) {
                    $amount = (int) str_replace(',', '', $matches[1]);
                }
            }
        }else if($data->form_id == 6){
             $formEntity = FormEntityModel::whereIn('parameter', ['cesspoolTankerGeneral','cesspoolTankerGoverment','cesspoolTankerOutsideMuni'])
                                          ->where('form_id', $data->id)
                                          ->first();
    
            if (!$formEntity) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Form entity not found.',
                ], 404); // Return 404 if the form entity is not found
            }
            $feeArray = $formEntity->value;
            // Decode the string to get the actual array
            $fees = json_decode($feeArray, true); // true to get an associative array
            
            // Check if the fees array has at least one item
            if (isset($fees[0])) {
                // Extract the string to match the fee amount (e.g., 1,000)
                preg_match('/Rs\s([\d,]+)/', $fees[0], $matches);
            
                // If a match is found, remove commas and convert to integer
                if (isset($matches[1])) {
                    $amount = (int) str_replace(',', '', $matches[1]);
                }
            }
        }else if($data->form_id == 0){
            // Pet Dog Registration - Fixed amount ₹250
            $amount = 250;
        }else if($data->form_id == 10){
            $formEntity = FormEntityModel::where('parameter', 'requirementTypeOf')
                                          ->where('form_id', $data->id)
                                          ->first();
                                          
            if (!$formEntity) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Form entity not found.',
                ], 404); // Return 404 if the form entity is not found
            }
            $formEntityNumber = FormEntityModel::where('parameter', 'no_of_banners')
                                          ->where('form_id', $data->id)
                                          ->first();
            $formEntitySize = FormEntityModel::where('parameter', 'size_of_banner')
                                          ->where('form_id', $data->id)
                                          ->first();
            if($formEntity->value == "Banner"){
                $amount = ($_ENV['BANNER_AMT'] * $formEntitySize->value) * $formEntityNumber->value;
            }else if($formEntity->value == "Hoarding"){
                $amount = ($_ENV['HOARDING_AMT'] * $formEntitySize->value) * $formEntityNumber->value;
            }else if($formEntity->value == "Poster"){
                if($formEntitySize->value == "A4"){
                    $amount = $_ENV['A4_AMT'] * $formEntityNumber->value;
                }else if($formEntitySize->value == "A3"){
                    $amount = $_ENV['A3_AMT'] * $formEntityNumber->value;
                }
            }
            
        }
        
        $orderID = $id.rand(00000,100000);
        
        $requestParameter  = "1003253|DOM|IN|INR|".$amount."|Other|https://laravelv2.turamunicipalboard.com/api/successData|https://laravelv2.turamunicipalboard.com/api/successData|SBIEPAY|".$orderID."|2|NB|ONLINE|ONLINE";
Log::warning(' eecrypted data format', ['data' => $requestParameter]);
        PaymentModel::updateOrCreate(
            ['form_id' => $data->id],
            [
                'request_body' => $requestParameter,
                'order_id' => $orderID, 
                'form_id' => $data->id,
                'payment_id' => $orderID,
                'amount' => $amount,
                'form_type_id' => $data->form_id // Add form type ID
            ]
        );

        // Encrypt the data
        $encryptedData = $this->encrypt($requestParameter, $key);

        return view('payment',compact('encryptedData','requestParameter'));
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
            return redirect()->away('https://turamunicipalboard.com/failure');
        }
    
        // Retrieve encrypted data
        $encData = $request->input('encData');
    
        // Decrypt the data
        try {
            $data = $this->decrypt($encData, $key);
        } catch (\Exception $e) {
            Log::error('Decryption failed', ['exception' => $e]);
             return redirect()->away('https://turamunicipalboard.com/failure');
        }
    
        // Validate the decrypted data
        $explode = explode("|", $data);
    
        if (count($explode) < 3) {
            Log::warning('Invalid decrypted data format', ['data' => $data]);
            return redirect()->away('https://turamunicipalboard.com/failure');
        }
    
        // Extract status from decrypted data
        $status = $explode[2];
        $amount = $explode[3];
        $orderId = $explode[0];
         Log::error('Data', [
                'order_id' => $explode[0],
                'response' => $data
            ]);
        
        $doubleVerification = $this->doubleVerification($orderId, $amount);

        // Check if the double verification failed (e.g., cURL or server error)
        if ($doubleVerification['status'] != "success") {
            PaymentModel::where('order_id', $explode[0])->update([
                'status' => 'failed',
                'response_body' => $data
            ]);
        
            Log::error('Double verification failed', [
                'order_id' => $explode[0],
                'response' => $doubleVerification
            ]);
        
            return redirect()->away('https://turamunicipalboard.com/failure');
        }
        
        // Safely explode and check the response format
        $explodeDoubleVeri = explode("|", $doubleVerification['data']);
        
        // Ensure there are enough elements
        if (count($explodeDoubleVeri) < 3 || trim($explodeDoubleVeri[2]) == "SUCCESS") {
            PaymentModel::where('order_id', $explode[0])->update(['status' => 'success', 'response_body' => $data]);
    
            Log::info('Transaction successful', ['data' => $data]);
            return redirect()->away('https://turamunicipalboard.com/success');
        }
    
        // Redirect based on the status
        if ($status == "SUCCESS") {
            PaymentModel::where('order_id', $explode[0])->update(['status' => 'success', 'response_body' => $data]);
    
            Log::info('Transaction successful', ['data' => $data]);
            return redirect()->away('https://turamunicipalboard.com/success');
        } elseif ($status == "FAIL") {
            PaymentModel::where('order_id', $explode[0])->update(['status' => 'failed', 'response_body' => $data]);
    
            Log::info('Transaction failed', ['data' => $data]);
            return redirect()->away('https://turamunicipalboard.com/failure');
        } else {
            PaymentModel::where('order_id', $explode[0])->update(['status' => 'failed', 'response_body' => $data]);
            Log::warning('Unknown status received', ['status' => $status, 'data' => $data]);
            return redirect()->away('https://turamunicipalboard.com/failure');
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
    
        // Log::info('Double verification API error', $result);
        // Log::info('Double verification API response', $response);
        // Log::info('Double verification API result', $result);
    
        // return $result;
    // }
}


