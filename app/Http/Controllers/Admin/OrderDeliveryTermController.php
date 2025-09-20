<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\SupplyOrderDeliveryTermsMaster;

class OrderDeliveryTermController extends Controller
{
    public function index()
    {
        $terms = SupplyOrderDeliveryTermsMaster::select(
                'supply_order_delivery_terms_id as id',
                'supply_order_delivery_terms_name as name',
                'supply_order_delivery_terms_status as status',
                'supply_order_delivery_terms_abbrv as days'
            )
            ->orderByDesc('supply_order_delivery_terms_id')
            ->get();
 
        return response()->json([
            'status' => 'success',
            'message' => 'Delivery terms listed successfully.',
            'data' => $terms
        ], 200);
    }

    public function edit(Request $request)
    {
        $term = SupplyOrderDeliveryTermsMaster::select(
                'supply_order_delivery_terms_id as id',
                'supply_order_delivery_terms_name as name',
                'supply_order_delivery_terms_status as status',
                'supply_order_delivery_terms_abbrv as days'
            )
            ->find($request->id);

        if (!$term) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delivery term not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery term details retrieved successfully.',
            'data' => $term
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'days'   => 'required',
            'id'     => 'nullable|exists:tbl_supply_order_delivery_terms_master,supply_order_delivery_terms_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        SupplyOrderDeliveryTermsMaster::updateOrCreate(
            ['supply_order_delivery_terms_id' => $request->id],
            [
                'supply_order_delivery_terms_name'   => $request->name,
                'supply_order_delivery_terms_status' => $request->status ?? 'active',
                'supply_order_delivery_terms_abbrv'  => $request->days,
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate
                ? 'Delivery term updated successfully.'
                : 'Delivery term created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $record = SupplyOrderDeliveryTermsMaster::find($request->id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delivery term not found.'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery term deleted successfully.'
        ], 200);
    }
 
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id'     => 'required|exists:tbl_supply_order_delivery_terms_master,supply_order_delivery_terms_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = SupplyOrderDeliveryTermsMaster::find($request->id);
        $record->supply_order_delivery_terms_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery term status updated successfully.'
        ], 200);
    }
}
