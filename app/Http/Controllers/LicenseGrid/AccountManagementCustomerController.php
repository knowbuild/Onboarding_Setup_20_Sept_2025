<?php

namespace App\Http\Controllers\LicenseGrid;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\License;
use App\Models\WebEnq;
use App\Models\WebEnqEdit;
use App\Models\ProductMain;
use App\Models\EnqSource;
use App\Models\AdminRoleType;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceMaster;

class AccountManagementCustomerController extends Controller
{


public function accountInformation(Request $request)
{
    $users = Auth::guard('api')->user(); 
    $tenant_id = $users ? $users->tenant_id : 1;

    $user_type = 'user';
    if ($users && $users->user_type == 'Admin') {
        $user_type = 'admin';
    }
    $customer = Customer::with([
        'licenseRelation.durationService',
        'accountManagers',
        'countryRelation',
        'companyState',
        'companyCity',
        'contacts.designation',
    ])->find($tenant_id);

    if (!$customer) {
        return response()->json(['error' => 'Customer not found.'], 404);
    }

    $latestLicense = $customer->licenseRelation()
        ->whereIn('account_status', ['approved', 'expired'])
        ->with('durationService')
        ->orderByDesc('id')
        ->first();


    $licenseCount = $customer->licenseRelation->sum('licenses');

    $personalDetails = [
        'customer_id'        => $customer->customer_code,
        'admin_email_id'     => $customer->email,
        'company_name'       => $customer->organisation,
        'country'            => optional($customer->countryRelation)->country_name,
        'registered_contact' => $customer->name,
        'registered_mobile'  => $customer->mobile,
        'payment_plan'       => optional($latestLicense->durationService)->service_name ?? null,
        'licenses_issued'    => $licenseCount,
    'date_of_joining'   => optional($customer->created_at)->format('Y-m-d'),
    'last_invoice_date' => optional($latestLicense?->created_at)->format('Y-m-d'),
    'next_payment_date'  => $latestLicense?->licenses_end_date ?? null,
    'vat_gst'            => $customer->gst_number,
    'vat_gst_verified'   => !empty($customer->gst_number),
];

  
    $otherDetails = [
        'purchase_address' => $customer->purchase_address ?? null,
        'country'          => optional($customer->countryRelation)->country_name,
        'state'            => $customer->companyState->zone_name ?? null,
        'city'             => $customer->companyCity->city_name ?? null,
        'zip_code'         => $customer->company_zipcode ?? null,
    ];


    // Final response
    $data = [
        'personal_details' => $personalDetails,
        'other_details'    => $otherDetails,
        'secondary_contacts' => $customer->contacts,
         'user_type' => $user_type,
    ];

    return response()->json([
        'status'  => 'success',
        'message' => 'Customer details retrieved successfully.',
        'data'    => $data,
       
        'tenant_id' => $tenant_id,
    ]);
}




    public function updateCustomerContacts(Request $request)
    {
        $user = Auth::guard('api')->user();
        $tenant_id = $user ? $user->tenant_id : 1;

        $validator = Validator::make($request->all(), [
            'contacts'   => 'required|array|min:1',
            'contacts.*.name'       => 'required|string|max:100',
            'contacts.*.email'      => 'nullable|email|max:100',
            'contacts.*.mobile_code'=> 'nullable|string|max:5',
            'contacts.*.mobile'     => 'required|string|max:20',
            'contacts.*.function'   => 'required|string|max:100',
            'contacts.*.user_type'  => 'required|string|max:100',
            'contacts.*.status'     => 'required|in:Approved,Pending',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        // Delete existing contacts for tenant
        CustomerContact::where('tenant_id', $tenant_id)->delete();

        $createdContacts = [];
        foreach ($request->contacts as $contactData) {
            $createdContacts[] = CustomerContact::create([
                'name'        => $contactData['name'],
                'email'       => $contactData['email'] ?? null,
                'mobile_code' => $contactData['mobile_code'] ?? '+91',
                'mobile'      => $contactData['mobile'],
                'function'    => $contactData['function'],
                'user_type'   => $contactData['user_type'],
                'status'      => $contactData['status'],
                'tenant_id'   => $tenant_id,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Support contacts updated successfully.',
           
        ], 200);
    }

    public function destroyCustomerContacts(Request $request)
    {
        $user = Auth::guard('api')->user();
        $tenant_id = $user ? $user->tenant_id : 1;

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:customer_contacts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $contact = CustomerContact::where('id', $request->id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$contact) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Support contact not found.'
            ], 404);
        }

        $contact->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Support contact deleted successfully.'
        ], 200);
    }
 
    public function accessLevelManagement()       
{
    $data = [
        [
            'id'   => 1,
            'name' => 'IT Support'
        ],
        [
            'id'   => 2,
            'name' => 'Billing Support'
        ]
    ];

    return response()->json([
        'status'  => 'success',
        'message' => 'User Type (Access Level) retrieved successfully.',
        'data'    => $data,
    ], 200);
}
   public function functionList()       
{
    $data = [
        [
            'id'   => 1,
            'name' => 'Admin'
        ],
        [
            'id'   => 2,
            'name' => 'Support'
        ],
         [
            'id'   => 3,
            'name' => 'User'
        ]
    ];

    return response()->json([
        'status'  => 'success',
        'message' => 'Function retrieved successfully.',
        'data'    => $data,
    ], 200);
}

public function licenseManagement(Request $request)
{
     $validator = Validator::make($request->all(), [
       'status'    => 'required|in:active,inactive',
 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
 
       $users = Auth::guard('api')->user(); 
    $tenant_id = $users ? $users->tenant_id : 1;

    $customer = Customer::with([
        'countryRelation',
        'customerUsers.businessFunction',
    ])->first();

    if (!$customer) {
        return response()->json(['error' => 'Customer not found.'], 404);
    }


    $licenseUsers = $customer->customerUsers->where('admin_status', $request->status)->map(function ($user) {
        return [
            'id'          => $user->admin_id,
            'name'        => trim("{$user->admin_fname} {$user->admin_lname}"),
            'email'       => $user->admin_email,
            'role'  => optional($user->businessFunction)->name,
            'function' => $user->user_type, // 'Admin','User','Support'
            'status'      => strtoupper($user->admin_status), // Active, Inactive
        ];
    })->values();

   $licenseCount = $customer->licenseRelation->sum('licenses');
    $licenseUsersCountActive = $customer->customerUsers->where('admin_status', 'active')->count();
    $licenseUsersCountInactive = $customer->customerUsers->where('admin_status', 'inactive')->count();
    $remaining_licenses = $licenseCount - $licenseUsersCountActive;
    if($remaining_licenses < 0){
        $remaining_licenses = 0;
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'Customer details retrieved successfully.',
        'data'    => $licenseUsers,
        'remaining_licenses' => $remaining_licenses,
        'license_users_count_active' => $licenseUsersCountActive,
        'license_users_count_inactive' => $licenseUsersCountInactive,
        'tenant_id' => $tenant_id,

    ]);
}


 public function updateFunctionLicenseUser(Request $request)
    {
   $user = Auth::guard('api')->user();
        $tenant_id = $user ? $user->tenant_id : 1;

        $validator = Validator::make($request->all(), [
            'customer_users_id' => 'required|exists:tbl_admin,admin_id',
            'function'    => 'required|in:User,Support',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $customerUser = User::where('admin_id', $request->customer_users_id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$customerUser) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'User not found.'
            ], 404);
        }
        if($request->function == 'Support'){
            $isSupportExists = User::where('tenant_id', $tenant_id)->where('user_type', 'Support')
            ->where('admin_status', 'active')->count();
            if($isSupportExists >= 2){
            return response()->json([
                'data' => [
                    'status'  => 'failed',
                    'message' => 'Only two active support users are allowed.'
                ],
            ], 200);
            }
        }
        $customerUser->update([
            'user_type' => $request->function,
        ]);
      

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer user account status updated successfully.',
        ]);
    }

 public function removeRestoreLicenseUser(Request $request)
    {
   $user = Auth::guard('api')->user();
        $tenant_id = $user ? $user->tenant_id : 1;

        $validator = Validator::make($request->all(), [
                   'customer_users_id' => 'required|exists:tbl_admin,admin_id',
              'type' => 'required|in:remove,restore'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $customerUser = User::where('admin_id', $request->customer_users_id)
            ->where('tenant_id', $tenant_id)
            ->first();

        if (!$customerUser) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'User not found.'
            ], 404);
        }
if($request->type == 'restore'){
    $customer = Customer::with([ 'countryRelation', 'customerUsers.businessFunction'])->first();

       $licenseCount = $customer->licenseRelation->sum('licenses');
    $licenseUsersCountActive = $customer->customerUsers->where('admin_status', 'active')->count();
    $remaining_licenses = $licenseCount - $licenseUsersCountActive;

    if($remaining_licenses <= 0){
            return response()->json([
                'status'  => 'failed',
                'message' => 'No remaining licenses available to restore the user.',
            ], 400);
        }

    $status = 'active';
}
if($request->type == 'remove'){
       $status = 'inactive';
}
        $customerUser->update([
            'admin_status' => $status,
            'deleteflag' => $status,
        ]);
      

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer user account status updated successfully.',
        ]);
    }


     public function editLicenseUser(Request $request)
{
    try {
        // Get logged-in user and tenant_id (default to 1 if not found)
        $user = Auth::guard('api')->user();
        $tenantId = $user ? $user->tenant_id : 1;

        // Validate request
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_admin,admin_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Validation Error!',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Find customer user under same tenant
        $customerUser = User::where('admin_id', $request->id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$customerUser) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'User not found for this tenant.'
            ], 404);
        }
    $responseData = [
            "id"       => $customerUser->admin_id,
            "first_name"     => $customerUser->admin_fname,
            "last_name"     => $customerUser->admin_lname,
            "email"    => $customerUser->admin_email,
            "role"     => $customerUser->admin_role_id,
            "function" => $customerUser->user_type,
            "status"   => $customerUser->admin_status,
        ];
        // Return success response with user details
        return response()->json([
            'status'  => 'success',
            'message' => 'Customer user details retrieved successfully.',
            'data'    => $responseData
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

            public function updateOrStoreLicenseUser(Request $request)
{
    try {
        // Authenticated tenant
        $user = Auth::guard('api')->user();
        $tenantId = $user ? $user->tenant_id : 1;

        // Validation rules
        $validator = Validator::make($request->all(), [
            'id'   => 'nullable|exists:tbl_admin,admin_id', // required if updating
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|max:100',
            'role'       => 'required|max:11',   // e.g. Super Admin, Manager
            'function'   => 'required|in:User,Support',  // predefined set
            'status'     => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Validation Error!',
                'errors'  => $validator->errors()
            ], 422);
        }

        // If admin_id is passed ? update, else create new
        $customerUser = User::updateOrCreate(
            [
                'admin_id'  => $request->id,
                'tenant_id' => $tenantId,
            ],
            [
                'admin_fname'   => $request->first_name,
                'admin_lname'   => $request->last_name,
                'admin_email'   => $request->email,
                'admin_role_id' => $request->role,       // mapped to role
                'user_type'      => $request->function,   // mapped to function
                'admin_status'  => $request->status,     // active/inactive
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => $request->id ? 'Customer user updated successfully.' : 'Customer user created successfully.',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong.',
            'error'   => $e->getMessage()
        ], 500);
    }
}
public function roles(Request $request)
{
    $adminRoleType = AdminRoleType::active()->select('admin_role_id as id', 'admin_role_name as name')->get();

    return response()->json([
        'status'  => 'success',
        'message' => 'Roles retrieved successfully.',
        'data'    => $adminRoleType
    ], 200);
}
 
public function lastLicenseDetails(Request $request)
{
            // Get logged-in user and tenant_id (default to 1 if not found)
        $user = Auth::guard('api')->user();
        $tenantId = $user ? $user->tenant_id : 1;

    $customer = Customer::with([
        'accountManagers',
        'source',
        'countryRelation',
        'onboardingProgres.onboardingStep',
        'notesRelation.creator',
        'customerUsers.department',
        'customerUsers.designation',
        'reminderRelation',
    ])->find($tenantId);

    if (!$customer) {
        return response()->json(['error' => 'Customer not found.'], 404);
    }

    $latestLicense = $customer->licenseRelation()
        ->whereIn('account_status', ['approved', 'expired'])
        ->with('durationService')
        ->orderByDesc('id')
        ->first();



    $licenses = [];

    if ($latestLicense) {
$startDate = \Carbon\Carbon::parse($latestLicense->licenses_start_date);
$endDate = \Carbon\Carbon::parse($latestLicense->licenses_end_date);

// Calculate remaining days
$remaining_days = $startDate->diffInDays($endDate);
$remaining_day = $remaining_days < 0 ? 0 : $remaining_days;

// Calculate contract days
$contract_days = optional($latestLicense->durationService)->service_abbrv ? 
                 ((int) $latestLicense->durationService->service_abbrv * 30) : 0;

// Avoid division by zero
$pro_rated_licenseCost = 0;
if ($contract_days > 0) {
    $pro_rated_licenseCost = round($latestLicense->price * ($remaining_day / $contract_days), 2);
}

        $licenses[] = [
            'customer_id'         => $latestLicense->customer_id,
            'license_id'          => $latestLicense->id,
            'licenses'             => $latestLicense->licenses,
            'price'               => $latestLicense->price,
            'sub_total_price'     => $latestLicense->sub_total_price,
            'tax_price'           => $latestLicense->tax_price,
            'license_cost'        => $latestLicense->license_cost,
            'licenses_start_date' => $latestLicense->licenses_start_date,
            'licenses_end_date'   => $latestLicense->licenses_end_date,
            'duration'            => optional($latestLicense->durationService)->service_name ?: null,
            'duration_id'            => $latestLicense->duration,
            'licenses_code'       => $latestLicense->licenses_code,
            'licenses_type'       => $latestLicense->licenses_type,
            'licenses_status'     => $latestLicense->account_status,
            'remaining_days'      => $remaining_day,
            'pro_rated_licenseCost' => $pro_rated_licenseCost,
            'gst_percentage'=>18,
            'contract_days' => $contract_days,
              'product_id'     => $latestLicense->product_id,
        ];
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'Customer details retrieved successfully.',
        'data'    => $licenses,
    ]);
}

public function payment(Request $request)
{
    $users = Auth::guard('api')->user(); 
    $tenant_id = $users ? $users->tenant_id : 1;

    $customer = Customer::with([
        'licenseRelation.durationService',
        'licenseRelation.paymentRecives', // assuming you have an invoices relation
    ])->find($tenant_id);

    if (!$customer) {
        return response()->json(['error' => 'Customer not found.'], 404);
    }

    // Get latest approved license (for current plan block)
    $currentPlan = $customer->licenseRelation()
        ->where('account_status', 'approved')
        ->orderByDesc('id')
        ->with('durationService')
        ->first();

    $currentPlanDetails = $currentPlan ? [
        'order type'       => optional($currentPlan->durationService)->service_name ?? null,
        'no_of_licenses'  => $currentPlan->licenses,
        'plan_start_date' => $currentPlan->licenses_start_date,
        'plan_end_date'   => $currentPlan->licenses_end_date,
    'per_license_cost' => $currentPlan->price,
            'total_cost' => $currentPlan->license_cost,
            'total_payment_due' => 0,
        'payment_method'  => "Online",
    ] : null;

    $data = [
        'tenant_id'            => $customer->id,
        'current_plan_details' => $currentPlanDetails,

    ];

    return response()->json([
        'status'  => 'success',
        'message' => 'Billing information retrieved successfully.',
        'data'    => $data,
    ]);
}

public function previousInvoice(Request $request)
{
    try {
        $user = Auth::guard('api')->user();
        $tenantId = $user ? $user->tenant_id : 1;

        // Validate request
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $financialYear = $request->year;
        [$startDate, $endDate] = $this->getDateRangeFromFinancialYear($financialYear);

        // Load customer with license & related invoices/payments
        $customer = Customer::with([
                'licenseRelation.durationService',
                'licenseRelation.paymentRecives',
            ])
            ->whereHas('licenseRelation', function ($q) use ($startDate, $endDate) {
                $q->active()->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->find($tenantId);

        if (!$customer) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Invoice not found.',
            ], 404);
        }

        // Invoice history mapping
        $history = $customer->licenseRelation->map(function ($invoice) {
            return [
                'invoice_id'   => $invoice->invoice_id ?? 'DR345',
                'payment_date' => $invoice->payment_date
                                    ? Carbon::parse($invoice->payment_date)->format('d M, Y')
                                    : '9 Aug, 2025',
                'amount_paid'  => $invoice->amount_paid ?? 7865.00,
               // 'invoice_url'  => route('invoices.show', ['id' => $invoice->invoice_id ?? 0]),
               'invoice_url' => "https://example.com/invoices/{$invoice->invoice_id}",
            ];
        })->values();

        return response()->json([
            'status'    => 'success',
            'message'   => 'Billing information retrieved successfully.',
            'data'      => $history->isNotEmpty() ? $history : [],
            'tenant_id' => $customer->id,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'An unexpected error occurred.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

/**
 * Get start & end date from financial year ID
 */
public function getDateRangeFromFinancialYear($financialYearId)
{
    $financialYear = FinancialYear::where('fin_id', $financialYearId)->value('fin_name');

    if (!$financialYear || !str_contains($financialYear, '-')) {
        throw new \InvalidArgumentException("Invalid financial year format.");
    }

    [$startYear, $endYear] = explode('-', $financialYear);

    $startDate = Carbon::createFromDate((int)$startYear, 4, 1)->startOfDay();
    $endDate   = Carbon::createFromDate((int)$endYear, 3, 31)->endOfDay();

    return [$startDate, $endDate];
}

public function activityLog()
{
    $data = [
        [
            'date'     => '2025-07-18',
            'name'     => 'John Doe',
            'role'     => 'Sales Support',
            'activity' => 'Added 2 new licensed users',
        ],
        [
            'date'     => '2025-07-31',
            'name'     => 'Jofin George',
            'role'     => 'Inbound Sales',
            'activity' => 'Changed price for UPC 123998 to 123500',
        ],
        [
            'date'     => '2025-08-02',
            'name'     => 'Stephanie Lee',
            'role'     => 'Purchasing',
            'activity' => 'Deleted UPC 115590',
        ],
        [
            'date'     => '2025-08-18',
            'name'     => 'Susan Hernandez',
            'role'     => 'Accounting',
            'activity' => 'Added 2 new licensed users',
        ],
        [
            'date'     => '2025-08-25',
            'name'     => 'Ross Jackson',
            'role'     => 'Tendering Home',
            'activity' => 'Changed price for UPC 123998 to 123500',
        ],
    ];

    return response()->json([
        'status'  => 'success',
        'message' => 'Activity log retrieved successfully.',
        'data'    => $data,
    ], 200);
}


}
 