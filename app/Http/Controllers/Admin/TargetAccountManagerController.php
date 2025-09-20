<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use App\Models\TargetAccountManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TargetAccountManagerController extends Controller
{
    // GET /target-account-managers/listing
    public function index()
    {
        $targets = TargetAccountManager::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Target account managers retrieved successfully.',
            'data' => $targets,
        ]);
    }

    // GET /target-account-managers/edit?id=1
    public function edit(Request $request)
    {
        $target = TargetAccountManager::find($request->id);

        if (!$target) {
            return response()->json([
                'status' => 'error',
                'message' => 'Target account manager not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Target account manager retrieved.',
            'data' => $target,
        ]);
    }

    // POST /target-account-managers/store-update
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_manger_id'    => 'nullable|integer|exists:users,id',
            'financial_year_id'    => 'nullable|integer|exists:financial_years,id',
            'new_target_amount'    => 'required|numeric|min:0',
            'discount_amount'      => 'required|numeric|min:0',
            'target_amount'        => 'required|numeric|min:0',
            'approved_by'          => 'nullable|integer|exists:users,id',
            'send_mail'            => 'in:0,1',
            'status'               => 'in:active,inactive,rejected,approved',
            'approved_status'      => 'in:pending,rejected,approved',
            'status_update_reason' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $target = TargetAccountManager::updateOrCreate(
            ['id' => $request->id],
            $request->only([
                'account_manger_id',
                'financial_year_id',
                'new_target_amount',
                'discount_amount',
                'target_amount',
                'approved_by',
                'approved_at',
                'send_mail',
                'status',
                'approved_status',
                'status_update_reason',
            ])
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Target account manager saved successfully.',
            'data' => $target,
        ]);
    }

    // POST /target-account-managers/destroy/{id}
    public function destroy($id)
    {
        $target = TargetAccountManager::find($id);

        if (!$target) {
            return response()->json([
                'status' => 'error',
                'message' => 'Target account manager not found.',
            ], 404);
        }

        $target->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Target account manager deleted successfully.',
        ]);
    }

    // POST /target-account-managers/status/{id}
    public function updateStatus(Request $request, $id)
    {
        $target = TargetAccountManager::find($id);

        if (!$target) {
            return response()->json([
                'status' => 'error',
                'message' => 'Target account manager not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,rejected,approved',
            'approved_status' => 'required|in:pending,rejected,approved',
            'status_update_reason' => 'nullable|string|max:200',
            'approved_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $target->status = $request->status;
        $target->approved_status = $request->approved_status;
        $target->status_update_reason = $request->status_update_reason ?? $target->status_update_reason;
        $target->approved_at = $request->approved_at ?? $target->approved_at;
        $target->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Target account manager status updated successfully.',
            'data' => $target,
        ]);
    }
}
