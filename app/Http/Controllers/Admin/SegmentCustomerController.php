<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CustSegment;

class SegmentCustomerController extends Controller
{
    /**
     * List all customer segments.
     */
    public function index(Request $request)
    {
        $segments = CustSegment::query()
            ->select([
                'cust_segment_id  as id',
                'cust_segment_name as name',
                'interactions_reqd as repeated_interactions',
                'cust_segment_status as status',
            ])
            ->orderByDesc('cust_segment_id')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Segments listed successfully.',
            'data'    => $segments
        ], 200);
    }

    /**
     * Get details of a single segment.
     */
    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_cust_segment,cust_segment_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $segment = CustSegment::query()
            ->where('cust_segment_id', $request->id)
            ->select([
                'cust_segment_id  as id',
                'cust_segment_name as name',
                'interactions_reqd as repeated_interactions',
                'cust_segment_status as status',
            ])
            ->first();

        if (!$segment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Segment not found.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Segment details retrieved successfully.',
            'data'    => $segment
        ], 200);
    }

    /**
     * Create or update a segment.
     */
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'                   => 'nullable|exists:tbl_cust_segment,cust_segment_id',
            'name'                 => 'required|string|max:255',
            'repeated_interactions'=> 'required|integer|min:0',
            'status'               => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $isUpdate = filled($request->id);

        $segment = CustSegment::updateOrCreate(
            ['cust_segment_id' => $request->id],
            [
                'cust_segment_name'   => $request->name,
                'interactions_reqd'   => $request->repeated_interactions,
                'cust_segment_status' => $request->status ?? 'active',
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate ? 'Segment updated successfully.' : 'Segment created successfully.',
            'data'    => [
                'id'   => $segment->cust_segment_id,
                'name' => $segment->cust_segment_name,
                'repeated_interactions' => $segment->interactions_reqd,
                'status'    => $segment->cust_segment_status,
            ]
        ], 200);
    }

    /**
     * Delete a segment.
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_cust_segment,cust_segment_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $segment = CustSegment::where('cust_segment_id', $request->id)->first();

        if (!$segment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Segment not found.'
            ], 404);
        }

        $segment->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Segment deleted successfully.'
        ], 200);
    }

    /**
     * Update only the status of a segment.
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:tbl_cust_segment,cust_segment_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $segment = CustSegment::where('cust_segment_id', $request->id)->first();

        if (!$segment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Segment not found.'
            ], 404);
        }

        $segment->cust_segment_status = $request->status;
        $segment->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Segment status updated successfully.',
            'data'    => [
                'id'     => $segment->cust_segment_id,
                'status' => $segment->cust_segment_status,
            ]
        ], 200);
    }
}
