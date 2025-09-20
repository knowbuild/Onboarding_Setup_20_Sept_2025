<?php

namespace App\Http\Controllers\LicenseGrid;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Favourite;
use App\Models\CustomerNote;
use App\Models\OnboardingProgres;
use App\Models\License;
use App\Models\WebEnq;
use App\Models\WebEnqEdit;
use App\Models\ProductMain;
use App\Models\EnqSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceMaster;
class AccountManagementController extends Controller
{
public function index(Request $request)
{
    $userId = $request->user_id ?? null;
    $page = $request->input('page', 1);
    $perPage = $request->input('record', 10);
    $today = Carbon::today();

    // Base query with relationships
    $licensesQuery = License::with([
        'customer',
        'customer.accountManagers',
        'customer.source',
        'durationService'
    ])->active();

    // Filter: Account Manager
    if ($request->filled('account_manger_id')) {
        $licensesQuery->whereHas('customer', function ($query) use ($request) {
            $query->where('account_manger_id', $request->account_manger_id);
        });
    }

    // Filter: Search keyword
    if ($request->filled('searchkey')) {
        $searchKey = $request->searchkey;
        $licensesQuery->whereHas('customer', function ($query) use ($searchKey) {
            $query->where(function ($q) use ($searchKey) {
                $q->where('name', 'like', "%{$searchKey}%")
                  ->orWhere('email', 'like', "%{$searchKey}%")
                  ->orWhere('mobile', 'like', "%{$searchKey}%")
                  ->orWhere('customer_code', 'like', "%{$searchKey}%")
                  ->orWhere('organisation', 'like', "%{$searchKey}%");
            });
        });
    }

    // Clone the filtered query before applying account_status filter
    $filteredQuery = clone $licensesQuery;

    // Calculate date for 30-day renewal window
    $renewalEndDate = $today->copy()->addDays(30);

    // Totals before narrowing down to "approved" licenses
    $RenewalAlert = (clone $filteredQuery)
        ->where('account_status', 'approved')
        ->whereBetween('licenses_end_date', [$today, $renewalEndDate])
        ->count();

    $AllActiveAccounts = (clone $filteredQuery)
        ->where('account_status', 'approved')
        ->count();

    $ExpiredAccounts = (clone $filteredQuery)
        ->where('account_status', 'expired')
        ->count();
  $DraftAccounts = (clone $filteredQuery)
        ->where('account_status', 'draft')
        ->count();
    // Apply account status filter to main query
      if ($request->filled('account_type')) {
    $licensesQuery->where('account_status', $request->account_type); //approved,expired
 }
    // Filter: License Type
    if ($request->filled('licenses_type')) {
        $licensesQuery->where('licenses_type', $request->licenses_type);
    }

    // Filter: Source
    if ($request->filled('source_id')) {
        $licensesQuery->whereHas('customer', function ($query) use ($request) {
            $query->where('source_id', $request->source_id);
        });
    }

    // Filter: Expiring in N days
    if ($request->filled('expiringIn')) {
        $expiringInDays = (int) $request->expiringIn;
        $expiringEndDate = $today->copy()->addDays($expiringInDays);
        $licensesQuery->whereBetween('licenses_end_date', [$today, $expiringEndDate]);
    }

    // Paginate final result
    $licenses = $licensesQuery->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

    // Format data
    $formatted = $licenses->getCollection()->map(function ($license) use ($today) {
        $isRecent = Carbon::parse($license->updated_at)->gt(Carbon::now()->subDays(7));
        $endDate = Carbon::parse($license->licenses_end_date);
        $daysDiff = $endDate->gte($today) ? $today->diffInDays($endDate) : 0;

        return [
            'id' => $license->id,
            'customer_id' => $license->customer_id,
            'license' => $license->licenses,
            'licenses_code' => $license->licenses_code,
            'licenses_type' => $license->licenses_type,
            'customer_code' => optional($license->customer)->customer_code,
            'organisation' => optional($license->customer)->organisation,
            'name' => optional($license->customer)->name,
            'email' => optional($license->customer)->email,
             'mobile' => optional($license->customer)->mobile,
            'progress_tracking' => $isRecent ? 1 : 0,
            'account_manger_id' => optional($license->customer)->account_manger_id,
             'account_manger_name' => trim(
    optional(optional($license->customer)->accountManagers)->admin_fname . ' ' .
    optional(optional($license->customer)->accountManagers)->admin_lname
) ?: null,

            'source' => optional(optional($license->customer)->source)->enq_source_name ?? null,
            'account_status' => $license->account_status,
            'licenses_start_date' => $license->licenses_start_date,
            'licenses_end_date' => $license->licenses_end_date,
       'duration' => optional($license->durationService)->service_name ?? null,
            'expiringIn' => "Licenses expiring in {$daysDiff} days",
        ];
    });

    // Return JSON response
    return response()->json([
        'status' => 'success',
        'message' => 'License listed successfully.',
        'data' => $formatted,
        'pagination' => [
            'RenewalAlert' => $RenewalAlert,
            'AllActiveAccounts' => $AllActiveAccounts,
            'ExpiredAccounts' => $ExpiredAccounts,
             'DraftAccounts' => $DraftAccounts,
            'total' => $licenses->total(),
            'per_page' => $licenses->perPage(),
            'current_page' => $licenses->currentPage(),
            'last_page' => $licenses->lastPage(),
        ],
    ]);
}

public function details(Request $request)
{
    $validator = Validator::make($request->all(), [
        'customer_id' => 'required|exists:customers,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $customer = Customer::with([
        'licenseRelation.durationService',
        'accountManagers',
        'source',
        'countryRelation',
        'onboardingProgres.onboardingStep',
        'notesRelation.creator',
        'customerUsers.department',
        'customerUsers.designation',
        'reminderRelation'
    ])->find($request->customer_id);

    if (!$customer) {
        return response()->json(['error' => 'Customer not found.'], 404);
    }

    // Onboarding Progress: latest created_at date
    $lastStepDate = optional($customer->onboardingProgres)->max('created_at');

    // Notes
    $notes = $customer->notesRelation->map(function ($note) {
        return [
            'id'         => $note->id,
            'note'       => $note->note,
            'created_by' => trim(optional($note->creator)->admin_fname . ' ' . optional($note->creator)->admin_lname) ?: null,
            'created_at' => $note->created_at,
        ];
    });

    // Licenses
    $licenses = $customer->licenseRelation->map(function ($license) {
        return [
            'license_id'          => $license->id,
            'license'             => $license->licenses,
            'licenses_code'       => $license->licenses_code,
            'licenses_type'       => $license->licenses_type,
            'licenses_status'     => $license->account_status,
            'license_cost'        => $license->license_cost,
            'licenses_start_date' => $license->licenses_start_date,
            'licenses_end_date'   => $license->licenses_end_date,
            'duration'            => optional($license->durationService)->service_name ?? null,
        ];
    });
 
    // Sum of license counts (sum of `licenses` column)
    $licenseCount = $customer->licenseRelation->sum('licenses');

    // Latest license_type by most recent created_at
    $licenses_type_customer = $customer->licenseRelation
        ->sortByDesc('created_at')
        ->pluck('licenses_type')
        ->first();

    // Customer users
    $customerUsers = $customer->customerUsers->map(function ($user) {
        return [
            'id'             => $user->admin_id,
            'name'           => trim("{$user->admin_fname} {$user->admin_lname}")  ?? null,
            'email'          => $user->admin_email,
            'mobile'         => $user->admin_telephone,
            'department'     => $user->admin_role_id,
            'designation'    => $user->admin_designation,
            'created_at'     => optional($user->created_at)->format('Y-m-d'),
            'last_active_at' => $user->last_active_at,
              'status' => $user->admin_status,
        ];
    });

    // Reminders
    $reminder = optional($customer->reminderRelation->sortByDesc('date')->first());
    $reminders = [
    'id'     => $reminder->id,
    'date'   => $reminder->date,
    'action' => $reminder->action,
    'note'   => $reminder->note,
];
$historyCompletedTasks = [
    [
        'task_name'  => 'Lead',
        'task_id'    => '48140',
        'task_date'  => '2025-06-20 04:20:25',
        'comment'    => 'Lead Created',
        'task_icon'  => 'crm/images/dashboard/das-icon/view_offer/lead.png',
    ],
    [
        'task_name'  => 'Inquiry',
        'task_id'    => '45398',
        'task_date'  => '2025-06-20 09:48:59',
        'comment'    => 'Enq assigned',
        'task_icon'  => 'crm/images/dashboard/das-icon/view_offer/inquiry.png',
    ],
    [
        'task_name'  => 'Offer',
        'task_id'    => '0-41676',
        'task_date'  => '2025-06-24 00:00:00',
        'comment'    => 'Offer Created',
        'task_icon'  => 'crm/images/dashboard/das-icon/view_offer/offer.png',
    ],
];

    // Final response
    $data = [
        'customer_id'            => $customer->id,
        'customer_code'          => $customer->customer_code,
        'organisation'           => $customer->organisation,
        'name'                   => $customer->name,
        'email'                  => $customer->email,
        'mobile'                 => $customer->mobile,
        'country'                => optional($customer->countryRelation)->country_name ?: null,
        'account_manger_id'      => $customer->account_manger_id,
        'account_manger_name'    => trim(optional($customer->accountManagers)->admin_fname . ' ' . optional($customer->accountManagers)->admin_lname) ?: null,
        'source'                 => optional($customer->source)->enq_source_name ?: null,
        'gst_number'             => $customer->gst_number,
        'note'                   => $customer->notes,

        'notes'                  => $notes,
        'licenses'               => $licenses,
        'customer_users'         => $customerUsers,
        'reminders'              => $reminders,
        'lastStepDate'           => $lastStepDate,
        'licenseCount'           => $licenseCount,
        'licenses_type_customer' => $licenses_type_customer,
        'historyCompletedTasks' => $historyCompletedTasks,
    ];

    return response()->json([
        'status'  => 'success',
        'message' => 'Customer details retrieved successfully.',
        'data'    => $data,
    ]);
}


public function lastLicenseDetails(Request $request)
{
    $validator = Validator::make($request->all(), [
        'customer_id' => 'required|exists:customers,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $customer = Customer::with([
        'accountManagers',
        'source',
        'countryRelation',
        'onboardingProgres.onboardingStep',
        'notesRelation.creator',
        'customerUsers.department',
        'customerUsers.designation',
        'reminderRelation',
    ])->find($request->customer_id);

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




    // ?? Update Department
    public function updateDepartmentCustomerUsers(Request $request)
    {
        $request->validate([
            'customer_users_id' => 'required',
            'department_id'     => 'required',
        ]);

        $customerUser = User::findOrFail($request->customer_users_id);

        $customerUser->update([
            'admin_role_id' => $request->department_id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer user department updated successfully.',
        ]);
    }

    // ?? Update Designation
    public function updateDesignationCustomerUsers(Request $request)
    {
        $request->validate([
            'customer_users_id'   => 'required',
            'designation_id'         => 'required',
        ]);

        $customerUser = User::findOrFail($request->customer_users_id);

        $customerUser->update([
            'admin_designation' => $request->designation_id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer user designation updated successfully.',
        ]);
    }
 
    // ?? Update Account Status
    public function updateStatusCustomerUsers(Request $request)
    {
        $request->validate([
            'customer_users_id' => 'required',
            'account_status'    => 'required|in:active,inactive',
        ]);

        $customerUser = User::findOrFail($request->customer_users_id);

        $customerUser->update([
            'admin_status' => $request->account_status,
            'remark'=>$request->remark,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer user account status updated successfully.',
        ]);
    }


public function storeNewCustomer(Request $request)
{
    $validator = Validator::make($request->all(), [
        'organisation'        => 'required|string|max:255|unique:customers',
        'name'                => 'required|string|max:100',
        'email'               => 'required|string|email|max:255|unique:customers',
        'mobile'              => 'required|string|max:20|unique:customers',
        'country_id'          => 'required|integer',
        'state_id'            => 'required|integer',
        'city_id'             => 'required|integer',
        'address'             => 'required|string|max:255',
        'notes'               => 'required|string|max:500',
        'source_id'           => 'required|integer',
        'segment_id'          => 'required|integer',
        'gst_vat_number'      => 'nullable|string|max:100',
        'company_website'     => 'required|string|max:100|unique:customers',
        'product_category_id' => 'required|integer',

        // License-specific
        'product_id'          => 'required|integer',
        'licenses'            => 'required|integer|min:1',
        'price'               => 'nullable|numeric|min:0',
        'duration'            => 'required|integer|exists:tbl_service_master,service_id',
        'licenses_type'       => 'required|in:trial,paid',
        'status'              => 'nullable|in:active,inactive',
        'account_status'      => 'nullable|in:pending,approved,hold,expired,rejected,draft',
    ]);
 
    if ($validator->fails()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422);
    }

    try {
        // Step 1: Create Customer
        $customer = Customer::create([
            'organisation'     => $request->organisation,
            'name'             => $request->name,
            'email'            => $request->email,
            'mobile'           => $request->mobile,
            'company_address'  => $request->address,
              'country'  => $request->country_id,
            'company_country'  => $request->country_id,
            'company_state'    => $request->state_id,
            'company_city'     => $request->city_id,

            'company_address'       => $request->address,
        'company_country'       => $request->country_id,
        'company_state'         => $request->state_id,
        'company_city'          => $request->city_id,
        'purchase_address'      => $request->address,
        'purchase_country'      => $request->country_id,
        'purchase_state'        => $request->state_id,
        'purchase_city'         => $request->city_id,

            'segment_id'       => $request->segment_id,
            'notes'            => $request->notes,
            'source_id'        => $request->source_id,
            'gst_number'       => $request->gst_vat_number,
            'company_website'  => $request->company_website,
             'licenses'     => $request->licenses,
            'status'           => 'active',
            'account_status'   => 'pending',
            'account_access'   => 'inactive',
            'customer_code'    => codeCustomer(), 
        ]);
$products = ProductMain::where('pro_id',$request->product_id)->first();
 // Step 2: Calculate License Financials
if($request->licenses_type == 'trial'){
        $price        =  0;
        $licenses     =  (int) $request->licenses;
        $subTotal     =  0;
        $taxRate      = 18;
        $taxPrice     =  0;
        $licenseCost  =  0;
}
else{
       $price        = (float) $products->pro_price ?? 0;
        $licenses     = (int) $request->licenses;
        $subTotal     = $price * $licenses;
        $taxRate      = 18;
        $taxPrice     = round(($subTotal * $taxRate) / 100, 2);
        $licenseCost  = $subTotal + $taxPrice;
}
       
     

        // Step 3: Duration & Date Calculation
        $durationService = ServiceMaster::find($request->duration);
        $monthsDuration  = (int) optional($durationService)->service_abbrv ?? 0;

        $startDate = Carbon::today();
        $endDate   = $startDate->copy()->addMonths($monthsDuration);

        // Step 4: Create License
      $licenses =  License::create([
            'customer_id'         => $customer->id,
            'product_id'          => $request->product_id,
            'licenses'            => $licenses,
            'price'               => $price,
            'sub_total_price'     => $subTotal,
            'tax_price'           => $taxPrice,
            'license_cost'        => $licenseCost,
            'duration'            => $request->duration,
            'licenses_start_date' => $startDate->format('Y-m-d'),
            'licenses_end_date'   => $endDate->format('Y-m-d'),
            'licenses_type'       => $request->licenses_type ?? 'paid',
            'status'              => $request->status ?? 'active',
            'account_status'      => $request->account_status ?? 'pending',
            'licenses_code'       => generateLicenseCode(),
        //    'proforma_invoice'    => generateProformaInvoiceCode(),
        ]);
$enqSource = EnqSource::where('enq_source_id',$request->source_id)->first();
        // Step 5: Create WebEnquiry
   $webEnq   =  WebEnq::create([
            'Cus_name'         => $request->name,
            'Cus_email'        => $request->email,
            'Cus_mob'          => $request->mobile,
            'Cus_msg'          => $request->notes,
            'product_category' => $request->product_category_id,
            'ref_source' => $enqSource->enq_source_description,
              'source_id' => $request->source_id,
        ]);
$customer_id = $customer->id;
$license_id = $licenses->id;

$webEnq_id = $webEnq->ID;
if($request->licenses_type == 'paid'){
 //  generateAllProcessOnlineLicense($customer_id,$license_id,$webEnq_id);
}
  

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer license inquiry created successfully.',
            'data'    => $customer,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'An error occurred: ' . $e->getMessage(),
        ], 500);
    }
}



}
 