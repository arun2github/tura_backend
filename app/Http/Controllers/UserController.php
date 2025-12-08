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

/**
 * @since 11-Aug-2024
 *
 * This is the main controller that is responsible for user registration,login,user-profile
 * refresh and logout API's.
 */
class UserController extends Controller
{
    /**
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWardList() {
        try {
            $locality = WardList::select('ward_no', 'status')
                ->where('status', 'Active')
                ->get();
            $wardAndLocality = [];
            foreach($locality as $loc){
                $wardAndLocality[] = json_decode($loc);
            }
            return response()->json([
                'status' => "success",
                'ward' => $wardAndLocality,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Something went worng in getWardList API');
            return $exception->message();
        }
    }
    
    /**
     * if so
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocalityList(Request $request) {
        try {
             $validator = Validator::make($request->all(), [
                'ward_id' => 'required|integer',
            ]);
            
            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }
            
            $locality = LocalityModel::select('id', 'ward_id', 'locality', 'status')
                ->where(['status'=> 'Active','ward_id' => $request->ward_id])
                ->get();
            $wardAndLocality = [];
            foreach($locality as $loc){
                $wardAndLocality[] = json_decode($loc);
            }
            return response()->json([
                'status' => "success",
                'locality' => $wardAndLocality,
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Something went worng in getWardAndLocality API');
            return $exception->message();
        }
    }
    
     /**
     * It takes a POST request and requires fields for the user to register,
     * and validates them if it is validated,creates those values in DB
     * and returns success response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
public function register(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|between:2,50',
            'lastname' => 'required|string|between:2,50',
            'ward_id' => 'nullable', // ✅ optional
            'locality' => 'nullable|string|max:255', // ✅ optional
            'dob' => 'required|date_format:Y-m-d',
            'phone_no' => 'required|string|min:10',
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $userArray = [
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'ward_id' => $request->ward_id ?? null, // ✅ allow null
            'locality' => $request->locality ?? null, // ✅ allow null
            'dob' => $request->dob,
            'phone_no' => $request->phone_no,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ];

        $userObject = new User();
        $existingUser = $userObject->userEmailValidation($request->email);

        // Check if user already exists
        if ($existingUser) {
            if ($existingUser->verifyemail === 'active') {
                throw new MunicipalBoardException("The email has already been taken", 401);
            }

            if ($existingUser->verifyemail === 'inactive') {
                $token = JWTAuth::fromUser($existingUser);
                $emailSent = false;
                $emailError = null;

                try {
                    $emailSent = $this->sendMail($token, $request->email);
                } catch (\Exception $e) {
                    $emailError = $e->getMessage();
                    Log::error('Verification email resend failed for existing user', [
                        'user_id' => $existingUser->id,
                        'email' => $request->email,
                        'error' => $emailError
                    ]);
                }

                Log::info('Resent verification email for existing unverified user', [
                    'user_id' => $existingUser->id,
                    'email' => $request->email,
                    'email_sent' => $emailSent ? 'Yes' : 'No',
                    'action' => 'duplicate_registration_prevented'
                ]);

                $responseMessage = 'User Successfully Registered';
                $statusCode = 201;

                if (!$emailSent && $emailError) {
                    $responseMessage = 'User Registered Successfully, but verification email could not be sent. Please contact support to verify your account.';
                    Log::warning('Existing user verification email resend failed', [
                        'user_id' => $existingUser->id,
                        'email' => $request->email,
                        'error' => $emailError
                    ]);
                }

                return response()->json([
                    'status' => $emailSent ? 'success' : 'warning',
                    'message' => $responseMessage,
                    'user_id' => $existingUser->id,
                    'email_sent' => $emailSent,
                    'verification_required' => true,
                    'email_error' => $emailSent ? null : 'SMTP Authentication Failed - Contact Administrator'
                ], $statusCode);
            }
        }

        // New User Registration
        $userDetail = $userObject->saveUserDetails($userArray);

        $token = JWTAuth::fromUser($userDetail);
        $emailSent = false;
        $emailError = null;

        if ($userDetail) {
            try {
                $emailSent = $this->sendMail($token, $request->email);
            } catch (\Exception $e) {
                $emailError = $e->getMessage();
                Log::error('Registration email failed during registration', [
                    'user_id' => $userDetail->id,
                    'email' => $request->email,
                    'error' => $emailError
                ]);
            }
        }

        Log::info('Registered new user Email : ' . $request->email . ' Email Sent: ' . ($emailSent ? 'Yes' : 'No'));

        $responseMessage = 'User Successfully Registered';
        $statusCode = 201;

        if (!$emailSent && $emailError) {
            $responseMessage = 'User Registered Successfully, but verification email could not be sent. Please contact support to verify your account.';
            Log::warning('User registered but email failed', [
                'user_id' => $userDetail->id,
                'email' => $request->email,
                'error' => $emailError
            ]);
        }

        return response()->json([
            'status' => $emailSent ? 'success' : 'warning',
            'message' => $responseMessage,
            'user_id' => $userDetail->id,
            'email_sent' => $emailSent,
            'verification_required' => true,
            'email_error' => $emailSent ? null : 'SMTP Authentication Failed - Contact Administrator'
        ], $statusCode);

    } catch (MunicipalBoardException $exception) {
        Log::error('Invalid User');
        return $exception->message();
    }
}


public function login(Request $request)
{
    try {
        // ✅ Step 1: Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // ✅ Step 2: Check if user exists
        $userObject = new User();
        $user = $userObject->userEmailValidation($request->email);

        if (!$user) {
            Log::error('User not found during login', ['email' => $request->email]);
            throw new MunicipalBoardException(
                "We cannot find the user with that e-mail address. You need to register first.",
                401
            );
        }

        // ✅ Step 3: Check credentials
        if (!$token = auth()->attempt($validator->validated())) {
            throw new MunicipalBoardException("Invalid Credentials", 401);
        }

        // ✅ Step 4: Check if email is verified
        if ($user->verifyemail === 'inactive') {
            $token = Auth::fromUser($user);
            $this->sendMail($token, $request->email);

            return response()->json([
                'status' => 211,
                'message' => 'Email not verified. Verification link has been sent again.'
            ], 211);
        }

        // ✅ Step 5: Safely fetch locality and ward (both optional)
        $locality = null;
        $ward = null;

        if (!empty($user->locality)) {
            $locality = LocalityModel::select('id', 'ward_id', 'locality', 'status')
                ->where('id', $user->locality)
                ->first();
        }

        if (!empty($user->ward_id)) {
            $ward = WardList::select('id', 'ward_no', 'status')
                ->where('id', $user->ward_id)
                ->first();
        }

        // ✅ Step 6: Build structured user details safely
        $user_details = [
            "firstname" => $user->firstname,
            "lastname" => $user->lastname,
            "dob" => $user->dob,
            "email" => $user->email,
            "phone_no" => $user->phone_no,
            "ward" => $ward ? [
                "id" => $ward->id,
                "ward_no" => $ward->ward_no
            ] : null,
            "locality" => $locality ? [
                "id" => $locality->id,
                "name" => $locality->locality
            ] : null
        ];

        // ✅ Step 7: Logging success
        Log::info('User login successful', [
            'email' => $request->email,
            'user_id' => $user->id,
            'has_ward' => $ward ? 'Yes' : 'No',
            'has_locality' => $locality ? 'Yes' : 'No'
        ]);

        // ✅ Step 8: Return JSON response
        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'role' => $user->role,
            'message' => 'Login successful',
            'user_details' => $user_details
        ], 200);

    } catch (MunicipalBoardException $exception) {
        Log::error('MunicipalBoardException in login', ['error' => $exception->getMessage()]);
        return $exception->message();
    } catch (\Exception $e) {
        Log::error('Unexpected error in login', [
            'email' => $request->email ?? 'unknown',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong during login. Please try again later.'
        ], 500);
    }
}

    /**
     * Takes the POST request and user credentials checks if it correct,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // public function login(Request $request) {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'email' => 'required|email',
    //             'password' => 'required|string|min:6',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json($validator->errors(), 400);
    //         }

    //         $userObject = new User();
    //         $user = $userObject->userEmailValidation($request->email);
    //         if (!$user) {
    //             Log::error('User failed to login.', ['id' => $request->email]);
    //             throw new MunicipalBoardException("we can not find the user with that e-mail address You need to register first", 401);
    //         }

    //         if (!$token = auth()->attempt($validator->validated())) {
    //             throw new MunicipalBoardException("Invalid Credentials", 401);
    //         }
            
    //         if($user->verifyemail === 'inactive') {
    //             $token = Auth::fromUser($user);

    //             $this->sendMail($token,$request->email);

    //             return  response()->json([
    //                 'status' => 211,
    //                 'message' => 'Email Not verified'
    //             ],211);
    //         }

    //         Log::info('Login Success : ' . 'Email Id :' . $request->email);
    //         $locality = LocalityModel::select('locality','id','ward_id','status')->where('id',$user->locality)->first();
    //         $ward = WardList::select('ward_no')->where('id',$user->ward_id)->first();

    //         $user_details = [
    //             "firstname" => $user->firstname,
    //             "lastname" => $user->lastname,
    //             "dob" => $user->dob,
    //             "email" => $user->email,
    //             "phone_no" => $user->phone_no,
    //             "locality" => $locality,
    //             "ward" => $ward->ward_no
    //         ];
            
    //         return response()->json([
    //             'status' => 'success',
    //             'access_token' => $token,
    //             'role' => $user->role,
    //             'message' => 'Login successfull',
    //             'user_details' => $user_details
    //         ], 200);
    //     } catch (MunicipalBoardException $exception) {
    //         Log::error('Invalid User');
    //         return $exception->message();
    //     }
    // }

    public function logout() {
        // Check if the token is valid
        try {
            // Attempt to authenticate the user using the token
            if (auth()->check()) {
                auth()->logout();
                return response()->json([
                    'status' => 'success',
                    'message' => 'User successfully signed out'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token is invalid or expired'
                ], 401);
            }
        } catch (\Exception $e) {
            // Handle the exception if token is expired or invalid
            return response()->json([
                'status' => 'error',
                'message' => 'Token is invalid or expired'
            ], 401);
        }
    }
    
    /**
     * It takes a POST request and requires fields for the user to profile update,
     * and validates them if it is validated,creates those values in DB
     * and returns success response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profileUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'firstname' => 'required|string|between:2,50',
                'lastname' => 'required|string|between:2,50',
                'dob' => 'required|date_format:Y-m-d',
                'phone_no' => 'required|string|min:10',
                'email' => 'required|string|email|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }

            $userDetails = User::where('email',$request->email)->first();

            if (empty($userDetails)) {
                throw new MunicipalBoardException("The email not found.", 401);
            }
            
            $userDetails->firstname = $request->firstname;
            $userDetails->lastname = $request->lastname;
            $userDetails->dob = $request->dob;
            $userDetails->phone_no = $request->phone_no;
            $userDetails->save();

            Log::info('Profile updated for user Email : ' . 'Email Id :' . $request->email);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile Successfully updated.',
            ], 201);

        } catch (MunicipalBoardException $exception) {
            Log::error('Invalid User');
            return $exception->message();
        }
    }
    
    /**
     * It takes a POST request and requires fields for the user to change Password,
     * and validates them if it is validated,creates those values in DB
     * and returns success response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string|min:6',
                'password' => 'required|string|min:6',
                'confirm_password' => 'required|same:password',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new MunicipalBoardException("Invalid authorization token", 401);
            }

            $userDetails = User::where('email',$user->email)->first();

            if (empty($user)) {
                throw new MunicipalBoardException("The email not found.", 401);
            }
            
            // Check if the current password matches the password in the database
            if (!Hash::check($request->current_password, $userDetails->password)) {
                return response()->json(['error' => 'Current password does not match.'], 400);
            }
            
            $userDetails->password = bcrypt($request->password);
            $userDetails->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Password Successfully updated.',
            ], 201);

        } catch (MunicipalBoardException $exception) {
            Log::error('Invalid User');
            return $exception->message();
        }
    }
    
    public function verifyEmail($token){
        $user = JWTAuth::parseToken()->authenticate($token);
        $user = User::where('email', $user->email)->first();
        if(!$user){
            return response()->json([
                'message' => "Not a Registered Email"
            ], 404);
        }elseif($user->verifyemail === 'inactive'){
            $user->verifyemail = 'active';
            $user->save();
            return response()->json([
                'message' => "Email is Successfully verified"
            ],201);
        }else{
            return response()->json([
                'message' => "Email Already verified"
            ],202);
        }
    }

    public function sendMail($token,$email){
        Log::info('Starting email sending process', [
            'email' => $email,
            'token_length' => strlen($token)
        ]);
        
        $url = url("/api/verifyEmail/" . $token);
        // Initialize PHPMailer
        $mail = new PHPMailer(true);

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
            
            Log::info('SMTP Configuration loaded', [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'encryption' => config('mail.mailers.smtp.encryption')
            ]);

            // Sender and recipient
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($email); // Add recipient email
            
            // Subject
            $mail->Subject = 'Account Verification Required - Tura Municipal Board | Government of Meghalaya'; // Set the subject line            // Email content
            $mail->isHTML(true); // Set email format to HTML
            // HTML body content - Professional Registration Template
            $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='utf-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Email Verification</title>
                </head>
                <body style='margin: 0; padding: 0; font-family: \"Times New Roman\", Times, serif; background-color: #f8f9fa; line-height: 1.6;'>
                    <div style='max-width: 700px; margin: 0 auto; background-color: #ffffff; border: 2px solid #1a365d; box-shadow: 0 4px 20px rgba(0,0,0,0.15);'>
                        <!-- Official Header -->
                        <div style='background: linear-gradient(135deg, #1a365d 0%, #2d5a87 100%); padding: 30px; text-align: center; position: relative;'>
                            <!-- Government Emblem Pattern -->
                            <div style='position: absolute; top: 10px; left: 20px; color: rgba(255,255,255,0.1); font-size: 40px;'>⚖️</div>
                            <div style='position: absolute; top: 10px; right: 20px; color: rgba(255,255,255,0.1); font-size: 40px;'>🏛️</div>
                            
                            <!-- Logo -->
                            <div style='margin-bottom: 15px;'>
                                <img src='" . asset('images/email/logo.png') . "' alt='Tura Municipal Board Official Seal' style='max-width: 100px; height: auto; border: 3px solid #ffffff; border-radius: 50%; box-shadow: 0 6px 20px rgba(0,0,0,0.3);'>
                            </div>
                            
                            <!-- Official Header Text -->
                            <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.5); letter-spacing: 1px;'>
                                Tura MUNICIPAL BOARD
                            </h1>
                            <div style='border-bottom: 2px solid #ffffff; width: 60%; margin: 10px auto;'></div>
                            <p style='color: #e2e8f0; margin: 8px 0 5px 0; font-size: 16px; font-weight: 500;'>
                                Government of Meghalaya
                            </p>
                            <p style='color: #cbd5e0; margin: 0; font-size: 14px; font-style: italic;'>
                                Established: 12th September, 1979
                            </p>
                        </div>
                        
                        <!-- Official Notice Section -->
                        <div style='padding: 35px 40px;'>
                            <!-- Official Notice Header -->
                            <div style='text-align: center; margin-bottom: 30px; border-bottom: 3px double #1a365d; padding-bottom: 20px;'>
                                <div style='background: #1a365d; color: #ffffff; padding: 12px 25px; display: inline-block; border-radius: 25px; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(26, 54, 93, 0.3);'>
                                    <span style='font-size: 20px; margin-right: 8px;'>📋</span>
                                    <span style='font-weight: bold; font-size: 16px;'>OFFICIAL NOTICE</span>
                                </div>
                                <h2 style='color: #1a365d; margin: 0 0 8px 0; font-size: 24px; font-weight: bold;'>
                                    ACCOUNT VERIFICATION REQUIRED
                                </h2>
                                <p style='color: #2d5a87; font-size: 16px; margin: 0; font-weight: 500;'>
                                    Citizen Registration Portal - Email Confirmation
                                </p>
                            </div>                            <!-- Official Communication -->
                            <div style='background: #f7fafc; border: 2px solid #e2e8f0; border-left: 6px solid #1a365d; padding: 30px; margin: 25px 0; border-radius: 8px;'>
                                <!-- Official Reference -->
                                <div style='text-align: right; margin-bottom: 20px; color: #718096; font-size: 12px; font-family: monospace;'>
                                    <strong>Ref No:</strong> TMB/REG/" . date('Y') . "/" . strtoupper(substr(md5($email), 0, 6)) . "<br>
                                    <strong>Date:</strong> " . date('d/m/Y') . "
                                </div>
                                
                                <h3 style='color: #1a365d; margin: 0 0 20px 0; font-size: 16px; text-decoration: underline; font-weight: bold;'>
                                    Subject: Email Verification for Digital Citizen Services Portal
                                </h3>
                                
                                <p style='color: #2d3748; font-size: 15px; line-height: 1.7; margin: 0 0 18px 0;'>
                                    <strong>Dear Respected Citizen,</strong>
                                </p>
                                
                                <p style='color: #2d3748; font-size: 15px; line-height: 1.7; margin: 0 0 15px 0; text-align: justify;'>
                                    Warm greetings from <strong>Tura Municipal Board, Government of Meghalaya</strong>. We hereby acknowledge the receipt of your application for registration in our Digital Citizen Services Portal.
                                </p>
                                
                                <p style='color: #2d3748; font-size: 15px; line-height: 1.7; margin: 0 0 15px 0; text-align: justify;'>
                                    As per the established protocols and in compliance with digital security guidelines issued by the Government of Meghalaya, email verification is mandatory for all citizen accounts.
                                </p>
                                
                                <p style='color: #1a365d; font-size: 15px; line-height: 1.7; margin: 0; text-align: justify; font-weight: 500;'>
                                    You are requested to complete the verification process by clicking on the official verification link provided below:
                                </p>
                            </div>
                            
                            <!-- Official Verification Button -->
                            <div style='text-align: center; margin: 35px 0; padding: 25px; background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border-radius: 10px; border: 2px dashed #1a365d;'>
                                <p style='color: #1a365d; font-size: 14px; margin: 0 0 15px 0; font-weight: bold;'>
                                    ⚠️ OFFICIAL VERIFICATION REQUIRED
                                </p>
                                <a href='{$url}' style='display: inline-block; background: linear-gradient(135deg, #1a365d 0%, #2d5a87 100%); color: #ffffff; text-decoration: none; padding: 18px 40px; border-radius: 6px; font-weight: bold; font-size: 16px; box-shadow: 0 6px 20px rgba(26, 54, 93, 0.4); border: 2px solid #ffffff; text-transform: uppercase; letter-spacing: 0.5px;'>
                                    🏛️ VERIFY CITIZEN ACCOUNT
                                </a>
                                <p style='color: #718096; font-size: 12px; margin: 15px 0 0 0; font-style: italic;'>
                                    Click the above button to verify your account officially
                                </p>
                            </div>
                            
                            <!-- Benefits Section -->
                            <div style='background: #f0fff4; border: 2px solid #68d391; border-radius: 8px; padding: 25px; margin: 30px 0;'>
                                <h4 style='color: #065f46; margin: 0 0 15px 0; font-size: 18px;'>
                                    �️ Municipal Services Available After Verification:
                                </h4>
                                <ul style='color: #047857; line-height: 1.8; margin: 0; padding-left: 20px;'>
                                    <li><strong>Government Job Applications:</strong> Apply for municipal positions and government jobs</li>
                                    <li><strong>Application Status Tracking:</strong> Monitor your application progress in real-time</li>
                                    <li><strong>Official Notifications:</strong> Receive important municipal announcements and updates</li>
                                    <li><strong>Document Management:</strong> Secure access to upload and manage official documents</li>
                                    <li><strong>Municipal Services:</strong> Access various civic and administrative services</li>
                                </ul>
                            </div>
                            
                            <!-- Official Security Notice -->
                            <div style='background: #fff8dc; border: 2px solid #d69e2e; border-radius: 8px; padding: 25px; margin: 30px 0; border-left: 6px solid #d69e2e;'>
                                <h4 style='color: #744210; margin: 0 0 12px 0; font-size: 16px; font-weight: bold; text-align: center;'>
                                    ⚠️ IMPORTANT SECURITY ADVISORY
                                </h4>
                                <div style='background: #ffffff; padding: 15px; border-radius: 6px; border: 1px solid #d69e2e;'>
                                    <p style='color: #744210; margin: 0 0 10px 0; font-size: 14px; line-height: 1.6; text-align: justify;'>
                                        <strong>OFFICIAL NOTICE:</strong> This communication is issued by Tura Municipal Board, Government of Meghalaya. This verification process is mandatory as per digital governance protocols.
                                    </p>
                                    <p style='color: #744210; margin: 0; font-size: 13px; line-height: 1.5; text-align: justify;'>
                                        If you have not registered for this account, please ignore this email. This verification link expires in 24 hours for security compliance. For any queries, contact the municipal office during official hours.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div style='text-align: center; margin-top: 40px;'>
                                <div style='background: #f3f4f6; padding: 20px; border-radius: 10px;'>
                                    <p style='color: #6b7280; font-size: 14px; margin: 0 0 10px 0;'>
                                        Having trouble with verification?
                                    </p>
                                    <p style='color: #374151; font-weight: bold; margin: 0; font-size: 16px;'>
                                        📧 " . config('mail.from.address') . "
                                    </p>
                                    <p style='color: #6b7280; font-size: 12px; margin: 10px 0 0 0;'>
                                        We're here to help you get started!
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Alternative Verification Link -->
                            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;'>
                                <p style='color: #6b7280; font-size: 12px; margin: 0 0 10px 0;'>
                                    If the button above doesn't work, copy and paste this link in your browser:
                                </p>
                                <p style='word-break: break-all; background: #f9fafb; padding: 10px; border-radius: 5px; color: #374151; font-size: 12px; font-family: monospace; margin: 0;'>
                                    {$url}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Official Footer -->
                        <div style='background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); padding: 35px; text-align: center; border-top: 4px solid #d69e2e;'>
                            <!-- Government Seal Pattern -->
                            <div style='margin-bottom: 20px;'>
                                <div style='display: inline-block; background: rgba(255,255,255,0.1); padding: 15px 25px; border-radius: 25px; border: 2px solid rgba(255,255,255,0.2);'>
                                    <span style='color: #e2e8f0; font-size: 18px; font-weight: bold; letter-spacing: 1px;'>🏛️ GOVERNMENT OF MEGHALAYA</span>
                                </div>
                            </div>
                            
                            <h3 style='color: #e2e8f0; margin: 0 0 5px 0; font-size: 18px; font-weight: bold;'>
                                Tura MUNICIPAL BOARD
                            </h3>
                            <p style='color: #a0aec0; margin: 0 0 8px 0; font-size: 14px; font-style: italic;'>
                                Committed to Civic Excellence and Public Service
                            </p>
                            
                            <div style='margin: 20px 0; height: 1px; background: linear-gradient(90deg, transparent 0%, #4a5568 20%, #4a5568 80%, transparent 100%);'></div>
                            
                            <p style='color: #a0aec0; margin: 0 0 15px 0; font-size: 13px; line-height: 1.5;'>
                                This is an <strong>OFFICIAL COMMUNICATION</strong> from Tura Municipal Board.<br>
                                Please do not reply to this automated verification email.
                            </p>
                            
                            <div style='background: rgba(0,0,0,0.2); padding: 20px; border-radius: 8px; margin: 20px 0;'>
                                <p style='color: #cbd5e0; margin: 0 0 8px 0; font-size: 12px; font-family: monospace;'>
                                    © " . date('Y') . " Tura Municipal Board | Government of Meghalaya | All Rights Reserved
                                </p>
                                <p style='color: #a0aec0; margin: 0; font-size: 11px;'>
                                    Established: 12th September 1979 | Digital India Initiative
                                </p>
                            </div>
                            
                            <div style='margin-top: 15px;'>
                                <span style='color: #718096; font-size: 10px; font-style: italic;'>
                                    Powered by Digital Governance Solutions | Government of Meghalaya
                                </span>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // Attempt to send the email
            Log::info('About to send email', [
                'email' => $email,
                'from_address' => config('mail.from.address'),
                'subject' => $mail->Subject
            ]);
            
            if (!$mail->send()) {
                Log::error('Registration email sending failed', [
                    'email' => $email,
                    'error' => $mail->ErrorInfo,
                    'smtp_host' => config('mail.mailers.smtp.host'),
                    'smtp_port' => config('mail.mailers.smtp.port')
                ]);
                throw new \Exception('Email not sent. Error: ' . $mail->ErrorInfo);
            } else {
                Log::info('Registration email sent successfully', [
                    'email' => $email,
                    'token' => substr($token, 0, 10) . '...' // Log partial token for security
                ]);
                return true;
            }

        } catch (Exception $e) {
            // Catch any PHPMailer exceptions
            Log::error('Registration email exception', [
                'email' => $email,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new \Exception('Message could not be sent. Mailer Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Resend verification email for unverified users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $userObject = new User();
            $user = $userObject->userEmailValidation($request->email);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email not found. Please register first.'
                ], 404);
            }

            if ($user->verifyemail === 'active') {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Email is already verified. You can login now.'
                ], 200);
            }

            // Generate new token and send verification email
            $token = JWTAuth::fromUser($user);
            $emailSent = false;
            $emailError = null;
            
            try {
                $emailSent = $this->sendMail($token, $request->email);
            } catch (\Exception $e) {
                $emailError = $e->getMessage();
                Log::error('Resend verification email failed', [
                    'user_id' => $user->id,
                    'email' => $request->email,
                    'error' => $emailError
                ]);
            }
            
            Log::info('Resend verification email request', [
                'user_id' => $user->id,
                'email' => $request->email,
                'email_sent' => $emailSent ? 'Yes' : 'No'
            ]);

            $responseMessage = $emailSent 
                ? 'Verification email has been resent successfully.' 
                : 'Failed to resend verification email. Please try again later or contact support.';

            return response()->json([
                'status' => $emailSent ? 'success' : 'error',
                'message' => $responseMessage,
                'user_id' => $user->id,
                'email_sent' => $emailSent,
                'email_error' => $emailSent ? null : 'SMTP Authentication Failed - Contact Administrator'
            ], $emailSent ? 200 : 500);

        } catch (\Exception $e) {
            Log::error('Resend verification email exception', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test email configuration and sending
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testEmail(Request $request)
    {
        try {
            $email = $request->input('email', 'test@example.com');
            $testToken = 'test-token-' . time();
            
            // Test email sending
            $result = $this->sendMail($testToken, $email);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Test email sent successfully',
                'email' => $email,
                'email_sent' => $result
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email sending failed',
                'error' => $e->getMessage(),
                'email_config' => [
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'username' => config('mail.mailers.smtp.username'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name')
                ]
            ], 500);
        }
    }

    /**
     * Manually verify a user's email (for admin use during email issues)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function manualVerifyEmail(Request $request)
    {
        try {
            $email = $request->input('email');
            
            if (!$email) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email is required'
                ], 400);
            }
            
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found with this email'
                ], 404);
            }
            
            if ($user->verifyemail === 'active') {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Email is already verified'
                ], 200);
            }
            
            // Manually verify the email
            $user->verifyemail = 'active';
            $user->save();
            
            Log::info('Email manually verified by admin', [
                'user_id' => $user->id,
                'email' => $email,
                'admin_ip' => $request->ip()
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Email verified successfully',
                'user_id' => $user->id,
                'email' => $email
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Manual verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function commandRun()
    {
        // Run a command, e.g., cache clear
        Artisan::call('route:cache');
        
        // Optionally get the output of the command
        $output = Artisan::output();
        
        return response()->json([
            'status' => 'Command executed',
            'output' => $output
        ]);
    }
}

