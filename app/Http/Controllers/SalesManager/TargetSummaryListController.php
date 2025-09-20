<?php

namespace App\Http\Controllers\SalesManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\{
    TaxInvoice,
    InvoiceProduct,
    CreditNoteInvoiceProduct,
    TesManager,
    Tes,
    Order,
    Application, 
    ApplicationService,
    ProductMain, 
    ProductsEntry,
    Service,
    ServicesEntry,
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
    Designation,
    PerformaInvoice,
};

use Carbon\Carbon;
 use DB;

class TargetSummaryListController extends Controller
{
   

public function salesConversionRate(Request $request)
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

    $financialYearId = $request->financial_year;
    $productType = $request->product_type;
    $categoryId = $request->category_id;
    $productId = $request->product_id;
    $conversionBy = $request->conversion_by;

    $financialYear = FinancialYear::where('fin_id', $financialYearId)->value('fin_name');
    if (!$financialYear) {
        return response()->json([
            'status' => 'no_data',
            'message' => 'Invalid financial year.',
            'data' => []
        ], 400);
    }

    [$startYear, $endYear] = explode('-', $financialYear);

    $month = $request->month;
    if ($month) {
        $year = ($month >= 1 && $month <= 3) ? $endYear : $startYear;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
    } else {
        $startDate = Carbon::createFromDate($startYear, 4, 1)->startOfDay(); // April
        $endDate = Carbon::createFromDate($endYear, 3, 31)->endOfDay(); // March
    }

    $salesSummary = $this->salesConversionSummary($accountManager, $startDate, $endDate, $productId, $categoryId, $productType);

    $data = [];

    foreach ($salesSummary as $summary) {
        $proId = $summary->pro_id;
        if ($proId) {
            $invoiceCount = $this->totalInvoiceCount($startDate, $endDate, $proId);
            $offerCount = $this->totalOfferCount($startDate, $endDate, $proId, $conversionBy);

            if ($invoiceCount > $offerCount) {
                $offerCount += $invoiceCount;
            }

            $conversionPercentage = $offerCount > 0 ? round(($invoiceCount * 100) / $offerCount, 2) : 0;

            $data[] = [
                'pro_id' => $summary->pro_id,
                'product' => $summary->product_name,
                'offer_given' => $offerCount,
                'order_received' => $invoiceCount,
                'percentage' => $conversionPercentage,
                'avg_conversion_period' => $summary->days,
            ];
        }
    }

    // Sort by order_received
    $sortedData = collect($data)->sortByDesc('order_received')->values();

    // Apply pagination
    $page = $request->get('page', 1);
    $perPage = $request->get('record', 10);
    $paginated = $sortedData->forPage($page, $perPage)->values();

    return response()->json([
        'status' => $sortedData->isNotEmpty() ? 'success' : 'no_data',
        'message' => $sortedData->isNotEmpty()
            ? 'Sales Conversion Rate is listed here successfully.'
            : 'No sales data found.',
        'data' => $paginated,
        'meta' => [
            'total' => $sortedData->count(),
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($sortedData->count() / $perPage),
        ]
    ]);
}

protected function salesConversionSummary($accountManager, $startDate, $endDate, $productId, $categoryId, $productType)
{
    $query = InvoiceProduct::with(['product.indexG2.application', 'taxInvoice.order'])
        ->whereHas('taxInvoice', function ($q) use ($accountManager, $startDate, $endDate) {
            $q->whereBetween('invoice_generated_date', [$startDate, $endDate]);
            if (!empty($accountManager)) {
                $q->whereIn('prepared_by', $accountManager);
            }
        });

    if (!empty($productId)) {
        $query->where('pro_id', $productId);
    }

    if (!empty($productType)) {
        $query->whereHas('product', function ($q) use ($productType) {
            $q->where('product_type_class_id', $productType);
        });
    }

    $invoiceProducts = $query->get();

    $grouped = $invoiceProducts->groupBy('pro_id');

    $result = [];

    foreach ($grouped as $proId => $items) {
        $days = $items->map(function ($item) {
            return Carbon::parse(optional($item->taxInvoice->order)->date_ordered)
                ->diffInDays(optional($item->taxInvoice)->invoice_generated_date ?? now(), false);
        })->filter()->avg();

        $product = optional($items->first()->product);
        $application = optional($product->indexG2)->application;

        $result[] = (object)[
            'days' => round($days),
            'pro_id' => $proId,
            'product_name' => $product->pro_title ?? '',
            'category_id' => optional($product->indexG2)->pro_id,
            'application_name' => $application->application_name ?? '',
        ];
    }

    return $result;
}

protected function totalInvoiceCount($startDate, $endDate, $productId)
{
    return InvoiceProduct::where('pro_id', $productId)
        ->whereHas('taxInvoice', function ($q) use ($startDate, $endDate) {
            $q->where('invoice_status', 'approved')
              ->whereBetween('invoice_generated_date', [$startDate, $endDate]);
        })
        ->count();
}

protected function totalOfferCount($startDate, $endDate, $productId, $conversionBy)
{
    $query = OrderProduct::where('pro_id', $productId)
        ->whereHas('order', function ($q) use ($startDate, $endDate, $conversionBy) {
            $q->whereBetween('date_ordered', [$startDate, $endDate]);
            if ($conversionBy == 3) {
                $q->whereIn('offer_probability', [3, 4]);
            } elseif ($conversionBy == 4) {
                $q->where('offer_probability', 4);
            }
        });

    return $query->count();
}

    public function conversionBy(Request $request)
    {
        $data = [
            [
                'value' => 3, // 3,4
                'name' => 'Offer + Opportunity',
            ],
            [
                'value' => 4,
                'name' => 'Opportunity',
            ],
        ];

        $response = [
            'status' => 'success',
            'message' => 'Conversion by is listed here successfully.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }
    public function productType(Request $request)
    {
        $data = [
            [
                'value' => 2,
                'name' => 'Accessory',
            ],
            [
                'value' => 3,
                'name' => 'Component',
            ],
            [
                'value' => 4,
                'name' => 'Kit',
            ],
            [
                'value' => 1,
                'name' => 'SKU',
            ],
        ];

        $response = [
            'status' => 'success',
            'message' => 'Conversion by is listed here successfully.',
            'data' => $data,
        ];

        return response()->json($response, 200);
    }
 
    public function productCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_type' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        $categoryType = $request->category_type;
    
        if ($categoryType === 'product') { 
            $data = Application::select('application_id as id', 'application_name as name')
                ->active()
                ->get();
        } else { 
            $data = ApplicationService::select('application_service_id as id', 'application_service_name as name')
                ->active()
                ->get();
        }
    
        return response()->json([
            'status'  => 'success',
            'message' => 'Categories listed successfully.',
            'data'    => $data,
        ]);
    }
    
    public function productService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_type' => 'required',
            'category_id' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        $categoryType = $request->category_type;
        $categoryId = $request->category_id;

        if ($categoryType === 'product') { 

                $products = ProductsEntry::with(['product' => fn($q) => $q->active()])
            ->active()->where('app_cat_id',$categoryId)
            ->whereNotIn('model_no', ['', '0000'])
            ->whereHas('product', fn($q) => $q->active())
            ->select('pro_id', 'model_no')
            ->distinct()
            ->get()
            ->sortBy(fn($item) => $item->product->pro_title ?? '');
    
        $data = $products->map(function ($item) {
            return [
                'id'   => $item->pro_id,
                'name' => optional($item->product)->pro_title,
                'model_no'     => $item->model_no,
            ];
        })->values();

        } else { 

        $service = ServicesEntry::with(['service' => fn($q) => $q->active()])
            ->active()->where('app_cat_id',$categoryId)
            ->whereNotIn('model_no', ['', '0000'])
            ->whereHas('service', fn($q) => $q->active())
            ->select('service_id', 'model_no')
            ->distinct()
            ->get()
            ->sortBy(fn($item) => $item->product->service_title ?? '');
    
        $data = $service->map(function ($item) {
            return [
                'id'   => $item->service_id,
                'name' => optional($item->service)->service_title,
                'model_no'     => $item->model_no,
            ];
        })->values();
    }
        return response()->json([
            'status'  => 'success',
            'message' => 'Product is listed here successfully.',
            'data'    => $data,
        ]);
    }
public function tesProgress(Request $request)
{

   $validator = Validator::make($request->all(), [
          'financial_year' => 'required|integer',
        'account_manager' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }
   $accountManager = $request->account_manager;
if (!empty($accountManager)) {
    $accountManager = explode(',', $accountManager);
}
    $financialYearId = $request->financial_year;
    $productId = $request->product_id;

    $financialYear = FinancialYear::find($financialYearId);
    if (!$financialYear) {
        return response()->json(['message' => 'Invalid financial year.'], 400);
    }

    [$startYear, $endYear] = explode('-', $financialYear->fin_name);

            $month = $request->month;
    // Date range
    if ($month) {
        $year = ($month >= 1 && $month <= 3) ? $endYear : $startYear;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
    } else {
        $startDate = Carbon::createFromDate($startYear, 4, 1)->startOfDay(); // April
        $endDate = Carbon::createFromDate($endYear, 3, 31)->endOfDay(); // March
    }
    
  

    $tesId = TesManager::approved()
        ->active()
        ->where('financial_year', $financialYearId)
        ->when(!empty($accountManager), fn($q) => $q->whereIn('account_manager', $accountManager))
        ->value('ID');

    if (!$tesId) {
        return response()->json(['message' => 'Target is missing for the selected Account Manager.'], 200);
    }

    $tesList = Tes::with([
            'event:id,customer,start_event,account_manager,evttxt,creation_date',
            'event.customer:id,comp_name',
            'event.taskTypes:tasktype_abbrv,task_icon,tasktype_name'
        ])
        ->where('tes_id', $tesId)
        ->when($productId && $productId != '0', fn($q) => $q->where('pro_id', $productId))
        ->whereHas('event', fn($q) => $q->whereBetween('start_event', [$startDate, $endDate])
                                        ->when(!empty($accountManager), fn($q) => $q->whereIn('account_manager', $accountManager)))
        ->get();

    $companyIds = $tesList->pluck('comp_id')->unique()->values();
    $productIds = $tesList->pluck('pro_id')->unique()->values();

    $companies = Company::with('custSegment')->whereIn('id', $companyIds)->get()->keyBy('id');
    $products = ProductMain::whereIn('pro_id', $productIds)->get()->keyBy('pro_id');

    $salesCache = [];
    $taskCache = [];

    $data = $tesList->map(function ($tes) use (
        $accountManager,
        $startDate,
        $endDate,
        $companies,
        $products,
        &$salesCache,
        &$taskCache
    ) {
        $companyId = $tes->comp_id;
        $productId = $tes->pro_id;

        $salesKey = "$productId|$companyId";
        if (!isset($salesCache[$salesKey])) {
            $salesCache[$salesKey] = $this->getProductQuantitySold($accountManager, $productId, $companyId, $startDate, $endDate);
        }
        $salesData = $salesCache[$salesKey];

        if (!isset($taskCache[$companyId])) {
           $taskCache[$companyId] = $this->getTaskEvents($companyId, $accountManager, $startDate, $endDate);
        }
        $tasks = $taskCache[$companyId];

        $company = $companies->get($companyId);
        $segmentData = $company?->custSegment;

        return [
            'customer_id'             => $companyId,
            'customer'                => $tes->comp_name ?? $company?->comp_name ?? 'N/A',
            'product_id'              => $productId,
            'product'                 => $products[$productId]->pro_title ?? 'N/A',
            'target_quantity'         => $tes->quantity,
            'targeted_sales'          => $tes->sub_total,
            'actual_quantity'         => $salesData['pro_quantity'],
            'actual_sales'            => $salesData['pro_price'],
           'last_contact_date'       => $tasks['last_contact_date'] ?? 'N/A',
           'last_contact_type'       => $tasks['last_contact_type'] ?? 'N/A',
           'task_images'             => $tasks['icons'] ?? [],
           'repeat_interactions'     => $tasks['interactions'] ?? [],
            'segment'                 => $segmentData->cust_segment_name ?? 'N/A',
            'recomended_interactions' => $segmentData->interactions_reqd ?? 'N/A',
        ];
    })
    ->filter(fn($item) => $item['actual_sales'] > 0)
    ->sortByDesc('actual_sales')
    ->take(50)
    ->values();

    return response()->json([
        'status' => 'success',
        'message' => 'TES product details retrieved.',
        'data' => $data
    ]);
}

public function getProductQuantitySold($accountManager = null, $productId, $companyId, $startDate, $endDate)
{
    $invoices = TaxInvoice::active()->approved()
        ->select('invoice_id', 'o_id', 'prepared_by')
        ->betweenDates($startDate, $endDate)
        ->with(['products' => function ($q) use ($productId) {
            $q->select('tax_invoice_id', 'quantity', 'price', 'pro_id')
              ->where('pro_id', $productId);
        }])
        ->whereHas('products', fn($q) => $q->where('pro_id', $productId))
        ->whereHas('order', function ($q) use ($companyId) {
            $q->where('customers_id', $companyId)
              ->orWhere('tes_linked_customer_id', $companyId);
        })
        ->when(!empty($accountManager), fn($q) => $q->whereIn('prepared_by', $accountManager))
        ->get();

    $quantity = 0;
    $price = 0;

    foreach ($invoices as $invoice) {
        foreach ($invoice->products as $product) {
            $quantity += $product->quantity;
            $price += $product->quantity * $product->price;
        }
    }

    return [
        'pro_quantity' => $quantity,
        'pro_price'    => $price
    ];
}
public function getTaskEvents($companyId, $accountManager = null, $startDate, $endDate)
{
    $eventTypes = ['Demo', 'OSV', 'TFU', 'LV'];

    $events = Event::active()
        ->select('id', 'evttxt', 'creation_date', 'account_manager', 'customer')
        ->with(['taskTypes:tasktype_abbrv,task_icon,tasktype_name'])
        ->whereBetween('creation_date', [$startDate, $endDate])
        ->whereIn('evttxt', $eventTypes)
        ->when(!empty($accountManager), fn($q) => $q->whereIn('account_manager', $accountManager))
        ->when($companyId, fn($q) => $q->where('customer', $companyId))
        ->get();

    $icons = [];
    $interactions = ['Demo' => 0, 'OSV' => 0, 'TFU' => 0, 'LV' => 0];
    $lastContactDate = null;
    $lastContactType = null;

    foreach ($events as $event) {
        $task = $event->taskTypes->first();
        if (!$task) continue;

        $eventType = $event->evttxt;
        if (isset($interactions[$eventType])) {
            $interactions[$eventType]++;
        }

        $icons["https://www.stanlay.in/" . $task->task_icon] = true;

        if (!$lastContactDate || $event->creation_date > $lastContactDate) {
            $lastContactDate = $event->creation_date;
            $lastContactType = $task->tasktype_name;
        }
    }

    return [
        'icons'              => array_keys($icons),
        'interactions'       => array_values(array_filter($interactions)),
        'last_contact_date'  => $this->formatIndianDate($lastContactDate),
        'last_contact_type'  => $lastContactType
    ];
}


public function formatIndianDate($date)
{
    return $date ? Carbon::parse($date)->format('d/M/Y') : 'N/A';
}



function getMonthName($month)
{
    return Carbon::createFromFormat('m', $month)->format('F'); // E.g., 'January', 'February', etc.
}

}
  

