<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\User;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Application;
use App\Models\ApplicationService;
use App\Models\WarrantyMaster;

use App\Exports\CategoryProductExport;
use App\Exports\CategoryServiceExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
 
class CategoryController extends Controller
{
    public function product_category_export()
    {
        try {
            $fileName = 'category_' . time() . '.xlsx';
            $filePath = 'exports/category/product/' . $fileName;

            // Store the file in public storage
            Excel::store(new CategoryProductExport, $filePath, 'public');

 // Generate public URL for download
            $fileUrl = Storage::url($filePath);

            return response()->json([
                'status' => 'success',
                'message' => 'Export successful',
                'file_url' => url($fileUrl),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function service_category_export()
    {
        try {
            $fileName = 'category_' . time() . '.xlsx';
            $filePath = 'exports/category/service/' . $fileName;

            // Store the file in public storage
            Excel::store(new CategoryServiceExport, $filePath, 'public');

 // Generate public URL for download
            $fileUrl = Storage::url($filePath);

            return response()->json([
                'status' => 'success',
                'message' => 'Export successful',
                'file_url' => url($fileUrl),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }
  
    public function importExcelCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|string|max:50',
            'category_excel' => 'required|string',
            'type' => 'required|string|in:product,service',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        try {
            $base64String = $request->category_excel;
            if (str_contains($base64String, 'base64,')) {
                $base64String = explode('base64,', $base64String)[1];
            }
    
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
        if ($colCount >= 7) break; // Stop after reading 7 columns
        $fileHeaders[] = trim($cell->getValue());
        $colCount++;
    }
}

 // Expected headers

 if ($request->type == 'product') {
 $expectedHeaders = [
     'S.No',
     'Product Category Name',
     'Abbreviation of Product Category (Maximum 3 alphabets), eg AB',
     'HSN Code (Fill in 8 digit code, else 4 digits)',
     'GST / VAT % (If GST applicable is 18%, fill in value as 18)',
     'Max Discount (In %) (If Max discount applicable is 15%, fill in value as 15)',
     'Product Warranty (Choose warranty through drop down)'
 ];
 }
 if ($request->type == 'service') {
    $expectedHeaders = [
        'S.No',
        'Service Category Name',
        'Abbreviation of Service Category (Maximum 3 alphabets), eg AB',
        'SAC Code (Fill in 8 digit code, else 4 digits)',
        'GST / VAT % (If  GST applicable is 18%, fill in value as 18)',
        'Max Discount (In %) (If  Max discount applicable is 15%, fill in value as 15)',
        'Service Warranty (Choose warranty through drop down)'
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
            array_shift($rows);
    
            $errors = [];
            $success = [];
            $uniqueNames = [];
    
            foreach ($rows as $index => $row) {
                if (empty($row[1])) {
                    continue; // Skip row if $row[2] is null or empty
                }

    
                $rowIndex = $index + 2;
                $name = trim($row[1] ?? '');
    
                if (in_array($name, $uniqueNames)) {
                    $errors[$rowIndex]['error'] = ['Duplicate name found in uploaded file: ' . $name];
                    continue;
                }
                $uniqueNames[] = $name;
    
                $rowValidator = Validator::make([
                    'name' => $name,
                    'abbreviation' => $row[2] ?? null,
                    'hsn_code' => $row[3] ?? null,
                    'gst_vat_percentage' => $row[4] ?? null,
                    'max_discount_percentage' => $row[5] ?? null,
                    'warranty' => $row[6] ?? null,
                ], [
                    'name' => 'required|string|min:3|max:100',
                    'abbreviation' => 'required|string|max:3',
                    'hsn_code' => 'required|string|min:4|max:8',
                    'gst_vat_percentage' => 'required|integer|min:0|max:100',
                    'max_discount_percentage' => 'required|integer|min:0|max:100',
                    'warranty' => 'required|string|min:0|max:100',
                ]);
    
                if ($rowValidator->fails()) {
                    $errors[$rowIndex] = $rowValidator->errors()->toArray();
                    continue;
                }
    
                $warrantyId = WarrantyMaster::where('warranty_name', 'like', $row[6])->value('warranty_id') ?? 0;
    
                if ($warrantyId === 0) {
                    $errors[$rowIndex]['warranty'] = ['Invalid warranty name: ' . $row[6]];
                    continue;
                } 
    
                if ($request->type == 'product') {
                    if (Application::where('application_name', $name)->exists()) {
                        $errors[$rowIndex]['error'] = ['Exact same record already exists.'];
                        continue;
                    }
                    $success[] = [
                        'application_name' => $name,
                        'cat_abrv' => $row[2],
                        'hsn_code' => $row[3],
                        'tax_class_id' => (int)$row[4],
                        'cat_warranty' => $warrantyId,
                     //   'max_discount' => $row[5],
                        'login_id' => 0,
                        'parent_id1' => 0,
                        'cat_classification' => 0,
                        'application_added_by' => 0,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
    
                if ($request->type == 'service') {
                    if (ApplicationService::where('application_service_name', $name)->exists()) {
                        $errors[$rowIndex]['error'] = ['Exact same record already exists.'];
                        continue;
                    }
                    $success[] = [
                        'application_service_name' => $name,
                        'cat_abrv' => $row[2],
                        'hsn_code' => $row[3],
                        'tax_class_id' => (int)$row[4],
                     //   'max_discount' => $row[5],
                        'login_id' => 0,
                        'parent_id1' => 0,
                        'application_service_added_by' => 0,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
   

            if ($request->type == 'product' && count($success) > 0 && count($errors) == 0) {
                Application::insert($success);
            }
            if ($request->type == 'service' && count($success) > 0 && count($errors) == 0) {
                ApplicationService::insert($success);

  
            }
            
              if (count($success) > 0 && count($errors) == 0) {
                // Find customer by customer_code
    $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();

                 $currentStep = 7;
    $existingStep = $customer->current_step;
    $customer_id = $customer->id;

                      if ($currentStep != $existingStep && $currentStep > $existingStep) {
        $customer->update(['current_step' => $currentStep]);
    }
    if ($currentStep != $existingStep){
   createonboardingProgres($customer_id,7);
    }
       }

            return response()->json([
                'errors' => $errors,
                'success' => $success
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid file format or corrupted file.'], 500);
        }
    }
    
   }
 