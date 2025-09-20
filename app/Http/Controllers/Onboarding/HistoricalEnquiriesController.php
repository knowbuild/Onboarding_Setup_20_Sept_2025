<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\User;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\Product;
use App\Models\WebEnq;
use App\Models\WebEnqEdit;
use App\Models\Application;
use App\Models\CustSegment;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
 
use App\Exports\HistoricalEnquiry;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HistoricalEnquiriesController extends Controller
{
    public function historical_enquiries_export()
    {
        try {
            $fileName = 'historical_enq_' . time() . '.xlsx';
            $filePath = 'exports/historical_enquiries/' . $fileName;

            // Store the file in public storage
            Excel::store(new HistoricalEnquiry, $filePath, 'public');

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
    public function historical_enquiries_export1()
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Export successful',
            'file_url' => getWeb()->web_url."/public/excel-download/Historical-Enquiries.xlsx" 
        ]);
    } 

    public function importExcelHistoricalEnquiries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|string|max:50',
            'historical_enquiries_excel' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        try {
            // Extract and decode Base64 Excel file
            $base64String = $request->historical_enquiries_excel;
            if (str_contains($base64String, 'base64,')) {
                $base64String = explode('base64,', $base64String)[1];
            }
    
            $excelData = base64_decode($base64String);
            if (!$excelData) {
                return response()->json(['error' => 'Invalid base64 data.'], 400);
            }
    
            // Save as a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_') . '.xlsx';
            file_put_contents($tempFile, $excelData);
    
            if (!file_exists($tempFile)) {
                return response()->json(['error' => 'Failed to create a temporary file.'], 500);
            }
    
            // Load spreadsheet
            $spreadsheet = IOFactory::load($tempFile);
            $worksheet = $spreadsheet->getActiveSheet();


             // Extract headers from the first row
 $fileHeaders = [];
 foreach ($worksheet->getRowIterator(1, 1) as $row) {
     $cellIterator = $row->getCellIterator();
     $cellIterator->setIterateOnlyExistingCells(false); // Read all columns
     foreach ($cellIterator as $cell) {
         $fileHeaders[] = trim($cell->getValue()); // Get actual header values
     }
 }
 
 // Expected headers
 $expectedHeaders = [
   'S.No', 'Customer Name', 'Email', 'Mobile', 'Country', 'State', 'City', 'Segment', 'Message', 'Source of Enquiry', 'Product Category'
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
    
            // Remove header row
            array_shift($rows);
            $errors = [];
            $success = [];
    
          
            $uniqueRows = [];

            foreach ($rows as $index => $row) {
                if (empty($row[1])) {
                    continue; // Skip row if $row[1] is null or empty
                }
            
                $rowIndex = $index + 2;
                $name = trim($row[2] ?? '');
            
                // Create a unique key based on multiple columns
                $uniqueKey = implode('|', array_slice($row, 1, 10)); // Adjust slice as needed
            
                if (in_array($uniqueKey, $uniqueRows)) {
                    $errors[$rowIndex]['error'] = ['Duplicate row found in uploaded file: ' . $name];
                    continue;
                }
            
                $uniqueRows[] = $uniqueKey;
    
                $rowValidator = Validator::make([
                    'customer_name' => $row[1] ?? null,
                    'email' => $row[2] ?? null,
                    'mobile' => $row[3] ?? null,
                    'country' => $row[4] ?? null,
                    'state' => $row[5] ?? null,
                    'city' => $row[6] ?? null,
                    'segment' => $row[7] ?? null,
                    'message' => $row[8] ?? null,
                    'source_of_enquiry' => $row[9] ?? null,
                    'product_category' => $row[10] ?? null,
                ], [
                    'customer_name' => 'required|string|max:255',
                    'email' => 'required|email|max:255',
                    'mobile' => 'required|string|max:10',
                    'country' => 'required|string|max:100',
                    'state' => 'required|string|max:100',
                    'city' => 'required|string|max:100',
                    'segment' => 'nullable|string|max:255',
                    'message' => 'nullable|string|max:1000',
                    'source_of_enquiry' => 'nullable|string|max:255',
                    'product_category' => 'nullable|string|max:255',
                ]);
    
                if ($rowValidator->fails()) {
                    $errors[$rowIndex] = $rowValidator->errors()->toArray();
                    continue;
                }
    
                // Check for duplicate entry
                $exists = WebEnq::where([
                    'Cus_name' => $row[1],
                    'Cus_email' => $row[2],
                    'Cus_mob' => $row[3],
                    'Cus_msg' => $row[8],
                    'ref_source' => $row[9],
                ])->exists();
    
                if ($exists) {
                    $errors[$rowIndex]['error'] = ['Exact same record already exists.'];
                    continue;
                }
    
                // Fetch related IDs (set default to 0 if not found)
                $countryId = Country::where('country_name', 'like', $row[4])->value('country_id') ?? 0;
                $stateId = State::where('zone_name', 'like', $row[5])->value('zone_id') ?? 0;
                $cityId = City::where('city_name', 'like', $row[6])->value('city_id') ?? 0;
                $segmentId = CustSegment::where('cust_segment_name', 'like', $row[7])->value('cust_segment_id') ?? 0;
                $categoryId = Application::where('application_name', 'like', $row[10])->value('application_id') ?? 0;
    
                // Insert into WebEnq
                $webEnq = WebEnq::create([
                    'Cus_name' => $row[1],
                    'Cus_email' => $row[2],
                    'Cus_mob' => $row[3],
                    'Cus_msg' => $row[8],
                    'ref_source' => $row[9],
                    'product_category' => $categoryId,
                    'deleteflag' => 'active',
                ]);
    
                // Insert into WebEnqEdit
                WebEnqEdit::create([
                    'enq_id' => $webEnq->id,
                    'Cus_name' => $row[1],
                    'Cus_email' => $row[2],
                    'Cus_mob' => $row[3],
                    'Cus_msg' => $row[8],
                    'city' => $cityId,
                    'country' => $countryId,
                    'state' => $stateId,
                    'acc_manager' => 0,
                    'ref_source' => $row[9],
                    'cust_segment' => $segmentId,
                    'deleteflag' => 'active',
                    'product_category' => $categoryId,
                    'assigned_by' => 0,
                ]);
    
                $success[] = "Row $rowIndex imported successfully.";
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
