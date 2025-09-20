<?php

namespace App\Http\Controllers\Admin;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\State; // Model should map to your zones table

class StateController extends Controller
{
    /**
     * List all states
     */
public function index(Request $request)
{
    try {
        // Build query with active states
        $query = State::active()->select(
            'zone_id as state_id',
            'zone_country_id as country_id',
            'zone_name as state_name',
            'zone_code as state_zone_code',
            'state_code',
            'latitude as state_latitude',
            'longitude as state_longitude',
            'status as state_status',
            
        );

        // Apply country filter if provided
        if ($request->filled('country_id')) {
            $query->where('zone_country_id', $request->country_id);
        }

        // Fetch states ordered by ID descending
        $states = $query->orderByDesc('zone_id')->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'States listed successfully.',
            'data'    => $states
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}


    /**
     * Edit / Get details of a state
     */
    public function edit(Request $request)
    {
        $state = State::select(
                    'zone_id as state_id',
            'zone_country_id as country_id',
            'zone_name as state_name',
            'zone_code as state_zone_code',
            'state_code',
        'latitude as state_latitude',
            'longitude as state_longitude',
            'state_status',
            )
            ->find($request->id);

        if (!$state) {
            return response()->json([
                'status'  => 'error',
                'message' => 'State not found.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'State details retrieved successfully.',
            'data'    => $state
        ], 200);
    }

    /**
     * Create or update a state
     */
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|exists:tbl_country,country_id',
            'state_name'       => 'required|string|max:155',
            'state_zone_code'       => 'required|string|max:10',
            'state_code' => 'nullable|string|max:10',
            'state_latitude'   => 'nullable|numeric',
            'state_longitude'  => 'nullable|numeric',
            'state_status'     => 'nullable|in:active,inactive',
            'state_id'         => 'nullable|exists:tbl_zones,zone_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->state_id);

        State::updateOrCreate(
            ['zone_id' => $request->state_id],
            [
                'zone_country_id' => $request->country_id,
                'zone_name'       => $request->state_name,
                'zone_code'       => $request->state_zone_code,
                'state_code'      => $request->state_code ?? 0,
                'latitude'        => $request->state_latitude,
                'longitude'       => $request->state_longitude,
                'status'    => $request->state_status ?? 'active',
                'updated_at'      => now(),
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate ? 'State updated successfully.' : 'State created successfully.'
        ], 200);
    }

    /**
     * Soft delete state
     */
    public function destroy(Request $request)
    {
        $state = State::find($request->id);

        if (!$state) {
            return response()->json([
                'status'  => 'error',
                'message' => 'State not found.'
            ], 404);
        }

        $state->deleteflag = 'inactive';
        $state->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'State deleted successfully.'
        ], 200);
    }

    /**
     * Update status (active/inactive)
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id'     => 'required|exists:tbl_zones,zone_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $state = State::find($request->id);
        $state->status = $request->status;
        $state->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'State status updated successfully.'
        ], 200);
    }
}
