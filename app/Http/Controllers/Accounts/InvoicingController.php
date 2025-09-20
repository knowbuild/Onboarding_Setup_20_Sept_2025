<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\TaxInvoice;
use App\Models\InvoiceProduct;

class InvoicingController extends Controller
{
    /**
     * Simple test method
     */
    public function test()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'InvoicingController is working'
        ]);
    }

    public function listWithSummary(Request $request)
    {
        try {
            // --- Collect filter and pagination params ---
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('records', 10);
            $sortKey = $request->get('sort_key', 'invoice_generated_date');
            $sortValue = $request->get('sort_value', 'desc');

            $filters = [
                'company_name' => $request->get('company_name'),
                'invoice_number' => $request->get('invoice_number'),
                'serial_number' => $request->get('serial_number'),
                'mobile_number' => $request->get('mobile_number'),
                'account_manager' => $request->get('account_manager'),
                'product_service' => $request->get('product_name'),
                'product_category' => $request->get('product_category'),  // category filter
                'product_name' => $request->get('product_service'),
                'service_name' => $request->get('service_name'),
                'invoice_status' => $request->get('invoice_status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            // --- Financial year logic ---
            $financialYear = $request->get('financial_year');
            $currentDate = Carbon::now();
            if (empty($financialYear)) {
                $financialYear = $currentDate->month >= 4 ?
                    $currentDate->year . '-' . ($currentDate->year + 1) :
                    ($currentDate->year - 1) . '-' . $currentDate->year;
            }
            [$startYear, $endYear] = explode('-', $financialYear);
            $fyStart = Carbon::createFromDate($startYear, 4, 1)->startOfDay();
            $fyEnd = Carbon::createFromDate($endYear, 3, 31)->endOfDay();

            // --- Shared filter logic closure ---
            $applyFilters = function($query, $filters, $dateRange = null) {
                if (!empty($filters['company_name'])) {
                    $query->where('cus_com_name', 'like', "%{$filters['company_name']}%");
                }
                if (!empty($filters['invoice_number'])) {
                    $query->where('invoice_id', 'like', "%{$filters['invoice_number']}%");
                }
                if (!empty($filters['product_name'])) {
                    $query->where('invoice_type', 'like', "%{$filters['product_name']}%");
                }
                if (!empty($filters['account_manager'])) {
                    $query->where('prepared_by', $filters['account_manager']);
                }
                if (!empty($filters['invoice_status'])) {
                    $query->where('invoice_status', $filters['invoice_status']);
                }
                if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                    $query->whereBetween('invoice_generated_date', [$filters['date_from'], $filters['date_to']]);
                } elseif (!empty($filters['date_from'])) {
                    $query->where('invoice_generated_date', '>=', $filters['date_from']);
                } elseif (!empty($filters['date_to'])) {
                    $query->where('invoice_generated_date', '<=', $filters['date_to']);
                }
                if ($dateRange) {
                    $query->whereBetween('invoice_generated_date', $dateRange);
                }
                // Product/service/category/serial/mobile filters (joins)
                if ( !empty($filters['service_name']) || !empty($filters['product_category']) || !empty($filters['serial_number']) || !empty($filters['mobile_number']) || !empty($filters['product_service'])) {
                    $orderQuery = DB::table('tbl_order_product as top')
                        ->select('top.order_id')
                        ->where('top.deleteflag', 'active');
                    // if (!empty($filters['product_name'])) {
                    //     $orderQuery->where('top.pro_name', 'like', "%{$filters['product_name']}%");
                    // }
                    if (!empty($filters['product_category'])) {
                        $orderQuery->leftJoin('tbl_products as tp', 'top.pro_id', '=', 'tp.pro_id')
                                  ->where('tp.cate_id', $filters['product_category']);
                    }
                    if (!empty($filters['serial_number'])) {
                        $orderQuery->leftJoin('tbl_product_serial_numbers as tpsn', 'top.pro_id', '=', 'tpsn.pro_id')
                                  ->where('tpsn.serial_number', 'like', "%{$filters['serial_number']}%");
                    }
                    $validOrderIds = $orderQuery->pluck('order_id')->toArray();
                    if (!empty($validOrderIds)) {
                        $query->whereIn('o_id', $validOrderIds);
                    } else {
                        $query->where('invoice_id', '=', 0);
                    }
                }
                return $query;
            };

            // --- Invoice List Query ---
            $listQuery = DB::table('tbl_tax_invoice')
                ->select([
                    'invoice_id',
                    'o_id as order_id',
                    'po_no as invoice_number',
                    'invoice_generated_date',
                    'po_due_date as due_date',
                    'cus_com_name as customer_name',
                    'con_name as contact_name',
                    'invoice_status',
                    'invoice_approval_status',
                    'sub_total_amount_without_gst',
                    'total_gst_amount',
                    'freight_amount',
                    'prepared_by',
                    'payment_terms',
                    'invoice_type',
                    'eway_bill_no',
                    'exchange_rate'
                ])
                ->where('deleteflag', 'active');
            // Always apply FY filter
            $listQuery->whereBetween('invoice_generated_date', [$fyStart->toDateString(), $fyEnd->toDateString()]);
            $listQuery = $applyFilters($listQuery, $filters);

            $paginator = $listQuery->orderBy($sortKey, $sortValue)
                ->paginate($perPage, ['*'], 'page', $page);
            $invoices = $paginator->items();

            $formattedInvoices = collect($invoices)->map(function ($invoice) {
                $products = DB::table('tbl_order_product')
                    ->select(['pro_id', 'pro_name as product_name', 'pro_quantity as quantity', 'pro_price as unit_price'])
                    ->where('order_id', $invoice->order_id)
                    ->where('deleteflag', 'active')
                    ->get()
                    ->map(function($product) {
                        return [
                            'pro_id' => $product->pro_id,
                            'product_name' => $product->product_name,
                            'quantity' => $product->quantity,
                            'unit_price' => number_format($product->unit_price ?? 0, 2),
                            'line_total' => number_format(($product->quantity ?? 0) * ($product->unit_price ?? 0), 2)
                        ];
                    });
                $irnData = DB::table('tbl_tax_invoice_gst_irn_response')
                    ->select(['irn', 'ackno', 'ackdt', 'ewbno', 'ewbdt', 'irn_status'])
                    ->where('invoice_id', $invoice->invoice_id)
                    ->first();
                $exchangeRate = $invoice->exchange_rate ?? 1;
                $subTotalWithRate = ($invoice->sub_total_amount_without_gst ?? 0) * $exchangeRate;
                $gstAmountWithRate = ($invoice->total_gst_amount ?? 0) * $exchangeRate;
                $freightAmountWithRate = ($invoice->freight_amount ?? 0) * $exchangeRate;
                $totalAmount = $subTotalWithRate + $gstAmountWithRate + $freightAmountWithRate;
                return [
                    'invoice_id' => $invoice->invoice_id,
                    'invoice_date' => $invoice->invoice_generated_date ? 
                        Carbon::parse($invoice->invoice_generated_date)->format('d M Y') : null,
                    'company_name' => $invoice->customer_name,
                    'account_manager' => admin_name($invoice->prepared_by ?? 0),
                    'products_services' => $products->pluck('product_name')->join(', ') ?: 'No products',
                    'net_value' => number_format($subTotalWithRate, 2),
                    'total_value' => number_format($totalAmount, 2),
                ];
            });

            // --- Dashboard Summary ---
            // Today
            $today = Carbon::today();
            $todayQuery = DB::table('tbl_tax_invoice')->where('deleteflag', 'active');
            $todayQuery = $applyFilters($todayQuery, $filters, [$today->toDateString(), $today->toDateString()]);
            $todayTotal = $todayQuery->sum(DB::raw('sub_total_amount_without_gst + total_gst_amount + freight_amount')) ?? 0;
            $todayCount = $todayQuery->count();
            // Month
            $startOfMonth = $currentDate->copy()->startOfMonth();
            $endOfMonth = $currentDate->copy()->endOfMonth();
            $monthQuery = DB::table('tbl_tax_invoice')->where('deleteflag', 'active');
            $monthQuery = $applyFilters($monthQuery, $filters, [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
            $monthTotal = $monthQuery->sum(DB::raw('sub_total_amount_without_gst + total_gst_amount + freight_amount')) ?? 0;
            $monthCount = $monthQuery->count();
            // Year
            $yearQuery = DB::table('tbl_tax_invoice')->where('deleteflag', 'active');
            $yearQuery = $applyFilters($yearQuery, $filters, [$fyStart->toDateString(), $fyEnd->toDateString()]);
            $yearTotal = $yearQuery->sum(DB::raw('sub_total_amount_without_gst + total_gst_amount + freight_amount')) ?? 0;
            $yearCount = $yearQuery->count();
            // Credit notes
            $creditQuery = DB::table('tbl_tax_credit_note_invoice')->where('deleteflag', 'active');
            $creditQuery->whereBetween('credit_invoice_generated_date', [$fyStart->toDateString(), $fyEnd->toDateString()]);
            $creditTotal = $creditQuery->sum(DB::raw('(sub_total_amount_without_gst + total_gst_amount + freight_amount)')) ?? 0;
            $creditCount = $creditQuery->count();

            // --- Response ---
            return response()->json([
                'status' => 'success',
                'data' => [
                    'invoices' => $formattedInvoices,
                    'pagination' => [
                        'page' => $paginator->currentPage(),
                        'pageSize' => $paginator->perPage(),
                        'totalItems' => $paginator->total(),
                        'totalPages' => $paginator->lastPage()
                    ],
                    'summary' => [
                        'financial_year' => $financialYear,
                        'today' => [
                            'total_amount' => $todayTotal,
                            'count' => $todayCount
                        ],
                        'this_month' => [
                            'total_amount' => $monthTotal,
                            'count' => $monthCount
                        ],
                        'this_year' => [
                            'total_amount' => $yearTotal,
                            'count' => $yearCount
                        ],
                        'credit_notes' => [
                            'total_amount' => $creditTotal,
                            'count' => $creditCount
                        ]
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching invoices with dashboard summary', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch invoices and dashboard summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoices list with advanced filtering and pagination
     * GET /api/accounts/invoicing/list
     */
     public function getInvoicesDetailList(Request $request)
    {
        try {
            // Log incoming filters for debugging
            Log::info('Invoice filters received:', $request->all());
            
            // Get pagination parameters
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('records', 10);
            
            // Get filter parameters from request
            $companyName = $request->get('company_name');
            $invoiceNumber = $request->get('invoice_number');
            $serialNumber = $request->get('serial_number');
            $mobileNumber = $request->get('mobile_number');
            $accountManager = $request->get('account_manager');
            $productService = $request->get('product_service'); // Product or Service
            $productCategory = $request->get('product_category');
            $productName = $request->get('product_name');
            $serviceName = $request->get('service_name');
            $invoiceStatus = $request->get('invoice_status');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $financialYear = $request->get('financial_year'); // e.g., "2025-2026"
            
            // Set default financial year if not provided (current FY)
            if (empty($financialYear)) {
                $currentMonth = date('n'); // 1-12
                $currentYear = date('Y');
                
                // Financial year starts from April (month 4)
                if ($currentMonth >= 4) {
                    // Apr-Mar: 2025-2026 (if current year is 2025)
                    $financialYear = $currentYear . '-' . ($currentYear + 1);
                } else {
                    // Jan-Mar: 2024-2025 (if current year is 2025)
                    $financialYear = ($currentYear - 1) . '-' . $currentYear;
                }
            }
            
            // Build base query with filters
            $query = DB::table('tbl_tax_invoice')
                ->select([
                    'invoice_id',
                    'o_id as order_id', 
                    'po_no as invoice_number',
                    'invoice_generated_date',
                    'po_due_date as due_date',
                    'cus_com_name as customer_name',
                    'con_name as contact_name',
                    'invoice_status',
                    'invoice_approval_status',
                    'sub_total_amount_without_gst',
                    'total_gst_amount',
                    'freight_amount',
                    'prepared_by',
                    'payment_terms',
                    'invoice_type',
                    'eway_bill_no',
                    'exchange_rate'
                ])
                ->where('deleteflag', 'active');

            // Apply financial year filter (always applied, defaults to current FY)
            if (!empty($financialYear)) {
                $yearParts = explode('-', $financialYear);
                if (count($yearParts) == 2) {
                    $startYear = $yearParts[0];
                    $endYear = $yearParts[1];
                    
                    // Financial year: April 1st of start year to March 31st of end year
                    $fyStartDate = $startYear . '-04-01';
                    $fyEndDate = $endYear . '-03-31';
                    
                    $query->whereBetween('invoice_generated_date', [$fyStartDate, $fyEndDate]);
                }
            }

            // Apply filters
            if (!empty($companyName)) {
                $query->where('cus_com_name', 'like', "%{$companyName}%");
            }

            if (!empty($invoiceNumber)) {
                $query->where('po_no', 'like', "%{$invoiceNumber}%");
            }

            if (!empty($accountManager)) {
                $query->where('prepared_by', $accountManager);
            }

            if (!empty($invoiceStatus)) {
                $query->where('invoice_status', $invoiceStatus);
            }

            if (!empty($dateFrom) && !empty($dateTo)) {
                $query->whereBetween('invoice_generated_date', [$dateFrom, $dateTo]);
            } elseif (!empty($dateFrom)) {
                $query->where('invoice_generated_date', '>=', $dateFrom);
            } elseif (!empty($dateTo)) {
                $query->where('invoice_generated_date', '<=', $dateTo);
            }

            // Additional filters that require joins to other tables
            if (!empty($productName) || !empty($serviceName) || !empty($productCategory) || !empty($serialNumber) || !empty($mobileNumber)) {
                // Get invoice IDs that match product/service criteria
                $orderQuery = DB::table('tbl_order_product as top')
                    ->select('top.order_id')
                    ->where('top.deleteflag', 'active');

                if (!empty($productName)) {
                    $orderQuery->where('top.pro_name', 'like', "%{$productName}%");
                }

                if (!empty($productCategory)) {
                    $orderQuery->leftJoin('tbl_products as tp', 'top.pro_id', '=', 'tp.pro_id')
                              ->where('tp.cate_id', $productCategory);
                }

                if (!empty($serialNumber)) {
                    $orderQuery->leftJoin('tbl_product_serial_numbers as tpsn', 'top.pro_id', '=', 'tpsn.pro_id')
                              ->where('tpsn.serial_number', 'like', "%{$serialNumber}%");
                }

                $validOrderIds = $orderQuery->pluck('order_id')->toArray();
                
                if (!empty($validOrderIds)) {
                    $query->whereIn('o_id', $validOrderIds);
                } else {
                    // No matching orders found, return empty result
                    $query->where('invoice_id', '=', 0);
                }
            }

            // Pagination and sorting
            $sortKey = $request->get('sort_key', 'invoice_generated_date');
            $sortValue = $request->get('sort_value', 'desc');
            $paginator = $query->orderBy($sortKey, $sortValue)
                ->paginate($perPage, ['*'], 'page', $page);

            $invoices = $paginator->items();

            // Format the response
            $formattedInvoices = collect($invoices)->map(function ($invoice) {
                // Get products for this invoice from order_product table
                $products = DB::table('tbl_order_product')
                    ->select(['pro_id', 'pro_name as product_name', 'pro_quantity as quantity', 'pro_price as unit_price'])
                    ->where('order_id', $invoice->order_id)
                    ->where('deleteflag', 'active')
                    ->get()
                    ->map(function($product) {
                        return [
                            'pro_id' => $product->pro_id,
                            'product_name' => $product->product_name,
                            'quantity' => $product->quantity,
                            'unit_price' => number_format($product->unit_price ?? 0, 2),
                            'line_total' => number_format(($product->quantity ?? 0) * ($product->unit_price ?? 0), 2)
                        ];
                    });

                // Check IRN status
                $irnData = DB::table('tbl_tax_invoice_gst_irn_response')
                    ->select(['irn', 'ackno', 'ackdt', 'ewbno', 'ewbdt', 'irn_status'])
                    ->where('invoice_id', $invoice->invoice_id)
                    ->first();

                // Calculate amounts with exchange rate
                $exchangeRate = $invoice->exchange_rate ?? 1;
                $subTotalWithRate = ($invoice->sub_total_amount_without_gst ?? 0) * $exchangeRate;
                $gstAmountWithRate = ($invoice->total_gst_amount ?? 0) * $exchangeRate;
                $freightAmountWithRate = ($invoice->freight_amount ?? 0) * $exchangeRate;
                $totalAmount = $subTotalWithRate + $gstAmountWithRate + $freightAmountWithRate;

                return [
                    'invoice_id' => $invoice->invoice_id,
                    'invoice_date' => $invoice->invoice_generated_date ? 
                        Carbon::parse($invoice->invoice_generated_date)->format('d M Y') : null,
                    'company_name' => $invoice->customer_name,
                    'account_manager' => admin_name($invoice->prepared_by ?? 0),
                    'products_services' => $products->pluck('product_name')->join(', ') ?: 'No products',
                    'net_value' => number_format($subTotalWithRate, 2),
                    'total_value' => number_format($totalAmount, 2), 
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedInvoices,
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'pageSize' => $paginator->perPage(),
                    'totalItems' => $paginator->total(),
                    'totalPages' => $paginator->lastPage()
                ],
                'summary' => [
                    'total_invoices' => $paginator->total()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching invoice list: ' . $e->getMessage());
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch invoices: ' . $e->getMessage()
            ], 500);
        }
    }
    // detailed implementation
    public function getInvoicesList(Request $request)
    {
        try {
            // Get pagination parameters
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('records', 10);
            
            // Get filter parameters from request
            $companyName = $request->get('company_name');
            $invoiceNumber = $request->get('invoice_number');
            $serialNumber = $request->get('serial_number');
            $mobileNumber = $request->get('mobile_number');
            $accountManager = $request->get('account_manager');
            $productService = $request->get('product_service'); // Product or Service
            $productCategory = $request->get('product_category');
            $productName = $request->get('product_name');
            $serviceName = $request->get('service_name');
            $invoiceStatus = $request->get('invoice_status');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $financialYear = $request->get('financial_year'); // e.g., "2025-2026"
            
            // Set default financial year if not provided (current FY)
            if (empty($financialYear)) {
                $currentMonth = date('n'); // 1-12
                $currentYear = date('Y');
                
                // Financial year starts from April (month 4)
                if ($currentMonth >= 4) {
                    // Apr-Mar: 2025-2026 (if current year is 2025)
                    $financialYear = $currentYear . '-' . ($currentYear + 1);
                } else {
                    // Jan-Mar: 2024-2025 (if current year is 2025)
                    $financialYear = ($currentYear - 1) . '-' . $currentYear;
                }
            }
            
            // Build base query with filters
            $query = DB::table('tbl_tax_invoice')
                ->select([
                    'invoice_id',
                    'o_id as order_id', 
                    'po_no as invoice_number',
                    'invoice_generated_date',
                    'po_due_date as due_date',
                    'cus_com_name as customer_name',
                    'con_name as contact_name',
                    'invoice_status',
                    'invoice_approval_status',
                    'sub_total_amount_without_gst',
                    'total_gst_amount',
                    'freight_amount',
                    'prepared_by',
                    'payment_terms',
                    'invoice_type',
                    'eway_bill_no',
                    'exchange_rate'
                ])
                ->where('deleteflag', 'active');

            // Apply financial year filter (always applied, defaults to current FY)
            if (!empty($financialYear)) {
                $yearParts = explode('-', $financialYear);
                if (count($yearParts) == 2) {
                    $startYear = $yearParts[0];
                    $endYear = $yearParts[1];
                    
                    // Financial year: April 1st of start year to March 31st of end year
                    $fyStartDate = $startYear . '-04-01';
                    $fyEndDate = $endYear . '-03-31';
                    
                    $query->whereBetween('invoice_generated_date', [$fyStartDate, $fyEndDate]);
                }
            }

            // Apply filters
            if (!empty($companyName)) {
                $query->where('cus_com_name', 'like', "%{$companyName}%");
            }

            if (!empty($invoiceNumber)) {
                $query->where('invoice_id', 'like', "%{$invoiceNumber}%");
            }

            if (!empty($accountManager)) {
                $query->where('prepared_by', $accountManager);
            }

            if (!empty($invoiceStatus)) {
                $query->where('invoice_status', $invoiceStatus);
            }

            if (!empty($dateFrom) && !empty($dateTo)) {
                $query->whereBetween('invoice_generated_date', [$dateFrom, $dateTo]);
            } elseif (!empty($dateFrom)) {
                $query->where('invoice_generated_date', '>=', $dateFrom);
            } elseif (!empty($dateTo)) {
                $query->where('invoice_generated_date', '<=', $dateTo);
            }

            // Additional filters that require joins to other tables
            if (!empty($productName) || !empty($serviceName) || !empty($productCategory) || !empty($serialNumber) || !empty($mobileNumber)) {
                // Get invoice IDs that match product/service criteria
                $orderQuery = DB::table('tbl_order_product as top')
                    ->select('top.order_id')
                    ->where('top.deleteflag', 'active');

                if (!empty($productName)) {
                    $orderQuery->where('top.pro_name', 'like', "%{$productName}%");
                }

                if (!empty($productCategory)) {
                    $orderQuery->leftJoin('tbl_products as tp', 'top.pro_id', '=', 'tp.pro_id')
                              ->where('tp.cate_id', $productCategory);
                }

                if (!empty($serialNumber)) {
                    $orderQuery->leftJoin('tbl_product_serial_numbers as tpsn', 'top.pro_id', '=', 'tpsn.pro_id')
                              ->where('tpsn.serial_number', 'like', "%{$serialNumber}%");
                }

                $validOrderIds = $orderQuery->pluck('order_id')->toArray();
                
                if (!empty($validOrderIds)) {
                    $query->whereIn('o_id', $validOrderIds);
                } else {
                    // No matching orders found, return empty result
                    $query->where('invoice_id', '=', 0);
                }
            }

            // Get total count for pagination
            $total = clone $query;
            $totalCount = $total->count();

            // Apply pagination and ordering
            $invoices = $query->orderBy('invoice_generated_date', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Format the response
            $formattedInvoices = $invoices->map(function ($invoice) {
                // Get products for this invoice from order_product table
                $products = DB::table('tbl_order_product')
                    ->select(['pro_id', 'pro_name as product_name', 'pro_quantity as quantity', 'pro_price as unit_price'])
                    ->where('order_id', $invoice->order_id)
                    ->where('deleteflag', 'active')
                    ->get()
                    ->map(function($product) {
                        return [
                            'pro_id' => $product->pro_id,
                            'product_name' => $product->product_name,
                            'quantity' => $product->quantity,
                            'unit_price' => number_format($product->unit_price ?? 0, 2),
                            'line_total' => number_format(($product->quantity ?? 0) * ($product->unit_price ?? 0), 2)
                        ];
                    });

                // Check IRN status
                $irnData = DB::table('tbl_tax_invoice_gst_irn_response')
                    ->select(['irn', 'ackno', 'ackdt', 'ewbno', 'ewbdt', 'irn_status'])
                    ->where('invoice_id', $invoice->invoice_id)
                    ->first();

                // Calculate amounts with exchange rate
                $exchangeRate = $invoice->exchange_rate ?? 1;
                $subTotalWithRate = ($invoice->sub_total_amount_without_gst ?? 0) * $exchangeRate;
                $gstAmountWithRate = ($invoice->total_gst_amount ?? 0) * $exchangeRate;
                $freightAmountWithRate = ($invoice->freight_amount ?? 0) * $exchangeRate;
                $totalAmount = $subTotalWithRate + $gstAmountWithRate + $freightAmountWithRate;

                return [
                    'invoice_id' => $invoice->invoice_id,
                    'invoice_date' => $invoice->invoice_generated_date ? 
                        Carbon::parse($invoice->invoice_generated_date)->format('d M Y') : null,
                    'company_name' => $invoice->customer_name,
                    'account_manager' => admin_name($invoice->prepared_by ?? 0),
                    'products_services' => $products->pluck('product_name')->join(', ') ?: 'No products',
                    'net_value' => number_format($subTotalWithRate, 2),
                    'total_value' => number_format($totalAmount, 2),
                    'exchange_rate' => $exchangeRate,
                    'view_invoice' => [
                        'invoice_number' => $invoice->invoice_number,
                        'order_id' => $invoice->order_id,
                        'due_date' => $invoice->due_date ? 
                            Carbon::parse($invoice->due_date)->format('d M Y') : null,
                        'contact_name' => $invoice->contact_name,
                        'products' => $products,
                        'subtotal_amount' => number_format($subTotalWithRate, 2),
                        'gst_amount' => number_format($gstAmountWithRate, 2),
                        'freight_amount' => number_format($freightAmountWithRate, 2),
                        'exchange_rate' => $exchangeRate,
                        'invoice_status' => $invoice->invoice_status,
                        'invoice_type' => $invoice->invoice_type,
                        'payment_terms' => $invoice->payment_terms,
                        'eway_bill_no' => $invoice->eway_bill_no,
                        'irn_details' => [
                            'irn' => $irnData->irn ?? null,
                            'ack_no' => $irnData->ackno ?? null,
                            'ack_date' => $irnData->ackdt ?? null,
                            'ewb_no' => $irnData->ewbno ?? null,
                            'ewb_date' => $irnData->ewbdt ?? null,
                            'irn_status' => $irnData->irn_status ?? null
                        ],
                        'actions' => [
                            'can_edit' => $invoice->invoice_status !== 'approved',
                            'can_generate_irn' => empty($irnData->irn ?? null) && $invoice->invoice_status === 'approved',
                            'can_download' => !empty($irnData->irn ?? null),
                            'can_generate_eway' => !empty($irnData->irn ?? null) && empty($irnData->ewbno ?? null)
                        ]
                    ]
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedInvoices,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_records' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage)
                ],
                'summary' => [
                    'total_invoices' => $totalCount
                ],
                'filters_applied' => [
                    'financial_year' => $financialYear,
                    'company_name' => $companyName,
                    'invoice_number' => $invoiceNumber,
                    'account_manager' => $accountManager,
                    'product_name' => $productName,
                    'service_name' => $serviceName,
                    'product_category' => $productCategory,
                    'invoice_status' => $invoiceStatus,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'serial_number' => $serialNumber,
                    'mobile_number' => $mobileNumber
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching invoice list: ' . $e->getMessage());
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter options for dropdown menus
     */
    public function getFilterOptions(Request $request)
    {
        try {
            // Get account managers
            $accountManagers = DB::table('tbl_admin')
                ->select('admin_id', 'admin_name')
                ->where('deleteflag', 'active')
                ->where('admin_status', 'active')
                ->orderBy('admin_name')
                ->get()
                ->map(function($admin) {
                    return [
                        'id' => $admin->admin_id,
                        'name' => $admin->admin_name
                    ];
                });

            // Get companies
            $companies = DB::table('tbl_tax_invoice')
                ->select('cus_com_name as company_name')
                ->where('deleteflag', 'active')
                ->whereNotNull('cus_com_name')
                ->where('cus_com_name', '!=', '')
                ->distinct()
                ->orderBy('cus_com_name')
                ->pluck('company_name');

            // Get product categories
            $productCategories = DB::table('tbl_category')
                ->select('cate_id', 'cate_name')
                ->where('deleteflag', 'active')
                ->where('status', 'active')
                ->orderBy('cate_name')
                ->get()
                ->map(function($category) {
                    return [
                        'id' => $category->cate_id,
                        'name' => $category->cate_name
                    ];
                });

            // Get products
            $products = DB::table('tbl_products')
                ->select('pro_id', 'pro_name', 'cate_id')
                ->where('deleteflag', 'active')
                ->where('status', 'active')
                ->orderBy('pro_name')
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->pro_id,
                        'name' => $product->pro_name,
                        'category_id' => $product->cate_id
                    ];
                });

            // Get services
            $services = DB::table('tbl_service')
                ->select('ser_id', 'ser_name', 'ser_cate_id')
                ->where('deleteflag', 'active')
                ->where('status', 'active')
                ->orderBy('ser_name')
                ->get()
                ->map(function($service) {
                    return [
                        'id' => $service->ser_id,
                        'name' => $service->ser_name,
                        'category_id' => $service->ser_cate_id
                    ];
                });

            // Get invoice statuses
            $invoiceStatuses = DB::table('tbl_tax_invoice')
                ->select('invoice_status')
                ->where('deleteflag', 'active')
                ->whereNotNull('invoice_status')
                ->where('invoice_status', '!=', '')
                ->distinct()
                ->orderBy('invoice_status')
                ->pluck('invoice_status');

            // Generate financial year options (current + past 5 years + future 2 years)
            $financialYears = [];
            $currentMonth = date('n');
            $currentYear = date('Y');
            
            // Determine current financial year
            $currentFY = ($currentMonth >= 4) ? 
                $currentYear . '-' . ($currentYear + 1) : 
                ($currentYear - 1) . '-' . $currentYear;
            
            // Generate FY options (5 years back to 2 years forward)
            for ($i = -5; $i <= 2; $i++) {
                if ($currentMonth >= 4) {
                    $startYear = $currentYear + $i;
                } else {
                    $startYear = ($currentYear - 1) + $i;
                }
                $endYear = $startYear + 1;
                $fy = $startYear . '-' . $endYear;
                
                $financialYears[] = [
                    'id' => $fy,
                    'name' => 'FY ' . $fy,
                    'is_current' => ($fy === $currentFY)
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'account_managers' => $accountManagers,
                    'companies' => $companies,
                    'product_categories' => $productCategories,
                    'products' => $products,
                    'services' => $services,
                    'invoice_statuses' => $invoiceStatuses,
                    'financial_years' => $financialYears,
                    'current_financial_year' => $currentFY,
                    'type_options' => [
                        ['id' => 'product', 'name' => 'Product'],
                        ['id' => 'service', 'name' => 'Service']
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching filter options: ' . $e->getMessage());
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch filter options: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard summary with key metrics
     * GET /api/accounts/invoicing/dashboard
     */

    /**
     * Get invoicing dashboard summary
     * GET /api/accounts/invoicing/dashboard
     */
    public function getDashboardSummary(Request $request)
    {
        try {
            $financialYear = $request->get('financial_year');
            $currentDate = Carbon::now();
            // If no financial year specified, use current
            if (!$financialYear) {
                $financialYear = $currentDate->month >= 4 ? 
                    $currentDate->year . '-' . ($currentDate->year + 1) : 
                    ($currentDate->year - 1) . '-' . $currentDate->year;
            }
            [$startYear, $endYear] = explode('-', $financialYear);
            $fyStart = Carbon::createFromDate($startYear, 4, 1)->startOfDay();
            $fyEnd = Carbon::createFromDate($endYear, 3, 31)->endOfDay();

            // Collect all filters
            $filters = [
                'company_name' => $request->get('company_name'),
                'invoice_number' => $request->get('invoice_number'),
                'serial_number' => $request->get('serial_number'),
                'mobile_number' => $request->get('mobile_number'),
                'account_manager' => $request->get('account_manager'),
                'product_service' => $request->get('product_service'),
                'product_category' => $request->get('product_category'),
                'product_name' => $request->get('product_name'),
                'service_name' => $request->get('service_name'),
                'invoice_status' => $request->get('invoice_status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            // Helper to apply filters to a query
            $applyFilters = function($query, $filters, $dateRange = null) {
                if (!empty($filters['company_name'])) {
                    $query->where('cus_com_name', 'like', "%{$filters['company_name']}%");
                }
                if (!empty($filters['invoice_number'])) {
                    $query->where('invoice_id', 'like', "%{$filters['invoice_number']}%");
                }
                if (!empty($filters['account_manager'])) {
                    $query->where('prepared_by', $filters['account_manager']);
                }
                if (!empty($filters['invoice_status'])) {
                    $query->where('invoice_status', $filters['invoice_status']);
                }
                if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                    $query->whereBetween('invoice_generated_date', [$filters['date_from'], $filters['date_to']]);
                } elseif (!empty($filters['date_from'])) {
                    $query->where('invoice_generated_date', '>=', $filters['date_from']);
                } elseif (!empty($filters['date_to'])) {
                    $query->where('invoice_generated_date', '<=', $filters['date_to']);
                }
                if ($dateRange) {
                    $query->whereBetween('invoice_generated_date', $dateRange);
                }
                // Product/service/category/serial/mobile filters (joins)
                if (!empty($filters['product_name']) || !empty($filters['service_name']) || !empty($filters['product_category']) || !empty($filters['serial_number']) || !empty($filters['mobile_number']) || !empty($filters['product_service'])) {
                    $orderQuery = DB::table('tbl_order_product as top')
                        ->select('top.order_id')
                        ->where('top.deleteflag', 'active');
                    if (!empty($filters['product_name'])) {
                        $orderQuery->where('top.pro_name', 'like', "%{$filters['product_name']}%");
                    }
                    if (!empty($filters['product_category'])) {
                        $orderQuery->leftJoin('tbl_products as tp', 'top.pro_id', '=', 'tp.pro_id')
                                  ->where('tp.cate_id', $filters['product_category']);
                    }
                    if (!empty($filters['serial_number'])) {
                        $orderQuery->leftJoin('tbl_product_serial_numbers as tpsn', 'top.pro_id', '=', 'tpsn.pro_id')
                                  ->where('tpsn.serial_number', 'like', "%{$filters['serial_number']}%");
                    }
                    $validOrderIds = $orderQuery->pluck('order_id')->toArray();
                    if (!empty($validOrderIds)) {
                        $query->whereIn('o_id', $validOrderIds);
                    } else {
                        $query->where('invoice_id', '=', 0);
                    }
                }
                return $query;
            };

            // Today
            $today = Carbon::today();
            $todayQuery = DB::table('tbl_tax_invoice')->where('deleteflag', 'active');
            $todayQuery = $applyFilters($todayQuery, $filters, [$today->toDateString(), $today->toDateString()]);
            $todayTotal = $todayQuery->sum(DB::raw('sub_total_amount_without_gst + total_gst_amount + freight_amount')) ?? 0;
            $todayCount = $todayQuery->count();

            // Month
            $startOfMonth = $currentDate->copy()->startOfMonth();
            $endOfMonth = $currentDate->copy()->endOfMonth();
            $monthQuery = DB::table('tbl_tax_invoice')->where('deleteflag', 'active');
            $monthQuery = $applyFilters($monthQuery, $filters, [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
            $monthTotal = $monthQuery->sum(DB::raw('sub_total_amount_without_gst + total_gst_amount + freight_amount')) ?? 0;
            $monthCount = $monthQuery->count();

            // Year
            $yearQuery = DB::table('tbl_tax_invoice')->where('deleteflag', 'active');
            $yearQuery = $applyFilters($yearQuery, $filters, [$fyStart->toDateString(), $fyEnd->toDateString()]);
            $yearTotal = $yearQuery->sum(DB::raw('sub_total_amount_without_gst + total_gst_amount + freight_amount')) ?? 0;
            $yearCount = $yearQuery->count();

            // Credit notes
            $creditQuery = DB::table('tbl_tax_credit_note_invoice')->where('deleteflag', 'active');
            $creditQuery->whereBetween('credit_invoice_generated_date', [$fyStart->toDateString(), $fyEnd->toDateString()]);
            $creditTotal = $creditQuery->sum(DB::raw('(sub_total_amount_without_gst + total_gst_amount + freight_amount)')) ?? 0;
            $creditCount = $creditQuery->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'financial_year' => $financialYear,
                    'today' => [
                        'total_amount' => $todayTotal,
                        'count' => $todayCount
                    ],
                    'this_month' => [
                        'total_amount' => $monthTotal,
                        'count' => $monthCount
                    ],
                    'this_year' => [
                        'total_amount' => $yearTotal,
                        'count' => $yearCount
                    ],
                    'credit_notes' => [
                        'total_amount' => $creditTotal,
                        'count' => $creditCount
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching invoicing dashboard', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch dashboard summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoice details by ID
     * GET /api/accounts/invoicing/{invoice_id}
     */
    public function getInvoiceDetails($invoiceId)
    {
        try {
            $invoice = DB::table('tbl_tax_invoice as tti')
                ->leftJoin('tbl_tax_invoice_gst_irn_response as tirn', 'tti.invoice_id', '=', 'tirn.invoice_id')
                ->select([
                    'tti.*',
                    'tirn.irn',
                    'tirn.ackno',
                    'tirn.ackdt',
                    'tirn.ewbno',
                    'tirn.ewbdt',
                    'tirn.ewbvalidtill',
                    'tirn.qrcodeurl',
                    'tirn.irn_status'
                ])
                ->where('tti.invoice_id', $invoiceId)
                ->where('tti.deleteflag', 'active')
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice not found'
                ], 404);
            }

            // Get invoice products
            $products = DB::table('tbl_order_product')
                ->where('order_id', $invoice->o_id)
                ->where('deleteflag', 'active')
                ->get();

            // Get credit notes for this invoice
            $creditNotes = $this->getCreditNotesByInvoiceId($invoiceId);

            $formattedInvoice = [
                'invoice_id' => $invoice->invoice_id,
                'order_id' => $invoice->o_id,
                'invoice_number' => $invoice->po_no,
                'invoice_date' => $invoice->invoice_generated_date,
                'due_date' => $invoice->po_due_date,
                'customer_details' => [
                    'company_name' => $invoice->cus_com_name,
                    'contact_name' => $invoice->con_name,
                    'address' => $invoice->con_address,
                    'city' => $invoice->con_city,
                    'state' => $invoice->con_state,
                    'country' => $invoice->con_country,
                    'pincode' => $invoice->con_pincode,
                    'mobile' => $invoice->con_mobile,
                    'email' => $invoice->con_email,
                    'gst_number' => $invoice->con_gst
                ],
                'billing_details' => [
                    'buyer_name' => $invoice->buyer_name,
                    'buyer_address' => $invoice->buyer_address,
                    'buyer_city' => $invoice->buyer_city,
                    'buyer_state' => $invoice->buyer_state,
                    'buyer_country' => $invoice->buyer_country,
                    'buyer_pincode' => $invoice->buyer_pin_code,
                    'buyer_mobile' => $invoice->buyer_mobile,
                    'buyer_email' => $invoice->buyer_email,
                    'buyer_gst' => $invoice->buyer_gst
                ],
                'products' => $products->map(function($product) {
                    return [
                        'product_id' => $product->pro_id,
                        'product_name' => $product->pro_name,
                        'model' => $product->pro_model,
                        'quantity' => $product->pro_quantity,
                        'unit_price' => number_format($product->pro_price ?? 0, 2),
                        'total' => number_format(($product->pro_quantity ?? 0) * ($product->pro_price ?? 0), 2),
                        'service_period' => $product->service_period
                    ];
                }),
                'amounts' => [
                    'subtotal' => number_format($invoice->sub_total_amount_without_gst ?? 0, 2),
                    'gst_amount' => number_format($invoice->total_gst_amount ?? 0, 2),
                    'freight_amount' => number_format($invoice->freight_amount ?? 0, 2),
                    'freight_gst' => number_format($invoice->freight_gst_amount ?? 0, 2),
                    'total_amount' => number_format(
                        ($invoice->sub_total_amount_without_gst ?? 0) + 
                        ($invoice->total_gst_amount ?? 0) + 
                        ($invoice->freight_amount ?? 0) + 
                        ($invoice->freight_gst_amount ?? 0), 2
                    )
                ],
                'status_info' => [
                    'invoice_status' => $invoice->invoice_status,
                    'approval_status' => $invoice->invoice_approval_status,
                    'prepared_by' => $invoice->prepared_by ?? 0 // admin_name($invoice->prepared_by ?? 0)
                ],
                'irn_details' => [
                    'irn' => $invoice->irn,
                    'ack_no' => $invoice->ackno,
                    'ack_date' => $invoice->ackdt,
                    'ewb_no' => $invoice->ewbno,
                    'ewb_date' => $invoice->ewbdt,
                    'ewb_valid_till' => $invoice->ewbvalidtill,
                    'qr_code_url' => $invoice->qrcodeurl,
                    'irn_status' => $invoice->irn_status
                ],
                'credit_notes' => $creditNotes,
                'other_details' => [
                    'invoice_type' => $invoice->invoice_type,
                    'payment_terms' => $invoice->payment_terms,
                    'gst_sale_type' => $invoice->gst_sale_type,
                    'currency' => $invoice->invoice_currency ?? 'INR',
                    'eway_bill_no' => $invoice->eway_bill_no,
                    'delivery_note' => $invoice->delivery_note,
                    'ref_no_and_date' => $invoice->ref_no_and_date,
                    'terms_of_delivery' => $invoice->terms_of_delivery,
                    'destination' => $invoice->destination
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $formattedInvoice
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching invoice details', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch invoice details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate IRN for invoice
     * POST /api/accounts/invoicing/{invoice_id}/generate-irn
     */
    public function generateIRN(Request $request, $invoiceId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'generate_eway_bill' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation Error!',
                    'data' => $validator->errors()
                ], 400);
            }

            // Check if invoice exists and is approved
            $invoice = DB::table('tbl_tax_invoice')
                ->where('invoice_id', $invoiceId)
                ->where('deleteflag', 'active')
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice not found'
                ], 404);
            }

            if ($invoice->invoice_status !== 'approved') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice must be approved before generating IRN'
                ], 400);
            }

            // Check if IRN already exists
            $existingIRN = DB::table('tbl_tax_invoice_gst_irn_response')
                ->where('invoice_id', $invoiceId)
                ->whereNotNull('irn')
                ->where('irn', '!=', '')
                ->first();

            if ($existingIRN) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'IRN already generated for this invoice',
                    'data' => ['irn' => $existingIRN->irn]
                ], 400);
            }

            // Here you would integrate with the actual IRN generation API
            // For now, we'll simulate the response
            $generateEwayBill = $request->get('generate_eway_bill', false);
            
            // Mock IRN generation - replace with actual API integration
            $irnResponse = $this->mockIRNGeneration($invoice, $generateEwayBill);

            // Save IRN response to database
            DB::table('tbl_tax_invoice_gst_irn_response')->updateOrInsert(
                ['invoice_id' => $invoiceId],
                [
                    'irn' => $irnResponse['irn'],
                    'ackno' => $irnResponse['ackno'],
                    'ackdt' => $irnResponse['ackdt'],
                    'signedinvoice' => $irnResponse['signedinvoice'] ?? null,
                    'signedqrcode' => $irnResponse['signedqrcode'] ?? null,
                    'ewbno' => $irnResponse['ewbno'] ?? null,
                    'ewbdt' => $irnResponse['ewbdt'] ?? null,
                    'ewbvalidtill' => $irnResponse['ewbvalidtill'] ?? null,
                    'qrcodeurl' => $irnResponse['qrcodeurl'] ?? null,
                    'irn_status' => 'generated',
                    'updated_at' => now()
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'IRN generated successfully',
                'data' => $irnResponse
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error generating IRN', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to generate IRN',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export invoices to Excel
     * POST /api/accounts/invoicing/export
     */
    public function exportInvoices(Request $request)
    {
        try {
            // Apply same filters as the list endpoint
            $query = $this->buildInvoicesQuery($request);
            
            // Get all records for export (remove pagination)
            $invoices = $query->get();

            // Format data for export
            $exportData = $invoices->map(function ($invoice) {
                return [
                    'Invoice ID' => $invoice->invoice_id,
                    'Order ID' => $invoice->order_id,
                    'Invoice Number' => $invoice->po_no,
                    'Invoice Date' => $invoice->invoice_generated_date,
                    'Due Date' => $invoice->po_due_date,
                    'Customer Name' => $invoice->customer_name,
                    'Contact Name' => $invoice->contact_name,
                    'Product Name' => $invoice->product_name,
                    'Quantity' => $invoice->pro_quantity,
                    'Unit Price' => $invoice->pro_price,
                    'Subtotal Amount' => $invoice->sub_total_amount_without_gst,
                    'GST Amount' => $invoice->total_gst_amount,
                    'Freight Amount' => $invoice->freight_amount,
                    'Total Amount' => ($invoice->sub_total_amount_without_gst ?? 0) + 
                                   ($invoice->total_gst_amount ?? 0) + 
                                   ($invoice->freight_amount ?? 0),
                    'Invoice Status' => $invoice->invoice_status,
                    'Approval Status' => $invoice->invoice_approval_status,
                    'IRN' => $invoice->irn,
                    'E-way Bill No' => $invoice->ewbno,
                    'Prepared By' => $invoice->prepared_by ?? 0 // admin_name($invoice->prepared_by ?? 0)
                ];
            });

            // Generate filename with timestamp
            $filename = 'invoices_export_' . Carbon::now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filepath = storage_path('app/exports/' . $filename);


            // Ensure directory exists
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            // Here you would use Laravel Excel or similar to generate the actual Excel file
            // For now, we'll return the data structure
            
            return response()->json([
                'status' => 'success',
                'message' => 'Export prepared successfully',
                'data' => [
                    'filename' => $filename,
                    'records_count' => count($exportData),
                    'download_url' => url('api/accounts/invoicing/download-export/' . $filename)
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error exporting invoices', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to export invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper methods

    private function getAppliedFiltersCount(Request $request)
    {
        $filters = [
            'order_id', 'company_name', 'account_manager', 'product_name',
            'invoice_type', 'irn_status', 'invoice_number', 'status',
            'date_from', 'date_to', 'serial_number'
        ];

        $count = 0;
        foreach ($filters as $filter) {
            if (!empty($request->get($filter))) {
                $count++;
            }
        }

        return $count;
    }

    private function getTodayInvoicingSummary()
    {
        $today = Carbon::today();
        
        return [
            'total_amount' => DB::table('tbl_tax_invoice')
                ->whereDate('invoice_generated_date', $today)
                ->where('deleteflag', 'active')
                ->sum(DB::raw('sub_total_amount_without_gst + total_gst_amount + freight_amount')) ?? 0,
            'count' => DB::table('tbl_tax_invoice')
                ->whereDate('invoice_generated_date', $today)
                ->where('deleteflag', 'active')
                ->count()
        ];
    }

    private function getMonthlyInvoicingSummary($date)
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        
        return [
            'total_amount' => DB::table('tbl_tax_invoice')
                ->whereBetween('invoice_generated_date', [$startOfMonth, $endOfMonth])
                ->where('deleteflag', 'active')
                ->sum(DB::raw('sub_total_amount_without_gst + total_gst_amount + freight_amount')) ?? 0,
            'count' => DB::table('tbl_tax_invoice')
                ->whereBetween('invoice_generated_date', [$startOfMonth, $endOfMonth])
                ->where('deleteflag', 'active')
                ->count()
        ];
    }

    private function getYearlyInvoicingSummary($fyStart, $fyEnd)
    {
        return [
            'total_amount' => DB::table('tbl_tax_invoice')
                ->whereBetween('invoice_generated_date', [$fyStart, $fyEnd])
                ->where('deleteflag', 'active')
                ->sum(DB::raw('sub_total_amount_without_gst + total_gst_amount + freight_amount')) ?? 0,
            'count' => DB::table('tbl_tax_invoice')
                ->whereBetween('invoice_generated_date', [$fyStart, $fyEnd])
                ->where('deleteflag', 'active')
                ->count()
        ];
    }

    private function getCreditNotesSummary($fyStart, $fyEnd)
    {
        return [
            'total_amount' => DB::table('tbl_tax_credit_note_invoice')
                ->whereBetween('credit_invoice_generated_date', [$fyStart, $fyEnd])
                ->where('deleteflag', 'active')
                ->sum(DB::raw('(sub_total_amount_without_gst + total_gst_amount + freight_amount)')) ?? 0,
            'count' => DB::table('tbl_tax_credit_note_invoice')
                ->whereBetween('credit_invoice_generated_date', [$fyStart, $fyEnd])
                ->where('deleteflag', 'active')
                ->count()
        ];
    }

    private function getCreditNotesByInvoiceId($invoiceId)
    {
        return DB::table('tbl_tax_credit_note_invoice')
            ->where('invoice_id', $invoiceId)
            ->where('deleteflag', 'active')
            ->get()
            ->map(function($cn) {
                $totalAmount = ($cn->sub_total_amount_without_gst ?? 0) + 
                              ($cn->total_gst_amount ?? 0) + 
                              ($cn->freight_amount ?? 0);
                return [
                    'credit_note_id' => $cn->credit_note_invoice_id,
                    'credit_note_number' => $cn->po_no,
                    'credit_note_date' => $cn->credit_invoice_generated_date,
                    'credit_note_amount' => number_format($totalAmount, 2),
                    'reason' => $cn->invoice_remarks ?? ''
                ];
            });
    }

    private function buildInvoicesQuery(Request $request)
    {
        // This method builds the same query as getInvoicesList but without pagination
        // Used for export functionality
        
        $query = DB::table('tbl_tax_invoice as tti')
            ->leftJoin('tbl_order_product as tip', 'tti.o_id', '=', 'tip.order_id')
            ->leftJoin('tbl_tax_invoice_gst_irn_response as tirn', 'tti.invoice_id', '=', 'tirn.invoice_id')
            ->select([
                'tti.invoice_id',
                'tti.o_id as order_id',
                'tti.po_no',
                'tti.invoice_generated_date',
                'tti.po_due_date',
                'tti.cus_com_name as customer_name',
                'tti.con_name as contact_name',
                'tti.prepared_by',
                'tti.invoice_status',
                'tti.invoice_approval_status',
                'tti.sub_total_amount_without_gst',
                'tti.total_gst_amount',
                'tti.freight_amount',
                'tip.pro_name as product_name',
                'tip.pro_quantity',
                'tip.pro_price',
                'tirn.irn',
                'tirn.ewbno'
            ])
            ->where('tti.deleteflag', 'active');

        // Apply all the same filters as in getInvoicesList
        // ... (implement filter logic)

        return $query;
    }

    private function mockIRNGeneration($invoice, $generateEwayBill = false)
    {
        // Mock IRN generation - replace with actual GST API integration
        $irn = strtoupper(uniqid('IRN'));
        $ackno = strtoupper(uniqid('ACK'));
        
        $response = [
            'irn' => $irn,
            'ackno' => $ackno,
            'ackdt' => Carbon::now()->format('Y-m-d H:i:s'),
            'signedinvoice' => base64_encode('mock_signed_invoice_data'),
            'signedqrcode' => base64_encode('mock_qr_code_data'),
            'qrcodeurl' => 'https://mockapi.example.com/qr/' . $irn
        ];

        if ($generateEwayBill) {
            $response['ewbno'] = rand(100000000000, 999999999999);
            $response['ewbdt'] = Carbon::now()->format('Y-m-d H:i:s');
            $response['ewbvalidtill'] = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');
        }

        return $response;
    }

   

    

    public function getInvoicingListTest(Request $request)
    {
       
        $query = DB::table('tbl_tax_invoice')
        ->where('deleteflag', 'active');

    // Apply filters
    if ($request->filled('company_name')) {
        $query->where('company_name', 'like', '%' . $request->company_name . '%');
    }
    if ($request->filled('invoice_number')) {
        $query->where('invoice_number', $request->invoice_number);
    }
    if ($request->filled('account_manager')) {
        $query->where('account_manager', $request->account_manager);
    }
    // ...add other filters similarly...

    // Date range filter
    if ($request->filled('date_from') && $request->filled('date_to')) {
        $query->whereBetween('invoice_generated_date', [$request->date_from, $request->date_to]);
    }

    // Pagination and sorting
    $sortKey = $request->get('sort_key', 'invoice_generated_date');
    $sortValue = $request->get('sort_value', 'desc');
    $perPage = $request->get('records', 10);

    $invoices = $query->orderBy($sortKey, $sortValue)->paginate($perPage);

    return response()->json([
        'status' => 'success',
        'data' => $invoices,
        'filters_applied' => $request->all(),
    ]);
    }
    
    /**
     * Create a new invoice with products and details based on UI form submission
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createInvoice(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                // Buyer details validation
                'buyerDetails' => 'required|array',
                'buyerDetails.id' => 'required|integer',
                'buyerDetails.company_name' => 'required|string|max:255',
                'buyerDetails.address' => 'required|string',
                'buyerDetails.email_id' => 'required|email|max:255',
                'buyerDetails.pan_no' => 'required|string|max:20',
                'buyerDetails.gst_no' => 'required|string|max:30',
                'buyerDetails.tenant_id' => 'sometimes|integer',
                
                // Consignee details validation
                'consigneeDetails' => 'required|array',
                'consigneeDetails.id' => 'required|integer',
                'consigneeDetails.company_name' => 'required|string|max:255',
                'consigneeDetails.address' => 'required|string',
                'consigneeDetails.email_id' => 'required|email|max:255',
                'consigneeDetails.pan_no' => 'required|string|max:20',
                'consigneeDetails.gst_no' => 'required|string|max:30',
                
                // Product details validation
                'selectedProducts' => 'required|array|min:1',
                'selectedProducts.*.id' => 'required',
                'selectedProducts.*.name' => 'required|string|max:255',
                'selectedProducts.*.category' => 'nullable|string|max:100',
                'selectedProducts.*.price' => 'required|numeric|min:0',
                'selectedProducts.*.itemCode' => 'nullable|string|max:50',
                'selectedProducts.*.hsnCode' => 'nullable|string|max:20',
                'selectedProducts.*.unit' => 'nullable|string|max:20',
                'selectedProducts.*.selectedQuantity' => 'required|numeric|min:1',
                'selectedProducts.*.totalPrice' => 'required|numeric|min:0',
                'selectedProducts.*.description' => 'nullable|string',
                
                // Invoice totals validation
                'totalPrice' => 'required|numeric|min:0',
                'currency' => 'required|string|max:3',
                
                // Purchase order data validation
                'purchaseOrderData' => 'required|array',
                'purchaseOrderData.poNumber' => 'required|string|max:50',
                'purchaseOrderData.poCreationDate' => 'required|date',
                'purchaseOrderData.poDueDate' => 'required|date|after_or_equal:purchaseOrderData.poCreationDate',
                'purchaseOrderData.paymentTerms' => 'required',
                'purchaseOrderData.uploadedFileName' => 'nullable|string|max:255',
                
                // Price and warranty validation
                'priceWarrantyData' => 'required|array',
                'priceWarrantyData.taxPercentage' => 'required|string|max:10',
                'priceWarrantyData.priceInclusiveOfTaxes' => 'required|string|in:Yes,No',
                'priceWarrantyData.warranty' => 'required|string|max:20',
                'priceWarrantyData.sendInvoiceBy' => 'required',
                'priceWarrantyData.specialInstructions' => 'nullable|string',
                'priceWarrantyData.specialInvoicingInstructions' => 'nullable|string',
                
                // Dispatch details validation
                'dispatchData' => 'required|array',
                'dispatchData.modeOfDispatch' => 'required|string|max:50',
                'dispatchData.dispatch' => 'required|string|max:50',
                'dispatchData.freight' => 'required|string|max:20',
                'dispatchData.insurance' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation Error!',
                    'data' => $validator->errors()
                ], 400);
            }

            // Start database transaction
            DB::beginTransaction();
            
            // Get tenant ID - either from request or fetch from company record if not provided
            $tenantId = $request->buyerDetails['tenant_id'] ?? null;
            if (empty($tenantId)) {
                // Try to fetch tenant ID from the database using the company ID
                $companyTenant = DB::table('tbl_comp')
                    ->where('id', $request->buyerDetails['id'])
                    ->where('deleteflag', 'active')
                    ->value('tenant_id');
                    
                $tenantId = $companyTenant ?? 1; // Default to 1 if not found
            }

            // Generate invoice number with format INV-YYYYMMDD-XXXX
            $invoicePrefix = 'INV-' . Carbon::now()->format('Ymd');
            $lastInvoice = TaxInvoice::where('invoice_number', 'like', $invoicePrefix . '-%')
                                    ->orderBy('invoice_id', 'desc')
                                    ->first();
                                    
            $nextNumber = 1;
            if ($lastInvoice) {
                $parts = explode('-', $lastInvoice->invoice_number);
                $nextNumber = intval(end($parts)) + 1;
            }
            
            $invoiceNumber = $invoicePrefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            // Create invoice record
            $invoice = new TaxInvoice();
            $invoice->invoice_number = $invoiceNumber;
            $invoice->buyer_comp_id = $request->buyerDetails['id'];
            $invoice->consignee_comp_id = $request->consigneeDetails['id'];
            $invoice->invoice_date = Carbon::now()->format('Y-m-d');
            $invoice->po_number = $request->purchaseOrderData['poNumber'];
            $invoice->po_date = $request->purchaseOrderData['poCreationDate'];
            $invoice->po_due_date = $request->purchaseOrderData['poDueDate'];
            $invoice->payment_terms_id = $request->purchaseOrderData['paymentTerms'];
            $invoice->total_amount = $request->totalPrice;
            $invoice->currency = $request->currency;
            $invoice->tax_percentage = $request->priceWarrantyData['taxPercentage'];
            $invoice->price_inclusive_taxes = $request->priceWarrantyData['priceInclusiveOfTaxes'] === 'Yes' ? 1 : 0;
            $invoice->warranty_period = $request->priceWarrantyData['warranty'];
            $invoice->send_invoice_by = $request->priceWarrantyData['sendInvoiceBy'];
            $invoice->special_instructions = $request->priceWarrantyData['specialInstructions'] ?? '';
            $invoice->special_invoicing_instructions = $request->priceWarrantyData['specialInvoicingInstructions'] ?? '';
            $invoice->mode_of_dispatch = $request->dispatchData['modeOfDispatch'];
            $invoice->dispatch_period = $request->dispatchData['dispatch'];
            $invoice->freight = $request->dispatchData['freight'];
            $invoice->insurance = $request->dispatchData['insurance'];
            // Include tenant_id using the resolved value
            $invoice->tenant_id = $tenantId;
            $invoice->invoice_status = 'generated';
            $invoice->admin_id = auth()->id() ?? 1; // Default to admin ID 1 if not authenticated
            $invoice->status = 'active';
            $invoice->deleteflag = 'active';
            $invoice->save();
            
            // Create invoice products
            foreach ($request->selectedProducts as $index => $product) {
                $invoiceProduct = new InvoiceProduct();
                $invoiceProduct->tax_invoice_id = $invoice->invoice_id;
                $invoiceProduct->product_id = $product['id'];
                $invoiceProduct->product_name = $product['name'];
                $invoiceProduct->item_code = $product['itemCode'] ?? '';
                $invoiceProduct->hsn_code = $product['hsnCode'] ?? '';
                $invoiceProduct->quantity = $product['selectedQuantity'];
                $invoiceProduct->unit_price = $product['price'];
                $invoiceProduct->total_price = $product['totalPrice'];
                $invoiceProduct->currency = $request->currency;
                $invoiceProduct->tax_percentage = $product['taxClassId'] ?? 0;
                $invoiceProduct->serial_number = $index + 1; // Add serial number as shown in UI
                $invoiceProduct->category = $product['category'] ?? '';
                $invoiceProduct->unit = $product['unit'] ?? 'Pcs';
                $invoiceProduct->description = $product['description'] ?? '';
                $invoiceProduct->status = 'active';
                $invoiceProduct->deleteflag = 'active';
                // Include tenant_id using the resolved value
                $invoiceProduct->tenant_id = $tenantId;
                $invoiceProduct->save();
            }
            
            // Calculate tax based on tax percentage
            $taxValue = 0;
            $taxPercentageStr = $request->priceWarrantyData['taxPercentage'] ?? '0%';
            if (!empty($taxPercentageStr) && $taxPercentageStr != '0%') {
                // Extract numeric part from percentage string (e.g. "18%" -> 18)
                $taxPercentage = floatval(preg_replace('/[^0-9.]/', '', $taxPercentageStr));
                $taxValue = ($request->totalPrice * $taxPercentage) / 100;
            }
            
            // Calculate freight charge based on dispatch data
            $freight = 0;
            $freightType = $request->dispatchData['freight'] ?? 'Exclusive';
            if ($freightType != 'Nil' && $freightType != 'Free' && isset($request->freightValue) && is_numeric($request->freightValue)) {
                $freight = $request->freightValue;
            }
            
            // Calculate advance received (if any)
            $advanceReceived = $request->advanceReceived ?? 0;
            
            // Calculate grand total
            $subTotal = $request->totalPrice;
            $grandTotal = $subTotal + $taxValue + $freight - $advanceReceived;
            
            // Update invoice with financial details
            $invoice->tax_value = $taxValue;
            $invoice->freight_value = $freight;
            $invoice->sub_total = $subTotal;
            $invoice->advance_received = $advanceReceived;
            $invoice->grand_total = $grandTotal;
            
            // Process file upload or reference
            if ($request->hasFile('purchaseOrderData.uploadedFile')) {
                // Direct file upload handling
                $file = $request->file('purchaseOrderData.uploadedFile');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('uploads/po_documents', $fileName, 'public');
                $invoice->po_document = $filePath;
            } else if (isset($request->purchaseOrderData['uploadedFileName']) && !empty($request->purchaseOrderData['uploadedFileName'])) {
                // Store the file name reference if it was uploaded separately
                $fileName = basename($request->purchaseOrderData['uploadedFileName']);
                $invoice->po_document = 'uploads/po_documents/' . $fileName;
            }
            
            $invoice->save();

            // Create DO (Delivery Order) if requested
            if ($request->has('createDeliveryOrder') && $request->createDeliveryOrder === true) {
                // Logic to create a delivery order based on the invoice
                // This would typically involve creating a record in a delivery_orders table
                // Code would go here...
            }
            
            // Commit the transaction
            DB::commit();
            
            // Prepare comprehensive response data
            $responseData = [
                'invoice_id' => $invoice->invoice_id,
                'invoice_number' => $invoice->invoice_number,
                'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                'invoice_date' => $invoice->invoice_date,
                'financial_details' => [
                    'sub_total' => $invoice->sub_total,
                    'tax_percentage' => $invoice->tax_percentage,
                    'tax_value' => $invoice->tax_value,
                    'freight_value' => $invoice->freight_value,
                    'advance_received' => $invoice->advance_received,
                    'grand_total' => $invoice->grand_total,
                    'currency' => $invoice->currency,
                    'price_inclusive_taxes' => (bool)$invoice->price_inclusive_taxes
                ],
                'buyer' => [
                    'id' => $request->buyerDetails['id'],
                    'company_name' => $request->buyerDetails['company_name'],
                    'address' => $request->buyerDetails['address'],
                    'email' => $request->buyerDetails['email_id'],
                    'gst_no' => $request->buyerDetails['gst_no']
                ],
                'consignee' => [
                    'id' => $request->consigneeDetails['id'],
                    'company_name' => $request->consigneeDetails['company_name'],
                    'address' => $request->consigneeDetails['address'],
                    'email' => $request->consigneeDetails['email_id'],
                    'gst_no' => $request->consigneeDetails['gst_no']
                ],
                'purchase_order' => [
                    'number' => $invoice->po_number,
                    'date' => $invoice->po_date,
                    'due_date' => $invoice->po_due_date,
                    'payment_terms_id' => $invoice->payment_terms_id
                ],
                'products_summary' => [
                    'count' => count($request->selectedProducts),
                    'items' => $request->selectedProducts
                ],
                'dispatch_info' => [
                    'mode' => $invoice->mode_of_dispatch,
                    'period' => $invoice->dispatch_period,
                    'freight' => $invoice->freight,
                    'insurance' => $invoice->insurance
                ],
                'warranty_period' => $invoice->warranty_period,
                'special_instructions' => $invoice->special_instructions,
                'special_invoicing_instructions' => $invoice->special_invoicing_instructions,
                'status' => $invoice->invoice_status
            ];
            
            // Add document URLs if available
            if (!empty($invoice->po_document)) {
                $responseData['documents'] = [
                    'po_document' => [
                        'filename' => basename($invoice->po_document),
                        'url' => url('storage/' . $invoice->po_document)
                    ]
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice created successfully',
                'data' => $responseData
            ], 201);

        } catch (\Exception $e) {
            // Rollback in case of failure
            DB::rollBack();
            
            Log::error('Invoice creation failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to create invoice: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
