<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\{
    WebsitePage
}; 
class SubmenuController extends Controller
{
  
    // Show 
   public function index(Request $request)
    {
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('record', 10);

        $data  = WebsitePage::active()->withWhereHas('menu', function ($query) {
            $query->active();
        });
        if ($request->filled('page_id')) {
            $data->whereHas('menu', function ($query) use ($request)
            {
                $query->where('page_id', $request->id );
            });
        }
        $submenu =  $data->orderByDesc('page_id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($sumenu) {
                return [
                    'id' => $sumenu->page_id,
                    'page_title' => $sumenu->page_title,
                    'page_name' => $sumenu->page_name,
                    'status' => $sumenu->status,
                    'menu_id' => $sumenu->menu->module_id,
                    'menu_name' => $sumenu->menu->module_name,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Submenu  listed successfully.',
            'data' => $submenu->items(),
            'pagination' => [
                'total'        => $submenu->total(),
                'current_page' => $submenu->currentPage(),
                'last_page'    => $submenu->lastPage(),
                'per_page'     => $submenu->perPage(),
            ]

        ], 200);
    }


    //  Edit 
    public function edit(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_website_page,page_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data_detail = WebsitePage::active()
            ->withWhereHas('menu', function ($query) {
            $query->active();
            })
            ->where("page_id",$request->id)
            ->first();

          $data = [
                    'id' => $data_detail->page_id,
                    'page_title' => $data_detail->page_title,
                    'page_name' => $data_detail->page_name,
                    'status' => $data_detail->status,
                    'menu_id' => $data_detail->menu->module_id,
                    'menu_name' => $data_detail->menu->module_name,
          ]   ;

            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Submenu ID not found'], 404);
            };
            

        return response()->json([
            'status' => 'success',
            'message' => 'Submenu retrieved successfully.',
            'data' => $data
        ], 200);
       
    }


    // Add and update 
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'nullable|exists:tbl_website_page,page_id',
            'page_title'  => 'required|string',
            'page_name'  => 'required|string',
            'status'  => 'required|in:active,inactive',
            
 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        
     
      
        $isUpdate = !empty($request->id);

      WebsitePage::updateOrCreate(
            ['page_id' => $request->id],
            [
                'page_title'  => $request->page_title,
                'page_name'  => $request->page_name, 
                'status'  => $request->status, 
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Submenu updated successfully.' : 'Submenu created successfully.'
        ], 200);
    }
  
    // Delete 
    public function destroy(Request $request)
    {
       $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_website_page,page_id',
          
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } 

        WebsitePage::find($request->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Submenu deleted successfully.',
        ], 200);
    }
    // Update status
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_website_page,page_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        WebsitePage::where('page_id', $request->id)->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => ' Submenu updated successfully.']);
    }
    
    
}
