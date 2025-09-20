<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\GstSaleTypeMaster;

class TaxRateController extends Controller
{
    public function index()
    {
        $tax_rates = GstSaleTypeMaster::active()->select(
            'gst_sale_type_id as id',
            'gst_sale_type_tax_per as tax_percent',
            'gst_sale_type_status as status',
        )
        ->orderByDesc('gst_sale_type_id')
        ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Tax rates listed successfully.',
            'data' => $tax_rates
        ], 200);
    }

    public function edit(Request $request)
    {
        $tax_rate = GstSaleTypeMaster::select(
            'gst_sale_type_id as id',
            'gst_sale_type_tax_per as tax_percent',
            'gst_sale_type_status as status',
        )
        ->find($request->id);

        if (!$tax_rate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tax rate not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tax rate details retrieved successfully.',
            'data' => $tax_rate
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'tax_percent' => 'required|string|max:55',
            'status' => 'nullable|in:active,inactive',
            'id' => 'nullable|exists:tbl_gst_sale_type_master,gst_sale_type_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        // Avoid duplicate entry on create
        if (!$isUpdate && GstSaleTypeMaster::where('gst_sale_type_tax_per', $request->tax_percent)->exists()) {
            return response()->json(['errors' => 'Exact same tax rate already exists.'], 400);
        }

        $data = [
            'gst_sale_type_name' => $request->tax_percent . '%',
            'gst_sale_type_tax_per' => $request->tax_percent,
            'gst_sale_type_status' => $request->status ?? 'active',
            'gst_sale_type_description' => $request->tax_percent . '%',
            'updated_at' => now(),
        ];

        if ($isUpdate) {
            GstSaleTypeMaster::where('gst_sale_type_id', $request->id)->update($data);
        } else {
            GstSaleTypeMaster::create($data);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Tax rate updated successfully.' : 'Tax rate created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $record = GstSaleTypeMaster::find($request->id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tax rate not found.'
            ], 404);
        }

        $record->deleteflag = 'inactive';
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Tax rate deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id' => 'required|exists:tbl_gst_sale_type_master,gst_sale_type_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = GstSaleTypeMaster::find($request->id);
        $record->gst_sale_type_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Tax rate status updated successfully.'
        ], 200);
    }
}
  