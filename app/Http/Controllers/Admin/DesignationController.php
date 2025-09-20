<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DesignationController extends Controller
{
public function index(Request $request)
{
    $query = Designation::with(['department:id,name'])
        ->select('id', 'department_id', 'name', 'details', 'status')
        ->orderByDesc('id');

    // Optional department_id filter
    if ($request->filled('department_id')) {
        $query->where('department_id', $request->department_id);
    }
 
    $designations = $query->get();

    $data = $designations->map(function ($designation) {
        return [
            'id'              => $designation->id,
            'name'            => $designation->name,
            'status'          => $designation->status,
            'details'         => $designation->details,
            'department_id'   => $designation->department_id,
            'department_name' => optional($designation->department)->name,
        ];
    });

    return response()->json([
        'status'  => 'success',
        'message' => 'Designations listed successfully.',
        'data'    => $data
    ], 200);
}



    public function edit(Request $request)
    {
        $designation = Designation::select('id', 'department_id', 'name', 'details', 'status')
            ->find($request->id);

        if (!$designation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Designation not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Designation details retrieved successfully.',
            'data' => $designation
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'nullable|exists:designations,id',
            'department_id' => 'nullable|exists:departments,id',
            'name'          => 'required|string|max:100',
            'details'       => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        $designation = Designation::updateOrCreate(
            ['id' => $request->id],
            [
                'department_id' => $request->department_id,
                'name'          => $request->name,
                'details'       => $request->details,
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate ? 'Designation updated successfully.' : 'Designation saved successfully.',
        ], 200);
    }

    public function destroy(Request $request)
    {
        $designation = Designation::find($request->id);

        if (!$designation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Designation not found.'
            ], 404);
        }

        $designation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Designation deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:designations,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $designation = Designation::find($request->id);
        $designation->status = $request->status;
        $designation->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Designation status updated successfully.'
        ], 200);
    }
}
