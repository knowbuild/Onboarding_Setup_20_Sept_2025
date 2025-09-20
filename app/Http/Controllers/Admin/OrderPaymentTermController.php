<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\SupplyOrderPaymentTermsMaster;

class OrderPaymentTermController extends Controller
{
    public function index()
    {
        $terms = SupplyOrderPaymentTermsMaster::select(
                'supply_order_payment_terms_id as id',
                'supply_order_payment_terms_name as name',
                'supply_order_payment_terms_status as status',
                'supply_order_payment_terms_abbrv as days',
                'approval_status'
            )
            ->orderByDesc('supply_order_payment_terms_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Order Payment terms listed successfully.',
            'data' => $terms
        ], 200);
    }
   
    public function edit(Request $request)
    {
        $term = SupplyOrderPaymentTermsMaster::select(
                'supply_order_payment_terms_id as id',
                'supply_order_payment_terms_name as name',
                'supply_order_payment_terms_status as status',
                'supply_order_payment_terms_abbrv as days',
                'approval_status'
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
            'message' => 'Order Payment term details retrieved successfully.',
            'data' => $term
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'status'          => 'nullable|in:active,inactive',
            'approval_status' => 'nullable|in:active,inactive',
            'days'            => 'required',
            'id'              => 'nullable|exists:tbl_supply_order_payment_terms_master,supply_order_payment_terms_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        SupplyOrderPaymentTermsMaster::updateOrCreate(
            ['supply_order_payment_terms_id' => $request->id],
            [
                'supply_order_payment_terms_name'   => $request->name,
                'supply_order_payment_terms_status' => $request->status ?? 'active',
                'approval_status'                  => $request->approval_status ?? 'inactive',
                'supply_order_payment_terms_abbrv'  => $request->days,
            ]
        );
 
        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate
                ? 'Order Payment term updated successfully.'
                : 'Order Payment term created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $record = SupplyOrderPaymentTermsMaster::find($request->id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order Payment term not found.'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Order Payment term deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id'     => 'required|exists:tbl_supply_order_payment_terms_master,supply_order_payment_terms_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = SupplyOrderPaymentTermsMaster::find($request->id);
        $record->supply_order_payment_terms_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order Payment term status updated successfully.'
        ], 200);
    }
}
