<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Department;
class DepartmentController extends Controller
{
    public function index()
{
    $departments = Department::orderBy('id', 'desc')->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Departments listed successfully.',
        'data' => $departments
    ]);
}
    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'id' => 'nullable|exists:departments,id'
        ]);

        $department = Department::updateOrCreate(
            ['id' => $request->id],
            ['name' => $request->name]
        );

        return response()->json([
            'status' => 'success',
            'message' => $request->id ? 'Department updated successfully.' : 'Department created successfully.',
            'data' => $department
        ]);
    }

    public function destroy($id)
    {
        Department::destroy($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Department deleted successfully.'
        ]);
    }

    public function updateStatus($id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        Department::where('id', $id)->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Department status updated successfully.'
        ]);
    }
}
