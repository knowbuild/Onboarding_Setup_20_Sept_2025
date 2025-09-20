<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\WarrantyMaster;

class WarrantyController extends Controller
{
    public function index()
    {
        $warranties = WarrantyMaster::select('warranty_id as id','warranty_name as name' ,'year', 'month', 'warranty_status as status','display_order')
            ->orderByDesc('warranty_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Warranties listed successfully.',
            'data' => $warranties
        ], 200);
    }
 
    public function edit(Request $request)
    {
        $warranty = WarrantyMaster::select('warranty_id as id','warranty_name as name' ,'year', 'month', 'warranty_status as status')
            ->find($request->id);

        if (!$warranty) {
            return response()->json([
                'status' => 'error',
                'message' => 'Warranty not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Warranty details retrieved successfully.',
            'data' => $warranty
        ], 200);
    }
public function storeOrUpdate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'year' => 'required|integer|min:0',
        'month' => 'required|integer|min:0',
        'status' => 'nullable|in:active,inactive',
        'id' => 'nullable|exists:tbl_warranty_master,warranty_id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $isUpdate = !empty($request->id);
    $year = $request->year;
    $month = $request->month;

    // Avoid duplicate entry on create
    if (!$isUpdate && WarrantyMaster::where('year', $year)->where('month', $month)->exists()) {
        return response()->json(['errors' => 'Exact same record already exists.'], 400);
    }

    // Determine duration type and value
    if ($year != 0) {
        $durationValue = $year;
        $durationType = 'Year';
    } else {
        $durationValue = $month;
        $durationType = 'Month';
    }

    $warrantyName = "{$durationValue} {$durationType}";

    // Create or update warranty
    WarrantyMaster::updateOrCreate(
        ['warranty_id' => $request->id],
        [
            'year' => $year,
            'month' => $month,
            'warranty_name' => $warrantyName,
            'warranty_status' => $request->status ?? 'active',
            'display_order' => $durationValue,
            'updated_at' => now(),
        ]
    );

    return response()->json([
        'status' => 'success',
        'message' => $isUpdate ? 'Warranty updated successfully.' : 'Warranty created successfully.'
    ], 200);
}


    public function destroy(Request $request)
    {
        $record = WarrantyMaster::find($request->id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Warranty not found.'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Warranty deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id' => 'required|exists:tbl_warranty_master,warranty_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = WarrantyMaster::find($request->id);
        $record->warranty_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Warranty status updated successfully.'
        ], 200);
    }
}
