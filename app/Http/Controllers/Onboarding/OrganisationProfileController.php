<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Customer;
use App\Models\Category;
use App\Models\CustomerBank;
use App\Models\CustomerCompany;
use App\Models\CustomerCompanyContact;
use App\Models\CustomerTradePrice;
use App\Models\Enquiry;
use App\Models\Product;

class OrganisationProfileController extends Controller
{
public function save(Request $request)
{
    // Validate request data
    $validator = Validator::make($request->all(), [
        'customer_code'     => 'required|string|max:50',
        'organisation'      => 'required|string|max:100',
        'country'           => 'required',
        'currency'          => 'required',
        'fiscal_month'      => 'required',
        'revenue_per_year'  => 'required',
        'date_format'       => 'required|string|max:20',
        'current_step'      => 'required',
    ]);

    // If validation fails, return errors
    if ($validator->fails()) {
        return response()->json([
            'status'  => 'failed',
            'message' => 'Validation Error!',
            'errors'  => $validator->errors(),
        ], 422);
    }

    // Find customer by customer_code
    $customer = Customer::where('customer_code', $request->customer_code)->first();

    // If customer not found, return error
    if (!$customer) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Customer not found',
        ], 404);
    }

    // Prepare update data
    $data = [
        'organisation'      => $request->organisation,
        'country'           => $request->country,
        'currency'          => $request->currency,
        'fiscal_month'      => $request->fiscal_month,
        'revenue_per_year'  => $request->revenue_per_year,
        'date_format'       => $request->date_format,
        'upc_digit'         => $request->upc_digit,
    ];

    // Update customer details
    $customer->update($data);

    // Update current_step if increased
    $currentStep = $request->current_step;
    $existingStep = $customer->current_step;
    $customer_id = $customer->id;

    if ($currentStep != $existingStep && $currentStep > $existingStep) {
        $customer->update(['current_step' => $currentStep]);

         createonboardingProgres($customer_id,1);
    }

    // Return success response
    return response()->json([
        'status'       => 'success',
        'message'      => 'Customer updated successfully',
        'current_step' => $currentStep,
    ], 200);
}

    
   
}


