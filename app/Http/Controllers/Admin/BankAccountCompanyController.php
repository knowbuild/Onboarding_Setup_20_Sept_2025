<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\{
    CompanyBankAddress
};
class BankAccountCompanyController extends Controller
{
   
    // Show 
   public function index(Request $request)
    {
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('record', 10);
        
        $data  = CompanyBankAddress::active()
            ->select(
                'bank_id as id',
                'bank_name',
                'account_holder_name',
                'bank_acc_no as account_no',
                'ifsc_code',
                'bank_address',
                'swift_code',
                'status',
            );

        $paginated = $data->orderByDesc('bank_id')
        ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Bank Account  listed successfully.',
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
            'id' => 'required|exists:tbl_company_bank_address,bank_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = CompanyBankAddress::active()
            ->select(
                'bank_id as id',
                'bank_name',
                'account_holder_name',
                'bank_acc_no as account_no',
                'ifsc_code',
                'bank_address',
                'swift_code',
                'status',
            )
            ->where("bank_id",$request->id)
            ->first();

            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Bank Account ID not found'], 404);
            };
            

        return response()->json([
            'status' => 'success',
            'message' => 'Bank Account retrieved successfully.',
            'data' => $data
        ], 200);
    }


    // Add and update 
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'nullable|exists:tbl_company_bank_address,bank_id',
            'bank_name'  => 'required|string',
            'account_holder_name'  => 'required|string',
            'account_no'  => 'required',
            'ifsc_code'  => 'required',
            'bank_address'  => 'required',
            'status'  => 'required|in:active,inactive',
            
 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        
     
      
        $isUpdate = !empty($request->id);

      CompanyBankAddress::updateOrCreate(
            ['bank_id' => $request->id],
            [
                'bank_name'  => $request->bank_name,
                'account_holder_name'  => $request->account_holder_name,
                'bank_acc_no' => $request->account_no,
                'ifsc_code' => $request->ifsc_code,
                'bank_address' => $request->bank_address,
                'swift_code'=> $request->swift_code,
                'status' => $request->status,
              
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Bank Account updated successfully.' : 'Bank Account created successfully.'
        ], 200);
    }
  
    // Delete 
    public function destroy(Request $request)
    {
       $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_company_bank_address,bank_id',
          
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } 

        CompanyBankAddress::find($request->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Bank Account deleted successfully.',
        ], 200);
    }
    // Update status
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_company_bank_address,bank_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        CompanyBankAddress::where('bank_id', $request->id)->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => ' Bank Account updated successfully.']);
    }
    
    
}
