<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class AccountsDashboardController extends Controller
{
    /**
     * Get Invoicing dashboard data (Today, This Month, This Year, Credit Notes)
     * Based on UI screenshot showing TOD, MTD, YTD values for invoicing
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvoicingDashboard(Request $request)
    {
        try {
            // Get request parameters
            $financialYear = $request->input('financial_year', '');
            $accManager = $request->input('acc_manager', '');
            
            // Calculate date ranges
            $today = Carbon::now();
            $startOfMonth = $today->copy()->startOfMonth();
            $endOfMonth = $today->copy()->endOfMonth();
            
            // Calculate financial year dates
            if (!empty($financialYear) && preg_match('/^(\d{4})-(\d{4})$/', $financialYear)) {
                [$startYear, $endYear] = explode('-', $financialYear);
                $fyStart = $startYear . '-04-01';
                $fyEnd = $endYear . '-03-31';
            } else {
                // Auto-calculate current financial year (April to March)
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
            
            $defaultCurrencySymbol = $defaultCurrency ? html_entity_decode($defaultCurrency->currency_html_code) : '₹';
            $defaultCurrencyCode = $defaultCurrency ? $defaultCurrency->currency_code : 'INR';
            
            // 1. TOD - Invoiced Today
            $invoicedToday = invoice_total_by_date($accManager ?: '', $today->format('Y-m-d'), $today->format('Y-m-d'));
            
            // 2. MTD - Invoiced This Month
            $invoicedThisMonth = invoice_total_by_date($accManager ?: '', $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));
            
            // 3. YTD - Invoiced This Year (Financial Year)
            $invoicedThisYear = invoice_total_by_date($accManager ?: '', $fyStart, $fyEnd);
            
            // 4. MTD - Credit Notes This Month
            $creditNotesThisMonthAmount = sum_of_credit_note(0, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'), $accManager ?: '');
            $creditNotesThisMonthCount = DB::table('tbl_tax_credit_note_invoice')
                ->where('deleteflag', 'active')
                ->where('invoice_status', 'approved')
                ->whereBetween('credit_invoice_generated_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->when(!empty($accManager), function($query) use ($accManager) {
                    return $query->where('prepared_by', $accManager);
                })
                ->count();
            
            // 5. YTD - Credit Notes This Year (Financial Year)
            $creditNotesThisYearAmount = sum_of_credit_note(0, $fyStart, $fyEnd, $accManager ?: '');
            $creditNotesThisYearCount = DB::table('tbl_tax_credit_note_invoice')
                ->where('deleteflag', 'active')
                ->where('invoice_status', 'approved')
                ->whereBetween('credit_invoice_generated_date', [$fyStart, $fyEnd])
                ->when(!empty($accManager), function($query) use ($accManager) {
                    return $query->where('prepared_by', $accManager);
                })
                ->count();
            
            // Prepare response data
            $invoicingData = [
                'invoiced_today' => [
                    'label' => 'TOD',
                    'title' => 'Invoiced Today',
                    'amount' => (float) $invoicedToday,
                    'amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($invoicedToday),
                    'date' => $today->format('Y-m-d'),
                    'date_formatted' => $today->format('d-M-Y'),
                    'icon_color' => '#FF8C00',
                    'background_color' => '#FFF8DC'
                ],
                'invoiced_this_month' => [
                    'label' => 'MTD',
                    'title' => 'Invoiced This Month',
                    'amount' => (float) $invoicedThisMonth,
                    'amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($invoicedThisMonth),
                    'month' => $today->format('M Y'),
                    'period' => $startOfMonth->format('d-M') . ' to ' . $endOfMonth->format('d-M-Y'),
                    'icon_color' => '#20B2AA',
                    'background_color' => '#F0FFFF'
                ],
                'invoiced_this_year' => [
                    'label' => 'YTD',
                    'title' => 'Invoiced This Year',
                    'amount' => (float) $invoicedThisYear,
                    'amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($invoicedThisYear),
                    'financial_year' => $financialYear,
                    'period' => $fyStart . ' to ' . $fyEnd,
                    'icon_color' => '#32CD32',
                    'background_color' => '#F0FFF0'
                ],
                'credit_notes_this_month' => [
                    'label' => 'MTD',
                    'title' => 'Credit Notes this month',
                    'count' => (int) $creditNotesThisMonthCount,
                    'amount' => (float) $creditNotesThisMonthAmount,
                    'amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($creditNotesThisMonthAmount),
                    'display_text' => $creditNotesThisMonthCount . ' / ' . $defaultCurrencySymbol . moneyFormatIndia($creditNotesThisMonthAmount),
                    'month' => $today->format('M Y'),
                    'period' => $startOfMonth->format('d-M') . ' to ' . $endOfMonth->format('d-M-Y'),
                    'icon_color' => '#FF6347',
                    'background_color' => '#FFF5EE'
                ],
                'credit_notes_this_year' => [
                    'label' => 'YTD',
                    'title' => 'Credit Notes this year',
                    'count' => (int) $creditNotesThisYearCount,
                    'amount' => (float) $creditNotesThisYearAmount,
                    'amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($creditNotesThisYearAmount),
                    'display_text' => $creditNotesThisYearCount . ' / ' . $defaultCurrencySymbol . moneyFormatIndia($creditNotesThisYearAmount),
                    'financial_year' => $financialYear,
                    'period' => $fyStart . ' to ' . $fyEnd,
                    'icon_color' => '#DC143C',
                    'background_color' => '#FFF0F5'
                ]
            ];
            
            // Calculate additional metrics
            $summary = [
                'total_invoiced_amount' => $invoicedThisYear,
                'total_invoiced_amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($invoicedThisYear),
                'total_credit_notes_amount' => $creditNotesThisYearAmount,
                'total_credit_notes_amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($creditNotesThisYearAmount),
                'net_invoiced_amount' => $invoicedThisYear - $creditNotesThisYearAmount,
                'net_invoiced_amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($invoicedThisYear - $creditNotesThisYearAmount),
                'month_vs_year_percentage' => $invoicedThisYear > 0 ? round(($invoicedThisMonth / $invoicedThisYear) * 100, 2) : 0,
                'today_vs_month_percentage' => $invoicedThisMonth > 0 ? round(($invoicedToday / $invoicedThisMonth) * 100, 2) : 0,
                'credit_note_percentage_of_total' => $invoicedThisYear > 0 ? round(($creditNotesThisYearAmount / $invoicedThisYear) * 100, 2) : 0,
                'currency_code' => $defaultCurrencyCode,
                'currency_symbol' => $defaultCurrencySymbol,
                'financial_year' => $financialYear
            ];
            
            return response()->json([
                'status' => 'success',
                'message' => 'Invoicing dashboard data retrieved successfully',
                'data' => [
                    'invoicing' => $invoicingData,
                    'summary' => $summary,
                    'filters_applied' => [
                        'financial_year' => $financialYear,
                        'acc_manager' => $accManager ?: 'All',
                        'currency_used' => $defaultCurrencyCode
                    ],
                    'date_info' => [
                        'today' => $today->format('Y-m-d'),
                        'month_start' => $startOfMonth->format('Y-m-d'),
                        'month_end' => $endOfMonth->format('Y-m-d'),
                        'fy_start' => $fyStart,
                        'fy_end' => $fyEnd
                    ]
                ],
                'generated_at' => now()->toISOString()
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error retrieving invoicing dashboard data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    /**
     * Get detailed invoicing breakdown by period (daily, weekly, monthly)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvoicingBreakdown(Request $request)
    {
        try {
            $period = $request->input('period', 'monthly'); // daily, weekly, monthly
            $financialYear = $request->input('financial_year', '');
            $accManager = $request->input('acc_manager', '');
            $limit = min(50, max(1, (int) $request->input('limit', 12))); // Max 50 records
            
            // Calculate financial year dates
            $today = Carbon::now();
            if (!empty($financialYear) && preg_match('/^(\d{4})-(\d{4})$/', $financialYear)) {
                [$startYear, $endYear] = explode('-', $financialYear);
                $fyStart = $startYear . '-04-01';
                $fyEnd = $endYear . '-03-31';
            } else {
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
            
            // Get default currency
            $defaultCurrency = DB::table('tbl_currencies')
                ->where('currency_super_default', 'yes')
                ->where('deleteflag', 'active')
                ->first();
            
            $defaultCurrencySymbol = $defaultCurrency ? html_entity_decode($defaultCurrency->currency_html_code) : '₹';
            
            // Build admin filter for raw SQL (to match legacy helper style)
            $adminFilter = '';
            if (!empty($accManager)) {
                $adminFilter = " and prepared_by = '$accManager'";
            }
            
            // Generate breakdown based on period
            $breakdown = [];
            
            if ($period === 'daily') {
                // Get last N days within financial year
                $currentDate = Carbon::now();
                $fyEndDate = Carbon::createFromFormat('Y-m-d', $fyEnd);
                $fyStartDate = Carbon::createFromFormat('Y-m-d', $fyStart);
                
                // Start from the earlier of today or FY end date
                $startDate = $currentDate->lt($fyEndDate) ? $currentDate : $fyEndDate;
                
                for ($i = 0; $i < $limit; $i++) {
                    $date = $startDate->copy()->subDays($i)->format('Y-m-d');
                    if ($date >= $fyStart && $date <= $fyEnd) {
                        $amount = invoice_total_by_date($accManager ?: '', $date, $date);
                        $breakdown[] = [
                            'date' => $date,
                            'date_formatted' => date_format_india($date),
                            'invoice_count' => $amount > 0 ? 1 : 0, // Simplified for now
                            'total_amount' => (float) $amount,
                            'total_amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($amount)
                        ];
                    }
                }
            } 
            elseif ($period === 'weekly') {
                // Get last N weeks within financial year
                $currentDate = Carbon::now();
                $fyEndDate = Carbon::createFromFormat('Y-m-d', $fyEnd);
                $fyStartDate = Carbon::createFromFormat('Y-m-d', $fyStart);
                
                for ($i = 0; $i < $limit; $i++) {
                    $weekStart = $currentDate->copy()->subWeeks($i)->startOfWeek()->format('Y-m-d');
                    $weekEnd = $currentDate->copy()->subWeeks($i)->endOfWeek()->format('Y-m-d');
                    
                    // Ensure week is within financial year
                    if ($weekEnd >= $fyStart && $weekStart <= $fyEnd) {
                        // Adjust dates to be within financial year bounds
                        $weekStart = max($weekStart, $fyStart);
                        $weekEnd = min($weekEnd, $fyEnd);
                        
                        $amount = invoice_total_by_date($accManager ?: '', $weekStart, $weekEnd);
                        $breakdown[] = [
                            'week_start' => $weekStart,
                            'week_end' => $weekEnd,
                            'period_display' => date_format_india($weekStart) . ' to ' . date_format_india($weekEnd),
                            'invoice_count' => $amount > 0 ? 1 : 0, // Simplified for now
                            'total_amount' => (float) $amount,
                            'total_amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($amount)
                        ];
                    }
                }
            } 
            else {
                // Monthly breakdown (default) - Show all 12 months of financial year
                $fyStartDate = Carbon::createFromFormat('Y-m-d', $fyStart);
                $fyEndDate = Carbon::createFromFormat('Y-m-d', $fyEnd);
                
                for ($i = 0; $i < 12; $i++) {
                    $monthDate = $fyStartDate->copy()->addMonths($i);
                    $monthStart = $monthDate->startOfMonth()->format('Y-m-d');
                    $monthEnd = $monthDate->endOfMonth()->format('Y-m-d');
                    
                    // Don't go beyond the financial year end date
                    if ($monthStart > $fyEnd) {
                        break;
                    }
                    
                    $amount = invoice_total_by_date($accManager ?: '', $monthStart, $monthEnd);
                    $monthName = $monthDate->format('M Y');
                    
                    $breakdown[] = [
                        'year' => $monthDate->year,
                        'month' => $monthDate->month,
                        'month_name' => $monthName,
                        'period_display' => $monthName,
                        'invoice_count' => $amount > 0 ? 1 : 0, // Simplified for now
                        'total_amount' => (float) $amount,
                        'total_amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($amount)
                    ];
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Invoicing breakdown retrieved successfully',
                'data' => [
                    'breakdown' => $breakdown,
                    'period' => $period,
                    'total_records' => count($breakdown),
                    'filters_applied' => [
                        'period' => $period,
                        'financial_year' => $financialYear,
                        'acc_manager' => $accManager ?: 'All',
                        'limit' => $limit
                    ]
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error retrieving invoicing breakdown: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get receivable follow-ups data
     * Based on receivable-follow-up.php - shows invoices with follow-up dates, payment status, aging
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReceivableFollowUps(Request $request)
    {
        try {
            // Get request parameters
            $accManager = $request->input('acc_manager', '');
            $dateFrom = $request->input('date_from', '');
            $dateTo = $request->input('date_to', '');
            // Ensure timePeriod and today are available for shortcut ranges
            $timePeriod = $request->input('time_period', '');
            $today = Carbon::now();
            // Read time_period and current date so shortcuts are applied
            $timePeriod = $request->input('time_period', '');
            $today = Carbon::now();
            // Read time_period (today, this_week, this_month) and current date
            $timePeriod = $request->input('time_period', '');
            $today = Carbon::now();
            $timePeriod = $request->input('time_period', '');
            $today = Carbon::now();
            $timePeriod = $request->input('time_period', ''); // today, this_week, this_month

            // If time_period is provided and explicit dates are not, compute dateFrom/dateTo
            if (empty($dateFrom) && empty($dateTo) && !empty($timePeriod)) {
                $now = Carbon::now();
                if ($timePeriod === 'today') {
                    $dateFrom = $now->format('Y-m-d');
                    $dateTo = $now->format('Y-m-d');
                } elseif ($timePeriod === 'this_week') {
                    $dateFrom = $now->startOfWeek()->format('Y-m-d');
                    $dateTo = $now->endOfWeek()->format('Y-m-d');
                } elseif ($timePeriod === 'this_month') {
                    $dateFrom = $now->startOfMonth()->format('Y-m-d');
                    $dateTo = $now->endOfMonth()->format('Y-m-d');
                }
            }
            $invoiceNumber = $request->input('invoice_number', '');
            $orderStatus = $request->input('order_status', '0'); // 0=All, 1=Confirmed, 2=Pending, 3=Closed
            $timePeriod = $request->input('time_period', 'today'); // today, this_week, this_month, custom
            $financialYear = $request->input('financial_year', ''); // For invoice_generated_date filtering
            $followUpFinancialYear = $request->input('followup_financial_year', ''); // For follow_up_date filtering
            $page = max(1, (int) $request->input('page', 1));
            $perPage = min(100, max(10, (int) $request->input('per_page', 20)));
            $offset = ($page - 1) * $perPage;

            // Check for count=1 parameter
            $countOnly = $request->input('count', null);

            // Calculate current financial year if not provided
            // Always use current FY if financial_year is blank or missing
            if (empty($financialYear)) {
                $financialYear = '2025-2026';
                $fyStart = '2025-04-01';
                $fyEnd = '2026-03-31';
            } else if (preg_match('/^(\d{4})-(\d{4})$/', $financialYear)) {
                [$startYear, $endYear] = explode('-', $financialYear);
                $fyStart = $startYear . '-04-01';
                $fyEnd = $endYear . '-03-31';
            } else {
                // Fallback to current FY
                $financialYear = '2025-2026';
                $fyStart = '2025-04-01';
                $fyEnd = '2026-03-31';
            }

            // Now build the main query - based on receivable-follow-up.php SQL
            $query = DB::table('tbl_payment_remarks as tpr')
                ->join('tbl_tax_invoice as tti', 'tti.invoice_id', '=', 'tpr.invoice_id')
                ->leftJoin('tbl_payment_received as tppr', 'tti.invoice_id', '=', 'tppr.invoice_id')
                ->leftJoin('tbl_delivery_order as tdo', 'tti.o_id', '=', 'tdo.O_Id')
                ->leftJoin('tbl_order as o', 'tdo.O_Id', '=', 'o.orders_id')
                ->leftJoin('tbl_supply_order_payment_terms_master as s', 'tdo.payment_terms', '=', 's.supply_order_payment_terms_id')
                ->leftJoin('tbl_invoice_disputes as tid', function($join) {
                    $join->on('tid.invoice_id', '=', 'tti.invoice_id')
                         ->where('tid.dispute_status', 'active')
                         ->where('tid.deleteflag', 'active');
                })
                ->select([
                    'tti.invoice_id',
                    // ...existing code...
                ])
                ->where('tti.invoice_id', '>', 230000)
                ->where('tti.invoice_status', 'approved')
                ->where('tti.deleteflag', 'active')
                ->whereBetween('tpr.payment_remarks_follow_up_date', [$fyStart, $fyEnd]); // Filter financial year by follow-up dates

            // ...existing filter logic...

            // Group and order
            $query->groupBy('tti.invoice_id')
                  ->orderBy('tpr.payment_remarks_follow_up_date', 'desc');

            // Get total count for pagination
            $totalQuery = clone $query;
            $totalRecords = $totalQuery->get()->count();

            // If count=1, return only totalItems
            if ($countOnly == 1 || $countOnly === '1') {
                return response()->json([
                    'totalItems' => $totalRecords
                ]);
            }
            $offset = ($page - 1) * $perPage;
            
            // Calculate current financial year if not provided
            $today = Carbon::now();
            if (empty($financialYear)) {
                if ($today->month >= 4) {
                    $fyStartYear = $today->year;
                    $fyEndYear = $today->year + 1;
                } else {
                    $fyStartYear = $today->year - 1;
                    $fyEndYear = $today->year;
                }
                $financialYear = $fyStartYear . '-' . $fyEndYear;
            }
            
            // Parse invoice financial year
            if (preg_match('/^(\d{4})-(\d{4})$/', $financialYear)) {
                [$startYear, $endYear] = explode('-', $financialYear);
                $fyStart = $startYear . '-04-01';
                $fyEnd = $endYear . '-03-31';
            } else {
                // Fallback to current FY
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
            
            // Parse follow-up financial year (if provided - optional separate filter)
            $followUpFyStart = null;
            $followUpFyEnd = null;
            if (!empty($followUpFinancialYear) && preg_match('/^(\d{4})-(\d{4})$/', $followUpFinancialYear)) {
                [$followUpStartYear, $followUpEndYear] = explode('-', $followUpFinancialYear);
                $followUpFyStart = $followUpStartYear . '-04-01';
                $followUpFyEnd = $followUpEndYear . '-03-31';
            }
            
            // Handle time period filtering - DEFAULT to today if no custom dates provided
            if (empty($dateFrom) && empty($dateTo) && $timePeriod !== 'custom') {
                switch ($timePeriod) {
                    case 'today':
                        $dateFrom = $today->format('Y-m-d');
                        $dateTo = $today->format('Y-m-d');
                        break;
                        
                    case 'this_week':
                        $dateFrom = $today->copy()->startOfWeek()->format('Y-m-d');
                        $dateTo = $today->copy()->endOfWeek()->format('Y-m-d');
                        break;
                        
                    case 'this_month':
                        $dateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
                        $dateTo = $today->copy()->endOfMonth()->format('Y-m-d');
                        break;
                        
                    default:
                        // Default to today for any invalid time_period
                        $dateFrom = $today->format('Y-m-d');
                        $dateTo = $today->format('Y-m-d');
                        $timePeriod = 'today';
                        break;
                }
            } elseif (!empty($dateFrom) || !empty($dateTo)) {
                // Custom date range provided
                $timePeriod = 'custom';
            } else {
                // No parameters provided - DEFAULT to today's follow-ups
                $dateFrom = $today->format('Y-m-d');
                $dateTo = $today->format('Y-m-d');
                $timePeriod = 'today';
            }
            
            // Get default currency
            $defaultCurrency = DB::table('tbl_currencies')
                ->where('currency_super_default', 'yes')
                ->where('deleteflag', 'active')
                ->first();
            
            $defaultCurrencySymbol = $defaultCurrency ? html_entity_decode($defaultCurrency->currency_html_code) : '₹';
            
            // Build the main query - based on receivable-follow-up.php SQL
            $query = DB::table('tbl_payment_remarks as tpr')
                ->join('tbl_tax_invoice as tti', 'tti.invoice_id', '=', 'tpr.invoice_id')
                ->leftJoin('tbl_payment_received as tppr', 'tti.invoice_id', '=', 'tppr.invoice_id')
                ->leftJoin('tbl_delivery_order as tdo', 'tti.o_id', '=', 'tdo.O_Id')
                ->leftJoin('tbl_order as o', 'tdo.O_Id', '=', 'o.orders_id')
                ->leftJoin('tbl_supply_order_payment_terms_master as s', 'tdo.payment_terms', '=', 's.supply_order_payment_terms_id')
                ->leftJoin('tbl_invoice_disputes as tid', function($join) {
                    $join->on('tid.invoice_id', '=', 'tti.invoice_id')
                         ->where('tid.dispute_status', 'active')
                         ->where('tid.deleteflag', 'active');
                })
                ->select([
                    'tti.invoice_id',
                    'tti.invoice_generated_date',
                    'tti.cus_com_name',
                    'tti.invoice_status',
                    'tti.freight_amount',
                    'tti.freight_gst_amount', 
                    'tti.sub_total_amount_without_gst',
                    'tti.total_gst_amount',
                    'tti.gst_sale_type',
                    'tti.payment_terms',
                    'tti.prepared_by',
                    'tti.buyer_gst',
                    'tti.o_id',
                    'tpr.payment_remarks_follow_up_date',
                    'tpr.updated_by as remarks_updated_by',
                    'tpr.payment_remarks_only',
                    'tpr.payment_remarks_id',
                    'o.orders_status',
                    's.supply_order_payment_terms_abbrv',
                    's.supply_order_payment_terms_name',
                    // Dispute information
                    'tid.dispute_id',
                    'tid.dispute_type',
                    'tid.disputed_amount',
                    'tid.dispute_reason',
                    'tid.dispute_status',
                    'tid.disputed_date',
                    DB::raw('SUM(COALESCE(tti.freight_amount, 0) + COALESCE(tti.sub_total_amount_without_gst, 0) + COALESCE(tti.total_gst_amount, 0)) - COALESCE(SUM(tppr.payment_received_value), 0) as balance_due'),
                    DB::raw('DATE_ADD(tti.invoice_generated_date, INTERVAL COALESCE(s.supply_order_payment_terms_abbrv, 0) DAY) AS invoice_date_with_payment_terms'),
                    DB::raw('DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL COALESCE(s.supply_order_payment_terms_abbrv, 0) DAY)) AS aging'),
                    // Dispute status indicators
                    DB::raw('CASE WHEN tid.dispute_id IS NOT NULL THEN 1 ELSE 0 END as is_disputed'),
                    DB::raw('CASE WHEN tid.dispute_type = "full_invoice" THEN 1 ELSE 0 END as is_full_invoice_disputed'),
                    DB::raw('CASE WHEN tid.dispute_type = "partial_payment" THEN 1 ELSE 0 END as is_partial_payment_disputed')
                ])
                ->where('tti.invoice_id', '>', 230000)
                ->where('tti.invoice_status', 'approved')
                ->where('tti.deleteflag', 'active')
                ->whereBetween('tpr.payment_remarks_follow_up_date', [$fyStart, $fyEnd]); // Filter financial year by follow-up dates
            
            // Apply filters
            if (!empty($accManager)) {
                $query->where('tti.prepared_by', $accManager);
            }
            
            // Apply follow-up date filters
            if (!empty($dateFrom) && !empty($dateTo)) {
                if ($dateFrom === $dateTo) {
                    // Single date
                    $query->whereDate('tpr.payment_remarks_follow_up_date', $dateFrom);
                } else {
                    // Date range
                    $query->whereBetween('tpr.payment_remarks_follow_up_date', [$dateFrom, $dateTo]);
                }
            } elseif (!empty($dateFrom)) {
                $query->whereDate('tpr.payment_remarks_follow_up_date', '>=', $dateFrom);
            } elseif (!empty($dateTo)) {
                $query->whereDate('tpr.payment_remarks_follow_up_date', '<=', $dateTo);
            }
            
            // Apply follow-up financial year filter (separate from invoice FY)
            if (!empty($followUpFyStart) && !empty($followUpFyEnd)) {
                $query->whereBetween('tpr.payment_remarks_follow_up_date', [$followUpFyStart, $followUpFyEnd]);
            }
            
            if (!empty($invoiceNumber)) {
                $query->where('tti.invoice_id', 'like', '%' . $invoiceNumber . '%');
            }
            
            if ($orderStatus !== '0') {
                $statusMap = ['1' => 'confirmed', '2' => 'pending', '3' => 'closed'];
                if (isset($statusMap[$orderStatus])) {
                    $query->where('o.orders_status', $statusMap[$orderStatus]);
                }
            }
            
            // Group and order
            $query->groupBy('tti.invoice_id')
                  ->orderBy('tpr.payment_remarks_follow_up_date', 'desc');
            
            // Get total count for pagination
            $totalQuery = clone $query;
            $totalRecords = $totalQuery->get()->count();
            
            // Apply pagination
            $followUps = $query->offset($offset)->limit($perPage)->get();
            
            // Process the results
            $processedFollowUps = [];
            foreach ($followUps as $followUp) {
                $totalInvoiceAmount = $followUp->sub_total_amount_without_gst + $followUp->total_gst_amount + $followUp->freight_amount;
                $aging = $followUp->aging;
                $agingDisplay = '';
                
                if ($aging > 0) {
                    $agingDisplay = "Overdue: " . abs($aging) . " days";
                } else {
                    $agingDisplay = "Due in " . abs($aging) . " days";
                }
                
                $processedFollowUps[] = [
                    'invoice_id' => $followUp->invoice_id,
                    'company_name' => $followUp->cus_com_name,
                    'invoice_date' => $followUp->invoice_generated_date,
                    'invoice_date_formatted' => date_format_india($followUp->invoice_generated_date),
                    'follow_up_date' => $followUp->payment_remarks_follow_up_date,
                    'follow_up_date_formatted' => date_format_india($followUp->payment_remarks_follow_up_date ?: ''),
                    'balance_due' => (float) $followUp->balance_due,
                    'balance_due_formatted' => $defaultCurrencySymbol . moneyFormatIndia($followUp->balance_due),
                    'total_invoice_amount' => (float) $totalInvoiceAmount,
                    'total_invoice_amount_formatted' => $defaultCurrencySymbol . moneyFormatIndia($totalInvoiceAmount),
                    'aging_days' => (int) $aging,
                    'aging_display' => $agingDisplay,
                    'payment_terms' => $followUp->supply_order_payment_terms_name ?: 'N/A',
                    'payment_terms_days' => (int) ($followUp->supply_order_payment_terms_abbrv ?: 0),
                    'account_manager' => admin_name($followUp->prepared_by),
                    'account_manager_id' => $followUp->prepared_by,
                    'invoice_status' => ucfirst($followUp->invoice_status),
                    'order_status' => ucfirst($followUp->orders_status ?: 'N/A'),
                    'payment_remarks' => $followUp->payment_remarks_only ?: '',
                    'remarks_updated_by' => admin_name($followUp->remarks_updated_by),
                    'buyer_gst' => $followUp->buyer_gst ?: '',
                    'is_overdue' => $aging > 0,
                    'overdue_category' => $this->getOverdueCategory($aging),
                    // Dispute Information - NEW
                    'dispute_info' => [
                        'is_disputed' => (bool) $followUp->is_disputed,
                        'dispute_id' => $followUp->dispute_id,
                        'dispute_type' => $followUp->dispute_type,
                        'dispute_type_label' => $followUp->dispute_type ? ($followUp->dispute_type === 'full_invoice' ? 'Full Invoice Disputed' : 'Partial Payment Disputed') : null,
                        'disputed_amount' => $followUp->disputed_amount ? (float) $followUp->disputed_amount : null,
                        'disputed_amount_formatted' => $followUp->disputed_amount ? $defaultCurrencySymbol . moneyFormatIndia($followUp->disputed_amount) : null,
                        'dispute_reason' => $followUp->dispute_reason,
                        'dispute_status' => $followUp->dispute_status,
                        'disputed_date' => $followUp->disputed_date,
                        'disputed_date_formatted' => $followUp->disputed_date ? date_format_india($followUp->disputed_date) : null,
                        'is_full_invoice_disputed' => (bool) $followUp->is_full_invoice_disputed,
                        'is_partial_payment_disputed' => (bool) $followUp->is_partial_payment_disputed
                    ]
                ];
            }
            
            // Calculate pagination info
            $totalPages = ceil($totalRecords / $perPage);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Receivable follow-ups retrieved successfully',
                'data' => [
                    'follow_ups' => $processedFollowUps,
                    'pagination' => [
                        'page' => $page,
                        'pageSize' => $perPage,
                        'totalItems' => $totalRecords,
                        'totalPages' => $totalPages
                    ],
                    'sorting' => [
                        'sortkey' => 'payment_remarks_follow_up_date',
                        'sortvalue' => 'desc'
                    ],
                    'filters_applied' => [
                        'acc_manager' => $accManager ?: 'All',
                        'financial_year' => $financialYear,
                        'followup_financial_year' => $followUpFinancialYear ?: 'Not specified',
                        'time_period' => $timePeriod,
                        'date_from' => $dateFrom ?: 'Auto',
                        'date_to' => $dateTo ?: 'Auto',
                        'invoice_number' => $invoiceNumber ?: 'All',
                        'order_status' => $orderStatus
                    ],
                    'filter_options' => [
                        'time_periods' => [
                            ['value' => 'today', 'label' => 'Today'],
                            ['value' => 'this_week', 'label' => 'This week'],
                            ['value' => 'this_month', 'label' => 'This month'],
                            ['value' => 'custom', 'label' => 'Custom date range']
                        ],
                        'order_statuses' => [
                            ['value' => '0', 'label' => 'All'],
                            ['value' => '1', 'label' => 'Confirmed'],
                            ['value' => '2', 'label' => 'Pending'],
                            ['value' => '3', 'label' => 'Closed']
                        ]
                    ],
                    'date_info' => [
                        'current_fy' => $financialYear,
                        'fy_start' => $fyStart,
                        'fy_end' => $fyEnd,
                        'followup_fy' => $followUpFinancialYear ?: 'Not specified',
                        'followup_fy_start' => $followUpFyStart,
                        'followup_fy_end' => $followUpFyEnd,
                        'filter_date_from' => $dateFrom,
                        'filter_date_to' => $dateTo,
                        'today' => $today->format('Y-m-d')
                    ],
                    'summary' => [
                        'total_follow_ups' => $totalRecords,
                        'overdue_count' => collect($processedFollowUps)->where('is_overdue', true)->count(),
                        'pending_count' => collect($processedFollowUps)->where('is_overdue', false)->count()
                    ]
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error retrieving receivable follow-ups: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get Promised Payments list (based on tbl_payment_remarks.payment_promised_on)
     * Matches legacy UI: company_name, invoice, invoice_date, invoice_amount, amount_received, amount_due,
     * overdue_for, last_follow_up_date, promised_date_of_payment, account_manager, payment_remarks
     */
    public function getPromisedPayments(Request $request)
    {
        try {
            // Always initialize timePeriod and today FIRST
            $timePeriod = $request->input('time_period', '');
            $today = Carbon::now();
            $accManager = $request->input('acc_manager', '');
            $dateFrom = $request->input('date_from', '');
            $dateTo = $request->input('date_to', '');
            $page = max(1, (int) $request->input('page', 1));
            $perPage = min(100, max(10, (int) $request->input('per_page', 20)));
            $offset = ($page - 1) * $perPage;

            // Base query: payment remarks with a promised date
            // Join delivery/order/payment-terms to compute due date and aging like legacy code
            $query = DB::table('tbl_payment_remarks as tpr')
                ->join('tbl_tax_invoice as tti', 'tti.invoice_id', '=', 'tpr.invoice_id')
                ->leftJoin('tbl_payment_received as tppr', 'tti.invoice_id', '=', 'tppr.invoice_id')
                ->leftJoin('tbl_delivery_order as tdo', 'tti.o_id', '=', 'tdo.O_Id')
                ->leftJoin('tbl_order as o', 'tdo.O_Id', '=', 'o.orders_id')
                ->leftJoin('tbl_supply_order_payment_terms_master as s', 'tdo.payment_terms', '=', 's.supply_order_payment_terms_id')
                ->select([
                    'tti.invoice_id',
                    'tti.invoice_generated_date',
                    'tti.cus_com_name',
                    'tti.sub_total_amount_without_gst',
                    'tti.total_gst_amount',
                    'tti.freight_amount',
                    'tti.prepared_by',
                    'tpr.payment_promised_on',
                    'tpr.payment_remarks_follow_up_date',
                    'tpr.payment_remarks_only',
                    DB::raw('COALESCE(SUM(tppr.payment_received_value),0) as amount_received'),
                    DB::raw('DATE_ADD(tti.invoice_generated_date, INTERVAL COALESCE(s.supply_order_payment_terms_abbrv, 0) DAY) AS invoice_date_with_payment_terms'),
                    DB::raw('DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL COALESCE(s.supply_order_payment_terms_abbrv, 0) DAY)) AS aging')
                ])
                ->where('tpr.deleteflag', 'active')
                ->whereNotNull('tpr.payment_promised_on')
                ->where('tpr.payment_promised_on', '!=', '0000-00-00')
                ->where('tti.deleteflag', 'active')
                ->groupBy('tti.invoice_id')
                ->orderBy('tpr.payment_promised_on', 'desc');

            if (!empty($accManager)) {
                $query->where('tti.prepared_by', $accManager);
            }

            // Handle time period shortcuts (today, this_week, this_month) when explicit dates are not provided
            if (empty($dateFrom) && empty($dateTo) && !empty($timePeriod)) {
                switch ($timePeriod) {
                    case 'today':
                        $dateFrom = $today->format('Y-m-d');
                        $dateTo = $today->format('Y-m-d');
                        break;
                    case 'this_week':
                        // Calendar week: Sunday -> Saturday
                        // Carbon's startOfWeek / endOfWeek default to Monday; set startOfWeek to Sunday for this calculation
                        $sunday = $today->copy()->startOfWeek(Carbon::SUNDAY);
                        $saturday = $sunday->copy()->endOfWeek(Carbon::SATURDAY);
                        $dateFrom = $sunday->format('Y-m-d');
                        $dateTo = $saturday->format('Y-m-d');
                        break;
                    case 'this_month':
                        $dateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
                        $dateTo = $today->copy()->endOfMonth()->format('Y-m-d');
                        break;
                    default:
                        // ignore unknown
                        break;
                }
            }

            if (!empty($dateFrom) && !empty($dateTo)) {
                // Use DATE() on the column to avoid DATETIME edge cases when comparing to date strings
                $query->whereBetween(DB::raw('DATE(tpr.payment_promised_on)'), [$dateFrom, $dateTo]);
            } elseif (!empty($dateFrom)) {
                $query->whereDate('tpr.payment_promised_on', '>=', $dateFrom);
            } elseif (!empty($dateTo)) {
                $query->whereDate('tpr.payment_promised_on', '<=', $dateTo);
            }

            $total = $query->get()->count();

            // If caller requests only count (count=1), return only totalItems to match API contract
            $countOnly = $request->input('count', null);
            if ($countOnly == 1 || $countOnly === '1') {
                return response()->json([
                    'totalItems' => $total
                ]);
            }

            $rows = $query->offset($offset)->limit($perPage)->get();

            $results = [];
            foreach ($rows as $r) {
                $invoiceAmount = ($r->sub_total_amount_without_gst ?: 0) + ($r->total_gst_amount ?: 0) + ($r->freight_amount ?: 0);
                $amountReceived = (float) $r->amount_received;
                $amountDue = max(0, $invoiceAmount - $amountReceived);

                // Overdue calculation (days since invoice due date using payment terms if available would be ideal; keep simple)
                $overdueFor = '';
                // Use SQL-calculated aging (days overdue). Positive => overdue, negative/zero => due in X days
                $aging = isset($r->aging) ? (int) $r->aging : null;
                if ($aging === null) {
                    $overdueFor = '';
                } else if ($aging > 0) {
                    $overdueFor = 'Overdue: ' . $aging . ' days';
                } else {
                    $overdueFor = 'Due in ' . abs($aging) . ' days';
                }

                $results[] = [
                    'company_name' => $r->cus_com_name,
                    'invoice' => $r->invoice_id,
                    'invoice_date' => $r->invoice_generated_date,
                    'invoice_amount' => (float) $invoiceAmount,
                    'amount_received' => $amountReceived,
                    'amount_due' => (float) $amountDue,
                    'overdue_for' => $overdueFor,
                    'last_follow_up_date' => $r->payment_remarks_follow_up_date,
                    'promised_date_of_payment' => $r->payment_promised_on,
                    'account_manager' => admin_name($r->prepared_by),
                    'payment_remarks' => $r->payment_remarks_only ?: ''
                ];
            }

            $pagination = [
                'page' => $page,
                'pageSize' => $perPage,
                'totalItems' => $total,
                'totalPages' => (int) ceil($total / $perPage)
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Promised payments retrieved',
                'data' => [
                    'promised_payments' => $results,
                    'pagination' => $pagination,
                    'debug_filters' => [
                        'time_period' => $timePeriod ?? null,
                        'time_period_from_request' => $request->input('time_period', null),
                        'date_from' => $dateFrom ?? null,
                        'date_to' => $dateTo ?? null
                    ],
                    'debug_request' => [
                        'query' => $request->all(),
                        'full_url' => $request->fullUrl()
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error retrieving promised payments: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get detailed follow-up information for a specific invoice
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFollowUpDetails(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            
            if (empty($invoiceId)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice ID is required',
                    'data' => null
                ], 400);
            }
            
            // Get payment received details
            $paymentsReceived = DB::table('tbl_payment_received')
                ->where('invoice_id', $invoiceId)
                ->where('deleteflag', 'active')
                ->orderBy('payment_received_date', 'desc')
                ->get();
            
            // Get payment remarks
            $paymentRemarks = DB::table('tbl_payment_remarks')
                ->where('invoice_id', $invoiceId)
                ->orderBy('inserted_date', 'desc')
                ->get();
            
            // Process payment received data
            $processedPayments = [];
            foreach ($paymentsReceived as $payment) {
                $processedPayments[] = [
                    'payment_id' => $payment->payment_received_id,
                    'amount' => (float) $payment->payment_received_value,
                    'amount_formatted' => '₹' . moneyFormatIndia($payment->payment_received_value),
                    'tds_amount' => (float) ($payment->payment_received_value_tds ?: 0),
                    'tds_amount_formatted' => '₹' . moneyFormatIndia($payment->payment_received_value_tds ?: 0),
                    'credit_note_value' => (float) ($payment->credit_note_value ?: 0),
                    'credit_note_formatted' => '₹' . moneyFormatIndia($payment->credit_note_value ?: 0),
                    'other_adjustments' => (float) ($payment->lda_other_value ?: 0),
                    'other_adjustments_formatted' => '₹' . moneyFormatIndia($payment->lda_other_value ?: 0),
                    'payment_date' => $payment->payment_received_date,
                    'payment_date_formatted' => date_format_india($payment->payment_received_date),
                    'payment_method' => get_payment_type_name($payment->payment_received_via),
                    'transaction_id' => $payment->transaction_id ?: '',
                    'bank_name' => get_comp_bank_name($payment->payment_received_in_bank),
                    'remarks' => $payment->payment_remarks ?: '',
                    'inserted_date' => $payment->inserted_date,
                    'inserted_date_formatted' => date_format_india($payment->inserted_date),
                    'updated_by' => admin_name($payment->updated_by)
                ];
            }
            
            // Process remarks data
            $processedRemarks = [];
            foreach ($paymentRemarks as $remark) {
                $processedRemarks[] = [
                    'remark_id' => $remark->payment_remarks_id,
                    'remark_text' => $remark->payment_remarks_only ?: '',
                    'follow_up_date' => $remark->payment_remarks_follow_up_date,
                    'follow_up_date_formatted' => date_format_india($remark->payment_remarks_follow_up_date ?: ''),
                    'updated_by' => admin_name($remark->updated_by),
                    'inserted_date' => $remark->inserted_date,
                    'inserted_date_formatted' => date_format_india($remark->inserted_date)
                ];
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Follow-up details retrieved successfully',
                'data' => [
                    'invoice_id' => $invoiceId,
                    'payments_received' => $processedPayments,
                    'payment_remarks' => $processedRemarks,
                    'payments_count' => count($processedPayments),
                    'remarks_count' => count($processedRemarks)
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error retrieving follow-up details: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Helper function to categorize overdue invoices
     * 
     * @param int $agingDays
     * @return string
     */
    private function getOverdueCategory($agingDays)
    {
        if ($agingDays <= 0) {
            return 'not_due';
        } elseif ($agingDays <= 30) {
            return '1-30_days';
        } elseif ($agingDays <= 60) {
            return '31-60_days';
        } elseif ($agingDays <= 90) {
            return '61-90_days';
        } else {
            return '90plus_days';
        }
    }
}
