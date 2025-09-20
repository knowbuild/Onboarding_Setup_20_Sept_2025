<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\VendorPaymentTermsMaster;
 
class VendorPaymentTermController extends Controller
{
    public function index(Request $request)
{
    try {
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('record', 100);

        $query = VendorPaymentTermsMaster::select(
                'vendor_payment_terms_id as id',
                'vendor_payment_terms_name as name',
                'vendor_payment_terms_status as status',
                'vendor_payment_terms_abbrv as days'
            )
            ->orderByDesc('vendor_payment_terms_id');

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status'     => 'success',
            'message'    => 'Payment terms listed successfully.',
            'data'       => $paginated->items(),
            'pagination' => [
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while fetching payment terms.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

  
    public function edit(Request $request)
    {
        $term = VendorPaymentTermsMaster::select(
                'vendor_payment_terms_id as id',
                'vendor_payment_terms_name as name',
                'vendor_payment_terms_status as status',
                'vendor_payment_terms_abbrv as days'
            )
            ->find($request->id);

        if (!$term) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment term not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment term details retrieved successfully.',
            'data' => $term
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'status'          => 'required|in:active,inactive',
            'days' => 'required',
            'id'              => 'nullable|exists:tbl_vendor_payment_terms_master,vendor_payment_terms_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        VendorPaymentTermsMaster::updateOrCreate(
            ['vendor_payment_terms_id' => $request->id],
            [
                'vendor_payment_terms_name'   => $request->name,
                'vendor_payment_terms_status' => $request->status ?? 'active',
                'vendor_payment_terms_abbrv'  => $request->days,
                'updated_at'                  => now(),
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate
                ? 'Payment term updated successfully.'
                : 'Payment term created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $record = VendorPaymentTermsMaster::find($request->id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment term not found.'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment term deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id'     => 'required|exists:tbl_vendor_payment_terms_master,vendor_payment_terms_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = VendorPaymentTermsMaster::find($request->id);
        $record->vendor_payment_terms_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment term status updated successfully.'
        ], 200);
    }
}
