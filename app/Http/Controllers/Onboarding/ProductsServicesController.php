<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\{User, Customer, Category, Application, ApplicationService, CurrencyPricelist, Product, ProductMain, ProductsEntry, ProQtyMaxDiscountPercentage, Service, ServicesEntry, Currency, WarrantyMaster, ProductTypeClassMaster};
use App\Exports\ProductExport;
use App\Exports\ServiceExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductsServicesController extends Controller
{

   public function product_export(Request $request)
    {
        try {
            $categoryId = $request->category_id ?? null;

            $fileName = 'product_' . time() . '.xlsx';
            $filePath = 'exports/items/product/' . $fileName;

            // Store the file in public storage with category filter
            Excel::store(new ProductExport($categoryId), $filePath, 'public');

            // Generate public URL for download
            $fileUrl = Storage::url($filePath);

            return response()->json([
                'status'  => 'success',
                'message' => 'Export successful',
                'file_url'=> url($fileUrl),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function service_export(Request $request)
    {
        try {
            $categoryId = $request->category_id ?? null;

            $fileName = 'service_' . time() . '.xlsx';
            $filePath = 'exports/items/service/' . $fileName;

            // Store the file in public storage with category filter
            Excel::store(new ServiceExport($categoryId), $filePath, 'public');

            // Generate public URL for download
            $fileUrl = Storage::url($filePath);

            return response()->json([
                'status'  => 'success',
                'message' => 'Export successful',
                'file_url'=> url($fileUrl),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }
            

        
 
  public function importExcelProductService(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'customer_code' => 'nullable|string|max:50',
            'item_excel'    => 'required|string',
            'type'          => 'required|string|in:product,service',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
       
 
        try {
            // Decode Base64 Excel Data
            $base64String = str_contains($request->item_excel, 'base64,') 
                ? explode('base64,', $request->item_excel)[1] 
                : $request->item_excel;
    
            $excelData = base64_decode($base64String);
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_') . '.xlsx';
            file_put_contents($tempFile, $excelData);
    
            if (!file_exists($tempFile)) {
                return response()->json(['error' => 'Temporary file not created.'], 500);
            }
    
            $spreadsheet = IOFactory::load($tempFile);
            $worksheet = $spreadsheet->getActiveSheet();


 // Extract headers from the first row
$fileHeaders = [];

foreach ($worksheet->getRowIterator(1, 1) as $row) {
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);

    $colCount = 0;
    foreach ($cellIterator as $cell) {
        if ($colCount >= 15) break; // Stop after reading 15 columns
        $fileHeaders[] = trim($cell->getValue());
        $colCount++;
    }
}
 // Expected headers

 if ($request->type == 'product') {
 $expectedHeaders = [
    'S.No', 'Product Category', 'Product Name', 'Hot Product', 'Product Class', 'Admin MOQ',
    'Reorder Stock Level', 'Max Discount (In %)', 'Product Warranty', 'Price List Type', 
    'Product Item Code', 'HSN Code', 'Product Price', 'Product Description', 'UPC'
 ];
 }
 if ($request->type == 'service') {
    $expectedHeaders = [
        'S.No', 'Service Category', 'Service Name', 'Hot Service', 'Service Class', 'Admin MOQ',
        'Reorder Stock Level', 'Max Discount (In %)', 'Service Warranty', 'Price List Type', 
        'Service Item Code', 'HSN Code', 'Service Price', 'Service Description', 'UPC'
    ];
 }
 // Check if headers match
 if ($fileHeaders !== $expectedHeaders) {
     return response()->json([
         'success' => false,
         'errors' => 'Incorrect Excel file format. Please select the correct file and try again.',
         'expected_headers' => $expectedHeaders,
         'received_headers' => $fileHeaders
     ], 501);
 }

            $rows = $worksheet->toArray();
    
            // Remove header row
            array_shift($rows);
    
            $errors = [];
            $success = [];
            
     
            $uniqueRows = [];
            $dataGet = [];
            foreach ($rows as $index => $row) {
                if (empty($row[1])) {
                    continue; // Skip row if $row[1] is null or empty
                }
            
                $rowIndex = $index + 2;
                $name = trim($row[2] ?? '');
            
                // Create a unique key based on multiple columns
                $uniqueKey = implode('|', array_slice($row, 1, 13)); // Adjust slice as needed
            
                if (in_array($uniqueKey, $uniqueRows)) {
                    $errors[$rowIndex]['error'] = ['Duplicate row found in uploaded file: ' . $name];
                    continue;
                }
            
                $uniqueRows[] = $uniqueKey;

     $upcCodeProduct = upcCodeProduct();
     $upcCode = upcCodeService(); // Call the helper function
                $data = [
                    'category'     => $row[1] ?? null,
                    'name'              => $row[2] ?? null,
                    'hot'               => $row[3] ?? null,
                    'model'             => $row[4] ?? null,
                    'moq'               => filter_var($row[5] ?? 0, FILTER_VALIDATE_INT),
                    'stock_level'       => filter_var($row[6] ?? 0, FILTER_VALIDATE_INT),
                    'max_discount_per'  => filter_var($row[7] ?? 0, FILTER_VALIDATE_INT),
                    'warranty'          => $row[8] ?? null,
                    'price_type'        => $row[9] ?? null,
                    'item_code'         => $row[10] ?? null,
                    'hsn_code'          => $row[11] ?? null,
                    'price'             => filter_var($row[12] ?? 0, FILTER_VALIDATE_FLOAT),
                    'description'       => $row[13] ?? null,
                    'type'              => $request->type,
                    'customer_code'  => $request->customer_code,
                    'upc'               => $upcCodeProduct,
                ];
                $dataGet[] = $data;
                // Validate each row
                $rowValidator = Validator::make($data, [
                    'category'     => 'required|string|max:100',
                    'name'              => 'required|string|max:100',
                    'hot'               => 'required|in:Yes,No',
                    'model'             => 'required|string|max:100',
                    'moq'               => 'nullable|integer|min:0',
                    'stock_level'       => 'nullable|integer|min:0',
                    'max_discount_per'  => 'required|integer|min:0|max:100',
                    'warranty'          => 'nullable|string|max:100',
                    'price_type'        => 'required|string',
                    '"hsn_code": [
      "The hsn code may not be greater than 8 characters."
    ]'         => 'required|string|max:100',
                    'hsn_code'          =>  'required|string|min:4|max:8',
                    'price'             => 'required|min:0',
                    'description'       => 'nullable|string',
                ]);
    
                if ($rowValidator->fails()) {
                    $errors[$rowIndex] = $rowValidator->errors()->toArray();
                    continue;
                }
  
                if ($request->type == 'product') {
                $categoryId = Application::where('application_name', 'like', $row[1])->value('application_id') ?? 0;
                }
                if ($request->type == 'service') {
                    $categoryId = ApplicationService::where('application_service_name', 'like', $row[1])->value('application_service_id') ?? 0;
                }
                if ($categoryId === 0) {
                    $errors[$rowIndex]['category'] = ['Invalid category name: ' . $row[1]];
                    continue;
                }  
     
                $modelId = ProductTypeClassMaster::where('product_type_class_name', 'like', $row[4])->value('product_type_class_id') ?? 0;
                if ($modelId === 0) {
                    $errors[$rowIndex]['model'] = ['Invalid model name: ' . $row[4]];
                    continue;
                }  
                $warrantyId = WarrantyMaster::where('warranty_name', 'like', $row[8])->value('warranty_id') ?? 0;
                if ($warrantyId === 0) {
                    $errors[$rowIndex]['warranty'] = ['Invalid warranty name: ' . $row[8]];
                    continue;
                }    
                $priceTypeId = CurrencyPricelist::where('price_list_name', 'like', $row[9])->value('pricelist_id') ?? 0;
           
                if ($priceTypeId === 0) {
                    $errors[$rowIndex]['price_type'] = ['Invalid price_type name: ' . $row[9]];
                    continue;
                }  

              $hotrow = $row[3];
              if($hotrow == 'Yes'){
                $hot = 1;
              }
              else{
                $hot = 0;
              }

                if ($request->type == 'product') {

                    $datan = [
                      
                        'cate_id' => $categoryId,
                        'pro_title' => $row[2] ?? null,

                        'pro_price' => filter_var($row[12] ?? 0, FILTER_VALIDATE_FLOAT),
                    
                     'pro_details' => $row[13] ?? null,
                        'ware_house_stock' => filter_var($row[6] ?? 0, FILTER_VALIDATE_INT),
                        'pro_max_discount' => filter_var($row[7] ?? 0, FILTER_VALIDATE_INT),
                    
                        'status' => 'active',
                        'deleteflag' => 'active',
                        'hot_product' => $hot ?? null,
                        'pro_warranty' => $warrantyId,
                    
                        'product_type_class_id' => $modelId,
                        'upc_code' => $upcCodeProduct,
                        'admin_moq' => filter_var($row[5] ?? 0, FILTER_VALIDATE_INT),
          
                    ];
                 
                    if (!ProductMain::where([
                        'pro_title' => $datan['pro_title'],
                        'pro_price' => $datan['pro_price'],
                        'pro_details' => $datan['pro_details'],
                        'ware_house_stock' => $datan['ware_house_stock'],
                        'pro_max_discount' => $datan['pro_max_discount'],
                        'admin_moq' => $datan['admin_moq'],
                    ])->exists()) {
                             
                        $product = ProductMain::create($datan);
                      insertCategoryIDProductService('product', $product->pro_id, $categoryId);
                        $success[$rowIndex] = "Product added successfully";
    
                        $data1 = [
                            'app_cat_id' => $categoryId,
                            'cate_id' => $categoryId,
                            'pro_id' => $product->pro_id,
                            'price_list' => $priceTypeId,
                            'pro_desc_entry' => $row[13] ?? null,
                            'pro_price_entry' =>filter_var($row[12] ?? 0, FILTER_VALIDATE_FLOAT),
                            'model_no' => $row[4] ?? null,
                            'sort_order' => 1,
                            'status' => 'active',
                            'deleteflag' => 'active',
                            'last_modified' => now(),
                            'hsn_code' => $row[11] ?? null,
                            'last_modified_by' => 1,
                        ];
            
                        ProductsEntry::create($data1);
                    } else {
                        $errors[$rowIndex] = ['error' => 'Product already exists.'];
                    }
                }
    
                if ($request->type == 'service') {
                    $datan = [
                 'cate_id'              => $categoryId,
                        'service_title' => $row[2] ?? null,
                        'service_price' => filter_var($row[12] ?? 0, FILTER_VALIDATE_FLOAT),
                       'service_details' => $row[13] ?? null,
                        'ware_house_stock' => filter_var($row[6] ?? 0, FILTER_VALIDATE_INT),
                        'service_max_discount' => filter_var($row[7] ?? 0, FILTER_VALIDATE_INT),
                        'status' => 'active',
                        'deleteflag' => 'active',
                        'hot_service' => $hot ?? 0,
                       'upc_code' =>  $upcCode,
                        'key_service' => filter_var($row[5] ?? 0, FILTER_VALIDATE_INT),
              
                    ];

                    if (!Service::where([
                        'service_title' => $datan['service_title'],
                        'service_price' => $datan['service_price'],
                        'service_details' => $datan['service_details'],
                        'ware_house_stock' => $datan['ware_house_stock'],
                        'service_max_discount' => $datan['service_max_discount'],
                        'hot_service' => $datan['hot_service'],
                        'key_service' => $datan['key_service'],
                    ])->exists()) {
                  
                        $service = Service::create($datan);
                        insertCategoryIDProductService('service', $service->service_id, $categoryId);
                        $success[$rowIndex] = "Service added successfully";
                      
                        $data1 = [
                            'app_cat_id' => $categoryId,
                            'cate_id' =>$categoryId,
                            'service_id' => $service->service_id,
                            'price_list' => $priceTypeId,
                            'service_desc_entry' => $row[13] ?? null,
                            'service_price_entry' => filter_var($row[12] ?? 0, FILTER_VALIDATE_FLOAT),
                            'model_no' => $row[4] ?? null,
                            'sort_order' => 1,
                            'status' => 'active',
                            'deleteflag' => 'active',
                            'last_modified' => now()->format('Y-m-d (D) H:i:s'),
                            'hsn_code' => $row[11] ?? null,
                            'last_modified_by' => 1,
                        ];
                 
                        ServicesEntry::create($data1);
                      
                    } else {
                        $errors[$rowIndex] = ['error' => 'Service already exists.'];
                    }
                }
            }
    
                      if (count($errors) == 0 && $request->customer_code) {
          
    $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();

                 $currentStep = 8;
    $existingStep = $customer->current_step;
    $customer_id = $customer->id;

                      if ($currentStep != $existingStep && $currentStep > $existingStep) {
        $customer->update(['current_step' => $currentStep]);
    }
    if ($currentStep != $existingStep){
   createonboardingProgres($customer_id,8);
    }
       }

            return response()->json([
                'success' => $success,
                'errors'  => $errors,
                  'data' => $dataGet,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid file format or corrupted file.'], 500);
        }
    }


 
  
    
    public function importExcelProductNew(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|string|max:50',
            'item_excel'    => 'required|string',
            'type'          => 'required|string|in:product,service',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
      
        try {
  
            // Decode Base64 Excel Data
            $base64String = str_contains($request->item_excel, 'base64,') 
                ? explode('base64,', $request->item_excel)[1] 
                : $request->item_excel;
    
            $excelData = base64_decode($base64String);
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_') . '.xlsx';
            file_put_contents($tempFile, $excelData);
    
            if (!file_exists($tempFile)) {
                return response()->json(['error' => 'Temporary file not created.'], 500);
            }
    
            $spreadsheet = IOFactory::load($tempFile);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
    
            // Remove header row
            array_shift($rows);
    
            $errors = [];
            $success = [];
    
            foreach ($rows as $index => $row) {
                $rowIndex = $index + 2;
    
                $data = [
                    'category_code'     => $row[1] ?? null,
                    'name'              => $row[2] ?? null,
                    'hot'               => $row[3] ?? null,
                    'model'             => $row[4] ?? null,
                    'moq'               => filter_var($row[5] ?? 0, FILTER_VALIDATE_INT),
                    'stock_level'       => filter_var($row[6] ?? 0, FILTER_VALIDATE_INT),
                    'max_discount_per'  => filter_var($row[7] ?? 0, FILTER_VALIDATE_INT),
                    'warranty'          => $row[8] ?? null,
                    'price_type'        => filter_var($row[9] ?? 0, FILTER_VALIDATE_INT),
                    'item_code'         => $row[10] ?? null,
                    'hsn_code'          => $row[11] ?? null,
                    'price'             => filter_var($row[12] ?? 0, FILTER_VALIDATE_FLOAT),
                    'description'       => $row[13] ?? null,
                    'type'              => $request->type,
                    'customer_code'  => $request->customer_code,
                    'upc'               => strtoupper(substr($row[1], 0, 3)) . rand(100, 999),
                ];
                $data1 = [
                    'category_code'     => $row[1] ?? null,
                    'name'              => $row[2] ?? null,
                    'hot'               => $row[3] ?? null,
                    'model'             => $row[4] ?? null,
                    'moq'               => filter_var($row[5] ?? 0, FILTER_VALIDATE_INT),
                    'stock_level'       => filter_var($row[6] ?? 0, FILTER_VALIDATE_INT),
                    'max_discount_per'  => filter_var($row[7] ?? 0, FILTER_VALIDATE_INT),
                    'warranty'          => $row[8] ?? null,
                    'price_type'        => filter_var($row[9] ?? 0, FILTER_VALIDATE_INT),
                    'item_code'         => $row[10] ?? null,
                    'hsn_code'          => $row[11] ?? null,
                    'price'             => filter_var($row[12] ?? 0, FILTER_VALIDATE_FLOAT),
                    'description'       => $row[13] ?? null,
                    'type'              => $request->type,
                'customer_code'  => $request->customer_code,
                ];
                // Validate data
                $rowValidator = Validator::make($data, [
                    'category_code'     => 'required|string|max:100',
                    'name'              => 'required|string|max:100',
                    'hot'               => 'required|in:0,1',
                    'model'             => 'required|string|max:100',
                    'moq'               => 'nullable|integer|min:0',
                    'stock_level'       => 'nullable|integer|min:0',
                    'max_discount_per'  => 'required|integer|min:0|max:100',
                    'warranty'          => 'nullable|string|max:100',
                    'price_type'        => 'required|integer',
                    'item_code'         => 'required|string|max:100',
                    'hsn_code'          => 'required|string|max:100',
                    'price'             => 'required|numeric|min:0',
                    'description'       => 'nullable|string',
                ]);
    
                if ($rowValidator->fails()) {
                    $errors[$rowIndex] = $rowValidator->errors()->toArray();
                    continue;
                }
              
                // Check for duplicate entries
                if (Product::where($data1)->exists()) {
                    $errors[$rowIndex]['error'] = ['Exact same record already exists.'];
                    continue;
                }
            
                $success[] = $data;
            }

            // Insert Data in Bulk
            if (!empty($success)) {
                Product::insert($success);
            }
    
            return response()->json([
                'success' => $success,
                'errors'  => $errors
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid file format or corrupted file.'], 500);
        }
    }
    
   
}
