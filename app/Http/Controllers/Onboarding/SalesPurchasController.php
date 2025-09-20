<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Customer;
use App\Models\Category;
use App\Models\CustomerBank;
use App\Models\CustomerCategory;
use App\Models\CustomerCompany;
use App\Models\CustomerCompanyContact;
use App\Models\CustomerProduct;
use App\Models\CustomerTradePrice;
use App\Models\Enquiry;
use App\Models\Product;

class SalesPurchasController extends Controller
{
   
   public function save(Request $request)
{
    // Validate the main customer fields
    $validator = Validator::make($request->all(), [
        'customer_code'          => 'required|string|max:50',
        'company_address'        => 'required|string|max:200',
        'company_address_2'      => 'required|string|max:200',
        'company_country'        => 'required|integer',
        'company_state'          => 'required|integer',
        'company_city'           => 'required|integer',
        'company_zipcode'        => 'required|string|max:100',
        'purchase_address'       => 'required|string|max:200',
        'purchase_address_2'     => 'required|string|max:200',
        'purchase_country'       => 'required|integer',
        'purchase_state'         => 'required|integer',
        'purchase_city'          => 'required|integer',
        'purchase_zipcode'       => 'required|string|max:100',
        'gst_number'             => 'required|string|max:100',
        'sales_offer_format'     => 'required|integer|max:100',
        'purchase_order_format'  => 'required|integer|max:100',
        'banks'                  => 'required|array|min:1',
        'banks.*.bank_name'           => 'required|string|max:100',
        'banks.*.account_holder_name' => 'required|string|max:100',
        'banks.*.ifsc_code'           => 'required|string|max:20',
        'banks.*.account_number'      => 'required|string|max:50|distinct|unique:customer_banks,account_number',
        'banks.*.bank_address'        => 'required|string|max:200',
        'banks.*.swift_number'        => 'nullable|string|max:50',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => 'failed',
            'message' => 'Validation Error!',
            'errors'  => $validator->errors(),
        ], 422);
    }

    // Find customer by customer_code
    $customer = Customer::where('customer_code', $request->customer_code)->first();

    if (!$customer) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Customer not found'
        ], 404);
    }

    // Update customer details
    $customer->update([
        'customer_code'         => $request->customer_code,
        'company_address'       => $request->company_address,
        'company_address_2'     => $request->company_address_2,
        'company_country'       => $request->company_country,
        'company_state'         => $request->company_state,
        'company_city'          => $request->company_city,
        'company_zipcode'       => $request->company_zipcode,
        'purchase_address'      => $request->purchase_address,
        'purchase_address_2'    => $request->purchase_address_2,
        'purchase_country'      => $request->purchase_country,
        'purchase_state'        => $request->purchase_state,
        'purchase_city'         => $request->purchase_city,
        'purchase_zipcode'      => $request->purchase_zipcode,
        'gst_number'            => $request->gst_number,
        'sales_offer_format'    => $request->sales_offer_format,
        'purchase_order_format' => $request->purchase_order_format
    ]);

    // Prepare and insert unique bank data
    foreach ($request->banks as $bank) {
        CustomerBank::firstOrCreate([
            'account_number' => $bank['account_number'],
        ], [
            'customer_code'        => $request->customer_code,
            'bank_name'            => $bank['bank_name'],
            'account_holder_name'  => $bank['account_holder_name'],
            'ifsc_code'            => $bank['ifsc_code'],
            'swift_number'         => $bank['swift_number'] ?? null,
            'bank_address'         => $bank['bank_address']
        ]);
    }
  $currentStep = 2;
    $existingStep = $customer->current_step;
    $customer_id = $customer->id;

    if ($currentStep != $existingStep && $currentStep > $existingStep) {
        $customer->update(['current_step' => $currentStep]);

      
    }
    if ($currentStep != $existingStep){
   createonboardingProgres($customer_id,2);
    }
    return response()->json([
        'status'  => 'success',
        'message' => 'Customer and bank details updated successfully',
    ], 200);
}

public function saveLogo(Request $request)
{
    // Validate input
    $validator = Validator::make($request->all(), [
        'customer_code' => 'required|string|max:50',
        'company_logo'  => 'required|string', // Accepting Base64 string
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => 'failed',
            'message' => 'Validation Error!',
            'errors'  => $validator->errors(),
        ], 422);
    }

    // Find customer by customer_code
    $customer = Customer::where('customer_code', $request->customer_code)->first();

    if (!$customer) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Customer not found'
        ], 404);
    }

    // Decode Base64 image
    $image = $request->company_logo;
    $image = str_replace('data:image/png;base64,', '', $image);
    $image = str_replace('data:image/jpeg;base64,', '', $image);
    $image = str_replace(' ', '+', $image);
    $imageData = base64_decode($image);

    // Generate unique file name
    $imageName = time() . '_' . uniqid() . '.png';
    $imagePath = 'uploads/Onboarding/logo/';

    // Save image in public directory
    file_put_contents(public_path('uploads/Onboarding/logo/') . $imageName, $imageData);

    // Save image path in database
    $customer->update([
        'company_logo' => $imagePath . $imageName
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Company logo uploaded successfully',
        'company_logo' => url('uploads/Onboarding/logo/' . $imageName) // Full URL for frontend
    ], 200);
}

    }
    

