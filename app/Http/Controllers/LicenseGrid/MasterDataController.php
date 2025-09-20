<?php

namespace App\Http\Controllers\LicenseGrid;
 use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    Currency,
    FinancialYear,
    FiscalMonth,
    Country,
    State,
    City,
    OnboardingStep
};

class MasterDataController  extends Controller
{
public function countries(Request $request)
{
    $data = Country::active()
        ->select(
            'id',
            'name',
            'code2',
            'code3',
            'latitude',
            'longitude',
            'mobile_code',
            'flag',
            'currency_id',
            'fiscal_month_id'
        )
        ->get()
        ->map(function ($country) {
            $country->flag = 'public/material/country-flags/' . $country->flag;
            return $country;
        });

    $response = [
        'status' => 'success',
        'message' => 'Country is listed here successfully.',
        'data' => $data,
    ];

    return response()->json($response, 200);
}


    public function currencies(Request $request)
    {
        $data = Currency::active()->get();

        $response = [
            'status' => 'success',
            'message' => 'Currencies are listed here successfully.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    public function fiscalMonths(Request $request)
    {
        $data = FiscalMonth::active()->get();

        $response = [
            'status' => 'success',
            'message' => 'Financial Months are listed here successfully.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    public function financialYears(Request $request)
    {
        $data = FinancialYear::active()->get();

        $response = [
            'status' => 'success',
            'message' => 'Financial Years are listed here successfully.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    public function states(Request $request)
    {
        $data = State::active()
            ->when($request->country_id, function ($query) use ($request) {
                $query->where('country_id', $request->country_id);
            })
            ->get();

        $response = [
            'status' => 'success',
            'message' => 'States are listed here successfully.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    public function cities(Request $request)
    {
        $data = City::active()
            ->when($request->state_id, function ($query) use ($request) {
                $query->where('state_id', $request->state_id);
            })
            ->get();

        $response = [
            'status' => 'success',
            'message' => 'Cities are listed here successfully.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    public function sources(Request $request)
{
    $data = Source::active()->get();

    $response = [
        'status' => 'success',
        'message' => 'Sources are listed here successfully.',
        'data' => $data,
    ];

    return response()->json($response, 200);
}

 public function salutation()
{
    $data = [
        ['name' => 'Mr.'],
        ['name' => 'Mrs.'],
        ['name' => 'Ms.'],
        ['name' => 'Dr.'],
        ['name' => 'Prof.'],
        ['name' => 'Er.'],
        ['name' => 'Mx.'],
    ];

    return response()->json([
        'status' => 'success',
        'message' => 'Salutation options listed successfully.',
        'data' => $data
    ]);
}
 public function getOnboardingSteps()
    {
        $steps = OnboardingStep::select('id', 'name')->orderBy('id')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Onboarding steps retrieved successfully.',
            'data' => $steps
        ], 200);
    }
public function CurrencyExchange(Request $request)
{
    $amount = $request->input('amount');
    $current_Currency = $request->input('current_Currency');
    $exchange_Currency = $request->input('exchange_Currency');

    $result = convertCurrency($amount, $current_Currency, $exchange_Currency);

    $statusCode = $result['status'] === 'success' ? 200 : 400;
    return response()->json($result, $statusCode);
}
}
 