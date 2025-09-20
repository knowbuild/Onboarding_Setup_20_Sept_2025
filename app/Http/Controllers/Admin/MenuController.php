<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\{
    WebsitePageModule
};
class MenuController extends Controller
{
   
    // Show 
   public function index(Request $request)
    {
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('record', 10);
        $data  = WebsitePageModule::active()->select(
            'module_id as id',
            'module_name as name',
            'status',
        );

            $paginated = $data->orderByDesc('module_id')
        ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menu  listed successfully.',
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
            'id' => 'required|exists:tbl_website_page_module,module_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = WebsitePageModule::active()->select(
            'module_id as id',
            'module_name as name',
            )
            ->where("module_id",$request->id)
            ->first();

            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Menu ID not found'], 404);
            };
            

        return response()->json([
            'status' => 'success',
            'message' => 'Menu retrieved successfully.',
            'data' => $data
        ], 200);
    }


    // Add and update 
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'nullable|exists:tbl_website_page_module,module_id',
            'name'  => 'required|string',
            'status'  => 'required|in:active,inactive',
            
 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        
     
      
        $isUpdate = !empty($request->id);

      WebsitePageModule::updateOrCreate(
            ['module_id' => $request->id],
            [
                'module_name'  => $request->name,
                'status'  => $request->status, 
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Menu updated successfully.' : 'Menu created successfully.'
        ], 200);
    }
  
    // Delete 
    public function destroy(Request $request)
    {
       $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_website_page_module,module_id',
          
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } 

        WebsitePageModule::find($request->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Menu deleted successfully.',
        ], 200);
    }
    // Update status
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_website_page_module,module_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        WebsitePageModule::where('module_id', $request->id)->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => ' Menu updated successfully.']);
    }
    
    
}
