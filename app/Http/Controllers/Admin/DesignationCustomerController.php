<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DesignationComp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DesignationCustomerController extends Controller
{
    /**
     * List all designations
     */
    public function index(Request $request)
    {
        $designations = DesignationComp::select(
                'designation_id as id',
                'designation_name as name',
                'designation_status as status'
            )
            ->orderByDesc('designation_id')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Designations listed successfully.',
            'data'    => $designations
        ], 200);
    }

    /**
     * Get single designation details
     */
    public function edit(Request $request)
    {
        $designation = DesignationComp::select(
                'designation_id as id',
                'designation_name as name',
                'designation_status as status'
            )
            ->find($request->id);

        if (!$designation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Designation not found.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Designation details retrieved successfully.',
            'data'    => $designation
        ], 200);
    }

    /**
     * Store or update designation
     */
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'             => 'nullable|exists:tbl_designation_comp,designation_id',
            'name'           => 'required|string|max:100',
            'status'         => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'errors'  => $validator->errors()
            ], 400);
        }

        $isUpdate = !empty($request->id);

        $designation = DesignationComp::updateOrCreate(
            ['designation_id' => $request->id],
            [
                'designation_name'  => $request->name,
                'designation_status'=> $request->status ?? 'active',
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate 
                ? 'Designation updated successfully.' 
                : 'Designation created successfully.',
           // 'data'    => $designation
        ], 200);
    }

    /**
     * Delete a designation
     */
    public function destroy(Request $request)
    {
        $designation = DesignationComp::find($request->id);

        if (!$designation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Designation not found.'
            ], 404);
        }

        $designation->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Designation deleted successfully.'
        ], 200);
    }

    /**
     * Update status (active/inactive)
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:tbl_designation_comp,designation_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'errors'  => $validator->errors()
            ], 400);
        }

        $designation = DesignationComp::find($request->id);
        $designation->designation_status = $request->status;
        $designation->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Designation status updated successfully.',
            'data'    => $designation
        ], 200);
    }
}
