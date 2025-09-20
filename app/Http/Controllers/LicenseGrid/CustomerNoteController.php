<?php

namespace App\Http\Controllers\LicenseGrid;
 use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerNote;
  
class CustomerNoteController extends Controller
{
    public function index(Request $request)
    {
        $notes = CustomerNote::with(['customer:id,name', 'user:id,name'])
            ->select('id', 'customer_id', 'created_by', 'note', 'status', 'created_at')
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->customer_id))
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Customer notes listed successfully.',
            'data' => $notes
        ], 200);
    }

    public function edit(Request $request)
    {
        $note = CustomerNote::select('id', 'customer_id', 'created_by', 'note', 'status')
            ->find($request->id);

        if (!$note) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer note not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Customer note details retrieved successfully.',
            'data' => $note
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'          => 'nullable|exists:customer_notes,id',
            'customer_id' => 'required|exists:customers,id',
            'note'        => 'required|string',
 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

     
      
        $isUpdate = !empty($request->id);

        CustomerNote::updateOrCreate(
            ['id' => $request->id],
            [
                'customer_id' => $request->customer_id,
                'created_by'     => $request->created_by,
                'note'        => $request->note,
              
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Customer note updated successfully.' : 'Customer note created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $note = CustomerNote::find($request->id);

        if (!$note) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer note not found.'
            ], 404);
        }

        $note->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Customer note deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:customer_notes,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $note = CustomerNote::find($request->id);
        $note->status = $request->status;
        $note->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Customer note status updated successfully.'
        ], 200);
    }
}
