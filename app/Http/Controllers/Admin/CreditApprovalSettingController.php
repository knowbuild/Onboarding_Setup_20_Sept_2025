<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CreditApprovalSetting;
use Illuminate\Support\Facades\Validator;

class CreditApprovalSettingController extends Controller
{
    /**
     * List all settings.
     */
    public function index()
    {
        $settings = CreditApprovalSetting::orderBy('id', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Credit & Approval settings retrieved successfully.',
            'data' => $settings
        ], 200);
    }

    /**
     * Show specific setting.
     */
    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:credit_approval_settings,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $setting = CreditApprovalSetting::find($request->id);

        if (!$setting) {
            return response()->json(['status' => 'error', 'message' => 'Setting not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $setting], 200);
    }

    /**
     * Store or update a setting.
     */
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'      => 'nullable|exists:credit_approval_settings,id',
            'name'    => 'required|string|max:100',
            'details' => 'nullable|string',
            'approval'=> 'required|in:active,inactive',
            'status'  => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $isUpdate = !empty($request->id);

        CreditApprovalSetting::updateOrCreate(
            ['id' => $request->id],
            [
                'name'    => $request->name,
                'details' => $request->details,
                'approval'=> $request->approval,
                'status'  => $request->status
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate ? 'Setting updated successfully.' : 'Setting created successfully.'
        ], 200);
    }

    /**
     * Delete setting.
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:credit_approval_settings,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        CreditApprovalSetting::where('id', $request->id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Setting deleted successfully.'], 200);
    }

    /**
     * Toggle approval status only.
     */
    public function updateApproval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'      => 'required|exists:credit_approval_settings,id',
            'approval'=> 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        CreditApprovalSetting::where('id', $request->id)->update(['approval' => $request->approval]);

        return response()->json(['status' => 'success', 'message' => 'Approval updated successfully.'], 200);
    }
}
