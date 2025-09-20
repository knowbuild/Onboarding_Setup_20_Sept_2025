<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\{User, Customer, Company, Designation, DepartmentComp, CompanyExtn, Country, State, City};

use App\Exports\CustomerDirectory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerDirectoryController extends Controller
{
    public function customer_directory_export()
    {
        try {
            $fileName = 'customer_dir_' . time() . '.xlsx';
            $filePath = 'exports/customer-directory/' . $fileName;

            // Store the file in public storage
            Excel::store(new CustomerDirectory, $filePath, 'public');

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
    public function customer_directory_export1()
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Export successful',
            'file_url' => getWeb()->web_url . "/public/excel-download/Customer-Directory.xlsx"
        ]);
    }

    public function importExcelCustomerDirectory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|string|max:50',
            'customer_directory_excel' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
      

        try {
            $base64String = explode('base64,', $request->customer_directory_excel)[1] ?? $request->customer_directory_excel;
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
        if ($colCount >= 18) break; // Stop after reading 18 columns
        $fileHeaders[] = trim($cell->getValue());
        $colCount++;
    }
}
 
 // Expected headers
 $expectedHeaders = [
    'S.No', 'Company Name', 'Company Type', 'Office Type', 'Customer Industry', 'Mobile',
    'GST / VAT Number', 'Company Website', 'Address', 'Country', 'State', 'City',
    'Zip/Postal Code', 'Person Salutation', 'Person Name', 'Designation', 'Department', 'Email'
 ];

 // Check if headers match
 if ($fileHeaders !== $expectedHeaders) {
     return response()->json([
         'success' => false,
         'errors' => 'Incorrect Excel file format. Please select the correct file and try again.',
        // 'expected_headers' => $expectedHeaders,
       //  'received_headers' => $fileHeaders
     ], 501);
 }


            $rows = $worksheet->toArray();
            array_shift($rows);
       
            $errors = [];
            $success = [];
      
            
            $uniqueRows = [];

            foreach ($rows as $index => $row) {
                if (empty($row[1])) {
                    continue; // Skip row if $row[1] is null or empty
                }
            
                $rowIndex = $index + 2;
                $name = trim($row[1] ?? '');
            
                // Create a unique key based on multiple columns
                $uniqueKey = implode('|', array_slice($row, 1, 17)); // Adjust slice as needed
            
                if (in_array($uniqueKey, $uniqueRows)) {
                    $errors[$rowIndex]['error'] = ['Duplicate row found in uploaded file: ' . $name];
                    continue;
                }
            
                $uniqueRows[] = $uniqueKey;

                $rowValidator = Validator::make([
                    'company_name' => $row[1] ?? null,
                    'company_type' => $row[2] ?? null,
                    'office_type' => $row[3] ?? null,
                    'customer_industry' => $row[4] ?? null,
                    'mobile' => $row[5] ?? null,
                    'gst_number' => $row[6] ?? null,
                    'company_website' => $row[7] ?? null,
                    'address' => $row[8] ?? null,
                    'country' => $row[9] ?? null,
                    'state' => $row[10] ?? null,
                    'city' => $row[11] ?? null,
                    'zip_code' => $row[12] ?? null,
                    'person_salutation' => $row[13] ?? null,
                    'person_name' => $row[14] ?? null,
                    'designation' => $row[15] ?? null,
                    'department' => $row[16] ?? null,
                    'email' => $row[17] ?? null,
                ], [
                    'company_name' => 'required|string|min:3|max:100',
                    'company_type' => 'required|string|max:100',
                    'office_type' => 'required|string|max:100',
                    'customer_industry' => 'required|string|max:100',
                    'mobile' => 'required|string|max:100',
                    'gst_number' => 'required|string|max:100',
                    'company_website' => 'required|string|max:100',
                    'address' => 'required|string|max:200',
                    'country' => 'required|string|max:100',
                    'state' => 'required|string|max:100',
                    'city' => 'required|string|max:100',
                    'zip_code' => 'required|string|max:100',
                    'person_salutation' => 'required|string|max:100',
                    'person_name' => 'required|string|min:3|max:100',
                    'designation' => 'required|string|max:100',
                    'department' => 'required|string|max:100',
                    'email' => 'required|email|string|max:100',
                ]);

                if ($rowValidator->fails()) {
                    $errors[$rowIndex] = $rowValidator->errors()->toArray();
                    continue;
                }
             
                $ids = [
                    'co_extn_id' => CompanyExtn::where('company_extn_name', 'like', $row[2])->value('company_extn_id') ?? 0,
                    'designation_id' => Designation::where('designation_name', 'like', $row[15])->value('designation_id') ?? 0,
                    'department_id' => DepartmentComp::where('department_name', 'like', $row[16])->value('department_id') ?? 0,
                    'country' => Country::where('country_name', 'like', $row[9])->value('country_id') ?? 0,
                    'state' => State::where('zone_name', 'like', $row[10])->value('zone_id') ?? 0,
                    'city' => City::where('city_name', 'like', $row[11])->value('city_id') ?? 0,
                ];
                if ($ids['co_extn_id'] === 0) {
                    $errors[$rowIndex]['company_type'] = ['Invalid company_type name: ' . $row[2]];
                    continue;
                } 
                if ($ids['designation_id'] === 0) {
                    $errors[$rowIndex]['designation'] = ['Invalid designation name: ' . $row[15]];
                    continue;
                } 
                if ($ids['department_id'] === 0) {
                    $errors[$rowIndex]['department'] = ['Invalid department name: ' . $row[16]];
                    continue;
                } 
                if ($ids['country'] === 0) {
                    $errors[$rowIndex]['country'] = ['Invalid country name: ' . $row[9]];
                    continue;
                } 
                if ($ids['state'] === 0) {
                    $errors[$rowIndex]['state'] = ['Invalid state name: ' . $row[10]];
                    continue;
                } 
                if ($ids['city'] === 0) {
                    $errors[$rowIndex]['city'] = ['Invalid city name: ' . $row[11]];
                    continue;
                } 
                if (Company::where([
                    'salutation' => $row[13],
                    'fname' => $row[14],
                    'comp_name' => $row[1],
                    'co_extn_id' => $ids['co_extn_id'],
                    'office_type' => $row[3],
                    'comp_website' =>str_replace('=>//', '://', $row[7]),
                    'designation_id' => $ids['designation_id'],
                    'department_id' => $ids['department_id'],
                    'telephone' => $row[5],
                    'address' => $row[8],
                    'city' => $ids['city'],
                    'country' => $ids['country'],
                    'state' => $ids['state'],
                    'zip' => $row[12],
                    'cust_segment' => $row[4],
                    'gst_no' => $row[6],
                    'email' => $row[17],
                ])->exists()) {
                    $errors[$rowIndex]['error'] = ['Exact same record already exists.'];
                    continue;
                }

                $success[] = array_merge([
                    'parent_id' => 0,
                    'lname' => 0,
                    'co_extn' => 0,
                    'comp_revenue' => 0,
                    'no_of_emp' => 0,
                    
                  
                    'fax_no' => 0,
                    'description' => 0,
                    'ref_source' => 0,
                    'acc_manager' => 0,
                    'india_mart_co' => 0,
                    'quality_check' => 0,
                    'co_division' => 0,
                    'co_city' => 0,
                    'key_customer' => 0,
                ], $ids, [
                    'salutation' => $row[13],
                    'fname' => $row[14],
                    'comp_name' => $row[1],
                    'office_type' => $row[3],
                    'comp_website' => str_replace('=>//', '://', $row[7]),
                    'telephone' => $row[5],
                    'address' => $row[8],
                    'zip' => $row[12],
                    'cust_segment' => $row[4],
                    'gst_no' => $row[6],
                    'mobile_no' =>$row[5],
                    'email' => $row[17],
                ]);
            }
   
            if (!empty($success) && count($errors) == 0) {
                Company::insert($success);

                               // Find customer by customer_code
    $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();

                 $currentStep = 9;
    $existingStep = $customer->current_step;
    $customer_id = $customer->id;

                      if ($currentStep != $existingStep && $currentStep > $existingStep) {
        $customer->update(['current_step' => $currentStep]);
    }
    if ($currentStep != $existingStep){
   createonboardingProgres($customer_id,9);
    }
            }
 
            return response()->json(['errors' => $errors, 'success' => $success], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid file format or corrupted file.'], 500);
        }
    }
}
