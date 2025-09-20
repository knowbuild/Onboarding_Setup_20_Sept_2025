<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Vendor;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VendorsExport;
use App\Models\VendorPaymentTermsMaster;
use App\Models\PriceBasis;
class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
#############################################Leads#############################################
#############################################Leads#############################################
//vendors listing	
public function vendorslisting(Request $request)
{
    $query = Vendor::query();

    // Filters
    if ($request->filled('vendor_id')) {
        $query->where('ID', $request->vendor_id);
    }


if (!is_null($request->fav_vendor)) {
    $query->where('fav_vendor', $request->fav_vendor);
}

if (!is_null($request->status)) {
    $query->where('status', $request->status);
}

if (!is_null($request->purchase_type)) {
    $query->where('purchase_type', $request->purchase_type);
}

if (!is_null($request->Country)) {
    $query->where('Country', $request->Country);
}



    if ($request->filled('search_by') && $request->search_by !== '0') {
        $keyword = $request->search_by;
        $query->where(function ($q) use ($keyword) {
            $q->where('C_Name', 'like', "%$keyword%")
              ->orWhere('Email', 'like', "%$keyword%")
              ->orWhere('Fax', 'like', "%$keyword%")
              ->orWhere('number2', 'like', "%$keyword%")
              ->orWhere('email2', 'like', "%$keyword%")
              ->orWhere('mobile2', 'like', "%$keyword%")
              ->orWhere('number3', 'like', "%$keyword%")
              ->orWhere('purchase_keywords', 'like', "%$keyword%");
        });
    }

    // Sorting
    $sort_by = $request->input('sort_by', 'ID');
    $order = ($sort_by === 'ID' || $sort_by === 'C_Name') ? 'desc' : 'asc';
    if ($sort_by === 'date_desc') {
        $sort_by = 'date_ordered';
    }
    $query->orderBy($sort_by, $order);

    // Export to Excel
    if ($request->input('export') === 'excel') {
        $allVendors = $query->select([
            'ID as vendor_id',
            'C_Name as vendor_name',
            'Email',
            'AddressName as vendor_address',
            'gst_no',
            'Contact_1 as management_contact_person',
            'Contact_2 as sales_contact_person',
            'Contact_3 as accounts_contact_person',
            'Fax as management_contact_number',
            'Number as landline_number',
            'number2 as sales_contact_number',
            'number3 as accounts_contact_number',
            'email2',
            'mobile2',
            'mobile3',
            'email3',
            'Country',
            'Currency',
            'purchase_keywords',
            'purchase_type',
            'Payment_Terms',
            'Price_Basis',
            'fav_vendor'
        ])->get();

        return Excel::download(new VendorsExport($allVendors), 'vendors.xlsx');
    }

    // ✅ Pagination using ?pageno & ?records
    $perPage = (int) $request->input('records', 10);
    $page = (int) $request->input('pageno', 1);
    $offset = ($page - 1) * $perPage;
    $totalCount = $query->count();

    $vendorData = $query->skip($offset)->take($perPage)->get();

    // Format vendor + embedded contact_data
   $vendors = $vendorData->map(function ($vendor) {
    // Helper function to clean 'N/A' or empty values
    $clean = fn($value) => ($value === 'N/A' || empty($value)) ? null : $value;

    return [
        'vendor_id' => $vendor->ID,
        'vendor_name' => $vendor->C_Name,
        'vendor_address' => $vendor->AddressName,
        'gst_no' => $vendor->gst_no,
        'landline_number' => $vendor->Number,
        'purchase_keywords' => $vendor->purchase_keywords,
        'purchase_type' => $vendor->purchase_type,
        'Payment_Terms' => $vendor->Payment_Terms,
        'Price_Basis' => $vendor->Price_Basis,
		'fav_vendor' => $vendor->fav_vendor,
        'Country' => $vendor->Country,
        'Currency' => $vendor->Currency,
        'contact_data' => [
            [
                'role' => 'management',
                'name' => $clean($vendor->Contact_3),
                'email' => $clean($vendor->email3),
                'mobile' => $clean($vendor->mobile3),
                'telephone' => $clean($vendor->number3),
                'designation' => $clean($vendor->management_designation),
            ],
            [
                'role' => 'sales',
                'name' => $clean($vendor->Contact_1),
                'email' => $clean($vendor->Email),
                'mobile' => $clean($vendor->sales_mobile),
                'telephone' => $clean($vendor->sales_telephone),
                'designation' => $clean($vendor->sales_designation),
            ],
            [
                'role' => 'accounts',
                'name' => $clean($vendor->Contact_2),
                'email' => $clean($vendor->email2),
                'mobile' => $clean($vendor->mobile2),
                'telephone' => $clean($vendor->number2),
                'designation' => $clean($vendor->accounts_designation),
            ],
            [
                'role' => 'support',
                'name' => $clean($vendor->support_name),
                'email' => $clean($vendor->support_email),
                'mobile' => $clean($vendor->support_mobile),
                'telephone' => $clean($vendor->support_telephone),
                'designation' => $clean($vendor->support_designation),
            ],
        ],
    ];
});


    return response()->json([
        'vendor_data' => $vendors,
        'num_rows_count' => $totalCount,
    ]);
}



 
 
public function add_new_vendor_old(Request $request)
    {
 $vendor_array				= $request->all(); 
 $todayDate					= date("Y-m-d H:i:s");	
 $date 	  					= date('Y-m-d');


    $dataArray["C_Name"]      		= addslashes($vendor_array["C_Name"]);
    $dataArray["AddressName"]   	= addslashes($vendor_array["AddressName"]);
    $dataArray["Number"]      		= $vendor_array["Number"];
    $dataArray["Email"]       		= $vendor_array["Email"];
    $dataArray["Fax"]      	 		= $vendor_array["Fax"];
    $dataArray["gst_no"]     	 	= $vendor_array["gst_no"];
    $dataArray["Contact_1"]     	= $vendor_array["Contact_1"];
    $dataArray["Contact_2"]     	= $vendor_array["Contact_2"];
    $dataArray["Contact_3"]     	= $vendor_array["Contact_3"];
    $dataArray["number2"]     		= $vendor_array["Number2"];
    $dataArray["email2"]      		= $vendor_array["Email2"];
    $dataArray["mobile2"]     		= $vendor_array["mobile2"];
    $dataArray["number3"]     		= $vendor_array["Number3"];
    $dataArray["email3"]      		= $vendor_array["Email3"];
    $dataArray["mobile3"]     		= $vendor_array["mobile3"];
    $dataArray["purchase_type"] 	= $vendor_array["purchase_type"];
    $dataArray["Country"]     		= $vendor_array["Country"];
    $dataArray["Currency"]      	= $vendor_array["Currency"];
    $dataArray["Price_Basis"]   	= $vendor_array["Price_Basis"];
    $dataArray["Payment_Terms"] 	= $vendor_array["Payment_Terms"];
    $dataArray["status"]      		= $vendor_array["status"];
    $dataArray["vendor_moq"]    	= $vendor_array["vendor_moq"];	
    $dataArray["vendor_confirmed"]  = $vendor_array["vendor_confirmed"];
    $dataArray["purchase_keywords"] = $vendor_array["purchase_keywords"];


   $inserted_vendor_id 			= DB::table('vendor_master')->insertGetId($dataArray);			
 if($inserted_vendor_id){
            $msg = "true"; 
        }else{
            $msg = "false";
       };  
	   
  return response()->json([            
        'message' => $msg, 
		 'inserted_vendor_id' => $inserted_vendor_id, 
        ]);
    }	
	
	
	
public function addNewVendor_old11_06_2025(Request $request)
{

		
    $validated = $request->validate([
        // Basic Data
        'basicData.companyName'           => 'required|string|max:255',
        'basicData.telephone'             => 'nullable|string|max:50',
        'basicData.mobile'                => 'nullable|string|max:50',
        'basicData.gstNumber'             => 'nullable|string|max:100',
        'basicData.officeAddress'         => 'nullable|string|max:255',
        'basicData.officeCountry'         => 'nullable|string|max:100',
        'basicData.officeState'           => 'nullable|string|max:100',
        'basicData.officeCity'            => 'nullable|string|max:100',
        'basicData.officeZipCode'         => 'nullable|string|max:20',
        'basicData.manufacturingAddress'  => 'nullable|string|max:255',
        'basicData.manufacturingCountry'  => 'nullable|string|max:100',
        'basicData.manufacturingState'    => 'nullable|string|max:100',
        'basicData.manufacturingCity'     => 'nullable|string|max:100',
        'basicData.manufacturingZipCode'  => 'nullable|string|max:20',
		'basicData.vendor_moq' 			  => 'nullable|numeric',
        'basicData.vendorStatus'          => 'nullable|string|max:50',

        // Terms Data
        'termsData.purchaseCurrency'      => 'nullable|string|max:50',
        'termsData.purchaseType'          => 'nullable|string|max:50',
        'termsData.paymentTerms'          => 'nullable|string|max:100',
        'termsData.priceBasis'            => 'nullable|string|max:100',
        'termsData.freightType'           => 'nullable|string|max:50',
        'termsData.warranty'              => 'nullable|string|max:100',
        'termsData.purchaseKeywords'      => 'nullable|string',

        // Contact Data - Sales
        'contactData.sales.name'          => 'nullable|string|max:100',
        'contactData.sales.email'         => 'nullable|email|max:255',
        'contactData.sales.mobile'        => 'nullable|string|max:50',
        'contactData.sales.telephone'     => 'nullable|string|max:50',
        'contactData.sales.designation'   => 'nullable|string|max:100',

        // Contact Data - Accounts
        'contactData.accounts.name'       => 'nullable|string|max:100',
        'contactData.accounts.email'      => 'nullable|email|max:255',
        'contactData.accounts.mobile'     => 'nullable|string|max:50',
        'contactData.accounts.telephone'  => 'nullable|string|max:50',
        'contactData.accounts.designation'=> 'nullable|string|max:100',

        // Contact Data - Management
        'contactData.management.name'       => 'nullable|string|max:100',
        'contactData.management.email'      => 'nullable|email|max:255',
        'contactData.management.mobile'     => 'nullable|string|max:50',
        'contactData.management.telephone'  => 'nullable|string|max:50',
        'contactData.management.designation'=> 'nullable|string|max:100',

        // Contact Data - Support
        'contactData.support.name'         => 'nullable|string|max:100',
        'contactData.support.email'        => 'nullable|email|max:255',
        'contactData.support.mobile'       => 'nullable|string|max:50',
        'contactData.support.telephone'    => 'nullable|string|max:50',
        'contactData.support.designation'  => 'nullable|string|max:100',

        // Account Data
        'accountData.bankName'            => 'nullable|string|max:150',
        'accountData.beneficiaryName'     => 'nullable|string|max:255',
        'accountData.accountNumber'       => 'nullable|string|max:100',
        'accountData.reAccountNumber'     => 'nullable|string|max:100',
        'accountData.ifsc'                => 'nullable|string|max:50',
    ]);
/*return response()->json([            
        'message' => "uooo", 
		
        ]);*/
    $vendorName = $validated['basicData']['companyName'];

    if (vendor_name_count($vendorName) > 0) {
        return response()->json([
            'status' => false,
            'message' => 'Vendor with this name already exists.',
            'inserted_vendor_id' => null,
        ], 409);
    }
	
    $vendorData = [
        // Basic Info
        'C_Name'            => $vendorName,
        'AddressName'       => $validated['basicData']['officeAddress'] ?? null,
        'Number'            => $validated['basicData']['telephone'] ?? null,
        'Fax'               => $validated['basicData']['mobile'] ?? null,
        'gst_no'            => $validated['basicData']['gstNumber'] ?? null,
        'Country'           => $validated['basicData']['officeCountry'] ?? null,
        'state'             => $validated['basicData']['officeState'] ?? null,
        'city'              => $validated['basicData']['officeCity'] ?? null,
        'pincode'           => $validated['basicData']['officeZipCode'] ?? null,
        'manufacturing_address' => $validated['basicData']['manufacturingAddress'] ?? null,
        'manufacturing_Country' => $validated['basicData']['manufacturingCountry'] ?? null,
        'manufacturing_state'   => $validated['basicData']['manufacturingState'] ?? null,
        'manufacturing_city'    => $validated['basicData']['manufacturingCity'] ?? null,
        'manufacturing_pincode' => $validated['basicData']['manufacturingZipCode'] ?? null,
        'status'         => $validated['basicData']['vendorStatus'] ?? null,

        // Sales Contact
        'Contact_1'         => !empty($validated['contactData']['sales']['name'])? $validated['contactData']['sales']['name']: '',
        'Email'             => $validated['contactData']['sales']['email'] ?? null,
        'sales_mobile'      => $validated['contactData']['sales']['mobile'] ?? null,
        'sales_telephone'   => $validated['contactData']['sales']['telephone'] ?? null,
        'sales_designation' => $validated['contactData']['sales']['designation'] ?? null,

        // Accounts Contact
        'Contact_2'         => !empty($validated['contactData']['accounts']['name'])? $validated['contactData']['accounts']['name']: '',
        'Email2'            => $validated['contactData']['accounts']['email'] ?? null,
        'mobile2'           => $validated['contactData']['accounts']['mobile'] ?? null,
        'Number2'           => $validated['contactData']['accounts']['telephone'] ?? null,
        'accounts_designation' => $validated['contactData']['accounts']['designation'] ?? null,

        // Management Contact
//      'Contact_3'         => $validated['contactData']['management']['name'] ?? null,
		
		'Contact_3' 		=> !empty($validated['contactData']['management']['name'])? $validated['contactData']['management']['name']: '',		
        'Email3'            => $validated['contactData']['management']['email'] ?? null,
        'mobile3'           => $validated['contactData']['management']['mobile'] ?? null,
        'Number3'           => $validated['contactData']['management']['telephone'] ?? null,
        'management_designation' => $validated['contactData']['management']['designation'] ?? null,

        // Support Contact
        'support_name'       => !empty($validated['contactData']['support']['name'])? $validated['contactData']['support']['name']: '',
        'support_email'      => $validated['contactData']['support']['email'] ?? null,
        'support_mobile'     => $validated['contactData']['support']['mobile'] ?? null,
        'support_telephone'  => $validated['contactData']['support']['telephone'] ?? null,
        'support_designation'=> $validated['contactData']['support']['designation'] ?? null,

        // Terms
        'purchase_type'     => $validated['termsData']['purchaseType'] ?? null,
        'Currency'          => $validated['termsData']['purchaseCurrency'] ?? null,
        'Price_Basis'       => $validated['termsData']['priceBasis'] ?? null,
        'Payment_Terms'     => $validated['termsData']['paymentTerms'] ?? null,
//        'freight_type'      => $validated['termsData']['freightType'] ?? null,
//        'warranty'          => $validated['termsData']['warranty'] ?? null,
        'purchase_keywords' => $validated['termsData']['purchaseKeywords'] ?? null,

        // Accounts Info
      /*  'bank_name'         => $validated['accountData']['bankName'] ?? null,
        'beneficiary_name'  => $validated['accountData']['beneficiaryName'] ?? null,
        'account_number'    => $validated['accountData']['accountNumber'] ?? null,
        're_account_number' => $validated['accountData']['reAccountNumber'] ?? null,
        'ifsc_code'         => $validated['accountData']['ifsc'] ?? null,*/

        // Defaults
        'vendor_moq'        => $validated['basicData']['vendor_moq'] ?? null,
        'vendor_confirmed'  => 1,
    ];

    $vendor = Vendor::create($vendorData);
	
	if ($vendor) {
    DB::table('vendor_bank_details')->insert([
        'vendor_id'          => $vendor->ID,
        'bank_name'          => $validated['accountData']['bankName'] ?? null,
        'beneficiary_name'   => $validated['accountData']['beneficiaryName'] ?? null,
        'account_number'     => $validated['accountData']['accountNumber'] ?? null,
        're_account_number'  => $validated['accountData']['reAccountNumber'] ?? null,
        'ifsc_code'          => $validated['accountData']['ifsc'] ?? null,
        'status'             => 'active',
        'deleteflag'         => 'active',
    ]);
}

    return response()->json([
        'status' => (bool) $vendor,
        'message' => $vendor ? 'Vendor added successfully.' : 'Failed to add vendor.',
        'inserted_vendor_id' => $vendor->id ?? null,
    ], $vendor ? 201 : 500);
	
}
 
 
 
 
 public function addNewVendor(Request $request)
{
    $validated = $request->validate([
        // Basic Data
        'basicData.companyName'           => 'required|string|max:255',
        'basicData.telephone'             => 'nullable|string|max:50',
        'basicData.mobile'                => 'nullable|string|max:50',
        'basicData.gstNumber'             => 'nullable|string|max:100',
        'basicData.officeAddress'         => 'nullable|string|max:255',
        'basicData.officeCountry'         => 'nullable|string|max:100',
        'basicData.officeState'           => 'nullable|string|max:100',
        'basicData.officeCity'            => 'nullable|string|max:100',
        'basicData.officeZipCode'         => 'nullable|string|max:20',
        'basicData.manufacturingAddress'  => 'nullable|string|max:255',
        'basicData.manufacturingCountry'  => 'nullable|string|max:100',
        'basicData.manufacturingState'    => 'nullable|string|max:100',
        'basicData.manufacturingCity'     => 'nullable|string|max:100',
        'basicData.manufacturingZipCode'  => 'nullable|string|max:20',
        'basicData.vendor_moq'            => 'nullable|numeric',
        'basicData.vendorStatus'          => 'nullable|string|max:50',

        // Terms Data
        'termsData.purchaseCurrency'      => 'nullable|string|max:50',
        'termsData.purchaseType'          => 'nullable|string|max:50',
        'termsData.paymentTerms'          => 'nullable|string|max:100',
        'termsData.priceBasis'            => 'nullable|string|max:100',
        'termsData.freightType'           => 'nullable|string|max:50',
        'termsData.warranty'              => 'nullable|string|max:100',
        'termsData.purchaseKeywords'      => 'nullable|string',

        // Contact Data - all types
        'contactData.*.name'              => 'nullable|string|max:100',
        'contactData.*.email'             => 'nullable|email|max:255',
        'contactData.*.mobile'            => 'nullable|string|max:50',
        'contactData.*.telephone'         => 'nullable|string|max:50',
        'contactData.*.designation'       => 'nullable|string|max:100',

        // Account Data
        'accountData.bankName'            => 'nullable|string|max:150',
        'accountData.beneficiaryName'     => 'nullable|string|max:255',
        'accountData.accountNumber'       => 'nullable|string|max:100',
        'accountData.reAccountNumber'     => 'nullable|string|max:100',
        'accountData.ifsc'                => 'nullable|string|max:50',
    ]);

    $vendorName = $validated['basicData']['companyName'];
    if (vendor_name_count($vendorName) > 0) {
        return response()->json([
            'status' => false,
            'message' => 'Vendor with this name already exists.',
            'inserted_vendor_id' => null,
        ], 409);
    }

    // Flatten contacts for old fields
    $sales     = $validated['contactData']['sales'] ?? [];
    $accounts  = $validated['contactData']['accounts'] ?? [];
    $mgmt      = $validated['contactData']['management'] ?? [];
    $support   = $validated['contactData']['support'] ?? [];

    $vendorData = [
        // Basic Info
        'C_Name'                => $vendorName,
        'AddressName'           => $validated['basicData']['officeAddress'] ?? null,
        'Number'                => $validated['basicData']['telephone'] ?? null,
        'Fax'                   => $validated['basicData']['mobile'] ?? null,
        'gst_no'                => $validated['basicData']['gstNumber'] ?? null,
        'Country'               => $validated['basicData']['officeCountry'] ?? null,
        'state'                 => $validated['basicData']['officeState'] ?? null,
        'city'                  => $validated['basicData']['officeCity'] ?? null,
        'pincode'               => $validated['basicData']['officeZipCode'] ?? null,
        'manufacturing_address' => $validated['basicData']['manufacturingAddress'] ?? null,
        'manufacturing_Country' => $validated['basicData']['manufacturingCountry'] ?? null,
        'manufacturing_state'   => $validated['basicData']['manufacturingState'] ?? null,
        'manufacturing_city'    => $validated['basicData']['manufacturingCity'] ?? null,
        'manufacturing_pincode' => $validated['basicData']['manufacturingZipCode'] ?? null,
        'status'                => $validated['basicData']['vendorStatus'] ?? null,
        'vendor_moq'            => $validated['basicData']['vendor_moq'] ?? null,
        'vendor_confirmed'      => 1,
        'purchase_type'         => $validated['termsData']['purchaseType'] ?? null,
        'Currency'              => $validated['termsData']['purchaseCurrency'] ?? null,
        'Price_Basis'           => $validated['termsData']['priceBasis'] ?? null,
        'Payment_Terms'         => $validated['termsData']['paymentTerms'] ?? null,
        'purchase_keywords'     => $validated['termsData']['purchaseKeywords'] ?? null,

        // 🟡 Old fields - Sales
        'Contact_1'         => $sales['name'] ?? '',
        'Email'             => $sales['email'] ?? '',
        'sales_mobile'      => $sales['mobile'] ?? '',
        'sales_telephone'   => $sales['telephone'] ?? '',
        'sales_designation' => $sales['designation'] ?? '',

        // 🟡 Old fields - Accounts
        'Contact_2'         => $accounts['name'] ?? '',
        'Email2'            => $accounts['email'] ?? '',
        'mobile2'           => $accounts['mobile'] ?? '',
        'Number2'           => $accounts['telephone'] ?? '',
        'accounts_designation' => $accounts['designation'] ?? '',

        // 🟡 Old fields - Management
        'Contact_3'         => $mgmt['name'] ?? '',
        'Email3'            => $mgmt['email'] ?? '',
        'mobile3'           => $mgmt['mobile'] ?? '',
        'Number3'           => $mgmt['telephone'] ?? '',
        'management_designation' => $mgmt['designation'] ?? '',

        // 🟡 Old fields - Support
        'support_name'       => $support['name'] ?? '',
        'support_email'      => $support['email'] ?? '',
        'support_mobile'     => $support['mobile'] ?? '',
        'support_telephone'  => $support['telephone'] ?? '',
        'support_designation'=> $support['designation'] ?? '',
    ];

    $vendor = Vendor::create($vendorData);

    if ($vendor) {
        // ✅ Insert into vendor_bank_details
        DB::table('vendor_bank_details')->insert([
            'vendor_id'         => $vendor->ID,
            'bank_name'         => $validated['accountData']['bankName'] ?? null,
            'beneficiary_name'  => $validated['accountData']['beneficiaryName'] ?? null,
            'account_number'    => $validated['accountData']['accountNumber'] ?? null,
            're_account_number' => $validated['accountData']['reAccountNumber'] ?? null,
            'ifsc_code'         => $validated['accountData']['ifsc'] ?? null,
            'status'            => 'active',
            'deleteflag'        => 'active',
        ]);

        // ✅ Insert contacts into vendor_contacts
        $types = ['sales', 'accounts', 'management', 'support'];
        foreach ($types as $type) {
            $contact = $validated['contactData'][$type] ?? [];
            if (!empty($contact['name'])) {
                DB::table('vendor_contacts')->insert([
                    'vendor_id'    => $vendor->ID,
                    'name'         => $contact['name'],
                    'email'        => $contact['email'] ?? null,
                    'mobile'       => $contact['mobile'] ?? null,
                    'telephone'    => $contact['telephone'] ?? null,
                    'designation'  => $contact['designation'] ?? null,
                    'type'         => $type,
                    'status'       => 'active',
                    'deleteflag'   => 'active',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }

    return response()->json([
        'status' => (bool) $vendor,
        'message' => $vendor ? 'Vendor added successfully.' : 'Failed to add vendor.',
        'inserted_vendor_id' => $vendor->ID ?? null,
    ], $vendor ? 201 : 500);
}


public function vendor_payment_terms_master()
{
    $terms = VendorPaymentTermsMaster::active() // using the scope
        ->orderBy('vendor_payment_terms_name', 'asc')
        ->select('vendor_payment_terms_id', 'vendor_payment_terms_name', 'vendor_payment_terms_abbrv')
        ->get();

    return response()->json([
        'vendor_payment_terms' => $terms,
    ]);
}

public function price_basis_master()
{
    $terms = PriceBasis::active()
        ->orderBy('price_basis_name', 'asc')
        ->select('price_basis_id', 'price_basis_name')
        ->get();

    return response()->json([
        'price_basis_master' => $terms,
    ]);
}


///
public function vendorDetailsById(Request $request)
{
    $vendorId = $request->query('vendor_id');

    if (!$vendorId) {
        return response()->json(['error' => 'vendor_id is required'], 400);
    }

    $vendor = DB::table('vendor_master')
        ->where('ID', $vendorId)
        ->where('deleteflag', '!=', 'deleted')
        ->first();

    if (!$vendor) {
        return response()->json([
            'vendor_data' => [],
            'num_rows_count' => 0
        ]);
    }

    // 🔄 New contact data from vendor_contacts table
    $contactTypes = ['sales', 'accounts', 'management', 'support'];
    $contactsFromDB = DB::table('vendor_contacts')
        ->where('vendor_id', $vendorId)
        ->where('deleteflag', 'active')
        ->whereIn('type', $contactTypes)
        ->get()
        ->keyBy('type');

  $contactDataNew = [];

foreach ($contactTypes as $type) {
    $c = $contactsFromDB[$type] ?? null;

    $contactDataNew[] = [
        'role'        => $type,
        'name'        => $c->name ?? null,
        'email'       => $c->email ?? null,
        'mobile'      => $c->mobile ?? null,
        'telephone'   => $c->telephone ?? null,
        'designation' => $c->designation ?? null,
    ];
}

    return response()->json([
        'basicData' => [
            'companyName'           => $vendor->C_Name,
            'telephone'             => $vendor->Number,
            'mobile'                => $vendor->Fax,
            'gstNumber'             => $vendor->gst_no,
            'vendor_moq'            => $vendor->vendor_moq,
            'officeAddress'         => $vendor->AddressName,
            'officeCountry'         => $vendor->Country,
            'officeState'           => $vendor->state,
            'officeCity'            => $vendor->city,
            'officeZipCode'         => $vendor->pincode,
            'manufacturingAddress'  => $vendor->manufacturing_address,
            'manufacturingCountry'  => $vendor->manufacturing_Country,
            'manufacturingState'    => $vendor->manufacturing_state,
            'manufacturingCity'     => $vendor->manufacturing_city,
            'manufacturingZipCode'  => $vendor->manufacturing_pincode,
            'vendorStatus'          => $vendor->status,
            'fav_vendor'            => $vendor->fav_vendor,
        ],

        // 🟡 Old flat field contact data
        'contactData' => [
            'sales' => [
                'name' => ($vendor->Contact_1 === 'N/A' || empty($vendor->Contact_1)) ? null : $vendor->Contact_1,
                'email' => empty($vendor->Email) ? null : $vendor->Email,
                'mobile' => empty($vendor->sales_mobile) ? null : $vendor->sales_mobile,
                'telephone' => empty($vendor->sales_telephone) ? null : $vendor->sales_telephone,
                'designation' => empty($vendor->sales_designation) ? null : $vendor->sales_designation,
            ],
            'accounts' => [
                'name' => ($vendor->Contact_2 === 'N/A' || empty($vendor->Contact_2)) ? null : $vendor->Contact_2,
                'email' => empty($vendor->email2) ? null : $vendor->email2,
                'mobile' => empty($vendor->mobile2) ? null : $vendor->mobile2,
                'telephone' => empty($vendor->number2) ? null : $vendor->number2,
                'designation' => empty($vendor->accounts_designation) ? null : $vendor->accounts_designation,
            ],
            'management' => [
                'name' => ($vendor->Contact_3 === 'N/A' || empty($vendor->Contact_3)) ? null : $vendor->Contact_3,
                'email' => empty($vendor->email3) ? null : $vendor->email3,
                'mobile' => empty($vendor->mobile3) ? null : $vendor->mobile3,
                'telephone' => empty($vendor->number3) ? null : $vendor->number3,
                'designation' => empty($vendor->management_designation) ? null : $vendor->management_designation,
            ],
            'support' => [
                'name' => ($vendor->support_name === 'N/A' || empty($vendor->support_name)) ? null : $vendor->support_name,
                'email' => empty($vendor->support_email) ? null : $vendor->support_email,
                'mobile' => empty($vendor->support_mobile) ? null : $vendor->support_mobile,
                'telephone' => empty($vendor->support_telephone) ? null : $vendor->support_telephone,
                'designation' => empty($vendor->support_designation) ? null : $vendor->support_designation,
            ],
        ],

        // ✅ New normalized contact data
        'contactDataNew' => $contactDataNew,

        'termsData' => [
            'purchaseCurrency'  => $vendor->Currency,
            'purchaseType'      => $vendor->purchase_type,
            'paymentTerms'      => $vendor->Payment_Terms,
            'priceBasis'        => $vendor->Price_Basis,
            'purchaseKeywords'  => $vendor->purchase_keywords,
        ],

        // ✅ Bank details
        'accountData' => vendor_bank_details($vendorId),
    ]);
}




//public function editVendor(Request $request, $vendor_id)
//{
//    $validated = $request->validate([
//        // Basic Data
//        'basicData.companyName'           => 'required|string|max:255',
//        'basicData.telephone'             => 'nullable|string|max:50',
//        'basicData.mobile'                => 'nullable|string|max:50',
//        'basicData.gstNumber'             => 'nullable|string|max:100',
//        'basicData.officeAddress'         => 'nullable|string|max:255',
//        'basicData.officeCountry'         => 'nullable|string|max:100',
//        'basicData.officeState'           => 'nullable|string|max:100',
//        'basicData.officeCity'            => 'nullable|string|max:100',
//        'basicData.officeZipCode'         => 'nullable|numeric',
//        'basicData.manufacturingAddress'  => 'nullable|string|max:255',
//        'basicData.manufacturingCountry'  => 'nullable|string|max:100',
//        'basicData.manufacturingState'    => 'nullable|string|max:100',
//        'basicData.manufacturingCity'     => 'nullable|string|max:100',
//        'basicData.manufacturingZipCode'  => 'nullable|numeric',
//        'basicData.vendor_moq'            => 'nullable|numeric',
////        'basicData.vendorStatus'          => 'nullable|string|max:50',
//		'basicData.vendorStatus' => 'nullable|string|in:active,inactive',
//
//        // Terms Data
//        'termsData.purchaseCurrency'      => 'nullable|string|max:50',
//        'termsData.purchaseType'          => 'nullable|string|max:50',
//        'termsData.paymentTerms'          => 'nullable|string|max:100',
//        'termsData.priceBasis'            => 'nullable|string|max:100',
//        'termsData.freightType'           => 'nullable|string|max:50',
//        'termsData.warranty'              => 'nullable|string|max:100',
//        'termsData.purchaseKeywords'      => 'nullable|string',
//
//        // Contact Data - Sales
//        'contactData.sales.name'          => 'nullable|string|max:100',
//        'contactData.sales.email'         => 'nullable|email|max:255',
//        'contactData.sales.mobile'        => 'nullable|string|max:50',
//        'contactData.sales.telephone'     => 'nullable|string|max:50',
//        'contactData.sales.designation'   => 'nullable|string|max:100',
//
//        // Contact Data - Accounts
//        'contactData.accounts.name'       => 'nullable|string|max:100',
//        'contactData.accounts.email'      => 'nullable|email|max:255',
//        'contactData.accounts.mobile'     => 'nullable|string|max:50',
//        'contactData.accounts.telephone'  => 'nullable|string|max:50',
//        'contactData.accounts.designation'=> 'nullable|string|max:100',
//
//        // Contact Data - Management
//        'contactData.management.name'       => 'nullable|string|max:100',
//        'contactData.management.email'      => 'nullable|email|max:255',
//        'contactData.management.mobile'     => 'nullable|string|max:50',
//        'contactData.management.telephone'  => 'nullable|string|max:50',
//        'contactData.management.designation'=> 'nullable|string|max:100',
//
//        // Contact Data - Support
//        'contactData.support.name'         => 'nullable|string|max:100',
//        'contactData.support.email'        => 'nullable|email|max:255',
//        'contactData.support.mobile'       => 'nullable|string|max:50',
//        'contactData.support.telephone'    => 'nullable|string|max:50',
//        'contactData.support.designation'  => 'nullable|string|max:100',
//
//        // Account Data
//        'accountData.bankName'            => 'nullable|string|max:150',
//        'accountData.beneficiaryName'     => 'nullable|string|max:255',
//        'accountData.accountNumber'       => 'nullable|string|max:100',
//        'accountData.reAccountNumber'     => 'nullable|string|max:100',
//        'accountData.ifsc'                => 'nullable|string|max:50',
//    ]);
//
//    $vendor = Vendor::find($vendor_id);
//    if (!$vendor) {
//        return response()->json([
//            'status' => false,
//            'message' => 'Vendor not found.',
//        ], 404);
//    }
//
//    $vendorData = [
//        'C_Name'                => $validated['basicData']['companyName'],
//        'AddressName'           => $validated['basicData']['officeAddress'] ?? null,
//        'Number'                => $validated['basicData']['telephone'] ?? null,
//        'Fax'                   => $validated['basicData']['mobile'] ?? null,
//        'gst_no'                => $validated['basicData']['gstNumber'] ?? null,
//        'Country'               => $validated['basicData']['officeCountry'] ?? null,
//        'state'                 => $validated['basicData']['officeState'] ?? null,
//        'city'                  => $validated['basicData']['officeCity'] ?? null,
//        'pincode'               => $validated['basicData']['officeZipCode'] ?? null,
//        'manufacturing_address' => $validated['basicData']['manufacturingAddress'] ?? null,
//        'manufacturing_Country' => $validated['basicData']['manufacturingCountry'] ?? null,
//        'manufacturing_state'   => $validated['basicData']['manufacturingState'] ?? null,
//        'manufacturing_city'    => $validated['basicData']['manufacturingCity'] ?? null,
//        'manufacturing_pincode' => $validated['basicData']['manufacturingZipCode'] ?? null,
//        'status'                => $validated['basicData']['vendorStatus'],
//
//        // Sales Contact
//        'Contact_1'         => $validated['contactData']['sales']['name'] ?? '',
//        'Email'             => $validated['contactData']['sales']['email'] ?? null,
//        'sales_mobile'      => $validated['contactData']['sales']['mobile'] ?? null,
//        'sales_telephone'   => $validated['contactData']['sales']['telephone'] ?? null,
//        'sales_designation' => $validated['contactData']['sales']['designation'] ?? null,
//
//        // Accounts Contact
//        'Contact_2'             => $validated['contactData']['accounts']['name'] ?? '',
//        'Email2'                => $validated['contactData']['accounts']['email'] ?? null,
//        'mobile2'               => $validated['contactData']['accounts']['mobile'] ?? null,
//        'Number2'               => $validated['contactData']['accounts']['telephone'] ?? null,
//        'accounts_designation' => $validated['contactData']['accounts']['designation'] ?? null,
//
//        // Management Contact
//        'Contact_3'              => $validated['contactData']['management']['name'] ?? '',
//        'Email3'                 => $validated['contactData']['management']['email'] ?? null,
//        'mobile3'                => $validated['contactData']['management']['mobile'] ?? null,
//        'Number3'                => $validated['contactData']['management']['telephone'] ?? null,
//        'management_designation'=> $validated['contactData']['management']['designation'] ?? null,
//
//        // Support Contact
//        'support_name'         => $validated['contactData']['support']['name'] ?? '',
//        'support_email'        => $validated['contactData']['support']['email'] ?? null,
//        'support_mobile'       => $validated['contactData']['support']['mobile'] ?? null,
//        'support_telephone'    => $validated['contactData']['support']['telephone'] ?? null,
//        'support_designation'  => $validated['contactData']['support']['designation'] ?? null,
//
//        // Terms
//        'purchase_type'        => $validated['termsData']['purchaseType'] ?? null,
//        'Currency'             => $validated['termsData']['purchaseCurrency'] ?? null,
//        'Price_Basis'          => $validated['termsData']['priceBasis'] ?? null,
//        'Payment_Terms'        => $validated['termsData']['paymentTerms'] ?? null,
//        'purchase_keywords'    => $validated['termsData']['purchaseKeywords'] ?? null,
//
//        // Accounts Info
//        'bank_name'            => $validated['accountData']['bankName'] ?? null,
//        'beneficiary_name'     => $validated['accountData']['beneficiaryName'] ?? null,
//        'account_number'       => $validated['accountData']['accountNumber'] ?? null,
//        're_account_number'    => $validated['accountData']['reAccountNumber'] ?? null,
//        'ifsc_code'            => $validated['accountData']['ifsc'] ?? null,
//        'vendor_moq'           => $validated['basicData']['vendor_moq'] ?? null,
//  ];
//
//    $vendor->fill($vendorData);
//    $vendor->save();
//
//  /*  if ($vendor->wasChanged()) {
//        return response()->json([
//            'success' => true,
//            'message' => 'Vendor updated successfully.',
//            'updated_fields' => $vendor->getChanges(),
//        ]);
//    }*/
//
//    return response()->json([
//        'success' => false,
//        'message' => 'No changes made to the vendor.',
//    ], 200);
//}



public function editVendor(Request $request, $vendor_id)
{
    $validated = $request->validate([
        // Basic Data
        'basicData.companyName'           => 'required|string|max:255',
        'basicData.telephone'             => 'nullable|string|max:50',
        'basicData.mobile'                => 'nullable|string|max:50',
        'basicData.gstNumber'             => 'nullable|string|max:100',
        'basicData.officeAddress'         => 'nullable|string|max:255',
        'basicData.officeCountry'         => 'nullable|string|max:100',
        'basicData.officeState'           => 'nullable|string|max:100',
        'basicData.officeCity'            => 'nullable|string|max:100',
        'basicData.officeZipCode'         => 'nullable|numeric',
        'basicData.manufacturingAddress'  => 'nullable|string|max:255',
        'basicData.manufacturingCountry'  => 'nullable|string|max:100',
        'basicData.manufacturingState'    => 'nullable|string|max:100',
        'basicData.manufacturingCity'     => 'nullable|string|max:100',
        'basicData.manufacturingZipCode'  => 'nullable|numeric',
        'basicData.vendor_moq'            => 'nullable|numeric',
        'basicData.vendorStatus'          => 'nullable|string|in:active,inactive',

        // Terms Data
        'termsData.purchaseCurrency'      => 'nullable|string|max:50',
        'termsData.purchaseType'          => 'nullable|string|max:50',
        'termsData.paymentTerms'          => 'nullable|string|max:100',
        'termsData.priceBasis'            => 'nullable|string|max:100',
        'termsData.freightType'           => 'nullable|string|max:50',
        'termsData.warranty'              => 'nullable|string|max:100',
        'termsData.purchaseKeywords'      => 'nullable|string',

        // Contact Data - Sales
        'contactData.sales.name'          => 'nullable|string|max:100',
        'contactData.sales.email'         => 'nullable|email|max:255',
        'contactData.sales.mobile'        => 'nullable|string|max:50',
        'contactData.sales.telephone'     => 'nullable|string|max:50',
        'contactData.sales.designation'   => 'nullable|string|max:100',

        // Contact Data - Accounts
        'contactData.accounts.name'       => 'nullable|string|max:100',
        'contactData.accounts.email'      => 'nullable|email|max:255',
        'contactData.accounts.mobile'     => 'nullable|string|max:50',
        'contactData.accounts.telephone'  => 'nullable|string|max:50',
        'contactData.accounts.designation'=> 'nullable|string|max:100',

        // Contact Data - Management
        'contactData.management.name'       => 'nullable|string|max:100',
        'contactData.management.email'      => 'nullable|email|max:255',
        'contactData.management.mobile'     => 'nullable|string|max:50',
        'contactData.management.telephone'  => 'nullable|string|max:50',
        'contactData.management.designation'=> 'nullable|string|max:100',

        // Contact Data - Support
        'contactData.support.name'         => 'nullable|string|max:100',
        'contactData.support.email'        => 'nullable|email|max:255',
        'contactData.support.mobile'       => 'nullable|string|max:50',
        'contactData.support.telephone'    => 'nullable|string|max:50',
        'contactData.support.designation'  => 'nullable|string|max:100',

        // Account Data
        'accountData.bankName'            => 'nullable|string|max:150',
        'accountData.beneficiaryName'     => 'nullable|string|max:255',
        'accountData.accountNumber'       => 'nullable|string|max:100',
        'accountData.reAccountNumber'     => 'nullable|string|max:100',
        'accountData.ifsc'                => 'nullable|string|max:50',

        // Bank Details Array
        'bankAccounts' => 'nullable|array',
        'bankAccounts.*.vendor_bank_details_id' => 'nullable|integer',
        'bankAccounts.*.bankName' => 'nullable|string|max:150',
        'bankAccounts.*.beneficiaryName' => 'nullable|string|max:255',
        'bankAccounts.*.accountNumber' => 'nullable|string|max:100',
        'bankAccounts.*.reAccountNumber' => 'nullable|string|max:100',
        'bankAccounts.*.ifsc' => 'nullable|string|max:50',
    ]);

    $vendor = Vendor::find($vendor_id);
    if (!$vendor) {
        return response()->json([
            'status' => false,
            'message' => 'Vendor not found.',
        ], 404);
    }

    $vendorData = [
        'C_Name'                => $validated['basicData']['companyName'],
        'AddressName'           => $validated['basicData']['officeAddress'] ?? null,
        'Number'                => $validated['basicData']['telephone'] ?? null,
        'Fax'                   => $validated['basicData']['mobile'] ?? null,
        'gst_no'                => $validated['basicData']['gstNumber'] ?? null,
        'Country'               => $validated['basicData']['officeCountry'] ?? null,
        'state'                 => $validated['basicData']['officeState'] ?? null,
        'city'                  => $validated['basicData']['officeCity'] ?? null,
        'pincode'               => $validated['basicData']['officeZipCode'] ?? null,
        'manufacturing_address' => $validated['basicData']['manufacturingAddress'] ?? null,
        'manufacturing_Country' => $validated['basicData']['manufacturingCountry'] ?? null,
        'manufacturing_state'   => $validated['basicData']['manufacturingState'] ?? null,
        'manufacturing_city'    => $validated['basicData']['manufacturingCity'] ?? null,
        'manufacturing_pincode' => $validated['basicData']['manufacturingZipCode'] ?? null,
        'status'                => $validated['basicData']['vendorStatus'],

        // Sales Contact
        'Contact_1'         => $validated['contactData']['sales']['name'] ?? '',
        'Email'             => $validated['contactData']['sales']['email'] ?? null,
        'sales_mobile'      => $validated['contactData']['sales']['mobile'] ?? null,
        'sales_telephone'   => $validated['contactData']['sales']['telephone'] ?? null,
        'sales_designation' => $validated['contactData']['sales']['designation'] ?? null,

        // Accounts Contact
        'Contact_2'             => $validated['contactData']['accounts']['name'] ?? '',
        'Email2'                => $validated['contactData']['accounts']['email'] ?? null,
        'mobile2'               => $validated['contactData']['accounts']['mobile'] ?? null,
        'Number2'               => $validated['contactData']['accounts']['telephone'] ?? null,
        'accounts_designation' => $validated['contactData']['accounts']['designation'] ?? null,

        // Management Contact
        'Contact_3'              => $validated['contactData']['management']['name'] ?? '',
        'Email3'                 => $validated['contactData']['management']['email'] ?? null,
        'mobile3'                => $validated['contactData']['management']['mobile'] ?? null,
        'Number3'                => $validated['contactData']['management']['telephone'] ?? null,
        'management_designation'=> $validated['contactData']['management']['designation'] ?? null,

        // Support Contact
        'support_name'         => $validated['contactData']['support']['name'] ?? '',
        'support_email'        => $validated['contactData']['support']['email'] ?? null,
        'support_mobile'       => $validated['contactData']['support']['mobile'] ?? null,
        'support_telephone'    => $validated['contactData']['support']['telephone'] ?? null,
        'support_designation'  => $validated['contactData']['support']['designation'] ?? null,

        // Terms
        'purchase_type'        => $validated['termsData']['purchaseType'] ?? null,
        'Currency'             => $validated['termsData']['purchaseCurrency'] ?? null,
        'Price_Basis'          => $validated['termsData']['priceBasis'] ?? null,
        'Payment_Terms'        => $validated['termsData']['paymentTerms'] ?? null,
        'purchase_keywords'    => $validated['termsData']['purchaseKeywords'] ?? null,

        // Accounts Info (Optional main bank)
        'bank_name'            => $validated['accountData']['bankName'] ?? null,
        'beneficiary_name'     => $validated['accountData']['beneficiaryName'] ?? null,
        'account_number'       => $validated['accountData']['accountNumber'] ?? null,
        're_account_number'    => $validated['accountData']['reAccountNumber'] ?? null,
        'ifsc_code'            => $validated['accountData']['ifsc'] ?? null,
        'vendor_moq'           => $validated['basicData']['vendor_moq'] ?? null,
    ];

    $vendor->fill($vendorData);
    $vendor->save();

    // ✅ Update or Insert Vendor Bank Details
    if (!empty($validated['bankAccounts'])) {
        foreach ($validated['bankAccounts'] as $bank) {
            if (!empty($bank['vendor_bank_details_id'])) {
                // Update existing
                DB::table('vendor_bank_details')
                    ->where('vendor_bank_details_id', $bank['vendor_bank_details_id'])
                    ->where('vendor_id', $vendor_id)
                    ->update([
                        'bank_name'         => $bank['bankName'] ?? null,
                        'beneficiary_name'  => $bank['beneficiaryName'] ?? null,
                        'account_number'    => $bank['accountNumber'] ?? null,
                        're_account_number' => $bank['reAccountNumber'] ?? null,
                        'ifsc_code'         => $bank['ifsc'] ?? null,
                        'updated_at'        => now(),
                    ]);
            } else {
                // Insert new
                DB::table('vendor_bank_details')->insert([
                    'vendor_id'         => $vendor_id,
                    'bank_name'         => $bank['bankName'] ?? null,
                    'beneficiary_name'  => $bank['beneficiaryName'] ?? null,
                    'account_number'    => $bank['accountNumber'] ?? null,
                    're_account_number' => $bank['reAccountNumber'] ?? null,
                    'ifsc_code'         => $bank['ifsc'] ?? null,
                    'deleteflag'        => 'active',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Vendor updated successfully.',
    ]);
}


public function markVendorAsFavorite(Request $request)
{
    $validated = $request->validate([
        'vendor_id' => 'required|integer|exists:vendor_master,ID',
        'fav_vendor' => 'required|in:0,1', // Accept only 0 or 1
    ]);

    DB::table('vendor_master')
        ->where('ID', $validated['vendor_id'])
        ->update(['fav_vendor' => $validated['fav_vendor']]);

    return response()->json([
        'success' => true,
        'message' => 'Vendor favorite status updated successfully.',
        'vendor_id' => $validated['vendor_id'],
        'fav_vendor' => $validated['fav_vendor']
    ]);
}

/*
public function vendorProducts($vendor_id)
{
    $products = getProductsByVendor($vendor_id);
    return response()->json([
        'vendor_id' => $vendor_id,
        'products' => $products
    ]);
}*/


public function getVendorProducts(Request $request)
{
    $vendorListId = $request->input('vendor_id');
    $productList = $request->input('product_name');
    $minPrice = $request->input('min_price');
    $maxPrice = $request->input('max_price');
    $searchKeyword = $request->input('search_by');

    // Pagination params
    $page = (int) $request->input('pageno', 1);
    $perPage = (int) $request->input('records', 15);

    // Base query
    $query = DB::table('vendor_product_list')
        ->join('vendor_master', 'vendor_product_list.Vendor_List', '=', 'vendor_master.ID')
        ->select(
            'vendor_product_list.ID as vendor_product_listing_id',
            'vendor_product_list.ACL_Item_Code as internalItemCode',
            'vendor_product_list.Vendor_Item_Code as vendorItemCode',
            'vendor_product_list.Product_List as product_name',
            'vendor_product_list.Product_Desc as product_description',
            'vendor_product_list.Prodcut_Price as product_price',
            'vendor_product_list.pro_id_entry',
            'vendor_product_list.pro_id',
            'vendor_product_list.Vendor_List as vendor_id',
            'vendor_product_list.upc_code as upc_code',
            'vendor_product_list.category as category',
            'vendor_master.Currency as currency'
        );

    // Apply filters
    if (!empty($vendorListId)) {
        $query->where('vendor_product_list.Vendor_List', $vendorListId);
    }

    if (!empty($productList)) {
        $query->where('vendor_product_list.Product_List', 'like', '%' . $productList . '%');
    }

    if (!empty($minPrice)) {
        $query->where('vendor_product_list.Prodcut_Price', '>=', $minPrice);
    }

    if (!empty($maxPrice)) {
        $query->where('vendor_product_list.Prodcut_Price', '<=', $maxPrice);
    }

    if (!empty($searchKeyword) && $searchKeyword !== '0') {
        $query->where(function ($q) use ($searchKeyword) {
            $q->where('vendor_product_list.Vendor_Item_Code', 'like', "%$searchKeyword%")
              ->orWhere('vendor_product_list.Product_List', 'like', "%$searchKeyword%")
			  ->orWhere('vendor_product_list.upc_code', 'like', "%$searchKeyword%")
              ->orWhere('vendor_product_list.ACL_Item_Code', 'like', "%$searchKeyword%");
        });
    }

    // Get paginated result
    $products = $query->paginate($perPage, ['*'], 'page', $page);

    // Format response
    return response()->json([
        'success' => true,
        'data' => $products->items(),
        'current_page' => $products->currentPage(),
        'per_page' => $products->perPage(),
        'total' => $products->total(),
        'last_page' => $products->lastPage()
    ]);
}


public function storeVendorProduct(Request $request)
{
    $validator = app('validator')->make($request->all(), [
        'vendorId'            => 'required|integer',
        'internalItemCode'    => 'required|string|max:50',
        'pro_id_entry'        => 'nullable|integer',
        'pro_id'              => 'nullable|integer',
        'vendorItemCode'      => 'required|string|max:50',
        'product_name'        => 'required|string|max:200',
        'productDescription'  => 'nullable|string',
        'purchasePrice'       => 'required|numeric|min:0',
        'upc_code'            => 'required|numeric|min:0',
		'category'            => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    // Check if entry already exists
    $exists = checkVendorProductExists($request->input('vendorId'), $request->input('pro_id'));

    if ($exists) {
        return response()->json(['success' => false, 'message' => 'Vendor product already exists for the given Vendor and Product.'], 409);
    }

    $dataArray = [
        'Vendor_List'      => $request->input('vendorId'),
        'ACL_Item_Code'    => trim($request->input('internalItemCode')),
        'pro_id_entry'     => $request->input('pro_id_entry'),
        'pro_id'           => $request->input('pro_id'),
        'Vendor_Item_Code' => trim($request->input('vendorItemCode')),
        'Product_List'     => addslashes(trim($request->input('product_name'))),
        'Product_Desc'     => $request->input('productDescription'),
        'Prodcut_Price'    => $request->input('purchasePrice'),
        'upc_code'         => $request->input('upc_code'),
		'category'         => $request->input('category'),
    ];

    DB::table('vendor_product_list')->insert($dataArray);

    return response()->json(['success' => true, 'message' => 'Vendor product inserted successfully.']);
}


public function updateVendorProduct(Request $request)
{
    $validator = app('validator')->make($request->all(), [
        'vendor_product_listing_id' => 'required|integer|exists:vendor_product_list,ID',
        'vendorId'                  => 'required|integer',
        'internalItemCode'          => 'required|string|max:50',
        'pro_id_entry'              => 'nullable|integer',
        'pro_id'                    => 'nullable|integer',
        'vendorItemCode'            => 'required|string|max:50',
        'product_name'              => 'required|string|max:200',
        'productDescription'        => 'nullable|string',
        'purchasePrice'             => 'required|numeric|min:0',
        'upc_code'                  => 'required|numeric|min:0',
		'category'            => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $vendorProductId = $request->input('vendor_product_listing_id');

    // Optional: Check for duplicate entry on Vendor_List + pro_id excluding current record
$duplicateCount = DB::table('vendor_product_list')
    ->where('Vendor_List', $request->input('vendorId'))
    ->where('pro_id', $request->input('pro_id'))
    ->where('ID', '!=', $vendorProductId)
    ->count();

if ($duplicateCount > 1) {
    return response()->json([
        'success' => false,
        'message' => 'Another vendor product with the same Vendor and Product already exists.'
    ], 409);
}


    $dataArray = [
        'Vendor_List'      => $request->input('vendorId'),
        'ACL_Item_Code'    => trim($request->input('internalItemCode')),
        'pro_id_entry'     => $request->input('pro_id_entry'),
        'pro_id'           => $request->input('pro_id'),
        'Vendor_Item_Code' => trim($request->input('vendorItemCode')),
        'Product_List'     => addslashes(trim($request->input('product_name'))),
        'Product_Desc'     => $request->input('productDescription'),
        'Prodcut_Price'    => $request->input('purchasePrice'),
        'upc_code'         => $request->input('upc_code'),
		'category'         => $request->input('category'),
    ];

    DB::table('vendor_product_list')
        ->where('ID', $vendorProductId)
        ->update($dataArray);

    return response()->json([
        'success' => true,
        'message' => 'Vendor product updated successfully.'
    ]);
}




public function deleteVendorProduct(Request $request)
{
    $id = $request->input('vendor_product_list_id');

    if (!$id) {
        return response()->json(['error' => 'vendor_product_list_id is required'], 400);
    }

    $deleted = DB::table('vendor_product_list')->where('ID', $id)->delete();

    if ($deleted) {
        return response()->json(['message' => 'Vendor product deleted successfully']);
    } else {
        return response()->json(['error' => 'Vendor product not found or already deleted'], 404);
    }
}



public function vendorOrderHistory(Request $request)
{
    $vendorId    = $request->input('vendorId');
    $poType      = $request->input('po_type');
    $page        = $request->input('pageno', 1);
    $limit       = $request->input('records', 100);
    $offset      = ($page - 1) * $limit;
    $orderBy     = $request->input('order_by', 'vendor_po_item.O_ID');
    $orderDir    = $request->input('order', 'DESC');

    if (!$vendorId) {
        return response()->json(['error' => 'Vendor ID (vendorId) is required'], 400);
    }

    $baseQuery = DB::table('vendor_po_item')
        ->join('vendor_po_final', 'vendor_po_final.PO_ID', '=', 'vendor_po_item.O_ID')
        ->join('vendor_po_order', 'vendor_po_order.ID', '=', 'vendor_po_final.PO_ID')
        ->leftJoin('vendor_master', 'vendor_master.ID', '=', 'vendor_po_final.Sup_Ref')
        ->leftJoin('vendor_po_invoice_new', 'vendor_po_invoice_new.po_id', '=', 'vendor_po_final.PO_ID')
        ->where('vendor_po_item.Vendor_List', $vendorId);

    if ($poType) {
        $baseQuery->where('vendor_po_order.po_type', $poType);
    }

    // Count total grouped records
    $totalRecords = DB::table(DB::raw("({$baseQuery->select('vendor_po_item.O_ID')->groupBy('vendor_po_item.O_ID')->toSql()}) as sub"))
        ->mergeBindings($baseQuery)
        ->count();

    $orders = $baseQuery
        ->select(
            'vendor_po_order.po_type',
            'vendor_po_item.O_ID',
            'vendor_po_item.Vendor_List as vendorId',
            'vendor_master.C_Name as vendor_name',
            'vendor_po_final.Date',
            'vendor_po_invoice_new.invoice_no',
            'vendor_po_invoice_new.invoice_upload as invoice_path',
			'vendor_po_invoice_new.awb_upload as awb_upload',
			'vendor_po_invoice_new.boe_upload as boe_upload',
            'vendor_po_invoice_new.value as invoice_value',
            'vendor_po_invoice_new.due_on as Due_on',
            'vendor_po_invoice_new.invoice_date as I_Date',
            'vendor_po_invoice_new.payment_paid_on_date'
        )
        ->groupBy('vendor_po_item.O_ID')
        ->orderBy($orderBy, $orderDir)
        ->offset($offset)
        ->limit($limit)
        ->get();

    $results = $orders->map(function ($order) {
        $invoice = DB::table('vendor_po_invoice_new')
            ->select('invoice_no', 'invoice_date', 'due_on', 'payment_paid_on_date','invoice_upload','awb_upload','boe_upload','value')
            ->where('po_id', $order->O_ID)
            ->first();

        $poTypeMap = [
            '1' => 'Draft',
            '2' => 'Send For Approval',
            '5' => 'DO Order',
        ];

        return [
            'ID' => $order->O_ID,
            'VPI' => (int) $order->vendorId,
            'po_total_value' => po_total($order->O_ID),			
            'received_date' => null, // replace if available from any table
            'Confirm_Purchase' => 'inactive', // replace if available
            'po_type' => $order->po_type,
            'flag' => 1,
            'order_id' => 0,
            'O_ID' => $order->O_ID,
            'vendorId' => (string) $order->vendorId,
            'vendor_name' => $order->vendor_name,
            'Date' => $order->Date,
            'po_type_label' => $poTypeMap[$order->po_type] ?? 'Normal',
            'invoice' => [
                'I_No' => $invoice->invoice_no ?? null,
                'I_Date' => $invoice->invoice_date ?? null,
				'invoice_path' => $invoice->invoice_upload ?? null,
				'awb_path' => $invoice->awb_upload ?? null,
				'boe_path' => $invoice->boe_upload ?? null,								
                'Due_on' => $invoice->due_on ?? null,
				'total_value' => $invoice->value ?? null,
                'Payment_Date_on' => $invoice->payment_paid_on_date ?? null,
            ]
        ];
    });

    return response()->json([
        'data' => $results,
        'page' => $page,
        'records' => $limit,
        'total_records' => $totalRecords,
    ]);
}




//add more bank:

public function addNewVendorBank(Request $request, $vendor_id)
{
    $validated = $request->validate([
        'accountData.bankName'           => 'nullable|string|max:150',
        'accountData.beneficiaryName'    => 'nullable|string|max:255',
        'accountData.accountNumber'      => 'nullable|string|max:100',
        'accountData.reAccountNumber'    => 'nullable|string|max:100',
        'accountData.ifsc'               => 'nullable|string|max:50',
    ]);

    $insertId = DB::table('vendor_bank_details')->insertGetId([
        'vendor_id'         => $vendor_id,
        'bank_name'         => $validated['accountData']['bankName'] ?? null,
        'beneficiary_name'  => $validated['accountData']['beneficiaryName'] ?? null,
        'account_number'    => $validated['accountData']['accountNumber'] ?? null,
        're_account_number' => $validated['accountData']['reAccountNumber'] ?? null,
        'ifsc_code'         => $validated['accountData']['ifsc'] ?? null,
        'status'            => 'active',
        'deleteflag'        => 'active',
   /*     'created_at'        => now(),
        'updated_at'        => now(),*/
    ]);

    return response()->json([
        'status' => $insertId ? true : false,
        'message' => $insertId ? 'Vendor bank details added successfully.' : 'Failed to add vendor bank details.',
        'inserted_vendor_bank_details_id' => $insertId,
    ], $insertId ? 201 : 500);
}

//edit bank
public function editVendorBank(Request $request, $vendor_bank_details_id)
{
    $validated = $request->validate([
        'accountData.bankName'           => 'nullable|string|max:150',
        'accountData.beneficiaryName'    => 'nullable|string|max:255',
        'accountData.accountNumber'      => 'nullable|string|max:100',
        'accountData.reAccountNumber'    => 'nullable|string|max:100',
        'accountData.ifsc'               => 'nullable|string|max:50',
    ]);

    $updated = DB::table('vendor_bank_details')
        ->where('vendor_bank_details_id', $vendor_bank_details_id)
        ->where('deleteflag', 'active') // ensure only active records get updated
        ->update([
            'bank_name'         => $validated['accountData']['bankName'] ?? null,
            'beneficiary_name'  => $validated['accountData']['beneficiaryName'] ?? null,
            'account_number'    => $validated['accountData']['accountNumber'] ?? null,
            're_account_number' => $validated['accountData']['reAccountNumber'] ?? null,
            'ifsc_code'         => $validated['accountData']['ifsc'] ?? null,
           // 'updated_at'        => now(),
        ]);

    return response()->json([
        'status' => $updated ? true : false,
        'message' => $updated ? 'Vendor bank details updated successfully.' : 'No changes made or record not found.',
    ]);
}


public function addVendorContact(Request $request, $vendor_id)
{
    $validated = $request->validate([
        'contacts' => 'required|array|min:1',
        'contacts.*.name'         => 'required|string|max:150',
        'contacts.*.email'        => 'nullable|email|max:255',
        'contacts.*.mobile'       => 'nullable|string|max:50',
        'contacts.*.telephone'    => 'nullable|string|max:50',
        'contacts.*.designation'  => 'nullable|string|max:100',
        'contacts.*.type'         => 'required|in:sales,accounts,management,support,other',
    ]);

    $insertedIds = [];

    foreach ($validated['contacts'] as $contact) {
        $insertId = DB::table('vendor_contacts')->insertGetId([
            'vendor_id'    => $vendor_id,
            'name'         => $contact['name'],
            'email'        => $contact['email'] ?? null,
            'mobile'       => $contact['mobile'] ?? null,
            'telephone'    => $contact['telephone'] ?? null,
            'designation'  => $contact['designation'] ?? null,
            'type'         => $contact['type'],
            'status'       => 'active',
            'deleteflag'   => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $insertedIds[] = $insertId;
    }

  return response()->json([
        'status' => count($insertedIds) > 0,
        'message' => count($insertedIds) > 0
            ? 'Vendor contact(s) added successfully.'
            : 'Failed to add vendor contact(s).',
        'inserted_contact_ids' => $insertedIds,
    ], count($insertedIds) > 0 ? 201 : 500);
}



}//class closed
