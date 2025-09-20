<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Vendor;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VendorsExport;
use App\Models\VendorPaymentTermsMaster;
use App\Models\PriceBasis;
use Carbon\Carbon;
use App\Models\GstSaleTypeMaster;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CurrencyController extends Controller
{
    public function updateRates(Request $request)
    {
        // Call your helper function
        $result = updateCurrencyRatesInINR();

       return response()->json([
    'status'  => true,
    'message' => 'Currency rates updated successfully',
    'updated' => mb_convert_encoding($result, 'UTF-8', 'UTF-8')
]);    }
}
?>