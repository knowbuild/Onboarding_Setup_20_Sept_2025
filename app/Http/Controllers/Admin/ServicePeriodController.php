<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ServiceMaster;

class ServicePeriodController extends Controller
{
    public function index()
{
    $services = ServiceMaster::select(
            'service_id as id',
            'service_name as name',
            'service_abbrv',
            'duration',
            'duration_type',
            'service_status as status',
            'display_order'
        )
        ->orderByDesc('service_id')
        ->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Services listed successfully.',
        'data' => $services
    ], 200);
}


    public function edit(Request $request)
    {
        $service = ServiceMaster::select('service_id as id', 'service_status as status',  'duration','duration_type')
            ->find($request->id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service period not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Service period details retrieved successfully.',
            'data' => $service
        ], 200);
    }
 
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer|exists:tbl_service_master,service_id',
            'status' => 'required|in:active,inactive',
            'duration' => 'required|integer',
            'duration_type' => 'required|in:Days,Months,Year',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

         $duration = $request->duration;
              $duration_type = $request->duration_type;
  $ServiceName = "{$duration} {$duration_type}";
  if($duration_type == 'Months'){
    $service_abbrv = $duration;
    $display_order = $duration;
  }
  elseif($duration_type == 'Days'){
    $service_abbrv = $duration;
        $display_order = 0;
  }
  elseif($duration_type == 'Year'){
    $service_abbrv = $duration*12;
        $display_order = $duration*12;
  }


        $record = ServiceMaster::updateOrCreate(
            ['service_id' => $request->id],
            [
            'service_status' => $request->status ?? 'active',
               'duration' =>$duration,
              'duration_type' => $duration_type,
             'service_name' => $ServiceName,
            'service_abbrv' => $service_abbrv,
            'display_order'  =>  $display_order,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Service period updated successfully.' : 'Service period created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $record = ServiceMaster::find($request->id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service period not found.'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Service period deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_service_master,service_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = ServiceMaster::find($request->id);
        $record->service_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Service period status updated successfully.'
        ], 200);
    }
}
