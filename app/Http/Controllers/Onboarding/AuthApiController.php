<?php

namespace App\Http\Controllers\Onboarding;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\Onboarding\Auth\WelcomeMail;
use App\Mail\Onboarding\Auth\VerificationCodeMail;
use App\Models\Customer;
 
use App\Mail\Onboarding\Auth\OtpMail;
use Carbon\Carbon;
 
class AuthApiController extends Controller
{
    public function register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:250',
            'email' => 'required|string|email|max:155|unique:tbl_admin,admin_email',
         'organisation'     => 'required|string|max:100', 
            'country'          => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validate->errors(),
            ], 403);
        }

        $nameParts = explode(" ", $request->name);
        $adminFname = $nameParts[0] ?? '';
        $adminLname = $nameParts[1] ?? '';
        $customer_code = codeCustomer();
        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'customer_code' => $customer_code,
            'organisation'  => $request->organisation,
            'country'       => $request->country,
        ]);
$token =  Str::random(60);
        $user = User::create([
            'admin_fname' => $adminFname,
            'admin_lname' => $adminLname,
            'admin_email' => $request->email,
            "confirmed" => 3,
            "remember_token" => $token,
            'customer_code' => $customer_code
        ]);
        $setupPasswordUrl = getWeb()->web_url."/setuppassword?tok=$token";
        Mail::to($request->email)->send(new WelcomeMail($user, $setupPasswordUrl));
        
        return response()->json([
            'status' => 'success',
            'message' => 'User is created successfully.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

public function setPasswordEmailLink(Request $request)
{
    try {
        // Validate email
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:155',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Validation Error!',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Check if user exists
        $user = User::where('admin_email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'No user found with this email address.'
            ], 404);
        }

        // Ensure token exists or generate one
        if (empty($user->remember_token)) {
            $user->remember_token = Str::random(60);
            $user->save();
        }

        $setupPasswordUrl = getWeb()->web_url . "/setuppassword?tok={$user->remember_token}";

        // Send email
        Mail::to($user->admin_email)->send(new WelcomeMail($user, $setupPasswordUrl));

        return response()->json([
            'status'            => 'success',
            'message'           => 'Password setup email sent successfully.',
            'setupPasswordUrl'  => $setupPasswordUrl
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while sending the email.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

// Reset Password
public function resetPassword(Request $request)
{
    $validate = Validator::make($request->all(), [
        'token' => 'required',
        'password' => 'required|string|min:6|confirmed'
    ]);

    if ($validate->fails()) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Validation Error!',
            'data' => $validate->errors(),
        ], 403);
    }

    $user = User::where('remember_token', $request->token)->first();

    if (!$user) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Invalid credentials',
        ], 401);
    }

    // Generate new tokens
    $rememberToken = Str::random(60);


    // Update user data
    $user->update([
        'admin_password' => $request->password,
        'password' => Hash::make($request->password),
   
        'remember_token' => $rememberToken,
        'email_verified_at' => now(),
       
    ]);
 
    return response()->json([
        'status' => 'success',
        'message' => 'Password successfully reset.',
        'token' => $rememberToken,
     
    ], 201);
}
public function login(Request $request)
{
    // Validate input
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ]);

    // Find user by email
    $user = User::where('admin_email', $request->email)->first();

    // Check if user exists
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Invalid credentials',
        ], 401);
    }

    // Generate token & OTP
    $rememberToken = Str::random(60);
    $verificationCode = rand(100000, 999999);

    // Update user with new token & OTP
    $user->update([
        'remember_token' => $rememberToken,
        'otp' => $verificationCode
    ]);

    $type = "Login";
// Send verification email
Mail::to($user->admin_email)->send(new VerificationCodeMail($verificationCode, $type));

    // Return response
    return response()->json([
        'status' => 'success',
        'message' => 'OTP successfully sent.',
        'token' => $rememberToken,
               'id' => $user->admin_id,
'customer_code' => $user->customer_code,
        'name' => $user->admin_fname . ' ' . $user->admin_lname,
        'email' => $user->admin_email,
    ], 200);
}
// Reset OTP
public function resetOtp(Request $request)
{
    $validate = Validator::make($request->all(), [
        'token' => 'required',
        'type'  => 'required',
    ]);

    if ($validate->fails()) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Validation Error!',
            'data' => $validate->errors(),
        ], 403);
    }

    $user = User::where('remember_token', $request->token)->first();

    if (!$user) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Invalid credentials',
        ], 401);
    }

    // Generate new tokens
    $rememberToken = Str::random(60);
    $verificationCode = rand(100000, 999999);

    // Update user data
    $user->update([
        'remember_token' => $rememberToken,
        'otp' => $verificationCode
    ]);

    $type = $request->type;
    // Send verification email
    Mail::to($user->admin_email)->send(new VerificationCodeMail($verificationCode ,$type));


    return response()->json([
        'status' => 'success',
        'message' => 'OTP successfully Send in Your Mail.',
        'token' => $rememberToken
    ], 201);
}

// Verify OTP
public function verifyOtp(Request $request)
{
    $validate = Validator::make($request->all(), [
        'token' => 'required',
        'otp' => 'required'
    ]);

    if ($validate->fails()) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Validation Error!',
            'data' => $validate->errors(),
        ], 403);
    }

    $user = User::where('remember_token', $request->token)
                ->where('otp', $request->otp)
                ->first();

    if (!$user) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Invalid credentials',
        ], 401);
    }

    // Generate new token
    $rememberToken = Str::random(60);

    // Update user token
    $user->update([
        'remember_token' => $rememberToken,
       'last_active_at' => Carbon::now(),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'OTP successfully verified.',
        'token' => $rememberToken
    ], 201);
}

  
     

    public function forgotPassword(Request $request)
    {
        // Validate request input
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:155',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Find user by email
        $user = User::where('admin_email', $request->email)->first();
    
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User not found!',
            ], 404);
        }
    
        try {
            // Generate token and OTP
            $rememberToken = Str::random(60);
            $verificationCode = rand(100000, 999999);
    
            // Update user record
            $user->update([
                'remember_token' => $rememberToken,
                'otp' => $verificationCode,
            ]);
    
            $type = "Forgot Password";
            // Send verification email
            Mail::to($user->admin_email)->send(new VerificationCodeMail($verificationCode, $type));
            
            return response()->json([
                'status' => 'success',
                'message' => 'OTP successfully sent.',
                'token' => $rememberToken,
             
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    
       
    } 

    public function logout(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        if ($user) {
            // Revoke token
            $request->user()->tokens()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully.'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'User not authenticated.'
        ], 401);
    }
 

}
 