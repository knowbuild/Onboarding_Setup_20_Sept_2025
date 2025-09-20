<?php

namespace App\Http\Controllers\LicenseGrid;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Favourite;
use App\Models\CustomerNote;
use App\Models\OnboardingProgres;
use App\Models\License;
use App\Models\OnboardingStep;
use App\Models\WebEnq;
use App\Models\WebEnqEdit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Mail\Onboarding\Auth\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class OnboardingRequestController extends Controller
{ 
public function index(Request $request)
{
    licenseCustomerInsert();

    $userId = $request->user_id ?? null;
    $page = $request->input('page', 1);
    $perPage = $request->input('record', 10);
    $today = Carbon::today();
    $onboardingStep = OnboardingStep::count();

    $baseQuery = License::with([
        'customer',
        'customer.accountManagers',
        'customer.source'
    ]);

    // Filters
    if ($request->filled('account_manger_id') && $request->account_manger_id != 0) {
        $baseQuery->whereHas('customer', fn($q) => $q->where('account_manger_id', $request->account_manger_id));
    }
    // Clone for metric calculations
    $filteredQuery = clone $baseQuery;
    $todayRequests = (clone $filteredQuery)->whereIn('account_status', ['pending', 'hold', 'approved'])->whereDate('created_at', $today) 
    ->whereHas('customer', fn($q) => $q->where('onboarding_step', '<', $onboardingStep))->count();
    $todayTotal = (clone $filteredQuery)->whereIn('account_status', ['pending', 'hold', 'approved'])->whereDate('created_at', $today) 
    ->whereHas('customer', fn($q) => $q->where('onboarding_step', '<', $onboardingStep)) ->count();
    $allRequests = (clone $filteredQuery)->whereIn('account_status', ['pending', 'hold', 'approved']) 
    ->whereHas('customer', fn($q) => $q->where('onboarding_step', '<', $onboardingStep)) ->count();
    $overdueRequests = (clone $filteredQuery) ->whereIn('account_status', ['pending', 'hold', 'approved'])->whereDate('created_at', '<', $today->copy()->subDays(7)) 
    ->whereHas('customer', fn($q) => $q->where('onboarding_step', '<', $onboardingStep))->count();
    $totalLicensesCompleted = (clone $filteredQuery)->where('account_status', 'approved')->whereHas('customer', fn($q) => $q->where('onboarding_step', $onboardingStep))->count();
    $totalLicensesReject = (clone $filteredQuery)->where('account_status', 'rejected')->count();

    if ($request->filled('onboarding_status')) {
        if ($request->onboarding_status === 'pending') {
            $baseQuery->whereIn('account_status', ['pending', 'hold', 'approved'])
                ->whereHas('customer', fn($q) => $q->where('onboarding_step', '<', $onboardingStep));
        } elseif ($request->onboarding_status === 'completed') {
            $baseQuery->where('account_status', 'approved')
                ->whereHas('customer', fn($q) => $q->where('onboarding_step',$onboardingStep));
        } elseif ($request->onboarding_status === 'rejected') {
            $baseQuery->where('account_status', 'rejected');
        }
    }

    if ($request->filled('request_type')) {
        if ($request->request_type === 'today') {
            $baseQuery->whereDate('created_at', $today);
        } elseif ($request->request_type === 'overdue') {
            $baseQuery->whereDate('created_at', '<', $today->copy()->subDays(7));
        }
    }

    // Summary counts
        $filteredQuerys = clone $baseQuery;
    $totalLicenses = (clone $filteredQuerys)->count();
    $totalTrial = (clone $filteredQuerys)->where('licenses_type', 'trial')->count();
    $totalPaid = (clone $filteredQuerys)->where('licenses_type', 'paid')->count();

    if ($request->filled('licenses_type')) {
        $baseQuery->where('licenses_type', $request->licenses_type);
    }

 //   if ($request->filled('account_status')) {
 //       $baseQuery->where('account_status', $request->account_status);
//    }





    // Paginated list
    $licenses = $baseQuery->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

    // Format response
    $formatted = $licenses->getCollection()->map(function ($license) {
        $isRecent = Carbon::parse($license->updated_at)->gt(Carbon::now()->subDays(7));
        $customer = optional($license->customer);
        $manager = optional($customer->accountManagers);

        return [
            'id' => $license->id,
            'customer_id' => $license->customer_id,
            'license' => $license->licenses,
            'licenses_code' => $license->licenses_code,
            'licenses_type' => $license->licenses_type,
            'customer_code' => $customer->customer_code,
            'organisation' => $customer->organisation,
            'name' => $customer->name,
            'email' => $customer->email,
            'progress_tracking' => $isRecent ? 1 : 0,
            'account_manger_id' => $customer->account_manger_id,
            'account_manger_name' => trim($manager->admin_fname . ' ' . $manager->admin_lname) ?: null,
            'source' => optional($customer->source)->enq_source_name,
            'account_status' => $license->account_status,
        ];
    });




    return response()->json([
        'status' => 'success',
        'message' => 'License listed successfully.',
        'data' => $formatted,
        'pagination' => [
            'todayRequests' => $todayRequests,
            'todayTotal' => $todayTotal,
            'allRequests' => $allRequests,
            'overdueRequests' => $overdueRequests,
             'total_licensesCompleted' => $totalLicensesCompleted,
            'total_licensesReject' => $totalLicensesReject,

            'total_licenses' => $totalLicenses,
            'total_trial' => $totalTrial,
            'total_paid' => $totalPaid,
            'total' => $licenses->total(),
            'per_page' => $licenses->perPage(),
            'current_page' => $licenses->currentPage(),
            'last_page' => $licenses->lastPage(),
           'onboardingStep' =>$onboardingStep,
        ],
    ]);
}


public function details(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id' => 'required|exists:licenses,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $license = License::with([
        'customer',
        'customer.accountManagers',
        'customer.source',
        'customer.countryRelation',
        'durationService',
        'customer.onboardingProgres.onboardingStep',
        'customer.notesRelation.creator',
        'customer.segment'
    ])->find($request->id);

    if (!$license) {
        return response()->json(['error' => 'License not found.'], 404);
    }

    $today = Carbon::today();
    $isRecent = Carbon::parse($license->updated_at)->gt($today->copy()->subDays(7));
    $progressTracking = $isRecent ? 1 : 0;

    $createdAt = Carbon::parse($license->created_at);
    $requestDate = $createdAt->format('d M Y') . ' (' . $createdAt->diffForHumans(null, true) . ')';

    // Onboarding progress steps
    $onboardingProgress = collect(optional($license->customer)->onboardingProgres);

// Get all onboarding steps
$allSteps = OnboardingStep::select('id', 'name')->orderBy('id')->get();
$totalSteps = $allSteps->count();

// Get customer's completed onboarding steps
$completedSteps = collect(optional($license->customer)->onboardingProgres)->keyBy('onboarding_step_id');
$completedCount = $completedSteps->count();

// Merge all steps with completed info
$onboardingSteps = $allSteps->map(function ($step) use ($completedSteps) {
    $progress = $completedSteps->get($step->id);

    return [
        'id'         => $step->id,
        'name'       => $step->name,
        'created_at' => $progress->created_at ?? null,
        'status'     => $progress ? $progress->status : 'pending',
    ];
});


// Calculate percentage (rounded)
$progressPercentage = $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0;


  
 
    $firstStepDate = $onboardingProgress->min('created_at');
    $lastStepDate = $onboardingProgress->max('created_at');
  if ($firstStepDate && $lastStepDate) {
    $dayse = (int) Carbon::parse($firstStepDate)->diffInDays(Carbon::parse($lastStepDate));
    $elapsedTime =  $dayse . ' days';
} else {
    $elapsedTime = 'Not Started Yet';
}


    // Overdue check
    $overdueRequests = License::where('customer_id', $license->customer_id)
        ->whereIn('account_status', ['pending', 'hold'])
        ->whereDate('created_at', '<', $today->copy()->subDays(7))
        ->count();

    $overdue = $overdueRequests > 0 ? 1 : 0;

    // Customer notes
 $notes = collect(optional($license->customer)->notesRelation)
    ->sortByDesc('created_at') // sort by latest first
    ->values() // reset keys to avoid JSON objects with numeric keys
    ->map(fn($note) => [
        'id' => $note->id,
        'note' => $note->note,
        'created_by' => trim(optional($note->creator)->admin_fname . ' ' . optional($note->creator)->admin_lname) ?: null,
        'created_at' => $note->created_at,
    ]);



    $data = [
        'id' => $license->id,
        'license' => $license->licenses,
        'licenses_start_date' => $license->licenses_start_date,
        'licenses_end_date' => $license->licenses_end_date,
        'duration_id' => $license->duration,
        'duration' => optional($license->durationService)->service_name,
        'licenses_code' => $license->licenses_code,
        'licenses_type' => $license->licenses_type,
        'request_date' => $requestDate,
        'country' => optional($license->customer?->countryRelation)->country_name,
        'customer_id' => $license->customer_id,
        'customer_code' => optional($license->customer)->customer_code,
        'organisation' => optional($license->customer)->organisation,
        'name' => optional($license->customer)->name,
        'email' => optional($license->customer)->email,
        'mobile' => optional($license->customer)->mobile,
        'progress_tracking' => $progressTracking,
        'account_manger_id' => optional($license->customer)->account_manger_id,
      'account_manger_name' => trim( optional($license->customer->accountManagers)->admin_fname . ' ' . optional($license->customer->accountManagers)->admin_lname) ?: null,

        'source' => optional($license->customer->source)->enq_source_name ?? null,
        'note' => optional($license->customer)->notes ?? null,
        'gst_number' => optional($license->customer)->gst_number,
        'onboardingProgres' => $onboardingSteps,
       'elapsed_time' =>  $elapsedTime,
        'notes' => $notes,
        'reject_reason' => $license->reject_reason,
        'reject_at' => $license->reject_at,
    'onboardingfirstStepDate' => $firstStepDate,
    'onboardinglastStepDate' =>  $lastStepDate,
        'licenses_status' => $license->account_status,
        'overdue' => $overdue,
            'onboardingProgres_percentage' => $progressPercentage,
            'segment' =>  optional($license->customer?->segment)->cust_segment_name,
    ];

    return response()->json([
        'status' => 'success',
        'message' => 'License details retrieved successfully.',
        'data' => $data,
    ]);
}




  public function updateAccountManager(Request $request) 
{
    $validator = Validator::make($request->all(), [
        'customer_id' => 'required|exists:customers,id',
        'account_manger_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        Customer::where('id', $request->customer_id)
                ->update(['account_manger_id' => $request->account_manger_id]);
                

 $customer = Customer::where('id', $request->customer_id)->first();

    
        $webEnq = WebEnq::where('Cus_name', $customer->name)
            ->where('Cus_email', $customer->email)
            ->where('Cus_mob', $customer->mobile)
            ->first();

       

        WebEnqEdit::updateOrCreate([
            'enq_id'           => $webEnq->ID,
            'Cus_name'         => $customer->name,
            'Cus_email'        => $customer->email,
            'Cus_mob'          => $customer->mobile,
        ],
             [
            'Cus_msg'          => $customer->notes,
            'country'          => $customer->country,
            'city'             => $customer->company_city,
            'state'            => $customer->company_state,
            'acc_manager'      => $request->account_manger_id,
            'ref_source'       => $webEnq->ref_source,
            'cust_segment'     => $customer->segment_id,
            'product_category' => $webEnq->product_category,
            'address'          => $customer->company_address,
            'enq_type'         => 'service',
            'price_type'       => 'pvt',
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Account Manager updated successfully.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
public function updateGstVatNumber(Request $request)
{
    $validator = Validator::make($request->all(), [
        'customer_id' => 'required|exists:customers,id',
        'gst_vat_number' => 'required|string|max:50',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        Customer::where('id', $request->customer_id)
                ->update(['gst_number' => $request->gst_vat_number]);

        return response()->json([
            'status' => 'success',
            'message' => 'GST/VAT Number updated successfully.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
public function updateRequestStatus(Request $request)
{
    $validator = Validator::make($request->all(), [
        'customer_id'    => 'required|exists:customers,id',
        'id'             => 'required|exists:licenses,id',
        'reject_reason'  => 'required_if:account_status,rejected|string|nullable',
        'account_status' => 'required|in:approved,rejected,pending',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'validation_error',
            'errors' => $validator->errors(),
        ], 422);
    }
      $customer = Customer::find($request->customer_id);

            if (empty($customer?->account_manger_id)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Error: Please assign an Account Manager first',
                ], 422);
            }
    try {
          

        $licenseData = [
            'account_status' => $request->account_status,
            'reject_reason'  => $request->reject_reason,
            'reject_at'      => $request->account_status === 'rejected' ? now() : null,
        ];

        License::where('id', $request->id)->update($licenseData);

        if ($request->account_status === 'approved') {
    

            $customerCode = $customer['customer_code'];
            $orgSlug      = strtolower(str_replace([' ', '.'], '', $customer['organisation']));

            $dbName     = "{$orgSlug}{$customerCode}";
            $dbUsername = "u{$orgSlug}{$customerCode}";
            $dbPassword = "p{$orgSlug}{$customerCode}";

            Customer::where('id', $request->customer_id)->update([
                'account_access'     => 'active',
                'account_status'     => 'approved',
                'database_name'      => $dbName,
                'database_username'  => $dbUsername,
                'database_password'  => $dbPassword,
            ]);

            $name  = $customer['name'];
            $email = $customer['email'];

            $nameParts   = explode(" ", $name);
            $adminFname  = $nameParts[0] ?? '';
            $adminLname  = implode(" ", array_slice($nameParts, 1)) ?: '';
            $token       = Str::random(60);

            $user = User::updateOrCreate(
                ['customer_id' => $request->customer_id],
                [
                    'customer_code'     => $customerCode,
                    'admin_fname'     => $adminFname,
                    'admin_lname'     => $adminLname,
                    'admin_email'     => $email,
                    'confirmed'       => 3,
                    'remember_token'  => $token,
                ]
            );

            $setupPasswordUrl = getWeb()->web_url . "/setuppassword?tok=$token";
 Mail::to($email)->send(new WelcomeMail($user, $setupPasswordUrl));

            $customer_id = $request->customer_id;
            $license_id  = $request->id;

            $webEnq = WebEnq::where('Cus_name', $name)
                ->where('Cus_email', $email)
                ->where('Cus_mob', $customer['mobile'])
                ->where('created_at', $customer['created_at'])
                ->first();

            $webEnq_id = $webEnq['ID'];

            generateAllProcessOnlineLicense($customer_id, $license_id, $webEnq_id);
             updateLicenseInCustomerCount($customer_id);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Status updated successfully.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}

public function offlineLicenseCustomer(Request $request)
{
    $request->validate([
        'payment_received_id' => 'required|exists:tbl_payment_received,payment_received_id',
    ]);

    try {

        licenseCustomer($request->payment_received_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Offline License Customer created successfully.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'An error occurred while creating the license customer.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

}
  