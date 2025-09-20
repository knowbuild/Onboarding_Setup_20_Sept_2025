<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CourierMaster;

class LogisticsProviderController extends Controller
{
    /**
     * List all logistics providers with pagination.
     */
    public function index(Request $request)
    {
        try {
            $page    = (int) $request->input('page', 1);
            $perPage = (int) $request->input('record', 10);

            $query = CourierMaster::active()
                ->select(
                    'courier_id as id',
                    'courier_name as name',
                    'courier_status as status',
                    'tracking_link'
                )
                ->orderByDesc('courier_id');

            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status'     => 'success',
                'message'    => 'Logistics providers listed successfully.',
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
     * Get details of a specific logistics provider.
     */
    public function edit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:tbl_courier_master,courier_id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = CourierMaster::active()
                ->select('courier_id as id', 'courier_name as name', 'courier_status as status', 'tracking_link')
                ->where('courier_id', $request->id)
                ->first();

            return response()->json([
                'status'  => 'success',
                'message' => 'Logistics provider retrieved successfully.',
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
     * Create or update a logistics provider.
     */
    public function storeOrUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'           => 'nullable|exists:tbl_courier_master,courier_id',
                'name'         => 'required|string|max:255',
                'status'       => 'required|in:active,inactive',
                'tracking_link'=> 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $isUpdate = !empty($request->id);

            CourierMaster::updateOrCreate(
                ['courier_id' => $request->id],
                [
                    'courier_name'   => $request->name,
                    'courier_status' => $request->status,
                    'tracking_link'  => $request->tracking_link,
                ]
            );

            return response()->json([
                'status'  => 'success',
                'message' => $isUpdate
                    ? 'Logistics provider updated successfully.'
                    : 'Logistics provider created successfully.'
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
     * Delete a logistics provider.
     */
    public function destroy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:tbl_courier_master,courier_id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            CourierMaster::where('courier_id', $request->id)->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Logistics provider deleted successfully.'
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
     * Update the status of a logistics provider.
     */
    public function updateStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'     => 'required|exists:tbl_courier_master,courier_id',
                'status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            CourierMaster::where('courier_id', $request->id)
                ->update(['courier_status' => $request->status]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Logistics provider status updated successfully.'
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
