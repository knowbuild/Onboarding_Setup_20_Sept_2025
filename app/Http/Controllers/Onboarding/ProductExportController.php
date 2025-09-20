<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ProductsExport;
use App\Exports\CategoryExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Exports\ProductExportTest;
 
class ProductExportController extends Controller
{
     public function category_export()
    {
        try {
            $fileName = 'category_' . time() . '.xlsx';
            $filePath = 'exports/' . $fileName;

            // Store the file in public storage
            Excel::store(new CategoryExport, $filePath, 'public');

            return response()->json([
                'status' => 'success',
                'message' => 'Export successful',
                'file_url' => Storage::url($filePath),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function product_list_export()
    {
        try {
            // Define filename & path
            $fileName = 'products_' . time() . '.xlsx';
            $filePath = 'exports/' . $fileName;

            // Store the exported Excel file in public storage
            Excel::store(new ProductsExport, $filePath, 'public');

            // Generate public URL for download
            $fileUrl = Storage::url($filePath);

            return response()->json([
                'status' => 'success',
                'message' => 'Export successful',
                'file_url' => url($fileUrl), // Full URL to the file
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function exportExcel()
    {
        try {
            // Define filename & path
            $fileName = 'products_' . time() . '.xlsx';
            $filePath = 'exports/' . $fileName;

            // Store the exported Excel file in public storage
            Excel::store(new ProductExportTest, $filePath, 'public');

            // Generate public URL for download
            $fileUrl = Storage::url($filePath);

            return response()->json([
                'status' => 'success',
                'message' => 'Export successful',
                'file_url' => url($fileUrl), // Full URL to the file
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

   
}
