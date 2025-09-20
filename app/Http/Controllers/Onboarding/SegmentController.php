<?php
namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\CustSegment;
use App\Exports\SegmentExport;
use App\Models\Customer;

class SegmentController extends Controller
{
    public function export()
    {
        try {
            $fileName = 'segment_' . time() . '.xlsx';
            $filePath = "exports/segment/{$fileName}";

            Excel::store(new SegmentExport, $filePath, 'public');

            return response()->json([
                'status' => 'success',
                'message' => 'Export successful',
                'file_url' => url(Storage::url($filePath)),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }
 
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|string|max:50',
            'segment_excel' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $base64 = $request->segment_excel;
            $base64 = str_contains($base64, 'base64,') ? explode('base64,', $base64)[1] : $base64;

            $fileContent = base64_decode($base64);
            $tempFile = tempnam(sys_get_temp_dir(), 'segment_') . '.xlsx';
            file_put_contents($tempFile, $fileContent);

            if (!file_exists($tempFile)) {
                return response()->json(['error' => 'Temporary file could not be created.'], 500);
            }

            $spreadsheet = IOFactory::load($tempFile);
            $worksheet = $spreadsheet->getActiveSheet();

            // Read header row
            $fileHeaders = [];
$expectedHeaders = ['S.No', 'Name', 'Targeted Interaction (only number)'];
$fileHeaders = [];

$firstRow = $worksheet->rangeToArray('A1:C1', null, true, true, true); // Read only A1 to C1
if (!empty($firstRow)) {
    foreach ($firstRow as $row) {
        foreach ($row as $cell) {
            $fileHeaders[] = trim($cell);
        }
    }
}

if ($fileHeaders !== $expectedHeaders) {
    return response()->json([
        'success' => false,
        'errors' => 'Incorrect Excel format. Please use the correct template.',
        'expected_headers' => $expectedHeaders,
        'received_headers' => $fileHeaders
    ], 422);
}


            $rows = $worksheet->toArray();
            array_shift($rows); // remove header row

            $errors = [];
            $success = [];
            $uniqueNames = [];

            foreach ($rows as $index => $row) {
                $name = trim($row[1] ?? '');
                $interactions = $row[2] ?? null;
                $rowIndex = $index + 2;

                if (!$name) continue;

                if (in_array($name, $uniqueNames)) {
                    $errors[$rowIndex]['error'][] = "Duplicate name found: {$name}";
                    continue;
                }

                $uniqueNames[] = $name;

                $rowValidator = Validator::make([
                    'cust_segment_name' => $name,
                    'interactions_reqd' => $interactions,
                ], [
                    'cust_segment_name' => 'required|string|max:100',
                    'interactions_reqd' => 'required|numeric',
                ]);

                if ($rowValidator->fails()) {
                    $errors[$rowIndex] = $rowValidator->errors()->toArray();
                    continue;
                }
     if (CustSegment::where('cust_segment_name', $name)->exists()) {
                        $errors[$rowIndex]['error'] = ['Exact same record already exists.'];
                        continue;
                    }
                $success[] = [
                    'cust_segment_name' => $name,
                    'interactions_reqd' => $interactions,
                ];
            }

            if (count($success) && count($errors) === 0) {
                CustSegment::insert($success);
            }

               // Find customer by customer_code
    $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();

                 $currentStep = 4;
    $existingStep = $customer->current_step;
    $customer_id = $customer->id;

    if ($currentStep != $existingStep && $currentStep > $existingStep) {
        $customer->update(['current_step' => $currentStep]);

      
    }
    if ($currentStep != $existingStep){
   createonboardingProgres($customer_id,4);
    }

            return response()->json([
                'success' => $success,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid or corrupted Excel file.'
            ], 500);
        }
    }
}
