<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use App\Models\Warranty;
use App\Models\ProductService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Exports\CategoryExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
 
class CategoryController extends Controller
{
    public function index(Request $request)
    {
           $validator = Validator::make($request->all(), [
            'type'       => 'required|in:product,service',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
     $categories = Category::with('warranty:id,name')
    ->select('id', 'name', 'hsn_sac_code', 'tax_percentage', 'warranty_id', 'type', 'status')
    ->where('type', $request->type)
    ->orderByDesc('id')
    ->get()
    ->map(function ($category) {
        return [
            'id'              => $category->id,
            'name'            => $category->name,
            'hsn_sac_code'    => $category->hsn_sac_code,
            'tax_percentage'  => $category->tax_percentage,
            'warranty_id'     => $category->warranty_id,
            'warranty_name'   => $category->warranty->name ?? null,
            'type'            => $category->type,
            'status'          => $category->status,
        ];
    });


        return response()->json([
            'status' => 'success',
            'message' => 'Categories listed successfully.',
            'data' => $categories
        ], 200);
    } 

    public function edit(Request $request)
    {
        $category = Category::select('id', 'name', 'hsn_sac_code', 'tax_percentage', 'warranty_id', 'type', 'status')->find($request->id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Category details retrieved successfully.',
            'data' => $category
        ], 200);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'             => 'nullable|exists:categories,id',
            'name'           => 'required|string|max:100',
            'hsn_sac_code'   => 'nullable|string|max:100',
            'tax_percentage' => 'required|numeric|min:0',
            'warranty_id'    => 'nullable|exists:warranties,id',
            'type'           => 'required|in:product,service',
            'status'         => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        $category = Category::updateOrCreate(
            ['id' => $request->id],
            [
                'name'           => $request->name,
                'hsn_sac_code'   => $request->hsn_sac_code,
                'tax_percentage' => $request->tax_percentage,
                'warranty_id'    => $request->warranty_id,
                'type'           => $request->type,
                'status'         => $request->status ?? 'active',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Category updated successfully.' : 'Category created successfully.'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $category = Category::find($request->id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found.'
            ], 404);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:categories,id',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $category = Category::find($request->id);
        $category->status = $request->status;
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Category status updated successfully.'
        ], 200);
    }
public function category_export(Request $request)
{
    $validator = Validator::make($request->all(), [
        'type' => 'required|in:product,service',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $type = $request->type;

    try {
        $fileName = $type . '_category_' . time() . '.xlsx';
        $filePath = 'exports/category/' . $fileName;

        // Pass type to export class
        $export = new CategoryExport($type);

        // Store the file in public disk
        Excel::store($export, $filePath, 'public');

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
        'category_excel' => 'required|string',
        'type' => 'required|string|in:product,service',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    try {
        $type = $request->type;
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

        // Determine expected headers
        $codeType = $type === 'product' ? 'HSN' : 'SAC';
        $typeLabel = ucfirst($type);
        $expectedHeaders = [
            'S.No',
            "$typeLabel Category Name",
            "$codeType Code (Fill in 8 digit code, else 4 digits)",
            'GST / VAT % (If GST applicable is 18%, fill in value as 18)',
            "$typeLabel Warranty (Choose warranty through drop down)"
        ];

        // Extract headers
        $fileHeaders = [];
        foreach ($worksheet->getRowIterator(1, 1) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $fileHeaders[] = trim($cell->getValue());
            }
        }

        // Validate header
        if ($fileHeaders !== $expectedHeaders) {
            return response()->json([
                'success' => false,
                'errors' => 'Incorrect Excel file format. Please upload a valid template.',
            ], 422);
        }

        // Read all rows and skip the header
        $rows = $worksheet->toArray();
        array_shift($rows);

        $success = [];
        $errors = [];
        $uniqueNames = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Offset for header

            $name = trim($row[1] ?? '');
            $hsnCode = $row[2] ?? '';
            $taxPercentage = $row[3] ?? '';
            $warrantyName = trim($row[4] ?? '');

            // Skip empty name rows
            if (empty($name)) {
                continue;
            }

            // Check for duplicates in current upload
            if (in_array($name, $uniqueNames)) {
                $errors[$rowNumber]['duplicate'] = ["Duplicate name found: $name"];
                continue;
            }
            $uniqueNames[] = $name;

            // Validate individual row
            $rowValidator = Validator::make([
                'name' => $name,
                'hsn_sac_code' => $hsnCode,
                'tax_percentage' => $taxPercentage,
                'warranty_name' => $warrantyName,
            ], [
                'name' => 'required|string|max:100',
                'hsn_sac_code' => 'required|string|max:100',
                'tax_percentage' => 'required|numeric|min:0',
                'warranty_name' => 'required|string|max:100',
            ]);

            if ($rowValidator->fails()) {
                $errors[$rowNumber] = $rowValidator->errors()->toArray();
                continue;
            }

            // Validate warranty
            $warrantyId = Warranty::where('type', $type)->where('name', $warrantyName)->value('id');
            if (!$warrantyId) {
                $errors[$rowNumber]['warranty'] = ["Invalid warranty name: $warrantyName"];
                continue;
            }

            // Check for duplicates in DB
            $alreadyExists = Category::where('name', $name)->where('type', $type)->exists();
            if ($alreadyExists) {
                $errors[$rowNumber]['exists'] = ['Category already exists with the same name and type.'];
                continue;
            }

            // Valid row
            $success[] = [
                'name' => $name,
                'hsn_sac_code' => $hsnCode,
                'tax_percentage' => $taxPercentage,
                'warranty_id' => $warrantyId,
                'type' => $type,
                'status' => $request->status ?? 'active',
            ];
        }

        // Bulk insert if no errors
        if (count($success) > 0 && empty($errors)) {
            Category::insert($success);
        }

        return response()->json([
            'success' => $success,
            'errors' => $errors,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Invalid file format or corrupted Excel file.',
            'exception' => $e->getMessage()
        ], 500);
    }
}


public function updateCategory(Request $request)
{
    try {
        // Validate input
        $validator = Validator::make($request->all(), [
            'category_id'            => 'required|exists:tbl_application,application_id',
            'products'               => 'required|array|min:1',
            'products.*.product_id'  => 'required|exists:tbl_products,pro_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Prepare product IDs
        $productIds = collect($request->products)->pluck('product_id')->toArray();

        // Bulk update all products in one query
        ProductMain::whereIn('pro_id', $productIds)
            ->update(['cate_id' => $request->category_id]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Products updated successfully.',
            'updated_count' => count($productIds)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}


}
