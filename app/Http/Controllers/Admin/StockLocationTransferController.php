<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\{
    Location
}; 
class StockLocationTransferController extends Controller
{
  
    // Show 
   public function index(Request $request)
    {
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('record', 10);

        $data  = Location::active()
            ->select(
                'id',
                'location',
                'status',
            );
 $paginated = $data->orderByDesc('id')
        ->paginate($perPage, ['*'], 'page', $page);
        return response()->json([
            'status' => 'success',
            'message' => 'Stock Location Transfer listed successfully.',
            'data' => $paginated->items(),
            'pagination' => [
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
            ]

        ], 200);
    }


    //  Edit 
    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_location,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = Location::active()
            ->select(
                'id',
                'location',
                'status',
            )
            ->where("id",$request->id)
            ->first();

            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Location ID not found'], 404);
            };
            

        return response()->json([
            'status' => 'success',
            'message' => 'Stock Location Transfer retrieved successfully.',
            'data' => $data
        ], 200);
    }


    // Add and update 
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'nullable|exists:tbl_location,id',
            'location'  => 'required|string',
            'status'  => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        
     
      
        $isUpdate = !empty($request->id);

      Location::updateOrCreate(
            ['id' => $request->id],
            [
                'location'  => $request->location,
                'status' => $request->status,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Stock Location Transfer updated successfully.' : 'Stock Location Transfer created successfully.'
        ], 200);
    }
  
    // Delete 
    public function destroy(Request $request)
    {
       $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_location,id',
          
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } 

        Location::find($request->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Stock Location Transfer deleted successfully.',
        ], 200);
    }
    // Update status
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_location,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Location::where('id', $request->id)->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Stock Location Transfer status updated successfully.']);
    }
    
    
}