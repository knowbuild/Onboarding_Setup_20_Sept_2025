<?php
namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\WarrantyMaster;
use App\Exports\WarrantyExport;
use App\Models\Customer;

class WarrantyController extends Controller
{
    public function export()
    {
        try {
            $fileName = 'warranty_' . time() . '.xlsx';
            $filePath = "exports/warranty/{$fileName}";

            Excel::store(new WarrantyExport, $filePath, 'public');

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
            'warranty_excel' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $base64String = $request->warranty_excel;
            if (str_contains($base64String, 'base64,')) {
                $base64String = explode('base64,', $base64String)[1];
            }

            $decodedData = base64_decode($base64String);
            $tempFilePath = tempnam(sys_get_temp_dir(), 'warranty_') . '.xlsx';
            file_put_contents($tempFilePath, $decodedData);

            if (!file_exists($tempFilePath)) {
                return response()->json(['error' => 'Temporary file could not be created.'], 500);
            }

            $spreadsheet = IOFactory::load($tempFilePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Extract headers
            $fileHeaders = [];
$expectedHeaders = ['S.No', 'Warranty Duration (only number)', 'Year/Month'];
$fileHeaders = [];

// Read only the first row, columns A to C (i.e., 3 expected headers)
$firstRow = $worksheet->rangeToArray('A1:C1', null, true, true, false);

if (!empty($firstRow)) {
    $fileHeaders = array_map('trim', $firstRow[0]); // Clean up spacing
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
            array_shift($rows); // Remove header

            $errors = [];
            $success = [];
            $uniqueDurations = [];

            foreach ($rows as $index => $row) {
                $rowIndex = $index + 2;
                $durationValue = trim($row[1] ?? '');
                $durationType = strtolower(trim($row[2] ?? ''));

                $combinedKey = $durationValue . '_' . $durationType;
                if (!$durationValue || !$durationType) continue;

                if (in_array($combinedKey, $uniqueDurations)) {
                    $errors[$rowIndex]['error'][] = "Duplicate warranty entry: {$durationValue} {$durationType}";
                    continue;
                }

                $uniqueDurations[] = $combinedKey;

                $rowValidator = Validator::make([
                    'warranty_duration' => $durationValue,
                    'duration_type' => $durationType,
                ], [
                    'warranty_duration' => 'required|numeric',
                    'duration_type' => 'required|in:year,month',
                ]);

                if ($rowValidator->fails()) {
                    $errors[$rowIndex] = $rowValidator->errors()->toArray();
                    continue;
                }

                $years = $durationType === 'year' ? $durationValue : 0;
                $months = $durationType === 'month' ? $durationValue : 0;

                   if (WarrantyMaster::where('year', $years)->where('month', $months)->exists()) {
                        $errors[$rowIndex]['error'] = ['Exact same record already exists.'];
                        continue;
                    }

                $success[] = [
                    'year' => $years,
                    'month' => $months,
                    'warranty_name' => "{$durationValue} {ucfirst($durationType)}",
                ];
            }

            if (count($success) > 0 && count($errors) === 0) {
                WarrantyMaster::insert($success);

                                       // Find customer by customer_code
    $customer = Customer::where('customer_code', $request->customer_code)->firstOrFail();

                 $currentStep = 5;
    $existingStep = $customer->current_step;
    $customer_id = $customer->id;

    if ($currentStep != $existingStep && $currentStep > $existingStep) {
        $customer->update(['current_step' => $currentStep]);
    }
    if ($currentStep != $existingStep){
   createonboardingProgres($customer_id,5);
    }
            }

            return response()->json([
                'success' => $success,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid or corrupted Excel file.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
