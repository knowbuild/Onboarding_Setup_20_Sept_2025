<?php

namespace App\Http\Controllers\SalesManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\{
    TaxInvoice,
    InvoiceProduct,
    CreditNoteInvoiceProduct,
    TesManager,
    Tes,
    Order,
    Application, 
    ApplicationService,
    Service,
    ServicesEntry,
    ProductMain, 
    ProductsEntry,
    OrderProduct,
    CustSegment,
    EnqSource,
    FinancialYear,
    TaxCreditNoteInvoice,
    Event,
    IndexG2,
    IndexS2,
    Lead,
    Company,
    
    User, 
    FiscalMonth,
};

use Carbon\Carbon;
use DB;

class TargetSummaryController extends Controller
{
   public function productCategoryAchieved(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'financial_year' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $accountManager = $request->filled('account_manager') 
            ? explode(',', $request->account_manager) 
            : null;

        [$startDate, $endDate] = $this->getDateRangeFromFinancialYear($request->financial_year, $request->month);

        $invoices = $this->fetchInvoices($startDate, $endDate, $accountManager);
        $creditNotes = $this->fetchCreditNotes($startDate, $endDate, $accountManager);

        if ($invoices->isEmpty() && $creditNotes->isEmpty() && $accountManager) {
            $invoices = $this->fetchInvoices($startDate, $endDate);
            $creditNotes = $this->fetchCreditNotes($startDate, $endDate);
        }

        $invoiceCategoryPrices = collect();
        foreach ($invoices as $invoice) {
            foreach ($invoice->products as $product) {
                $category = $this->getCategoryName($invoice->invoice_type, $product);
                $price = $product->quantity * $product->price * $invoice->exchange_rate;
                $invoiceCategoryPrices[$category] = ($invoiceCategoryPrices[$category] ?? 0) + $price;
            }
        }

        $creditNoteCategoryPrices = collect();
        foreach ($creditNotes as $creditNote) {
            foreach ($creditNote->products as $product) {
                $category = $this->getCategoryName($creditNote->invoice_type, $product);
                $price = $product->quantity * $product->price * $creditNote->exchange_rate;
                $creditNoteCategoryPrices[$category] = ($creditNoteCategoryPrices[$category] ?? 0) + $price;
            }
        }

        $allCategories = $invoiceCategoryPrices->keys()
            ->merge($creditNoteCategoryPrices->keys())
            ->unique();

        $finalDataTemp = $allCategories->map(function ($category) use ($invoiceCategoryPrices, $creditNoteCategoryPrices) {
            $gross = $invoiceCategoryPrices[$category] ?? 0;
            $credit = $creditNoteCategoryPrices[$category] ?? 0;
            $net = $gross - $credit;
            $percentage = $gross > 0 ? round(($net / $gross) * 100, 2) : 0;

            return compact('category', 'gross', 'credit', 'net', 'percentage') + [
                'category_name' => $category,
                'gross_price' => $gross,
                'credit_note' => $credit,
                'net_price' => $net,
                'price_percentage' => $percentage,
            ];
        });

        $finalDataSumPrice = $finalDataTemp->sum('net_price');

        $finalData = $finalDataTemp->map(function ($item) use ($finalDataSumPrice) {
            $totalPercentage = ($item['net_price'] > 0 && $finalDataSumPrice > 0)
                ? round(($item['net_price'] * 100) / $finalDataSumPrice, 2)
                : 0;

            return $item + ['total_price_percentage' => $totalPercentage];
        })->sortByDesc('net_price')->values();

        return response()->json([
            'status' => $finalData->isNotEmpty() ? 'success' : 'no_data',
            'message' => $finalData->isNotEmpty()
                ? 'Product Category is listed here successfully.'
                : 'No Product Category found.',
            'data' => $finalData,
            'finalDataSumPrice' => $finalDataSumPrice,
        ]);
    }

    public function customerSegment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'financial_year' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $accountManager = $request->filled('account_manager') 
            ? explode(',', $request->account_manager) 
            : null;

        [$startDate, $endDate] = $this->getDateRangeFromFinancialYear($request->financial_year, $request->month);

        $segmentData = TaxInvoice::with(['order.lead.customerSegment'])
            ->whereBetween('invoice_generated_date', [$startDate, $endDate])
            ->where('invoice_status', 'approved')
            ->when($accountManager, fn($q) => $q->whereIn('prepared_by', $accountManager))
            ->get()
            ->groupBy(fn($inv) => optional($inv->order->lead->customerSegment)->cust_segment_name ?? 'Others')
            ->map(function ($items, $segmentName) {
                $total = $items->sum(fn($i) => $i->sub_total_amount_without_gst * $i->exchange_rate);
                return ['segment_name' => $segmentName, 'total_price' => $total];
            })
            ->sortByDesc('total_price')
            ->values()
            ->take(50);

        return response()->json([
            'status' => $segmentData->isNotEmpty() ? 'success' : 'no_data',
            'message' => $segmentData->isNotEmpty()
                ? 'Customer Segment is listed here successfully.'
                : 'No Customer Segment found.',
            'data' => $segmentData,
        ]);
    }


 public function assignedEnquiryConversionFunnel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'financial_year' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $financialYearId = $request->financial_year;
        $month = $request->month;
        $accountManagers = $this->explodeAccountManager($request->account_manager);

        [$startDate, $endDate] = $this->getDateRangeFromFinancialYear($financialYearId, $month);

        // Generate Month Labels
        $months = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $months[] = $current->format('F Y');
            $current->addMonth();
        }

        // Get TES target
        $target = TesManager::approved()
            ->active()
            ->where('financial_year', $financialYearId)
            ->when(!empty($accountManagers), fn($q) => $q->whereIn('account_manager', $accountManagers))
            ->sum('actual_target');

        // Get Invoices and Credit Notes
        $invoices = $this->fetchInvoices($startDate, $endDate, $accountManagers);
        $creditNotes = $this->fetchCreditNotes($startDate, $endDate, $accountManagers);

        $achievedAmount = $invoices->sum(fn($i) => $i->sub_total_amount_without_gst * $i->exchange_rate);
        $creditNoteAmount = $creditNotes->sum(fn($c) => $c->sub_total_amount_without_gst * $c->exchange_rate);
        $achieved = $achievedAmount - $creditNoteAmount;

        $achieved_target_per = $target > 0
            ? min(100, number_format(($achieved / $target) * 100, 2)) . '%'
            : ($achieved > 0 ? '100%' : '0%');

        $invoiceCount = $invoices->pluck('invoice_id')->unique()->count();

        // Monthly Analysis
        $invoiceCountByMonths = [];
        $achievedByMonths = [];
        $achieved_target_perByMonths = [];
        $achieved_target_perBycumulative = [];

        $currentAchieved = 0;
        $monthlyAchievedSum = 0;

        foreach ($months as $monthLabel) {
            [$monthName, $year] = explode(' ', $monthLabel);
            $monthStart = Carbon::parse("first day of $monthName $year");
            $monthEnd = $monthStart->copy()->endOfMonth();

            $monthlyInvoices = $this->fetchInvoices($monthStart, $monthEnd, $accountManagers);
            $monthlyCreditNotes = $this->fetchCreditNotes($monthStart, $monthEnd, $accountManagers);

            $monthlyAchieved = $monthlyInvoices->sum(fn($i) => $i->sub_total_amount_without_gst * $i->exchange_rate);
            $monthlyCreditNote = $monthlyCreditNotes->sum(fn($c) => $c->sub_total_amount_without_gst * $c->exchange_rate);
            $netMonthlyAchieved = $monthlyAchieved - $monthlyCreditNote;

            $monthlyAchievedSum += $netMonthlyAchieved;
        }

        // Reset target if missing
        if ($target == 0) {
            $target = $monthlyAchievedSum;
        }

        foreach ($months as $monthLabel) {
            [$monthName, $year] = explode(' ', $monthLabel);
            $monthStart = Carbon::parse("first day of $monthName $year");
            $monthEnd = $monthStart->copy()->endOfMonth();

            $monthlyInvoices = $this->fetchInvoices($monthStart, $monthEnd, $accountManagers);
            $monthlyCreditNotes = $this->fetchCreditNotes($monthStart, $monthEnd, $accountManagers);

            $monthlyAchieved = $monthlyInvoices->sum(fn($i) => $i->sub_total_amount_without_gst * $i->exchange_rate);
            $monthlyCreditNote = $monthlyCreditNotes->sum(fn($c) => $c->sub_total_amount_without_gst * $c->exchange_rate);
            $netMonthlyAchieved = $monthlyAchieved - $monthlyCreditNote;

            $monthlyInvoiceCount = $monthlyInvoices->pluck('invoice_id')->unique()->count();
            $currentAchieved += $netMonthlyAchieved;

            $monthlyPercentage = $target > 0
                ? number_format(min(100, ($netMonthlyAchieved / $target) * 100), 2) . '%'
                : ($netMonthlyAchieved > 0 ? '100%' : '0%');

            $cumulativePercentage = $target > 0
                ? number_format(min(100, ($currentAchieved / $target) * 100), 2) . '%'
                : ($currentAchieved > 0 ? '100%' : '0%');

            $invoiceCountByMonths[$monthLabel] = $monthlyInvoiceCount;
            $achievedByMonths[$monthLabel] = $this->moneyFormatIndia($netMonthlyAchieved);
            $achieved_target_perByMonths[$monthLabel] = $monthlyPercentage;
            $achieved_target_perBycumulative[$monthLabel] = $cumulativePercentage;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Assigned Enquiry Conversion Funnel is listed here successfully.',
            'achieved' => $this->moneyFormatIndia($achieved),
            'target' => $this->moneyFormatIndia($target),
            'achieved_target_per' => $achieved_target_per,
            'credit_note' => $this->moneyFormatIndia($creditNoteAmount),
            'total_invoice' => $invoiceCount,
            'number_of_invoices_raised' => $invoiceCountByMonths,
            'amount_achieved' => $achievedByMonths,
            'per_target_achieved_monthly' => $achieved_target_perByMonths,
            'per_target_achieved_cumulative' => $achieved_target_perBycumulative,
            'months' => $months,
        ]);
    }












public function enquirySourceTrend(Request $request)
{
    $validator = Validator::make($request->all(), [
        'financial_year' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $financial_year = $request->financial_year;
    $month = $request->month;
    $accountManager = $this->explodeAccountManager($request->account_manager);

    [$startDate, $endDate] = $this->getDateRangeFromFinancialYear($financial_year, $month);

    // Generate month list
    $months = [];
    $current = $startDate->copy();
    while ($current <= $endDate) {
        $months[] = $current->format('F Y');
        $current->addMonth();
    }

    // Load invoice data
    $invoicesQuery = TaxInvoice::with(['order.lead'])
        ->whereBetween('invoice_generated_date', [$startDate, $endDate])
        ->where('invoice_status', 'approved');

    if (!empty($accountManager)) {
        $invoicesQuery->whereIn('prepared_by', $accountManager);
    }

    $allInvoices = $invoicesQuery->get();

    $invoicesBySource = $allInvoices->groupBy(fn($invoice) => optional($invoice->order->lead)->ref_source);
    $invoicesByMonth = $allInvoices->groupBy(fn($invoice) =>
        Carbon::parse($invoice->invoice_generated_date)->format('F Y')
    );

    // Active sources
    $sources = EnqSource::where('enq_source_status', 'active')
        ->where('deleteflag', 'active')
        ->get();

    $sourceMap = $sources->mapWithKeys(fn($source) =>
        [$source->enq_source_description => $source]
    );

    $totalAmount = $allInvoices->sum(fn($invoice) =>
        $invoice->sub_total_amount_without_gst * $invoice->exchange_rate
    );
    $yearly_target_achieved = max($totalAmount, 1); // prevent divide by zero

    // === Overall Source Summary ===
    $overallSourceData = [];
    foreach ($sourceMap as $ref_source => $source) {
        $matchingInvoices = $invoicesBySource->get($ref_source, collect());

        $price = $matchingInvoices->sum(fn($invoice) =>
            $invoice->sub_total_amount_without_gst * $invoice->exchange_rate
        );

        $overallSourceData[] = [
            'source' => $source->enq_source_name ?? 'Others',
            'source_icon' => "https://www.stanlay.in/" . ($source->enq_source_icon ?? 'images/dashboard/das-icon/targetsummary/phonein.png'),
            'enq_source_percentage' => round(($price * 100) / $yearly_target_achieved, 2) . '%',
            'total_price_raw' => $price,
            'total_price' => $this->moneyFormatIndia($price),
            'source_count' => $matchingInvoices->count(),
        ];
    }

    $sortedDataoverallSource = collect($overallSourceData)
        ->sortByDesc('total_price_raw')
        ->map(function ($item) {
            unset($item['total_price_raw']);
            return $item;
        })
        ->values();

    // === Monthly Breakdown ===
    $enquiry_sourceMonths = [];
    foreach ($months as $monthString) {
        $monthlyInvoices = $invoicesByMonth->get($monthString, collect());
        $monthData = [];

        foreach ($sourceMap as $ref_source => $source) {
            $sourceInvoices = $monthlyInvoices->filter(fn($invoice) =>
                optional($invoice->order->lead)->ref_source === $ref_source
            );

            $monthData[] = [
                'source' => $source->enq_source_name ?? 'Others',
                'source_count' => $sourceInvoices->count(),
            ];
        }

        $enquiry_sourceMonths[$monthString] = $monthData;
    }

    return response()->json([
        'status' => $sortedDataoverallSource->isNotEmpty() ? 'success' : 'no_data',
        'message' => $sortedDataoverallSource->isNotEmpty()
            ? 'Enquiry Source Trend is listed here successfully.'
            : 'No Enquiry Source Trend data found.',
        'enquiry_source' => $sortedDataoverallSource,
        'enquiry_source_months' => $enquiry_sourceMonths,
        'months' => $months,
    ], 200);
}



    
public function activity(Request $request)
{
    $validator = Validator::make($request->all(), [
        'financial_year' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $accountManager = $this->explodeAccountManager($request->account_manager);
    $financial_year = $request->financial_year;
    $month = $request->month;

    [$startDate, $endDate] = $this->getDateRangeFromFinancialYear($financial_year, $month);

    // Closure to fetch event count by type
    $getEventCount = function ($evttxt) use ($accountManager, $startDate, $endDate) {
        return Event::whereBetween('creation_date', [$startDate, $endDate])
            ->where('status', 'Completed')
            ->when(!empty($accountManager), fn($query) => $query->whereIn('account_manager', $accountManager))
            ->when(!empty($evttxt) && $evttxt !== '0', fn($query) => $query->where('evttxt', $evttxt))
            ->distinct('id')
            ->count('id');
    };

    $countOutsideVisit = $getEventCount('OSV');
    $countDemosConducted = $getEventCount('Demo');
    $countLocalVisit = $getEventCount('LV');
    $countCallsMade = $getEventCount('TFU');

    $countTotal = $countOutsideVisit + $countDemosConducted + $countLocalVisit + $countCallsMade;

    // Activity summary
    $activity = [
        [
            'name' => "Outside Visit",
            'total' => $countOutsideVisit,
            'percentage' => $this->calculatePercentage($countOutsideVisit, $countTotal),
            'icon' => "https://www.stanlay.in/crm/images/dashboard/das-icon/targetsummary/osv.png",
        ],
        [
            'name' => "Demos Conducted",
            'total' => $countDemosConducted,
            'percentage' => $this->calculatePercentage($countDemosConducted, $countTotal),
            'icon' => "https://www.stanlay.in/crm/images/dashboard/das-icon/targetsummary/demo.png",
        ],
        [
            'name' => "Local Visit",
            'total' => $countLocalVisit,
            'percentage' => $this->calculatePercentage($countLocalVisit, $countTotal),
            'icon' => "https://www.stanlay.in/crm/images/dashboard/das-icon/targetsummary/local-visit.png",
        ],
        [
            'name' => "Calls Made",
            'total' => $countCallsMade,
            'percentage' => $this->calculatePercentage($countCallsMade, $countTotal),
            'icon' => "https://www.stanlay.in/crm/images/dashboard/das-icon/targetsummary/phonein.png",
        ],
    ];

    // Common base query
    $baseQuery = Order::active()
        ->whereBetween('date_ordered', [$startDate, $endDate])
        ->where('orders_status', 'Pending')
        ->when(!empty($accountManager), fn($query) => $query->whereIn('order_by', $accountManager));

    $opportunities = (clone $baseQuery)->whereIn('offer_probability', [3, 4])->sum('total_order_cost');
    $offer = (clone $baseQuery)->where('offer_probability', 4)->sum('total_order_cost');

    return response()->json([
        'status' => 'success',
        'message' => 'Activity Count here successfully.',
        'activity' => $activity,
        'opportunities' => round($opportunities, 2),
        'offer' => round($offer, 2),
    ], 200);
}



public function distribution(Request $request)
{
    $request->validate([
        'financial_year' => 'required|integer',
    ]);

    $accountManager = $this->explodeAccountManager($request->account_manager);
    $financialYearId = $request->financial_year;
    $month = $request->month;

    // Get financial year dates
    [$startDate, $endDate] = $this->getDateRangeFromFinancialYear($financialYearId, $month);

    // Get TES data
    $tesIds = $accountManager
        ? $this->getTesId($accountManager, $financialYearId)
        : $this->getAllTesIds($financialYearId);

    $tesCompanyIds = $this->getTesCompanyIds($tesIds);

    // TES / Non-TES Achievements
    $tesAmount = $this->getTesAchievedAmount($accountManager, $startDate, $endDate, $tesCompanyIds);
    $nonTesAmount = $this->getNonTesAchievedAmount($accountManager, $startDate, $endDate, $tesCompanyIds);
    $totalAchieved = $tesAmount + $nonTesAmount;

    // Prepare response
    $response = [
        'status' => 'success',
        'message' => 'Distribution Count fetched successfully.',
        'distribution' => [
            [
                'name' => 'TES',
                'total' => $this->moneyFormatIndia($tesAmount),
                'percentage' => $this->calculatePercentage($tesAmount, $totalAchieved),
                'icon' => 'https://www.stanlay.in/crm/images/dashboard/das-icon/targetsummary/tes.png',
            ],
            [
                'name' => 'Other (Non TES)',
                'total' => $this->moneyFormatIndia($nonTesAmount),
                'percentage' => $this->calculatePercentage($nonTesAmount, $totalAchieved),
                'icon' => 'https://www.stanlay.in/crm/images/dashboard/das-icon/targetsummary/non-tes.png',
            ],
        ],
    ];

    return response()->json($response);
}


private function getTesId($accountManager, $financialYear)
{
    return TesManager::where([
        'account_manager' => $accountManager,
        'financial_year' => $financialYear,
        'status' => 'approved',
        'deleteflag' => 'active',
    ])->value('ID');
}

private function getAllTesIds($financialYear)
{
    return TesManager::where([
        'financial_year' => $financialYear,
        'status' => 'approved',
        'deleteflag' => 'active',
    ])->pluck('ID')->implode(',');
}

private function getTesCompanyIds($tesIds)
{
    return Tes::whereIn('tes_id', explode(',', $tesIds))
        ->distinct()
        ->pluck('comp_id')
        ->implode(',');
}

private function getTesAchievedAmount($accountManager, $startDate, $endDate, $tesCompanyIds)
{
    return (float) DB::table('tbl_order as o')
        ->join('tbl_delivery_order as tdo', 'o.orders_id', '=', 'tdo.O_Id')
        ->join('tbl_do_products as tdp', 'tdo.O_Id', '=', 'tdp.OID')
        ->join('tbl_delivery_challan', 'tdo.O_Id', '=', 'tbl_delivery_challan.O_Id')
        ->join('tble_invoice', 'tdo.O_Id', '=', 'tble_invoice.o_id')
        ->whereIn('o.customers_id', explode(',', $tesCompanyIds))
        ->whereBetween('tbl_delivery_challan.invoice_gen_date', [$startDate, $endDate])
        ->when(!empty($accountManager), fn($q) => $q->whereIn('o.order_by', $accountManager))
        ->whereIn('o.orders_status', ['Confirmed', 'Order Closed'])
        ->sum(DB::raw('tdp.Quantity * tdp.Price'));
}

private function getNonTesAchievedAmount($accountManager, $startDate, $endDate, $tesCompanyIds)
{
    return (float) DB::table('tbl_order as o')
        ->join('tbl_delivery_order as tdo', 'o.orders_id', '=', 'tdo.O_Id')
        ->join('tbl_do_products as tdp', 'tdo.O_Id', '=', 'tdp.OID')
        ->join('tbl_delivery_challan', 'tdo.O_Id', '=', 'tbl_delivery_challan.O_Id')
        ->join('tble_invoice', 'tdo.O_Id', '=', 'tble_invoice.o_id')
        ->whereNotIn('o.customers_id', explode(',', $tesCompanyIds))
        ->whereNotIn('o.tes_linked_customer_id', explode(',', $tesCompanyIds))
        ->whereBetween('tbl_delivery_challan.invoice_gen_date', [$startDate, $endDate])
        ->when(!empty($accountManager), fn($q) => $q->whereIn('o.order_by', $accountManager))
        ->whereIn('o.orders_status', ['Confirmed', 'Order Closed'])
        ->sum(DB::raw('tdp.Quantity * tdp.Price'));
}





 


public function topCustomer(Request $request)
{
    $validator = Validator::make($request->all(), [
        'financial_year' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $accountManager = $request->account_manager;
if (!empty($accountManager)) {
    $accountManager = explode(',', $accountManager);
}
    $financial_year = $request->financial_year;
    $month = $request->month;

    // Fetch year range from FinancialYear
    $financialYearRange = FinancialYear::where('fin_id', $financial_year)->value('fin_name');
    [$startYear, $endYear] = explode('-', $financialYearRange);

    // Get start and end dates based on month or full financial year
    if ($month) {
        $year = ($month >= 1 && $month <= 3) ? $endYear : $startYear;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
    } else {
        $startDate = Carbon::createFromDate($startYear, 4, 1)->startOfDay();
        $endDate = Carbon::createFromDate($endYear, 3, 31)->endOfDay();
    }

    // Get approved invoices within the time range
    $invoices = TaxInvoice::with('order')
        ->where('invoice_status', 'approved')
        ->whereBetween('invoice_generated_date', [$startDate, $endDate])
        ->when(!empty($accountManager), function ($q) use ( $accountManager) {
            $q->whereIn('prepared_by',  $accountManager);
        })
        ->get();

    // Group invoices by customer
    $grouped = $invoices->groupBy(fn($invoice) => $invoice->order->customers_id ?? null)
        ->filter(); // Remove null keys (missing orders/customers)

    // Map grouped invoices into structured customer data
    $customers = $grouped->map(function ($invoices, $customer_id) {
        $first = $invoices->first();
        $order = $first->order;
        $lead_id = $order?->lead_id;

        $avg_days = $invoices->map(function ($invoice) use ($order) {
            return $order ? Carbon::parse($order->date_ordered)->diffInDays($invoice->invoice_generated_date) : null;
        })->filter()->avg();

        return (object)[
            'customers_id' => $customer_id,
            'cus_com_name' => $first->cus_com_name,
            'lead_id' => $lead_id,
            'days' => round($avg_days),
            'tot_value' => $invoices->sum('sub_total_amount_without_gst'),
        ];
    })->sortByDesc('tot_value')->take(50)->values();

    if ($customers->isEmpty()) {
        return response()->json([
            'status' => 'no_data',
            'message' => 'No top customers found.',
        ], 200);
    }

    // Total for percentage calculation
    $yearly_total = $customers->sum('tot_value');

    // Prepare response
    $data = $customers->map(function ($customer) use ($yearly_total) {
        $segment_name = $this->getCustomerSegment($customer->lead_id);
        $percentage = $yearly_total > 0 ? ($customer->tot_value/ $yearly_total)* 100 : 0;

        return [
            'customer_name'        => $customer->cus_com_name,
            'customer_segment'     => $segment_name,
            'sales_revenue'        => $this->moneyFormatIndia($customer->tot_value),
            'percentage'           => round($percentage, 2),
            'avg_conversion_period'=> $customer->days,
            'tes_factored'         => 'Yes',
        ];
    })->sortByDesc('tot_value')->values();

   
        return response()->json([
            'status' => !empty($data) ? 'success' : 'no_data',
            'message' => !empty($data)
                ? 'Top Customer is listed here successfully.'
                : 'No Top Customer data found.',
                'data' => $data,
                'yearly_total' => $yearly_total,
        ], 200);


}



public function targetCustomerRequiringAttention(Request $request)
{
    $page = (int) $request->input('page', 1);
    $perPage = (int) $request->input('record', 10);

    $accountManager = $this->explodeAccountManager($request->account_manager);
    $months_not_order = $request->input('month', 6);
    $dateThreshold = now()->subMonths($months_not_order);

    // Fetch orders with relevant relationships
    $orders = Order::with([
            'taxInvoices' => function ($query) use ($dateThreshold) {
                $query->approved()
                      ->where('invoice_generated_date', '<=', $dateThreshold);
            },
            'taxInvoices.InvoiceProduct',
            'customer.custSegment'
        ])
        ->when(!empty($accountManager), function ($q) use ($accountManager) {
            $q->whereIn('order_by', $accountManager);
        })
        ->whereHas('taxInvoices', function ($q) use ($dateThreshold) {
            $q->approved()
              ->where('invoice_generated_date', '<=', $dateThreshold);
        })
        ->get();

    // Get product titles for all used pro_ids
    $productIds = $orders->flatMap(fn($order) =>
        $order->taxInvoices->flatMap(fn($invoice) =>
            $invoice->InvoiceProduct->pluck('pro_id')
        )
    )->unique();

    $products = ProductMain::whereIn('pro_id', $productIds)->pluck('pro_title', 'pro_id');

    $today = now();
    $structuredData = [];

    foreach ($orders as $order) {
        $latestInvoice = $order->taxInvoices->sortByDesc('invoice_generated_date')->first();
        if (!$latestInvoice) continue;

        $companyName = $order->customer->comp_name ?? null;
        if (!$companyName) continue;

        $invoiceDateRaw = $latestInvoice->invoice_generated_date;
        if (!$invoiceDateRaw) continue;

        $invoiceDate = Carbon::parse($invoiceDateRaw);
        $lastOrderPeriod = $invoiceDate->floatDiffInDays($today); // float for precision

        $productNames = $latestInvoice->InvoiceProduct->pluck('pro_id')
            ->map(fn($id) => $products[$id] ?? null)
            ->filter()
            ->values()
            ->toArray();

        $structuredData[] = [
            'customer' => ucwords($companyName),
            'customer_segment' => $order->customer->custSegment->cust_segment_name ?? 'N/A',
            'last_product_order' => $productNames,
            'invoice_date' => $invoiceDate->format('d/M/Y'),
            'last_order_period' => round($lastOrderPeriod/30, 0), // maintain float precision if needed
        ];
    }

    if (empty($structuredData)) {
        return response()->json([
            'status' => 'no_data',
            'message' => 'No Target Customer Requiring data found.',
        ], 200);
    }

    // Sort and reset array keys
    $sortedData = collect($structuredData)
        ->sortByDesc('last_order_period')
        ->values(); // force numeric indexing

    $paginated = new LengthAwarePaginator(
        $sortedData->forPage($page, $perPage),
        $sortedData->count(),
        $perPage,
        $page,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    return response()->json([
        'status' => 'success',
        'message' => 'Target Customer Requiring is listed here successfully.',
        'data' => array_values($paginated->items()), // Force clean numeric array
        'pagination' => [
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
        ]
    ], 200);
}


protected function getCustomerSegment($lead_id)
{
  
    $segment_code = $this->lead_cust_segment($lead_id);
    return $this->cust_segment_name($segment_code);
}
protected function lead_cust_segment($lead_id)
{
    return Lead::where('id', $lead_id)->value('cust_segment');
}


private function dateFormatIndia($date)
{
    return $date ? Carbon::parse($date)->format('d/M/Y') : 'N/A';
}

function moneyFormatIndia($num)
{
    $num = preg_replace('/,+/', '', $num);
    $words = explode(".", $num);
    $des = "00";
    if (count($words) <= 2) {
        $num = $words[0];
        if (count($words) >= 2) {
            $des = $words[1];
        }
        if (strlen($des) < 2) {
            $des = "$des";
        } else {
            $des = substr($des, 0, 2);
        }
    }
    if (strlen($num) > 3) {
        $lastthree = substr($num, strlen($num) - 3, strlen($num));
        $restunits = substr($num, 0, strlen($num) - 3);
        $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits;
        $expunit = str_split($restunits, 2);
        $explrestunits = "";
        foreach ($expunit as $i => $unit) {
            $explrestunits .= $i == 0 ? (int)$unit . "," : $unit . ",";
        }
        $thecash = $explrestunits . $lastthree;
    } else {
        $thecash = $num;
    }
    return "$thecash.$des";
}
function getMonthName($month)
{
    return Carbon::createFromFormat('m', $month)->format('F'); // E.g., 'January', 'February', etc.
}


  

protected function cust_segment_name($cust_segment_id)
{
    return CustSegment::where('cust_segment_id', $cust_segment_id)
        ->where('deleteflag', 'active')
        ->value('cust_segment_name');
}



    public function topSellingProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'financial_year' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        [$startDate, $endDate] = $this->getDateRangeFromFinancialYear($request->financial_year, $request->month);
        $accountManagers = $this->explodeAccountManager($request->account_manager);

        $tesIds = TesManager::where('financial_year', $request->financial_year)
            ->approvedActive()
            ->when($accountManagers, fn($q) => $q->whereIn('account_manager', $accountManagers))
            ->pluck('ID')->toArray();

        $topSelling = InvoiceProduct::with(['product.indexG2.application', 'invoice'])
            ->whereHas('invoice', function ($q) use ($startDate, $endDate, $accountManagers) {
                $q->approved()->betweenDates($startDate, $endDate);
                if ($accountManagers) $q->whereIn('prepared_by', $accountManagers);
            })
            ->when($request->product_search, fn($q) =>
                $q->whereHas('product', fn($p) =>
                    $p->where('pro_title', 'like', '%' . $request->product_search . '%')
                )
            )
            ->when($request->category_id, fn($q) =>
                $q->whereHas('product.indexG2.application', fn($app) =>
                    $app->where('application_id', $request->category_id)
                )
            )
            ->selectRaw('pro_id, SUM(quantity) as tot_qty, SUM(quantity * price) as tot_price, SUM(quantity * price) / NULLIF(SUM(quantity), 0) as avgprice')
            ->groupBy('pro_id')->get();

        $productIds = $topSelling->pluck('pro_id')->unique()->toArray();

        $tesData = Tes::selectRaw('pro_id, SUM(quantity) as sum_qty, SUM(sub_total) as sum_value')
            ->whereIn('tes_id', $tesIds)->whereIn('pro_id', $productIds)
            ->groupBy('pro_id')->get()->keyBy('pro_id');

        $productNames = ProductMain::whereIn('pro_id', $productIds)->pluck('pro_title', 'pro_id');

        $formatted = $topSelling->map(function ($item) use ($tesData, $productNames) {
            $proId = $item->pro_id;
            if (!$proId || empty($productNames[$proId])) return null;

            $tes = $tesData[$proId] ?? (object)['sum_qty' => 0, 'sum_value' => 0];
            $achievedQty = $item->tot_qty;
            $achievedVal = $achievedQty * $item->avgprice;

            return [
                'product_id'        => $proId,
                'product'           => $productNames[$proId],
                'targeted_quantity' => $tes->sum_qty,
                'new_price'         => $this->moneyFormatIndia($item->avgprice),
                'targeted_value'    => $this->moneyFormatIndia($tes->sum_value),
                'achieved_quantity' => $achievedQty,
                'achieved_value_raw'=> $achievedVal,
                'achieved_value'    => $this->moneyFormatIndia($achievedVal),
            ];
        })->filter()
          ->groupBy('product_id')->map(fn($g) => $g->first())
          ->sortByDesc('achieved_value_raw')->values();

        $total = $formatted->count();
        $paginated = $this->paginateCollection($formatted, $request);

        return $this->apiResponse(
            $paginated->isNotEmpty() ? 'success' : 'no_data',
            $paginated->isNotEmpty() ? 'Top Selling Product listed successfully.' : 'No data found.',
            $paginated, $request->input('page', 1), $request->input('records', 10), $total
        );
    }

    public function monthOnMonthSalesTrend(Request $request)
{
    $validator = Validator::make($request->all(), [
        'financial_year1' => 'required|integer',
        'financial_year2' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $accountManagers = $this->explodeAccountManager($request->account_manager);
    [$start1, $end1] = $this->getDateRangeFromFinancialYear($request->financial_year1);
    [$start2, $end2] = $this->getDateRangeFromFinancialYear($request->financial_year2);

    $months = collect(range(0, 11))->map(fn($i) => $start1->copy()->addMonths($i)->format('F'))->toArray();

    $ach1 = $this->calculateMonthlyAchieved($start1, $end1, $accountManagers, $request->product_category, $request->product_choose, false);
    $ach2 = $this->calculateMonthlyAchieved($start2, $end2, $accountManagers, $request->product_category, $request->product_choose, false);

    $comparison = collect(range(0, 11))->map(function ($i) use ($ach1, $ach2) {
        $v1 = (float)($ach1[$i] ?? 0);
        $v2 = (float)($ach2[$i] ?? 0);

        return ($v2 > 0)
            ? min(round(($v1 / $v2) * 100, 2), 100) . '%'
            : ($v1 > 0 ? '100%' : '0%');
    });

    return response()->json([
        'status' => 'success',
        'message' => 'Month-on-Month Sales Trend is listed here successfully.',
        'achieved_year1' => $ach1,
        'achieved_year2' => $ach2,
        'achieved_comparison_perByMonths' => $comparison,
        'financialYear1' => FinancialYear::find($request->financial_year1)->fin_name ?? '',
        'financialYear2' => FinancialYear::find($request->financial_year2)->fin_name ?? '',
        'months' => $months
    ]);
}


    public function productsRequiringAttentionTarget(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'financial_year' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        [$startDate, $endDate] = $this->getDateRangeFromFinancialYear($request->financial_year, $request->month);
        $accountManagers = $this->explodeAccountManager($request->account_manager);

        $tesIds = TesManager::approvedActive()
            ->where('financial_year', $request->financial_year)
            ->when($accountManagers, fn($q) => $q->whereIn('account_manager', $accountManagers))
            ->pluck('ID')->toArray();

        if (empty($tesIds)) {
            return $this->apiResponse('no_data', 'No TES target found for selected manager.', []);
        }

        $tes = Tes::whereIn('tes_id', $tesIds)
            ->selectRaw('pro_id, SUM(quantity) as total_quantity, SUM(sub_total) as total_sub_total')
            ->groupBy('pro_id')->get();

        $productIds = $tes->pluck('pro_id')->toArray();
        $invoiceData = InvoiceProduct::select('pro_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereIn('pro_id', $productIds)
            ->whereHas('taxInvoice', function ($q) use ($startDate, $endDate, $accountManagers) {
                $q->approved()->betweenDates($startDate, $endDate);
                if ($accountManagers) $q->whereIn('prepared_by', $accountManagers);
            })
            ->groupBy('pro_id')->pluck('total_sold', 'pro_id');

        $productNames = ProductMain::whereIn('pro_id', $productIds)->pluck('pro_title', 'pro_id');

        $data = $tes->map(function ($item) use ($invoiceData, $productNames) {
            $sold = $invoiceData[$item->pro_id] ?? 0;
            $threshold = $item->total_quantity * 0.2;

            if ( $sold >= $threshold || $item->total_quantity <= $sold) return null;

            return [
                'product' => $productNames[$item->pro_id] ?? 'N/A',
                'targeted_quantity' => $this->moneyFormatIndia($item->total_quantity),
                'achieved_quantity' => $sold,
                'targeted_value' => $this->moneyFormatIndia($item->total_sub_total)
            ];
        })->filter()->sortByDesc('achieved_quantity')->values();

        $total = $data->count();
        $paginated = $this->paginateCollection($data, $request);

        return $this->apiResponse(
            $total ? 'success' : 'no_data',
            $total ? 'Products Requiring Attention (<20% of Target) listed successfully.' : 'No such products found.',
            $paginated, $request->input('page', 1), $request->input('records', 10), $total
        );
    }

    // ?? Reusable Helpers


 public function getDateRangeFromFinancialYear($financialYearId, $month = null)
    {
        $financialYear = FinancialYear::where('fin_id', $financialYearId)->value('fin_name');
        [$startYear, $endYear] = explode('-', $financialYear);

        if ($month) {
            $year = ($month >= 1 && $month <= 3) ? $endYear : $startYear;
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        } else {
            $startDate = Carbon::createFromDate($startYear, 4, 1)->startOfDay();
            $endDate = Carbon::createFromDate($endYear, 3, 31)->endOfDay();
        }

        return [$startDate, $endDate];
    }
    private function explodeAccountManager($input)
    {
        return !empty($input) ? explode(',', $input) : [];
    }

    private function paginateCollection($collection, $request)
    {
        $page = max((int)$request->input('page', 1), 1);
        $perPage = max((int)$request->input('records', 10), 1);
        $offset = ($page - 1) * $perPage;

        return $collection->slice($offset, $perPage)->values()->map(function ($item) {
            unset($item['achieved_value_raw']);
            return $item;
        });
    }

    private function apiResponse($status, $message, $data, $page = 1, $perPage = 10, $total = 0)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'page' => $page,
            'records' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ]);
    }

   private function calculateMonthlyAchieved(Carbon $start, Carbon $end, $accountManagers, $catId, $proId, $format = false)
{
    $results = [];
    $current = $start->copy();

    while ($current <= $end) {
        $monthStart = $current->copy()->startOfMonth();
        $monthEnd = $current->copy()->endOfMonth();

        $invoices = TaxInvoice::approved()
            ->with(['products.productG2.application'])
            ->betweenDates($monthStart, $monthEnd)
            ->when($accountManagers, fn($q) => $q->whereIn('prepared_by', $accountManagers))
            ->where(function ($q) use ($proId, $catId) {
                if ($proId) {
                    $q->whereHas('products', fn($qq) => $qq->where('pro_id', $proId));
                }
                if ($catId) {
                    $q->whereHas('products.productG2.application', fn($qq) => $qq->where('pro_id', $catId));
                }
            })
            ->get();

        $credits = TaxCreditNoteInvoice::approved()
            ->with(['products.productG2.application'])
            ->betweenDates($monthStart, $monthEnd)
            ->when($accountManagers, fn($q) => $q->whereIn('prepared_by', $accountManagers))
            ->where(function ($q) use ($proId, $catId) {
                if ($proId) {
                    $q->whereHas('products', fn($qq) => $qq->where('pro_id', $proId));
                }
                if ($catId) {
                    $q->whereHas('products.productG2.application', fn($qq) => $qq->where('pro_id', $catId));
                }
            })
            ->get();

        $amount = $invoices->sum(fn($i) => $i->sub_total_amount_without_gst * $i->exchange_rate);
        $credit = $credits->sum(fn($c) => $c->sub_total_amount_without_gst * $c->exchange_rate);
        $net = round($amount - $credit);

        $results[] = $format ? $this->moneyFormatIndia($net) : $net;
        $current->addMonth();
    }

    return $results;
}


    public function fetchInvoices($startDate, $endDate, $accountManager = null)
    {
        return TaxInvoice::with(['products.productG2.application', 'services.productS2.applicationService'])
            ->whereBetween('invoice_generated_date', [$startDate, $endDate])
            ->where('invoice_status', 'approved')
            ->when($accountManager, fn($q) => $q->whereIn('prepared_by', $accountManager))
            ->get();
    }

    public function fetchCreditNotes($startDate, $endDate, $accountManager = null)
    {
        return TaxCreditNoteInvoice::with(['products.productG2.application', 'services.productS2.applicationService'])
            ->whereBetween('credit_invoice_generated_date', [$startDate, $endDate])
            ->where('invoice_status', 'approved')
            ->when($accountManager, fn($q) => $q->whereIn('prepared_by', $accountManager))
            ->get();
    }

    public function getCategoryName($invoiceType, $product)
    {
        if ($invoiceType === 'product' && $product->productG2?->application) {
            return $product->productG2->application->application_name;
        }
        if ($invoiceType === 'service' && $product->productS2?->applicationService) {
            return $product->productS2->applicationService->application_service_name;
        }
        return 'Others';
    }

private function calculatePercentage($amount, $total)
{
    return ($amount > 0 || $total > 0) ? round(($amount / $total) * 100, 2) . '%' : '0%';
}



}