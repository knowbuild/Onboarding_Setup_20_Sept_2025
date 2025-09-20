<?php

namespace App\Http\Controllers\LicenseGrid;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = License::with('customer')->orderByDesc('id')->get();
          return response()->json([
            'status' => 'success',
            'message' => 'Licenses retrieved successfully.',
            'data' => $licenses,
        ]);
       
    }

    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:licenses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $license = License::with('customer')->find($request->id);
           if (!$license) {
            return response()->json([
                'status' => 'error',
                'message' => 'License not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'License retrieved successfully.',
            'data' => $license,
        ],200);
  
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:licenses,id',
            'customer_id' => 'required|exists:customers,id',
                 'product_id' => 'nullable',
            'licenses' => 'required',
            'price' => 'nullable',
            'sub_total_price' => 'nullable',
            'tax_price' => 'nullable',
            'license_cost' => 'nullable',
            'duration' => 'required|integer|min:1',
            'licenses_start_date' => 'nullable|date',
            'licenses_end_date' => 'nullable|date',
            'licenses_type' => 'nullable|in:trial,paid',
           'status' => 'nullable|in:active,inactive',
         'account_status' => 'nullable|in:pending,approved,hold,expired,rejected,draft',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'customer_id', 'licenses','price','sub_total_price','tax_price', 'license_cost', 'duration',
            'licenses_start_date', 'licenses_end_date', 'licenses_type',
            'status', 'account_status','product_id'
        ]);
if (!$request->id) {
    $data['licenses_code'] = generateLicenseCode();
  //  $data['proforma_invoice'] = generateProformaInvoiceCode();
}

        $license = License::updateOrCreate(
            ['id' => $request->id],
            $data
        );
  
   // If status is approved, update related customer info
    if ($request->licenses_type === 'paid') {
        Customer::where('id', $request->customer_id)->update([
            'status'         => 'active',
            'account_status' => 'approved',
            'account_access' => 'active',
        ]);
    }

     updateLicenseInCustomerCount($request->customer_id);

        return response()->json([
            'status' => 'success',
            'message' => $request->id ? 'Updated successfully.' : 'Created successfully.',
            'data' => $license,
        ]);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:licenses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        License::find($request->id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Deleted successfully.']);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:licenses,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        License::where('id', $request->id)->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Status updated successfully.']);
    }

public function indexByCustomer(Request $request)
{
    $userId = $request->user_id ?? null;
    $page = $request->input('page', 1);
    $perPage = $request->input('record', 100);
    $today = Carbon::today();

    // Base query
    $licensesQuery = License::with(['durationService', 'paymentRecives']) // Include payment relation
        ->where('customer_id', $request->customer_id)
        ->active();

    // Clone for counts
    $filteredQuery = clone $licensesQuery;
    $renewalEndDate = $today->copy()->addDays(30);

    // Count summaries
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
        ->where('account_status', ['draft', 'pending'])
        ->count();

    // Apply filter
    if ($request->filled('account_status')) {
        if($request->account_status == 'pending'){
           $licensesQuery->where('account_status',  ['draft', 'pending']); 
        }
        else{
    $licensesQuery->where('account_status', $request->account_status);
        }
    
    }

    // Paginate results
    $licenses = $licensesQuery->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

    // Format response
    $formatted = $licenses->getCollection()->map(function ($license) use ($today) {
        $isRecent = Carbon::parse($license->updated_at)->gt(Carbon::now()->subDays(7));
        $endDate = Carbon::parse($license->licenses_end_date);
        $daysDiff = $endDate->gte($today) ? $today->diffInDays($endDate) : 0;

        // Payment data
        $totalAmount = $license->paymentRecives->sum('amount');
        $latestPayment = $license->paymentRecives->sortByDesc('created_at')->first();

        return [
            'id' => $license->id,
            'license_id' => $license->id,
            'customer_id' => $license->customer_id,
            'licenses_code' => $license->licenses_code,
            'licenses' => $license->licenses,
            'product_id' => $license->product_id,
            'price' => $license->price,
            'sub_total_price' => $license->sub_total_price,
            'tax_price' => $license->tax_price,
            'license_cost' => $license->license_cost,
            'duration' => optional($license->durationService)->service_name ?? null,
            'licenses_start_date' => $license->licenses_start_date,
            'licenses_end_date' => $license->licenses_end_date,
            'licenses_type' => $license->licenses_type,
            'status' => $license->status,
            'account_status' => $license->account_status,
            'proforma_invoice' => $license->proforma_invoice,
            'proforma_invoice_at' => $license->proforma_invoice_at,
            'buyer_po_number' => $license->buyer_po_number,
            'buyer_po_image' => $license->buyer_po_image,
            'term_payment_id' => $license->term_payment_id,
            'po_notes' => $license->po_notes,
            'company_bill_bank_id' => $license->company_bill_bank_id,
            'created_by' => $license->created_by,
'created_at'=>$license->created_at,
            // Newly added fields from license_payment_recives
            'payment_received_amount' => $totalAmount,
            'payment_mode' => optional($latestPayment)->payment_mode ?? null,
            'payment_attachment' => optional($latestPayment)->attachment_path ?? null,

            'progress_tracking' => $isRecent ? 1 : 0,
            'expiringIn' => "Licenses expiring in {$daysDiff} days",
        ];
    });

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
public function accountStatusUpdate(Request $request)
{
    // Validate incoming request
    $request->validate([
        'id'             => 'required|exists:licenses,id',
        'account_status' => 'required|in:pending,approved,hold,expired,rejected,draft',
        'remark'         => 'required|string',
    ]);

    // Find the license or fail with 404
    $license = License::findOrFail($request->id);

    // Update the license account status and remark
    $license->update([
        'account_status' => $request->account_status,
        'remark'         => $request->remark,
    ]);

    // If status is approved, update related customer info
    if ($request->account_status === 'approved') {
        Customer::where('id', $license->customer_id)->update([
            'status'         => 'active',
            'account_status' => 'approved',
            'account_access' => 'active',
        ]);
    }

    // Return successful JSON response
    return response()->json([
        'status'  => 'success',
        'message' => 'Account status updated successfully.',
        'data'    => [
            'id'             => $license->id,
            'account_status' => $license->account_status,
            'remark'         => $license->remark,
        ],
    ]);
}


public function indexView(Request $request)
{
    $today = Carbon::today();
    $renewalEndDate = $today->copy()->addDays(30);

    // Base query
    $licensesQuery = License::with(['durationService', 'paymentRecives'])
        ->where('id', $request->licenses_id)
        ->active();

    // Cloned query for counts
    $countQuery = clone $licensesQuery;

   

    // Get all results (no pagination)
    $licenses = $licensesQuery->orderByDesc('id')->get();

    // Format license data
    $formatted = $licenses->map(function ($license) use ($today) {
        $isRecent = Carbon::parse($license->updated_at)->gt(Carbon::now()->subDays(7));
        $endDate = Carbon::parse($license->licenses_end_date);
        $daysDiff = $endDate->gte($today) ? $today->diffInDays($endDate) : 0;

        $totalAmount = $license->paymentRecives->sum('payment_received_value');
        $latestPayment = $license->paymentRecives->sortByDesc('created_at')->first();

             $startDate = Carbon::parse($license->licenses_start_date);
        $endDate = Carbon::parse($license->licenses_end_date);
        $remaining_days = $startDate->diffInDays($endDate);
$remaining_day = $remaining_days < 0 ? 0 : $remaining_days;



// Calculate contract days
$contract_days = optional($license->durationService)->service_abbrv ? 
                 ((int) $license->durationService->service_abbrv * 30) : 0;
 
// Avoid division by zero
$pro_rated_licenseCost = 0;
if ($contract_days > 0) {
    $pro_rated_licenseCost = round($license->price * ($remaining_day / $contract_days), 2);
}
        return [
            'id' => $license->id,
            'license_id' => $license->id,
            'customer_id' => $license->customer_id,
            'licenses_code' => $license->licenses_code,
            'licenses' => $license->licenses,
            'product_id' => $license->product_id,
            'price' => $license->price,
            'sub_total_price' => $license->sub_total_price,
            'tax_price' => $license->tax_price,
            'license_cost' => $license->license_cost,
            'duration' => optional($license->durationService)->service_name ?? null,
             'duration_id'            => $license->duration,
            'licenses_start_date' => $license->licenses_start_date,
            'licenses_end_date' => $license->licenses_end_date,
            'licenses_type' => $license->licenses_type,
            'status' => $license->status,
            'account_status' => $license->account_status,
            'proforma_invoice' => $license->proforma_invoice,
            'proforma_invoice_at' => $license->proforma_invoice_at,
            'buyer_po_number' => $license->buyer_po_number,
            'buyer_po_image' => $license->buyer_po_image,
            'term_payment_id' => $license->term_payment_id,
            'po_notes' => $license->po_notes,
            'company_bill_bank_id' => $license->company_bill_bank_id,
            'created_by' => $license->created_by,

            'payment_received_amount' => $totalAmount,
            'payment_mode' => optional($latestPayment)->payment_mode ?? null,
            'payment_attachment' => optional($latestPayment)->attachment_path ?? null,

            'progress_tracking' => $isRecent ? 1 : 0,
            'expiringIn' => "Licenses expiring in {$daysDiff} days",
            'paymentRecives' => $license->paymentRecives,
            'payment_status' => $totalAmount < $license->license_cost ? 'partially paid' : 'paid',
                  'remaining_days'      => $remaining_day,
      'duration_day' => $this->getDurationDays(
    optional($license->durationService)->service_abbrv,
    $license->duration),
           'pro_rated_licenseCost' => $pro_rated_licenseCost,
            'gst_percentage'=>18,
            'contract_days' => $contract_days,
        ];
    });

    return response()->json([
        'status' => 'success',
        'message' => 'License listed successfully.',
        'data' => $formatted,
       
    ]);
}
protected function getDurationDays($abbrv, $durationCount)
{
    $daysPerUnit = match (strtoupper($abbrv)) {
        'D' => 1,
        'M' => 30,
        'Y' => 365,
        default => 0,
    };

    return $durationCount * $daysPerUnit;
}

}
