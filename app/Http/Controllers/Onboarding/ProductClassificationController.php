<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ProductTypeClassMaster;
use App\Exports\ProductClassificationExport;
use App\Models\Customer;

class ProductClassificationController extends Controller
{
    public function export()
    {
        try {
            $fileName = 'product_classification_' . time() . '.xlsx';
            $filePath = "exports/product_classification/{$fileName}";

            Excel::store(new ProductClassificationExport, $filePath, 'public');

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
            'product_classification_excel' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $base64 = str_contains($request->product_classification_excel, 'base64,')
                ? explode('base64,', $request->product_classification_excel)[1]
                : $request->product_classification_excel;

            $tempFile = tempnam(sys_get_temp_dir(), 'product_classification_') . '.xlsx';
            file_put_contents($tempFile, base64_decode($base64));

            if (!file_exists($tempFile)) {
                return response()->json(['error' => 'Temporary file could not be created.'], 500);
            }

            $spreadsheet = IOFactory::load($tempFile);
            $worksheet = $spreadsheet->getActiveSheet();

            // Extract headers
            $fileHeaders = [];
     $expectedHeaders = ['S.No', 'Name', 'Show on PQV (Yes or No)'];

// Read only the first row and only first 3 columns (A1 to C1)
$headerRow = $worksheet->rangeToArray('A1:C1', null, true, true, false);

$fileHeaders = [];
if (!empty($headerRow)) {
    $fileHeaders = array_map('trim', $headerRow[0]);
}

if ($fileHeaders !== $expectedHeaders) {
    return response()->json([
        'success' => false,
        'errors' => 'Incorrect Excel format. Please use the correct template.',
        'expected_headers' => $expectedHeaders,
        'received_headers' => $fileHeaders,
    ], 422);
}


            $rows = $worksheet->toArray();
            array_shift($rows); // Remove header

            $errors = [];
            $success = [];
            $uniqueNames = [];

            foreach ($rows as $index => $row) {
                $rowIndex = $index + 2;
                $name = trim($row[1] ?? '');
                $showOnPQV = trim($row[2] ?? '');

                if (!$name) continue;

                if (in_array($name, $uniqueNames)) {
                    $errors[$rowIndex]['error'][] = "Duplicate name found: {$name}";
                    continue;
                }

                $uniqueNames[] = $name;

                $rowValidator = Validator::make([
                    'product_type_class_name' => $name,
                    'show_on_pqv' => $showOnPQV,
                ], [
                    'product_type_class_name' => 'required|string|max:100',
                    'show_on_pqv' => 'required|in:Yes,No',
                ]);

                if ($rowValidator->fails()) {
                    $errors[$rowIndex] = $rowValidator->errors()->toArray();
                    continue;
                }
     if (ProductTypeClassMaster::where('product_type_class_name', $name)->exists()) {
                        $errors[$rowIndex]['error'] = ['Exact same record already exists.'];
                        continue;
                    }
                    if($showOnPQV == 'Yes'){
                       $showOnPQVs =  'yes';
                    }
                    elseif($showOnPQV == 'No'){
                       $showOnPQVs =  'no';
                    }
                $success[] = [
                    'product_type_class_name' => $name,
                    'product_type_class_show' => $showOnPQVs,
                ];
            }

            if (count($success) && count($errors) === 0) {
                ProductTypeClassMaster::insert($success);

                                       // Find customer by customer_code
    $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();

    $currentStep = 6;
    $existingStep = $customer->current_step;
    $customer_id = $customer->id;

    if ($currentStep != $existingStep && $currentStep > $existingStep) {
        $customer->update(['current_step' => $currentStep]);
    }
    if ($currentStep != $existingStep){
   createonboardingProgres($customer_id,6);
    }
            }

            return response()->json([
                'success' => $success,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid or corrupted Excel file.',
            ], 500);
        }
    }
}
