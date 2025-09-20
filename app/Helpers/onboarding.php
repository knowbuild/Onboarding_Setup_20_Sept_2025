<?php

use App\Models\{
    WebsiteSetting,
    ProductMain,
    Service,
    Customer,
    License,
    LicensePaymentRecive,
    OnboardingProgres,
    IndexG2,
    IndexS2
};



if (!function_exists('getWeb')) {
    function getWeb()
    {
        return WebsiteSetting::where('id', 1)->first();
    } 

 
}

function upcCodeProduct()
{
    // Get the last entry ordered by upc_code in descending order
    $lastProduct = ProductMain::orderBy('upc_code', 'desc')->first();

    // Check if there is no previous UPC or if the UPC is invalid
    if (!$lastProduct || !preg_match('/^\d{6}$/', $lastProduct->upc_code)) {
        return "000001"; // Start with "000001" if no valid previous UPC exists
    }

    // Increment the last UPC code
    $nextUpc = str_pad($lastProduct->upc_code + 1, 6, "0", STR_PAD_LEFT);

    return $nextUpc;
}

function upcCodeService()
{
    // Get the last entry ordered by upc_code in descending order
    $lastService = Service::orderBy('upc_code', 'desc')->first();

    // Check if there is no previous UPC or if the UPC is invalid
    if (!$lastService || !preg_match('/^\d{6}$/', $lastService->upc_code)) {
        return "000001"; // Start with "000001" if no valid previous UPC exists
    }

    // Increment the last UPC code
    $nextUpc = str_pad($lastService->upc_code + 1, 6, "0", STR_PAD_LEFT);

    return $nextUpc;
}


if (!function_exists('codeCustomer')) {
    function codeCustomer()
    {
        $prefix = 'KB-';
        $lastCustomer = \App\Models\Customer::orderBy('customer_code', 'desc')->first();

        if (!$lastCustomer || !preg_match('/^' . $prefix . '(\d{6})$/', $lastCustomer->customer_code, $matches)) {
            return $prefix . '000001';
        }

        $nextNumber = str_pad(((int) $matches[1]) + 1, 6, '0', STR_PAD_LEFT);
        return $prefix . $nextNumber;
    }
}

if (!function_exists('generateLicenseCode')) {
    function generateLicenseCode(): string
    {
        $prefix = 'LIC';
        $latest = License::orderBy('licenses_code', 'desc')->first();

        if (!$latest || !preg_match('/^' . $prefix . '(\d{3,})$/', $latest->licenses_code, $matches)) {
            return $prefix . '001';
        }

        $nextNumber = str_pad(((int) $matches[1]) + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . $nextNumber;
    }
}

if (!function_exists('generateProformaInvoiceCode')) {
    function generateProformaInvoiceCode(): string
    {
        $prefix = 'PI';
        $latest = License::orderBy('proforma_invoice', 'desc')->first();

        if (!$latest || !preg_match('/^' . $prefix . '(\d{3,})$/', $latest->proforma_invoice, $matches)) {
            return $prefix . '001';
        }

        $nextNumber = str_pad(((int) $matches[1]) + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . $nextNumber;
    }
}
if (!function_exists('generateLicenseInvoiceCode')) {
    function generateLicenseInvoiceCode(): string
    {
        $prefix = 'INV';
        $latest = LicensePaymentRecive::orderBy('invoice_code', 'desc')->first();

        if (!$latest || !preg_match('/^' . $prefix . '(\d{4,})$/', $latest->invoice_code, $matches)) {
            return $prefix . '0001';
        }

        $nextNumber = str_pad(((int) $matches[1]) + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . $nextNumber;
    }
}

if (!function_exists('createonboardingProgres')) {
    function createonboardingProgres($customer_id, $onboarding_step_id)
    {
        // Loop through all steps up to the current onboarding step
        for ($i = 1; $i <= $onboarding_step_id; $i++) {
            $existingProgress = OnboardingProgres::where('customer_id', $customer_id)
                ->where('onboarding_step_id', $i)
                ->count();

            // If progress record does not exist, mark it as 'skip'
            if ($existingProgress == 0) {
                OnboardingProgres::updateOrCreate(
                    [
                        'customer_id'         => $customer_id,
                        'onboarding_step_id'  => $i,
                    ],
                    [
                        'status' => 'skip',
                    ]
                );
            }
        }

        // Mark the current step as completed
        OnboardingProgres::updateOrCreate(
            [
                'customer_id'         => $customer_id,
                'onboarding_step_id'  => $onboarding_step_id,
            ],
            [
                'status' => 'completed',
            ]
        );
    }
}

if (!function_exists('insertCategoryProductID')) {
        function insertCategoryProductID($type, $item_id, $category_id)
        {
            if ($type === 'product') {
             IndexG2::updateOrCreate(
    ['match_pro_id_g2' => $item_id],
    ['pro_id' => $category_id]
);
               
            } elseif ($type === 'service') {
            IndexS2::updateOrCreate(
                    ['match_service_id_s2' => $item_id],
                    ['service_id' => $category_id]
                );
            }
        }
   }



