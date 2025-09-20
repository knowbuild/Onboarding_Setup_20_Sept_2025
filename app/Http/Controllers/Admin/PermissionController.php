<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{

    public function index()
    {
        $permissions = Permission::orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Permission list retrieved successfully.',
            'data' => $permissions,
        ]);
    }

    public function edit(Request $request)
    {
        $permission = Permission::find($request->id);

        if (!$permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permission data retrieved successfully.',
            'data' => $permission,
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'name'    => 'required|string|max:100',
            'url'     => 'required|string|max:200',
            'create'  => 'in:0,1',
            'edit'    => 'in:0,1',
            'view'    => 'in:0,1',
            'delete'  => 'in:0,1',
            'status'  => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission = Permission::updateOrCreate(
            ['id' => $request->id],
            $request->all()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Permission saved successfully.',
            'data' => $permission,
        ]);
    }

    public function destroy($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission not found.',
            ], 404);
        }

        $permission->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Permission deleted successfully.',
        ]);
    }

    public function updateStatus($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission not found.',
            ], 404);
        }

        $permission->status = $permission->status === 'active' ? 'inactive' : 'active';
        $permission->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Permission status updated successfully.',
            'data' => $permission,
        ]);
    }
}
