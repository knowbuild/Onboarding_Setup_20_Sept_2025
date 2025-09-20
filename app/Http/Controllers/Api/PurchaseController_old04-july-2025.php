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
use Carbon\Carbon;
use App\Models\GstSaleTypeMaster;
class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

//vendors listing	
public function vendorslisting(Request $request)
{
    $perPage = (int) $request->input('records', 10);
    $page = (int) $request->input('pageno', 1);

    $query = Vendor::query();

    // Filters
    if ($request->filled('vendor_id')) $query->where('ID', $request->vendor_id);
    if ($request->filled('fav_vendor')) $query->where('fav_vendor', $request->fav_vendor);
    if ($request->filled('status')) $query->where('status', $request->status);
    if ($request->filled('purchase_type')) $query->where('purchase_type', $request->purchase_type);
    if ($request->filled('Country')) $query->where('Country', $request->Country);
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
    $sortField = $request->input('sort_by', 'ID');
    $sortOrder = strtolower($request->input('order', 'desc'));

    // Determine Financial Year (from input or fallback to current)
    $fyYear = $request->input('fy_year');
    if ($fyYear && is_numeric($fyYear)) {
        $fyStart = Carbon::create((int) $fyYear, 4, 1)->startOfDay();
        $fyEnd = Carbon::create((int) $fyYear + 1, 3, 31)->endOfDay();
    } else {
        $now = now();
        $fyStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)->startOfDay()
            : Carbon::create($now->year - 1, 4, 1)->startOfDay();
        $fyEnd = $fyStart->copy()->addYear()->subDay()->endOfDay();
    }
    $fyLabel = 'FY ' . $fyStart->format('Y') . '-' . $fyEnd->format('y');

    // Lookups
    $countries = DB::table('tbl_country')->where('deleteflag', 'active')->pluck('country_name', 'country_id')->toArray();
    $paymentTerms = DB::table('tbl_supply_order_payment_terms_master')->where('deleteflag', 'active')->pluck('supply_order_payment_terms_name', 'supply_order_payment_terms_id')->toArray();
    $priceBases = DB::table('tbl_price_basis')->where('deleteflag', 'active')->pluck('price_basis_name', 'price_basis_id')->toArray();

    $vendors = $query->get();

    // Get PO summary data
    $poData = DB::table('vendor_po_final as vf')
        ->join('vendor_po_order as vo', 'vf.PO_ID', '=', 'vo.ID')
        ->select(
            'vo.VPI as vendor_id',
            'vf.PO_ID',
            DB::raw('MAX(vf.Date) as po_date'),
            DB::raw('SUM(vf.Prodcut_Qty * vf.Prodcut_Price * (1 + (IFNULL(vf.Tax_Value, 0) / 100))) as po_value')
        )
        ->groupBy('vo.VPI', 'vf.PO_ID')
        ->get();

    $poSummary = [];
    foreach ($poData->groupBy('vendor_id') as $vendorId => $records) {
        $fyTotal = $records->filter(fn($r) => $r->po_date >= $fyStart && $r->po_date <= $fyEnd)->sum('po_value');
        $lastPO = $records->sortByDesc(fn($r) => strtotime($r->po_date))->first();
        $poSummary[$vendorId] = [
            'purchase_current_fy' => round($fyTotal, 2),
            'last_purchase_date' => $lastPO?->po_date,
            'last_purchase_amount' => round($lastPO?->po_value ?? 0, 2),
        ];
    }

    // Sort vendors
    if ($sortField === 'last_purchase_date') {
        $vendors = $vendors->sortBy(function ($vendor) use ($poSummary) {
            return $poSummary[$vendor->ID]['last_purchase_date'] ?? '0000-00-00';
        }, SORT_REGULAR, $sortOrder === 'desc');
    } else {
        $vendors = $vendors->sortBy($sortField, SORT_REGULAR, $sortOrder === 'desc');
    }

    // Product count
    $productCounts = DB::table('vendor_product_list')
        ->select(DB::raw('COUNT(pro_id) as product_count'), 'Vendor_List')
        ->groupBy('Vendor_List')
        ->pluck('product_count', 'Vendor_List')
        ->toArray();

    // Pagination
    $paginated = $vendors->forPage($page, $perPage)->values();
    $totalCount = $vendors->count();
    $clean = fn($v) => ($v === 'N/A' || empty($v)) ? null : $v;

    // Response mapping
    $results = $paginated->map(function ($vendor) use (
        $countries, $paymentTerms, $priceBases, $productCounts, $poSummary, $fyLabel, $clean
    ) {
        $contactsFromDB = DB::table('vendor_contacts')
            ->where('vendor_id', $vendor->ID)
            ->where('deleteflag', 'active')
            ->get()
            ->groupBy('type')
            ->map(fn($g) => $g->unique(fn($c) => strtolower(trim($c->email)) . '|' . trim($c->mobile))->values());

        $fallbackContacts = [
            'management' => [
                'name' => $clean($vendor->Contact_3),
                'email' => $clean($vendor->email3),
                'mobile' => $clean($vendor->mobile3),
                'telephone' => $clean($vendor->number3),
                'designation' => $clean($vendor->management_designation),
            ],
            'sales' => [
                'name' => $clean($vendor->Contact_1),
                'email' => $clean($vendor->Email),
                'mobile' => $clean($vendor->sales_mobile),
                'telephone' => $clean($vendor->sales_telephone),
                'designation' => $clean($vendor->sales_designation),
            ],
            'accounts' => [
                'name' => $clean($vendor->Contact_2),
                'email' => $clean($vendor->email2),
                'mobile' => $clean($vendor->mobile2),
                'telephone' => $clean($vendor->number2),
                'designation' => $clean($vendor->accounts_designation),
            ],
            'support' => [
                'name' => $clean($vendor->support_name),
                'email' => $clean($vendor->support_email),
                'mobile' => $clean($vendor->support_mobile),
                'telephone' => $clean($vendor->support_telephone),
                'designation' => $clean($vendor->support_designation),
            ],
        ];

        $mergedContacts = [];
        foreach ($fallbackContacts as $role => $fallback) {
            $contacts = $contactsFromDB[$role] ?? collect();
            if ($contacts->isEmpty()) {
                $mergedContacts[] = array_merge(['role' => $role, 'vendor_contacts_id' => null], $fallback);
            } else {
                foreach ($contacts as $c) {
                    $mergedContacts[] = [
                        'role' => $role,
                        'vendor_contacts_id' => $c->id ?? null,
                        'name' => $c->name ?? $fallback['name'],
                        'email' => $c->email ?? $fallback['email'],
                        'mobile' => $c->mobile ?? $fallback['mobile'],
                        'telephone' => $c->telephone ?? $fallback['telephone'],
                        'designation' => $c->designation ?? $fallback['designation'],
                    ];
                }
            }
        }

        foreach ($contactsFromDB as $type => $contacts) {
            if (!array_key_exists($type, $fallbackContacts)) {
                foreach ($contacts as $c) {
                    $mergedContacts[] = [
                        'role' => $type,
                        'vendor_contacts_id' => $c->id,
                        'name' => $c->name,
                        'email' => $c->email,
                        'mobile' => $c->mobile,
                        'telephone' => $c->telephone,
                        'designation' => $c->designation,
                    ];
                }
            }
        }

        return [
            'vendor_id' => $vendor->ID,
            'vendor_name' => $vendor->C_Name,
            'vendor_address' => $vendor->AddressName,
            'co_contact_number' => $vendor->Fax,
            'co_email' => $vendor->Email,
            'gst_no' => $vendor->gst_no,
            'landline_number' => $vendor->Number,
            'purchase_keywords' => $vendor->purchase_keywords,
            'purchase_type' => $vendor->purchase_type,
            'Payment_Terms' => $paymentTerms[$vendor->Payment_Terms] ?? $vendor->Payment_Terms,
            'Price_Basis' => $priceBases[$vendor->Price_Basis] ?? $vendor->Price_Basis,
            'Country' => $countries[$vendor->Country] ?? $vendor->Country,
            'Currency' => $vendor->Currency,
            'fav_vendor' => $vendor->fav_vendor,
            'status' => $vendor->status,
            'product_count' => $productCounts[$vendor->ID] ?? 0,
            'purchase_current_fy' => $poSummary[$vendor->ID]['purchase_current_fy'] ?? 0,
            'last_purchase_date' => $poSummary[$vendor->ID]['last_purchase_date'] ?? null,
            'last_purchase_amount' => $poSummary[$vendor->ID]['last_purchase_amount'] ?? 0,
            'fy_label' => $fyLabel,
            'contact_data' => $mergedContacts,
        ];
    });

    return response()->json([
        'vendor_data' => $results,
        'num_rows_count' => $totalCount,
    ]);
}








public function vendorslisting_oldsssss(Request $request)
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

public function price_basis_master(Request $request)
{
    $purchase_type = $request->query('purchase_type');

    $query = PriceBasis::active()
        ->orderBy('price_basis_name', 'asc')
        ->select('price_basis_id', 'price_basis_name', 'purchase_type');

    // Apply where condition only if purchase_type is provided
    if (!empty($purchase_type)) {
        $query->where('purchase_type', $purchase_type);
    }

    $terms = $query->get();

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
        $contactDataNew[$type] = [
            'vendor_contacts_id'       => $c->id ?? null,
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
    $vendorId = $request->input('vendorId');
    $poType = $request->input('po_type');
    $page = max((int)$request->input('pageno', 1), 1);
    $limit = max((int)$request->input('records', 100), 1);
    $offset = ($page - 1) * $limit;
    $financialYear = $request->input('financial_year');

    if (!$vendorId) {
        return response()->json(['error' => 'Vendor ID (vendorId) is required'], 400);
    }

    // Determine financial year if not provided
    if (empty($financialYear)) {
        $currentYear = date('Y');
        $nextYear = date('Y') + 1;
        $prevYear = date('Y') - 1;
        $financialYear = (date('m') > 3) ? "$currentYear-$nextYear" : "$prevYear-$currentYear";
    }

    $finYrId = show_financial_year_id($financialYear);
    $financialYearParts = explode('-', $financialYear);
    $startDate = $financialYearParts[0] . '-04-01';
    $endDate = $financialYearParts[1] . '-03-31';

    // Allowed columns for sorting
    $allowedSortColumns = [
        'vendor_po_item.O_ID',
        'vendor_po_order.po_type',
		'vendor_po_order.created_by',
        'vendor_po_final.Date',
        'vendor_po_invoice_new.invoice_no',
        'vendor_po_invoice_new.invoice_date',
        'vendor_po_invoice_new.value',
    ];

    $orderBy = $request->input('sortBy', 'vendor_po_final.Date');
    $orderBy = in_array($orderBy, $allowedSortColumns) ? $orderBy : 'vendor_po_final.Date';
    $orderDir = strtoupper($request->input('orderBy', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

    $baseQuery = DB::table('vendor_po_item')
        ->join('vendor_po_final', 'vendor_po_final.PO_ID', '=', 'vendor_po_item.O_ID')
        ->leftJoin('vendor_po_order', 'vendor_po_order.ID', '=', 'vendor_po_final.PO_ID')
        ->leftJoin('vendor_master', 'vendor_master.ID', '=', 'vendor_po_final.Sup_Ref')
        ->leftJoin('vendor_po_invoice_new', 'vendor_po_invoice_new.po_id', '=', 'vendor_po_final.PO_ID')
        ->where('vendor_po_final.Sup_Ref', $vendorId)
        ->whereBetween('vendor_po_final.Date', [$startDate, $endDate]);

    if ($poType) {
        $baseQuery->where('vendor_po_order.po_type', $poType);
    }

    $totalRecords = DB::table(DB::raw("({$baseQuery->select('vendor_po_item.O_ID')->groupBy('vendor_po_item.O_ID')->toSql()}) as sub"))
        ->mergeBindings($baseQuery)
        ->count();

    $orders = $baseQuery
        ->select(
            'vendor_po_order.po_type',
            'vendor_po_item.O_ID',
			'vendor_po_order.created_by',
            'vendor_po_item.Vendor_List as vendorId',
            'vendor_master.C_Name as vendor_name',
			'vendor_master.Currency as currency',
            'vendor_po_final.Date',
            'vendor_po_order.Confirm_Purchase',
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
            ->select('invoice_no', 'invoice_date', 'due_on', 'payment_paid_on_date', 'invoice_upload', 'awb_upload', 'boe_upload', 'value')
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
            'received_date' => null,
            'Confirm_Purchase' => $order->Confirm_Purchase,
			'createdBy' => $order->created_by,
			'currency' => $order->currency,
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
        'contacts.*.team'         => 'required|in:sales,accounts,management,support,other',
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
            'type'         => $contact['team'],
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


//edit vendor contact person 
public function editVendorContact(Request $request, $vendor_contacts_id)
{
    $validated = $request->validate([
        'name'        => 'required|string|max:150',
        'email'       => 'nullable|email|max:255',
        'mobile'      => 'nullable|string|max:50',
        'telephone'   => 'nullable|string|max:50',
        'designation' => 'nullable|string|max:100',
        'team'        => 'required|in:sales,accounts,management,support,other',
    ]);

    $updated = DB::table('vendor_contacts')
        ->where('id', $vendor_contacts_id)
        ->update([
            'name'        => $validated['name'],
            'email'       => $validated['email'] ?? null,
            'mobile'      => $validated['mobile'] ?? null,
            'telephone'   => $validated['telephone'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'type'        => $validated['team'],
            'updated_at'  => now(),
        ]);

    return response()->json([
        'status'  => $updated,
        'message' => $updated ? 'Vendor contact updated successfully.' : 'No changes made or invalid contact ID.',
    ]);
}



//enable disable vendor status :

public function editVendorStatus(Request $request, $vendor_id)
{
    $validated = $request->validate([
        'basicData.vendorStatus' => 'required|string|in:active,inactive', // ENUM-safe
    ]);

    $vendor = Vendor::find($vendor_id);

    if (!$vendor) {
        return response()->json([
            'status' => false,
            'message' => 'Vendor not found.',
        ], 404);
    }

    $vendor->status = $validated['basicData']['vendorStatus'];
    $vendor->save();

    return response()->json([
        'success' => true,
        'message' => 'Vendor status updated successfully.',
    ]);
}

//Vendor products manager get products listing by vendor: ID/Vendor Name/ Product Name

public function getVendorProductsforPo(Request $request)
{
    $vendorListId = $request->input('vendor_id');
    $productList = $request->input('product_name');
    $minPrice = $request->input('min_price');
    $maxPrice = $request->input('max_price');
    $searchKeyword = $request->input('search_by');
    $searchType = $request->input('search_type'); // new param

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
            'vendor_master.C_name as Vendor_name',
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

    // Dropdown Search Logic
    if (!empty($searchKeyword) && !empty($searchType)) {
        $query->where(function ($q) use ($searchKeyword, $searchType) {
            switch ($searchType) {
                case 'vendor_name':
                    $q->where('vendor_master.C_name', 'like', "%$searchKeyword%");
                    break;

                case 'vendor_id':
                    $q->where('vendor_master.ID', $searchKeyword); // exact match for ID
                    break;

                case 'product_name':
                    $q->where('vendor_product_list.Product_List', 'like', "%$searchKeyword%");
                    break;


                case 'upc_code':
                    $q->where('vendor_product_list.upc_code', $searchKeyword);
                    break;
                default:
                    // fallback multi-field search
                    $q->where('vendor_product_list.Vendor_Item_Code', 'like', "%$searchKeyword%")
                      ->orWhere('vendor_product_list.Product_List', 'like', "%$searchKeyword%")
                      ->orWhere('vendor_product_list.upc_code', 'like', "%$searchKeyword%")
                      ->orWhere('vendor_product_list.ACL_Item_Code', 'like', "%$searchKeyword%");
            }
        });
    }

    // Get paginated result
    $products = $query->paginate($perPage, ['*'], 'page', $page);
 // Append currency symbol
 
 
 // Append currency details
    $productsTransformed = collect($products->items())->map(function ($item) {
        $currencyDetails = currencySymbolByCurrencyCode($item->currency);
        $item->currency_symbol = $currencyDetails[0] ?? null;
        $item->currency_value = $currencyDetails[1] ?? null;
        $item->currency_css_symbol = $currencyDetails[2] ?? null;
        return $item;
    });

 
    
    return response()->json([
        'success' => true,
        'data' => $products->items(),
        'current_page' => $products->currentPage(),
        'per_page' => $products->perPage(),
        'total' => $products->total(),
        'last_page' => $products->lastPage()
    ]);
}

public function vendorAutoSuggest(Request $request)
{
    $search = $request->input('search', '');
    $purchase_type = $request->input('purchase_type', '');

    $query = DB::table('vendor_master')
        ->leftJoin('vendor_product_list', 'vendor_master.ID', '=', 'vendor_product_list.Vendor_List')
        ->select(
            'vendor_master.ID as id',
            'vendor_master.C_name as vendor_name',
            'vendor_master.Email',
            'vendor_master.Number',
            'vendor_master.purchase_type',
            'vendor_master.purchase_keywords'
        )
        ->where('vendor_master.deleteflag', '=', 'active');

    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('vendor_master.C_name', 'like', "%$search%")
              ->orWhere('vendor_master.ID', 'like', "%$search%")
              ->orWhere('vendor_master.purchase_keywords', 'like', "%$search%")
              ->orWhere('vendor_product_list.Product_List', 'like', "%$search%");
        });
    }

    if (!empty($purchase_type)) {
        $query->where('vendor_master.purchase_type', '=', $purchase_type);
    }

    $results = $query->distinct()->limit(20)->get();

    return response()->json([
        'success' => true,
        'data' => $results
    ]);
}


//Create new purchase order
public function createPurchaseOrder_olds(Request $request)
{
    DB::beginTransaction();

    try {
        // 1. Insert into vendor_po_order
        $orderId = DB::table('vendor_po_order')->insertGetId([
            'VPI' => $request->input('vpi'),
            'received_date' => $request->input('received_date'),
            'Confirm_Purchase' => $request->input('confirm_purchase'),
            'po_type' => $request->input('po_type', 0),
            'flag' => $request->input('flag', 1),
            'order_id' => $request->input('order_id', 0),
        ]);

        // 2. Insert multiple items for the same PO
        $items = $request->input('items', []);

        foreach ($items as $item) {
            DB::table('vendor_po_item')->insert([
                'VPI' => $item['vendor_list'],//$request->input('vpi'),
                'Product_Name' => $item['product_name'],
                'Vendor_List' => $request->input('vpi'),//$item['vendor_list'],
                'O_ID' => $orderId,
                'pro_id_entry' => $item['pro_id_entry'] ?? 0,
                'pro_id' => $item['pro_id'] ?? 0,
                'upc_code' => $item['upc_code'] ?? '0',
                'hsn_code' => ProHsn_code($item['pro_id']) ?? '0',
                'pro_price' => $item['product_price'] ?? '0',
                'pro_quantity' => $item['quantity'] ?? '0',
				'subTotal' => $item['subTotal'] ?? '0',
				'category' => $item['category'] ?? '0',					
            ]);
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'order_id' => $orderId,
            'message' => 'Purchase order created successfully with multiple items.',
        ]);

    } catch (\Exception $e) {
        DB::rollback();

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to create purchase order.',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function createPurchaseOrder(Request $request)
{
    DB::beginTransaction();

    try {
        // Extract relevant fields from JSON
        $vendorId = $request->input('vendorId');
        $purchaseType = $request->input('purchaseType', 'domestic');
        $selectedProducts = $request->input('selectedProducts', []);
		$purchaseType = $request->input('purchaseType');
		
		$createdBy = $request->input('createdBy');
		$totalPrice = $request->input('totalPrice');
		$totalQuantity = $request->input('totalQuantity');
		$poDate = $request->input('poDate');

        // Insert into vendor_po_order
        $orderId = DB::table('vendor_po_order')->insertGetId([
            'VPI' => $vendorId,
            'received_date' => now(), // Set current date/time or adjust as needed
            'Confirm_Purchase' => 'inactive', // Assuming confirmed since it's being inserted
            'po_type' => $purchaseType,
            'flag' => 1,
            'order_id' => 0, 
			'purchaseType' => $purchaseType, 
			'created_by' => $createdBy, 
			'poDate' => $poDate, 						
			'totalPrice' => $totalPrice,
			'totalQuantity' => $totalQuantity, 
			// Set 0 if not linked to existing order
        ]);

        // Insert each selected product into vendor_po_item
        foreach ($selectedProducts as $item) {
            DB::table('vendor_po_item')->insert([
                'VPI' => $item['vendor_product_listing_id'],
                'Product_Name' => $item['product_name'],
                'Vendor_List' => $vendorId,
                'O_ID' => $orderId,
                'pro_id_entry' => $item['pro_id_entry'] ?? 0,
                'pro_id' => $item['pro_id'] ?? 0,
                'upc_code' => $item['upc_code'] ?? '0',
				'vendorItemCode' => $item['vendorItemCode'] ?? '0',
                'hsn_code' => ProHsn_code($item['pro_id']) ?? '0',
                'pro_price' => $item['product_price'] ?? '0',
                'pro_quantity' => $item['quantity'] ?? '0',
				'subTotal' => $item['subTotal'] ?? '0',
				'category' => $item['category'] ?? '0',				
            ]);
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'po_id' => $orderId,
            'message' => 'Purchase order created successfully with selected products.',
        ]);

    } catch (\Exception $e) {
        DB::rollback();

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to create purchase order.',
            'error' => $e->getMessage()
        ], 500);
    }
}


 
public function editPurchaseOrder(Request $request)
{
    try {
        DB::beginTransaction();

        $data = $request->all();
        $poId = $data['po_id'] ?? $data['poNumber'] ?? null;
        $createdBy = $data['createdBy'] ?? null;
        $poDate = $data['poDate'] ?? null;
        $currency = $data['currency'] ?? 'INR';
        $flag = strtolower($currency) === 'domestic' ? 'INR' : $currency;
        $supRef = $data['vendorId'] ?? $data['poDetails']['vendorToEdit'] ?? null;

        $requirements = $data['selectedProducts'] ?? $data['requirementDetails']['requirements'] ?? [];
        $totalPrice = $data['totalPrice'] ?? 0;
        $totalQuantity = $data['totalQuantity'] ?? 0;

        // Fetch current terms if missing in request
        $existingTerms = DB::table('vendor_po_final')->where('PO_ID', $poId)->first();

        // Terms & Conditions
        $terms = $data['termsDetails'] ?? [];
        $paymentTerms = array_key_exists('paymentTerms', $terms) && $terms['paymentTerms'] !== '' ? $terms['paymentTerms'] : ($existingTerms->Payment_Terms ?? null);
        $priceBasis = array_key_exists('priceBasis', $terms) && $terms['priceBasis'] !== '' ? $terms['priceBasis'] : ($existingTerms->priceBasis ?? null);
        $warranty = array_key_exists('warranty', $terms) && $terms['warranty'] !== '' ? $terms['warranty'] : ($existingTerms->Warranty ?? null);
        $ORDER_Acknowledgement = array_key_exists('additionalInstructions', $terms) && $terms['additionalInstructions'] !== '' ? $terms['additionalInstructions'] : ($existingTerms->ORDER_Acknowledgement ?? null);

        // Dispatch
        $dispatchDate = $data['dispatchDetails']['dispatchDate'] ?? null;
        $dispatchMode = $data['dispatchDetails']['dispatchMode'] ?? null;

        // Payment follow-up
        $paymentFollowUp = $data['paymentFollowUpDetails'] ?? [];

        // Fetch buyer default if not provided
        $buyer = $data['buyerDetails'] ?? [];
        if (empty($buyer['companyName'])) {
            $defaultBranch = DB::table('tbl_company_branch_address')->where('default_branch', 1)->first();
            $buyer = [
                'companyName' => $defaultBranch->company_name ?? '',
                'address' => $defaultBranch->address ?? '',
                'city' => $defaultBranch->city ?? '',
                'state' => $defaultBranch->state ?? '',
                'country' => $defaultBranch->country ?? '',
                'pincode' => $defaultBranch->pincode ?? '',
                'telephone' => $defaultBranch->phone_number ?? '',
                'email' => $defaultBranch->email_id ?? '',
                'gstNo' => $defaultBranch->gst_no ?? '',
                'countryId' => $defaultBranch->country ?? '',
                'stateId' => $defaultBranch->state ?? '',
                'cityId' => $defaultBranch->city ?? '',
            ];
        }

        // Format buyer, consignee, and vendor data
        $consignee = $data['consigneeDetails'] ?? [];
        $vendor = $data['vendorDetails'] ?? [];

        $buyerFormatted = ($buyer['companyName'] ?? '') . "\n" . ($buyer['address'] ?? '') . ",\nNew Delhi - " . ($buyer['pincode'] ?? '') . "\nTel: " . ($buyer['telephone'] ?? '') . "\nEmail: " . ($buyer['email'] ?? '') . "\nGST: " . ($buyer['gstNo'] ?? '');

        $consigneeFormatted = ($consignee['companyName'] ?? '') . "\n" . ($consignee['address'] ?? '') . ",\nNew Delhi - " . ($consignee['pincode'] ?? '');

        $exporterFormatted = ($vendor['companyName'] ?? '') . "\n" . ($vendor['address'] ?? '') . "\nCity: " . ($vendor['city'] ?? '') . "\nZIP: " . ($vendor['pincode'] ?? '') . "\nState: " . ($vendor['state'] ?? '') . "\n" . ($vendor['country'] ?? '') . "\nContact: " . ($vendor['contactName'] ?? '') . "\nTel: " . ($vendor['telephone'] ?? '') . "\nMobile: " . ($vendor['mobile'] ?? '') . "\nEmail: " . ($vendor['email'] ?? '') . "\nGST no: " . ($vendor['gstNo'] ?? '');

        DB::table('vendor_po_order')->where('ID', $poId)->update([
            'poDate' => $poDate,
            'created_by' => $createdBy,
            'VPI' => $supRef,
            'purchaseType' => $data['purchaseType'] ?? 0,
            'flag' => $flag,
            'totalPrice' => $totalPrice,
            'totalQuantity' => $totalQuantity,
            'sub_total' => $data['requirementDetails']['subTotal'] ?? 0,
            'tax_type' => $data['requirementDetails']['taxType'] ?? '',
            'tax' => $data['requirementDetails']['tax'] ?? 0,
            'payment_followup_name' => $paymentFollowUp['name'] ?? null,
            'payment_followup_telephone' => $paymentFollowUp['telephone'] ?? null,
            'payment_followup_email' => $paymentFollowUp['email'] ?? null,
            'payment_followup_cc' => $paymentFollowUp['cc'] ?? null,
            'payment_followup_contact_name' => $paymentFollowUp['contactName'] ?? null,
        ]);

        DB::table('vendor_po_final')->where('PO_ID', $poId)->delete();
        DB::table('vendor_po_item')->where('O_ID', $poId)->delete();

        foreach ($requirements as $item) {
            DB::table('vendor_po_item')->insert([
                'VPI' => $supRef,
                'Product_Name' => $item['product_name'] ?? '',
                'Vendor_List' => $vendor['companyName'] ?? '',
                'O_ID' => $poId,
                'pro_id_entry' => $item['pro_id_entry'] ?? 0,
                'pro_id' => $item['pro_id'] ?? 0,
                'upc_code' => $item['upc_code'] ?? 0,
                'vendorItemCode' => $item['vendorItemCode'] ?? '0',
                'hsn_code' => $item['hsn'] ?? '0',
                'pro_price' => $item['product_price'] ?? $item['productPrice'] ?? 0,
                'pro_quantity' => $item['quantity'] ?? 1,
                'subTotal' => $item['subTotal'] ?? 0,
                'category' => $item['category'] ?? 0,
            ]);

            DB::table('vendor_po_final')->insert([
                'PO_ID' => $poId,
                'Date' => $poDate,
                'E_Date' => $dispatchDate,
                'Delivery' => $dispatchDate,
                'Dispatch' => $dispatchMode,
                'Sup_Ref' => $supRef,
                'tax_type' => $data['requirementDetails']['taxType'] ?? '',
                'Tax_Value' => $data['requirementDetails']['tax'] ?? 0,
                'Payment_Terms' => $paymentTerms,
                'Warranty' => $warranty,
                'Flag' => $flag,
                'Product_Desc' => $item['product_description'] ?? $item['description'] ?? '',
                'Prodcut_Qty' => $item['quantity'] ?? 1,
                'Prodcut_Price' => $item['product_price'] ?? $item['productPrice'] ?? 0,
                'upc_code' => $item['upc_code'] ?? 0,
                'hsn_code' => $item['hsn'] ?? '0',
                'pro_id' => $item['pro_id'] ?? null,
                'Vendor_Item_Code' => $item['vendorItemCode'] ?? '',
                'ORDER_Acknowledgement' => $ORDER_Acknowledgement ?? '',
                'priceBasis' => $priceBasis ?? '',
                'buyer' => $buyerFormatted,
                'Consignee' => $consigneeFormatted,
                'Exporter' => $exporterFormatted,
                'buyer_company_name' => $buyer['companyName'] ?? '',
                'buyer_contact_name' => $buyer['contactName'] ?? '',
                'buyer_address' => $buyer['address'] ?? '',
                'buyer_city' => $buyer['city'] ?? '',
                'buyer_state' => $buyer['state'] ?? '',
                'buyer_country' => $buyer['country'] ?? '',
                'buyer_pincode' => $buyer['pincode'] ?? '',
                'buyer_telephone' => $buyer['telephone'] ?? '',
                'buyer_mobile' => $buyer['mobile'] ?? '',
                'buyer_email' => $buyer['email'] ?? '',
                'buyer_gst_no' => $buyer['gstNo'] ?? '',
                'consignee_company_name' => $consignee['companyName'] ?? '',
                'consignee_contact_name' => $consignee['contactName'] ?? '',
                'consignee_address' => $consignee['address'] ?? '',
                'consignee_city' => $consignee['city'] ?? '',
                'consignee_state' => $consignee['state'] ?? '',
                'consignee_country' => $consignee['country'] ?? '',
                'consignee_pincode' => $consignee['pincode'] ?? '',
                'consignee_telephone' => $consignee['telephone'] ?? '',
                'consignee_mobile' => $consignee['mobile'] ?? '',
                'consignee_email' => $consignee['email'] ?? '',
                'consignee_gst_no' => $consignee['gstNo'] ?? '',
                'exporter_company_name' => $vendor['companyName'] ?? '',
                'exporter_contact_name' => $vendor['contactName'] ?? '',
                'exporter_address' => $vendor['address'] ?? '',
                'exporter_city' => $vendor['city'] ?? '',
                'exporter_state' => $vendor['state'] ?? '',
                'exporter_country' => $vendor['country'] ?? '',
                'exporter_pincode' => $vendor['pincode'] ?? '',
                'exporter_telephone' => $vendor['telephone'] ?? '',
                'exporter_mobile' => $vendor['mobile'] ?? '',
                'exporter_email' => $vendor['email'] ?? '',
                'exporter_gst_no' => $vendor['gstNo'] ?? '',
            ]);
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase order updated successfully',
            'po_id' => $poId
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update purchase order.',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function purchaseTrends_old(Request $request)
    {
		
		
$financialYear = trim($request->input('financial_year'));
$productSearch = trim($request->input('product_search'));

if ($financialYear) {
    // Parse from provided financial year
    [$startYear, $endYear] = explode('-', $financialYear);
    $fromDate = Carbon::createFromDate((int) $startYear, 4, 1)->format('Y-m-d');
    $toDate = Carbon::createFromDate((int) $endYear, 3, 31)->format('Y-m-d');
} else {
    // Derive current FY based on today's date
    $now = Carbon::now();
    $startFY = $now->month >= 4 ? $now->year : $now->year - 1;
    $fromDate = Carbon::createFromDate($startFY, 4, 1)->format('Y-m-d');
    $toDate = Carbon::createFromDate($startFY + 1, 3, 31)->format('Y-m-d');
    $financialYear = "$startFY-" . ($startFY + 1); // set for response
}		
		
		
		
      //  $fromDate = $request->input('from_date', '2025-04-01');
      //  $toDate = $request->input('to_date', '2026-03-31');
        $supRef = $request->input('sup_ref');
        $flag = trim($request->input('flag')) ?: null;

        $endDate = Carbon::parse($toDate);

        $yearOnYearStartFY = ($endDate->month >= 4 ? $endDate->year : $endDate->year - 1) - 4;
        $monthlyQtrStartFY = $yearOnYearStartFY + 3;
        $endFY = $endDate->month >= 4 ? $endDate->year : $endDate->year - 1;

        $fyRangesYear = collect();
        $fyRangesMonthQtr = collect();

        for ($fy = $yearOnYearStartFY; $fy <= $endFY; $fy++) {
            $range = [
                'fy_year' => "$fy-" . ($fy + 1),
                'from' => Carbon::createFromDate($fy, 4, 1),
                'to' => Carbon::createFromDate($fy + 1, 3, 31)
            ];

            $fyRangesYear->push($range);

            if ($fy >= $monthlyQtrStartFY) {
                $fyRangesMonthQtr->push($range);
            }
        }

        $makeQuery = function ($from, $to) use ($supRef, $flag) {
            return DB::table('vendor_po_final')
                ->whereBetween('Date', [$from, $to])
                ->when($supRef, fn($q) => $q->where('Sup_Ref', $supRef))
                ->when(!is_null($flag), fn($q) => $q->where('Flag', $flag));
        };

        $yearOnYear = $fyRangesYear->map(function ($range) use ($makeQuery, $flag) {
            $total = (clone $makeQuery($range['from'], $range['to']))->sum(DB::raw('Prodcut_Qty * Prodcut_Price'));
            return [
                'fy_year' => $range['fy_year'],
                'currency' => $flag ?? 'ALL',
                'current_year' => (string) round($total, 2),
                'previous_year' => null,
                'growth_percentage' => null,
            ];
        });

        if ($yearOnYear->count() >= 2) {
            for ($i = 1; $i < $yearOnYear->count(); $i++) {
                $prev = $yearOnYear[$i - 1];
                $curr = $yearOnYear[$i];
                $growth = ($prev['current_year'] > 0)
                    ? round((($curr['current_year'] - $prev['current_year']) / $prev['current_year']) * 100, 2)
                    : null;
                $yearOnYear->put($i, array_merge($curr, [
                    'previous_year' => $prev['current_year'],
                    'growth_percentage' => $growth,
                ]));
            }
        }

        $monthlyTrendRaw = collect();
        foreach ($fyRangesMonthQtr as $range) {
            $month = $range['from']->copy();
            while ($month <= $range['to']) {
                $monthlyTrendRaw->push([
                    'fy_year' => $range['fy_year'],
                    'month' => $month->format('Y-m'),
                    'month_name' => $month->format('M'),
                    'total' => (string) round((clone $makeQuery($month->copy()->startOfMonth(), $month->copy()->endOfMonth()))
                        ->sum(DB::raw('Prodcut_Qty * Prodcut_Price')), 2),
                ]);
                $month->addMonth();
            }
        }

        $monthlyTrend = collect();
        $months = $monthlyTrendRaw->pluck('month')->map(fn($m) => substr($m, 5))->unique();
        foreach ($months as $monthPart) {
            $current = $monthlyTrendRaw->first(fn($r) => substr($r['month'], 5) === $monthPart && $r['fy_year'] === $fyRangesMonthQtr->last()['fy_year']);
            $prev = $monthlyTrendRaw->first(fn($r) => substr($r['month'], 5) === $monthPart && $r['fy_year'] === $fyRangesMonthQtr->first()['fy_year']);

            $currTotal = isset($current['total']) ? (float) $current['total'] : 0;
            $prevTotal = isset($prev['total']) ? (float) $prev['total'] : 0;

            $monthlyTrend->push([
                'fy_year' => $fyRangesMonthQtr->last()['fy_year'],
                'prev_year' => $fyRangesMonthQtr->first()['fy_year'],
                'month' => $current['month'] ?? $prev['month'],
                'month_name' => $current['month_name'] ?? $prev['month_name'],
                'prev_total' => (string) $prevTotal,
                'curr_total' => (string) $currTotal,
                'total' => (string) round($currTotal + $prevTotal, 2)
            ]);
        }

        $quarterlyTrendRaw = collect();
        foreach ($fyRangesMonthQtr as $range) {
            $quarters = [
                ['label' => 'Q1', 'start' => Carbon::create($range['from']->year, 4, 1), 'end' => Carbon::create($range['from']->year, 6, 30)],
                ['label' => 'Q2', 'start' => Carbon::create($range['from']->year, 7, 1), 'end' => Carbon::create($range['from']->year, 9, 30)],
                ['label' => 'Q3', 'start' => Carbon::create($range['from']->year, 10, 1), 'end' => Carbon::create($range['from']->year, 12, 31)],
                ['label' => 'Q4', 'start' => Carbon::create($range['to']->year, 1, 1), 'end' => Carbon::create($range['to']->year, 3, 31)],
            ];

            foreach ($quarters as $q) {
                $total = (clone $makeQuery($q['start'], $q['end']))->sum(DB::raw('Prodcut_Qty * Prodcut_Price'));
                $quarterlyTrendRaw->push([
                    'fy_year' => $range['fy_year'],
                    'quarter' => $range['fy_year'] . '-' . $q['label'],
                    'label' => $q['label'],
                    'total' => (string) round($total, 2),
                ]);
            }
        }

        $quarterlyTrend = collect();
        foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $qLabel) {
            $curr = $quarterlyTrendRaw->first(fn($r) => $r['label'] === $qLabel && $r['fy_year'] === $fyRangesMonthQtr->last()['fy_year']);
            $prev = $quarterlyTrendRaw->first(fn($r) => $r['label'] === $qLabel && $r['fy_year'] === $fyRangesMonthQtr->first()['fy_year']);

            $currTotal = isset($curr['total']) ? (float) $curr['total'] : 0;
            $prevTotal = isset($prev['total']) ? (float) $prev['total'] : 0;

            $quarterlyTrend->push([
                'fy_year' => $fyRangesMonthQtr->last()['fy_year'],
                'prev_fy_year' => $fyRangesMonthQtr->first()['fy_year'],
                'quarter' => $curr['quarter'] ?? $prev['quarter'],
                'label' => $qLabel,
                'prev_total' => (string) $prevTotal,
                'curr_total' => (string) $currTotal,
                'total' => (string) round($currTotal + $prevTotal, 2)
            ]);
        }

        $latestRange = $fyRangesYear->last();
        $topProducts = (clone $makeQuery($latestRange['from'], $latestRange['to']))
            ->select(
                'sup_ref',
                'pro_id',
                'upc_code',
                'hsn_code',
                'Model_No',
                'Product_Desc',
                'Prodcut_Qty',
                'Prodcut_Price',
                DB::raw('SUM(Prodcut_Qty * Prodcut_Price) as vendor_total_price')
            )
            ->groupBy('pro_id')
            ->orderByDesc('vendor_total_price')
            ->limit(1500)
            ->get()
            ->map(function ($product) {
                $product->product_name = product_name($product->pro_id);
                return $product;
            });

        return response()->json([
          'filters' => [
    'financial_year' => $financialYear,
    'from_date' => $fromDate,
    'to_date' => $toDate,
    'sup_ref' => $supRef,
    'flag' => $flag,
],
            'year_on_year' => $yearOnYear,
            'monthly_trend' => $monthlyTrend,
            'quarterly_trend' => $quarterlyTrend,
            'top_products' => $topProducts,
        ]);
}



public function purchaseTrends(Request $request)
{
    $financialYear = trim($request->input('financial_year'));
    $supRef = $request->input('sup_ref');
    $flag = trim($request->input('flag')) ?: null;
    $productSearch = trim($request->input('product_search'));

    // Determine date range
    if ($financialYear) {
        [$startYear, $endYear] = explode('-', $financialYear);
        $fromDate = Carbon::createFromDate((int) $startYear, 4, 1)->format('Y-m-d');
        $toDate = Carbon::createFromDate((int) $endYear, 3, 31)->format('Y-m-d');
    } else {
        $now = Carbon::now();
        $startFY = $now->month >= 4 ? $now->year : $now->year - 1;
        $fromDate = Carbon::createFromDate($startFY, 4, 1)->format('Y-m-d');
        $toDate = Carbon::createFromDate($startFY + 1, 3, 31)->format('Y-m-d');
        $financialYear = "$startFY-" . ($startFY + 1);
    }

    $endDate = Carbon::parse($toDate);
    $yearOnYearStartFY = ($endDate->month >= 4 ? $endDate->year : $endDate->year - 1) - 4;
    $monthlyQtrStartFY = $yearOnYearStartFY + 3;
    $endFY = $endDate->month >= 4 ? $endDate->year : $endDate->year - 1;

    $fyRangesYear = collect();
    $fyRangesMonthQtr = collect();

    for ($fy = $yearOnYearStartFY; $fy <= $endFY; $fy++) {
        $range = [
            'fy_year' => "$fy-" . ($fy + 1),
            'from' => Carbon::createFromDate($fy, 4, 1),
            'to' => Carbon::createFromDate($fy + 1, 3, 31)
        ];
        $fyRangesYear->push($range);
        if ($fy >= $monthlyQtrStartFY) {
            $fyRangesMonthQtr->push($range);
        }
    }

    $makeQuery = function ($from, $to) use ($supRef, $flag) {
        return DB::table('vendor_po_final')
            ->whereBetween('Date', [$from, $to])
            ->when($supRef, fn($q) => $q->where('Sup_Ref', $supRef))
            ->when(!is_null($flag), fn($q) => $q->where('Flag', $flag));
    };

    $yearOnYear = $fyRangesYear->map(function ($range) use ($makeQuery, $flag) {
        $total = (clone $makeQuery($range['from'], $range['to']))->sum(DB::raw('Prodcut_Qty * Prodcut_Price'));
        return [
            'fy_year' => $range['fy_year'],
            'currency' => $flag ?? 'ALL',
            'current_year' => (string) round($total, 2),
            'previous_year' => null,
            'growth_percentage' => null,
        ];
    });

    if ($yearOnYear->count() >= 2) {
        for ($i = 1; $i < $yearOnYear->count(); $i++) {
            $prev = $yearOnYear[$i - 1];
            $curr = $yearOnYear[$i];
            $growth = ($prev['current_year'] > 0)
                ? round((($curr['current_year'] - $prev['current_year']) / $prev['current_year']) * 100, 2)
                : null;
            $yearOnYear->put($i, array_merge($curr, [
                'previous_year' => $prev['current_year'],
                'growth_percentage' => $growth,
            ]));
        }
    }

    $monthlyTrendRaw = collect();
    foreach ($fyRangesMonthQtr as $range) {
        $month = $range['from']->copy();
        while ($month <= $range['to']) {
            $monthlyTrendRaw->push([
                'fy_year' => $range['fy_year'],
                'month' => $month->format('Y-m'),
                'month_name' => $month->format('M'),
                'total' => (string) round((clone $makeQuery($month->copy()->startOfMonth(), $month->copy()->endOfMonth()))
                    ->sum(DB::raw('Prodcut_Qty * Prodcut_Price')), 2),
            ]);
            $month->addMonth();
        }
    }

    $monthlyTrend = collect();
    $months = $monthlyTrendRaw->pluck('month')->map(fn($m) => substr($m, 5))->unique();
    foreach ($months as $monthPart) {
        $current = $monthlyTrendRaw->first(fn($r) => substr($r['month'], 5) === $monthPart && $r['fy_year'] === $fyRangesMonthQtr->last()['fy_year']);
        $prev = $monthlyTrendRaw->first(fn($r) => substr($r['month'], 5) === $monthPart && $r['fy_year'] === $fyRangesMonthQtr->first()['fy_year']);

        $currTotal = isset($current['total']) ? (float) $current['total'] : 0;
        $prevTotal = isset($prev['total']) ? (float) $prev['total'] : 0;

        $monthlyTrend->push([
            'fy_year' => $fyRangesMonthQtr->last()['fy_year'],
            'prev_year' => $fyRangesMonthQtr->first()['fy_year'],
            'month' => $current['month'] ?? $prev['month'],
            'month_name' => $current['month_name'] ?? $prev['month_name'],
            'prev_total' => (string) $prevTotal,
            'curr_total' => (string) $currTotal,
            'total' => (string) round($currTotal + $prevTotal, 2)
        ]);
    }

    $quarterlyTrendRaw = collect();
    foreach ($fyRangesMonthQtr as $range) {
        $quarters = [
            ['label' => 'Q1', 'start' => Carbon::create($range['from']->year, 4, 1), 'end' => Carbon::create($range['from']->year, 6, 30)],
            ['label' => 'Q2', 'start' => Carbon::create($range['from']->year, 7, 1), 'end' => Carbon::create($range['from']->year, 9, 30)],
            ['label' => 'Q3', 'start' => Carbon::create($range['from']->year, 10, 1), 'end' => Carbon::create($range['from']->year, 12, 31)],
            ['label' => 'Q4', 'start' => Carbon::create($range['to']->year, 1, 1), 'end' => Carbon::create($range['to']->year, 3, 31)],
        ];

        foreach ($quarters as $q) {
            $total = (clone $makeQuery($q['start'], $q['end']))->sum(DB::raw('Prodcut_Qty * Prodcut_Price'));
            $quarterlyTrendRaw->push([
                'fy_year' => $range['fy_year'],
                'quarter' => $range['fy_year'] . '-' . $q['label'],
                'label' => $q['label'],
                'total' => (string) round($total, 2),
            ]);
        }
    }

    $quarterlyTrend = collect();
    foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $qLabel) {
        $curr = $quarterlyTrendRaw->first(fn($r) => $r['label'] === $qLabel && $r['fy_year'] === $fyRangesMonthQtr->last()['fy_year']);
        $prev = $quarterlyTrendRaw->first(fn($r) => $r['label'] === $qLabel && $r['fy_year'] === $fyRangesMonthQtr->first()['fy_year']);

        $currTotal = isset($curr['total']) ? (float) $curr['total'] : 0;
        $prevTotal = isset($prev['total']) ? (float) $prev['total'] : 0;

        $quarterlyTrend->push([
            'fy_year' => $fyRangesMonthQtr->last()['fy_year'],
            'prev_fy_year' => $fyRangesMonthQtr->first()['fy_year'],
            'quarter' => $curr['quarter'] ?? $prev['quarter'],
            'label' => $qLabel,
            'prev_total' => (string) $prevTotal,
            'curr_total' => (string) $currTotal,
            'total' => (string) round($currTotal + $prevTotal, 2)
        ]);
    }

    $latestRange = $fyRangesYear->last();

    $topProducts = DB::table('vendor_po_final')
//        ->leftJoin('tbl_products as tp', 'vendor_po_final.pro_id', '=', 'tp.ID')
		->leftJoin('tbl_products as tp', 'vendor_po_final.pro_id', '=', 'tp.pro_id')

        ->whereBetween('vendor_po_final.Date', [$latestRange['from'], $latestRange['to']])
        ->when($supRef, fn($q) => $q->where('vendor_po_final.Sup_Ref', $supRef))
        ->when(!is_null($flag), fn($q) => $q->where('vendor_po_final.Flag', $flag))
        ->when($productSearch, fn($q) => $q->where('tp.pro_title', 'like', '%' . $productSearch . '%'))
        ->select(
            'vendor_po_final.sup_ref',
            'vendor_po_final.pro_id',
            'tp.pro_title as product_name',
            'vendor_po_final.upc_code',
            'vendor_po_final.hsn_code',
            'vendor_po_final.Model_No',
            'vendor_po_final.Product_Desc',
            'vendor_po_final.Prodcut_Qty',
            'vendor_po_final.Prodcut_Price',
            DB::raw('SUM(vendor_po_final.Prodcut_Qty * vendor_po_final.Prodcut_Price) as vendor_total_price')
        )
        ->groupBy('vendor_po_final.pro_id')
        ->orderByDesc('vendor_total_price')
        ->limit(1500)
        ->get();

    return response()->json([
        'filters' => [
            'financial_year' => $financialYear,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'sup_ref' => $supRef,

            'flag' => $flag,
            'product_search' => $productSearch,
        ],
        'year_on_year' => $yearOnYear,
        'monthly_trend' => $monthlyTrend,
        'quarterly_trend' => $quarterlyTrend,
        'top_products' => $topProducts,
    ]);
}






public function getPurchaseOrderDetails(Request $request)
{
    $poId = $request->input('po_id');

    if (!$poId) {
        return response()->json([
            'status' => false,
            'message' => 'po_id is required.'
        ], 400);
    }

    // Get PO
    $purchaseOrder = DB::table('vendor_po_order')->where('id', $poId)->first();
    if (!$purchaseOrder) {
        return response()->json([
            'status' => false,
            'message' => 'Purchase order not found.'
        ], 404);
    }

    // Get PO Items
    $items = DB::table('vendor_po_item')->where('O_ID', $poId)->get();

    // Get Buyer Info
    $buyer = DB::table('tbl_company_branch_address')->where('default_branch', 1)->first();

    // Get Vendor Info
 //   $vendor = DB::table('vendor_master')->where('ID', $purchaseOrder->VPI)->first();

$vendor = DB::table('vendor_master')->where('ID', $purchaseOrder->VPI)->first();

if (!$vendor) {
    return response()->json([
        'status' => false,
        'message' => 'Vendor not found for the provided VPI.'
    ], 404);
}

    $purchaseType = $purchaseOrder->purchaseType;

    // Map Items to Requirements
    $requirements = $items->map(function ($item, $index) {
        return [
            'sno' => $index + 1,
            'pro_id' => $item->pro_id ?? 'N/A',
            'description' => $item->Product_Name ?? 'N/A',
            'upc' => $item->upc_code ?? 'N/A',
            'hsn' => $item->hsn_code ?? 'N/A',
            'vendorItemCode' => $item->vendorItemCode ?? 'N/A',
            'quantity' => $item->pro_quantity ?? 'N/A',
            'productPrice' => $item->pro_price ?? 'N/A',
            'subTotal' => $item->subTotal ?? 0,
        ];
    });

    // Fix: Use correct subtotal field
    $subTotal = $requirements->sum('subTotal');
    $tax = $subTotal * 0.18;
    $poTotal = $subTotal + $tax;

    // Fix: Handle Price_Basis (convert name to ID if needed)
   $priceBasisRaw = $vendor->Price_Basis ?? '';
$priceBasisId = null;
 $po_approval_status = po_approval_status(vendor_po_status($poId));
// If it's numeric, use as-is
if (is_numeric($priceBasisRaw)) {
    $priceBasisId = (int) $priceBasisRaw;
} else {
    // Else get the price_basis_id using name
    $basis = get_price_basis_by_name($priceBasisRaw);
    $priceBasisId = $basis->price_basis_id ?? null;
}

    return response()->json([
        'buyerDetails' => [
            'companyName' => $buyer->company_name ?? '',
            'contactName' => $buyer->location ?? '',
            'address' => $buyer->address ?? '',
            'city' => '',
            'state' => '',
            'countryId' => $buyer->country ?? '',
            'stateId' => $buyer->state ?? '',
            'cityId' => $buyer->city ?? '',
            'country' => 'India',
            'pincode' => $buyer->pincode ?? '',
            'telephone' => $buyer->phone_number ?? '',
            'mobile' => $buyer->phone_number ?? '',
            'email' => $buyer->email_id ?? '',
            'gstNo' => $buyer->gst_no ?? ''
        ],
        'vendorDetails' => [
            'companyName' => $vendor->C_Name ?? '',
            'contactName' => $vendor->Contact_1 ?? '',
            'address' => $vendor->AddressName ?? '',
            'city' => $vendor->city ?? '',
            'state' => $vendor->state ?? '',
            'country' => $vendor->Country ?? '',
            'pincode' => $vendor->pincode ?? '',
            'telephone' => $vendor->Number ?? '',
            'mobile' => $vendor->sales_mobile ?? '',
            'email' => $vendor->Email ?? '',
            'gstNo' => $vendor->gst_no ?? '',
            'countryId' => $vendor->Country ?? '',
            'stateId' => $vendor->state ?? '',
            'cityId' => $vendor->city ?? ''
        ],
        'consigneeDetails' => [
            'companyName' => $buyer->company_name ?? '',
            'contactName' => $buyer->location ?? '',
            'address' => $buyer->address ?? '',
            'city' => '',
            'state' => '',
            'countryId' => $buyer->country ?? '',
            'stateId' => $buyer->state ?? '',
            'cityId' => $buyer->city ?? '',
            'country' => 'India',
            'pincode' => $buyer->pincode ?? '',
            'telephone' => $buyer->phone_number ?? '',
            'mobile' => $buyer->phone_number ?? '',
            'email' => $buyer->email_id ?? '',
            'gstNo' => $buyer->gst_no ?? ''
        ],
  /*      'paymentFollowUpDetails' => [
            'name' => $vendor->support_name ?? '',
            'telephone' => $vendor->support_telephone ?? '',
            'email' => $vendor->support_email ?? '',
            'cc' => '' // Not available in DB, leave blank
        ],*/
      'paymentFollowUpDetails' => [
            'name' => '',
            'telephone' => '',
            'email' => '',
            'cc' => '' // Not available in DB, leave blank
        ],		
		
        'termsDetails' => [
            'paymentTerms' => $vendor->Payment_Terms ?? '',
     
			'priceBasis' => $priceBasisId,
    'priceBasis_name' => $priceBasisRaw,
            'warranty' => '', // Not available
            'additionalInstructions' => '' // Not available
        ],
        'dispatchDetails' => [
            'dispatchDate' => $purchaseOrder->received_date ?? now()->toDateString(),
            'dispatchMode' => 'road' // Hardcoded
        ],
        'requirementDetails' => [
            'requirements' => $requirements,
            'taxType' => 'igst_18', // Hardcoded
            'subTotal' => $subTotal,
            'tax' => $tax,
            'poTotal' => $poTotal
        ],
        'poDetails' => [
            'vendorToEdit' => $purchaseOrder->VPI,
            'editVendorName' => $vendor->C_Name,
            'type' => $purchaseType,
			'po_approval_status' => $po_approval_status,
			'createdBy' => $purchaseOrder->created_by,
			
        ]  
    ]);
}


public function getPurchaseOrders(Request $request)
{
    $pageno = (int) $request->input('pageno', 1);
    $records = (int) $request->input('records', 100);
    $offset = ($pageno - 1) * $records;

    $purchaseTypeSearch = $request->input('purchase_type');
    $poTypeSearch = $request->input('po_type');
    $confirmPurchase = $request->input('confirm_purchase');
    $vendorNameSearch = $request->input('vendor_name');
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');

    $query = DB::table('vendor_po_order')
        ->select(
            'vendor_po_order.order_id',
            'vendor_master.ID as VID',
            'vendor_master.Currency',
            'vendor_master.purchase_type',
            'vendor_master.C_Name',
            'vendor_master.Contact_1',
            'vendor_master.gst_no',
            'vendor_master.Number',
            'vendor_master.AddressName',
            'vendor_master.Email',
            'vendor_po_final.ID',
            'vendor_po_final.PO_ID',
            'vendor_po_final.Date',
            'vendor_po_final.E_Date',
            'vendor_po_final.Exporter',
            'vendor_po_order.VPI',
            'vendor_po_order.po_type',
            'vendor_po_order.Confirm_Purchase'
        )
        ->join('vendor_master', 'vendor_master.ID', '=', 'vendor_po_order.VPI')
        ->join('vendor_po_final', 'vendor_po_order.ID', '=', 'vendor_po_final.PO_ID');

    if (!empty($purchaseTypeSearch)) {
        $query->where('vendor_master.purchase_type', $purchaseTypeSearch);
    }
    if (!empty($poTypeSearch)) {
        $query->where('vendor_po_order.po_type', $poTypeSearch);
    }
    if (!empty($confirmPurchase)) {
        $query->where('vendor_po_order.Confirm_Purchase', $confirmPurchase);
    } else {
        $query->where('vendor_po_order.Confirm_Purchase', 'inactive');
    }
/*    if (!empty($vendorNameSearch)) {
        $query->where(function ($q) use ($vendorNameSearch) {
            $q->where('vendor_master.C_Name', 'like', '%' . $vendorNameSearch . '%');

            if (is_numeric($vendorNameSearch)) {
                $q->orWhere('vendor_po_final.PO_ID', $vendorNameSearch);
            }
        });
    }*/
	
	
	if (!empty($vendorNameSearch)) {
    $query->where(function ($q) use ($vendorNameSearch) {
        $q->where('vendor_master.C_Name', 'like', '%' . $vendorNameSearch . '%')
          ->orWhere('vendor_po_final.PO_ID', 'like', '%' . $vendorNameSearch . '%');
    });
}
    if (!empty($fromDate) && !empty($toDate)) {
        $query->whereBetween('vendor_po_final.Date', [$fromDate, $toDate]);
    }

    $totalQuery = clone $query;
    $totalCount = $totalQuery->groupBy('vendor_po_final.PO_ID')->get()->count();

    $results = $query
        ->groupBy('vendor_po_final.PO_ID')
        ->orderByDesc('vendor_po_order.ID')
        ->offset($offset)
        ->limit($records)
        ->get();

    $results = $results->map(function ($item) {
        $vendorId = $item->VID;

        $clean = fn($v) => ($v === 'N/A' || empty($v)) ? null : $v;

        $fallback = DB::table('vendor_master')->where('ID', $vendorId)->first();

        $fallbackContacts = [
            'management' => [
                'name' => $clean($fallback->Contact_3 ?? null),
                'email' => $clean($fallback->email3 ?? null),
                'mobile' => $clean($fallback->mobile3 ?? null),
                'telephone' => $clean($fallback->number3 ?? null),
                'designation' => $clean($fallback->management_designation ?? null),
            ],
            'sales' => [
                'name' => $clean($fallback->Contact_1 ?? null),
                'email' => $clean($fallback->Email ?? null),
                'mobile' => $clean($fallback->sales_mobile ?? null),
                'telephone' => $clean($fallback->sales_telephone ?? null),
                'designation' => $clean($fallback->sales_designation ?? null),
            ],
            'accounts' => [
                'name' => $clean($fallback->Contact_2 ?? null),
                'email' => $clean($fallback->email2 ?? null),
                'mobile' => $clean($fallback->mobile2 ?? null),
                'telephone' => $clean($fallback->number2 ?? null),
                'designation' => $clean($fallback->accounts_designation ?? null),
            ],
            'support' => [
                'name' => $clean($fallback->support_name ?? null),
                'email' => $clean($fallback->support_email ?? null),
                'mobile' => $clean($fallback->support_mobile ?? null),
                'telephone' => $clean($fallback->support_telephone ?? null),
                'designation' => $clean($fallback->support_designation ?? null),
            ],
        ];

        $contactsFromDB = DB::table('vendor_contacts')
            ->where('vendor_id', $vendorId)
            ->where('deleteflag', 'active')
            ->get()
            ->groupBy('type')
            ->map(function ($group) {
                return $group->unique(fn($c) => strtolower(trim($c->email)) . '|' . trim($c->mobile))->values();
            });

        $contactData = [];

        foreach ($fallbackContacts as $role => $fallbackRoleData) {
            $roleContacts = $contactsFromDB[$role] ?? collect();

            if ($roleContacts->isEmpty()) {
                $contactData[] = array_merge(['role' => $role, 'vendor_contacts_id' => null], $fallbackRoleData);
            } else {
                foreach ($roleContacts as $c) {
                    $contactData[] = [
                        'role' => $role,
                        'vendor_contacts_id' => $c->id ?? null,
                        'name' => $c->name ?? $fallbackRoleData['name'],
                        'email' => $c->email ?? $fallbackRoleData['email'],
                        'mobile' => $c->mobile ?? $fallbackRoleData['mobile'],
                        'telephone' => $c->telephone ?? $fallbackRoleData['telephone'],
                        'designation' => $c->designation ?? $fallbackRoleData['designation'],
                    ];
                }
            }
        }

        foreach ($contactsFromDB as $type => $contacts) {
            if (!array_key_exists($type, $fallbackContacts)) {
                foreach ($contacts as $c) {
                    $contactData[] = [
                        'role' => $type,
                        'vendor_contacts_id' => $c->id ?? null,
                        'name' => $c->name,
                        'email' => $c->email,
                        'mobile' => $c->mobile,
                        'telephone' => $c->telephone,
                        'designation' => $c->designation,
                    ];
                }
            }
        }

        $item->contact_data = $contactData;
        $item->po_total = po_total($item->PO_ID);
        $item->vendor_po_status = po_approval_status(vendor_po_status($item->PO_ID));
        $item->last_purchase = vendor_last_purchase_details($vendorId);

        return $item;
    });

    return response()->json([
        'data' => $results,
        'pageno' => $pageno,
        'records' => $records,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $records),
    ]);
}







public function createpofinal(Request $request)
{
    $validated = $request->validate([
        'poNumber' => 'required|integer',
        'poDate' => 'required|date',
        'createdBy' => 'required|integer',
        'requirementDetails.requirements' => 'required|array|min:1',
    ]);

    try {
        DB::beginTransaction();

        $data       = $request->all();
        $poId       = $data['poNumber'];
        $poDate     = $data['poDate'];
        $createdBy  = $data['createdBy'];

        $buyer      = $data['buyerDetails'] ?? [];
        $consignee  = $data['consigneeDetails'] ?? [];
        $exporter   = $data['vendorDetails'] ?? [];
        $paymentFollowUp = $data['paymentFollowUpDetails'] ?? [];

        $dispatchDate = $data['dispatchDetails']['dispatchDate'] ?? null;
        $dispatchMode = $data['dispatchDetails']['dispatchMode'] ?? null;
        $paymentTerms = $data['termsDetails']['paymentTerms'] ?? null;
		$priceBasis   = $data['termsDetails']['priceBasis'] ?? null;
		$ORDER_Acknowledgement   = $data['termsDetails']['additionalInstructions'] ?? null;		
		 
        $warranty     = $data['termsDetails']['warranty'] ?? null;
        $taxType      = $data['requirementDetails']['taxType'] ?? null;
        $taxValue     = $data['requirementDetails']['tax'] ?? 0.00;
        $subTotal     = $data['requirementDetails']['subTotal'] ?? 0.00;
        $poTotal      = $data['requirementDetails']['poTotal'] ?? 0.00;
        $flag         = strtolower($data['currency'] ?? '') === 'domestic' ? 'INR' : 'USD';
        $supRef       = $data['poDetails']['vendorToEdit'] ?? null;


        // Format legacy blocks
        $buyerFormatted = "{$buyer['companyName']}\n{$buyer['address']},\nNew Delhi - {$buyer['pincode']}\nTel: {$buyer['telephone']}\nEmail: {$buyer['email']}\nGST: {$buyer['gstNo']}";
        $consigneeFormatted = "{$consignee['companyName']}\n{$consignee['address']},\nNew Delhi - {$consignee['pincode']}";
        $exporterFormatted = "{$exporter['companyName']}\n{$exporter['address']}\nCity: {$exporter['city']}\nZIP: {$exporter['pincode']}\nState: {$exporter['state']}\n{$exporter['country']}\nContact: {$exporter['contactName']}\nTel: {$exporter['telephone']}\nMobile: {$exporter['mobile']}\nEmail: {$exporter['email']}\nGST no: {$exporter['gstNo']}";

        // Insert or Update vendor_po_order
        DB::table('vendor_po_order')->updateOrInsert(
            ['ID' => $poId],
            [
                'po_type'       => $data['poDetails']['type'] ?? null,
                'Confirm_Purchase' => 'inactive',
                'received_date' => $poDate,
                'created_by'    => $createdBy,
                'VPI'           => $supRef,
                'flag'          => $flag,
                'totalPrice'    => $poTotal,
                'totalQuantity' => array_sum(array_column($data['requirementDetails']['requirements'], 'quantity')),
                'purchaseType'  => $data['poDetails']['type'] === 'domestic' ? 0 : 1,
                'poDate'        => $poDate,
                'tax_type'      => $taxType,
                'tax'           => $taxValue,
                'sub_total'     => $subTotal,

                //  Payment Follow-up Fields
                'payment_followup_name'          => $paymentFollowUp['name'] ?? null,
                'payment_followup_telephone'     => $paymentFollowUp['telephone'] ?? null,
                'payment_followup_email'         => $paymentFollowUp['email'] ?? null,
                'payment_followup_cc'            => $paymentFollowUp['cc'] ?? null,
                'payment_followup_contact_name'  => $paymentFollowUp['contactName'] ?? null,
				
				
				
				'buyer'              => $buyerFormatted,
				'Consignee'          => $consigneeFormatted,
				'Exporter'           => $exporterFormatted,
			
				'buyer_company_name' => $buyer['companyName'] ?? '',
				'buyer_contact_name' => $buyer['contactName'] ?? '',
				'buyer_address'      => $buyer['address'] ?? '',
				'buyer_city'         => $buyer['city'] ?? '',
				'buyer_state'        => $buyer['state'] ?? '',
				'buyer_country'      => $buyer['country'] ?? '',
				'buyer_pincode'      => $buyer['pincode'] ?? '',
				'buyer_telephone'    => $buyer['telephone'] ?? '',
				'buyer_mobile'       => $buyer['mobile'] ?? '',
				'buyer_email'        => $buyer['email'] ?? '',
				'buyer_gst_no'       => $buyer['gstNo'] ?? '',
			
				'consignee_company_name' => $consignee['companyName'] ?? '',
				'consignee_contact_name' => $consignee['contactName'] ?? '',
				'consignee_address'      => $consignee['address'] ?? '',
				'consignee_city'         => $consignee['city'] ?? '',
				'consignee_state'        => $consignee['state'] ?? '',
				'consignee_country'      => $consignee['country'] ?? '',
				'consignee_pincode'      => $consignee['pincode'] ?? '',
				'consignee_telephone'    => $consignee['telephone'] ?? '',
				'consignee_mobile'       => $consignee['mobile'] ?? '',
				'consignee_email'        => $consignee['email'] ?? '',
				'consignee_gst_no'       => $consignee['gstNo'] ?? '',
			
				'exporter_company_name' => $exporter['companyName'] ?? '',
				'exporter_contact_name' => $exporter['contactName'] ?? '',
				'exporter_address'      => $exporter['address'] ?? '',
				'exporter_city'         => $exporter['city'] ?? '',
				'exporter_state'        => $exporter['state'] ?? '',
				'exporter_country'      => $exporter['country'] ?? '',
				'exporter_pincode'      => $exporter['pincode'] ?? '',
				'exporter_telephone'    => $exporter['telephone'] ?? '',
				'exporter_mobile'       => $exporter['mobile'] ?? '',
				'exporter_email'        => $exporter['email'] ?? '',
				'exporter_gst_no'       => $exporter['gstNo'] ?? '',
				
// Buyer location fields
'buyer_country' => $buyer['countryId'] ?? $buyer['country'] ?? null,
'buyer_state'   => $buyer['stateId'] ?? $buyer['state'] ?? null,
'buyer_city'    => $buyer['cityId'] ?? $buyer['city'] ?? null,

// Vendor location fields
'exporter_country' => $exporter['countryId'] ?? $exporter['country'] ?? null,
'exporter_state'   => $exporter['stateId'] ?? $exporter['state'] ?? null,
'exporter_city'    => $exporter['cityId'] ?? $exporter['city'] ?? null,

// Consignee location fields
'consignee_country' => $consignee['countryId'] ?? $consignee['country'] ?? null,
'consignee_state'   => $consignee['stateId'] ?? $consignee['state'] ?? null,
'consignee_city'    => $consignee['cityId'] ?? $consignee['city'] ?? null,
            ]
        );

        $requirements = $data['requirementDetails']['requirements'];

        foreach ($requirements as $item) {
            $productId = $item['id'] ?? null;
            if (!$productId) continue;

            $existing = DB::table('vendor_po_final')
                ->where('PO_ID', $poId)
                ->where('pro_id', $productId)
                ->first();

            if ($existing) {
                DB::table('vendor_po_final')
                    ->where('ID', $existing->ID)
                    ->update([
                        'Prodcut_Qty'    => $item['quantity'] ?? 1,
                        'Prodcut_Price'  => $item['productPrice'] ?? $existing->Prodcut_Price,
                        'Date'           => $poDate,
						'ORDER_Acknowledgement' => $ORDER_Acknowledgement ?? '0',
						'priceBasis' => $priceBasis ?? '0',
                    ]);
            } else {
                DB::table('vendor_po_final')->insert([
                    'PO_ID'              => $poId,
                    'Date'               => $poDate,
                    'E_Date'             => $dispatchDate,
                    'Delivery'           => $dispatchDate,
                    'Dispatch'           => $dispatchMode,
                    'Sup_Ref'            => $supRef,
                    'tax_type'           => $taxType,
                    'Tax_Value'          => $taxValue,
                    'Payment_Terms'      => $paymentTerms,
                    'Warranty'           => $warranty,
                    'Flag'               => $flag,
                    'Product_Desc'       => $item['description'] ?? '',
                    'Prodcut_Qty'        => $item['quantity'] ?? 1,
                    'Prodcut_Price'      => $item['productPrice'] ?? 0.00,
                    'upc_code'           => $item['upc'] ?? 0,
                    'hsn_code'           => $item['hsn'] ?? '0',
                    'pro_id'             => $item['id'] ?? null,
                    'Vendor_Item_Code'   => $item['vendorItemCode'] ?? '0',
					'ORDER_Acknowledgement' => $ORDER_Acknowledgement ?? '0',
					'priceBasis' => $priceBasis ?? '0',
					

                    // legacy display blocks
                    'buyer'              => $buyerFormatted,
                    'Consignee'          => $consigneeFormatted,
                    'Exporter'           => $exporterFormatted,

                    // buyer info
                    'buyer_company_name' => $buyer['companyName'] ?? '',
                    'buyer_contact_name' => $buyer['contactName'] ?? '',
                    'buyer_address'      => $buyer['address'] ?? '',
                    'buyer_city'         => $buyer['city'] ?? '',
                    'buyer_state'        => $buyer['state'] ?? '',
                    'buyer_country'      => $buyer['country'] ?? '',
                    'buyer_pincode'      => $buyer['pincode'] ?? '',
                    'buyer_telephone'    => $buyer['telephone'] ?? '',
                    'buyer_mobile'       => $buyer['mobile'] ?? '',
                    'buyer_email'        => $buyer['email'] ?? '',
                    'buyer_gst_no'       => $buyer['gstNo'] ?? '',

                    // consignee info
                    'consignee_company_name' => $consignee['companyName'] ?? '',
                    'consignee_contact_name' => $consignee['contactName'] ?? '',
                    'consignee_address'      => $consignee['address'] ?? '',
                    'consignee_city'         => $consignee['city'] ?? '',
                    'consignee_state'        => $consignee['state'] ?? '',
                    'consignee_country'      => $consignee['country'] ?? '',
                    'consignee_pincode'      => $consignee['pincode'] ?? '',
                    'consignee_telephone'    => $consignee['telephone'] ?? '',
                    'consignee_mobile'       => $consignee['mobile'] ?? '',
                    'consignee_email'        => $consignee['email'] ?? '',
                    'consignee_gst_no'       => $consignee['gstNo'] ?? '',

                    // exporter/vendor info
                    'exporter_company_name' => $exporter['companyName'] ?? '',
                    'exporter_contact_name' => $exporter['contactName'] ?? '',
                    'exporter_address'      => $exporter['address'] ?? '',
                    'exporter_city'         => $exporter['city'] ?? '',
                    'exporter_state'        => $exporter['state'] ?? '',
                    'exporter_country'      => $exporter['country'] ?? '',
                    'exporter_pincode'      => $exporter['pincode'] ?? '',
                    'exporter_telephone'    => $exporter['telephone'] ?? '',
                    'exporter_mobile'       => $exporter['mobile'] ?? '',
                    'exporter_email'        => $exporter['email'] ?? '',
                    'exporter_gst_no'       => $exporter['gstNo'] ?? '',
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'PO Final created/updated successfully',
            'po_id' => $poId,
            'processed_count' => count($requirements)
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to process PO Final',
            'error' => $e->getMessage()
        ], 500);
    }
}



//po final details using PO_id


 public function getFinalPurchaseOrderDetails(Request $request)
{
    $poId = $request->input('po_id');
    $po_approval_status = po_approval_status(vendor_po_status($poId));

    if (!$poId) {
        return response()->json([
            'status' => false,
            'message' => 'po_id is required.'
        ], 400);
    }

    $poFinalRows = DB::table('vendor_po_final')->where('PO_ID', $poId)->get();
    $poFinal = $poFinalRows->first();
    $poOrder = DB::table('vendor_po_order')->where('id', $poId)->first();

    if ($poFinalRows->isEmpty() && !$poOrder) {
        return response()->json([
            'status' => false,
            'message' => 'PO record not found.'
        ], 404);
    }

    // Vendor fallback from vendor_master
    $vendorId = $poFinal->Sup_Ref ?? $poOrder->exporter ?? null;
    $vendorMaster = $vendorId
        ? DB::table('vendor_master')->where('ID', $vendorId)->first()
        : null;

    // Buyer fallback from tbl_company_branch_address (default_branch = 1)
    $buyerMaster = DB::table('tbl_company_branch_address')
        ->where('default_branch', 1)
        ->first();

    $hasPaymentFollowup = $poOrder && (
        !empty($poOrder->payment_followup_name) ||
        !empty($poOrder->payment_followup_contact_name) ||
        !empty($poOrder->payment_followup_telephone) ||
        !empty($poOrder->payment_followup_email) ||
        !empty($poOrder->payment_followup_cc)
    );

    $paymentFollowUpDetails = $hasPaymentFollowup ? [
        'name' => $poOrder->payment_followup_name ?? 'N/A',
        'telephone' => $poOrder->payment_followup_telephone ?? 'N/A',
        'email' => $poOrder->payment_followup_email ?? 'N/A',
        'cc' => $poOrder->payment_followup_cc ?? '',
        'contactName' => $poOrder->payment_followup_contact_name ?? 'N/A'
    ] : [
        'name' => '241',
        'telephone' => '01141860000',
        'email' => 'Purchaseaccounts@stanlay.com',
        'cc' => 'finance@stanlay.com, export@stanlay.com',
        'contactName' => 'Mr. Shashank Gaur'
    ];

    $getValue = fn($fieldFinal, $fieldOrder) => $poFinal->$fieldFinal ?? $poOrder->$fieldOrder ?? '';

    $requirements = $poFinalRows->map(function ($item, $index) {
        $qty = $item->Prodcut_Qty ?? 0;
        $price = $item->Prodcut_Price ?? 0;
        return [
            'sno' => $index + 1,
            'pro_id' => $item->pro_id ?? '',
            'description' => $item->Product_Desc ?? '',
            'upc' => $item->upc_code ?? '',
            'hsn' => $item->hsn_code ?? '',
            'vendorItemCode' => $item->Vendor_Item_Code ?? '',
            'quantity' => $qty,
            'productPrice' => $price,
            'subTotal' => $qty * $price,
        ];
    });

    $subTotal = $requirements->sum('subTotal');
    $tax = $poFinal->Tax_Value ?? 0;
    $poTotal = $subTotal + $tax;

    // Price Basis handling
    $priceBasisRaw = $poFinal->priceBasis ?? $vendorMaster->Price_Basis ?? '';
    $priceBasisId = '';
    $priceBasisName = '';

    if (!empty($priceBasisRaw)) {
        if (is_numeric($priceBasisRaw)) {
            $priceBasisId = (int) $priceBasisRaw;
            $priceBasis = PriceBasis::active()->where('price_basis_id', $priceBasisId)->first();
            $priceBasisName = $priceBasis->price_basis_name ?? '';
        } else {
            $priceBasis = PriceBasis::active()
                ->where('price_basis_name', 'like', '%' . $priceBasisRaw . '%')
                ->select('price_basis_id', 'price_basis_name')
                ->first();

            $priceBasisId = $priceBasis->price_basis_id ?? '';
            $priceBasisName = $priceBasis->price_basis_name ?? $priceBasisRaw;
        }
    }

   // Must return ['mode_name' => '', 'mode_id' => '']



$modeRaw = $poFinal->Dispatch ?? '';
$modeDetails = getModeDetails($modeRaw);
    return response()->json([
        'buyerDetails' => [
            'companyName' => $getValue('buyer_company_name', 'buyer_company_name') ?: ($buyerMaster->company_name ?? ''),
            'contactName' => $getValue('buyer_contact_name', 'buyer_contact_name') ?: 'N/A',
            'address' => $getValue('buyer_address', 'buyer_address') ?: ($buyerMaster->address ?? ''),
            'city' => $getValue('buyer_city', 'buyer_city') ?: ($buyerMaster->city ?? ''),
            'state' => $getValue('buyer_state', 'buyer_state') ?: ($buyerMaster->state ?? ''),
            'country' => $getValue('buyer_country', 'buyer_country') ?: ($buyerMaster->country ?? ''),
            'countryId' => $poOrder->buyer_country ?? ($buyerMaster->country ?? ''),
            'stateId' => $poOrder->buyer_state ?? ($buyerMaster->state ?? ''),
            'cityId' => $poOrder->buyer_city ?? ($buyerMaster->city ?? ''),
            'pincode' => $getValue('buyer_pincode', 'buyer_pincode') ?: ($buyerMaster->pincode ?? ''),
            'telephone' => $getValue('buyer_telephone', 'buyer_telephone') ?: ($buyerMaster->phone_number ?? ''),
            'mobile' => $getValue('buyer_mobile', 'buyer_mobile') ?: 'N/A',
            'email' => $getValue('buyer_email', 'buyer_email') ?: ($buyerMaster->email_id ?? ''),
            'gstNo' => $getValue('buyer_gst_no', 'buyer_gst_no') ?: ($buyerMaster->gst_no ?? '')
        ],

        'vendorDetails' => [
            'companyName' => $getValue('exporter_company_name', 'exporter_company_name') ?: ($vendorMaster->C_Name ?? ''),
            'contactName' => $getValue('exporter_contact_name', 'exporter_contact_name') ?: ($vendorMaster->Contact_1 ?? ''),
            'address' => $getValue('exporter_address', 'exporter_address') ?: ($vendorMaster->AddressName ?? ''),
            'city' => $getValue('exporter_city', 'exporter_city') ?: '',
            'state' => $getValue('exporter_state', 'exporter_state') ?: '',
            'country' => $getValue('exporter_country', 'exporter_country') ?: 'India',
            'pincode' => $getValue('exporter_pincode', 'exporter_pincode') ?: ($vendorMaster->pincode ?? ''),
            'telephone' => $getValue('exporter_telephone', 'exporter_telephone') ?: ($vendorMaster->Number ?? ''),
            'mobile' => $getValue('exporter_mobile', 'exporter_mobile') ?: ($vendorMaster->Number ?? ''),
            'email' => $getValue('exporter_email', 'exporter_email') ?: ($vendorMaster->Email ?? ''),
            'gstNo' => $getValue('exporter_gst_no', 'exporter_gst_no') ?: ($vendorMaster->gst_no ?? ''),
            'countryId' => $poOrder->exporter_country ?? '',
            'stateId' => $poOrder->exporter_state ?? '',
            'cityId' => $poOrder->exporter_city ?? ''
        ],

        'consigneeDetails' => [
            'companyName' => $getValue('consignee_company_name', 'consignee_company_name') ?: ($buyerMaster->company_name ?? ''),
            'contactName' => $getValue('consignee_contact_name', 'consignee_contact_name') ?: (($buyerMaster->company_name ?? '') . ' delhi'),
            'address' => $getValue('consignee_address', 'consignee_address') ?: ($buyerMaster->address ?? ''),
            'city' => $getValue('consignee_city', 'consignee_city') ?: ($buyerMaster->city ?? ''),
            'state' => $getValue('consignee_state', 'consignee_state') ?: ($buyerMaster->state ?? ''),
            'country' => $getValue('consignee_country', 'consignee_country') ?: ($buyerMaster->country ?? 'India'),
            'countryId' => $poOrder->consignee_country ?? ($buyerMaster->country ?? 99),
            'stateId' => $poOrder->consignee_state ?? ($buyerMaster->state ?? 10),
            'cityId' => $poOrder->consignee_city ?? ($buyerMaster->city ?? 241),
            'pincode' => $getValue('consignee_pincode', 'consignee_pincode') ?: ($buyerMaster->pincode ?? 110020),
            'telephone' => $getValue('consignee_telephone', 'consignee_telephone') ?: ($buyerMaster->phone_number ?? '01141860000'),
            'mobile' => $getValue('consignee_mobile', 'consignee_mobile') ?: ($buyerMaster->phone_number ?? '01141860000'),
            'email' => $getValue('consignee_email', 'consignee_email') ?: ($buyerMaster->email_id ?? 'finance@stanlay.com'),
            'gstNo' => $getValue('consignee_gst_no', 'consignee_gst_no') ?: ($buyerMaster->gst_no ?? '07AAACA0859J1ZQ')
        ],

        'paymentFollowUpDetails' => $paymentFollowUpDetails,

        'termsDetails' => [
            'paymentTerms' => $poFinal->Payment_Terms ?? '',
            'priceBasis' => $priceBasisId,
            'priceBasis_name' => $priceBasisName,
            'warranty' => $poFinal->Warranty ?? '',
            'additionalInstructions' => $poFinal->ORDER_Acknowledgement ?? ''
        ],

      'dispatchDetails' => [
    'dispatchDate' => $poFinal->E_Date ?? now()->toDateString(),
    'dispatchMode' => $modeDetails['mode_id'],
    'mode_name' => $modeDetails['mode_name']
],

        'requirementDetails' => [
            'requirements' => $requirements,
            'taxType' => $poFinal->tax_type ?? 'N/A',
            'subTotal' => $subTotal,
            'tax' => $tax,
            'poTotal' => $poTotal
        ],

        'poDetails' => [
            'vendorToEdit' => $poFinal->Sup_Ref ?? '',
            'editVendorName' => $poFinal->exporter_company_name ?? '',
            'type' => $poFinal->Flag ?? '',
            'po_approval_status' => $po_approval_status ?? '',
			 'createdBy' => $poOrder->created_by ?? 'N/A',
        ]
    ]);
}





public function getAllGstSaleTypes()
{
    try {
        $gstSaleTypes = GstSaleTypeMaster::all();

        return response()->json([
            'status' => true,
            'message' => 'GST Sale Type data retrieved successfully.',
            'data' => $gstSaleTypes
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error fetching GST Sale Type data.',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function deletePurchaseOrder(Request $request)
{
    $poId = $request->input('po_id');

    if (!$poId) {
        return response()->json([
            'status' => false,
            'message' => 'po_id is required.'
        ], 400);
    }

    try {
        DB::beginTransaction();

        // Delete from vendor_po_item
        DB::table('vendor_po_item')->where('O_ID', $poId)->delete();

        // Delete from vendor_po_final
        DB::table('vendor_po_final')->where('PO_ID', $poId)->delete();

        // Delete from vendor_po_order
        DB::table('vendor_po_order')->where('ID', $poId)->delete();

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Purchase Order deleted successfully.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'Error deleting Purchase Order: ' . $e->getMessage()
        ], 500);
    }
}




public function getAllActiveVendors()
{
    $vendors = DB::table('vendor_master')
        ->select('ID as vendor_id', 'C_Name as vendor_name')
        ->where('status', 'active')
		->orderBy('C_Name', 'asc')
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'Active vendors fetched successfully.',
        'data' => $vendors
    ]);
}


public function purchasealerts_old(Request $request)
{
    // Import Carbon if not already
   

    // Calculate current Financial Year
    $currentDate = Carbon::now();
    $fyStart = Carbon::create(
        $currentDate->month >= 4 ? $currentDate->year : $currentDate->year - 1,
        4, 1, 0, 0, 0
    );
    $fyEnd = (clone $fyStart)->addYear()->subSecond();

    // Paging parameters
    $perPage = (int) $request->input('records', 100);
    $page = (int) $request->input('pageno', 1);
    $offset = ($page - 1) * $perPage;

    // Base query (without select and pagination)
    $baseQuery = DB::table('tbl_delivery_order')
        ->leftJoin('tbl_delivery_challan', 'tbl_delivery_order.O_Id', '=', 'tbl_delivery_challan.O_Id')
        ->join('tbl_order', 'tbl_order.orders_id', '=', 'tbl_delivery_order.O_Id')
        ->join('tbl_order_product', 'tbl_order.orders_id', '=', 'tbl_order_product.order_id')
        ->join('tbl_products as tp', 'tbl_order_product.pro_id', '=', 'tp.pro_id')
        ->join('tbl_products_entry as tpe', 'tbl_order_product.pro_id', '=', 'tpe.pro_id')
		->join('tbl_application as ta', 'tpe.app_cat_id', '=', 'ta.application_id')
		
        ->join('tbl_do_products', function ($join) {
            $join->on('tbl_delivery_order.O_Id', '=', 'tbl_do_products.OID')
                 ->on('tbl_do_products.pro_id', '=', 'tp.pro_id');
        })
        ->whereNull('tbl_delivery_challan.Invoice_No')
        ->whereBetween(DB::raw("STR_TO_DATE(tbl_delivery_order.D_Order_Date, '%Y-%m-%d')"), [$fyStart->format('Y-m-d H:i:s'), $fyEnd->format('Y-m-d H:i:s')])
        ->where('tbl_delivery_order.D_Order_Date', '!=', '0000-00-00')
        ->where('tbl_delivery_order.do_type', '!=', 'service')
        ->where('tp.ware_house_stock', '=', 0)
        ->whereNotIn('tp.pro_id', function ($subquery) {
            $subquery->select('pro_id')->from('vendor_draft_po');
        });

    // Clone the base query for total count
$totalCount = DB::table(DB::raw("({$baseQuery->select('tp.pro_id')->groupBy('tp.pro_id')->toSql()}) as sub"))
    ->mergeBindings($baseQuery)
    ->selectRaw('COUNT(*) as total')
    ->value('total');


    // Apply select, group, limit, offset
    $results = $baseQuery
        ->select(
            'tp.pro_id',
            'tp.admin_moq as recommended_quantity',
            'tp.pro_title',
            'tp.ware_house_stock as current_stock',
            'tp.reorder_qty as minimum_reorder_stock_level',			
            'tpe.app_cat_id as pro_category_id',
			'ta.application_name as category_name',
			'tp.upc_code',
            'tpe.hsn_code',
            'tpe.model_no',
			'tpe.pro_desc_entry as product_description',
            'tbl_delivery_order.O_Id',
            'tbl_delivery_order.D_Order_Date as alert_date',
            'tbl_order_product.pro_id as order_product_pro_id',
            'tbl_do_products.pro_id as DO_product_pro_id',
            DB::raw('SUM(tbl_do_products.Quantity) as invoiced_quantity_to_be_fulfilled'),
            'tbl_delivery_challan.id as delivery_challan_id'
        )
        ->groupBy('tbl_do_products.pro_id')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    return response()->json([
        'financial_year' => $fyStart->format('Y') . '-' . $fyEnd->format('Y'),
        'from' => $fyStart->format('Y-m-d'),
        'to' => $fyEnd->format('Y-m-d'),
        'total_count' => $totalCount,
        'page' => $page,
        'per_page' => $perPage,
        'purchase_alerts' => $results,
    ]);
}


public function purchasealerts(Request $request)
{
    $currentDate = Carbon::now();
    $fyStart = Carbon::create(
        $currentDate->month >= 4 ? $currentDate->year : $currentDate->year - 1,
        4, 1
    );
    $fyEnd = (clone $fyStart)->addYear()->subSecond();

    $lastFyStart = (clone $fyStart)->subYear();
    $lastFyEnd = (clone $fyStart)->subSecond();

    $currentFyLabel = $fyStart->format('Y') . '-' . $fyEnd->format('y');
    $lastFyLabel = $lastFyStart->format('Y') . '-' . $lastFyEnd->format('y');

    $perPage = (int) $request->input('records', 100);
    $page = (int) $request->input('pageno', 1);
    $offset = ($page - 1) * $perPage;

    $baseQuery = DB::table('tbl_delivery_order')
        ->leftJoin('tbl_delivery_challan', 'tbl_delivery_order.O_Id', '=', 'tbl_delivery_challan.O_Id')
        ->join('tbl_order', 'tbl_order.orders_id', '=', 'tbl_delivery_order.O_Id')
        ->join('tbl_order_product', 'tbl_order.orders_id', '=', 'tbl_order_product.order_id')
        ->join('tbl_products as tp', 'tbl_order_product.pro_id', '=', 'tp.pro_id')
        ->join('tbl_products_entry as tpe', 'tbl_order_product.pro_id', '=', 'tpe.pro_id')
        ->join('tbl_application as ta', 'tpe.app_cat_id', '=', 'ta.application_id')
        ->join('tbl_do_products', function ($join) {
            $join->on('tbl_delivery_order.O_Id', '=', 'tbl_do_products.OID')
                 ->on('tbl_do_products.pro_id', '=', 'tp.pro_id');
        })
        ->whereNull('tbl_delivery_challan.Invoice_No')
        ->whereBetween(DB::raw("STR_TO_DATE(tbl_delivery_order.D_Order_Date, '%Y-%m-%d')"), [
            $fyStart->format('Y-m-d H:i:s'), $fyEnd->format('Y-m-d H:i:s')
        ])
        ->where('tbl_delivery_order.D_Order_Date', '!=', '0000-00-00')
        ->where('tbl_delivery_order.do_type', '!=', 'service')
        ->where('tp.ware_house_stock', '=', 0)
        ->whereNotIn('tp.pro_id', function ($subquery) {
            $subquery->select('pro_id')->from('vendor_draft_po');
        });

    $totalCount = DB::table(DB::raw("({$baseQuery->select('tp.pro_id')->groupBy('tp.pro_id')->toSql()}) as sub"))
        ->mergeBindings($baseQuery)
        ->selectRaw('COUNT(*) as total')
        ->value('total');

    $results = $baseQuery
        ->select(
            'tp.pro_id',
            'tp.admin_moq as recommended_quantity',
            'tp.pro_title',
            'tp.ware_house_stock as current_stock',
            'tp.reorder_qty as minimum_reorder_stock_level',
            'tpe.app_cat_id as pro_category_id',
            'ta.application_name as category_name',
            'tp.upc_code',
            'tpe.hsn_code',
            'tpe.model_no',
            'tpe.pro_desc_entry as product_description',
            'tbl_delivery_order.O_Id',
            'tbl_delivery_order.D_Order_Date as alert_date',
            'tbl_order_product.pro_id as order_product_pro_id',
            'tbl_do_products.pro_id as DO_product_pro_id',
            DB::raw('SUM(tbl_do_products.Quantity) as invoiced_quantity_to_be_fulfilled'),
            'tbl_delivery_challan.id as delivery_challan_id'
        )
        ->groupBy('tbl_do_products.pro_id')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    $enrichedResults = $results->map(function ($row) use ($fyStart, $fyEnd, $lastFyStart, $lastFyEnd, $currentFyLabel, $lastFyLabel) {
        $pro_id = $row->pro_id;
        $month = 0;

        $salesFunnel = (float) $row->invoiced_quantity_to_be_fulfilled;
        $invoicedQty = total_qty_invoice($fyStart->toDateString(), $fyEnd->toDateString(), $pro_id, $month);

        $conversionRate = $salesFunnel > 0 ? ($invoicedQty / $salesFunnel) : 0;
        $conversionRatePer = $conversionRate * 100;

        $row->sales_funnel = $salesFunnel;
        $row->conversion_rate = round($conversionRatePer, 2) . '%';
        $row->invoiced_total_quantity_of_product = (int) $invoicedQty;

        $summary = getPoSummaryByProductId($pro_id);
        $row->ytd = [
            'label' => 'YTD (' . $currentFyLabel . ')',
            'purchase_quantity' => $summary['ytd']['purchase_quantity'] ?? 0,
            'avg_price' => $summary['ytd']['avg_price'] ?? 0,
        ];
        $row->last_fy = [
            'label' => 'Last FY (' . $lastFyLabel . ')',
            'purchase_quantity' => $summary['last_fy']['purchase_quantity'] ?? 0,
            'avg_price' => $summary['last_fy']['avg_price'] ?? 0,
        ];

        $poHistory = getPoHistoryFinalByProductId($pro_id);
        $row->po_history = $poHistory;

        if (!empty($poHistory[0])) {
            $lastPO = (array) $poHistory[0];
            $row->po_date = $lastPO['E_Date'] ?? null;
            $row->vendor_name = $lastPO['vendor_name'] ?? null;
            $row->last_purchase_price = $lastPO['price'] ?? null;
            $row->last_purchase_quantity = $lastPO['quantity'] ?? null;
        }

        return $row;
    });

    return response()->json([
        'financial_year' => $fyStart->format('Y') . '-' . $fyEnd->format('Y'),
        'from' => $fyStart->format('Y-m-d'),
        'to' => $fyEnd->format('Y-m-d'),
        'total_count' => $totalCount,
        'page' => $page,
        'per_page' => $perPage,
        'purchase_alerts' => $enrichedResults,
    ]);
}





public function purchaseforecasting_old(Request $request)
{
    $startDate = $request->input('start_date', '2025-04-01');
    $endDate = $request->input('end_date', '2026-03-31');
    $offerProbabilities = [3];

    // Pagination
    $perPage = (int) $request->input('records', 10);
    $page = (int) $request->input('pageno', 1);
    $offset = ($page - 1) * $perPage;

    // Base query
    $baseQuery = DB::table('tbl_order as t1')
        ->join('tbl_order_product as t2', 't1.orders_id', '=', 't2.order_id')
        ->join('tbl_products as tp', 't2.pro_id', '=', 'tp.pro_id')
        ->join('tbl_products_entry as tpe', 't2.pro_id', '=', 'tpe.pro_id')
        ->join('tbl_application as ta', 'tpe.app_cat_id', '=', 'ta.application_id')
        ->whereBetween('t1.time_ordered', [$startDate, $endDate])
        ->whereIn('t1.offer_probability', $offerProbabilities)
        ->where('tp.ware_house_stock', '>', 0)
        ->whereNotIn('tp.pro_id', function ($query) {
            $query->select('pro_id')->from('vendor_draft_po');
        })
        ->groupBy('tp.pro_id')
        ->select(
            'tp.pro_id',
            'tp.admin_moq as recommended_quantity',
            'tp.pro_title',
            'tp.ware_house_stock as current_stock',
            'tp.reorder_qty as minimum_reorder_stock_level',			
            'tpe.app_cat_id as pro_category_id',
            'ta.application_name as category_name',
            'tp.upc_code',
            'tpe.hsn_code',
            'tpe.model_no',
            't1.orders_id',
            't1.customers_id',
            't1.Price_type',
            't1.date_ordered',
            't1.time_ordered',
            't1.orders_status',
            't1.order_by',
            't1.offer_probability',
            DB::raw('SUM(t2.pro_quantity) as total_product_qty_ordered')
        );

    // Total count (for frontend)
    $totalCount = DB::table(DB::raw("({$baseQuery->toSql()}) as sub"))
                    ->mergeBindings($baseQuery)
                    ->count();

    // Paginated result
    $forecastData = $baseQuery
        ->orderByDesc('total_product_qty_ordered')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    return response()->json([
        'status' => true,
        'data' => $forecastData,
        'total_records' => $totalCount,
        'page' => $page,
        'per_page' => $perPage,
        'message' => 'Purchase forecasting data retrieved successfully.'
    ]);
}



public function purchaseforecasting(Request $request)
{
    $now = Carbon::now();
    $fyStart = Carbon::create($now->year, 4, 1);
    if ($now->month < 4) {
        $fyStart->subYear();
    }
    $fyEnd = $fyStart->copy()->addYear()->subDay();

    $lastFyStart = (clone $fyStart)->subYear();
    $lastFyEnd = (clone $fyStart)->subDay();

    $currentFyLabel = $fyStart->format('Y') . '-' . $fyEnd->format('y');
    $lastFyLabel = $lastFyStart->format('Y') . '-' . $lastFyEnd->format('y');

    $startDate = $request->input('start_date', $fyStart->toDateString());
    $endDate = $request->input('end_date', $fyEnd->toDateString());

    $perPage = (int) $request->input('records', 10);
    $page = (int) $request->input('pageno', 1);
    $offset = ($page - 1) * $perPage;

    $subQuery = DB::table('tbl_order as t1')
        ->join('tbl_order_product as t2', 't1.orders_id', '=', 't2.order_id')
        ->join('tbl_products as tp', 't2.pro_id', '=', 'tp.pro_id')
		->leftJoin('tbl_products_entry as tpe', 'tp.pro_id', '=', 'tpe.pro_id')

        ->whereBetween('t1.time_ordered', [$startDate, $endDate])
        ->where('tp.reorder_qty', '!=', 0)
        ->where('tp.ware_house_stock', '>', 0)
        ->whereIn('t1.offer_probability', [3])
        ->whereNotIn('tp.pro_id', function ($query) {
            $query->select('pro_id')->from('vendor_draft_po');
        })
        ->groupBy('tp.pro_id')
        ->select(
            'tp.pro_id',
            'tp.pro_title',
			'tpe.pro_desc_entry as description',
            'tp.ware_house_stock as current_stock',
            'tp.reorder_qty as minimum_reorder_stock_level',
            'tp.upc_code',
			'tpe.hsn_code',
            'tpe.model_no',
            'tpe.app_cat_id as pro_category_id',
            't1.orders_id',
            't1.customers_id',
            't1.Price_type',
            't1.date_ordered',
            't1.time_ordered',
            't1.orders_status',
            't1.order_by',
            't1.offer_probability',
            DB::raw('SUM(t2.pro_quantity) as total_product_qty_ordered')
        );

    $totalCount = DB::table(DB::raw("({$subQuery->toSql()}) as counted"))
        ->mergeBindings($subQuery)
        ->count();

    $rawData = $subQuery->orderByDesc('total_product_qty_ordered')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    $forecastData = $rawData->map(function ($row) use ($startDate, $endDate, $fyStart, $fyEnd, $lastFyStart, $lastFyEnd, $currentFyLabel, $lastFyLabel) {
        $pro_id = $row->pro_id;
        $month = 0;

        $invoicedQty = total_qty_invoice($startDate, $endDate, $pro_id, $month);

        $salesFunnel = (float) $row->total_product_qty_ordered;
        $conversionRate = $salesFunnel > 0 ? ($invoicedQty / $salesFunnel) : 0;
        $conversionRatePer = $conversionRate * 100;

        $recommendedQty = (($salesFunnel * $conversionRate) - $row->current_stock) + $row->minimum_reorder_stock_level;
        $recommendedQty = $recommendedQty < 0 ? 0 : round($recommendedQty);

        $row->invoiced_total_quantity_of_product = (int) $invoicedQty;
        $row->conversion_rate = round($conversionRatePer, 2) . '%';
        $row->recommended_quantity = $recommendedQty;
        $row->sales_funnel = $salesFunnel;

        $summary = getPoSummaryByProductId($pro_id);
        $row->ytd = [
            'label' => 'YTD (' . $currentFyLabel . ')',
            'purchase_quantity' => $summary['ytd']['purchase_quantity'] ?? 0,
            'avg_price' => $summary['ytd']['avg_price'] ?? 0,
        ];
        $row->last_fy = [
            'label' => 'Last FY (' . $lastFyLabel . ')',
            'purchase_quantity' => $summary['last_fy']['purchase_quantity'] ?? 0,
            'avg_price' => $summary['last_fy']['avg_price'] ?? 0,
        ];

        $poHistory = getPoHistoryFinalByProductId($pro_id);
        $row->po_history = $poHistory;

        if (!empty($poHistory[0])) {
            $lastPO = (array) $poHistory[0];
            $row->po_date = $lastPO['E_Date'] ?? null;
            $row->vendor_name = $lastPO['vendor_name'] ?? null;
            $row->last_purchase_price = $lastPO['price'] ?? null;
            $row->last_purchase_quantity = $lastPO['quantity'] ?? null;
        }

        return $row;
    });

    return response()->json([
        'status' => true,
        'message' => 'Purchase forecasting data retrieved successfully.',
        'data' => $forecastData,
        'total_records' => $totalCount,
        'page' => $page,
        'per_page' => $perPage,
        'fy_label' => $currentFyLabel,
    ]);
}





public function stockbreach_old(Request $request)
{
    // Financial Year Dates
    $today = Carbon::now();
    if ($today->month >= 4) {
        $startDate = Carbon::create($today->year, 4, 1)->toDateString();
        $endDate = Carbon::create($today->year + 1, 3, 31)->toDateString();
    } else {
        $startDate = Carbon::create($today->year - 1, 4, 1)->toDateString();
        $endDate = Carbon::create($today->year, 3, 31)->toDateString();
    }

    $startDate = $request->input('start_date', $startDate);
    $endDate = $request->input('end_date', $endDate);

    $perPage = (int) $request->input('records', 10);
    $page = (int) $request->input('pageno', 1);
    $offset = ($page - 1) * $perPage;

    // Base query
    $query = DB::table('tbl_delivery_order')
        ->leftJoin('tbl_delivery_challan', 'tbl_delivery_order.O_Id', '=', 'tbl_delivery_challan.O_Id')
        ->join('tbl_order', 'tbl_order.orders_id', '=', 'tbl_delivery_order.O_Id')
        ->join('tbl_do_products as t2', 'tbl_delivery_order.O_Id', '=', 't2.OID')
        ->join('tbl_products as tp', 't2.pro_id', '=', 'tp.pro_id')
        ->join('tbl_products_entry as tpe', 'tp.pro_id', '=', 'tpe.pro_id')
        ->join('tbl_application as ta', 'tpe.app_cat_id', '=', 'ta.application_id')
        ->whereNull('tbl_delivery_challan.Invoice_No')
        ->whereBetween(DB::raw("STR_TO_DATE(tbl_delivery_order.D_Order_Date, '%Y-%m-%d')"), [$startDate, $endDate])
        ->where('tbl_delivery_order.D_Order_Date', '!=', '0000-00-00')
        ->whereNotIn('tp.pro_id', function ($subQuery) {
            $subQuery->select('pro_id')->from('vendor_draft_po');
        })
        ->groupBy('t2.pro_id')
        ->select([
            'tp.pro_id',
			 'tp.admin_moq as recommended_quantity',
            'tp.pro_title',
            'tp.ware_house_stock as current_stock',
            'tp.reorder_qty as minimum_reorder_stock_level',			
            'tpe.app_cat_id as pro_category_id',
            'ta.application_name as category_name',
            'tp.upc_code',
            'tpe.hsn_code',
            'tpe.model_no',
            'tbl_delivery_order.O_Id',
            'tbl_delivery_order.D_Order_Date',
            't2.pro_id as DO_product_pro_id',
            DB::raw('SUM(t2.Quantity) as total_product_qty_ordered'),
            'tbl_delivery_challan.id as delivery_challan_id',
            'ta.application_name',
            'tpe.app_cat_id',
        ])
        ->havingRaw('SUM(t2.Quantity) > tp.reorder_qty');

    //  Paginate results
    $results = $query
        ->orderBy('tp.pro_title')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    // Total count using cloned query and count() on collection
    $totalCount = $query->get()->count();

    return response()->json([
        'status' => 'success',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'total_records' => $totalCount,
        'current_page' => $page,
        'per_page' => $perPage,
        'data' => $results,
    ]);
}


public function stockbreach(Request $request)
{
    $today = Carbon::now();
    $fyStart = Carbon::create($today->month >= 4 ? $today->year : $today->year - 1, 4, 1);
    $fyEnd = (clone $fyStart)->addYear()->subDay();

    $lastFyStart = (clone $fyStart)->subYear();
    $lastFyEnd = (clone $fyStart)->subDay();

    $currentFyLabel = $fyStart->format('Y') . '-' . $fyEnd->format('y');
    $lastFyLabel = $lastFyStart->format('Y') . '-' . $lastFyEnd->format('y');

    $startDate = $request->input('start_date', $fyStart->toDateString());
    $endDate = $request->input('end_date', $fyEnd->toDateString());

    $perPage = (int) $request->input('records', 10);
    $page = (int) $request->input('pageno', 1);
    $offset = ($page - 1) * $perPage;

    $query = DB::table('tbl_delivery_order')
        ->leftJoin('tbl_delivery_challan', 'tbl_delivery_order.O_Id', '=', 'tbl_delivery_challan.O_Id')
        ->join('tbl_order', 'tbl_order.orders_id', '=', 'tbl_delivery_order.O_Id')
        ->join('tbl_do_products as t2', 'tbl_delivery_order.O_Id', '=', 't2.OID')
        ->join('tbl_products as tp', 't2.pro_id', '=', 'tp.pro_id')
        ->leftJoin('tbl_products_entry as tpe', 'tp.pro_id', '=', 'tpe.pro_id')
        ->leftJoin('tbl_application as ta', 'tpe.app_cat_id', '=', 'ta.application_id')
        ->whereNull('tbl_delivery_challan.Invoice_No')
        ->whereBetween(DB::raw("STR_TO_DATE(tbl_delivery_order.D_Order_Date, '%Y-%m-%d')"), [$startDate, $endDate])
        ->where('tbl_delivery_order.D_Order_Date', '!=', '0000-00-00')
        ->whereNotIn('tp.pro_id', function ($subQuery) {
            $subQuery->select('pro_id')->from('vendor_draft_po');
        })
        ->groupBy('t2.pro_id')
        ->select([
            'tp.pro_id',
            'tp.admin_moq as recommended_quantity',
            'tp.pro_title',
            'tp.ware_house_stock as current_stock',
            'tp.reorder_qty as minimum_reorder_stock_level',
            'tpe.app_cat_id as pro_category_id',
            'ta.application_name as category_name',
            'tp.upc_code',
            'tpe.hsn_code',
            'tpe.model_no',
            'tpe.pro_desc_entry as description',
            'tbl_delivery_order.O_Id',
            'tbl_delivery_order.D_Order_Date',
            't2.pro_id as DO_product_pro_id',
            DB::raw('SUM(t2.Quantity) as total_product_qty_ordered'),
            'tbl_delivery_challan.id as delivery_challan_id',
            'ta.application_name',
            'tpe.app_cat_id',
        ])
        ->havingRaw('SUM(t2.Quantity) > tp.reorder_qty');

    $results = $query
        ->orderBy('tp.pro_title')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    $totalCount = $query->get()->count();

    $finalResults = $results->map(function ($row) use ($startDate, $endDate, $fyStart, $fyEnd, $lastFyStart, $lastFyEnd, $currentFyLabel, $lastFyLabel) {
        $pro_id = $row->pro_id;
        $month = 0;

        $invoicedQty = total_qty_invoice($startDate, $endDate, $pro_id, $month);
        $salesFunnel = (float) $row->total_product_qty_ordered;
        $conversionRate = $salesFunnel > 0 ? ($invoicedQty / $salesFunnel) : 0;
        $conversionRatePer = $conversionRate * 100;

        $recommendedQty = (($salesFunnel * $conversionRate) - $row->current_stock) + $row->minimum_reorder_stock_level;
        $recommendedQty = $recommendedQty < 0 ? 0 : round($recommendedQty);

        $row->invoiced_total_quantity_of_product = (int) $invoicedQty;
        $row->conversion_rate = round($conversionRatePer, 2) . '%';
        $row->recommended_quantity = $recommendedQty;
        $row->sales_funnel = $salesFunnel;

        $summary = getPoSummaryByProductId($pro_id);
        $row->ytd = [
            'label' => 'YTD (' . $currentFyLabel . ')',
            'purchase_quantity' => $summary['ytd']['purchase_quantity'] ?? 0,
            'avg_price' => $summary['ytd']['avg_price'] ?? 0,
        ];
        $row->last_fy = [
            'label' => 'Last FY (' . $lastFyLabel . ')',
            'purchase_quantity' => $summary['last_fy']['purchase_quantity'] ?? 0,
            'avg_price' => $summary['last_fy']['avg_price'] ?? 0,
        ];

        $poHistory = getPoHistoryFinalByProductId($pro_id);
        $row->po_history = $poHistory;

        if (!empty($poHistory[0])) {
            $lastPO = (array) $poHistory[0];
            $row->po_date = $lastPO['E_Date'] ?? null;
            $row->vendor_name = $lastPO['vendor_name'] ?? null;
            $row->last_purchase_price = $lastPO['price'] ?? null;
            $row->last_purchase_quantity = $lastPO['quantity'] ?? null;
        }

        return $row;
    });

    return response()->json([
        'status' => 'success',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'total_records' => $totalCount,
        'current_page' => $page,
        'per_page' => $perPage,
        'data' => $finalResults,
    ]);
}




}
//class closed