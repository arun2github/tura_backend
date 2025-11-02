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
                // 'role' => 'required|string|between:2,10',
                'firstname' => 'required|string|between:2,50',
                'lastname' => 'required|string|between:2,50',
                'ward_id' => 'required',
                'locality' => 'required',
                'dob' => 'required|date_format:Y-m-d',
                'phone_no' => 'required|string|min:10',
                'email' => 'required|string|email|max:100',
                'password' => 'required|string|min:6',
                'confirm_password' => 'required|same:password',
            ]);
            $userArray = array(
                // 'role' => $request->role,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'ward_id' => $request->ward_id,
                'locality' => $request->locality,
                'dob' => $request->dob,
                'phone_no' => $request->phone_no,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            );

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $userObject = new User();
            $user = $userObject->userEmailValidation($request->email);

            if ($user) {
                throw new MunicipalBoardException("The email has already been taken", 401);
            }

            $userDetail = $userObject->saveUserDetails($userArray);

            $token = JWTAuth::fromUser($userDetail);
            if($userDetail) {
                $this->sendMail($token,$request->email);
            }

            Log::channel('customLog')->info('Registered user Email : ' . 'Email Id :' . $request->email);
            Log::info('Registered user Email : ' . 'Email Id :' . $request->email);

            return response()->json([
                'status' => 'success',
                'message' => 'User Successfully Registered',
            ], 201);

        } catch (MunicipalBoardException $exception) {
            Log::error('Invalid User');
            return $exception->message();
        }
    }

    /**
     * Takes the POST request and user credentials checks if it correct,
     * if so, returns JWT access token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $userObject = new User();
            $user = $userObject->userEmailValidation($request->email);
            if (!$user) {
                Log::error('User failed to login.', ['id' => $request->email]);
                throw new MunicipalBoardException("we can not find the user with that e-mail address You need to register first", 401);
            }

            if (!$token = auth()->attempt($validator->validated())) {
                throw new MunicipalBoardException("Invalid Credentials", 401);
            }
            
            if($user->verifyemail === 'inactive') {
                $token = Auth::fromUser($user);

                $this->sendMail($token,$request->email);

                return  response()->json([
                    'status' => 211,
                    'message' => 'Email Not verified'
                ],211);
            }

            Log::info('Login Success : ' . 'Email Id :' . $request->email);
            $locality = LocalityModel::select('locality','id','ward_id','status')->where('id',$user->locality)->first();
            $ward = WardList::select('ward_no')->where('id',$user->ward_id)->first();

            $user_details = [
                "firstname" => $user->firstname,
                "lastname" => $user->lastname,
                "dob" => $user->dob,
                "email" => $user->email,
                "phone_no" => $user->phone_no,
                "locality" => $locality,
                "ward" => $ward->ward_no
            ];
            
            return response()->json([
                'status' => 'success',
                'access_token' => $token,
                'role' => $user->role,
                'message' => 'Login successfull',
                'user_details' => $user_details
            ], 200);
        } catch (MunicipalBoardException $exception) {
            Log::error('Invalid User');
            return $exception->message();
        }
    }

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

            Log::channel('customLog')->info('Registered user Email : ' . 'Email Id :' . $request->email);

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

            // Sender and recipient
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($email); // Add recipient email
            
            // Subject
            $mail->Subject = 'Email Verification - TURA Municiple Board'; // Set the subject line

            // Email content
            $mail->isHTML(true); // Set email format to HTML
            // HTML body content
            $mail->Body = "
                <h3>Welcome to TURA Municipal Board.</h3>
                <p>Please verify your email to get started with us by clicking the link below:</p>
                <a href='{$url}'><strong>Verification of Email</strong></a>
                <br><br>
                <p>Thank you for registering!</p>
            ";

            // Attempt to send the email
            if (!$mail->send()) {
                return back()->with("error", "Email not sent. Error: " . $mail->ErrorInfo);
            } else {
                return back()->with("success", "Email has been sent.");
            }

        } catch (Exception $e) {
            // Catch any PHPMailer exceptions
            return back()->with('error', 'Message could not be sent. Mailer Error: ' . $e->getMessage());
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

