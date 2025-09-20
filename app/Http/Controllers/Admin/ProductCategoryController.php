<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Application;
use App\Models\WarrantyMaster;
use App\Models\ProductMain;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Exports\CategoryExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::select(
                'application_id as id',
                'application_name as name',
                'application_status as status',
                'tax_class_id as gst_vat_percentage',
                'hsn_code',
                'cat_warranty as warranty_id',
                'cat_abrv as abbreviation',
                'update_product_warranty',
                'max_discount',
                'max_discount_category',
                'application_added_by as created_by'
            )
            ->orderByDesc('application_id');

                // ? Apply search filter
        if ($request->filled('name')) {
            $name = $request->name;
            $query->where('application_name', 'LIKE', "%{$name}%");
        }
    $categories = $query->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Product categories listed successfully.',
            'data' => $categories
        ], 200);
    }

    public function edit(Request $request)
    {
        $category = Application::select(
                'application_id as id',
                'application_name as name',
                'application_status as status',
                'tax_class_id as gst_vat_percentage',
                'hsn_code',
                'cat_warranty as warranty_id',
                'cat_abrv as abbreviation',
                'update_product_warranty',
                'max_discount',
                'max_discount_category',
                  'application_added_by as created_by'
            )
            ->find($request->id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product category not found.'
            ], 404);
        }
 
        return response()->json([
            'status' => 'success',
            'message' => 'Product category details retrieved successfully.',
            'data' => $category
        ], 200);
    }
 
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                   => 'required|string|max:155',
            'gst_vat_percentage'     => 'required|integer|min:0',
            'hsn_code'               => 'required|string|max:100',
            'warranty_id'            => 'nullable|exists:tbl_warranty_master,warranty_id',
            'abbreviation'           => 'nullable|string|max:3',
            'update_product_warranty'=> 'nullable|in:Yes,No',
            'max_discount'           => 'nullable|numeric|min:0',
            'max_discount_category'  => 'nullable|in:Yes,No',
            'status'                 => 'nullable|in:active,inactive',
            'id'                     => 'nullable|exists:tbl_application,application_id',
            'created_by'   => 'required|integer|exists:tbl_admin,admin_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        Application::updateOrCreate(
            ['application_id' => $request->id],
            [
                'application_name'         => $request->name,
                'application_status'       => $request->status ?? 'active',
                'tax_class_id'             => $request->gst_vat_percentage,
                'hsn_code'                 => $request->hsn_code,
                'cat_warranty'             => $request->warranty_id ?? 3,
                'cat_abrv'                 => $request->abbreviation ?? '0',
                'update_product_warranty'  => $request->update_product_warranty ?? 'Yes',
                'max_discount'             => $request->max_discount ?? 0,
                'max_discount_category'    => $request->max_discount_category ?? 'No',
                'login_id'  => $request->created_by,
                'application_added_by' => $request->created_by,
            ]
        );

    if ($request->update_product_warranty === 'Yes') {
    ProductMain::where('cate_id', $request->id)->update([
        'pro_warranty' => $request->warranty_id ?? 3,
    ]);
}

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Product category updated successfully.' : 'Product category created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $record = Application::find($request->id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product category not found.'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product category deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id'     => 'required|exists:tbl_application,application_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = Application::find($request->id);
        $record->application_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Product category status updated successfully.'
        ], 200);
    }
    public function updateCategory(Request $request)
{
    try {
        // Validate input
        $validator = Validator::make($request->all(), [
            'category_id'            => 'required|exists:tbl_application,application_id',
            'products'               => 'required|array|min:1',
            'products.*.product_id'  => 'required|exists:tbl_products,pro_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Prepare product IDs
        $productIds = collect($request->products)->pluck('product_id')->toArray();

        // Bulk update all products in one query
        ProductMain::whereIn('pro_id', $productIds)
            ->update(['cate_id' => $request->category_id]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Products updated successfully.',
            'updated_count' => count($productIds)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

}
 