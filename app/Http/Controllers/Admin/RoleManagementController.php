<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\WebsitePageModule;
use App\Models\WebsitePage;
use App\Models\AdminAccess;
use DB;

class RoleManagementController extends Controller
{

     public function getPermissions()
    {
            $modules = WebsitePageModule::with('pages')->orderBy('module_id', 'asc')
            ->get();
     $formattedData = $modules->map(function ($module) {
            return [
                'module_id' => $module->module_id,
                'module_name' => $module->module_name,
                'submodules' => $module->pages->map(function ($page) {
                    return [
                        'submodule_id' => $page->page_id,
                        'submodule_name' => $page->page_title
                    ];
                })
            ];
        });


        return response()->json([
            'status' => 'success',
            'message' => 'Permissions retrieved successfully.',
            'data' => $formattedData,


        ], 200);
    }
 

public function index(Request $request)
{
    try {
        // Build the base query
        $query = AdminAccess::with(['page', 'module', 'accessrole'])
            ->active()
            ->orderByDesc('access_id')
            ->groupBy('role_id');

        // Apply filter if provided
        if ($request->filled('business_function_id')) {
            $query->where('role_id', $request->business_function_id);
        }

        // Fetch data
        $data = $query->get();

        // Format the response
        $formattedData = $data->map(function ($access) {
            return [
                'name'                   => $access->name,
                'business_function_id'   => $access->role_id,
                'business_functions'     => optional($access->accessrole)->admin_role_name,
                'status'                 => $access->status,
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Role Management list retrieved successfully.',
            'data'    => $formattedData
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while retrieving the role management list.',
            'error'   => $e->getMessage()
        ], 500);
    }
}


public function edit(Request $request)
{
    if ($request->filled('business_function_id')) {
        try {
            // ? Validate request
            $validator = Validator::make($request->all(), [
                'business_function_id' => 'nullable|exists:tbl_admin_access,role_id'
            ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        $businessFunctionId = $request->business_function_id;

        // ? Fetch existing access records for the role
        $adminAccessRecords = AdminAccess::where('role_id', $businessFunctionId)->get();
        $adminAccess = $adminAccessRecords->first();
        $name = $adminAccess->name ?? null;

        // ? Fetch modules with pages
        $modules = WebsitePageModule::with('pages')
            ->orderBy('module_id', 'asc')
            ->get();

        // ? Format data
        $formattedData = $modules->map(function ($module) use ($adminAccessRecords) {
            return [
               
                'module_id' => $module->module_id,
                'module_name' => $module->module_name,
                'submodules' => $module->pages->map(function ($page) use ($adminAccessRecords) {
                    return [
                        'submodule_id' => $page->page_id,
                        'submodule_name' => $page->page_title,
                        'permission_status' => $adminAccessRecords->contains('page_id', $page->page_id) ? 'yes' : 'no'
                    ];
                })
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Role Management details retrieved successfully.',
             'name' => $name,
                'business_function_id' => $businessFunctionId,
            'data'    => $formattedData
        ], 200);
    
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong.',
            'error'   => $e->getMessage()
        ], 500);
    }
}
else {
                 $modules = WebsitePageModule::with('pages')->orderBy('module_id', 'asc')
            ->get();
     $formattedData = $modules->map(function ($module) {
            return [
                'module_id' => $module->module_id,
                'module_name' => $module->module_name,
                'submodules' => $module->pages->map(function ($page) {
                    return [
                        'submodule_id' => $page->page_id,
                        'submodule_name' => $page->page_title,
                         'permission_status' => 'no'
                    ];
                })
            ];
        });

            return response()->json([
            'status' => 'success',
            'message' => 'Permissions retrieved successfully.',
             'name' => null,
                'business_function_id' => null,
            'data' => $formattedData,


        ], 200);
    }
}


public function storeOrUpdate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:50',
        'business_function_id' => 'required|integer|exists:tbl_admin_role_type,admin_role_id',
        'permissions' => 'required|array|min:1',
        'permissions.*.module_id' => 'required|integer|exists:tbl_website_page_module,module_id',
        'permissions.*.submodules' => 'nullable|array',
        'permissions.*.submodules.*.submodule_id' => 'nullable|integer|exists:tbl_website_page,page_id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    DB::beginTransaction();

    try {
          $business_function_id = $request->business_function_id;
        $adminAccessCount = AdminAccess::where('role_id', $business_function_id)->count();
      
        // If updating, remove old permissions
        if ($adminAccessCount !== 0) {
            AdminAccess::where('role_id', $business_function_id)->delete();
        }

        $permissionsData = [];

        // Loop through modules and submodules
        foreach ($request->permissions as $module) {
            foreach ($module['submodules'] as $submodule) {
                $permissionsData[] = [
                    'name' => $request->name,
                    'role_id' => $business_function_id,
                    'module_id' => $module['module_id'],
                    'page_id' => $submodule['submodule_id'],
                ];
            }
        }

        // Bulk insert permissions
        if (!empty($permissionsData)) {
            AdminAccess::insert($permissionsData);
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' =>  'Role Management Permissions successfully Updated.'
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
              'business_function_id' => 'nullable|exists:tbl_admin_access,role_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    $business_function_id = $request->business_function_id;
     AdminAccess::where('role_id', $business_function_id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role Management deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
              'business_function_id' => 'nullable|exists:tbl_admin_access,role_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    $business_function_id = $request->business_function_id;
     AdminAccess::where('role_id', $business_function_id)->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Role Management status updated successfully.'
        ], 200);
    }

   
}
