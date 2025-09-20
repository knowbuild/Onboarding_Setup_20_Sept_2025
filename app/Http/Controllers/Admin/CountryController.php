<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\{
    Country,
    State,
    City,
    Currency,
    FiscalMonth
}; 
class CountryController extends Controller
{
  
    // Show country
   public function index(Request $request)
{
    $query = Country::active()
        ->withWhereHas('currencyList', function ($q) {
            $q->active()->select('id', 'symbol', 'code');
        })
        ->withWhereHas('fiscalMonth', function ($q) {
            $q->active()->select('id', 'start_month_name', 'end_month_name');
        })
        ->select(
            'country_id',
            'country_name',
            'country_code2',
            'country_code3',
            'latitude',
            'longitude',
            'country_status',
            'currency',
            'fiscal_month'
        );

    if ($request->filled('country_name')) {
        $query->where('country_name', 'LIKE', '%' . $request->country_name . '%');
    }

    $countries = $query->orderByDesc('country_id')->get();

    $countryData = $countries->map(function ($country) {
        return [
            'id'               => $country->country_id,
            'name'             => $country->country_name,
            'country_code2'    => $country->country_code2,
            'country_code3'    => $country->country_code3,
            'latitude'         => $country->latitude,
            'longitude'        => $country->longitude,
            'status'           => $country->country_status,
            'currency_id'      => $country->currencyList->id ?? null,
            'currency_symbol'  => $country->currencyList->symbol ?? null,
            'currency_code'    => $country->currencyList->code ?? null,
            'fiscal_id'         => $country->fiscalMonth->id ?? null,
            'start_month_name' => $country->fiscalMonth->start_month_name ?? null,
            'end_month_name'   => $country->fiscalMonth->end_month_name ?? null,
        ];
    });

    return response()->json([
        'status'     => 'success',
        'message'    => 'Country listed successfully.',
        'data'       => $countryData,
    ], 200);
}

 

    //  Edit Country
    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_country,country_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $country = Country::active()
            ->withWhereHas('currencyList', function ($query) {
                $query->active()->select('id', 'symbol', 'code'); 
            })
            ->withWhereHas('fiscalMonth', function ($query_fiscalMonth) {
                $query_fiscalMonth->active()
                    ->select('id', 'start_month_name', 'end_month_name'); 
            })
            ->select(
                'country_id',
                'country_name',
                'country_code2',
                'country_code3',
                'latitude',
                'longitude',
                'country_status',
                'currency',
                'fiscal_month'
            )
            ->where("country_id",$request->id)
            ->first();
            if (!$country) {
                return response()->json(['status' => 'error', 'message' => 'Country not found'], 404);
            }
            $country_data = [
                'id' => $country->country_id,
                'name' => $country->country_name,
                'country_code2' => $country->country_code2,
                'country_code3' => $country->country_code3,
                'latitude' => $country->latitude,
                'longitude' => $country->longitude,
                'status' => $country->country_status,
                'currency_id'      => $country->currencyList->id ?? null,
                'currency_symbol' => $country->currencyList->symbol ?? null,
                'currency_code' => $country->currencyList->code ?? null,
                'fiscal_id'         => $country->fiscalMonth->id ?? null,
                'start_month_name' => $country->fiscalMonth->start_month_name ?? null,
                'end_month_name' => $country->fiscalMonth->end_month_name ?? null,
            ];
            

        return response()->json([
            'status' => 'success',
            'message' => 'Country retrieved successfully.',
            'data' => $country_data
        ], 200);
    }


    // Add and update country
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'nullable|exists:tbl_country,country_id',
            'name'  => 'required|string',
            'country_code2' => 'required|string|max:5',
            'country_code3' => 'required|string|max:5',
            'latitude'      => 'required',
            'longitude'     => 'required',
            'status'        => 'required',
            
 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
       
     
      
        $isUpdate = !empty($request->id);

      Country::updateOrCreate(
            ['country_id' => $request->id],
            [
                'country_name'  => $request->name,
                'country_code2' => $request->country_code2,
                'country_code3' => $request->country_code3,
                'latitude'      => $request->latitude,
                'longitude'     => $request->longitude,
                'country_status'=> $request->status,
                'currency'=> $request->currency_id,
                'fiscal_month'=> $request->fiscal_id,
                'updated_at'     => now(),
              
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Country updated successfully.' : 'Country created successfully.'
        ], 200);
    }
  
    // Delete Country
    public function destroy(Request $request)
    {
       $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_country,country_id',
          
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } 

        Country::find($request->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Country deleted successfully.',
        ], 200);
    }
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_country,country_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Country::where('country_id', $request->id)->update(['country_status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Status updated successfully.']);
    }
    
    
}
