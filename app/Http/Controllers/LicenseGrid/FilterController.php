<?php

namespace App\Http\Controllers\LicenseGrid;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\{
    Segment,
    Customer,
User
};

class FilterController extends Controller
{
   // CustomerController.php
public function accountManagers()
{
    $accountManagerIds = Customer::select('account_manger_id')
        ->distinct()
        ->pluck('account_manger_id');

    $accountManagers = User::whereIn('id', $accountManagerIds)
        ->select('id', 'name')
        ->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Account Managers listed successfully.',
        'data' => $accountManagers
    ], 200);
}

public function accountCreatedBy()
{
    $accountCreatedByIds = Customer::select('created_by_id')
        ->distinct()
        ->pluck('created_by_id');

    $accountCreatedBy = User::whereIn('id', $accountCreatedByIds)
        ->select('id', 'name')
        ->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Account Created By listed successfully.',
        'data' => $accountCreatedBy
    ], 200);
}
   
    public function accountType()
{
    $data = [
        ['label' => 'Trial', 'value' => 'trial'],
        ['label' => 'Paid', 'value' => 'paid'],
    ];

    return response()->json([
        'status' => 'success',
        'message' => 'Account types listed successfully.',
        'data' => $data
    ]);
}
public function expiringIn()
{
    $data = [
        ['label' => '1 Week', 'value' => 7],
        ['label' => '2 Weeks', 'value' => 14],
        ['label' => '3 Weeks', 'value' => 21],
        ['label' => '1 Month', 'value' => 30],
        ['label' => '2 Months', 'value' => 60],
        ['label' => '3 Months', 'value' => 90],
    ];

    return response()->json([
        'status' => 'success',
        'message' => 'Expiring options listed successfully.',
        'data' => $data
    ]);
}
public function companyStatus()
{
    $data = [
        ['label' => 'Pending', 'value' => 'pending'],
        ['label' => 'Approved', 'value' => 'approved'],
        ['label' => 'Hold', 'value' => 'hold'],
        ['label' => 'Expired', 'value' => 'expired'],
        ['label' => 'Rejected', 'value' => 'rejected'],

    ];

    return response()->json([
        'status' => 'success',
        'message' => 'Company Status listed successfully.',
        'data' => $data
    ]);
}

public function teamDirectorySearch()
{
    $data = [
        ['label' => 'Last month', 'value' => 'LastMonth'],
        ['label' => 'Last 3 months', 'value' => 'Last3Months'],
        ['label' => 'Last 6 months', 'value' => 'Last6Months'],
        ['label' => 'Current year', 'value' => 'CurrentYear'],
        ['label' => 'Previous year', 'value' => 'PreviousYear'],
    ];

    return response()->json([
        'status' => 'success',
        'message' => 'Team Directory Search options listed successfully.',
        'data' => $data
    ]);
}

public function teamDirectorySort()
{
    $data = [
        ['label' => 'Relevant', 'value' => 'Relevant'],
        ['label' => 'Naming: A to Z', 'value' => 'AToZ'],
        ['label' => 'Naming: Z to A', 'value' => 'ZToA'],
        ['label' => 'Created date: Old to New', 'value' => 'OldToNew'],
        ['label' => 'Created date: New to Old', 'value' =>'NewToOld'],
      
    ];

    return response()->json([
        'status' => 'success',
        'message' => 'Team Directory Sort options listed successfully.',
        'data' => $data
    ]);
}

 public function segments(Request $request)
    {
       $validator = Validator::make($request->all(), [
     'account_manger_id' => 'required',

    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
  }
        $segments = Segment::select('id', 'name')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Segments listed successfully.',
            'data' => $segments
        ], 200);
    }

        public function categories(Request $request)
    {
             $validator = Validator::make($request->all(), [
     'account_manger_id' => 'required',

    ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
     $categories = Category::select('id', 'name')->get();
    

        return response()->json([
            'status' => 'success',
            'message' => 'Categories listed successfully.',
            'data' => $categories
        ], 200);
    } 

public function accountStatus()
{
    $data = [
        ['value' => 'pending',  'label' => 'Pending'],
        ['value' => 'approved', 'label' => 'Approved'],
        ['value' => 'hold',     'label' => 'Hold'],
        ['value' => 'expired',  'label' => 'Expired'],
        ['value' => 'rejected', 'label' => 'Rejected'],
    ];

    return response()->json([
        'status'  => 'success',
        'message' => 'Account status options listed successfully.',
        'data'    => $data
    ]);
}


}
 