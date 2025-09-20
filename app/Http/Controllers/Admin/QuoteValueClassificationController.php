<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\KeyCustomerMaster;

class QuoteValueClassificationController extends Controller
{
    /**
     * List all quote value classifications.
     */
    public function index(Request $request)
    {
        $items = KeyCustomerMaster::query()
            ->select([
                'key_customer_id  as id',
                'key_customer_name as name',
                'min_value as price_min',
                'max_value as price_max',
                'key_customer_status as status',
            ])
            ->orderByDesc('key_customer_id')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Quote value classifications listed successfully.',
            'data'    => $items,
        ], 200);
    }

    /**
     * Get a single record for editing.
     */
    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_key_customer_master,key_customer_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $item = KeyCustomerMaster::query()
            ->where('key_customer_id', $request->id)
            ->select([
                'key_customer_id  as id',
                'key_customer_name as name',
                'min_value as price_min',
                'max_value as price_max',
                'key_customer_status as status',
            ])
            ->first();

        if (!$item) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Quote value classification not found.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Quote value classification details retrieved successfully.',
            'data'    => $item,
        ], 200);
    }

    /**
     * Create or update a record.
     */
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'        => 'nullable|exists:tbl_key_customer_master,key_customer_id',
            'name'      => 'required|string|max:100',
            'price_min' => 'required|integer|min:0',
            'price_max' => 'nullable|integer|min:0',
            'status'    => 'nullable|in:active,inactive',
        ]);

        // Ensure price_max >= price_min when provided
        $validator->after(function ($v) use ($request) {
            if (!is_null($request->price_max) && $request->price_max < $request->price_min) {
                $v->errors()->add('price_max', 'The maximum price must be greater than or equal to the minimum price.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $isUpdate = filled($request->id);

        $attributes = [
            'key_customer_name'   => $request->name,
            'min_value'           => $request->price_min,
            'max_value'           => $request->price_max,
            'key_customer_status' => $request->status ?? 'active',
        ];

        $item = KeyCustomerMaster::updateOrCreate(
            ['key_customer_id' => $request->id],
            $attributes
        );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate
                ? 'Quote value classification updated successfully.'
                : 'Quote value classification created successfully.',
            'data'    => [
                'id'        => $item->key_customer_id,
                'name'      => $item->key_customer_name,
                'price_min' => $item->min_value,
                'price_max' => $item->max_value,
                'status'    => $item->key_customer_status,
            ],
        ], 200);
    }

    /**
     * Delete a record.
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_key_customer_master,key_customer_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $item = KeyCustomerMaster::where('key_customer_id', $request->id)->first();

        if (!$item) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Quote value classification not found.',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Quote value classification deleted successfully.',
        ], 200);
    }

    /**
     * Update only the status.
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:tbl_key_customer_master,key_customer_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $item = KeyCustomerMaster::where('key_customer_id', $request->id)->first();

        if (!$item) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Quote value classification not found.',
            ], 404);
        }

        $item->key_customer_status = $request->status;
        $item->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Quote value classification status updated successfully.',
            'data'    => [
                'id'     => $item->key_customer_id,
                'status' => $item->key_customer_status,
            ],
        ], 200);
    }
}
