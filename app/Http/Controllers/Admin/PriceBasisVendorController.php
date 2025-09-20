<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PriceBasis;
use Illuminate\Support\Facades\Validator;

class PriceBasisVendorController extends Controller
{
    public function index()
    {
        $priceBases = PriceBasis::active()->select('price_basis_id as id', 'price_basis_name', 'status', 'purchase_type')
            ->orderByDesc('price_basis_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Price bases listed successfully.',
            'data' => $priceBases
        ], 200);
    }

    public function edit(Request $request)
    {
        $priceBase = PriceBasis::select('price_basis_id as id', 'price_basis_name',  'status', 'purchase_type')
            ->find($request->id);

        if (!$priceBase) {
            return response()->json([
                'status' => 'error',
                'message' => 'Price base not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Price base details retrieved successfully.',
            'data' => $priceBase
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'price_basis_name' => 'required|string|max:100',
            'purchase_type' => 'required|string|max:15',
            'status' => 'nullable|in:active,inactive',
            'id' => 'nullable|exists:tbl_price_basis,price_basis_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $isUpdate = !empty($request->id);

        // Avoid duplicate entry on create

        if (!$isUpdate && PriceBasis::where('price_basis_name', $request->price_basis_name)
            ->where('purchase_type', $request->purchase_type)
            ->where('status', $request->status ?? 'active')
            ->exists()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Exact same record already exists.',
                'data' => null
            ], 400);
        }

        $data = [
            'price_basis_name' => $request->price_basis_name,
            'purchase_type' => $request->purchase_type,
            'status' => $request->status ?? 'active',
        ];

        if ($isUpdate) {
            PriceBasis::where('price_basis_id', $request->id)->update($data);
        } else {
            PriceBasis::create($data);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Price basis updated successfully.' : 'Price basis created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $priceBase = PriceBasis::find($request->id);

        if (!$priceBase) {
            return response()->json([
                'status' => 'error',
                'message' => 'Price basis not found.'
            ], 404);
        }


    $priceBase->deleteflag = 'inactive';
    $priceBase->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Price basis deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id' => 'required|exists:tbl_price_basis,price_basis_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $priceBase = PriceBasis::find($request->id);
        $priceBase->status = $request->status;
        $priceBase->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Price basis status updated successfully.'
        ], 200);
    }
  public function updatePurchaseType(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'purchase_type' => 'required|string|max:15',
            'id' => 'required|exists:tbl_price_basis,price_basis_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $priceBase = PriceBasis::find($request->id);
        $priceBase->purchase_type = $request->purchase_type;
        $priceBase->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Price basis purchase type updated successfully.'
        ], 200);
    }
}
 