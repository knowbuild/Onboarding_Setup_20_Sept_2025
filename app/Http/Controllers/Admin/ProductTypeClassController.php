<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductTypeClassMaster;
 
class ProductTypeClassController extends Controller
{
    public function index()
    {
        $classes = ProductTypeClassMaster::select('product_type_class_id as id', 'product_type_class_name as name', 'product_type_class_show as show_on_pqv','product_type_class_status as status')
            ->orderByDesc('product_type_class_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Product type classes listed successfully.',
            'data' => $classes
        ], 200);
    }
 
    public function edit(Request $request)
    {
        $class = ProductTypeClassMaster::select('product_type_class_id as id', 'product_type_class_name as name', 'product_type_class_show as show_on_pqv', 'product_type_class_status as status')
            ->find($request->id);

        if (!$class) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product type class not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product type class details retrieved successfully.',
            'data' => $class
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'show_on_pqv' => 'required|in:yes,no',
            'status' => 'nullable|in:active,inactive',
            'id' => 'nullable|exists:tbl_product_type_class_master,product_type_class_id'
        ]);
 
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        $record = ProductTypeClassMaster::updateOrCreate(
            ['product_type_class_id' => $request->id],
            [
                'product_type_class_name' => $request->name,
                'product_type_class_show' => $request->show_on_pqv,
                'product_type_class_status' => $request->status ?? 'active',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Product type class updated successfully.' : 'Product type class created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $record = ProductTypeClassMaster::find($request->id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product type class not found.'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product type class deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id' => 'required|exists:tbl_product_type_class_master,product_type_class_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = ProductTypeClassMaster::find($request->id);
        $record->product_type_class_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Product type class status updated successfully.'
        ], 200);
    }
    public function updateStatusShowOnPQV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'show_on_pqv' => 'required|in:yes,no',
            'id' => 'required|exists:tbl_product_type_class_master,product_type_class_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = ProductTypeClassMaster::find($request->id);
        $record->product_type_class_show = $request->show_on_pqv;
        $record->save();

        return response()->json([
            'status' => 'success',
          'message' => 'Product type class Show On PQV status updated successfully.'
        ], 200);
    }
}
