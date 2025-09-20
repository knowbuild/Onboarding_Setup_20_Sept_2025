<?php
namespace App\Http\Controllers\Accounts;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\{
    TaxInvoice
};
use Carbon\Carbon;

class VendorPaymentsFinanceController extends Controller
{  



public function paymentsPaidThisYear(Request $request)
{
    $accManager    = $request->input('acc_manager', 0);
    $financialYear = $request->input('financial_year', null); // e.g. "2023-2024"
    $companyName   = $request->input('company_name', null);
    $vendorId      = $request->input('vendor_id', null);
    $month         = $request->input('month', null);

    // If no financial year provided, use current FY
    if (empty($financialYear)) {
        $today = Carbon::now();

        $fyStart = ($today->month >= 4)
            ? Carbon::create($today->year, 4, 1)->startOfDay()
            : Carbon::create($today->year - 1, 4, 1)->startOfDay();

        $fyEnd = $fyStart->copy()->addYear()->subDay()->endOfDay();

        $financialYear = $fyStart->year . '-' . $fyEnd->year;
    } else {
        // Parse provided financial year "YYYY-YYYY"
        [$startYear, $endYear] = explode('-', $financialYear);

        $fyStart = Carbon::create((int)$startYear, 4, 1)->startOfDay();
        $fyEnd   = Carbon::create((int)$endYear, 3, 31)->endOfDay();
    }

    // Use the enhanced helper function with currency support
    $totalPayable = pending_account_payables_paid_monthly(
        $accManager,
        $fyStart->toDateString(),
        $fyEnd->toDateString(),
        $vendorId ?: $companyName,
        $month
    );

    // Get detailed currency breakdown if needed
    $detailedData = pending_account_payables_paid_monthly_detailed(
        $accManager,
        $fyStart->toDateString(),
        $fyEnd->toDateString(),
        $vendorId ?: $companyName,
        $month
    );

    return response()->json([
        'total_paid'      => $totalPayable,
        'total_formatted' => currencySymbolDefault(1) . ' ' . number_format($totalPayable, 2),
        'financial_year'  => $financialYear,
        'start_date'      => $fyStart->toDateString(),
        'end_date'        => $fyEnd->toDateString(),
        'currency_details' => [
            'total_currencies' => $detailedData['currency_count'],
            'currency_breakdown' => $detailedData['currency_breakdown']
        ]
    ]);
}





public function pendingAccountPayables(Request $request)
{
    $accManager    = $request->input('acc_manager', 0);
    $vendorId      = $request->input('vendor_id', null);
    $month         = $request->input('month', null);
    $financialYear = $request->input('financial_year', null);

    // Normalize month parameter - remove leading zeros and validate
    if (!empty($month)) {
        $month = (int) $month;
        if ($month < 1 || $month > 12) {
            $month = null; // Invalid month, ignore filter
        }
    }

    // Derive FY start and end from financial_year param
    if (!empty($financialYear) && preg_match('/^\d{4}-\d{4}$/', $financialYear)) {
        [$fyStartYear, $fyEndYear] = explode('-', $financialYear);
        $fyStart = Carbon::create($fyStartYear, 4, 1)->startOfDay();
        $fyEnd   = Carbon::create($fyEndYear, 3, 31)->endOfDay();
    } else {
        // Default: current FY
        $today = Carbon::now();
        $fyStart = ($today->month >= 4)
            ? Carbon::create($today->year, 4, 1)->startOfDay()
            : Carbon::create($today->year - 1, 4, 1)->startOfDay();
        $fyEnd = $fyStart->copy()->addYear()->subDay()->endOfDay();
        $financialYear = $fyStart->format('Y') . '-' . $fyEnd->format('Y');
    }

    $startDate = $fyStart->toDateString();
    $endDate   = $fyEnd->toDateString();

    // Use the helper function with currency support
    $totalPending = pending_account_payables_monthly(
        $accManager,
        $startDate,
        $endDate,
        $vendorId,
        $month
    );

    // Get detailed currency breakdown
    $detailedData = pending_account_payables_monthly_detailed(
        $accManager,
        $startDate,
        $endDate,
        $vendorId,
        $month
    );

    return response()->json([
        'total_pending'   => $totalPending,
        'total_formatted' => currencySymbolDefault(1) . ' ' . number_format($totalPending, 2),
        'financial_year'  => $financialYear,
        'start_date'      => $startDate,
        'end_date'        => $endDate,
        'currency_details' => [
            'total_currencies' => $detailedData['currency_count'],
            'currency_breakdown' => $detailedData['currency_breakdown']
        ]
    ]);
}




public function pendingAccountPayablesByAging(Request $request)
{
    $accManager    = $request->input('acc_manager', 0);
    $agingMin      = $request->input('aging_min', 0);
    $agingMax      = $request->input('aging_max', null);
    $companyName   = $request->input('company_name', null);
    $vendorId      = $request->input('vendor_id', null);
    $financialYear = $request->input('financial_year', null); // e.g., "2024-2025"

    // If no FY given → use current FY
    if (empty($financialYear)) {
        $today = Carbon::now();
        $fyStart = ($today->month >= 4)
            ? Carbon::create($today->year, 4, 1)->startOfDay()
            : Carbon::create($today->year - 1, 4, 1)->startOfDay();

        $fyEnd = $fyStart->copy()->addYear()->subDay()->endOfDay();
    } else {
        // Parse FY string like "2024-2025"
        [$startYear, $endYear] = explode('-', $financialYear);

        $fyStart = Carbon::createFromDate((int)$startYear, 4, 1)->startOfDay();
        $fyEnd   = Carbon::createFromDate((int)$endYear, 3, 31)->endOfDay();
    }

    $startDate = $fyStart->toDateString();
    $endDate   = $fyEnd->toDateString();

    $query = DB::table('vendor_po_final as vpf')
        ->join('vendor_po_invoice_new as vpi', 'vpf.PO_ID', '=', 'vpi.po_id')
        ->leftJoin('tbl_currencies as c', 'vpf.Flag', '=', 'c.currency_code')
        ->selectRaw('
            DATEDIFF(CURDATE(), vpi.due_on) as aging, 
            vpi.vendor_id, 
            SUM(DISTINCT(vpi.value * COALESCE(c.currency_value, 1))) as total_payable,
            c.currency_code,
            c.currency_css_symbol,
            vpf.Flag
        ')
        ->where('vpi.status', '1');

    // Aging filter
    if (!empty($agingMax)) {
        $query->whereRaw("DATEDIFF(CURDATE(), vpi.due_on) BETWEEN ? AND ?", [$agingMin, $agingMax]);
    }

    // Company/Vendor filter
    $vendorFilter = $vendorId ?: $companyName;
    if (!empty($vendorFilter)) {
        $query->where('vpi.vendor_id', $vendorFilter);
    }

    // Always apply FY filter
    $query->whereBetween('vpi.due_on', [$startDate, $endDate]);

    $row = $query->orderByDesc('aging')->first();
    $totalPayable = $row ? $row->total_payable : 0;

    // Get currency information from the result
    $currencyInfo = null;
    if ($row) {
        $currencyInfo = [
            'currency_code' => $row->currency_code ?? $row->Flag ?? 'INR',
            'currency_symbol' => $row->currency_css_symbol ?? ($row->Flag == 'INR' ? '₹' : $row->Flag),
            'flag' => $row->Flag,
            'aging_days' => $row->aging ?? 0
        ];
    }

    return response()->json([
        'total_payable'   => (float) $totalPayable,
        'total_formatted' => currencySymbolDefault(1) . ' ' . number_format($totalPayable, 2),
        'aging_min'       => $agingMin,
        'aging_max'       => $agingMax,
        'vendor_id'       => $vendorId,
        'company_name'    => $companyName,
        'vendor_filter'   => $vendorFilter ?? null,
        'financial_year'  => $financialYear ?? ($fyStart->year . '-' . $fyEnd->year),
        'fy_start'        => $startDate,
        'fy_end'          => $endDate,
        'currency_info'   => $currencyInfo
    ]);
}

public function accountsPayableAgingDashboard(Request $request)
{
    $accManager    = $request->input('acc_manager', 0);
    $companyName   = $request->input('company_name', null);
    $vendorId      = $request->input('vendor_id', null);
    $financialYear = $request->input('financial_year', null);

    // Get default currency information
    $defaultCurrency = DB::table('tbl_currencies')
        ->select('currency_html_code', 'currency_css_symbol', 'currency_code')
        ->where('currency_super_default', 'yes')
        ->where('deleteflag', 'active')
        ->first();

    $defaultCurrencyHtmlCode = $defaultCurrency->currency_html_code ?? '&#8377;';
    $defaultCurrencySymbol = $defaultCurrency->currency_css_symbol ?? '₹';
    $defaultCurrencyCode = $defaultCurrency->currency_code ?? 'INR';

    // Base query for all outstanding invoices
    $baseQuery = DB::table('vendor_po_final as vpf')
        ->join('vendor_po_invoice_new as vpi', 'vpf.PO_ID', '=', 'vpi.po_id')
        ->leftJoin('tbl_currencies as c', 'vpf.Flag', '=', 'c.currency_code')
        ->where('vpi.status', '1'); // Only pending/outstanding invoices

    // Apply vendor filter if provided (using same logic as original)
    $vendorFilter = $vendorId ?: $companyName;
    if (!empty($vendorFilter)) {
        $baseQuery->where('vpi.vendor_id', $vendorFilter);
    }

    // Apply financial year filter - calculate current FY if not provided
    if (!empty($financialYear)) {
        // Parse financial year format like "2025-2026"
        if (preg_match('/^(\d{4})-(\d{4})$/', $financialYear, $matches)) {
            $startYear = $matches[1];
            $endYear = $matches[2];
            
            // Financial year starts April 1st and ends March 31st
            $fyStart = $startYear . '-04-01';
            $fyEnd = $endYear . '-03-31';
            
            // Filter by invoice due date falling within the financial year
            $baseQuery->whereBetween('vpi.due_on', [$fyStart, $fyEnd]);
        }
    } else {
        // Calculate current financial year automatically
        $today = Carbon::now();
        
        // If current month is April or later, FY starts this year
        // If current month is before April, FY started last year
        if ($today->month >= 4) {
            $fyStartYear = $today->year;
            $fyEndYear = $today->year + 1;
        } else {
            $fyStartYear = $today->year - 1;
            $fyEndYear = $today->year;
        }
        
        $fyStart = $fyStartYear . '-04-01';
        $fyEnd = $fyEndYear . '-03-31';
        $financialYear = $fyStartYear . '-' . $fyEndYear;
        
        // Filter by invoice due date falling within the current financial year
        $baseQuery->whereBetween('vpi.due_on', [$fyStart, $fyEnd]);
    }

    // Define aging buckets based on original PHP logic - cumulative ranges from current date
    // Note: These are cumulative, not exclusive ranges
    $agingBuckets = [
        'next_7_days' => [
            'label' => 'Next 7 days',
            'aging_min' => -7,  // Due 7 days from now
            'aging_max' => 0,   // Due today
            'icon_class' => 'calendar-check',
            'color_class' => 'text-success',
            'iconBgColor' => '#E0F2F1', // Light teal background
            'iconText' => '7D',
            'iconTextColor' => '#00796B', // Teal text
            'iconOutlineColor' => '#00796B', // Teal outline
            'daysText' => 'Next 7 days',
            'backgroundColor' => '#fff'
        ],
        'next_15_days' => [
            'label' => 'Next 15 days',
            'aging_min' => -15, // Due 15 days from now
            'aging_max' => 0,   // Due today (cumulative)
            'icon_class' => 'calendar-clock',
            'color_class' => 'text-info',
            'iconBgColor' => '#FFF3E0', // Light orange background
            'iconText' => '15D',
            'iconTextColor' => '#F57C00', // Orange text
            'iconOutlineColor' => '#F57C00', // Orange outline
            'daysText' => 'Next 15 days',
            'backgroundColor' => '#fff'
        ],
        'next_1_month' => [
            'label' => 'Next 1 month',
            'aging_min' => -30, // Due 30 days from now
            'aging_max' => 0,   // Due today (cumulative)
            'icon_class' => 'calendar',
            'color_class' => 'text-primary',
            'iconBgColor' => '#E3F2FD', // Light blue background
            'iconText' => '1M',
            'iconTextColor' => '#1976D2', // Blue text
            'iconOutlineColor' => '#1976D2', // Blue outline
            'daysText' => 'Next 1 month',
            'backgroundColor' => '#fff'
        ],
        'more_than_1_month' => [
            'label' => '> 1 month',
            'aging_min' => -1365, // Due up to 1365 days from now (about 3.7 years)
            'aging_max' => -30,    // Due more than 30 days from now
            'icon_class' => 'calendar-plus',
            'color_class' => 'text-warning',
            'iconBgColor' => '#E8F5E8', // Light green background
            'iconText' => '2M',
            'iconTextColor' => '#388E3C', // Green text
            'iconOutlineColor' => '#388E3C', // Green outline
            'daysText' => '> 1 month',
            'backgroundColor' => '#fff'
        ],
      /*  'not_yet_due' => [
            'label' => 'Not yet due',
            'aging_min' => -1365, // Due up to 1365 days from now (about 3.7 years)
            'aging_max' => 0,      // Due today (includes all future payments)
            'icon_class' => 'calendar-check',
            'color_class' => 'text-success',
            'iconBgColor' => '#E8F5E8',
            'iconText' => 'NYD',
            'iconTextColor' => '#28a745',
            'iconOutlineColor' => '#28a745',
            'daysText' => 'Not yet due',
            'backgroundColor' => '#f8fff8'
        ],*/
        'overdue' => [
            'label' => 'Overdue',
            'aging_min' => 1,      // Due 1 day ago or more
            'aging_max' => 999999, // Very large positive number for all overdue
            'icon_class' => 'calendar-x',
            'color_class' => 'text-danger',
            'iconBgColor' => '#FAEBE8', // Figma color for icon background
            'iconText' => '3M',
            'iconTextColor' => '#D32F2F', // Red text
            'iconOutlineColor' => '#D32F2F', // Red outline
            'daysText' => 'Overdue',
            'backgroundColor' => '#F0B9B9' // Figma color for card background
        ]
    ];

    $agingData = [];
    $totalAmount = 0;

    foreach ($agingBuckets as $key => $bucket) {
        $query = clone $baseQuery;
        
        // Apply aging filter using same logic as original PHP function
        $query->whereRaw("DATEDIFF(CURDATE(), vpi.due_on) BETWEEN ? AND ?", 
            [$bucket['aging_min'], $bucket['aging_max']]);
        
        // Use same SUM calculation as original (with DISTINCT and currency_value)
        $amount = $query->sum(DB::raw('DISTINCT(vpi.value * COALESCE(c.currency_value, 1))'));
        $amount = (float) $amount;
        $totalAmount += $amount;

        $agingData[] = [
            'key' => $key,
            'label' => $bucket['label'],
            'amount' => $amount,
            'formatted_amount' => $defaultCurrencySymbol . number_format($amount, 2),
            'currency_code' => $defaultCurrencyCode,
            'currency_html_code' => $defaultCurrencyHtmlCode,
            'icon_class' => $bucket['icon_class'],
            'color_class' => $bucket['color_class'],
            'aging_min' => $bucket['aging_min'],
            'aging_max' => $bucket['aging_max'],
            'iconBgColor' => $bucket['iconBgColor'],
            'iconText' => $bucket['iconText'],
            'iconTextColor' => $bucket['iconTextColor'],
            'iconOutlineColor' => $bucket['iconOutlineColor'],
            'daysText' => $bucket['daysText'],
            'backgroundColor' => $bucket['backgroundColor']
        ];
    }

    // Calculate percentages
    foreach ($agingData as $index => &$data) {
        $data['percentage'] = $totalAmount > 0 ? round(($data['amount'] / $totalAmount) * 100, 1) : 0;
    }

    // Get financial year for display (already calculated above if blank)
    if (empty($financialYear)) {
        $today = Carbon::now();
        
        if ($today->month >= 4) {
            $fyStartYear = $today->year;
            $fyEndYear = $today->year + 1;
        } else {
            $fyStartYear = $today->year - 1;
            $fyEndYear = $today->year;
        }
        
        $financialYear = $fyStartYear . '-' . $fyEndYear;
    }

    return response()->json([
        'financial_year' => $financialYear,
        'vendor_id' => $vendorId,
        'company_name' => $companyName,
        'vendor_filter' => $vendorFilter ?? null,
        'total_amount' => $totalAmount,
        'total_formatted' => $defaultCurrencySymbol . number_format($totalAmount, 2),
        'currency_info' => [
            'currency_code' => $defaultCurrencyCode,
            'currency_html_code' => $defaultCurrencyHtmlCode,
            'currency_symbol' => $defaultCurrencySymbol
        ],
        'aging_buckets' => $agingData,
        'debug_info' => [
            'note' => 'Using DATEDIFF(CURDATE(), vpi.due_on) logic where negative = future, positive = overdue'
        ],
        'generated_at' => Carbon::now()->toISOString()
    ]);
}




public function pendingAccountPayablesHeatmap(Request $request)
{
    $accManager     = $request->input('acc_manager', 0);
    $financialYear  = $request->input('financial_year', null);
    $vendorId       = $request->input('vendor_id', null);
    $month          = $request->input('month', null);

    // 🔹 Calculate FY start & end from input param
    if ($financialYear) {
        // Example: "2024-2025" → FY starts 2024-04-01, ends 2025-03-31
        [$fyStartYear, $fyEndYear] = explode('-', $financialYear);
        $fyStart = Carbon::create((int)$fyStartYear, 4, 1)->startOfDay();
        $fyEnd   = Carbon::create((int)$fyEndYear, 3, 31)->endOfDay();
    } else {
        // 🔹 Default: current financial year
        $today = now();
        $fyStart = $today->month > 3
            ? Carbon::create($today->year, 4, 1)->startOfDay()
            : Carbon::create($today->year - 1, 4, 1)->startOfDay();

        $fyEnd = $fyStart->copy()->addYear()->subDay()->endOfDay();
    }

    $startDate = $fyStart->toDateString();
    $endDate   = $fyEnd->toDateString();

    //  Total pending (denominator for %) with proper currency handling
    $totalPendingQuery = DB::table('vendor_po_final as vpf')
        ->join('vendor_po_invoice_new as vpi', 'vpf.PO_ID', '=', 'vpi.po_id')
        ->leftJoin('tbl_currencies as c', 'vpf.Flag', '=', 'c.currency_code')
        ->where('vpi.status', '1')
        ->whereBetween('vpi.due_on', [$startDate, $endDate]);

    // Apply vendor filter if provided
    if (!empty($vendorId)) {
        $totalPendingQuery->where('vpi.vendor_id', $vendorId);
    }

    // Apply month filter if provided
    if (!empty($month) && $month != '0') {
        $totalPendingQuery->whereRaw("MONTH(vpi.due_on) = ?", [$month]);
    }

    $totalPendingAll = $totalPendingQuery->sum(DB::raw('DISTINCT(vpi.value * COALESCE(c.currency_value, 1))'));

    //  Pending per vendor with currency details
    $vendorsQuery = DB::table('vendor_po_final as vpf')
        ->join('vendor_po_invoice_new as vpi', 'vpf.PO_ID', '=', 'vpi.po_id')
        ->leftJoin('tbl_currencies as c', 'vpf.Flag', '=', 'c.currency_code')
        ->select(
            'vpi.vendor_id',
            DB::raw('SUM(DISTINCT(vpi.value * COALESCE(c.currency_value, 1))) as total_pending'),
            'c.currency_code',
            'c.currency_css_symbol',
            'vpf.Flag'
        )
        ->where('vpi.status', '1')
        ->whereBetween('vpi.due_on', [$startDate, $endDate]);

    // Apply vendor filter if provided
    if (!empty($vendorId)) {
        $vendorsQuery->where('vpi.vendor_id', $vendorId);
    }

    // Apply month filter if provided
    if (!empty($month) && $month != '0') {
        $vendorsQuery->whereRaw("MONTH(vpi.due_on) = ?", [$month]);
    }

    $vendors = $vendorsQuery
        ->groupBy('vpi.vendor_id', 'c.currency_code', 'c.currency_css_symbol', 'vpf.Flag')
        ->orderByDesc(DB::raw('SUM(vpi.value * COALESCE(c.currency_value, 1))'))
        ->get();

    $data = [];

    foreach ($vendors as $vendor) {
        $pendingValue = (float) $vendor->total_pending;
        $percentage   = $totalPendingAll > 0
            ? round(($pendingValue * 100) / $totalPendingAll, 1)
            : 0;

        //  Heatmap color logic
        if ($percentage >= 5) {
            $color = "res-he-row1 heat2-red";
        } elseif ($percentage >= 4) {
            $color = "res-he-row1 heat1-red";
        } elseif ($percentage >= 3) {
            $color = "res-he-row1 heat0-red";
        } elseif ($percentage >= 2) {
            $color = "res-he-row1 heat2-org";
        } elseif ($percentage >= 1) {
            $color = "res-he-row1 heat1-org";
        } elseif ($percentage >= 0.9) {
            $color = "res-he-row1 heat0-org";
        } else {
            $color = "res-he-row1 heat2-green";
        }

        $data[] = [
            'vendor_id'   => $vendor->vendor_id,
            'vendor_name' => vendor_name($vendor->vendor_id), // 🔹 Replace with actual helper/model
            'total_value' => $pendingValue,
            'total_formatted' => ($vendor->currency_css_symbol ?? ($vendor->Flag == 'INR' ? '₹' : $vendor->Flag)) . ' ' . number_format($pendingValue, 2),
            'percentage'  => $percentage,
            'color_class' => $color,
            'currency_info' => [
                'currency_code' => $vendor->currency_code ?? $vendor->Flag ?? 'INR',
                'currency_symbol' => $vendor->currency_css_symbol ?? ($vendor->Flag == 'INR' ? '₹' : $vendor->Flag),
                'flag' => $vendor->Flag
            ]
        ];
    }

    return response()->json([
        'financial_year' => $financialYear ?? ($fyStart->format('Y') . '-' . $fyEnd->format('Y')),
        'total_pending'  => $totalPendingAll,
        'total_formatted' => currencySymbolDefault(1) . ' ' . number_format($totalPendingAll, 2),
        'vendors'        => $data
    ]);
}



//snapshot:

public function accountsPayableSnapshot(Request $request)
{
    $acc_manager   = $request->input('acc_manager', 0);
    $company_name  = $request->input('company_name', '');
    $vendor_id     = $request->input('vendor_id', null);
    $financialYear = $request->input('financial_year', '');

    // If financial_year not provided  fallback to current FY
    if (!empty($financialYear)) {
        // Expected format: YYYY-YYYY (e.g. "2024-2025")
        [$startYear, $endYear] = explode('-', $financialYear);
        $fyStart = $startYear . '-04-01';
        $fyEnd   = $endYear   . '-03-31';
    } else {
        // Default: Current FY (April�March)
        $currentMonth = date('n');
        $currentYear  = date('Y');
        if ($currentMonth < 4) {
            $fyStart = ($currentYear - 1) . '-04-01';
            $fyEnd   = $currentYear . '-03-31';
        } else {
            $fyStart = $currentYear . '-04-01';
            $fyEnd   = ($currentYear + 1) . '-03-31';
        }
        $financialYear = date("Y", strtotime($fyStart)) . "-" . date("Y", strtotime($fyEnd));
    }

    $quarterColors = [
        "Q1" => "#E6EEFA",
        "Q2" => "#FFF0E6",
        "Q3" => "#D8F6F6",
        "Q4" => "#FEEAEA",
    ];

    $quarters = [
        "Q1" => [],
        "Q2" => [],
        "Q3" => [],
        "Q4" => [],
    ];

    $start = new \DateTime($fyStart);
    $end   = new \DateTime($fyEnd);

    // Loop through each month in FY
    while ($start <= $end) {
        $monthNumber = $start->format('n');
        $monthName   = $start->format('F Y');

        // Call enhanced helpers with currency support
        $payableDetails = pending_account_payables_monthly_detailed(
            $acc_manager,
            $fyStart,
            $fyEnd,
            $vendor_id ?: $company_name,
            $monthNumber
        );

        $paidDetails = pending_account_payables_paid_monthly_detailed(
            $acc_manager,
            $fyStart,
            $fyEnd,
            $vendor_id ?: $company_name,
            $monthNumber
        );

        // Get default currency HTML code from database
        $defaultCurrency = DB::table('tbl_currencies')
            ->select('currency_html_code', 'currency_css_symbol', 'currency_code')
            ->where('currency_super_default', 'yes')
            ->where('deleteflag', 'active')
            ->first();

        $defaultCurrencyHtmlCode = $defaultCurrency->currency_html_code ?? '&#8377;';
        $defaultCurrencySymbol = $defaultCurrency->currency_css_symbol ?? '₹';
        $defaultCurrencyCode = $defaultCurrency->currency_code ?? 'INR';

        $monthData = [
            'monthName'      => $monthName,
            'currency'       => $defaultCurrencyCode, // Always use default currency
            'currency_html_code' => $defaultCurrencyHtmlCode, // Always use default currency HTML code
            'payableAmount'  => $payableDetails['total_pending'],
            'paidAmount'     => $paidDetails['total_payable'],
            'payableRaw'     => $payableDetails['total_pending'],
            'paidRaw'        => $paidDetails['total_payable'],
            'payableCurrencies' => $payableDetails['currency_count'],
            'paidCurrencies'    => $paidDetails['currency_count'],
            'currency_breakdown' => [
                'payable' => $payableDetails['currency_breakdown'],
                'paid' => $paidDetails['currency_breakdown']
            ]
        ];

        // Assign month to correct quarter
        if (in_array($monthNumber, [4, 5, 6])) {
            $quarters["Q1"][] = $monthData;
        } elseif (in_array($monthNumber, [7, 8, 9])) {
            $quarters["Q2"][] = $monthData;
        } elseif (in_array($monthNumber, [10, 11, 12])) {
            $quarters["Q3"][] = $monthData;
        } elseif (in_array($monthNumber, [1, 2, 3])) {
            $quarters["Q4"][] = $monthData;
        }

        $start->modify('+1 month');
    }

    // Final JSON restructure
    $response = [];
    foreach ($quarters as $qName => $months) {
        $response[] = [
            "quarterName"    => $qName,
            "headingBgColor" => $quarterColors[$qName],
            "monthData"      => $months,
        ];
    }

    return response()->json([
        'financial_year' => $financialYear,
        'vendor_id'      => $vendor_id,
        'company_name'   => $company_name,
        'vendor_filter'  => $vendor_id ?: $company_name,
        'snapshot'       => $response
    ]);
}

/**
 * Get Due In tabs summary data for accounts payable dashboard
 * Provides amounts for each tab: Next 7 days, Next 15 days, Next 1 month, > 1 month, Overdue
 */
public function accountsPayableDueInSummary(Request $request)
{
    try {
        // Get filters (same as listing)
        $vendorId = $request->input('vendor_id', '');
        $searchCurrencyId = $request->input('search_currency_id', '');
        $financialYear = $request->input('financial_year', '');
        
        // Get default currency for display
        $defaultCurrency = DB::table('tbl_currencies')->where('cur_default', 'yes')->first();
        $defaultCurrencyCode = $defaultCurrency->cur_code ?? 'INR';
        $defaultCurrencySymbol = $defaultCurrency->cur_symbol ?? '₹';
        $defaultCurrencyId = $defaultCurrency->cur_id ?? 1;
        
        // Base SQL for all tabs
        $baseQuery = "
            SELECT 
                SUM(CASE 
                    WHEN vpi.flag = '$defaultCurrencyId' THEN (vpi.value - COALESCE(paid_amounts.total_paid, 0))
                    ELSE (vpi.value - COALESCE(paid_amounts.total_paid, 0)) * COALESCE(exchange_rates.exchange_rate, 1)
                END) as total_amount
            FROM vendor_po_invoice_new vpi
            LEFT JOIN (
                SELECT 
                    vpf_invoice_id,
                    SUM(amount_paid) as total_paid
                FROM vendor_po_final 
                WHERE deleteflag = 'active'
                GROUP BY vpf_invoice_id
            ) as paid_amounts ON vpi.id = paid_amounts.vpf_invoice_id
            LEFT JOIN (
                SELECT cur_id, exchange_rate 
                FROM tbl_currencies 
                WHERE deleteflag = 'active'
            ) as exchange_rates ON vpi.flag = exchange_rates.cur_id
            WHERE vpi.deleteflag = 'active' 
            AND vpi.status = 'active'
            AND (vpi.value - COALESCE(paid_amounts.total_paid, 0)) > 0
        ";
        
        // Add vendor filter if specified
        if (!empty($vendorId)) {
            $baseQuery .= " AND vpi.vendor_id = '$vendorId'";
        }
        
        // Add currency filter if specified
        if (!empty($searchCurrencyId)) {
            $baseQuery .= " AND vpi.flag = '$searchCurrencyId'";
        }
        
        // Add financial year filter if specified
        if (!empty($financialYear)) {
            $yearParts = explode('-', $financialYear);
            if (count($yearParts) == 2) {
                $startYear = $yearParts[0];
                $endYear = $yearParts[1];
                $baseQuery .= " AND vpi.invoice_date >= '$startYear-04-01' AND vpi.invoice_date <= '$endYear-03-31'";
            }
        }
        
        // Execute queries for each tab
        $tabs = [
            'next_7_days' => [
                'label' => 'Next 7 days',
                'condition' => 'AND DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -7 AND 0',
                'amount' => 0
            ],
            'next_15_days' => [
                'label' => 'Next 15 days', 
                'condition' => 'AND DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -15 AND 0',
                'amount' => 0
            ],
            'next_1_month' => [
                'label' => 'Next 1 month',
                'condition' => 'AND DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -30 AND 0', 
                'amount' => 0
            ],
            'more_than_1_month' => [
                'label' => '> 1 month',
                'condition' => 'AND DATEDIFF(CURDATE(), vpi.due_on) < -30',
                'amount' => 0
            ],
            'overdue' => [
                'label' => 'Overdue',
                'condition' => 'AND DATEDIFF(CURDATE(), vpi.due_on) > 0',
                'amount' => 0
            ]
        ];
        
        foreach ($tabs as $key => &$tab) {
            $query = $baseQuery . ' ' . $tab['condition'];
            $result = DB::select($query);
            $tab['amount'] = $result[0]->total_amount ?? 0;
            $tab['amount_formatted'] = html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($tab['amount']);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Due in summary retrieved successfully',
            'data' => [
                'tabs' => $tabs,
                'currency' => [
                    'code' => $defaultCurrencyCode,
                    'symbol' => html_entity_decode($defaultCurrencySymbol)
                ],
                'filters_applied' => [
                    'vendor_id' => $vendorId,
                    'currency_id' => $searchCurrencyId,
                    'financial_year' => $financialYear
                ]
            ]
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Error retrieving due in summary: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

/**
 * Get accounts payable listing data based on filters - JSON only
 * Replicates the old core PHP listing functionality matching the UI screenshot
 */
public function accountsPayableListing(Request $request)
{
    try {
        // Get request parameters
        $vendorId = $request->input('vendor_id', '');
        $searchCurrencyId = $request->input('search_currency_id', '');
        $currency = $request->input('currency', ''); // New currency parameter
        $currencyCode = $request->input('currency_code', ''); // New currency_code parameter
        $agingSearch = $request->input('aging_search', '');
        $financialYear = $request->input('financial_year', '');
        $overdueFilter = $request->input('overdue_filter', ''); // Legacy overdue filter
        $overdue = $request->input('overdue', ''); // New overdue filter: All/Yes/No
        $dueInFilter = $request->input('due_in_filter', '');
        $searchBy = $request->input('search_by', ''); // New search by invoice/PO
        $invoiceNumberSearch = $request->input('invoice_number', '');
        $poNumberSearch = $request->input('po_number', '');
        $startDate = $request->input('start_date', '');
        $endDate = $request->input('end_date', '');
        
        // Pagination parameters
        $page = $request->input('pageno', 1);
        $perPage = $request->input('records', 20);
        $orderBy = $request->input('order_by', 'vpi.due_on');
        $order = $request->input('order', 'DESC');
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Build search conditions
        $searchConditions = [];
        
        // Vendor filter
        if (!empty($vendorId) && $vendorId != '0') {
            $searchConditions[] = "vpi.vendor_id = '$vendorId'";
        }
        
        // Currency filter - support multiple parameter formats
        if (!empty($searchCurrencyId) && $searchCurrencyId != '0') {
            // Legacy parameter: search_currency_id
            $searchConditions[] = "vpf.Flag = '$searchCurrencyId'";
        } elseif (!empty($currency)) {
            // New parameter: currency (can be currency_id or currency_code)
            if (is_numeric($currency)) {
                // If numeric, treat as currency_id and get the currency_code
                $currencyInfo = DB::table('tbl_currencies')
                    ->where('currency_id', $currency)
                    ->first();
                if ($currencyInfo) {
                    $searchConditions[] = "vpf.Flag = '{$currencyInfo->currency_code}'";
                }
            } else {
                // If non-numeric, treat as currency_code directly
                $searchConditions[] = "vpf.Flag = '$currency'";
            }
        } elseif (!empty($currencyCode)) {
            // New parameter: currency_code
            $searchConditions[] = "vpf.Flag = '$currencyCode'";
        }
        
        // Financial year filter
        if (!empty($financialYear)) {
            // Parse financial year (e.g., "2024-2025")
            [$startYear, $endYear] = explode('-', $financialYear);
            $fyStart = $startYear . '-04-01';
            $fyEnd = $endYear . '-03-31';
            $searchConditions[] = "vpi.invoice_date BETWEEN '$fyStart' AND '$fyEnd'";
        }
        
        // Enhanced Overdue filter - All/Yes/No
        if (!empty($overdue)) {
            if (strtolower($overdue) === 'yes') {
                // Show only overdue invoices
                $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) > 0";
            } elseif (strtolower($overdue) === 'no') {
                // Show only non-overdue invoices
                $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) <= 0";
            }
            // If 'all' or empty, no additional filter (show all)
        }
        
        // Legacy overdue filter (for backward compatibility)
        if (!empty($overdueFilter) && empty($overdue)) {
            if ($overdueFilter === 'yes') {
                $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) > 0";
            } elseif ($overdueFilter === 'no') {
                $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) <= 0";
            }
        }
        
        // Search by Invoice Number or PO ID
        if (!empty($searchBy)) {
            // Search in both invoice number and PO ID
            $searchConditions[] = "(vpi.invoice_no LIKE '%$searchBy%' OR vpi.po_id LIKE '%$searchBy%')";
        }
        
        // Due in filter - matching the UI tabs exactly
        if (!empty($dueInFilter)) {
            switch ($dueInFilter) {
                case 'next_7_days':
                    // Next 7 days tab
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -7 AND 0";
                    break;
                case 'next_15_days':
                    // Next 15 days tab
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -15 AND 0";
                    break;
                case 'next_1_month':
                case 'next_30_days':
                    // Next 1 month tab
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -30 AND 0";
                    break;
                case 'more_than_1_month':
                case 'greater_than_1_month':
                    // > 1 month tab - payments due more than 30 days in future
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) < -30";
                    break;
                case 'overdue':
                    // Overdue tab - payments past due date
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) > 0";
                    break;
            }
        }
        
        // Invoice number search
        if (!empty($invoiceNumberSearch)) {
            $searchConditions[] = "vpi.invoice_no LIKE '%$invoiceNumberSearch%'";
        }
        
        // PO number search
        if (!empty($poNumberSearch)) {
            $searchConditions[] = "vpi.po_id LIKE '%$poNumberSearch%'";
        }
        
        // Date range filter
        if (!empty($startDate) && !empty($endDate)) {
            $searchConditions[] = "vpi.invoice_date BETWEEN '$startDate' AND '$endDate'";
        }
        
        // Aging filter (legacy support)
        if (!empty($agingSearch) && $agingSearch != '0') {
            switch ($agingSearch) {
                case '1': // Next 7 days
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -7 AND 0";
                    break;
                case '2': // Next 15 days
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -15 AND 0";
                    break;
                case '3': // Next 1 month
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -30 AND 0";
                    break;
                case '4': // > 1 month future
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -365 AND -30";
                    break;
                case '5': // Overdue
                    $searchConditions[] = "DATEDIFF(CURDATE(), vpi.due_on) > 0";
                    break;
            }
        }
        
        // Combine search conditions
        $searchRecord = empty($searchConditions) ? '' : 'AND ' . implode(' AND ', $searchConditions);
        
        // Build main query - include file upload columns
        $sql = "SELECT DISTINCT(vpi.vendor_id), 
                       vpf.Date,
                       vpf.payment_terms, 
                       vpf.Term_Delivery,
                       DATEDIFF(CURDATE(), vpi.due_on) as aging,
                       vpf.Flag, 
                       vpi.po_id, 
                       vpi.vendor_id, 
                       vpi.id, 
                       vpi.value, 
                       vpi.invoice_no, 
                       vpi.invoice_date, 
                       vpi.due_on,  
                       vpi.payment_date_on, 
                       vpi.status,
                       vpi.invoice_upload,
                       vpi.awb_upload,
                       vpi.boe_upload
                FROM vendor_po_final vpf 
                INNER JOIN vendor_po_invoice_new vpi ON vpf.PO_ID = vpi.po_id 
                WHERE vpi.status = 1 
                $searchRecord
                GROUP BY vpi.id  
                ORDER BY $orderBy $order 
                LIMIT $offset, $perPage";
        
        // Execute query
        $invoices = DB::select($sql);
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(DISTINCT vpi.id) as total
                     FROM vendor_po_final vpf 
                     INNER JOIN vendor_po_invoice_new vpi ON vpf.PO_ID = vpi.po_id 
                     WHERE vpi.status = 1 
                     $searchRecord";
        
        $totalCount = DB::select($countSql)[0]->total;
        
        // Get default currency information
        $defaultCurrency = DB::table('tbl_currencies')
            ->where('currency_super_default', 'yes')
            ->first();
        
        $defaultCurrencyCode = $defaultCurrency ? $defaultCurrency->currency_code : 'INR';
        $defaultCurrencySymbol = $defaultCurrency ? $defaultCurrency->currency_html_code : '&#8377;';
        
        // Process invoice data
        $processedInvoices = [];
        $totalPayable = 0;
        $totalAmountPaid = 0;
        $totalAmountDue = 0;
        
        foreach ($invoices as $invoice) {
            // Get vendor name
            $vendorName = vendor_name($invoice->vendor_id);
            
            // Get payment terms name
            $paymentTermsName = vendor_payment_terms_name($invoice->payment_terms);
            
            // Get currency symbol and exchange rate
            $currencyDetails = currency_symbol_by_currency_code($invoice->Flag);
            $currencySymbol = $currencyDetails[0] ?? $defaultCurrencySymbol;
            $exchangeRate = $currencyDetails[1] ?? 1;
            $currencyId = $currencyDetails[2] ?? 1;
            
            // Get file paths from database and determine upload status
            $invoiceUpload = $invoice->invoice_upload ?? '';
            $awbUpload = $invoice->awb_upload ?? '';
            $boeUpload = $invoice->boe_upload ?? '';
            
            // Check if files exist and are not empty
            $hasInvoiceFile = !empty($invoiceUpload) && trim($invoiceUpload) !== '';
            $hasAwbFile = !empty($awbUpload) && trim($awbUpload) !== '';
            $hasBoeFile = !empty($boeUpload) && trim($boeUpload) !== '';
            
            // Get total payments made for this invoice
            $totalPaymentsPaid = get_total_part_payment_paid($invoice->id);
            $balanceAmount = $invoice->value - $totalPaymentsPaid;
            
            // Convert to default currency if needed
            $valueInDefaultCurrency = ($invoice->Flag != $defaultCurrencyCode) 
                ? $invoice->value * $exchangeRate 
                : $invoice->value;
            
            $paidInDefaultCurrency = ($invoice->Flag != $defaultCurrencyCode) 
                ? $totalPaymentsPaid * $exchangeRate 
                : $totalPaymentsPaid;
                
            $dueInDefaultCurrency = ($invoice->Flag != $defaultCurrencyCode) 
                ? $balanceAmount * $exchangeRate 
                : $balanceAmount;
            
            $totalPayable += $valueInDefaultCurrency;
            $totalAmountPaid += $paidInDefaultCurrency;
            $totalAmountDue += $dueInDefaultCurrency;
            
            // Format aging display and calculate days overdue
            $agingDisplay = '';
            $daysOverdue = 0;
            if ($invoice->aging > 0) {
                $daysOverdue = $invoice->aging;
                $agingDisplay = $daysOverdue . " DAYS";
            } else {
                $agingDays = $invoice->aging ?? 0;
                $agingDisplay = "Due in " . abs($agingDays) . " days";
            }
            
            // Prepare file paths with full URLs
            $baseUrl = config('app.url', 'http://localhost');
            $invoicePath = $invoiceUpload ? $baseUrl . '/' . $invoiceUpload : '';
            $awbPath = $awbUpload ? $baseUrl . '/' . $awbUpload : '';
            $boePath = $boeUpload ? $baseUrl . '/' . $boeUpload : '';
            
            $processedInvoices[] = [
                'id' => $invoice->id,
                'po_id' => $invoice->po_id,
                'vendor_id' => $invoice->vendor_id,
                'vendor_name' => $vendorName,
                'payment_terms' => $paymentTermsName,
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => $invoice->invoice_date,
                'invoice_date_formatted' => date_format_india($invoice->invoice_date),
                'due_on' => $invoice->due_on,
                'due_date_formatted' => date_format_india($invoice->due_on),
                'currency_code' => $invoice->Flag,
                'currency_symbol' => html_entity_decode($currencySymbol),
                'total_payable' => (float) $invoice->value,
                'total_payable_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($invoice->value),
                'amount_paid' => $totalPaymentsPaid,
                'amount_paid_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($totalPaymentsPaid),
                'amount_due' => $balanceAmount,
                'amount_due_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($balanceAmount),
                'aging' => $invoice->aging,
                'days_overdue' => $daysOverdue,
                'aging_display' => $agingDisplay,
                'is_overdue' => $invoice->aging > 0,
                'invoice_path' => $invoicePath,
                'awb_path' => $awbPath,
                'boe_path' => $boePath,
                'has_invoice_file' => $hasInvoiceFile,
                'has_awb_file' => $hasAwbFile,
                'has_boe_file' => $hasBoeFile,
                'status' => $invoice->status,
                'value_in_default_currency' => $valueInDefaultCurrency
            ];
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Accounts payable listing retrieved successfully',
            'data' => [
                'invoices' => $processedInvoices,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => (int) $totalCount,
                    'total_pages' => ceil($totalCount / $perPage),
                    'has_next_page' => ($page * $perPage) < $totalCount,
                    'has_prev_page' => $page > 1
                ],
                'summary' => [
                    'total_payable' => $totalPayable,
                    'total_payable_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalPayable),
                    'total_amount_paid' => $totalAmountPaid,
                    'total_amount_paid_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalAmountPaid),
                    'total_amount_due' => $totalAmountDue,
                    'total_amount_due_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalAmountDue),
                    'currency_code' => $defaultCurrencyCode,
                    'currency_symbol' => html_entity_decode($defaultCurrencySymbol),
                    'invoice_count' => count($processedInvoices),
                    'overdue_count' => count(array_filter($processedInvoices, function($inv) { return $inv['is_overdue']; }))
                ],
                'filters_applied' => [
                    'vendor_id' => $vendorId,
                    'currency_id' => $searchCurrencyId,
                    'currency' => $currency, // New currency parameter
                    'currency_code' => $currencyCode, // New currency_code parameter
                    'financial_year' => $financialYear,
                    'overdue_filter' => $overdueFilter, // Legacy parameter
                    'overdue' => $overdue, // New overdue filter (All/Yes/No)
                    'search_by' => $searchBy, // New search by invoice/PO
                    'due_in_filter' => $dueInFilter,
                    'aging_search' => $agingSearch,
                    'invoice_number' => $invoiceNumberSearch,
                    'po_number' => $poNumberSearch,
                    'date_range' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ],
            'generated_at' => now()->toISOString()
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Error retrieving accounts payable listing: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

/**
 * Get invoices payable after more than 90 days (long-term future payments)
 * Useful for long-term cash flow planning
 */
public function invoicesPayableAfter90Days(Request $request)
{
    try {
        // Get request parameters
        $financialYear = $request->input('financial_year', '');
        $vendorId = $request->input('vendor_id', '');
        $minDaysAhead = $request->input('min_days_ahead', 90); // Default to 90+ days
        $maxDaysAhead = $request->input('max_days_ahead', 365); // Default to 1 year ahead
        
        // Calculate financial year dates
        if (!empty($financialYear) && preg_match('/^(\d{4})-(\d{4})$/', $financialYear)) {
            [$startYear, $endYear] = explode('-', $financialYear);
            $fyStart = $startYear . '-04-01';
            $fyEnd = $endYear . '-03-31';
        } else {
            // Auto-calculate current financial year
            $today = Carbon::now();
            if ($today->month >= 4) {
                $fyStartYear = $today->year;
                $fyEndYear = $today->year + 1;
            } else {
                $fyStartYear = $today->year - 1;
                $fyEndYear = $today->year;
            }
            $fyStart = $fyStartYear . '-04-01';
            $fyEnd = $fyEndYear . '-03-31';
            $financialYear = $fyStartYear . '-' . $fyEndYear;
        }
        
        // Get default currency information
        $defaultCurrency = DB::table('tbl_currencies')
            ->where('currency_super_default', 'yes')
            ->where('deleteflag', 'active')
            ->first();
        
        $defaultCurrencyCode = $defaultCurrency ? $defaultCurrency->currency_code : 'INR';
        $defaultCurrencySymbol = $defaultCurrency ? html_entity_decode($defaultCurrency->currency_html_code) : '₹';
        
        // Build query for invoices payable after 90+ days
        $query = DB::table('vendor_po_final as vpf')
            ->join('vendor_po_invoice_new as vpi', 'vpf.PO_ID', '=', 'vpi.po_id')
            ->leftJoin('tbl_currencies as c', 'vpf.Flag', '=', 'c.currency_code')
            ->select([
                'vpi.id as invoice_id',
                'vpi.po_id',
                'vpi.vendor_id',
                'vpi.invoice_no',
                'vpi.invoice_date',
                'vpi.due_on',
                'vpi.value as invoice_amount',
                'vpf.Flag as currency_code',
                'c.currency_html_code',
                'c.currency_css_symbol',
                'c.currency_value',
                DB::raw('DATEDIFF(CURDATE(), vpi.due_on) as aging_days'),
                DB::raw('ABS(DATEDIFF(CURDATE(), vpi.due_on)) as days_until_due'),
                DB::raw('vpi.value * COALESCE(c.currency_value, 1) as amount_default_currency')
            ])
            ->where('vpi.status', '1') // Only active/pending invoices
            ->whereBetween('vpi.due_on', [$fyStart, $fyEnd]) // Within financial year
            ->whereRaw("DATEDIFF(CURDATE(), vpi.due_on) BETWEEN ? AND ?", [-$maxDaysAhead, -$minDaysAhead]); // 90+ days ahead
        
        // Apply vendor filter if provided
        if (!empty($vendorId)) {
            $query->where('vpi.vendor_id', $vendorId);
        }
        
        // Get results ordered by due date
        $invoices = $query->orderBy('vpi.due_on', 'ASC')->get();
        
        // Process results
        $processedInvoices = [];
        $totalAmountDefaultCurrency = 0;
        $currencyBreakdown = [];
        
        foreach ($invoices as $invoice) {
            // Get vendor name
            $vendorName = vendor_name($invoice->vendor_id);
            
            // Get proper currency symbol
            $currencySymbol = html_entity_decode($invoice->currency_html_code) ?: 
                             ($invoice->currency_code == 'INR' ? '₹' : $invoice->currency_code);
            
            $daysUntilDue = $invoice->days_until_due;
            $totalAmountDefaultCurrency += $invoice->amount_default_currency;
            
            // Group by currency for breakdown
            if (!isset($currencyBreakdown[$invoice->currency_code])) {
                $currencyBreakdown[$invoice->currency_code] = [
                    'currency_code' => $invoice->currency_code,
                    'currency_symbol' => $currencySymbol,
                    'invoice_count' => 0,
                    'total_amount' => 0,
                    'total_default_currency' => 0
                ];
            }
            
            $currencyBreakdown[$invoice->currency_code]['invoice_count']++;
            $currencyBreakdown[$invoice->currency_code]['total_amount'] += $invoice->invoice_amount;
            $currencyBreakdown[$invoice->currency_code]['total_default_currency'] += $invoice->amount_default_currency;
            
            // Determine priority based on days until due
            $priority = 'low';
            if ($daysUntilDue <= 120) {
                $priority = 'medium';
            } elseif ($daysUntilDue <= 180) {
                $priority = 'high';
            }
            
            $processedInvoices[] = [
                'invoice_id' => $invoice->invoice_id,
                'po_id' => $invoice->po_id,
                'vendor_id' => $invoice->vendor_id,
                'vendor_name' => $vendorName,
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => $invoice->invoice_date,
                'invoice_date_formatted' => date_format_india($invoice->invoice_date),
                'due_on' => $invoice->due_on,
                'due_date_formatted' => date_format_india($invoice->due_on),
                'currency_code' => $invoice->currency_code,
                'currency_symbol' => $currencySymbol,
                'invoice_amount' => (float) $invoice->invoice_amount,
                'invoice_amount_formatted' => $currencySymbol . ' ' . number_format($invoice->invoice_amount, 2),
                'amount_default_currency' => (float) $invoice->amount_default_currency,
                'aging_days' => $invoice->aging_days,
                'days_until_due' => $daysUntilDue,
                'due_status' => "Due in {$daysUntilDue} days",
                'priority' => $priority,
                'months_until_due' => round($daysUntilDue / 30, 1),
                'is_long_term' => $daysUntilDue > 180
            ];
        }
        
        // Format currency breakdown
        $formattedCurrencyBreakdown = array_values(array_map(function($item) {
            return [
                'currency_code' => $item['currency_code'],
                'currency_symbol' => $item['currency_symbol'],
                'invoice_count' => $item['invoice_count'],
                'total_amount' => $item['total_amount'],
                'total_formatted' => $item['currency_symbol'] . ' ' . number_format($item['total_amount'], 2),
                'total_default_currency' => $item['total_default_currency']
            ];
        }, $currencyBreakdown));
        
        // Create aging ranges for better analysis
        $agingRanges = [
            '90-120_days' => ['min' => 90, 'max' => 120, 'label' => '90-120 Days', 'count' => 0, 'amount' => 0],
            '121-180_days' => ['min' => 121, 'max' => 180, 'label' => '121-180 Days', 'count' => 0, 'amount' => 0],
            '181-270_days' => ['min' => 181, 'max' => 270, 'label' => '181-270 Days', 'count' => 0, 'amount' => 0],
            'beyond_270_days' => ['min' => 271, 'max' => 999, 'label' => 'Beyond 270 Days', 'count' => 0, 'amount' => 0]
        ];
        
        foreach ($processedInvoices as $invoice) {
            $days = $invoice['days_until_due'];
            foreach ($agingRanges as $key => &$range) {
                if ($days >= $range['min'] && $days <= $range['max']) {
                    $range['count']++;
                    $range['amount'] += $invoice['amount_default_currency'];
                    break;
                }
            }
        }
        
        // Format aging ranges
        $formattedAgingRanges = array_map(function($range) use ($defaultCurrencySymbol) {
            return [
                'label' => $range['label'],
                'invoice_count' => $range['count'],
                'total_amount' => $range['amount'],
                'total_formatted' => $defaultCurrencySymbol . ' ' . number_format($range['amount'], 2)
            ];
        }, $agingRanges);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Invoices payable after 90+ days retrieved successfully',
            'data' => [
                'summary' => [
                    'total_amount' => $totalAmountDefaultCurrency,
                    'total_formatted' => $defaultCurrencySymbol . ' ' . number_format($totalAmountDefaultCurrency, 2),
                    'total_invoices' => count($processedInvoices),
                    'currency_code' => $defaultCurrencyCode,
                    'currency_symbol' => $defaultCurrencySymbol,
                    'financial_year' => $financialYear,
                    'fy_start' => $fyStart,
                    'fy_end' => $fyEnd,
                    'min_days_ahead' => $minDaysAhead,
                    'max_days_ahead' => $maxDaysAhead,
                    'average_days_until_due' => count($processedInvoices) > 0 ? round(array_sum(array_column($processedInvoices, 'days_until_due')) / count($processedInvoices)) : 0
                ],
                'currency_breakdown' => $formattedCurrencyBreakdown,
                'aging_ranges' => $formattedAgingRanges,
                'invoices' => $processedInvoices,
                'filters_applied' => [
                    'vendor_id' => $vendorId ?: null,
                    'financial_year' => $financialYear,
                    'min_days_ahead' => $minDaysAhead,
                    'max_days_ahead' => $maxDaysAhead
                ]
            ]
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Error retrieving invoices payable after 90+ days: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

/**
 * Get all payments that are not yet due (future payments)
 * Specialized function for cash flow planning and payment scheduling
 */
public function notYetDuePayments(Request $request)
{
    try {
        // Get request parameters
        $vendorId = $request->input('vendor_id', '');
        $financialYear = $request->input('financial_year', '');
        $currencyCode = $request->input('currency_code', '');
        $maxDaysAhead = $request->input('max_days_ahead', 365); // Default to 1 year ahead
        
        // Pagination parameters
        $pageNo = max(1, (int) $request->input('pageno', 1));
        $records = min(1000, max(1, (int) $request->input('records', 25))); // Max 1000 records per page
        $offset = ($pageNo - 1) * $records;
        
        // Calculate financial year dates
        if (!empty($financialYear) && preg_match('/^(\d{4})-(\d{4})$/', $financialYear)) {
            [$startYear, $endYear] = explode('-', $financialYear);
            $fyStart = $startYear . '-04-01';
            $fyEnd = $endYear . '-03-31';
        } else {
            // Auto-calculate current financial year
            $today = Carbon::now();
            if ($today->month >= 4) {
                $fyStartYear = $today->year;
                $fyEndYear = $today->year + 1;
            } else {
                $fyStartYear = $today->year - 1;
                $fyEndYear = $today->year;
            }
            $fyStart = $fyStartYear . '-04-01';
            $fyEnd = $fyEndYear . '-03-31';
            $financialYear = $fyStartYear . '-' . $fyEndYear;
        }
        
        // Get default currency information
        $defaultCurrency = DB::table('tbl_currencies')
            ->where('currency_super_default', 'yes')
            ->where('deleteflag', 'active')
            ->first();
        
        $defaultCurrencyCode = $defaultCurrency ? $defaultCurrency->currency_code : 'INR';
        $defaultCurrencySymbol = $defaultCurrency ? html_entity_decode($defaultCurrency->currency_html_code) : '₹';
        
        // Build base query for not yet due payments
        $baseQuery = DB::table('vendor_po_final as vpf')
            ->join('vendor_po_invoice_new as vpi', 'vpf.PO_ID', '=', 'vpi.po_id')
            ->leftJoin('tbl_currencies as c', 'vpf.Flag', '=', 'c.currency_code')
            ->where('vpi.status', '1') // Only active/pending invoices
            ->whereBetween('vpi.due_on', [$fyStart, $fyEnd]) // Within financial year
            ->whereRaw("DATEDIFF(CURDATE(), vpi.due_on) BETWEEN ? AND ?", [-$maxDaysAhead, 0]); // Not yet due
        
        // Apply vendor filter if provided
        if (!empty($vendorId)) {
            $baseQuery->where('vpi.vendor_id', $vendorId);
        }
        
        // Apply currency filter if provided
        if (!empty($currencyCode)) {
            $baseQuery->where('vpf.Flag', $currencyCode);
        }
        
        // Get summary totals by currency
        $summaryQuery = clone $baseQuery;
        $currencySummary = $summaryQuery
            ->select([
                'vpf.Flag as currency_code',
                'c.currency_html_code',
                'c.currency_css_symbol',
                DB::raw('COUNT(DISTINCT vpi.id) as invoice_count'),
                DB::raw('SUM(DISTINCT vpi.value) as total_amount'),
                DB::raw('SUM(DISTINCT vpi.value * COALESCE(c.currency_value, 1)) as total_amount_default_currency')
            ])
            ->groupBy('vpf.Flag', 'c.currency_html_code', 'c.currency_css_symbol')
            ->orderByDesc('total_amount_default_currency')
            ->get();
        
        // Get aging breakdown
        $agingBreakdown = [];
        $agingBuckets = [
            'today' => ['min' => 0, 'max' => 0, 'label' => 'Due Today'],
            'next_7_days' => ['min' => -7, 'max' => -1, 'label' => 'Next 7 Days'],
            'next_15_days' => ['min' => -15, 'max' => -8, 'label' => 'Next 8-15 Days'],
            'next_30_days' => ['min' => -30, 'max' => -16, 'label' => 'Next 16-30 Days'],
            'beyond_30_days' => ['min' => -$maxDaysAhead, 'max' => -31, 'label' => 'Beyond 30 Days']
        ];
        
        foreach ($agingBuckets as $key => $bucket) {
            $agingQuery = clone $baseQuery;
            $agingAmount = $agingQuery
                ->whereRaw("DATEDIFF(CURDATE(), vpi.due_on) BETWEEN ? AND ?", [$bucket['min'], $bucket['max']])
                ->sum(DB::raw('DISTINCT(vpi.value * COALESCE(c.currency_value, 1))'));
            
            $agingCount = $agingQuery
                ->whereRaw("DATEDIFF(CURDATE(), vpi.due_on) BETWEEN ? AND ?", [$bucket['min'], $bucket['max']])
                ->count(DB::raw('DISTINCT vpi.id'));
            
            $agingBreakdown[] = [
                'key' => $key,
                'label' => $bucket['label'],
                'days_range' => $bucket['min'] . ' to ' . $bucket['max'],
                'invoice_count' => (int) $agingCount,
                'total_amount' => (float) $agingAmount,
                'total_formatted' => $defaultCurrencySymbol . ' ' . number_format($agingAmount, 2)
            ];
        }
        
        // Get total count for pagination
        $totalCountQuery = clone $baseQuery;
        $totalCount = $totalCountQuery->count(DB::raw('DISTINCT vpi.id'));
        
        // Get detailed invoice list with pagination
        $detailedQuery = clone $baseQuery;
        $detailedInvoices = $detailedQuery
            ->select([
                'vpi.id as invoice_id',
                'vpi.po_id',
                'vpi.vendor_id',
                'vpi.invoice_no',
                'vpi.invoice_date',
                'vpi.due_on',
                'vpi.value as invoice_amount',
                'vpf.Flag as currency_code',
                'c.currency_html_code',
                'c.currency_css_symbol',
                DB::raw('DATEDIFF(CURDATE(), vpi.due_on) as aging_days'),
                DB::raw('vpi.value * COALESCE(c.currency_value, 1) as amount_default_currency')
            ])
            ->orderBy('vpi.due_on', 'ASC') // Earliest due dates first
            ->offset($offset)
            ->limit($records)
            ->get();
        
        // Process detailed invoices
        $processedInvoices = [];
        foreach ($detailedInvoices as $invoice) {
            $vendorName = vendor_name($invoice->vendor_id);
            $currencySymbol = html_entity_decode($invoice->currency_html_code) ?: ($invoice->currency_code == 'INR' ? '₹' : $invoice->currency_code);
            
            // Calculate days until due
            $agingDays = $invoice->aging_days ?? 0;
            $daysUntilDue = abs($agingDays);
            $dueStatus = '';
            
            if ($invoice->aging_days == 0) {
                $dueStatus = 'Due Today';
            } elseif ($invoice->aging_days < 0) {
                $dueStatus = "Due in {$daysUntilDue} days";
            } else {
                $dueStatus = "{$daysUntilDue} days overdue"; // Shouldn't happen in this query
            }
            
            $processedInvoices[] = [
                'invoice_id' => $invoice->invoice_id,
                'po_id' => $invoice->po_id,
                'vendor_id' => $invoice->vendor_id,
                'vendor_name' => $vendorName,
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => $invoice->invoice_date,
                'invoice_date_formatted' => date_format_india($invoice->invoice_date),
                'due_on' => $invoice->due_on,
                'due_date_formatted' => date_format_india($invoice->due_on),
                'currency_code' => $invoice->currency_code,
                'currency_symbol' => $currencySymbol,
                'invoice_amount' => (float) $invoice->invoice_amount,
                'invoice_amount_formatted' => $currencySymbol . ' ' . number_format($invoice->invoice_amount, 2),
                'amount_default_currency' => (float) $invoice->amount_default_currency,
                'aging_days' => $invoice->aging_days,
                'days_until_due' => $daysUntilDue,
                'due_status' => $dueStatus,
                'is_due_today' => $invoice->aging_days == 0,
                'priority' => $invoice->aging_days >= -7 ? 'high' : ($invoice->aging_days >= -30 ? 'medium' : 'low')
            ];
        }
        
        // Calculate grand totals
        $grandTotal = $currencySummary->sum('total_amount_default_currency');
        $totalInvoices = $currencySummary->sum('invoice_count');
        
        // Calculate pagination info
        $totalPages = ceil($totalCount / $records);
        $pagination = [
            'current_page' => $pageNo,
            'records_per_page' => $records,
            'total_records' => $totalCount,
            'total_pages' => $totalPages,
            'has_next_page' => $pageNo < $totalPages,
            'has_previous_page' => $pageNo > 1,
            'next_page' => $pageNo < $totalPages ? $pageNo + 1 : null,
            'previous_page' => $pageNo > 1 ? $pageNo - 1 : null,
            'showing_records' => [
                'from' => $totalCount > 0 ? $offset + 1 : 0,
                'to' => min($offset + $records, $totalCount),
                'total' => $totalCount
            ]
        ];
        
        return response()->json([
            'status' => 'success',
            'message' => 'Not yet due payments retrieved successfully',
            'data' => [
                'pagination' => $pagination,
                'summary' => [
                    'total_amount' => $grandTotal,
                    'total_formatted' => $defaultCurrencySymbol . ' ' . number_format($grandTotal, 2),
                    'total_invoices' => $totalInvoices,
                    'total_invoices_current_page' => count($processedInvoices),
                    'currency_code' => $defaultCurrencyCode,
                    'currency_symbol' => $defaultCurrencySymbol,
                    'financial_year' => $financialYear,
                    'fy_start' => $fyStart,
                    'fy_end' => $fyEnd,
                    'max_days_ahead' => $maxDaysAhead
                ],
                'currency_breakdown' => $currencySummary->map(function ($item) {
                    $symbol = html_entity_decode($item->currency_html_code) ?: ($item->currency_code == 'INR' ? '₹' : $item->currency_code);
                    return [
                        'currency_code' => $item->currency_code,
                        'currency_symbol' => $symbol,
                        'invoice_count' => $item->invoice_count,
                        'total_amount' => (float) $item->total_amount,
                        'total_formatted' => $symbol . ' ' . number_format($item->total_amount, 2),
                        'total_default_currency' => (float) $item->total_amount_default_currency
                    ];
                }),
                'aging_breakdown' => $agingBreakdown,
                'invoices' => $processedInvoices,
                'filters_applied' => [
                    'vendor_id' => $vendorId ?: null,
                    'currency_code' => $currencyCode ?: null,
                    'financial_year' => $financialYear,
                    'max_days_ahead' => $maxDaysAhead,
                    'pageno' => $pageNo,
                    'records' => $records
                ]
            ]
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Error retrieving not yet due payments: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

/**
 * Get Purchase Order details with payment information
 * Based on old ven_payments.php functionality for detailed PO view
 */
public function purchaseOrderPaymentDetails(Request $request)
{
    try {
        // Get request parameters
        $poId = $request->input('po_id', '');
        $invoiceId = $request->input('invoice_id', '');
        $vendorId = $request->input('vendor_id', '');
        
        // Pagination parameters
        $pageNo = max(1, (int) $request->input('pageno', 1));
        $records = min(1000, max(1, (int) $request->input('records', 25))); // Max 1000 records per page
        $offset = ($pageNo - 1) * $records;
        
        // Validate required parameters
        if (empty($poId) && empty($invoiceId)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'PO ID or Invoice ID is required',
                'data' => null
            ], 400);
        }
        
        // Build base query for invoice details with counting
        $countQuery = "
            SELECT COUNT(DISTINCT vpi.id) as total_count
            FROM vendor_po_invoice_new vpi
            INNER JOIN vendor_po_final vpf ON vpi.po_id = vpf.PO_ID
            WHERE vpi.status = '1'
        ";
        
        // Build main query for invoice details
        $invoiceQuery = "
            SELECT 
                vpi.id as invoice_id,
                vpi.po_id,
                vpi.vendor_id,
                vpi.invoice_no,
                vpi.invoice_date,
                vpi.due_on,
                vpi.value as invoice_amount,
                vpi.status,
                vpi.payment_date_on,
                vpf.Date as po_date,
                vpf.payment_terms,
                vpf.Term_Delivery,
                vpf.Flag as currency_code,
                DATEDIFF(CURDATE(), vpi.due_on) as aging,
                vpf.buyer_company_name,
                vpf.buyer_contact_name,
                vpf.buyer_address,
                vpf.buyer_city,
                vpf.buyer_state,
                vpf.buyer_country,
                vpf.buyer_pincode,
                vpf.buyer_telephone,
                vpf.buyer_mobile,
                vpf.buyer_email,
                vpf.buyer_gst_no
            FROM vendor_po_invoice_new vpi
            INNER JOIN vendor_po_final vpf ON vpi.po_id = vpf.PO_ID
            WHERE vpi.status = '1'
        ";
        
        // Add conditions based on parameters
        $whereConditions = '';
        if (!empty($invoiceId)) {
            $whereConditions .= " AND vpi.id = '$invoiceId'";
        }
        
        if (!empty($poId)) {
            $whereConditions .= " AND vpi.po_id = '$poId'";
        }
        
        if (!empty($vendorId)) {
            $whereConditions .= " AND vpi.vendor_id = '$vendorId'";
        }
        
        // Add conditions to both queries
        $countQuery .= $whereConditions;
        $invoiceQuery .= $whereConditions;
        
        // Get total count first
        $totalCountResult = DB::select($countQuery);
        $totalCount = $totalCountResult[0]->total_count ?? 0;
        
        // Add pagination and ordering to main query
        $invoiceQuery .= " GROUP BY vpi.id ORDER BY vpi.invoice_date DESC LIMIT $records OFFSET $offset";
        
        // Execute invoice query
        $invoices = DB::select($invoiceQuery);
        
        if (empty($invoices)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No invoices found for the specified criteria',
                'data' => null
            ], 404);
        }

        // Get PO-level payment totals (amount_paid by PO) including TDS components
        $poIds = array_unique(array_column($invoices, 'po_id'));
        $poPaymentTotals = [];
        
        if (!empty($poIds)) {
            $poIdsString = implode(',', $poIds);
            $poPaymentQuery = "
                SELECT 
                    po_id,
                    SUM(payment_paid_value) as total_amount_paid,
                    SUM(payment_paid_value_tds) as total_tds_amount,
                    SUM(credit_note_value) as total_credit_note,
                    SUM(lda_other_value) as total_lda_other,
                    SUM(payment_paid_value + payment_paid_value_tds + credit_note_value + lda_other_value) as total_payment_received,
                    COUNT(*) as payment_count,
                    COUNT(CASE WHEN tds_check_on_portal = 1 THEN 1 END) as tds_verified_count
                FROM tbl_payment_paid 
                WHERE po_id IN ($poIdsString) 
                AND deleteflag = 'active'
                GROUP BY po_id
            ";
            
            $poPayments = DB::select($poPaymentQuery);
            
            foreach ($poPayments as $poPayment) {
                $poPaymentTotals[$poPayment->po_id] = [
                    'total_amount_paid' => (float) $poPayment->total_amount_paid,
                    'total_tds_amount' => (float) $poPayment->total_tds_amount,
                    'total_credit_note' => (float) $poPayment->total_credit_note,
                    'total_lda_other' => (float) $poPayment->total_lda_other,
                    'total_payment_received' => (float) $poPayment->total_payment_received,
                    'payment_count' => (int) $poPayment->payment_count,
                    'tds_verified_count' => (int) $poPayment->tds_verified_count,
                    'has_tds' => $poPayment->total_tds_amount > 0,
                    'has_credit_note' => $poPayment->total_credit_note > 0,
                    'has_lda_other' => $poPayment->total_lda_other > 0
                ];
            }
        }
        
        // Get default currency information
        $defaultCurrency = DB::table('tbl_currencies')
            ->where('currency_super_default', 'yes')
            ->first();
        
        $defaultCurrencyCode = $defaultCurrency ? $defaultCurrency->currency_code : 'INR';
        $defaultCurrencySymbol = $defaultCurrency ? $defaultCurrency->currency_html_code : '&#8377;';
        
        $processedInvoices = [];
        $totalInvoiceAmount = 0;
        $totalPaidAmount = 0;
        $totalDueAmount = 0;
        $totalTdsAmount = 0;
        $totalCreditNoteAmount = 0;
        $totalLdaOtherAmount = 0;
        $totalPaymentReceivedAmount = 0;
        
        foreach ($invoices as $invoice) {
            // Get comprehensive vendor details from vendor_master table
            $vendorDetails = DB::table('vendor_master')
                ->select([
                    'ID as vendor_id',
                    'C_Name as vendor_company_name',
                    'Contact_1 as vendor_contact_person',
                    'Email as vendor_email',
                    'Number as vendor_phone',
                    'sales_mobile as vendor_mobile',
                    'AddressName as vendor_address',
                    'city as vendor_city',
                    'state as vendor_state',
                    'Country as vendor_country',
                    'pincode as vendor_pincode',
                    'gst_no as vendor_gst_no',
                    'bank_name as vendor_bank_name',
                    'account_number as vendor_bank_account_no',
                    'ifsc_code as vendor_bank_ifsc',
                    'status as vendor_status',
                    'Currency as vendor_currency',
                    'purchase_type as vendor_category',
                    'support_name as vendor_website',
                    'support_email as vendor_remarks'
                ])
                ->where('ID', $invoice->vendor_id)
                ->where('deleteflag', 'active')
                ->first();
            
            // Fallback to helper function if vendor not found
            $vendorName = $vendorDetails ? $vendorDetails->vendor_company_name : vendor_name($invoice->vendor_id);
            
            // Get payment terms name
            $paymentTermsName = vendor_payment_terms_name($invoice->payment_terms);
            
            // Get currency details
            $currencyDetails = currency_symbol_by_currency_code($invoice->currency_code);
            $currencySymbol = $currencyDetails[0] ?? $defaultCurrencySymbol;
            $exchangeRate = $currencyDetails[1] ?? 1;
            $currencyId = $currencyDetails[2] ?? 1;
            
            // Get all payments made for this invoice with TDS/TCS details
            $paymentsQuery = "
                SELECT 
                    pp.payment_paid_id as payment_id,
                    pp.payment_paid_value as amount_paid,
                    pp.payment_paid_value_tds as tds_amount,
                    pp.credit_note_value,
                    pp.lda_other_value as lda_other_charges,
                    pp.payment_paid_date as payment_date,
                    pp.exchange_rate,
                    pp.currency_id,
                    pp.payment_paid_via as payment_method,
                    pp.transaction_id,
                    pp.payment_paid_in_bank as bank_id,
                    pp.payment_paid_type as payment_type,
                    pp.tds_check_on_portal,
                    pp.payment_remarks as remarks,
                    pp.inserted_date as created_date
                FROM tbl_payment_paid pp
                WHERE pp.invoice_id = '{$invoice->invoice_id}'
                AND pp.deleteflag = 'active'
                ORDER BY pp.payment_paid_date DESC
            ";
            
            $payments = DB::select($paymentsQuery);
            
            // Process payments
            $processedPayments = [];
            $invoiceTotalPaid = 0;
            $invoiceTotalTds = 0;
            $invoiceTotalCreditNote = 0;
            $invoiceTotalLdaOther = 0;
            
            foreach ($payments as $payment) {
                // Use invoice currency since payment currency is not available
                $paymentCurrencySymbol = $currencySymbol;
                
                // Calculate totals including TDS components
                $invoiceTotalPaid += $payment->amount_paid;
                $invoiceTotalTds += $payment->tds_amount;
                $invoiceTotalCreditNote += $payment->credit_note_value;
                $invoiceTotalLdaOther += $payment->lda_other_charges;
                
                // Calculate net payment (amount_paid + TDS + credit note + LDA/other)
                $netPaymentAmount = $payment->amount_paid + $payment->tds_amount + $payment->credit_note_value + $payment->lda_other_charges;
                
                $processedPayments[] = [
                    'payment_id' => $payment->payment_id,
                    'amount_paid' => (float) $payment->amount_paid,
                    'amount_paid_formatted' => html_entity_decode($paymentCurrencySymbol) . ' ' . moneyFormatIndia($payment->amount_paid),
                    'tds_amount' => (float) $payment->tds_amount,
                    'tds_amount_formatted' => html_entity_decode($paymentCurrencySymbol) . ' ' . moneyFormatIndia($payment->tds_amount),
                    'credit_note_value' => (float) $payment->credit_note_value,
                    'credit_note_value_formatted' => html_entity_decode($paymentCurrencySymbol) . ' ' . moneyFormatIndia($payment->credit_note_value),
                    'lda_other_charges' => (float) $payment->lda_other_charges,
                    'lda_other_charges_formatted' => html_entity_decode($paymentCurrencySymbol) . ' ' . moneyFormatIndia($payment->lda_other_charges),
                    'net_payment_amount' => $netPaymentAmount,
                    'net_payment_amount_formatted' => html_entity_decode($paymentCurrencySymbol) . ' ' . moneyFormatIndia($netPaymentAmount),
                    'exchange_rate' => (float) ($payment->exchange_rate ?? 1),
                    'payment_date' => $payment->payment_date,
                    'payment_date_formatted' => date_format_india($payment->payment_date),
                    'payment_method' => $payment->payment_method ?? '',
                    'transaction_id' => $payment->transaction_id ?? '',
                    'bank_id' => $payment->bank_id ?? '',
                    'payment_type' => $payment->payment_type ?? '',
                    'tds_check_on_portal' => (int) $payment->tds_check_on_portal,
                    'tds_verified' => $payment->tds_check_on_portal == 1,
                    'remarks' => $payment->remarks ?? '',
                    'created_date' => $payment->created_date ?? '',
                    'created_date_formatted' => $payment->created_date ? date_format_india($payment->created_date) : '',
                    'currency_code' => $invoice->currency_code,
                    'currency_symbol' => html_entity_decode($paymentCurrencySymbol)
                ];
            }
            
            // Calculate balance considering all payment components
            $totalPaymentReceived = $invoiceTotalPaid + $invoiceTotalTds + $invoiceTotalCreditNote + $invoiceTotalLdaOther;
            $balanceAmount = $invoice->invoice_amount - $totalPaymentReceived;
            
            // Convert to default currency if needed
            $valueInDefaultCurrency = ($invoice->currency_code != $defaultCurrencyCode) 
                ? $invoice->invoice_amount * $exchangeRate 
                : $invoice->invoice_amount;
            
            $paidInDefaultCurrency = ($invoice->currency_code != $defaultCurrencyCode) 
                ? $totalPaymentReceived * $exchangeRate 
                : $totalPaymentReceived;
                
            $dueInDefaultCurrency = ($invoice->currency_code != $defaultCurrencyCode) 
                ? $balanceAmount * $exchangeRate 
                : $balanceAmount;
            
            // TDS/TCS component conversions
            $tdsInDefaultCurrency = ($invoice->currency_code != $defaultCurrencyCode) 
                ? $invoiceTotalTds * $exchangeRate 
                : $invoiceTotalTds;
                
            $creditNoteInDefaultCurrency = ($invoice->currency_code != $defaultCurrencyCode) 
                ? $invoiceTotalCreditNote * $exchangeRate 
                : $invoiceTotalCreditNote;
                
            $ldaOtherInDefaultCurrency = ($invoice->currency_code != $defaultCurrencyCode) 
                ? $invoiceTotalLdaOther * $exchangeRate 
                : $invoiceTotalLdaOther;
            
            // Update totals
            $totalInvoiceAmount += $valueInDefaultCurrency;
            $totalPaidAmount += $paidInDefaultCurrency;
            $totalDueAmount += $dueInDefaultCurrency;
            $totalTdsAmount += $tdsInDefaultCurrency;
            $totalCreditNoteAmount += $creditNoteInDefaultCurrency;
            $totalLdaOtherAmount += $ldaOtherInDefaultCurrency;
            $totalPaymentReceivedAmount += $paidInDefaultCurrency; // This includes all components
            
            // Format aging display
            $agingDisplay = '';
            $daysOverdue = 0;
            if ($invoice->aging > 0) {
                $daysOverdue = $invoice->aging;
                $agingDisplay = $daysOverdue . " DAYS OVERDUE";
            } else {
                $agingDays = $invoice->aging ?? 0;
                $agingDisplay = "Due in " . abs($agingDays) . " days";
            }
            
            $processedInvoices[] = [
                'invoice_id' => $invoice->invoice_id,
                'po_id' => $invoice->po_id,
                'vendor_id' => $invoice->vendor_id,
                'vendor_name' => $vendorName,
                'payment_terms' => $paymentTermsName,
                'term_delivery' => $invoice->Term_Delivery,
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => $invoice->invoice_date,
                'invoice_date_formatted' => date_format_india($invoice->invoice_date),
                'due_on' => $invoice->due_on,
                'due_date_formatted' => date_format_india($invoice->due_on),
                'po_date' => $invoice->po_date,
                'po_date_formatted' => date_format_india($invoice->po_date),
                'currency_code' => $invoice->currency_code,
                'currency_symbol' => html_entity_decode($currencySymbol),
                'invoice_amount' => (float) $invoice->invoice_amount,
                'invoice_amount_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($invoice->invoice_amount),
                'total_paid' => $invoiceTotalPaid,
                'total_paid_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($invoiceTotalPaid),
                'total_payment_received' => $totalPaymentReceived,
                'total_payment_received_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($totalPaymentReceived),
                'balance_amount' => $balanceAmount,
                'balance_amount_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($balanceAmount),
                'aging' => $invoice->aging,
                'days_overdue' => $daysOverdue,
                'aging_display' => $agingDisplay,
                'is_overdue' => $invoice->aging > 0,
                'is_fully_paid' => $balanceAmount <= 0,
                'payment_status' => $balanceAmount <= 0 ? 'Paid' : ($invoice->aging > 0 ? 'Overdue' : 'Pending'),
                'status' => $invoice->status,
                'value_in_default_currency' => $valueInDefaultCurrency,
                // TDS/TCS Summary for this invoice
                'tds_summary' => [
                    'total_tds_amount' => $invoiceTotalTds,
                    'total_tds_amount_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($invoiceTotalTds),
                    'total_credit_note' => $invoiceTotalCreditNote,
                    'total_credit_note_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($invoiceTotalCreditNote),
                    'total_lda_other' => $invoiceTotalLdaOther,
                    'total_lda_other_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($invoiceTotalLdaOther),
                    'tds_amount_default_currency' => $tdsInDefaultCurrency,
                    'credit_note_default_currency' => $creditNoteInDefaultCurrency,
                    'lda_other_default_currency' => $ldaOtherInDefaultCurrency,
                    'has_tds' => $invoiceTotalTds > 0,
                    'has_credit_note' => $invoiceTotalCreditNote > 0,
                    'has_lda_other' => $invoiceTotalLdaOther > 0,
                    'payment_breakdown' => [
                        'direct_payment' => $invoiceTotalPaid,
                        'tds_deducted' => $invoiceTotalTds,
                        'credit_note_adjusted' => $invoiceTotalCreditNote,
                        'lda_other_charges' => $invoiceTotalLdaOther,
                        'total_settlement' => $totalPaymentReceived
                    ]
                ],
                // Buyer details from vendor_po_final
                'buyer_details' => [
                    'buyer_company_name' => $invoice->buyer_company_name ?? '',
                    'buyer_contact_name' => $invoice->buyer_contact_name ?? '',
                    'buyer_email' => $invoice->buyer_email ?? '',
                    'buyer_telephone' => $invoice->buyer_telephone ?? '',
                    'buyer_mobile' => $invoice->buyer_mobile ?? '',
                    'buyer_address' => $invoice->buyer_address ?? '',
                    'buyer_city' => $invoice->buyer_city ?? '',
                    'buyer_state' => $invoice->buyer_state ?? '',
                    'buyer_country' => $invoice->buyer_country ?? '',
                    'buyer_pincode' => $invoice->buyer_pincode ?? '',
                    'buyer_gst_no' => $invoice->buyer_gst_no ?? ''
                ],
                // PO-level payment totals (amount_paid as per UI) with TDS breakdown
                'po_amount_paid' => isset($poPaymentTotals[$invoice->po_id]) ? $poPaymentTotals[$invoice->po_id]['total_amount_paid'] : 0,
                'po_amount_paid_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia(isset($poPaymentTotals[$invoice->po_id]) ? $poPaymentTotals[$invoice->po_id]['total_amount_paid'] : 0),
                'po_payment_count' => isset($poPaymentTotals[$invoice->po_id]) ? $poPaymentTotals[$invoice->po_id]['payment_count'] : 0,
                // PO-level TDS summary
                'po_tds_summary' => isset($poPaymentTotals[$invoice->po_id]) ? [
                    'total_tds_amount' => $poPaymentTotals[$invoice->po_id]['total_tds_amount'],
                    'total_tds_amount_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($poPaymentTotals[$invoice->po_id]['total_tds_amount']),
                    'total_credit_note' => $poPaymentTotals[$invoice->po_id]['total_credit_note'],
                    'total_credit_note_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($poPaymentTotals[$invoice->po_id]['total_credit_note']),
                    'total_lda_other' => $poPaymentTotals[$invoice->po_id]['total_lda_other'],
                    'total_lda_other_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($poPaymentTotals[$invoice->po_id]['total_lda_other']),
                    'total_payment_received' => $poPaymentTotals[$invoice->po_id]['total_payment_received'],
                    'total_payment_received_formatted' => html_entity_decode($currencySymbol) . ' ' . moneyFormatIndia($poPaymentTotals[$invoice->po_id]['total_payment_received']),
                    'tds_verified_count' => $poPaymentTotals[$invoice->po_id]['tds_verified_count'],
                    'has_tds' => $poPaymentTotals[$invoice->po_id]['has_tds'],
                    'has_credit_note' => $poPaymentTotals[$invoice->po_id]['has_credit_note'],
                    'has_lda_other' => $poPaymentTotals[$invoice->po_id]['has_lda_other']
                ] : [
                    'total_tds_amount' => 0,
                    'total_tds_amount_formatted' => html_entity_decode($currencySymbol) . ' 0.00',
                    'total_credit_note' => 0,
                    'total_credit_note_formatted' => html_entity_decode($currencySymbol) . ' 0.00',
                    'total_lda_other' => 0,
                    'total_lda_other_formatted' => html_entity_decode($currencySymbol) . ' 0.00',
                    'total_payment_received' => 0,
                    'total_payment_received_formatted' => html_entity_decode($currencySymbol) . ' 0.00',
                    'tds_verified_count' => 0,
                    'has_tds' => false,
                    'has_credit_note' => false,
                    'has_lda_other' => false
                ],
                'payments' => $processedPayments,
                'payment_count' => count($processedPayments)
            ];
        }
        
        // Collect comprehensive vendor details from vendor_master
        $vendorDetailsMap = [];
        $vendorIds = array_unique(array_column($processedInvoices, 'vendor_id'));
        
        foreach ($vendorIds as $vId) {
            // Get comprehensive vendor details from vendor_master table
            $vendorMaster = DB::table('vendor_master')
                ->select([
                    'ID as vendor_id',
                    'C_Name as vendor_company_name',
                    'Contact_1 as vendor_contact_person',
                    'Email as vendor_email',
                    'Number as vendor_phone',
                    'sales_mobile as vendor_mobile',
                    'AddressName as vendor_address',
                    'city as vendor_city',
                    'state as vendor_state',
                    'Country as vendor_country',
                    'pincode as vendor_pincode',
                    'gst_no as vendor_gst_no',
                    'bank_name as vendor_bank_name',
                    'account_number as vendor_bank_account_no',
                    'ifsc_code as vendor_bank_ifsc',
                    'status as vendor_status',
                    'Currency as vendor_currency',
                    'purchase_type as vendor_category',
                    'support_name as vendor_website',
                    'support_email as vendor_remarks'
                ])
                ->where('ID', $vId)
                ->where('deleteflag', 'active')
                ->first();
            
            // Get invoice info for this vendor
            $vendorInvoices = array_filter($processedInvoices, function($inv) use ($vId) {
                return $inv['vendor_id'] == $vId;
            });
            $firstInvoice = reset($vendorInvoices);
            
            $vendorDetailsMap[] = [
                'vendor_id' => $vId,
                'vendor_company_name' => $vendorMaster->vendor_company_name ?? $firstInvoice['vendor_name'],
                'vendor_contact_person' => $vendorMaster->vendor_contact_person ?? '',
                'vendor_email' => $vendorMaster->vendor_email ?? '',
                'vendor_phone' => $vendorMaster->vendor_phone ?? '',
                'vendor_mobile' => $vendorMaster->vendor_mobile ?? '',
                'vendor_address' => $vendorMaster->vendor_address ?? '',
                'vendor_city' => $vendorMaster->vendor_city ?? '',
                'vendor_state' => $vendorMaster->vendor_state ?? '',
                'vendor_country' => $vendorMaster->vendor_country ?? '',
                'vendor_pincode' => $vendorMaster->vendor_pincode ?? '',
                'vendor_gst_no' => $vendorMaster->vendor_gst_no ?? '',
                'vendor_pan_no' => $vendorMaster->vendor_pan_no ?? '',
                'vendor_bank_name' => $vendorMaster->vendor_bank_name ?? '',
                'vendor_bank_account_no' => $vendorMaster->vendor_bank_account_no ?? '',
                'vendor_bank_ifsc' => $vendorMaster->vendor_bank_ifsc ?? '',
                'vendor_status' => $vendorMaster->vendor_status ?? '',
                'vendor_rating' => $vendorMaster->vendor_rating ?? '',
                'vendor_category' => $vendorMaster->vendor_category ?? '',
                'vendor_website' => $vendorMaster->vendor_website ?? '',
                'vendor_remarks' => $vendorMaster->vendor_remarks ?? '',
                'payment_terms' => $firstInvoice['payment_terms'],
                'term_delivery' => $firstInvoice['term_delivery'],
                'invoice_count' => count($vendorInvoices),
                'currencies_used' => array_unique(array_column($vendorInvoices, 'currency_code')),
                'total_invoice_amount' => array_sum(array_column($vendorInvoices, 'value_in_default_currency')),
                'total_balance_amount' => array_sum(array_column($vendorInvoices, 'balance_amount'))
            ];
        }
        
        // Remove vendor details from invoices to avoid duplication
        $cleanedInvoices = array_map(function($invoice) {
            unset($invoice['vendor_name']);
            unset($invoice['payment_terms']);
            unset($invoice['term_delivery']);
            return $invoice;
        }, $processedInvoices);
        
        // Calculate pagination info
        $totalPages = ceil($totalCount / $records);
        $pagination = [
            'current_page' => $pageNo,
            'records_per_page' => $records,
            'total_records' => $totalCount,
            'total_pages' => $totalPages,
            'has_next_page' => $pageNo < $totalPages,
            'has_previous_page' => $pageNo > 1,
            'next_page' => $pageNo < $totalPages ? $pageNo + 1 : null,
            'previous_page' => $pageNo > 1 ? $pageNo - 1 : null,
            'showing_records' => [
                'from' => $totalCount > 0 ? $offset + 1 : 0,
                'to' => min($offset + $records, $totalCount),
                'total' => $totalCount
            ]
        ];
        
        return response()->json([
            'status' => 'success',
            'message' => 'Purchase order payment details retrieved successfully',
            'data' => [
                'vendors' => $vendorDetailsMap,
                'invoices' => $cleanedInvoices,
                'pagination' => $pagination,
                'summary' => [
                    'total_invoice_amount' => $totalInvoiceAmount,
                    'total_invoice_amount_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalInvoiceAmount),
                    'total_paid_amount' => $totalPaidAmount,
                    'total_paid_amount_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalPaidAmount),
                    'total_due_amount' => $totalDueAmount,
                    'total_due_amount_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalDueAmount),
                    // TDS/TCS Global Summary
                    'tds_global_summary' => [
                        'total_tds_amount' => $totalTdsAmount,
                        'total_tds_amount_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalTdsAmount),
                        'total_credit_note_amount' => $totalCreditNoteAmount,
                        'total_credit_note_amount_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalCreditNoteAmount),
                        'total_lda_other_amount' => $totalLdaOtherAmount,
                        'total_lda_other_amount_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalLdaOtherAmount),
                        'total_payment_received_amount' => $totalPaymentReceivedAmount,
                        'total_payment_received_amount_formatted' => html_entity_decode($defaultCurrencySymbol) . ' ' . moneyFormatIndia($totalPaymentReceivedAmount),
                        'tds_percentage_of_total' => $totalInvoiceAmount > 0 ? round(($totalTdsAmount / $totalInvoiceAmount) * 100, 2) : 0,
                        'payment_breakdown_percentage' => [
                            'direct_payment_percentage' => $totalPaymentReceivedAmount > 0 ? round((($totalPaidAmount - $totalTdsAmount - $totalCreditNoteAmount - $totalLdaOtherAmount) / $totalPaymentReceivedAmount) * 100, 2) : 0,
                            'tds_percentage' => $totalPaymentReceivedAmount > 0 ? round(($totalTdsAmount / $totalPaymentReceivedAmount) * 100, 2) : 0,
                            'credit_note_percentage' => $totalPaymentReceivedAmount > 0 ? round(($totalCreditNoteAmount / $totalPaymentReceivedAmount) * 100, 2) : 0,
                            'lda_other_percentage' => $totalPaymentReceivedAmount > 0 ? round(($totalLdaOtherAmount / $totalPaymentReceivedAmount) * 100, 2) : 0
                        ],
                        'invoices_with_tds_count' => count(array_filter($cleanedInvoices, function($inv) { return $inv['tds_summary']['has_tds']; })),
                        'invoices_with_credit_note_count' => count(array_filter($cleanedInvoices, function($inv) { return $inv['tds_summary']['has_credit_note']; })),
                        'invoices_with_lda_other_count' => count(array_filter($cleanedInvoices, function($inv) { return $inv['tds_summary']['has_lda_other']; }))
                    ],
                    'currency_code' => $defaultCurrencyCode,
                    'currency_symbol' => html_entity_decode($defaultCurrencySymbol),
                    'invoice_count_current_page' => count($cleanedInvoices),
                    'invoice_count_total' => $totalCount,
                    'vendor_count' => count($vendorDetailsMap),
                    'fully_paid_count' => count(array_filter($cleanedInvoices, function($inv) { return $inv['is_fully_paid']; })),
                    'overdue_count' => count(array_filter($cleanedInvoices, function($inv) { return $inv['is_overdue']; }))
                ],
                'filters_applied' => [
                    'po_id' => $poId,
                    'invoice_id' => $invoiceId,
                    'vendor_id' => $vendorId,
                    'pageno' => $pageNo,
                    'records' => $records
                ]
            ],
            'generated_at' => now()->toISOString()
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Error retrieving purchase order payment details: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

/**
 * Get "Not yet due" summary that matches the UI filter requirements
 * This combines all future payments similar to your screenshot
 */
public function notYetDueSummary(Request $request)
{
    try {
        $accManager    = $request->input('acc_manager', 0);
        $companyName   = $request->input('company_name', null);
        $vendorId      = $request->input('vendor_id', null);
        $financialYear = $request->input('financial_year', null);

        // Get default currency information
        $defaultCurrency = DB::table('tbl_currencies')
            ->select('currency_html_code', 'currency_css_symbol', 'currency_code')
            ->where('currency_super_default', 'yes')
            ->where('deleteflag', 'active')
            ->first();

        $defaultCurrencySymbol = $defaultCurrency->currency_css_symbol ?? '₹';
        $defaultCurrencyCode = $defaultCurrency->currency_code ?? 'INR';

        // Base query for all outstanding invoices
        $baseQuery = DB::table('vendor_po_final as vpf')
            ->join('vendor_po_invoice_new as vpi', 'vpf.PO_ID', '=', 'vpi.po_id')
            ->leftJoin('tbl_currencies as c', 'vpf.Flag', '=', 'c.currency_code')
            ->where('vpi.status', '1'); // Only pending/outstanding invoices

        // Apply vendor filter if provided
        $vendorFilter = $vendorId ?: $companyName;
        if (!empty($vendorFilter)) {
            $baseQuery->where('vpi.vendor_id', $vendorFilter);
        }

        // Apply financial year filter
        if (!empty($financialYear)) {
            if (preg_match('/^(\d{4})-(\d{4})$/', $financialYear, $matches)) {
                $startYear = $matches[1];
                $endYear = $matches[2];
                
                $fyStart = $startYear . '-04-01';
                $fyEnd = $endYear . '-03-31';
                
                $baseQuery->whereBetween('vpi.due_on', [$fyStart, $fyEnd]);
            }
        } else {
            // Calculate current financial year automatically
            $today = Carbon::now();
            
            if ($today->month >= 4) {
                $fyStartYear = $today->year;
                $fyEndYear = $today->year + 1;
            } else {
                $fyStartYear = $today->year - 1;
                $fyEndYear = $today->year;
            }
            
            $fyStart = $fyStartYear . '-04-01';
            $fyEnd = $fyEndYear . '-03-31';
            $financialYear = $fyStartYear . '-' . $fyEndYear;
            
            $baseQuery->whereBetween('vpi.due_on', [$fyStart, $fyEnd]);
        }

        // "Not yet due" means all invoices with negative or zero aging (future due dates + due today)
        $baseQuery->whereRaw("DATEDIFF(CURDATE(), vpi.due_on) <= 0");

        // Calculate the total amount
        $totalNotYetDueAmount = $baseQuery->sum(DB::raw('DISTINCT(vpi.value * COALESCE(c.currency_value, 1))'));
        $totalNotYetDueAmount = (float) $totalNotYetDueAmount;

        // Get count of invoices
        $totalInvoiceCount = $baseQuery->count();

        return response()->json([
            'status' => 'success',
            'message' => 'Not yet due summary retrieved successfully',
            'data' => [
                'filter_type' => 'not_yet_due',
                'label' => 'Not yet due',
                'description' => 'All invoices with due dates in the future or due today',
                'total_amount' => $totalNotYetDueAmount,
                'formatted_amount' => $defaultCurrencySymbol . number_format($totalNotYetDueAmount, 2),
                'invoice_count' => $totalInvoiceCount,
                'currency_code' => $defaultCurrencyCode,
                'currency_symbol' => $defaultCurrencySymbol,
                'filter_criteria' => 'DATEDIFF(CURDATE(), due_date) <= 0',
                'financial_year' => $financialYear,
                'vendor_filter' => $vendorFilter ?? 'All vendors',
                'ui_styling' => [
                    'iconBgColor' => '#E8F5E8',
                    'iconText' => 'NYD',
                    'iconTextColor' => '#28a745',
                    'iconOutlineColor' => '#28a745',
                    'backgroundColor' => '#f8fff8',
                    'color_class' => 'text-success'
                ]
            ],
            'generated_at' => now()->toISOString()
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Error retrieving not yet due summary: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

/**
 * Get payment types listing for dropdown/selection purposes
 * Used for "Payment paid via" dropdown options
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function getPaymentTypes(Request $request)
{
    try {
        // Get query parameters
        $status = $request->input('status', 'active');
        $search = $request->input('search', '');
        
        // Build query
        $query = DB::table('tbl_payment_type_master')
            ->select([
                'payment_type_id',
                'payment_type_name',
                'payment_type_abbrv',
                'payment_type_description',
                'payment_type_status',
                'date_added'
            ])
            ->where('deleteflag', 'active');
        
        // Apply status filter
        if ($status !== 'all') {
            $query->where('payment_type_status', $status);
        }
        
        // Apply search filter
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('payment_type_name', 'LIKE', "%{$search}%")
                  ->orWhere('payment_type_abbrv', 'LIKE', "%{$search}%")
                  ->orWhere('payment_type_description', 'LIKE', "%{$search}%");
            });
        }
        
        // Get results ordered by name
        $paymentTypes = $query->orderBy('payment_type_name', 'ASC')->get();
        
        // Process the results
        $processedPaymentTypes = [];
        foreach ($paymentTypes as $paymentType) {
            $processedPaymentTypes[] = [
                'id' => $paymentType->payment_type_id,
                'name' => $paymentType->payment_type_name,
                'abbreviation' => $paymentType->payment_type_abbrv ?? '',
                'description' => $paymentType->payment_type_description ?? '',
                'status' => $paymentType->payment_type_status,
                'date_added' => $paymentType->date_added,
                'date_added_formatted' => date_format_india($paymentType->date_added),
                // For dropdown usage
                'value' => $paymentType->payment_type_id,
                'label' => $paymentType->payment_type_name,
                'display_text' => $paymentType->payment_type_name . ($paymentType->payment_type_abbrv ? ' (' . $paymentType->payment_type_abbrv . ')' : '')
            ];
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Payment types retrieved successfully',
            'data' => [
                'payment_types' => $processedPaymentTypes,
                'total_count' => count($processedPaymentTypes),
                'filters_applied' => [
                    'status' => $status,
                    'search' => $search
                ]
            ],
            'generated_at' => now()->toISOString()
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Error retrieving payment types: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

/**
 * Get disputed amounts by aging range
 * Similar to pendingAccountPayablesByAging but for disputed amounts
 * GET /api/accounts/finance/disputed-amounts-by-aging
 */
public function disputedAmountsByAging(Request $request)
{
    $accManager    = $request->input('acc_manager', 0);
    $agingMin      = $request->input('aging_min', 0);
    $agingMax      = $request->input('aging_max', null);
    $companyName   = $request->input('company_name', null);
    $vendorId      = $request->input('vendor_id', null);
    $financialYear = $request->input('financial_year', null); // e.g., "2024-2025"

    // If no FY given → use current FY
    if (empty($financialYear)) {
        $today = Carbon::now();
        $fyStart = ($today->month >= 4)
            ? Carbon::create($today->year, 4, 1)->startOfDay()
            : Carbon::create($today->year - 1, 4, 1)->startOfDay();

        $fyEnd = $fyStart->copy()->addYear()->subDay()->endOfDay();
    } else {
        // Parse FY string like "2024-2025"
        [$startYear, $endYear] = explode('-', $financialYear);

        $fyStart = Carbon::createFromDate((int)$startYear, 4, 1)->startOfDay();
        $fyEnd   = Carbon::createFromDate((int)$endYear, 3, 31)->endOfDay();
    }

    $startDate = $fyStart->toDateString();
    $endDate   = $fyEnd->toDateString();

    // Query disputed amounts from vendor_payment_disputes table
    // Use the same pattern as VendorPaymentDisputeController for currency handling
    $query = DB::table('tbl_vendor_payment_disputes as vpd')
        ->join('vendor_po_invoice_new as vpi', 'vpd.vendor_invoice_id', '=', 'vpi.id')
        ->join('vendor_master as vm', 'vpi.vendor_id', '=', 'vm.ID')
        ->leftJoin('tbl_currencies as c', 'vm.Currency', '=', 'c.currency_code')
        ->selectRaw('
            DATEDIFF(CURDATE(), vpi.due_on) as aging,
            vpi.vendor_id,
            vpd.disputed_amount,
            vm.Currency as vendor_currency,
            COALESCE(c.currency_value, 1) as exchange_rate,
            vpd.disputed_amount * COALESCE(c.currency_value, 1) as converted_amount
        ')
        ->where('vpd.dispute_status', 'active')
        ->where('vpi.status', '1');

    // Aging filter - calculate aging based on dispute creation date vs due date
    if (!empty($agingMax)) {
        $query->whereRaw("DATEDIFF(CURDATE(), vpi.due_on) BETWEEN ? AND ?", [$agingMin, $agingMax]);
    } else {
        $query->whereRaw("DATEDIFF(CURDATE(), vpi.due_on) >= ?", [$agingMin]);
    }

    // Company/Vendor filter
    $vendorFilter = $vendorId ?: $companyName;
    if (!empty($vendorFilter)) {
        $query->where('vpi.vendor_id', $vendorFilter);
    }

    // Always apply FY filter based on dispute creation date
    $query->whereBetween('vpd.created_at', [$startDate, $endDate]);

    $results = $query->get();
    
    // Calculate total disputed amount and get currency info
    $totalDisputed = $results->sum('converted_amount');
    
    // Get currency information from the most recent result (largest aging)
    $latestResult = $results->sortByDesc('aging')->first();
    $currencyInfo = null;
    
    if ($latestResult) {
        // Get proper currency symbol from currency table
        $currencyData = DB::table('tbl_currencies')
            ->where('currency_code', $latestResult->vendor_currency)
            ->first();
            
        $currencyInfo = [
            'currency_code' => $latestResult->vendor_currency ?? 'INR',
            'currency_symbol' => $currencyData->currency_css_symbol ?? ($latestResult->vendor_currency == 'INR' ? '₹' : $latestResult->vendor_currency),
            'flag' => $latestResult->vendor_currency ?? 'INR',
            'aging_days' => $latestResult->aging ?? 0
        ];
    } else {
        // Default currency info when no results
        $currencyInfo = [
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'flag' => 'INR',
            'aging_days' => 0
        ];
    }

    return response()->json([
        'total_disputed'  => (float) $totalDisputed,
        'total_formatted' => currencySymbolDefault(1) . ' ' . number_format($totalDisputed, 2),
        'aging_min'       => $agingMin,
        'aging_max'       => $agingMax,
        'vendor_id'       => $vendorId,
        'company_name'    => $companyName,
        'vendor_filter'   => $vendorFilter ?? null,
        'financial_year'  => $financialYear ?? ($fyStart->year . '-' . $fyEnd->year),
        'fy_start'        => $startDate,
        'fy_end'          => $endDate,
        'currency_info'   => $currencyInfo
    ]);
}

}
