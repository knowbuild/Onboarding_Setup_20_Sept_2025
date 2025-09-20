<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\AdminRoleType;

class BusinessFunctionMasterController extends Controller
{
    /**
     * List all active Business Function Masters.
     */
    public function index()
    {
        $data = AdminRoleType::active()
            ->select('admin_role_id as id', 'admin_role_name as name', 'category', 'status')
            ->orderByDesc('admin_role_id')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Business Function Masters listed successfully.',
            'data'    => $data
        ], 200);
    }

    /**
     * Get details of a specific Business Function Master.
     */
    public function edit(Request $request)
    {
        $businessFunction = AdminRoleType::select('admin_role_id as id', 'admin_role_name as name', 'category', 'status')
            ->find($request->id);

        if (!$businessFunction) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Business Function Master not found.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Business Function Master details retrieved successfully.',
            'data'    => $businessFunction
        ], 200);
    }

    /**
     * Create or update Business Function Master.
     */
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'nullable|exists:tbl_admin_role_type,admin_role_id',
            'name'   => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        $isUpdate = !empty($request->id);

        if ($isUpdate) {
            AdminRoleType::where('admin_role_id', $request->id)
                ->update([
                    'admin_role_name' => $request->name,
                    'status'          => $request->status,
                    'updated_at'     => now(),
                ]);
        } else {
            AdminRoleType::create([
                'admin_role_name' => $request->name,
                'category'        => 'User Defined',
                'status'          => $request->status
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate
                ? 'Business Function Master updated successfully.'
                : 'Business Function Master created successfully.'
        ], 200);
    }

    /**
     * Soft delete a Business Function Master (User Defined only).
     */
    public function destroy(Request $request)
    {
        try {
            $id = $request->input('id');
            $data = AdminRoleType::findOrFail($id);

            if ($data->category === 'User Defined') {
                $data->deleteflag = 'inactive';
                $data->save();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Business Function Master deleted successfully.'
                ], 200);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Only User Defined categories can be deleted.'
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Business Function Master not found.'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the status of a Business Function Master (User Defined only).
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:tbl_admin_role_type,admin_role_id',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $data = AdminRoleType::findOrFail($request->id);

            if ($data->category === 'User Defined') {
                $data->status = $request->status;
                $data->save();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Status updated successfully.',
                    'data'    => $data
                ], 200);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Only User Defined categories can have their status updated.'
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Role not found.'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore System Defined Business Function Masters.
     */
    public function restore()
    {
        $adminRoleTypes = AdminRoleType::where('category', 'System Defined')->get();

        foreach ($adminRoleTypes as $roleType) {
            AdminRoleType::where('admin_role_id', $roleType->admin_role_id)
                ->update([
                    'admin_role_name' => $roleType->name,
                ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Business Function Masters restored successfully.'
        ], 200);
    }
}
