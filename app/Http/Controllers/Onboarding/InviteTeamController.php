<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\Onboarding\Auth\WelcomeMail;
use App\Mail\Onboarding\Auth\ResetPasswordMail;
use App\Models\DepartmentComp;
class InviteTeamController extends Controller
{ 
   public function saveInviteTeam(Request $request)
{
    $validator = Validator::make($request->all(), [
        'customer_code' => 'required|string|max:50',
        'name' => 'required|string|max:250',
        'email' => 'required|string|email|max:155',
        'department' => 'required|string|max:250',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Validation Error!',
            'data' => $validator->errors(),
        ], 403);
    }

    // Split name into first and last name
    $nameParts = explode(' ', $request->name, 2);
    $adminFname = $nameParts[0] ?? '';
    $adminLname = $nameParts[1] ?? '';

    // Find user by email
    $user = User::where('admin_email', $request->email)->first();

    if ($user) {
        // Update existing user
        $user->update([
            'admin_fname' => $adminFname,
            'admin_lname' => $adminLname,
            'admin_role_id' => 5,
            'admin_designation' => $request->department,
            'remember_token' => Str::random(60),
        ]);
    } else {
        // Create new user
        $user = User::create([
            'admin_fname' => $adminFname,
            'admin_lname' => $adminLname,
            'admin_email' => $request->email,
            'admin_role_id' => 5,
            'admin_designation' => $request->department,
            'remember_token' => Str::random(60),
        ]);
    } 

    if ($user) {
        $token = $user->remember_token;
        $setupPasswordUrl = getWeb()->web_url . "/setuppassword?tok=$token";

        Mail::to($request->email)->send(new WelcomeMail($user, $setupPasswordUrl));


          
                // Find customer by customer_code
    $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();

                 $currentStep = 10;
    $existingStep = $customer->current_step;
    $customer_id = $customer->id;

                      if ($currentStep != $existingStep && $currentStep > $existingStep) {
        $customer->update(['current_step' => $currentStep]);
    }
    if ($currentStep != $existingStep){
   createonboardingProgres($customer_id,10);
    }
      



        return response()->json([
            'status' => 'success',
            'message' => 'Email successfully sent.',
            'setupPasswordUrl' => $setupPasswordUrl,
        ], 201);
    }

    // In case something went wrong
    return response()->json([
        'status' => 'failed',
        'message' => 'Unable to save user and send email.',
    ], 500);
}

}
