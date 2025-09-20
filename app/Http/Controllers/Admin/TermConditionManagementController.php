<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TermConditionManagement;

class TermConditionManagementController extends Controller
{
    public function index(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'term_type'  => 'required|in:order_delivery,order_payment',
            'type'       => 'required|in:product,service',
        ]);
 
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $terms = TermConditionManagement::select('id', 'name', 'term_type', 'type', 'status')
        ->where('term_type',$request->term_type)->where('type',$request->type)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Terms & conditions listed successfully.',
            'data' => $terms
        ], 200);
    }

    public function edit(Request $request)
    {
        
        $term = TermConditionManagement::select('id', 'name', 'term_type', 'type', 'status')->find($request->id);

        if (!$term) {
            return response()->json([
                'status' => 'error',
                'message' => 'Term not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Term retrieved successfully.',
            'data' => $term
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'         => 'nullable|exists:term_condition_management,id',
            'name'       => 'required|string|max:100',
            'details'    => 'required|string',
            'term_type'  => 'required|in:order_delivery,order_payment',
            'type'       => 'required|in:product,service',
            'status'     => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        $term = TermConditionManagement::updateOrCreate(
            ['id' => $request->id],
            [
                'name'      => $request->name,
                'details'   => $request->details,
                'term_type' => $request->term_type,
                'type'      => $request->type,
                'status'    => $request->status ?? 'active',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Term updated successfully.' : 'Term created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $term = TermConditionManagement::find($request->id);

        if (!$term) {
            return response()->json([
                'status' => 'error',
                'message' => 'Term not found.'
            ], 404);
        }

        $term->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Term deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:term_condition_management,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $term = TermConditionManagement::find($request->id);
        $term->status = $request->status;
        $term->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Term status updated successfully.'
        ], 200);
    }
}
