<?php

namespace App\Http\Controllers\LicenseGrid;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Favourite;
use App\Models\CustomerNote;
use App\Models\OnboardingProgres;
use App\Models\License;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Command;
use App\Models\PaymentReceived;
 
class ProformaInvoiceController extends Controller
{
public function details(Request $request)
{
    $request->validate([
        'licenses_id' => 'required|exists:licenses,id',
    ]);

    $license = License::with([
        'customer:id,customer_code,organisation,name,email,mobile,company_address,gst_number',
        'product:pro_id,pro_title',
        'product.productsEntry:pro_id,hsn_code',
        'durationService:service_id,service_name',
         'createdBy:admin_id,admin_fname,admin_lname',
        'paymentTerms:supply_order_payment_terms_id,supply_order_payment_terms_name'
    ])->find($request->licenses_id);

    if (!$license) {
        return response()->json([
            'status' => 'error',
            'message' => 'License not found.',
        ], 404);
    }

    // Convert license to array and remove relationship blocks
    $data = $license->toArray();

    // Remove nested objects
    unset( $data['durationService'], $data['createdBy'], $data['paymentTerms'],$data['customer'], $data['product']);

    // Add flattened fields manually
    $data['customer_code']           = $license->customer->customer_code ?? null;
    $data['customer_organisation']   = $license->customer->organisation ?? null;
    $data['customer_name']           = $license->customer->name ?? null;
    $data['customer_email']          = $license->customer->email ?? null;
    $data['customer_mobile']         = $license->customer->mobile ?? null;
    $data['company_address']         = $license->customer->company_address ?? null;
    $data['customer_gst_number']     = $license->customer->gst_number ?? null;

    $data['product_name']            = $license->product->pro_title ?? null;
    $data['hsn_code']                = optional($license->product->productsEntry)->hsn_code ?? null;

    $data['duration_name']           = optional($license->durationService)->service_name ?? null;
 $data['gst_percentage']     = 18;
  $data['created_by_name']     = trim(optional($license->createdBy)->admin_fname . ' ' . optional($license->createdBy)->admin_lname) ?: null;
$data['terms_of_payment_name'] = optional($license->paymentTerms)->supply_order_payment_terms_name ?? null;



   $data['duration']     =  $license->duration;
     $data['term_payment_id']     =  $license->term_payment_id;
       $data['created_by']     =  $license->created_by;
    return response()->json([
        'status' => 'success',
        'message' => 'License retrieved successfully.',
        'data' => $data,
    ], 200);
}


 
public function update(Request $request)
{
    $validator = Validator::make($request->all(), [
        'licenses_id' => 'nullable|exists:licenses,id',
        'customer_id' => 'required|exists:customers,id',
        'product_id' => 'nullable|exists:tbl_products,pro_id',
        'licenses' => 'required|integer|min:1',
        'price' => 'nullable|numeric',
        'sub_total_price' => 'nullable|numeric',
        'tax_price' => 'nullable|numeric',
        'license_cost' => 'nullable|numeric',
        'duration' => 'required|integer|min:1',
        'licenses_start_date' => 'nullable|date',
        'licenses_end_date' => 'nullable|date|after_or_equal:licenses_start_date',
        'licenses_type' => 'nullable|in:trial,paid',
        'status' => 'nullable|in:active,inactive',
        'account_status' => 'nullable|in:pending,approved,hold,expired,rejected,draft',
        'company_bill_id' => 'nullable',
        'proforma_invoice_at' => 'nullable|date',
        'buyer_po_number' => 'nullable|string',
        'buyer_po_image' => 'nullable|string',
        'term_payment_id' => 'nullable',
        'po_notes' => 'nullable|string',
        'company_bill_bank_id' => 'nullable',
        'created_by' => 'nullable|integer',
        'payment_link' => 'nullable|string',
    ]);
 
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $request->only([
        'customer_id',
        'product_id',
        'licenses',
        'price',
        'sub_total_price',
        'tax_price',
        'license_cost',
        'duration',
        'licenses_start_date',
        'licenses_end_date',
        'licenses_type',
        'status',
        'account_status',
        'company_bill_id',
        'proforma_invoice_at',
        'buyer_po_number',
        'term_payment_id',
        'po_notes',
        'company_bill_bank_id',
        'created_by',
        'payment_link'
    ]);
      $licensesdata = License::find($request->licenses_id);


    // Handle buyer_po_image
    if ($request->filled('buyer_po_image')) {
        $base64 = $request->input('buyer_po_image');
        $filePath = $this->saveBase64File($base64, 'uploads/po_files');

        if ($filePath && str_ends_with($filePath, '.pdf')) {
            $this->compressPdf(public_path($filePath), public_path($filePath));
        }

        $data['buyer_po_image'] = $filePath;
    }

    $license = License::updateOrCreate(
        ['id' => $request->licenses_id],
        $data
    );
$customer_id = $request->licenses_id;
$license_id = $license['id'];
     if (empty($licensesdata->proforma_invoice)) {
    craeteProformaInvoiceByLicense($customer_id, $license_id);
    }
    else{
updateProformaInvoiceByLicense($customer_id, $license_id);
    }

    return response()->json([
        'status' => 'success',
        'message' => $request->licenses_id ? 'License updated successfully.' : 'License created successfully.',
        'data' => $license,
    ]);
}

protected function saveBase64File($base64, $uploadPath)
{
    if (!preg_match('/^data:(.*?);base64,/', $base64, $match)) {
        return null;
    }

    $mimeType = $match[1];
    $extension = explode('/', $mimeType)[1] ?? 'bin';
    $data = substr($base64, strpos($base64, ',') + 1);
    $data = base64_decode(str_replace(' ', '+', $data));

    if (!$data) return null;

    $fileName = uniqid('po_', true) . '.' . $extension;
    $fullPath = public_path($uploadPath);

    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0755, true);
    }

    file_put_contents($fullPath . '/' . $fileName, $data);

    return $uploadPath . '/' . $fileName;
}

protected function compressPdf($source, $destination)
{
    // Ensure Ghostscript is available in your server (Linux or Windows)
    $gs = 'gs'; // Path to ghostscript binary
    $cmd = "$gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($destination) . " " . escapeshellarg($source);

    exec($cmd, $output, $returnCode);
    return $returnCode === 0;
}




}
