<?php

namespace App\Http\Controllers\LicenseGrid;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReminderController extends Controller
{
    // GET /reminders/listing
public function index(Request $request)
{

   

    $validator = Validator::make($request->all(), [
           
            'customer_id' => 'required|exists:customers,id', 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

    $reminders = Reminder::with('creator')
        ->where('customer_id', $request->customer_id)
        ->orderByDesc('date')
        ->get()
        ->map(function ($reminder) {
            return [
                'id'         => $reminder->id,
                'date'       => $reminder->date,
                'action'     => $reminder->action,
                'note'       => $reminder->note,
                'created_by' => trim(optional($reminder->creator)->admin_fname . ' ' . optional($reminder->creator)->admin_lname),
                'created_at' => $reminder->created_at,
            ];
        });

    return response()->json([
        'status'  => 'success',
        'message' => 'Reminders retrieved successfully.',
        'data'    => $reminders,
    ]);
}

 
    // GET /reminders/edit?id=1
    public function edit(Request $request)
    {
        $reminder = Reminder::find($request->id);

        if (!$reminder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reminder not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder retrieved.',
            'data' => $reminder,
        ]);
    }

    // POST /reminders/store-update
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'date'        => 'required|date',
            'action'      => 'required|string|max:100',
            'note'        => 'required|string|max:200',
            'created_by'  => 'nullable|integer|exists:users,id',
         
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $reminder = Reminder::updateOrCreate(
            ['id' => $request->id],
            $request->only(['customer_id', 'date', 'action', 'note', 'created_by'])
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder saved successfully.',
            'data' => $reminder,
        ]);
    }

  
    public function destroy(Request $request)
    {
        $id =  $request->id;
        $reminder = Reminder::find($id);

        if (!$reminder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reminder not found.',
            ], 404);
        }

        $reminder->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder deleted successfully.',
        ]);
    }

    // POST /reminders/status/{id}
    public function updateStatus(Request $request)
    {
           $id =  $request->id;
      
        $reminder = Reminder::find($id);

        if (!$reminder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reminder not found.',
            ], 404);
        }

        $reminder->status = $request->status === 'active' ? 'inactive' : 'active';
        $reminder->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder status updated.',
   
        ]);
    }
}
