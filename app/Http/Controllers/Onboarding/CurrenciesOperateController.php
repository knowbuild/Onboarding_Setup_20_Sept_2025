<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Customer;
use App\Models\CurrencyPricelist;

class CurrenciesOperateController extends Controller
{
    public function save(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'user_id'         => 'required|integer',
            'customer_code'   => 'required|string|max:50',
            'current_step'    => 'required|integer',
            'domesticMarkets' => 'required|array|min:1',
            'domesticMarkets.*.currencyId'    => 'required|integer',
            'domesticMarkets.*.priceListName' => 'required|string|max:100',
            'domesticMarkets.*.isDefault'     => 'required|boolean',
            'foreignMarkets'  => 'sometimes|array',
            'foreignMarkets.*.currencyId'    => 'required|integer',
            'foreignMarkets.*.priceListName' => 'required|string|max:100',
            'foreignMarkets.*.isDefault'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Validation Error!',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Find customer
        $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();

        // Delete old records by type (optionally add customer_id condition if needed)
        CurrencyPricelist::whereIn('type', ['domesticMarkets', 'foreignMarkets'])->delete();

        // Collect domestic markets
        $domestic = collect($request->domesticMarkets)->map(function ($trade) {
            return [
                'type'             => 'domesticMarkets',
                'currency_id'      => $trade['currencyId'],
                'price_list_name'  => $trade['priceListName'],
                'is_default'       => filter_var($trade['isDefault'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            ];
        });

        // Collect foreign markets (if any)
        $foreign = collect($request->foreignMarkets ?? [])->map(function ($trade) {
            return [
                'type'             => 'foreignMarkets',
                'currency_id'      => $trade['currencyId'],
                'price_list_name'  => $trade['priceListName'],
                'is_default'       => filter_var($trade['isDefault'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            ];
        });

        // Merge and save all
        $tradeData = $domestic->merge($foreign);

        foreach ($tradeData as $trade) {
            CurrencyPricelist::create($trade);
        }

        // Update market flags
        $domestic_market = $domestic->isNotEmpty() ? 1 : 0;
        $foreign_market  = $foreign->isNotEmpty()  ? 1 : 0;

        $customer->update([
            'domestic_market' => $domestic_market,
            'foreign_market'  => $foreign_market,
        ]);

        // Handle onboarding step progress
        $currentStep   = $request->current_step;
        $existingStep  = $customer->current_step;

        if ($currentStep > $existingStep) {
            $customer->update(['current_step' => $currentStep]);
        }

        if ($currentStep != $existingStep) {
            createonboardingProgres($customer->id, $currentStep); // custom helper
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer and Sales Preferences updated successfully',
        ], 200);
    }
}
