<?php

use App\Models\{WebsiteSetting, ProductMain, ProductsEntry, Service, Customer, CustomerContact, License, LicensePaymentRecive, PaymentReceived, User, Company, ServiceMaster, WebEnq, WebEnqEdit, CompPerson,OrderProduct, Lead, Order, TaxInvoice, InvoiceProduct,LeadProduct,PerformaInvoice,DeliveryOrder,DoProduct };
use Illuminate\Support\Facades\{Auth, Mail};
use App\Mail\Onboarding\Auth\WelcomeMail;
use Carbon\Carbon;
use Illuminate\Support\Str;
     
if (!function_exists('updateLicenseInCustomerCount')) {
    function updateLicenseInCustomerCount($customer_id)
    {
        // Count only active licenses (end date >= now)
  $activeLicenseCount = License::where('customer_id', $customer_id)
    ->where('status', 'active')
    ->where('account_status', 'approved')
    ->where('licenses_end_date', '>=', Carbon::now())
    ->sum('licenses');


        // Update customer's license count
        Customer::where('id', $customer_id)->update([
            'licenses' => $activeLicenseCount,
        ]);

        return true;
    }
}



if (!function_exists('licenseCustomerInsert')) {
    function licenseCustomerInsert()
    {
        // Get the most recent payment record
        $paymentReceived = PaymentReceived::active()->latest()->first();

        if (!$paymentReceived) {
            return false; // No payment found
        }

        // Check if a license already exists for this order
        $licenseExists = License::where('order_id', $paymentReceived->o_id)->exists();

        if (!$licenseExists) {
            licenseCustomer($paymentReceived->payment_received_id);
        }

        return true;
    }
}
if (!function_exists('licenseCustomer')) {
    function licenseCustomer($payment_received_id)
    {
        $paymentReceived = PaymentReceived::with([
            'taxInvoice.invoiceProduct',
            'order.customer',
             'order.orderProducts'
        ])->find($payment_received_id);

        if (
            !$paymentReceived ||
            !$paymentReceived->order ||
            !$paymentReceived->taxInvoice
        ) {
            return;
        }

        $company = $paymentReceived->order->customer;
        $products = $paymentReceived->taxInvoice->invoiceProduct->first();

        if($products){
$product = $products;
    $quantity      = $product->quantity;
        $price         = $product->price;
        }
        else{
            $product = $paymentReceived->order->orderProducts->first(); 
                $quantity      = $product->pro_quantity;
        $price         = $product->pro_price;
        }

        // License details
    
        $subTotal      = $quantity * $price;
        $taxPercentage = $product->per_item_tax_rate;
        $taxAmount     = ($subTotal * $taxPercentage) / 100;
        $totalCost     = $subTotal + $taxAmount;

        // Duration calculation
        $durationService = ServiceMaster::find($product->service_period);
        $monthsDuration  = (int) (optional($durationService)->service_abbrv ?? 0);
        $startDate       = Carbon::today();
        $endDate         = $startDate->copy()->addMonths($monthsDuration);

        // Create or update customer
        $existingCustomer = Customer::where('organisation', $company->comp_name)->where('email', $company->email)->first();

        if (!$existingCustomer) {
            $customer = Customer::create([
                'organisation'     => $company->comp_name,
                'name'             => trim(optional($company)->fname . ' ' . optional($company)->lname) ?: null,
                'email'            => $company->email,
                'mobile'           => $company->mobile_no,
                'company_address'  => $company->address,
                'country'          => $company->country,
                'company_country'  => $company->country,
                'company_state'    => $company->state,
                'company_city'     => $company->city,

        'purchase_address'      => $company->address,
        'purchase_country'      => $company->country,
        'purchase_state'        => $company->state,
        'purchase_city'         => $company->city,
                'segment_id'       => $company->cust_segment, 
                'source_id'        => $company->ref_source,
                'gst_number'       => $company->gst_no,
                'company_website'  => $company->comp_website,
                'licenses'         => $quantity,
                'status'           => 'active',
                'account_status'   => 'active',
                'account_access'   => 'active',
                'customer_code'    => codeCustomer(),
                'company_id' => $company->id,
            ]);
        } else {
            $existingCustomer->increment('licenses', $quantity);
            $customer = $existingCustomer;
        }

        // Create license
        License::create([
            'customer_id'         => $customer->id,
            'product_id'          => $product->pro_id,
            'order_id'            => $paymentReceived->o_id,
            'licenses'            => $quantity,
            'price'               => $price,
            'sub_total_price'     => $subTotal,
            'tax_price'           => $taxAmount,
            'license_cost'        => $totalCost,
            'duration'            => $product->service_period,
            'licenses_start_date' => $startDate->toDateString(),
            'licenses_end_date'   => $endDate->toDateString(),
            'licenses_type'       => 'paid',
            'status'              => 'active',
            'account_status'      => 'approved',
            'licenses_code'       => generateLicenseCode(),
        ]);

        // Setup DB access
        $customerCode = $customer->customer_code;
        $orgSlug      = strtolower(str_replace([' ', '.'], '', $customer->organisation));

        $dbName     = "{$orgSlug}{$customerCode}";
        $dbUsername = "u{$orgSlug}{$customerCode}";
        $dbPassword = "p{$orgSlug}{$customerCode}";

        $customer->update([
            'account_access'     => 'active',
            'account_status'     => 'approved',
            'database_name'      => $dbName,
            'database_username'  => $dbUsername,
            'database_password'  => $dbPassword,
        ]);

        // Create admin user and send welcome email
        $token = Str::random(60);
        $user = User::create([
            'customer_id'    => $customer->id,
            'admin_fname'    => $company->fname,
            'admin_lname'    => $company->lname,
            'admin_email'    => $company->email,
            'confirmed'      => 3,
            'remember_token' => $token,
            'customer_code'  => $customerCode,
        ]);

        $setupPasswordUrl = getWeb()->web_url . "/setuppassword?tok={$token}";
        Mail::to($company->email)->send(new WelcomeMail($user, $setupPasswordUrl));

         updateLicenseInCustomerCount($customer->id);
    }
}





     if (!function_exists('generateAllProcessOnlineLicense')) {
    function generateAllProcessOnlineLicense($customer_id, $license_id, $webEnq_id)
    {
        $customer = Customer::find($customer_id);
        $license = License::find($license_id);

        if (!$customer || !$license) return false;

        // Customer details
        $organisation      = $customer->organisation;
        $name              = $customer->name;
        $email             = $customer->email;
        $mobile            = $customer->mobile;
        $company_address   = $customer->company_address;
        $company_country   = $customer->company_country;
        $company_state     = $customer->company_state;
        $company_city      = $customer->company_city;
        $segment_id        = $customer->segment_id;
        $notes             = $customer->notes;
        $source_id         = $customer->source_id;
        $gst_number        = $customer->gst_number;
        $company_website   = $customer->company_website;
        $account_manager   = $customer->account_manger_id;
        $customer_code     = $customer->customer_code;

        // License details
        $product_id          = $license->product_id;
        $price               = $license->price;
        $sub_total_price     = $license->sub_total_price;
        $tax_price           = $license->tax_price;
        $license_cost        = $license->license_cost;
        $duration            = $license->duration;
        $license_start_date  = $license->licenses_start_date;
        $license_end_date    = $license->licenses_end_date;
        $license_type        = $license->licenses_type;
        $quantity        = $license->licenses;

        // Product details
        $product      = ProductMain::where('pro_id', $product_id)->first();
        $product_name = $product?->pro_title;
        $upc_code     = $product?->upc_code;

        $productEntry = ProductsEntry::where('pro_id', $product_id)->first();
        $hsn_code     = $productEntry?->hsn_code;

        $webEnq   =  WebEnq::where('ID', $webEnq_id)->first();
        $category_id = $webEnq['product_category'] ?? null;
        $nameParts = explode(" ", $name);
$fname = $nameParts[0] ?? '';
$lname = implode(" ", array_slice($nameParts, 1)) ?: '';
        // Company creation
        $company = Company::create([
            'parent_id'      => 0,
            'comp_name'      => $organisation,
            'comp_website'   => $company_website,
            'email'          => $email,
            'mobile_no'      => $mobile,
            'address'        => $company_address,
            'city'           => $company_city,
            'country'        => $company_country,
            'state'          => $company_state,
            'description'    => $notes,
            'ref_source'     => $source_id,
            'cust_segment'   => $segment_id,
            'acc_manager'    => $account_manager,
            'gst_no'         => $gst_number,
            'india_mart_co'  => 'no',
            'quality_check'  => 0,
            'co_division'    => '0',
            'co_city'        => '0',
            'key_customer'   => 0,
            'fname'          => $fname ?? null,
            'lname'          => $lname ?? null,
        ]);

 
// 1. Update Customer's Company ID
$companyId = $company['id'];

Customer::where('id', $customer_id)->update([
    'company_id' => $companyId,
]);




// 2. Create CompPerson Record
$compPerson = CompPerson::create([
    'company_id'     => $companyId,
    'salutation'     => $salutation ?? null,
    'fname'          => $fname ?? null,
    'lname'          => $lname ?? null,
    'comp_website'   => $company_website ?? null,
    'designation_id' => '0',
    'department_id'  => '0',
    'email'          => $email ?? null,
    'telephone'      => '',
    'mobile_no'      => $mobile ?? null,
    'fax_no'         => '',
    'address'        =>  $company_address,
    'city'           => $company_city,
    'country'        => $company_country,
    'state'          => $company_state ,
    'zip'            => '',
    'acc_manager'    => $account_manager ?? null,
]);
$comp_person_id = $compPerson['id'];
// 3. Create Lead Record
$lead = Lead::create([
    'ref_source'             => $source_id ?? null,
    'date_opened'            => now()->toDateString(),
    'time_lead_added'        => now(),
    'cust_segment'           =>  $segment_id  ?? null,
    'desc_details'           => $notes ?? null,
    'dec_time_frame'         =>  null,
    'estimated_value'        => $license_cost ?? 0,
    'acc_manager'            => $account_manager ?? null,
    'priority'               => 'Normal' ?? null,
    'lead_fname'             => $fname ?? null,
    'lead_lname'             => $lname ?? null,
    'lead_contact_address1'  =>  $company_address ?? null,
    'lead_email'             => $email ?? null,
    'lead_phone'             => $mobile ?? '0',
    'lead_contact_city'      => $company_city ?? null,
    'lead_contact_zip_code'  =>  null,
    'lead_area_code'         =>  null,
    'lead_fax'               =>  null,
    'lead_contact_state'     => $company_state ?? null,
    'lead_contact_country'   => $company_country ?? null,
    'comp_website'           => $company_website ?? null,
    'no_of_emp'              => 0,
    'comp_name'              => $companyId,
    'comp_person_id'         => $comp_person_id ?? 0,
    'comp_revenue'           =>  null,
    'app_cat_id'             => $category_id ?? 0,
    'enq_id'                 => $webEnq_id ?? '0',
    'competition'            =>  null,
    'status'                 => 'active',
    'offer_status'           => 1,
    'offer_type'             => 'service',
]);

 $lead_id = $lead['id'];

$leadProduct = LeadProduct::create([
    'lead_id'             => $lead_id,
    'pro_id'              => $product_id,
    'upc_code'            => $upc_code ?? 0,
    'price_list'          =>  null,
    'proidentry'          =>  0,
    'pro_category'        => $category_id ?? 0,
    'customers_id'        => $companyId,
    'pro_model'           => null,
    'pro_name'            => $product_name ?? '',
    'pro_price'           =>  $price ?? 0,
    'Pro_tax'             => $tax_price ?? 0,
    'GST_percentage'      => 18 ?? '0',
    'freight_amount'      =>  0,
    'hsn_code'            => $hsn_code ?? '0',
    'pro_quantity'        => $quantity ?? 0,
    'pro_sort'            =>  0,
    'pro_discount_amount' => 0,
    'lead_pro_status'     => 'Pending',
    'service_period'      => $duration ?? 0,
]);


//if($license_type == 'paid'){


$order = Order::create([
    'customers_id' => $companyId,
    'tes_linked_customer_id' => 0,
    'order_type' => 'Order',
    'customers_name' => $name,
    'customers_email' => $email,
    'customers_contact_no' => $mobile,
    'payment_mode' => 4,
    'shipping_company' => $organisation,
    'shipping_name' => $name,
    'shipping_street_address' => $company_address,
    'shipping_city' =>  $company_city,
    'shipping_zip_code' => '',
    'shipping_state' => $company_state,
    'shipping_country_name' => $company_country,
    'shipping_telephone_no' => $mobile,
    'shipping_fax_no' => null,
    'billing_name' => $name,
    'billing_company' => $organisation,
    'billing_street_address' => $company_address,
    'billing_city' =>  $company_city,
    'billing_zip_code' => '',
    'billing_state' => $company_state,
    'billing_country_name' => $company_country,
    'billing_telephone_no' => $mobile,
    'billing_fax_no' => null,
    'last_modified' => Carbon::now(),
    'date_ordered' => Carbon::today(),
    'time_ordered' => Carbon::now(),
    'orders_status' => 'Pending',
    'shipComment' => 'Handle with care.',
    'trackingNo' => '',
    'orders_date_finished' => null,
    'total_order_cost' => $license_cost,
    'cyber_amount' => 0.00,
    'cyber_credit' => 0.00,
    'tax_cost' => $tax_price ,
    'shipping_method_cost' => '0',
    'additional_disc' => 0.00,
    'payment_terms' => 19,
    'tax_per_amt' => 0,
    'discount_per_amt' => 0,
    'taxes_perc' => 0,
    'discount_perc' => 0,
    'offer_subject' => 'Special July Offer',
    'delivery_day' => 1,
    'order_in_favor_of' => '',
    'offer_warranty' => 1,
    'offer_calibration' => 1,
    'offer_validity' => 30,
    'show_discount' => 'Yes',
    'tax_included' => 'No',
    'order_by' => 1,
    'lead_id' => $lead_id,
    'offer_probability' => 80,
    'follow_up_date' => Carbon::tomorrow(),
    'Price_type' => 'pvt',
    'Price_value' => '<img src="https://www.stanlay.in/backoffice/images/Rupee.png" height="10"/>',
    'edited_enq_id' => 0,
    'deleteflag' => 'active',
    'total_order_cost_new' => $license_cost,
    'ensure_sale_month' => 11,
    'ensure_sale_month_date' => Carbon::now(),
    'offer_type' => 'service',
    'hot_offer' => 2,
    'tds_check_on_portal' => 0,
    'offer_currency' => 2,
    'freight_amount' => 0,
]);

$order_id = $order['orders_id'];

$orderProduct = OrderProduct::create([
    'order_id' => $order_id,
    'pro_id' => $product_id,
    'proidentry' => 1,
    'group_id' => 1,
    'qty_attDset_id' => 1,
    'customers_id' => $customer_id,
    'manufacturers_id' => 0,
    'pro_model' => '',
    'pro_name' => $product_name ?? '',
    'pro_price' => $price ?? 0,
    'pro_final_price' => $license_cost,
    'wrap_cost' => 0.00,
    'Pro_tax' => $tax_price,
    'GST_percentage' => '18',
    'freight_amount' => 0,
    'hsn_code' => $hsn_code ?? '0',
    'pro_quantity' =>$quantity ?? 0,
    'pro_discount_amount' => 0,
    'additional_disc' => 0.00,
    'proAttribute_Cost' => 0.0000,
    'order_pro_status' => 'Pending',
    'pro_text' => '',
    'pro_ret_remarks' => '',
    'pro_ret_qty' => 0,
    'pro_ret_amt' => 0.00,
    'barcode' => '',
    'service_period' => $duration ?? 0,
]);
$performa_invoice = PerformaInvoice::create([
    'O_Id'              => $order_id,
    'PO_NO'             => '0',
    'buyer_gst'         => $gst_number ?? '0',
    'PO_Due_Date'       =>  null,
    'Payment_Terms'     => 8 ?? '0',
    'Special_Ins'       => 'software' ?? null,
    'PO_path'           =>  null,
    'PO_Date'           =>  null,
    'pi_generated_date' => now(),
    'PO_From'           =>  0,
    'Cus_Com_Name'      => $organisation ?? '0',
    'Buyer_Name'        => $name ?? '0',
    'Buyer'             => $company_address ?? null,
    'Buyer_Tel'         => $mobile ?? '0',
    'Buyer_Fax'         => '0',
    'Buyer_Mobile'      => $mobile ?? '0',
    'Buyer_Email'       => $email  ?? null,
    'Prepared_by'       => 1 ?? 0,
    'pi_status'         =>  'pending',
    'branch_sel'        =>  1,
    'bank_sel'          =>  1,
    'Payment_Status'    => 'paid',
    'save_send'         => 'no',
    'service_order_id'  => 0,
    'pi_type'           => 'order',
    'performa_notes'    => null,
    'advance_received'  => $license_cost ?? 0.00,
]);
$performa_invoice_id = $performa_invoice['pi_id'];

$deliveryOrder = DeliveryOrder::create([
    'O_Id'                    => $order_id,
    'PO_NO'                   =>  null,
    'PO_Value'                =>  $license_cost  ?? 0.00 ,
    'Payment_Terms'           => 1 ?? null,
    'Special_Ins'             =>  1,
    'invoicing_instruction'   =>  1,
    'special_invoicing_ins'   =>  1,
    'PO_path'                 =>  0,
    'PO_Date'                 => Carbon::today(),
    'D_Order_Date'            => Carbon::today(),
    'PO_From'                 => $companyId ?? null,
    'Cus_Com_Name'            => $organisation ?? null,
    'Con_Com_Name'            => $organisation ?? null,
    'Buyer_Name'              => $name ?? null,
    'Con_Name'                => $name ?? null,
    'Buyer'                   => $company_address  ?? null,
    'Consignee'               => $company_address  ?? null,
    'Buyer_Tel'               => $mobile ?? null,
    'Con_Tel'                 => $mobile ?? null,
    'Buyer_Fax'               => null,
    'Con_Fax'                 =>  null,
    'Buyer_Mobile'            => $mobile ?? null,
    'Con_Mobile'              => $mobile ?? null,
    'Buyer_Email'             => $email ?? null,
    'Con_Email'               => $email ?? null,
    'Buyer_CST'               => null,
    'Con_CST'                 =>  null,
    'Tax_Per'                 =>  null,
    'Tax_Stat'                =>  null,
    'Dispatch'                => null,
    'Delivery'                => null,
    'Freight'                 =>  null,
    'Freight_amount'          =>  0,
    'Octroi_Value'            => null,
    'Octroi_Value_Rs'         => null,
    'Insurance'               => null,
    'delay_reason'            =>  '0',
    'Prepared_by'             => 1 ?? null,
    'DO_Status'               =>  'active',
    'Payment_Status'          => 'paid',
    'delivery_offer_warranty' => 0,
    'D_Order_Date1'           => Carbon::now(),
    'do_type'                 => 'service',
    'buyer_country'           => $company_country ?? null,
    'buyer_state'             => $company_state ?? null,
    'buyer_city'              => $company_city ?? null,
    'buyer_pincode'           =>  null,
    'con_country'             => $company_country ?? null,
    'con_state'               => $company_state ?? null,
    'con_city'                => $company_city ?? null,
    'con_pincode'             =>  null,
]);

  $deliveryOrder_id = $deliveryOrder['DO_ID'];

$doProduct = DoProduct::create([
    'OID'                => $deliveryOrder_id,
    'ItemCode'           =>  null,
    'pro_id'             => $product_id ?? '0',
    'pro_name'           => $product_name ?? null,
    'hsn_code'           => $hsn_code ?? '0',
    'Description'        => $notes ?? null,
    'Quantity'           => $quantity ?? 1,
    'Price'              => $price ?? '0.0000',
    'S_Inst'             => null,
    'PStatus'            =>  'active',
    'service_period'     => $duration ?? 0,
    'service_period_id'  => $duration ?? 0,
    'is_service'         => 'Y',
    'per_item_tax_rate'  => 18.00,
]);
$taxInvoice = TaxInvoice::create([
    'invoice_id'                   => $invoice_id ?? 0,
    'o_id'                         => $order_id ?? 0,
    'invoice_generated_date'      => now(),
    'cus_com_name'                 => $organisation ?? '0',
    'con_name'                     => $name  ?? '0',
    'con_address'                  => $company_address ?? '0',
    'con_country'                  => $company_country ?? '0',
    'con_state'                    =>  $company_state ?? '0',
    'con_city'                     => $company_city ?? '0',
    'con_mobile'                   => $mobile  ?? '0',
    'con_email'                    => $email ?? '0',
    'con_gst'                      => $gst_number  ?? '0',
    'buyer_name'                   => $name ?? '0',
    'buyer_address'                => $company_address ?? '0',
    'buyer_country'                => $company_country ?? '0',
    'buyer_state'                  =>  $company_state ?? '0',
    'buyer_city'                   => $company_city ?? '0',
    'buyer_mobile'                 => $mobile  ?? '0',
    'buyer_email'                  => $email ?? '0',
    'prepared_by'                  =>  0,
    'invoice_status'               => 'pending',
    'branch_sel'                   => 1,
    'bank_sel'                     =>  1,
    'payment_terms'               => '0',
    'save_send'                   =>  'no',
    'invoice_type'                =>  'service',
    'deleteflag'                  => 'active',
    'con_cust_co_name'            => $name ?? null,
    'con_pincode'                 =>  null,
    'buyer_gst'                   => $gst_number ?? null,
    'buyer_pin_code'              =>  null,
    'eway_bill_no'                => null,
    'delivery_note'               =>  null,
    'ref_no_and_date'             => null,
    'offer_ref'                   => null,
    'dispatch_doc_no'             =>  null,
 
    'Delivery'                    => $company_address ?? null,
    'destination'                 => null,
    'terms_of_delivery'           =>  null,
    'freight_amount'              =>  0,
    'freight_gst_amount'          =>  0,
    'total_gst_amount'            => $tax_price ?? 0,
    'sub_total_amount_without_gst'=> $sub_total_price?? 0,
    'gst_sale_type'               => 1,
    'invoice_currency'            =>  'INR',
    'flight_no'                   =>  '',
    'port_of_loading'             =>  '',
    'port_of_discharge'           =>  '',
    'final_destination'           =>  '',
    'country_of_origin_of_goods' => $company_country ?? '',
    'country_of_final_destination'=> $company_country ?? '',
    'invoice_approval_status'     =>  'pending',
    'invoice_remarks'             =>  null,
    'rental_start_date'           => $license_start_date,
    'rental_end_date'             => $license_end_date ?? now(),
    'transportation_mode'         =>  null,
    'transporter_document_name'   =>  null,
    'transporter_document_number' =>  null,
    'transporter_document_date'   =>  now(),
    'vehicle_number'              =>  null,
    'vehicle_type'                =>  null,
    'transport_id'                =>  null,
    'transport_name'              =>  null,
    'exchange_rate'               => '1',
    'rental_period_show'          =>  'Yes',
    'item_code_show'              => 'Yes',
    'invoice_closed_status'       =>  'Yes',
]);

$invoice_id = $taxInvoice['invoice_id'];

$invoiceProduct = InvoiceProduct::create([
    'tax_invoice_id'        => $invoice_id,
    'order_id'              => $order_id,
    'model_no'              =>  null,
    'pro_id'                => $product_id ?? '0',
    'hsn_code'              => $hsn_code ?? '0',
    'pro_description'       =>  null,
    'quantity'              => $quantity ?? 1,
    'price'                 => $price ?? 0.00,
    's_inst'                =>  null,
    'status'                => 'active',
    'service_period'        => $duration ?? 0,
    'is_service'            => 'Y',
    'per_item_tax_rate'     => 18.00,
]);
$paymentReceived = PaymentReceived::create([
    'invoice_id'                  => $invoice_id,
    'o_id'                        => $order_id,
    'payment_received_value'      => $license_cost ?? 0.00,
    'payment_received_value_tds'  => 0.00,
    'credit_note_value'           => 0.00,
    'lda_other_value'             =>  0.00,
    'payment_received_date'       =>  now()->toDateString(),
    'payment_received_via'        =>  'Online',
    'transaction_id'              =>  'N/A',
    'updated_by'                  =>  0,
    'inserted_date'               => now(),
    'payment_received_in_bank'    =>  '0',
    'payment_received_type'       =>  'full_payment_received',
    'tds_check_on_portal'         =>  0,
    'payment_remarks'             =>  null,
]);

  WebEnqEdit::where('enq_id', $webEnq_id)->update([
            'lead_id'           => $lead_id,
            'order_id'         => $order_id,

        ]);
          License::where('id', $license_id)->update([
            'order_id'         => $order_id,
            'proforma_invoice'         =>  $performa_invoice_id,

        ]);
        
//}
    }}




    if (!function_exists('craeteProformaInvoiceByLicense')) {
    function craeteProformaInvoiceByLicense($customer_id, $license_id)
    {
        $customer = Customer::find($customer_id);
        $license = License::find($license_id);

        if (!$customer || !$license) return false;

        // Customer details
        $organisation      = $customer->organisation;
        $name              = $customer->name;
        $email             = $customer->email;
        $mobile            = $customer->mobile;
        $company_address   = $customer->company_address;
        $company_country   = $customer->company_country;
        $company_state     = $customer->company_state;
        $company_city      = $customer->company_city;
        $segment_id        = $customer->segment_id;
        $notes             = $customer->notes;
        $source_id         = $customer->source_id;
        $gst_number        = $customer->gst_number;
        $company_website   = $customer->company_website;
        $account_manager   = $customer->account_manger_id;
        $customer_code     = $customer->customer_code;

        // License details
        $product_id          = $license->product_id;
        $price               = $license->price;
        $sub_total_price     = $license->sub_total_price;
        $tax_price           = $license->tax_price;
        $license_cost        = $license->license_cost;
        $duration            = $license->duration;
        $license_start_date  = $license->licenses_start_date;
        $license_end_date    = $license->licenses_end_date;
        $license_type        = $license->licenses_type;
        $quantity        = $license->licenses;

        // Product details
        $product      = ProductMain::where('pro_id', $product_id)->first();
        $product_name = $product?->pro_title;
        $upc_code     = $product?->upc_code;

        $productEntry = ProductsEntry::where('pro_id', $product_id)->first();
        $hsn_code     = $productEntry?->hsn_code;
  $category_id = $product?->cate_id;

$enqSource = EnqSource::where('enq_source_id',$source_id)->first();
        // Step 5: Create WebEnquiry
   $webEnq   =  WebEnq::create([
            'Cus_name'         => $name,
            'Cus_email'        => $email,
            'Cus_mob'          => $mobile,
            'Cus_msg'          => $notes,
            'product_category' => $category_id,
            'ref_source' => $enqSource->enq_source_description,
              'source_id' => $source_id,
        ]);

       WebEnqEdit::create([
            'enq_id'           => $webEnq->ID,
            'Cus_name'         => $name,
            'Cus_email'        => $email,
            'Cus_mob'          => $mobile,
            'Cus_msg'          => $notes,
            'country'          => $company_country,
            'city'             => $company_city,
            'state'            => $company_state,
            'acc_manager'      => $account_manager,
            'ref_source'       => $webEnq->ref_source,
            'cust_segment'     => $segment_id,
            'product_category' => $category_id,
            'address'          => $company_address,
            'enq_type'         => 'service',
            'price_type'       => 'pvt',
        ]);

        $nameParts = explode(" ", $name);
$fname = $nameParts[0] ?? '';
$lname = implode(" ", array_slice($nameParts, 1)) ?: '';
        // Company creation
        $company = Company::create([
            'parent_id'      => 0,
            'comp_name'      => $organisation,
            'comp_website'   => $company_website,
            'email'          => $email,
            'mobile_no'      => $mobile,
            'address'        => $company_address,
            'city'           => $company_city,
            'country'        => $company_country,
            'state'          => $company_state,
            'description'    => $notes,
            'ref_source'     => $source_id,
            'cust_segment'   => $segment_id,
            'acc_manager'    => $account_manager,
            'gst_no'         => $gst_number,
            'india_mart_co'  => 'no',
            'quality_check'  => 0,
            'co_division'    => '0',
            'co_city'        => '0',
            'key_customer'   => 0,
            'fname'          => $fname ?? null,
            'lname'          => $lname ?? null,
        ]);

 
// 1. Update Customer's Company ID
$companyId = $company['id'];

Customer::where('id', $customer_id)->update([
    'company_id' => $companyId,
]);




// 2. Create CompPerson Record
$compPerson = CompPerson::create([
    'company_id'     => $companyId,
    'salutation'     => $salutation ?? null,
    'fname'          => $fname ?? null,
    'lname'          => $lname ?? null,
    'comp_website'   => $company_website ?? null,
    'designation_id' => '0',
    'department_id'  => '0',
    'email'          => $email ?? null,
    'telephone'      => '',
    'mobile_no'      => $mobile ?? null,
    'fax_no'         => '',
    'address'        =>  $company_address,
    'city'           => $company_city,
    'country'        => $company_country,
    'state'          => $company_state ,
    'zip'            => '',
    'acc_manager'    => $account_manager ?? null,
]);
$comp_person_id = $compPerson['id'];
// 3. Create Lead Record
$lead = Lead::create([
    'ref_source'             => $source_id ?? null,
    'date_opened'            => now()->toDateString(),
    'time_lead_added'        => now(),
    'cust_segment'           =>  $segment_id  ?? null,
    'desc_details'           => $notes ?? null,
    'dec_time_frame'         =>  null,
    'estimated_value'        => $license_cost ?? 0,
    'acc_manager'            => $account_manager ?? null,
    'priority'               => 'Normal' ?? null,
    'lead_fname'             => $fname ?? null,
    'lead_lname'             => $lname ?? null,
    'lead_contact_address1'  =>  $company_address ?? null,
    'lead_email'             => $email ?? null,
    'lead_phone'             => $mobile ?? '0',
    'lead_contact_city'      => $company_city ?? null,
    'lead_contact_zip_code'  =>  null,
    'lead_area_code'         =>  null,
    'lead_fax'               =>  null,
    'lead_contact_state'     => $company_state ?? null,
    'lead_contact_country'   => $company_country ?? null,
    'comp_website'           => $company_website ?? null,
    'no_of_emp'              => 0,
    'comp_name'              => $companyId,
    'comp_person_id'         => $comp_person_id ?? 0,
    'comp_revenue'           =>  null,
    'app_cat_id'             => $category_id ?? 0,
    'enq_id'                 => $webEnq_id ?? '0',
    'competition'            =>  null,
    'status'                 => 'active',
    'offer_status'           => 1,
    'offer_type'             => 'service',
]);

 $lead_id = $lead['id'];

$leadProduct = LeadProduct::create([
    'lead_id'             => $lead_id,
    'pro_id'              => $product_id,
    'upc_code'            => $upc_code ?? 0,
    'price_list'          =>  null,
    'proidentry'          =>  0,
    'pro_category'        => $category_id ?? 0,
    'customers_id'        => $companyId,
    'pro_model'           => null,
    'pro_name'            => $product_name ?? '',
    'pro_price'           =>  $price ?? 0,
    'Pro_tax'             => $tax_price ?? 0,
    'GST_percentage'      => 18 ?? '0',
    'freight_amount'      =>  0,
    'hsn_code'            => $hsn_code ?? '0',
    'pro_quantity'        => $quantity ?? 0,
    'pro_sort'            =>  0,
    'pro_discount_amount' => 0,
    'lead_pro_status'     => 'Pending',
    'service_period'      => $duration ?? 0,
]);


$order = Order::create([
    'customers_id' => $companyId,
    'tes_linked_customer_id' => 0,
    'order_type' => 'Order',
    'customers_name' => $name,
    'customers_email' => $email,
    'customers_contact_no' => $mobile,
    'payment_mode' => 4,
    'shipping_company' => $organisation,
    'shipping_name' => $name,
    'shipping_street_address' => $company_address,
    'shipping_city' =>  $company_city,
    'shipping_zip_code' => '',
    'shipping_state' => $company_state,
    'shipping_country_name' => $company_country,
    'shipping_telephone_no' => $mobile,
    'shipping_fax_no' => null,
    'billing_name' => $name,
    'billing_company' => $organisation,
    'billing_street_address' => $company_address,
    'billing_city' =>  $company_city,
    'billing_zip_code' => '',
    'billing_state' => $company_state,
    'billing_country_name' => $company_country,
    'billing_telephone_no' => $mobile,
    'billing_fax_no' => null,
    'last_modified' => Carbon::now(),
    'date_ordered' => Carbon::today(),
    'time_ordered' => Carbon::now(),
    'orders_status' => 'Pending',
    'shipComment' => 'Handle with care.',
    'trackingNo' => '',
    'orders_date_finished' => null,
    'total_order_cost' => $license_cost,
    'cyber_amount' => 0.00,
    'cyber_credit' => 0.00,
    'tax_cost' => $tax_price ,
    'shipping_method_cost' => '0',
    'additional_disc' => 0.00,
    'payment_terms' => 19,
    'tax_per_amt' => 0,
    'discount_per_amt' => 0,
    'taxes_perc' => 0,
    'discount_perc' => 0,
    'offer_subject' => 'Special July Offer',
    'delivery_day' => 1,
    'order_in_favor_of' => '',
    'offer_warranty' => 1,
    'offer_calibration' => 1,
    'offer_validity' => 30,
    'show_discount' => 'Yes',
    'tax_included' => 'No',
    'order_by' => 1,
    'lead_id' => $lead_id,
    'offer_probability' => 80,
    'follow_up_date' => Carbon::tomorrow(),
    'Price_type' => 'pvt',
    'Price_value' => '<img src="https://www.stanlay.in/backoffice/images/Rupee.png" height="10"/>',
    'edited_enq_id' => 0,
    'deleteflag' => 'active',
    'total_order_cost_new' => $license_cost,
    'ensure_sale_month' => 11,
    'ensure_sale_month_date' => Carbon::now(),
    'offer_type' => 'service',
    'hot_offer' => 2,
    'tds_check_on_portal' => 0,
    'offer_currency' => 2,
    'freight_amount' => 0,
]);

$order_id = $order['orders_id'];

$orderProduct = OrderProduct::create([
    'order_id' => $order_id,
    'pro_id' => $product_id,
    'proidentry' => 1,
    'group_id' => 1,
    'qty_attDset_id' => 1,
    'customers_id' => $customer_id,
    'manufacturers_id' => 0,
    'pro_model' => '',
    'pro_name' => $product_name ?? '',
    'pro_price' => $price ?? 0,
    'pro_final_price' => $license_cost,
    'wrap_cost' => 0.00,
    'Pro_tax' => $tax_price,
    'GST_percentage' => '18',
    'freight_amount' => 0,
    'hsn_code' => $hsn_code ?? '0',
    'pro_quantity' =>$quantity ?? 0,
    'pro_discount_amount' => 0,
    'additional_disc' => 0.00,
    'proAttribute_Cost' => 0.0000,
    'order_pro_status' => 'Pending',
    'pro_text' => '',
    'pro_ret_remarks' => '',
    'pro_ret_qty' => 0,
    'pro_ret_amt' => 0.00,
    'barcode' => '',
    'service_period' => $duration ?? 0,
]);
$performa_invoice = PerformaInvoice::create([
    'O_Id'              => $order_id,
    'PO_NO'             => '0',
    'buyer_gst'         => $gst_number ?? '0',
    'PO_Due_Date'       =>  null,
    'Payment_Terms'     => 8 ?? '0',
    'Special_Ins'       => 'software' ?? null,
    'PO_path'           =>  null,
    'PO_Date'           =>  null,
    'pi_generated_date' => now(),
    'PO_From'           =>  0,
    'Cus_Com_Name'      => $organisation ?? '0',
    'Buyer_Name'        => $name ?? '0',
    'Buyer'             => $company_address ?? null,
    'Buyer_Tel'         => $mobile ?? '0',
    'Buyer_Fax'         => '0',
    'Buyer_Mobile'      => $mobile ?? '0',
    'Buyer_Email'       => $email  ?? null,
    'Prepared_by'       => 1 ?? 0,
    'pi_status'         =>  'pending',
    'branch_sel'        =>  1,
    'bank_sel'          =>  1,
    'Payment_Status'    => 'paid',
    'save_send'         => 'no',
    'service_order_id'  => 0,
    'pi_type'           => 'order',
    'performa_notes'    => null,
    'advance_received'  => $license_cost ?? 0.00,
]);
$performa_invoice_id = $performa_invoice['pi_id'];



  WebEnqEdit::where('enq_id', $webEnq_id)->update([
            'lead_id'           => $lead_id,
            'order_id'         => $order_id,

        ]);
          License::where('id', $license_id)->update([
            'order_id'         => $order_id,
            'proforma_invoice'         =>  $performa_invoice_id,

        ]);
        

    }}

    

    if (!function_exists('updateProformaInvoiceByLicense')) {
    function updateProformaInvoiceByLicense($customer_id, $license_id)
    {
              $customer = Customer::find($customer_id);
        $license = License::find($license_id);

        if (!$customer || !$license) return false;

        // Customer details
        $organisation      = $customer->organisation;
        $name              = $customer->name;
        $email             = $customer->email;
        $mobile            = $customer->mobile;
        $company_address   = $customer->company_address;
        $company_country   = $customer->company_country;
        $company_state     = $customer->company_state;
        $company_city      = $customer->company_city;
        $segment_id        = $customer->segment_id;
        $notes             = $customer->notes;
        $source_id         = $customer->source_id;
        $gst_number        = $customer->gst_number;
        $company_website   = $customer->company_website;
        $account_manager   = $customer->account_manger_id;
        $customer_code     = $customer->customer_code;

        // License details
        $product_id          = $license->product_id;
        $price               = $license->price;
        $sub_total_price     = $license->sub_total_price;
        $tax_price           = $license->tax_price;
        $license_cost        = $license->license_cost;
        $duration            = $license->duration;
        $license_start_date  = $license->licenses_start_date;
        $license_end_date    = $license->licenses_end_date;
        $license_type        = $license->licenses_type;
        $quantity        = $license->licenses;

        // Product details
        $product      = ProductMain::where('pro_id', $product_id)->first();
        $product_name = $product?->pro_title;
        $upc_code     = $product?->upc_code;

        $productEntry = ProductsEntry::where('pro_id', $product_id)->first();
        $hsn_code     = $productEntry?->hsn_code;
  $category_id = $product?->cate_id;


$order_id = $license->product_id;
$performa_invoice_id = $license->product_id;

$performa_invoice = PerformaInvoice::where('pi_id', $performa_invoice_id)->update([
    'buyer_gst'         => $gst_number ?? '0',
    'pi_status'         =>  'pending',
    'advance_received'  => $license_cost ?? 0.00,
]);

$order = Order::where('orders_id', $order_id)->update([

    'total_order_cost' => $license_cost,
    'tax_cost' => $tax_price ,
    'total_order_cost_new' => $license_cost,
]);

$orderProduct = OrderProduct::where('orders_id', $order_id)->update([
    'pro_id' => $product_id,
    'pro_name' => $product_name ?? '',
    'pro_price' => $price ?? 0,
    'pro_final_price' => $license_cost,
    'Pro_tax' => $tax_price,
    'GST_percentage' => '18',
    'hsn_code' => $hsn_code ?? '0',
    'pro_quantity' =>$quantity ?? 0,
    'order_pro_status' => 'Pending',
    'service_period' => $duration ?? 0,
]);

 $lead_id = $order['lead_id'];

// 3. Create Lead Record
$lead = Lead::where('lead_id', $lead_id)->update([

    'estimated_value'        => $license_cost ?? 0,
 
]);

$leadProduct = LeadProduct::where('lead_id', $lead_id)->update([
    'pro_id'              => $product_id,
    'upc_code'            => $upc_code ?? 0,
    'pro_category'        => $category_id ?? 0,
    'pro_name'            => $product_name ?? '',
    'pro_price'           =>  $price ?? 0,
    'Pro_tax'             => $tax_price ?? 0,
    'GST_percentage'      => 18 ?? '0',
    'pro_quantity'        => $quantity ?? 0,
    'pro_sort'            =>  0,
    'pro_discount_amount' => 0,
    'lead_pro_status'     => 'Pending',
    'service_period'      => $duration ?? 0,
]);

    }}