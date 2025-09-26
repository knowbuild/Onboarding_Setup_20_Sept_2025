<?php

namespace App\Http\Controllers\SalesManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\{
    Customer,
    SaleOfferTemplate,
    SaleOfferTemplateGeneral,
    Country,
    State,
    City
};
 use PDF;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;

class SaleOfferTemplateController extends Controller
{
    public function showDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        $customerId = $request->customer_id;

        $saleOfferTemplate = SaleOfferTemplate::where('customer_id', $customerId)->first();

        if ($saleOfferTemplate) {
            $data = [
                'customer_id'        => $saleOfferTemplate->customer_id,
                'organisation'       => $saleOfferTemplate->organisation,
                'country'            => $saleOfferTemplate->country,
                'state'              => $saleOfferTemplate->state,
                'city'               => $saleOfferTemplate->city,
                'address'            => $saleOfferTemplate->address,
                'tel'                => $saleOfferTemplate->tel,
                'mobile'             => $saleOfferTemplate->mobile,
                'email'              => $saleOfferTemplate->email,
                'company_website'    => $saleOfferTemplate->company_website,
                'company_logo'       => $saleOfferTemplate->company_logo,
                'certification_img'  => $saleOfferTemplate->certification_img,
                'template_no'        => 1,
                'terms_conditions'   => $this->formatTerms($saleOfferTemplate),
                'notes'              => $saleOfferTemplate->notes,
                'general_standard'   => $saleOfferTemplate->general_standard,
                'subject'            => $saleOfferTemplate->subject,
                'style_font'         => $saleOfferTemplate->style_font,
                'mail_message'       => $saleOfferTemplate->mail_message,
                'certified_note'     => $saleOfferTemplate->certified_note,
                'contact_member1'    => $saleOfferTemplate->contact_member1,
                'contact_member2'    => $saleOfferTemplate->contact_member2,
                'contact_member3'    => $saleOfferTemplate->contact_member3,
                'contact_member4'    => $saleOfferTemplate->contact_member4,
            ];
        } else {
            $generalTemplate = SaleOfferTemplateGeneral::first();

            $customer = Customer::select(
                'id',
                'email',
                'mobile',
                'organisation',
                'company_address',
                'company_address_2',
                'company_logo',
                'company_website',
                'company_country',
                'company_state',
                'company_city'
            )->find($customerId);

            $country = Country::where('country_id', $customer->company_country)->value('country_name') ?? null;
            $state   = State::where('zone_id', $customer->company_state)->value('zone_name') ?? null;
            $city    = City::where('city_id', $customer->company_city)->value('city_name') ?? null;

            $data = [
                'customer_id'        => $customer->id,
                'organisation'       => $customer->organisation,
                'country'            => $country,
                'state'              => $state,
                'city'               => $city,
                'address'                => $customer->company_address . ' ' . $customer->company_address_2,
                'tel'                => '+91', // default
                'mobile'             => $customer->mobile,
                'email'              => $customer->email,
                'company_website'    => $customer->company_website,
                'company_logo'       => $customer->company_logo,
                'certification_img'  => $generalTemplate?->certification_img,
                'template_no'        => 1,
                'terms_conditions'   => $this->formatTerms($generalTemplate),
                'notes'              => $generalTemplate?->notes,
                'general_standard'   => $generalTemplate?->general_standard,
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Customer details retrieved successfully.',
            'data' => $data,
        ], 200);
    }

    private function formatTerms($template)
    {
        $terms = [];

        for ($i = 1; $i <= 8; $i++) {
            $termName     = $template?->{'term_name' . $i};
            $termDetails  = $template?->{'term_details' . $i};
            $termDisable  = $template?->{'term_disable' . $i};

            if ($termName || $termDetails) {
                $terms[] = [
                    'id' => $i,
                    'term_name'    => $termName,
                    'term_details' => $termDetails,
                    'term_disable' => $termDisable ?? 'false',
                ];
            }
        }

        return $terms;
    }
public function storeOrUpdate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'customer_id'       => 'required|exists:customers,id',
        'organisation'      => 'required|string',
        'country'           => 'required|string',
        'state'             => 'required|string',
        'city'              => 'required|string',
        'address'            => 'required|string',
        'tel'               => 'required|string',
        'mobile'            => 'required|string',
        'email'             => 'required|email',
        'company_website'   => 'required|url',
        'company_logo'      => 'nullable|string',
        'certification_img' => 'nullable|string',
        'template_no'       => 'required|integer|in:1',
        'terms_conditions'  => 'required|array|min:1|max:8',
        'terms_conditions.*.term_name'    => 'required|string',
        'terms_conditions.*.term_details' => 'required|string',
        'terms_conditions.*.term_disable' => 'required|string|in:true,false',
        'notes'             => 'required|string',
        'general_standard'  => 'required|string',
        'subject'           => 'required|string',
        'style_font'        => 'required|string',
        'mail_message'      => 'required|string',
        'certified_note'    => 'required|string',
        'contact_member1'   => 'required|string',
        'contact_member2'   => 'required|string',
        'contact_member3'   => 'required|string',
        'contact_member4'   => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 400);
    }

    // Fetch or initialize SaleOfferTemplate
    $existingTemplate = SaleOfferTemplate::where('customer_id', $request->customer_id)->first();

    // Save or retain company logo
    $companyLogoPath = $existingTemplate->company_logo ?? null;
    if (!empty($request->company_logo)) {
        $companyLogoPath = $this->saveBase64Image($request->company_logo, 'uploads/Template/company_logo/') ?? $companyLogoPath;
    }

    // Save or retain certification image
    $certificateLogoPath = $existingTemplate->certification_img ?? null;
    if (!empty($request->certification_img)) {
        $certificateLogoPath = $this->saveBase64Image($request->certification_img, 'uploads/Template/certification_img/') ?? $certificateLogoPath;
    }

    // Create or update the template
    $template = SaleOfferTemplate::updateOrCreate(
        ['customer_id' => $request->customer_id],
        [
            'organisation'      => $request->organisation,
            'country'           => $request->country,
            'state'             => $request->state,
            'city'              => $request->city,
            'address'            => $request->address,
            'tel'               => $request->tel,
            'mobile'            => $request->mobile,
            'email'             => $request->email,
            'company_website'   => $request->company_website,
            'company_logo'      => $companyLogoPath,
            'certification_img' => $certificateLogoPath,
            'template_no'       => $request->template_no,
            'notes'             => $request->notes,
            'general_standard'  => $request->general_standard,
            'subject'           => $request->subject,
            'style_font'        => $request->style_font,
            'mail_message'      => $request->mail_message,
            'certified_note'    => $request->certified_note,
            'contact_member1'   => $request->contact_member1,
            'contact_member2'   => $request->contact_member2,
            'contact_member3'   => $request->contact_member3,
            'contact_member4'   => $request->contact_member4,
        ]
    );

    // Assign up to 8 terms
    $terms = $request->input('terms_conditions', []);
    for ($i = 0; $i < 8; $i++) {
        $index = $i + 1;
       
        $template->{'term_name' . $index}    = $terms[$i]['term_name'] ?? null;
        $template->{'term_details' . $index} = $terms[$i]['term_details'] ?? null;
        $template->{'term_disable' . $index} = $terms[$i]['term_disable'] ?? 'false';
    }

    $template->save();
 $customerId = $request->customer_id;
$saleOfferTemplate = SaleOfferTemplate::where('customer_id', $customerId)->first();

$wordDownloadPath = $this->downloadWord($saleOfferTemplate);
$pdfDownloadPath = $this->downloadPdf($saleOfferTemplate);

return response()->json([
    'status'  => 'success',
    'message' => 'Sale Offer Template saved successfully.',
    'word_download' => asset($wordDownloadPath),
    'pdf_download'  => asset($pdfDownloadPath),
], 200);
}

// protected function downloadPdf($saleOfferTemplate)
public function storeOrUpdate_test()
{
    
    $saleOfferTemplate = SaleOfferTemplate::where('customer_id', 2)->first();
    
    $pdf = PDF::loadView('Onboarding.pdf_word_download.sales_offer_format', [
        'saleOfferTemplate' => $saleOfferTemplate
    ])->setPaper('a4', 'portrait');
     
    //   return view('Onboarding.pdf_word_download.sales_offer_format', [
    //     'saleOfferTemplate' => $saleOfferTemplate
    // ]);
    return $pdf->stream('purchase_order_test.pdf');
    $fileName = 'sale_offer_template_' . $saleOfferTemplate->customer_id . '_' . time() . '.pdf';
    $filePath = 'downloads/sale_offer_templates/' . $fileName;
    $fullPath = public_path($filePath);

    if (!file_exists(dirname($fullPath))) {
        mkdir(dirname($fullPath), 0755, true);
    }

    $pdf->save($fullPath);

    return $filePath;
}

protected function downloadWord($saleOfferTemplate)
{
    // Render Blade view as HTML and save as .doc file in public directory, return relative path for download link
    $content = View::make('Onboarding.pdf_word_download.sales_offer_format', [
        'saleOfferTemplate' => $saleOfferTemplate
    ])->render();

    $fileName = 'sale_offer_template_' . $saleOfferTemplate->customer_id . '_' . time() . '.doc';
    $filePath = 'downloads/sale_offer_templates/' . $fileName;
    $fullPath = public_path($filePath);

    // Ensure directory exists
    if (!file_exists(dirname($fullPath))) {
        mkdir(dirname($fullPath), 0755, true);
    }

    file_put_contents($fullPath, $content);

    return $filePath;
}

protected function saveBase64Image($base64, $path)
{
    try {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            return null;
        }

        $image = substr($base64, strpos($base64, ',') + 1);
        $image = str_replace(' ', '+', $image);
        $imageData = base64_decode($image);

        if (!$imageData) return null;

        $extension = $type[1]; // jpg, png, gif, etc.
        $imageName = uniqid('img_', true) . '.' . $extension;
        $fullPath = public_path($path);

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        file_put_contents($fullPath . '/' . $imageName, $imageData);

        return $path . $imageName;
    } catch (\Exception $e) {
        return null;
    }

}

public function termDisableUpdate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'customer_id'                  => 'required|exists:customers,id',
        'terms_conditions'             => 'required|array|min:1|max:8',
        'terms_conditions.*.id'        => 'required|integer|min:1|max:8',
        'terms_conditions.*.term_disable' => 'required|string|in:true,false',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 400);
    }

    // Create or update Sale Offer Template for this customer
    $template = SaleOfferTemplate::updateOrCreate(
        ['customer_id' => $request->customer_id]
    );

    // Assign term_disable values based on term ID
    foreach ($request->terms_conditions as $term) {
        $fieldIndex = $term['id']; // taken from 'terms_conditions.*.id'
        
        if ($fieldIndex >= 1 && $fieldIndex <= 8) {
            $template->{'term_disable' . $fieldIndex} = $term['term_disable'];
        }
    }

    $template->save();

    return response()->json([
        'status'  => 'success',
        'message' => 'Sale Offer Template saved successfully.',
    ], 200);
}

}


 





