<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\{
    ModeMaster
};
class ModeMasterController extends Controller
{
   
    // Show 
public function index(Request $request)
{
    $page    = (int) $request->input('page', 1);
    $perPage = (int) $request->input('record', 10);

    $query = ModeMaster::active()
        ->select(
            'mode_id as id',
            'mode_name as name',
            'mode_status as status'
        )
        ->orderByDesc('mode_id');

    $paginated = $query->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'status'     => 'success',
        'message'    => 'Freight Mode listed successfully.',
        'data'       => $paginated->items(),
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
            'id' => 'required|exists:tbl_mode_master,mode_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = ModeMaster::active()
            ->select(
                'mode_id',
                'mode_name',
                'mode_status',
            )
            ->where("mode_id",$request->id)
            ->first();
            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Freight Mode ID not found'], 404);
            }
            $detail_data = [
                'id' => $data->mode_id,
                'name' => $data->mode_name,
                'status' => $data->mode_status,
            ];
            

        return response()->json([
            'status' => 'success',
            'message' => 'Freight Mode retrieved successfully.',
            'data' => $detail_data
        ], 200);
    }


    // Add and update 
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'nullable|exists:tbl_mode_master,mode_id',
            'mode_name'  => 'required|string',
            'status'  => 'required|in:active,inactive',
            
 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        
     
      
        $isUpdate = !empty($request->id);

      ModeMaster::updateOrCreate(
            ['mode_id' => $request->id],
            [
                'mode_name'  => $request->mode_name,
                'mode_status' => $request->status,
              
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Freight Mode updated successfully.' : 'Freight Mode created successfully.'
        ], 200);
    }
  
    // Delete 
    public function destroy(Request $request)
    {
       $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_mode_master,mode_id',
          
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } 

        ModeMaster::find($request->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Freight Mode deleted successfully.',
        ], 200);
    }
    // Update status
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_mode_master,mode_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        ModeMaster::where('mode_id', $request->id)->update(['mode_status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Freight Mode updated successfully.']);
    }
    
    
}
