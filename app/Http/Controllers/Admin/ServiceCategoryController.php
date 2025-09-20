<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ApplicationService;
use App\Models\WarrantyMaster;
use App\Models\Service;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Exports\CategoryExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        $categories = ApplicationService::select(
                'application_service_id as id',
                'application_service_name as name',
                'application_service_status as status',
                'tax_class_id as gst_vat_percentage',
                'hsn_code',             
                'cat_abrv as abbreviation',
                'application_service_added_by as created_by'
            )
            ->orderByDesc('application_service_id')
            ->get();
 
        return response()->json([
            'status' => 'success',
            'message' => 'Service categories listed successfully.',
            'data' => $categories
        ], 200);
    }

    public function edit(Request $request)
    {
        $category = ApplicationService::select(
                'application_service_id as id',
                'application_service_name as name',
                'application_service_status as status',
                'tax_class_id as gst_vat_percentage',
                'hsn_code',
                'cat_abrv as abbreviation',
                'application_service_added_by as created_by'
            )
            ->find($request->id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service category not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Service category details retrieved successfully.',
            'data' => $category
        ], 200);
    }
 
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                   => 'required|string|max:155',
            'gst_vat_percentage'     => 'required|integer|min:0',
            'hsn_code'               => 'required|string|max:100',
            'abbreviation'           => 'nullable|string|max:3',
            'status'                 => 'nullable|in:active,inactive',
            'id'                     => 'nullable|exists:tbl_application_service,application_service_id',
            'created_by'   => 'required|integer|exists:tbl_admin,admin_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        ApplicationService::updateOrCreate(
            ['application_service_id' => $request->id],
            [
                'application_service_name'         => $request->name,
                'application_service_status'       => $request->status ?? 'active',
                'tax_class_id'             => $request->gst_vat_percentage,
                'hsn_code'                 => $request->hsn_code,         
                'cat_abrv'                 => $request->abbreviation ?? '0',
                'login_id'  => $request->created_by,
                'application_service_added_by' => $request->created_by,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Service category updated successfully.' : 'Service category created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $record = ApplicationService::find($request->id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service category not found.'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Service category deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id'     => 'required|exists:tbl_application_service,application_service_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = ApplicationService::find($request->id);
        $record->application_service_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Service category status updated successfully.'
        ], 200);
    }
}
 