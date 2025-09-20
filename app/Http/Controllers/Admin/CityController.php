<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\City;

class CityController extends Controller
{

    public function index(Request $request)
    {
        try {
            $query = City::active()->select(
                'city_id as city_id',
                'country_id',
                'state_code as state_id',
                'city_name as city_name',
                'city_code',
                'latitude',
                'longitude',
                'status'
            );

            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            if ($request->filled('state_id')) {
                $query->where('state_code', $request->state_id);
            }

            $cities = $query->orderByDesc('city_id')->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'Cities listed successfully.',
                'data'    => $cities 
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:all_cities,city_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $city = City::where('city_id', $request->id)->select(
                'city_id as city_id',
                'country_id',
                'state_code as state_id',
                'city_name as city_name',
                'city_code',
                'latitude',
                'longitude',
                'status'
            )->first();

        return response()->json([
            'status'  => 'success',
            'message' => 'City retrieved successfully.',
            'data'    => $city
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id'         => 'nullable|exists:all_cities,city_id',
            'country_id' => 'required|integer',
            'state_id'   => 'required|integer',
            'city_name'       => 'required|string|max:255',
            'city_code'  => 'required|string|max:50',
            'latitude'   => 'nullable|numeric',
            'longitude'  => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->city_id);

        City::updateOrCreate(
            ['city_id' => $request->city_id],
            [
                'country_id' => $request->country_id,
                'state_code' => $request->state_id,
                'city_name'  => $request->city_name,
                'city_code'  => $request->city_code,
                'latitude'   => $request->latitude,
                'longitude'  => $request->longitude,
                'updated_at'     => now(),
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate ? 'City updated successfully.' : 'City created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:all_cities,city_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        City::where('city_id', $request->id)->update(['deleteflag' => 'inactive']);

        return response()->json([
            'status'  => 'success',
            'message' => 'City deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:all_cities,city_id',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        City::where('city_id', $request->id)->update(['status' => $request->status]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Status updated successfully.'
        ], 200);
    }
}
