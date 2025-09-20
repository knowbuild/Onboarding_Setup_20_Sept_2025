<?php

use Illuminate\Support\Facades\Http;

if (!function_exists('convertCurrency')) {
    function convertCurrency($amount, $fromCurrency, $toCurrency)
    {
        try {
            $amount = floatval($amount);
            $fromCurrency = strtoupper(trim($fromCurrency));
            $toCurrency = strtoupper(trim($toCurrency));

            if ($amount <= 0 || !$fromCurrency || !$toCurrency) {
                return [
                    'status' => 'error',
                    'message' => 'Please provide valid amount and currency codes.',
                ];
            }

            $appId = 'ec553ef7328146fab7f75effc1ed05b8';
            $apiUrl = "https://openexchangerates.org/api/latest.json?app_id={$appId}&base={$fromCurrency}";

            $response = Http::get($apiUrl);

            if (!$response->successful()) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to fetch currency rates.',
                ];
            }

            $rates = $response->json()['rates'];
            $targetRate = $rates[$toCurrency] ?? null;

            if (!$targetRate) {
                return [
                    'status' => 'error',
                    'message' => "Rate for currency {$toCurrency} not found.",
                ];
            }

            $convertedAmount = round($amount * $targetRate, 2);

            return [
                'status' => 'success',
                'amount_in_' . strtolower($fromCurrency) => $amount,
                'amount_in_' . strtolower($toCurrency) => $convertedAmount,
                'rate_used' => $targetRate,
                'base_currency' => $fromCurrency,
                'exchange_currency' => $toCurrency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ];
        }
    }
}
