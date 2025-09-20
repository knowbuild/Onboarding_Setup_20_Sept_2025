<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\{
    User, 
    Customer, 
    Category, 
    CustomerBank, 
    CustomerCompany, 
    CustomerCompanyContact, 
    CurrencyPricelist, 
    Enquiry, 
    Product, 
    Country, 
    Currency, 
    FinancialYear, 
    State, 
    City, 
    Application, 
    ApplicationService, 
    ProductMain, 
    ProductsEntry, 
    ProQtyMaxDiscountPercentage, 
    Service, 
    ServicesEntry, 
    Company, 
    Designation, 
    DepartmentComp, 
    CompanyExtn,
    WebEnq,
    WebEnqEdit,
    CustSegment,
    FiscalMonth,
    ProductTypeClassMaster,
    ServiceMaster,
    WarrantyMaster,
   
};

use DB;

class GetAllController extends Controller
{
    public function countries(Request $request)
    {       
        $data = Country::where('country_status','active')->get();
       
        $response = [
            'status' => 'success',
            'message' => 'Country is listed here successfully. ',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }


    public function currencies(Request $request)
    {       
        $data = Currency::where('status','active')->get();
        
        $response = [
            'status' => 'success',
            'message' => 'Currencies is listed here successfully. ',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }
    public function fiscal_months(Request $request)
    {       
        $data = FiscalMonth::where('status', 'active')->get();

        $response = [

            'status' => 'success',
            'message' => 'Financial Month is listed here successfully. ',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    public function financial_years(Request $request)
    {       
        $data = FinancialYear::where('fin_status', 'active')->where('deleteflag','active')->get();

        $response = [

            'status' => 'success',
            'message' => 'Financial Year is listed here successfully. ',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    public function states(Request $request)
    {             
        $data = State::where('zone_country_id',$request->country_id)->get();       
        $response = [
            'status' => 'success',
            'message' => 'States is listed here successfully. ',
            'data' => $data,
        ];
        return response()->json($response, 200);
    }

    public function cities(Request $request)
    {             
        $data = City::where('state_code',$request->state_id)->get();       
        $response = [
            'status' => 'success',
            'message' => 'City is listed here successfully. ',
            'data' => $data,
        ];
        return response()->json($response, 200);
    }

  public function departments(Request $request)
{
    $data = DepartmentComp::where('deleteflag', 'active')
                ->orderBy('department_name', 'asc')
                ->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Departments listed successfully.',
        'data' => $data,
    ], 200);
}


    public function getCustomer(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|string|max:50',
            'step'          => 'required|integer|min:1|max:9',
        ]);
    
        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Validation Error!',
                'errors'  => $validator->errors(),
            ], 422);
        }
      
        // Fetch customer
        $customer = Customer::where('customer_code', $request->customer_code)->first();
    
        if (!$customer) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Customer not found!',
            ], 404);
        }
     
        $step = (int) $request->step;
    
        // Step-wise logic
        switch ($step) {
            case 1:
                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Customer retrieved successfully',
                    'step'     => $step,
                    'customer' => $customer->only([
                        'id', 'customer_code', 'name', 'email', 'licenses', 'organisation',
                        'country', 'currency', 'fiscal_month', 'revenue_per_year', 'date_format','upc_digit'
                    ]),
                    'userfin_data' => $customer->only(['country', 'currency', 'fiscal_month']),
                ], 200);
    
            case 2:
                $banks = CustomerBank::where('customer_code', $customer->customer_code)->get();
                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Customer retrieved successfully',
                    'step'     => $step,
                    'customer' => array_merge(
                        $customer->only([
                            'customer_code', 'company_address_2','company_address', 'company_country', 'company_state',
                            'company_city', 'company_zipcode', 'purchase_address','purchase_address_2', 'purchase_country',
                            'purchase_state', 'purchase_city', 'purchase_zipcode', 'gst_number',
                            'sales_offer_format', 'purchase_order_format', 'company_logo'
                        ]),
                        ['banks' => $banks] // Include banks in response
                    ),
                    'userfin_data' => $customer->only(['country', 'currency', 'fiscal_month']),
                ], 200);
    
            case 3: 
                // Fetch domestic and foreign markets
                $domesticMarkets = CurrencyPricelist::where('type', 'domesticMarkets')
                    ->get(['currency_id as currencyId', 'price_list_name as priceListName', 'is_default']);
    
                $foreignMarkets = CurrencyPricelist::where('type', 'foreignMarkets')
                    ->get(['currency_id as currencyId', 'price_list_name as priceListName', 'is_default']);
    
                // Convert is_default (0/1) back to boolean
                $domesticMarkets->transform(fn($item) => [
                    'currencyId'    => $item->currencyId,
                    'priceListName' => $item->priceListName,
                    'isDefault'     => (bool) $item->is_default,
                ]);
    
                $foreignMarkets->transform(fn($item) => [
                    'currencyId'    => $item->currencyId,
                    'priceListName' => $item->priceListName,
                    'isDefault'     => (bool) $item->is_default,
                ]);
    
                return response()->json([
                    'status'          => 'success',
                    'message'         => 'Customer retrieved successfully',
                    'step'            => $step,
                    'domesticMarkets' => $domesticMarkets,
                    'foreignMarkets'  => $foreignMarkets,
                    'userfin_data' => $customer->only(['country', 'currency', 'fiscal_month']),
                ], 200);
    
                 case 4:
           
              $segments = CustSegment::select('cust_segment_id as id', 'cust_segment_name as name', 'interactions_reqd','cust_segment_status as status')
            ->orderByDesc('cust_segment_id')
            ->get();
               
                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Segment retrieved successfully',
                    'step'     => $step,
             
                    'segments' => $segments,
                    'userfin_data' => $customer->only(['country', 'currency', 'fiscal_month']),
                ], 200);
                       case 5:
           
          $warranties = WarrantyMaster::select('warranty_id as id', 'year', 'month','warranty_name', 'warranty_status as status')
            ->orderByDesc('warranty_id')
            ->get();
               
                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Warranty retrieved successfully',
                    'step'     => $step,
             
                    'warranties' => $warranties,
                    'userfin_data' => $customer->only(['country', 'currency', 'fiscal_month']),
                ], 200);
                       case 6:
               $productClassification = ProductTypeClassMaster::select('product_type_class_id as id', 'product_type_class_name as name', 'show_on_pqv','default', 'product_type_class_status as status')
            ->orderByDesc('product_type_class_id')
            ->get();
               
                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Product Classification retrieved successfully',
                    'step'     => $step,
             
                    'productClassification' => $productClassification,
                    'userfin_data' => $customer->only(['country', 'currency', 'fiscal_month']),
                ], 200);

            case 7:
         
                if ($request->type === 'product') {
                    $items = Application::leftJoin('tbl_warranty_master', 'tbl_application.cat_warranty', '=', 'tbl_warranty_master.warranty_id')
                        ->select(
                            'tbl_application.application_name as name',
                            'tbl_application.cat_abrv as abbreviation',
                            'tbl_application.hsn_code as hsn_code',
                            'tbl_application.tax_class_id as gst_vat_percentage',
                      //      'tbl_application.max_discount as max_discount_percentage',
                            'tbl_warranty_master.warranty_name as warranty', // Fetch Warranty Name
                         
                        )
                        ->where('tbl_application.application_status', 'active')
                        ->where('tbl_application.deleteflag', 'active')
                        ->orderBy('tbl_application.created_at', 'desc')
                        ->get();
                } else {
                    $items = ApplicationService::select(
                        'tbl_application_service.application_service_name as name',
                        'tbl_application_service.cat_abrv as abbreviation',
                        'tbl_application_service.hsn_code as hsn_code',
                        'tbl_application_service.tax_class_id as gst_vat_percentage',
                     //   'tbl_application_service.max_discount as max_discount_percentage',
                    )
                    ->where('tbl_application_service.application_service_status', 'active')
                    ->where('tbl_application_service.deleteflag', 'active')
                        ->orderBy('tbl_application_service.created_at', 'desc')
                        ->get();
                }
        
            
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Category retrieved successfully',
                    'items'   => $items,
                    'userfin_data' => $customer->only(['country', 'currency', 'fiscal_month']),
                ], 200);

            
                case 8:
                   if ($request->type === 'product') {
    $items = ProductMain::with('productsEntry')  // Eager load related entry
        ->get()
        ->map(function ($product) {
                 $productTypeClassName  = ProductTypeClassMaster::where('product_type_class_id', $product->product_type_class_id)->value('product_type_class_name') ?? "Null";
                   $category = Application::where('application_id', $product->cate_id)->get();
            return [
                'category_code'     => $product->cate_id,
                'name'              => $product->pro_title,
                'hot'               => $product->hot_product,
                'model'             => optional($product->productsEntry)->model_no,
                'moq'               => $product->admin_moq,
                'stock_level'       => $product->ware_house_stock,
                'max_discount_per'  => $product->pro_max_discount,
                'warranty'          => $product->pro_warranty,
                'price_type'        => optional($product->productsEntry)->price_list,
                'item_code'         => $product->pro_group_id, // Verify if this is correct
                'hsn_code'          => optional($product->productsEntry)->hsn_code,
                'price'             => $product->pro_price,
                'description'       => optional($product->productsEntry)->pro_desc_entry,
                'upc'               => $product->upc_code,
                'gst_vat_percentage'  => $category['tax_class_id'] ?? "Null",
'product_class'  => $productTypeClassName ?? "Null",
'category_name'  => $category['application_name'] ?? "Null",
            ];
        });
} else { 
    $items = Service::with('serviceEntry')  // Eager load related entry
        ->get()
        ->map(function ($service) {
              $category = ServiceMaster::where('service_id', $service->cate_id)->get();
            return [
                'category_code'     => $service->cate_id,
                'name'              => $service->service_title,
                'hot'               => $service->hot_service,
                'model'             => optional($service->serviceEntry)->model_no,
                'moq'               => $service->admin_moq,
                'stock_level'       => $service->ware_house_stock,
                'max_discount_per'  => $service->service_max_discount,
                'price_type'        => optional($service->serviceEntry)->price_list,
                'item_code'         => $service->service_group_id, // Verify if this is correct
                'hsn_code'          => optional($service->serviceEntry)->hsn_code,
                'price'             => $service->service_price,
                'description'       => optional($service->serviceEntry)->service_desc_entry,
                'upc'               => $service->upc_code,
                          
                            'category_name'  => $category['service_name'] ?? "Null",
            ];
        });
}

return response()->json([
    'status'       => 'success',
    'message'      => 'Customer retrieved successfully',
    'items'        => $items,
    'userfin_data' => $customer->only(['country', 'currency', 'fiscal_month']),
], 200);

                    
       
            case 9:
                $company = Company::select([
                    'comp_name as company_name',
                    'cust_segment as customer_industry',
                    'office_type as office_type',
                    'mobile_no as mobile',
                    'gst_no as gst_number',
                    'comp_website as company_website',
                    'address as address',
                    'zip as zip_code',
                    'salutation as person_salutation',
                    'fname as person_name',
                    'email as email',
                    // Adding subqueries for related IDs
                    DB::raw('(SELECT company_extn_name FROM tbl_company_extn WHERE company_extn_id  = tbl_comp.co_extn_id  LIMIT 1) AS company_type'),
                    DB::raw('(SELECT designation_name  FROM tbl_designation WHERE designation_id = tbl_comp.designation_id LIMIT 1) AS designation'),
                    DB::raw('(SELECT department_name  FROM tbl_department_comp WHERE department_id = tbl_comp.department_id LIMIT 1) AS department'),
                    DB::raw('(SELECT country_name  FROM tbl_country WHERE country_id = tbl_comp.country LIMIT 1) AS country'),
                    DB::raw('(SELECT zone_name FROM tbl_zones WHERE zone_id  = tbl_comp.state LIMIT 1) AS state'),
                    DB::raw('(SELECT city_name  FROM all_cities WHERE city_id = tbl_comp.city LIMIT 1) AS city')
                ])->limit(100)->get();
              
                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Customer retrieved successfully',
                    'step'     => $step,
                    'customer' => $company,
                    'userfin_data' => $customer->only(['country', 'currency', 'fiscal_month']),
                ], 200);
           
    
            default:
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Invalid step provided!',
                ], 400);
        }
    }
    


}
