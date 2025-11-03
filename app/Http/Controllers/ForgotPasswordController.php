<?php

namespace App\Http\Controllers;

use App\Exceptions\MunicipalBoardException;
use App\Models\User;
use App\Notifications\PasswordResetRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPMailer\PHPMailer\PHPMailer;
use Tymon\JWTAuth\Facades\JWTAuth;

class ForgotPasswordController extends Controller
{

    /**
     * This API Takes the request which is the email id and validates it and check where that email id
     * is present in DB or not if it is not,it returns failure with the appropriate response code and
     * checks for password reset model once the email is valid and by creating an object of the
     * sendEmail function which is there in App\Http\Requests\SendEmailRequest and calling the function
     * by passing args and successfully sending the password reset link to the specified email id.
     *
     * @return success reponse about reset link.
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $userObject = new User();
            $user = $userObject->userEmailValidation($request->email);
            if (!$user) {
                Log::error('Email not found.', ['id' => $request->email]);
                throw new MunicipalBoardException("we can not find a user with that email address", 404);
            }
            $token = JWTAuth::fromUser($user);
            if ($user) {
                Log::info('test');
                $res = $this->sendMail($token,$request->email);
                Log::info($res);
                Log::info('test');
            }
            Log::info('Forgot PassWord Link : ' . 'Email Id :' . $request->email);
            return response()->json([
                'status' => 200,
                'message' => 'we have mailed your password reset link to respective E-mail'
            ], 200);
        } catch (MunicipalBoardException $exception) {
            return $exception->message();
        }
    }
    
    // Show the reset password form
    public function showResetForm($token)
    {
        $user = JWTAuth::parseToken()->authenticate($token);
        $user = User::where('email', $user->email)->first();
        if (!$user) {
            return redirect('/')->with('error', 'Invalid token.');
        }
        
        return view('auth.reset', ['token' => $token]);
    }

     /**
     * This API Takes the request which has new password and confirm password and validates both of them
     * if validation fails returns failure resonse and if it passes it checks with DB whether the token
     * is there or not if not returns a failure response and checks the user email also if everything is
     * good resets the password successfully.
     *
     */
    public function resetPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'password' => 'min:6|required|',
            'password_confirmation' => 'required|same:password'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => "Password doesn't match"
            ], 400);
        }
        try {
            $currentUser = Auth::user();
            $userObject = new User();
            $user = $userObject->userEmailValidation($currentUser->email);
            if (!$user) {
                Log::error('Email not found.', ['id' => $request->email]);
                throw new MunicipalBoardException("we can not find a user with that email address", 404);
            } else {
                $user->password = bcrypt($request->password);
                $user->save();
                Log::info('Reset Successful : ' . 'Email Id :' . $request->email);
                return response()->json([
                    'status' => 201,
                    'message' => 'Password reset successfull!'
                ], 201);
            }
        } catch (MunicipalBoardException $exception) {
            return $exception->message();
        }
    }

    public function sendMail($token,$email){
        $url = url("/api/resetpassword/".$token);
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
            $mail->Subject = 'Password Reset - Tura Municiple Board'; // Set the subject line

            // Email content
            $mail->isHTML(true); // Set email format to HTML
            // HTML body content
             // HTML body content
            $mail->Body = "
                <h3>You are receiving this email because a password reset request was made for your account.</h3>
                <p>To reset your password, please click the link below:</p>
                <a href='{$url}'><strong>Reset Password</strong></a>
                <p>This link is valid for 12 hours. If you did not request a password reset, no further action is required.</p>
                <br>
                <p>Thank you!</p>
            ";

            // Attempt to send the email
            if (!$mail->send()) {
                 Log::info('test');
                return back()->with("error", "Email not sent. Error: " . $mail->ErrorInfo);
            } else {
                Log::info('test');
                return back()->with("success", "Email has been sent.");
            }

        } catch (Exception $e) {
            return $e;
            // Catch any PHPMailer exceptions
            return back()->with('error', 'Message could not be sent. Mailer Error: ' . $e->getMessage());
        }
    }
}
