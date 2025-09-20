<?php

namespace App\Http\Controllers\SalesManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\{
    TesManager,
    Tes,
    Application, 
    Product, 
    CustSegment,
    FinancialYear,
    Company,
    ProductMain,
    User, 
};

use Carbon\Carbon;
use App\Mail\SalesManager\TesManagerApproveMail;

class TESManagerController extends Controller
{
public function searchCustomers(Request $request)
{
    $validator = Validator::make($request->all(), [
        'search_key' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $search_key = $request->search_key;

    $companies = Company::select('id', 'comp_name', 'fname', 'lname', 'cust_segment', 'email', 'mobile_no')
        ->active()
        ->where(function ($query) use ($search_key) {
            $query->where(function ($q) use ($search_key) {
                $q->where('comp_name', 'like', "%$search_key%")
                  ->orWhere('fname', 'like', "%$search_key%")
                  ->orWhere('email', 'like', "%$search_key%")
                  ->orWhere('mobile_no', 'like', "%$search_key%");
            })->orWhereHas('comPerson', function ($q) use ($search_key) {
                $q->where('email', 'like', "%$search_key%")
                  ->orWhere('mobile_no', 'like', "%$search_key%");
            });
        })
    //    ->whereNotNull('cust_segment')
    //    ->where('cust_segment', '!=', 0)
        ->with([
            'custSegment:cust_segment_id,cust_segment_name',
            'comPerson:id,company_id,email,mobile_no'
        ])
        ->limit(10)
        ->get()
       // ->filter(function ($company) {
      //      return $company->cust_segment !== null && $company->cust_segment != 0;
      //  })
        ->map(function ($company) {
            return [
                'company_id'    => $company->id,
                'company_name'  => $company->comp_name,
                'customer_name' => trim($company->fname . ' ' . $company->lname),
                'segment_id'    => optional($company->cust_segment),
                'segment_name'  => optional($company->custSegment)->cust_segment_name ?? 'No Data',
                'email'         => optional($company->comPerson)->email ?? $company->email,
                'mobile_no'     => optional($company->comPerson)->mobile_no ?? $company->mobile_no,
            ];
        })->values(); // reset collection index

    return response()->json([
        'status'  => $companies->isNotEmpty() ? 'success' : 'no_data',
        'message' => $companies->isNotEmpty()
            ? 'Customers are listed here successfully.'
            : 'No customer found.',
        'data'    => $companies,
    ], 200);
}
          
            public function searchCustomerProduct(Request $request)
            {
                $validator = Validator::make($request->all(), [
                    'search_key' => 'required|string',
                ]);
            
                if ($validator->fails()) {
                    return response()->json(['errors' => $validator->errors()], 400);
                }
            
                $search_key = $request->search_key;

                $products = ProductMain::with('productsEntry:pro_id,pro_price_entry,model_no')
                ->select('pro_id', 'pro_title','upc_code')
                ->active()
                ->where('pro_title', 'like', "%{$search_key}%")
                      ->orWhere('upc_code', $search_key)
                ->limit(10)
                ->get()
                ->map(function ($product) {
                    return [
                        'upc_code'   => $product->upc_code,
                        'product_id'   => $product->pro_id,
                        'product_name' => $product->pro_title,
                        'price'        => optional($product->productsEntry)->pro_price_entry ?? 0,
                        'model_no' => optional($product->productsEntry)->model_no ?? 0,
                    ];
                });
            
            return response()->json([
                'status'  => 'success',
                'message' => 'Products are listed here successfully.',
                'data'    => $products,
            ], 200);
            
            
            
                return response()->json([
                    'status'  => $products->isNotEmpty() ? 'success' : 'no_data',
                    'message' => $products->isNotEmpty()
                        ? 'Products are listed here successfully.'
                        : 'No Product found.',
                    'data'    => $products,
                ], 200);
            }
public function saveTesManagerData(Request $request)
{
    $validated = $request->validate([
        'account_manager' => 'required|integer',
        'financial_year'  => 'required|integer',
        'price'           => 'required|numeric',
    ]);

    $accountManager = $validated['account_manager'];
    $financialYear  = $validated['financial_year'];
    $tesTarget      = $validated['price'];

    $existingRecord = TesManager::where('account_manager', $accountManager)
        ->where('financial_year', $financialYear)
        ->first();

    $discount = $existingRecord->discount ?? 0;

    $actualTarget = $discount == 0
        ? $tesTarget
        : $tesTarget - $discount;

    $data = [
        'tes_target'    => $tesTarget,
        'discount'      => $discount,
        'actual_target' => $actualTarget,
    ];

    if ($existingRecord) {
        TesManager::where('account_manager', $accountManager)
        ->where('financial_year', $financialYear)
->update($data);
        $message = "TES Manager data updated successfully.";
    } else {
        TesManager::create(array_merge([
            'account_manager' => $accountManager,
            'financial_year'  => $financialYear,
        ], $data));
        $message = "TES Manager data saved successfully.";
    }

    return response()->json([
        'status'         => 'success',
        'message'        => $message,
     //   'tes_target'     => $tesTarget,
     //   'discount'       => $discount,
     //   'actual_target'  => $actualTarget,
    ]);
}
                
            public function saveTesData(Request $request)
{
    $validator = Validator::make($request->all(), [
        'account_manager' => 'required|integer',
        'financial_year'  => 'required|integer',
        'send_approve'    => 'required|integer',
        'total_amount'    => 'required|numeric',
        'targetCustomers' => 'required|array|min:1',
        'targetCustomers.*.company_id'    => 'required|integer',
        'targetCustomers.*.company_name'  => 'required|string',
        'targetCustomers.*.items'         => 'required|array|min:1',
        'targetCustomers.*.items.*.product_id'   => 'required|integer',
        'targetCustomers.*.items.*.product_name' => 'required|string',
        'targetCustomers.*.items.*.quantity'     => 'required|numeric',
        'targetCustomers.*.items.*.discount'     => 'required|numeric',
        'targetCustomers.*.items.*.price'        => 'required|numeric',
        'targetCustomers.*.items.*.total_price'  => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $accountManager  = $request->account_manager;
    $financialYear   = $request->financial_year;
    $sendApprove     = $request->send_approve;
    $tesTargetAmount = $request->total_amount;

    $existingRecord = TesManager::where('account_manager', $accountManager)
        ->where('financial_year', $financialYear)
        ->first();

    $discount = $existingRecord?->discount ?? 0;
    $discountAmount = $discount > 0 ? ($discount / $tesTargetAmount) * 100 : 0;
    $actualTarget = $tesTargetAmount - $discountAmount;

    if ($existingRecord) {
        $existingRecord->update([
            'tes_target'    => $tesTargetAmount,
            'discount'      => $discount,
            'actual_target' => $actualTarget,
            'save_send'     => $sendApprove === 1 ? 'yes' : 'no',
        ]);
        $tesManager = $existingRecord;
    } else {
        $tesManager = TesManager::create([
            'account_manager' => $accountManager,
            'financial_year'  => $financialYear,
            'tes_target'      => $tesTargetAmount,
            'discount'        => $discount,
            'actual_target'   => $actualTarget,
            'save_send'       => $sendApprove === 1 ? 'yes' : 'no',
        ]);
    }

    // Delete old TES entries
    Tes::where('tes_id', $tesManager->ID)->delete();

    foreach ($request->targetCustomers as $customer) {
        $segment_id = is_numeric($customer['segment_id'] ?? null) ? $customer['segment_id'] : 0;
        $segment_name = !empty($customer['segment_name']) ? $customer['segment_name'] : 'no data';

        foreach ($customer['items'] as $item) {
            Tes::create([
                'tes_id'             => $tesManager->ID,
                'pro_id'             => $item['product_id'],
                'comp_id'            => $customer['company_id'],
                'account_manager'    => $accountManager,
                'comp_name'          => $customer['company_name'],
                'cosegmentid'        => $segment_id,
                'cosegment'          => $segment_name,
                'pro_name'           => $item['product_name'],
                'quantity'           => $item['quantity'],
                'price'              => $item['price'],
                'discount'           => $item['discount'],
                'sub_total'          => $item['total_price'],
                'cust_classification'=> 'key',
            ]);
        }
    }

    // Send approval email if required
    if ($sendApprove === 1) {
        $team = User::where('admin_id', $accountManager)->first();

        if ($team) {
            $teamEmailData = User::where('admin_id', $team->admin_team)->first();
            $teamEmail = $teamEmailData->admin_email ?? null;

            if ($teamEmail) {
                $accountManagerName = trim("{$team->admin_fname} {$team->admin_lname}");
                $financialYearName = FinancialYear::find($financialYear)?->fin_name;

                $tesData = Tes::where('tes_id', $tesManager->ID)
                    ->get()
                    ->groupBy('comp_id')
                    ->map(function ($grouped) {
                        $first = $grouped->first();

                        return [
                            'company_id'   => $first->comp_id,
                            'company_name' => $first->comp_name,
                            'segment_id'   => $first->cosegmentid ?? 0,
                            'segment_name' => $first->cosegment ?? 'no data',
                            'items'        => $grouped->map(fn($item) => [
                                'product_id'   => $item->pro_id,
                                'product_name' => $item->pro_name,
                                'quantity'     => $item->quantity,
                                'discount'     => $item->discount,
                                'price'        => $item->price,
                                'total_price'  => $item->sub_total,
                            ])->values(),
                        ];
                    })->values();

                Mail::to($teamEmail)->send(
                    new TesManagerApproveMail($tesManager, $tesData, $accountManagerName, $financialYearName)
                );
            }
        }
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'TES data saved successfully.',
    ]);
}

            
public function getTesDataById(Request $request)
{
    $request->validate([
        'id' => 'required|integer',
    ]);

    $id = $request->id;

    $tesManager = TesManager::where('ID', $id)->first();

    if (!$tesManager) {
        return response()->json([
            'status' => 'error',
            'message' => 'TES Manager record not found.'
        ], 404);
    }

    $tesEntries = Tes::where('tes_id', $id)
        ->get()
        ->groupBy('comp_id')
        ->map(function ($grouped) {
            $first = $grouped->first();

            // Sort items by product_name ASC
            $items = $grouped->sortBy('pro_name')->values()->map(function ($item) {
                return [
                    'product_id'   => $item->pro_id,
                    'product_name' => $item->pro_name,
                    'quantity'     => $item->quantity,
                    'discount'     => $item->discount,
                    'price'        => $item->price,
                    'total_price'  => $item->sub_total,
                ];
            });

            return [
                'company_id'   => $first->comp_id,
                'company_name' => $first->comp_name,
                'segment_id'   => $first->cosegmentid,
                'segment_name' => $first->cosegment,
                'items'        => $items
            ];
        })
        // Sort companies by company_name ASC
        ->sortBy('company_name')
        ->values();

    return response()->json([
        'status' => 'success',
        'data' => [
            'id'              => $tesManager->ID,
            'account_manager' => $tesManager->account_manager,
            'financial_year'  => $tesManager->financial_year,
            'targetCustomers' => $tesEntries
        ]
    ], 200);
}




public function statusTesManagerData(Request $request)
{
    $request->validate([
        'id'          => 'required|integer',
        'status'      => 'required|string|max:50',
        'approved_by' => 'required|integer',
    ]);

    // Base data to update
    $updateData = [
        'approved_on' => Carbon::now(),
        'approved_by' => $request->approved_by,
        'status'      => $request->status,
    ];

    // Only include status_update_reason if status is rejected
    if ($request->status === 'rejected') {
        $request->validate([
            'note_remark' => 'required|string|max:500',
        ]);
        $updateData['status_update_reason'] = $request->note_remark;
    }

    $updated = TesManager::where('ID', $request->id)->update($updateData);

    return response()->json([
        'success'              => $updated ? 'success' : 'unsuccess',
        'message'              => $updated ? 'Status updated successfully!' : 'Failed to update status.',
        'status_update_reason' => $updateData['status_update_reason'] ?? null,
    ], 200);
}

public function deleteTesManagerData(Request $request)
{
    $id = $request->id;

   Tes::where('tes_id', $id)->delete();
   $data = [
    'status_update_reason' => $request->delete_remark,
    'deleteflag' => 'inactive',
];
  $delete = TesManager::where('ID', $id)->update($data);
    
    return response()->json([ 'success' => $delete ? 'success' : 'unsuccess', 'message' => $delete ? 'Successfully Deleted!' : 'Error Occurred!' ],200);
}

public function deleteTesData(Request $request)
    {
        $id = $request->id;

     $delete = Tes::where('ID', $id)->delete();
        return response()->json([ 'success' => $delete ? 'success' : 'unsuccess', 'message' => $delete ? 'Successfully Deleted!' : 'Error Occurred!' ],200);
    }

    public function discountTesManagerData(Request $request)
    {
        $request->validate([
            'id'       => 'required|integer',
            'discount' => 'required|numeric|min:0',
        ]);
    $id = $request->id;
        $tesManager = TesManager::find($request->id);
    
        if (!$tesManager) {
            return response()->json([
                'success' => 'unsuccess',
                'message' => 'TES Manager not found.'
            ], 404);
        }
    
        $tesTargetAmount = $tesManager->tes_target;
        $discount        = $request->discount;
    
        // Calculate discount amount only if target is greater than 0
        $discountAmount  = $tesTargetAmount > 0 ? ($tesTargetAmount * $discount / 100) : 0;
        $actualTarget    = $tesTargetAmount - $discountAmount;
 
        $updated = TesManager::where('ID',$id)->update([
            'tes_target'     => $tesTargetAmount,
            'discount'       => $discount,
            'actual_target'  => $actualTarget,
        ]);
    
        return response()->json([
            'success' => $updated ? 'success' : 'unsuccess',
            'message' => $updated ? 'TES data updated successfully!' : 'Failed to update TES data.',
  'discount' => $discount,
        ], 200);
    }
  
    

    
    
}
 