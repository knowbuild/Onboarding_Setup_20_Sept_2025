<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ModeMaster;

class FreightModeController extends Controller
{
    /**
     * List Freight Modes with pagination
     */
    public function index(Request $request)
    {
        try {
            $page    = (int) $request->input('page', 1);
            $perPage = (int) $request->input('record', 10);

            $query = ModeMaster::active()
                ->select('mode_id as id', 'mode_name as name', 'mode_status as status')
                ->orderByDesc('mode_id');

            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status'     => 'success',
                'message'    => 'Freight Modes listed successfully.',
                'data'       => $paginated->items(),
                'pagination' => [
                    'total'        => $paginated->total(),
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Freight Mode details by ID
     */
    public function edit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:tbl_mode_master,mode_id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = ModeMaster::active()
                ->select('mode_id as id', 'mode_name as name', 'mode_status as status')
                ->where('mode_id', $request->id)
                ->first();

            return response()->json([
                'status'  => 'success',
                'message' => 'Freight Mode retrieved successfully.',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or Update Freight Mode
     */
    public function storeOrUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'     => 'nullable|exists:tbl_mode_master,mode_id',
                'name'   => 'required|string|max:255',
                'status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $isUpdate = !empty($request->id);

            ModeMaster::updateOrCreate(
                ['mode_id' => $request->id],
                [
                    'mode_name'   => $request->name,
                    'mode_status' => $request->status,
                ]
            );

            return response()->json([
                'status'  => 'success',
                'message' => $isUpdate
                    ? 'Freight Mode updated successfully.'
                    : 'Freight Mode created successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Freight Mode by ID
     */
    public function destroy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:tbl_mode_master,mode_id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            ModeMaster::where('mode_id', $request->id)->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Freight Mode deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Freight Mode status
     */
    public function updateStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'     => 'required|exists:tbl_mode_master,mode_id',
                'status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            ModeMaster::where('mode_id', $request->id)
                ->update(['mode_status' => $request->status]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Freight Mode status updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
