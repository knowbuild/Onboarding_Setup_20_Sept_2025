<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\{
    CompanyBranchAddress
}; 
class TaxRegisteredBranchesCompanyController extends Controller
{
  
    // Show 
   public function index(Request $request)
    {
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('record', 10);
        $data  = CompanyBranchAddress::active()
            ->select(
                'id',
                'location',
                'pan_no',
                'gst_no',
                'status',
            );

 $paginated = $data->orderByDesc('id')
        ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Tax-registered branches  listed successfully.',
            'data' => $paginated->items(),
            'pagination' => [
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
            ]

        ], 200);
    }


    //  Edit 
 public function edit(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_company_branch_address,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $branch = CompanyBranchAddress::active()
        ->select(
            'id',
            'company_name',
            'pan_no as pancard',
            'cin_no',
            'default_branch',
            'country',
            'state',
            'city',
            'address',
            'gst_no',
            'email_id as email',
            'location as branch_name',
            'head_office',
            'branch_office',
            'status'
        )
        ->where('id', $request->id)
        ->first();

    if (!$branch) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tax-registered branches ID not found'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Tax-registered branches retrieved successfully.',
        'data' => $branch
    ], 200);
}

    // Add and update 
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'nullable|exists:tbl_company_branch_address,id',
            'company_name'  => 'required',
            'address' => 'required',
            
 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        
     
       
        $isUpdate = !empty($request->id);

      CompanyBranchAddress::updateOrCreate(
            ['id' => $request->id],
            [
                'company_name'  => $request->company_name,
                'pan_no'  => $request->pancard,
                'cin_no' => $request->cin_no,
                'default_branch' => $request->default_branch,
                'country' => $request->country,
                'state'=> $request->state,
                'city' => $request->city,
                'address' => $request->address,
                'gst_no' => $request->gst_no,
                'email_id' => $request->email,
                'location' => $request->branch_name,
                'head_office' => $request->head_office,
                'branch_office' => $request->branch_office,
                'status' => $request->status,
                'updated_at'     => now(),
              
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Tax-registered branches updated successfully.' : 'Tax-registered branches created successfully.'
        ], 200);
    }
  
    // Delete 
    public function destroy(Request $request)
    {
       $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_company_branch_address,id',
          
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } 

        CompanyBranchAddress::find($request->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tax-registered branches deleted successfully.',
        ], 200);
    }
    // Update status
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_company_branch_address,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        CompanyBranchAddress::where('id', $request->id)->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => ' Tax-registered branches updated successfully.']);
    }
    
    
}
