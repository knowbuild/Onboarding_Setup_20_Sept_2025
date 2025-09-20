<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use App\Models\TargetAccountManagerProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TargetAccountManagerProductController extends Controller
{
    // GET /target-account-manager-products/listing
    public function index()
    {
        $products = TargetAccountManagerProduct::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Target account manager products retrieved successfully.',
            'data' => $products,
        ]);
    }

    // GET /target-account-manager-products/edit?id=1
    public function edit(Request $request)
    {
        $product = TargetAccountManagerProduct::find($request->id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Target account manager product not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Target account manager product retrieved.',
            'data' => $product,
        ]);
    }

    // POST /target-account-manager-products/store-update
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_account_manager_id' => 'nullable|integer|exists:target_account_managers,id',
            'customer_id'               => 'nullable|integer|exists:customers,id',
            'segment_id'                => 'nullable|integer|exists:segments,id',
            'product_id'                => 'nullable|integer|exists:products,id',
            'quantity'                  => 'required|integer|min:0',
            'discount'                  => 'required|numeric|min:0',
            'price'                     => 'required|numeric|min:0',
            'total_price'               => 'required|numeric|min:0',
            'account_manger_id'         => 'nullable|integer|exists:users,id',
            'financial_year_id'         => 'nullable|integer|exists:financial_years,id',
            'status'                   => 'in:active,inactive',
            'approved_status'          => 'in:pending,rejected,approved',
            'status_update_reason'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = TargetAccountManagerProduct::updateOrCreate(
            ['id' => $request->id],
            $request->only([
                'target_account_manager_id',
                'customer_id',
                'segment_id',
                'product_id',
                'quantity',
                'discount',
                'price',
                'total_price',
                'account_manger_id',
                'financial_year_id',
                'status',
                'approved_status',
                'status_update_reason',
            ])
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Target account manager product saved successfully.',
            'data' => $product,
        ]);
    }

    // POST /target-account-manager-products/destroy/{id}
    public function destroy($id)
    {
        $product = TargetAccountManagerProduct::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Target account manager product not found.',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Target account manager product deleted successfully.',
        ]);
    }

    // POST /target-account-manager-products/status/{id}
    public function updateStatus(Request $request, $id)
    {
        $product = TargetAccountManagerProduct::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Target account manager product not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status'              => 'required|in:active,inactive',
            'approved_status'     => 'required|in:pending,rejected,approved',
            'status_update_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product->status = $request->status;
        $product->approved_status = $request->approved_status;
        $product->status_update_reason = $request->status_update_reason ?? $product->status_update_reason;
        $product->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Target account manager product status updated successfully.',
            'data' => $product,
        ]);
    }
}
