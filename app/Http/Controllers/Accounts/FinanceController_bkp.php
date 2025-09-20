<?php

namespace App\Http\Controllers\Accounts;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\{
    TaxInvoice,
    PaymentReceived
};

class FinanceController extends Controller
{  
    function payment_received_by_aging($acc_manager,$aging_min,$aging_max,$company_name)
    {
    
        // base where condition
        $where = "
            tti.invoice_id > 230000
            AND tti.invoice_status = 'approved'
            AND tti.invoice_closed_status = 'No'
            AND tti.payment_terms = s.supply_order_payment_terms_id
        ";

        // aging condition
        if ($aging_max !== '' && $aging_max != '0') {
            $where .= " AND DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) 
                        BETWEEN $aging_min AND $aging_max ";
        }

        // account manager condition
        if ($acc_manager !== '' && $acc_manager != '0' && $acc_manager != 'All') {
            $where .= " AND tti.prepared_by = '$acc_manager' ";
        }

        // company name condition
        if ($company_name !== '') {
            $where .= " AND tti.cus_com_name LIKE '%$company_name%' ";
        }

        // SQL query
        $sql = "SELECT 
                    SUM(tpr.payment_received_value) AS total_payment_received,
                    tti.exchange_rate,
                    DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) AS invoice_date_with_payment_terms,
                    DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging
                FROM tbl_tax_invoice tti
                INNER JOIN tbl_supply_order_payment_terms_master s
                LEFT JOIN tbl_payment_received tpr ON tti.invoice_id = tpr.invoice_id
                WHERE tpr.invoice_id = tti.invoice_id
                AND $where
                ORDER BY aging DESC";

        // Run query
        $result = \DB::select($sql);

        // if rows exist
        $total_payment_received = 0;
        $extra = [];

        if (!empty($result)) {
            $row = $result[0];
            $total_payment_received = $row->total_payment_received ?? 0;
            $extra = [
                'exchange_rate' => $row->exchange_rate ?? null,
                'invoice_date_with_payment_terms' => $row->invoice_date_with_payment_terms ?? null,
                'aging' => $row->aging ?? null,
            ];
        }

        return $total_payment_received;
    }

    function pending_account_receivables($acc_manager,$qtr_start_date_show,$qtr_end_date_show,$enq_source_search,$company_name, $aging_min, $aging_max)
    {
   
        // base where clause
        $where = "
            tti.invoice_id > 230000
            AND tti.invoice_closed_status = 'No'
            AND tti.invoice_status = 'approved'
            AND ttcni.invoice_id IS NULL
        ";

        // account manager filter
        if ($acc_manager !== '' && $acc_manager != '0' && $acc_manager != 'All') {
            $where .= " AND tti.prepared_by IN ($acc_manager) ";
        }

        // company filter
        if ($company_name !== '') {
            $where .= " AND tti.cus_com_name LIKE '%$company_name%' ";
        }

        // enquiry source filter
        if ($enq_source_search !== '' && $enq_source_search != '0') {
            $where .= " AND ref_source = '$enq_source_search' ";
        }

        // aging filter
        if ($aging_max !== '' && $aging_max != '0') {
            $where .= " AND DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, 
                            INTERVAL s.supply_order_payment_terms_abbrv DAY)) 
                            BETWEEN $aging_min AND $aging_max ";
        }

        // SQL Query
        $sql = "SELECT 
                    tti.exchange_rate,
                    DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) AS invoice_date_with_payment_terms,
                    DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging,
                    SUM((tti.freight_amount + tti.sub_total_amount_without_gst + tti.total_gst_amount) * tti.exchange_rate) AS total_value_receivables
                FROM tbl_tax_invoice tti
                INNER JOIN tbl_supply_order_payment_terms_master s ON tti.payment_terms = s.supply_order_payment_terms_id
                LEFT JOIN tbl_tax_credit_note_invoice ttcni ON ttcni.invoice_id = tti.invoice_id
                WHERE $where
                ORDER BY aging DESC";

        // Run query
        $result = \DB::select($sql);

        $total_value_receivables = 0;
        $extra = [];

        if (!empty($result)) {
            $row = $result[0];
            $total_value_receivables = $row->total_value_receivables ?? 0;

            $extra = [
                'exchange_rate' => $row->exchange_rate ?? null,
                'invoice_date_with_payment_terms' => $row->invoice_date_with_payment_terms ?? null,
                'aging' => $row->aging ?? null,
            ];
        }
        return  $total_value_receivables ;
    }

    public function pending_account_receivables_by_aging(Request $request)
    {
        $acc_manager   = $request->query('acc_manager', 0);
        $aging_min     = $request->query('aging_min', 0);
        $aging_max     = $request->query('aging_max', '');
        $company_name  = $request->query('company_name', '');

        // Base where clause
        $where = "
            tti.invoice_id > 230000
            AND tti.invoice_status = 'approved'
            AND tti.invoice_closed_status = 'No'
            AND tti.payment_terms = s.supply_order_payment_terms_id
        ";

        // Aging condition
        if ($aging_max !== '' && $aging_max != '0') {
            $where .= " AND DATEDIFF(
                            CURDATE(),
                            DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)
                        ) BETWEEN $aging_min AND $aging_max ";
        }

        // Company filter
        if ($company_name !== '') {
            $where .= " AND tti.cus_com_name LIKE '%$company_name%' ";
        }

        // Account manager filter
        if ($acc_manager !== '' && $acc_manager != '0') {
            $where .= " AND tti.prepared_by = '$acc_manager' ";
        }

        // SQL Query
        $sql = "SELECT 
                    tti.exchange_rate,
                    DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) AS invoice_date_with_payment_terms,
                    DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging,
                    SUM((tti.freight_amount + tti.sub_total_amount_without_gst + tti.total_gst_amount) * tti.exchange_rate) AS total_value_receivables
                FROM tbl_tax_invoice tti
                INNER JOIN tbl_supply_order_payment_terms_master s ON tti.payment_terms = s.supply_order_payment_terms_id
                WHERE $where
                ORDER BY aging DESC";

        // Run query
        $result = \DB::select($sql);

        $total_value_receivables = 0;
        $extra = [];

        if (!empty($result)) {
            $row = $result[0];
            $total_value_receivables = $row->total_value_receivables ?? 0;

            $extra = [
                'exchange_rate' => $row->exchange_rate ?? null,
                'invoice_date_with_payment_terms' => $row->invoice_date_with_payment_terms ?? null,
                'aging' => $row->aging ?? null,
            ];
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Pending account receivables by aging fetched successfully.',
            'data'    => [
                'total_value_receivables' => $total_value_receivables,
                'extra' => $extra
            ]
        ], 200);
    }

    function pending_account_receivables_by_aging_not_yet_due($acc_manager = 0, $company_name = '')
    {

        // Always set aging_min = 0 (as per your PHP code)
        $aging_min = 0;

        // Base where conditions
        $where = "
            tti.invoice_id > 230000
            AND tti.invoice_status = 'approved'
            AND tti.invoice_closed_status = 'No'
            AND tti.payment_terms = s.supply_order_payment_terms_id
            AND DATEDIFF(
                CURDATE(),
                DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)
            ) <= $aging_min
        ";

        // Account manager filter
        if ($acc_manager !== '' && $acc_manager != '0') {
            $where .= " AND tti.prepared_by = '$acc_manager' ";
        }

        // Company name filter
        if ($company_name !== '') {
            $where .= " AND tti.cus_com_name LIKE '%$company_name%' ";
        }

        // SQL query
        $sql = "SELECT 
                    tti.exchange_rate,
                    DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) AS invoice_date_with_payment_terms,
                    DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging,
                    SUM(
                        (tti.freight_amount + tti.sub_total_amount_without_gst + tti.total_gst_amount) * tti.exchange_rate
                    ) AS total_value_receivables
                FROM tbl_tax_invoice tti
                INNER JOIN tbl_supply_order_payment_terms_master s
                    ON tti.payment_terms = s.supply_order_payment_terms_id
                WHERE $where
                ORDER BY aging DESC";

        // Run query
        $result = \DB::select($sql);

        $total_value_receivables = 0;
        $extra = [];

        if (!empty($result)) {
            $row = $result[0];
            $total_value_receivables = $row->total_value_receivables ?? 0;

            $extra = [
                'exchange_rate' => $row->exchange_rate ?? null,
                'invoice_date_with_payment_terms' => $row->invoice_date_with_payment_terms ?? null,
                'aging' => $row->aging ?? null,
            ];
        }

        return  $total_value_receivables;
    }
    public function receivablePieSeries(Request $request)
    {
        $acc_manager     = $request->query('acc_manager', '');
        $aging_min       = $request->query('aging_min', '');
        $aging_max       = $request->query('aging_max', '');
        $company_name    = $request->query('company_name', '');
        $invoice_number  = $request->query('invoice_number', '');
        $team_members    = $request->query('team_members', '');
        $sortKey         = $request->query('sortkey', 'amount'); // 'company_name', 'amount'
        $sortValue       = strtoupper($request->query('sortvalue', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Base where conditions
        $where = "
            tti.invoice_id > 230000
            AND tti.invoice_closed_status = 'No'
            AND tti.invoice_status = 'approved'
            AND tti.payment_terms = s.supply_order_payment_terms_id
        ";

        // Account Manager Filter
        if (!empty($acc_manager) && $acc_manager != '0' && $acc_manager != 'All') {
            $where .= " AND tti.prepared_by = '$acc_manager' ";
        }

        // Aging Filter
        if (!empty($aging_max) && $aging_max != '0') {
            $where .= " AND DATEDIFF(
                            CURDATE(),
                            DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)
                        ) BETWEEN $aging_min AND $aging_max ";
        }

        // Company Name Filter
        if (!empty($company_name)) {
            $where .= " AND tti.cus_com_name LIKE '%$company_name%' ";
        }

        // Invoice Number Filter
        if (!empty($invoice_number)) {
            $where .= " AND tti.invoice_number LIKE '%$invoice_number%' ";
        }

        // Team Members Filter
        if (!empty($team_members)) {
            $where .= " AND tti.prepared_by IN ($team_members) ";
        }

        // Sorting logic
        $sortable = [
            'company_name' => 'tti.cus_com_name',
            'amount' => 'total_payment',
        ];
        $sortColumn = $sortable[$sortKey] ?? 'total_payment';

        // SQL query
        $sql = "SELECT 
                    tti.invoice_id,
                    tti.cus_com_name,
                    tpr.invoice_id,
                    SUM(tpr.payment_received_value) AS payment_received,
                    tti.prepared_by,
                    tti.exchange_rate,
                    tti.invoice_currency,
                    SUM((tti.freight_amount + tti.sub_total_amount_without_gst + tti.total_gst_amount)) * tti.exchange_rate AS total_payment
                FROM tbl_tax_invoice tti
                INNER JOIN tbl_supply_order_payment_terms_master s
                LEFT JOIN tbl_payment_received tpr ON tti.invoice_id = tpr.invoice_id
                LEFT JOIN tbl_tax_credit_note_invoice ttcni ON ttcni.invoice_id = tti.invoice_id
                WHERE $where
                GROUP BY tti.cus_com_name  
                ORDER BY $sortColumn $sortValue";

        $result = \DB::select($sql);

        $datapie = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $datapie[] = [
                    'name'   => $row->cus_com_name,
                    'amount' =>(int) ($row->total_payment ?? 0)
                ];
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Receivable pie series fetched successfully.',
            'data'    => $datapie
        ], 200);
    }

    public function topReceivables(Request $request)
    {
        // Apply search filters (coming from request)
        $searchAccManager   = $request->input('acc_manager');
        $searchAging        = $request->input('aging');
        $companyName        = $request->input('company_name');
        $invoiceNumber      = $request->input('invoice_number');

        // Pagination parameters
        $page = (int) $request->input('pageno', 1);
        $perPage = (int) $request->input('records', 20);
        $offset = ($page - 1) * $perPage;

        // Sorting parameters (from UI)
        $sortKey = $request->input('sortkey', 'total_bal_left');
        $sortValue = strtoupper($request->input('sortvalue', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Build query
        $query = DB::table('tbl_tax_invoice as tti')
            ->selectRaw("
                tti.invoice_id,
                tti.prepared_by,
                tti.exchange_rate,
                tti.invoice_currency,
                tti.cus_com_name,
                SUM((tti.freight_amount + tti.sub_total_amount_without_gst + tti.total_gst_amount) * tti.exchange_rate) as total_payment,
                IFNULL(SUM(tpr.payment_received),0) as payment_received,
                SUM((tti.freight_amount + tti.sub_total_amount_without_gst + tti.total_gst_amount) * tti.exchange_rate) 
                    - IFNULL(SUM(tpr.payment_received),0) as total_bal_left
            ")
            ->leftJoin(
                DB::raw('(SELECT invoice_id, SUM(payment_received_value) as payment_received 
                          FROM tbl_payment_received 
                          GROUP BY invoice_id) as tpr'),
                'tti.invoice_id',
                '=',
                'tpr.invoice_id'
            )
            ->join('tbl_supply_order_payment_terms_master as s', 'tti.payment_terms', '=', 's.supply_order_payment_terms_id')
            ->leftJoin('tbl_tax_credit_note_invoice as ttcni', 'tti.invoice_id', '=', 'ttcni.invoice_id')
            ->where('tti.invoice_id', '>', 230000)
            ->where('tti.invoice_closed_status', 'No')
            ->where('tti.invoice_status', 'approved')
            ->whereNull('ttcni.invoice_id')
            ->groupBy('tti.prepared_by', 'tti.cus_com_name');

        // Optional filters
        if (!empty($searchAccManager)) {
            $query->where('tti.prepared_by', $searchAccManager);
        }
        if (!empty($companyName)) {
            $query->where('tti.cus_com_name', 'like', "%$companyName%");
        }
        if (!empty($invoiceNumber)) {
            $query->where('tti.invoice_id', $invoiceNumber);
        }

        // Sorting logic (matches UI)
        $sortable = [
            'company_name' => 'tti.cus_com_name',
            'amount' => 'total_bal_left',
            'total_bal_left' => 'total_bal_left',
        ];
        $sortColumn = $sortable[$sortKey] ?? 'total_bal_left';
        $query->orderBy($sortColumn, $sortValue);

        $totalRecords = $query->count();
        $result = $query->offset($offset)->limit($perPage)->get();

        // Calculate percentages (like in PHP loop)
        $totalPendingAll = $result->sum('total_bal_left');

        $formatted = $result->map(function ($row, $index) use ($totalPendingAll, $searchAccManager, $offset) {
            $percentage = $totalPendingAll > 0
                ? round(($row->total_bal_left * 100) / $totalPendingAll, 2)
                : 0;

            if (!empty($searchAccManager)) {
                $percentage = 100;
            }

            return [
                'rank'               => $offset + $index + 1,
                'prepared_by'        => $this->getAdminName($row->prepared_by) ?? $row->prepared_by,
                'company_name'       => $row->cus_com_name,
                'total_payment'      => $row->total_payment,
                'payment_received'   => $row->payment_received,
                'total_bal_left'     => $row->total_bal_left,
                'pending_percentage' => $percentage,
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $formatted,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords,
                'total_pages' => ceil($totalRecords / $perPage),
            ]
        ]);
    }
    // all receivables
    public function accountsReceivables(Request $request)
    {
        $accManager     = $request->input('acc_manager');
        $companyName    = $request->input('company_name');
        $invoiceNumber  = $request->input('invoice_number');
        $agingSearch    = $request->input('aging_search'); // format: "30-60"
        $teamName       = $request->input('admin_team');
        $hvcSearch      = $request->input('hvc_search');

        // Pagination parameters
        $page = (int) $request->input('pageno', 1);
        $perPage = (int) $request->input('records', 20);
        $offset = ($page - 1) * $perPage;

        $query = DB::table('tbl_tax_invoice as tti')
            ->selectRaw("
                tti.invoice_id,
                tti.invoice_generated_date,
                tti.cus_com_name,
                tti.invoice_status,
                tti.freight_amount,
                tti.freight_gst_amount,
                tti.sub_total_amount_without_gst,
                tti.total_gst_amount,
                tti.gst_sale_type,
                tti.payment_terms,
                tti.exchange_rate,
                tti.invoice_currency,
                tti.prepared_by,
                tti.buyer_gst,
                tti.o_id,
                tc.currency_html_code,
                twee.id as e_id,
                tti.invoice_closed_status,
                s.supply_order_payment_terms_abbrv,
                s.supply_order_payment_terms_id,
                s.supply_order_payment_terms_name,
                DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) as invoice_date_with_payment_terms,
                DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) as aging
            ")
            ->join('tbl_supply_order_payment_terms_master as s', 'tti.payment_terms', '=', 's.supply_order_payment_terms_id')
            ->leftJoin('tbl_tax_credit_note_invoice as ttcni', 'tti.invoice_id', '=', 'ttcni.invoice_id')
            ->leftJoin('tbl_web_enq_edit as twee', 'tti.o_id', '=', 'twee.order_id')
            ->leftJoin('tbl_currencies as tc', 'tc.currency_id', '=', 'tti.invoice_currency')
            ->where('tti.invoice_id', '>', 230000)
            ->where('tti.invoice_status', 'approved')
            ->where('tti.invoice_closed_status', 'No')
            ->where('tc.currency_status', 'yes')
            ->whereNull('ttcni.invoice_id');

        // Filters
        if (!empty($accManager)) {
            $query->where('tti.prepared_by', $accManager);
        }

        if (!empty($companyName)) {
            $query->where('tti.cus_com_name', 'like', "%$companyName%");
        }

        if (!empty($invoiceNumber)) {
            $query->where('tti.invoice_id', $invoiceNumber);
        }

        if (!empty($agingSearch)) {
            $parts = explode('-', $agingSearch);
            $minDays = (int) $parts[0];
            $maxDays = (int) $parts[1];
            $query->whereRaw("DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) BETWEEN ? AND ?", [$minDays, $maxDays]);
        }

        if (!empty($teamName)) {
            $teamMembers = DB::table('tbl_admin')
                ->where('admin_team', $teamName)
                ->pluck('admin_id')
                ->toArray();

            if (!empty($teamMembers)) {
                $query->whereIn('tti.prepared_by', $teamMembers);
            }
        }

        if (!empty($hvcSearch) && $hvcSearch == '1') {
            $query->whereIn('tti.cus_com_name', function ($sub) {
                $sub->select('company_name')
                    ->from('tbl_high_value_customer')
                    ->where('hvc_status', 'active');
            });
        }

        $totalRecords = $query->count();
        $rows = $query->orderByDesc('aging')->offset($offset)->limit($perPage)->get();

        // Format data
        $receivables = $rows->map(function ($row) {
            $totalInvoiceAmount = ($row->sub_total_amount_without_gst + $row->total_gst_amount + $row->freight_amount) * $row->exchange_rate;
            $partPaymentReceived = DB::table('tbl_payment_received')
                ->where('invoice_id', $row->invoice_id)
                ->sum('payment_received_value');
            return [
                'e_id'                  => $row->e_id,
                'invoice_id'           => $row->invoice_id,
                'invoice_date'         => date('m-d-Y', strtotime($row->invoice_generated_date)),
                'company_name'         => $row->cus_com_name,
                'account_manager'      => $this->getAdminName($row->prepared_by),
                'total_invoice_amount' => html_entity_decode($row->currency_html_code) . ' ' . number_format($totalInvoiceAmount, 2, '.', ''),
                'part_payment_received'=> html_entity_decode($row->currency_html_code) . ' ' . number_format($partPaymentReceived, 2, '.', ''),
                'balance_amount'       => html_entity_decode($row->currency_html_code) . ' ' . number_format($totalInvoiceAmount - $partPaymentReceived, 2, '.', ''),
                'aging'                => $row->aging > 0 ? "Overdue: " . abs($row->aging) : "Due in " . abs($row->aging) . " days",
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $receivables,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords,
                'total_pages' => ceil($totalRecords / $perPage),
            ]
        ]);
    }

    public function ShortTermReceivables(Request $request)
    {
        
            // Use Carbon for date handling
            $accManager     = $request->input('acc_manager');
            $companyName    = $request->input('company_name');
            $invoiceNumber  = $request->input('invoice_number');
            $agingSearch    = $request->input('aging_search');
            $teamName       = $request->input('admin_team');
            $hvcSearch      = $request->input('hvc_search');
            $filterType     = $request->input('filter_type', 'week'); // 'week' or 'month'

            // Pagination parameters
            $page    = (int) $request->input('page', 1);
            $perPage = (int) $request->input('record', 10);

            // Carbon for date range
            $now = now();
            if ($filterType === 'week') {
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
            } else {
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
            }

            $query = DB::table('tbl_tax_invoice as tti')
                ->selectRaw("
                    tti.invoice_id,
                    tti.invoice_generated_date,
                    tti.cus_com_name,
                    tti.invoice_status,
                    tti.freight_amount,
                    tti.freight_gst_amount,
                    tti.sub_total_amount_without_gst,
                    tti.total_gst_amount,
                    tti.gst_sale_type,
                    tti.payment_terms,
                    tti.exchange_rate,
                    tti.invoice_currency,
                    tti.prepared_by,
                    tti.buyer_gst,
                    tti.o_id,
                    ttcni.po_due_date,
                    tc.currency_html_code,
                    twee.id as e_id,
                    tti.invoice_closed_status,
                    s.supply_order_payment_terms_abbrv,
                    s.supply_order_payment_terms_id,
                    s.supply_order_payment_terms_name,
                    DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) as invoice_date_with_payment_terms,
                    DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) as aging
                ")
                ->join('tbl_supply_order_payment_terms_master as s', 'tti.payment_terms', '=', 's.supply_order_payment_terms_id')
                ->leftJoin('tbl_tax_credit_note_invoice as ttcni', 'tti.invoice_id', '=', 'ttcni.invoice_id')
                ->leftJoin('tbl_web_enq_edit as twee', 'tti.o_id', '=', 'twee.order_id')
                ->leftJoin('tbl_currencies as tc', 'tc.currency_id', '=', 'tti.invoice_currency')
                ->where('tti.invoice_id', '>', 230000)
                ->where('tti.invoice_status', 'approved')
                ->where('tti.invoice_closed_status', 'No')
                ->where('tc.currency_status', 'yes')
                ->whereBetween('tti.invoice_generated_date', [$start, $end])
                ->whereNull('ttcni.invoice_id');

            // Filters
            if (!empty($accManager)) {
                $query->where('tti.prepared_by', $accManager);
            }
            if (!empty($companyName)) {
                $query->where('tti.cus_com_name', 'like', "%$companyName%");
            }
            if (!empty($invoiceNumber)) {
                $query->where('tti.invoice_id', $invoiceNumber);
            }
            if (!empty($agingSearch)) {
                $parts = explode('-', $agingSearch);
                $minDays = (int) $parts[0];
                $maxDays = (int) $parts[1];
                $query->whereRaw("DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) BETWEEN ? AND ?", [$minDays, $maxDays]);
            }
            if (!empty($teamName)) {
                $teamMembers = DB::table('tbl_admin')
                    ->where('admin_team', $teamName)
                    ->pluck('admin_id')
                    ->toArray();
                if (!empty($teamMembers)) {
                    $query->whereIn('tti.prepared_by', $teamMembers);
                }
            }
            if (!empty($hvcSearch) && $hvcSearch == '1') {
                $query->whereIn('tti.cus_com_name', function ($sub) {
                    $sub->select('company_name')
                        ->from('tbl_high_value_customer')
                        ->where('hvc_status', 'active');
                });
            }

            $paginated = $query->orderByDesc('aging')
                ->paginate($perPage, ['*'], 'page', $page);

            $ShortTermReceivables = collect($paginated->items())->map(function ($row) {
                $totalInvoiceAmount = ($row->sub_total_amount_without_gst + $row->total_gst_amount + $row->freight_amount) * $row->exchange_rate;
                $partPaymentReceived = DB::table('tbl_payment_received')
                    ->where('invoice_id', $row->invoice_id)
                    ->sum('payment_received_value');
                return [
                    'e_id'                  => $row->e_id,
                    'invoice_id'            => $row->invoice_id,
                    'invoice_date'          => date('m-d-Y', strtotime($row->invoice_generated_date)),
                    'company_name'          => $row->cus_com_name,
                    'account_manager'       => $this->getAdminName($row->prepared_by),
                    'total_invoice_amount'  => html_entity_decode($row->currency_html_code) . ' ' . number_format($totalInvoiceAmount, 2, '.', ''),
                    'part_payment_received' => html_entity_decode($row->currency_html_code) . ' ' . number_format($partPaymentReceived, 2, '.', ''),
                    'balance_amount'        => html_entity_decode($row->currency_html_code) . ' ' . number_format($totalInvoiceAmount - $partPaymentReceived, 2, '.', ''),
                    'aging'                 => $row->aging > 0 ? "Overdue: " . abs($row->aging) : "Due in " . abs($row->aging) . " days",
                ];
            });

            return response()->json([
                'status'     => 'success',
                'message'    => 'Short Term Receivables listed successfully.',
                'data'       => $ShortTermReceivables,
                'pagination' => [
                    'total'        => $paginated->total(),
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                ]
            ], 200);
    }

    public function overdueReceivables(Request $request)
    {
        
        $currentYear = date('Y');

        $accManager     = $request->input('acc_manager');
        $companyName    = $request->input('company_name');
        $invoiceNumber  = $request->input('invoice_number');
        $agingSearch    = $request->input('aging_search'); // format: "30-60"
        $teamName       = $request->input('admin_team');
        $hvcSearch      = $request->input('hvc_search');
        $overdueFilter  = $request->input('overdue_filter', 'all'); // new: all, <1, 1-2, 2-3, 3+
        $sortKey        = $request->input('sortkey', 'aging');
        $sortValue      = strtoupper($request->input('sortvalue', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $page           = (int) $request->input('pageno', 1);
        $perPage        = (int) $request->input('records', 20);
        $offset         = ($page - 1) * $perPage;

        $query = DB::table('tbl_tax_invoice as tti')
            ->selectRaw("
                tti.invoice_id,
                tti.invoice_generated_date,
                tti.cus_com_name,
                tti.invoice_status,
                tti.freight_amount,
                tti.freight_gst_amount,
                tti.sub_total_amount_without_gst,
                tti.total_gst_amount,
                tti.gst_sale_type,
                tti.payment_terms,
                tti.exchange_rate,
                tti.invoice_currency,
                tti.prepared_by,
                tti.buyer_gst,
                tti.o_id,
                ttcni.po_due_date,
                tc.currency_html_code,
                twee.id as e_id,
                tti.invoice_closed_status,
                s.supply_order_payment_terms_abbrv,
                s.supply_order_payment_terms_id,
                s.supply_order_payment_terms_name,
                DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) as invoice_date_with_payment_terms,
                DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) as aging
            ")
            ->join('tbl_supply_order_payment_terms_master as s', 'tti.payment_terms', '=', 's.supply_order_payment_terms_id')
            ->leftJoin('tbl_tax_credit_note_invoice as ttcni', 'tti.invoice_id', '=', 'ttcni.invoice_id')
            ->leftJoin('tbl_web_enq_edit as twee', 'tti.o_id', '=', 'twee.order_id')
            ->leftJoin('tbl_currencies as tc', 'tc.currency_id', '=', 'tti.invoice_currency')
            ->where('tti.invoice_id', '>', 230000)
            ->where('tti.invoice_status', 'approved')
            ->where('tti.invoice_closed_status', 'No')
            ->where('tc.currency_status', 'yes')
            ->whereYear('tti.invoice_generated_date', $currentYear)
            ->whereNull('ttcni.invoice_id');

        // Filters
        if (!empty($accManager)) {
            $query->where('tti.prepared_by', $accManager);
        }

        if (!empty($companyName)) {
            $query->where('tti.cus_com_name', 'like', "%$companyName%");
        }

        if (!empty($invoiceNumber)) {
            $query->where('tti.invoice_id', $invoiceNumber);
        }

        if (!empty($agingSearch)) {
            $parts = explode('-', $agingSearch);
            $minDays = (int) $parts[0];
            $maxDays = (int) $parts[1];
            $query->whereRaw("DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) BETWEEN ? AND ?", [$minDays, $maxDays]);
        }

        if (!empty($teamName)) {
            $teamMembers = DB::table('tbl_admin')
                ->where('admin_team', $teamName)
                ->pluck('admin_id')
                ->toArray();

            if (!empty($teamMembers)) {
                $query->whereIn('tti.prepared_by', $teamMembers);
            }
        }

        if (!empty($hvcSearch) && $hvcSearch == '1') {
            $query->whereIn('tti.cus_com_name', function ($sub) {
                $sub->select('company_name')
                    ->from('tbl_high_value_customer')
                    ->where('hvc_status', 'active');
            });
        }

        // Overdue period filter (by months)
        // UI: All, <1 month, 1-2 months, 2-3 months, 3+ months
        // Logic: aging in days: <30, 30-59, 60-89, >=90
        if ($overdueFilter === '<1') {
            $query->whereRaw('DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) < 30');
        } elseif ($overdueFilter === '1-2') {
            $query->whereRaw('DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) BETWEEN 30 AND 59');
        } elseif ($overdueFilter === '2-3') {
            $query->whereRaw('DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) BETWEEN 60 AND 89');
        } elseif ($overdueFilter === '3+') {
            $query->whereRaw('DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) >= 90');
        }

        // Sorting logic
        $sortable = [
            'company_name' => 'tti.cus_com_name',
            'invoice_date' => 'tti.invoice_generated_date',
            'amount_due' => DB::raw('(tti.freight_amount + tti.sub_total_amount_without_gst + tti.total_gst_amount) * tti.exchange_rate'),
            'amount_received' => DB::raw('(SELECT SUM(payment_received_value) FROM tbl_payment_received WHERE invoice_id = tti.invoice_id)'),
            'overdue_for' => 'aging',
            'promised_date' => 'ttcni.po_due_date',
            'account_manager' => 'tti.prepared_by',
            'aging' => 'aging',
        ];
        $sortColumn = $sortable[$sortKey] ?? 'aging';
        $query->orderBy($sortColumn, $sortValue);

        // Pagination
        $totalRecords = $query->count();
        $rows = $query->offset($offset)->limit($perPage)->get();

        // Format data
        $overdueReceivables = $rows->map(function ($row) {
            $totalInvoiceAmount = ($row->sub_total_amount_without_gst + $row->total_gst_amount + $row->freight_amount) * $row->exchange_rate;
            $partPaymentReceived = DB::table('tbl_payment_received')
                ->where('invoice_id', $row->invoice_id)
                ->sum('payment_received_value');
            $amountDue = $totalInvoiceAmount - $partPaymentReceived;
            return [
                'company_name'         => $row->cus_com_name,
                'invoice_id'           => $row->invoice_id,
                'invoice_date'         => date('d M Y', strtotime($row->invoice_generated_date)),
                'total_invoice_amount' => html_entity_decode($row->currency_html_code) . ' ' . number_format($totalInvoiceAmount, 2, '.', ','),
                'amount_received'      => html_entity_decode($row->currency_html_code) . ' ' . number_format($partPaymentReceived, 2, '.', ','),
                'amount_due'           => html_entity_decode($row->currency_html_code) . ' ' . number_format($amountDue, 2, '.', ','),
                'due_date'             => $row->po_due_date,
                'overdue_for'          => $row->aging > 0 ? "Overdue: " . abs($row->aging) . " days" : "Due in " . abs($row->aging) . " days",
                'last_follow_up_date'  => "18 Mar 2024", // Placeholder, replace with actual if available
                'promised_date_of_payment' => $row->po_due_date,
                'account_manager'      => $this->getAdminName($row->prepared_by),
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $overdueReceivables,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords,
                'total_pages' => ceil($totalRecords / $perPage),
            ]
        ]);
    }

    public function DisputedReceivables(Request $request)
    {
        $accManager     = $request->input('acc_manager');
        $companyName    = $request->input('company_name');
        $invoiceNumber  = $request->input('invoice_number');
        $agingSearch    = $request->input('aging_search'); // format: "30-60"
        $teamName       = $request->input('admin_team');
        $hvcSearch      = $request->input('hvc_search');

        $query = DB::table('tbl_tax_invoice as tti')
            ->selectRaw("
                tti.invoice_id,
                tti.invoice_generated_date,
                tti.cus_com_name,
                tti.invoice_status,
                tti.freight_amount,
                tti.freight_gst_amount,
                tti.sub_total_amount_without_gst,
                tti.total_gst_amount,
                tti.gst_sale_type,
                tti.payment_terms,
                tti.exchange_rate,
                tti.invoice_currency,
                tti.prepared_by,
                tti.buyer_gst,
                tti.o_id,
                ttcni.po_due_date,
                tc.currency_html_code,
                twee.id as e_id,
                tti.invoice_closed_status,
                s.supply_order_payment_terms_abbrv,
                s.supply_order_payment_terms_id,
                s.supply_order_payment_terms_name,
                DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) as invoice_date_with_payment_terms,
                DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) as aging,
                tid.dispute_reason,
                tid.disputed_date,
                tid.disputed_amount,
                tid.dispute_status
            ")
            ->join('tbl_invoice_disputes as tid', 'tti.invoice_id', '=', 'tid.invoice_id') // Only disputed invoices
            ->leftJoin('tbl_supply_order_payment_terms_master as s', 'tti.payment_terms', '=', 's.supply_order_payment_terms_id')
            ->leftJoin('tbl_tax_credit_note_invoice as ttcni', 'tti.invoice_id', '=', 'ttcni.invoice_id')
            ->leftJoin('tbl_web_enq_edit as twee', 'tti.o_id', '=', 'twee.order_id')
            ->leftJoin('tbl_currencies as tc', 'tc.currency_id', '=', 'tti.invoice_currency')
            ->where('tti.invoice_id', '>', 230000)
            ->where('tti.invoice_status', 'approved') // Only approved invoices
            ->where('tti.invoice_closed_status', 'No') // Only non-closed invoices
            ->where('tid.deleteflag', 'active') // Only active disputes
            ->where('tid.dispute_status', 'active') // Only active disputes (not resolved)
            ->whereNull('ttcni.invoice_id');
        // ðŸ”Ž Filters
        if (!empty($accManager)) {
            $query->where('tti.prepared_by', $accManager);
        }

        if (!empty($companyName)) {
            $query->where('tti.cus_com_name', 'like', "%$companyName%");
        }

        if (!empty($invoiceNumber)) {
            $query->where('tti.invoice_id', $invoiceNumber);
        }

        if (!empty($agingSearch)) {
            $parts = explode('-', $agingSearch);
            $minDays = (int) $parts[0];
            $maxDays = (int) $parts[1];
            $query->whereRaw("DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) BETWEEN ? AND ?", [$minDays, $maxDays]);
        }

        if (!empty($teamName)) {
            $teamMembers = DB::table('tbl_admin')
                ->where('admin_team', $teamName)
                ->pluck('admin_id')
                ->toArray();

            if (!empty($teamMembers)) {
                $query->whereIn('tti.prepared_by', $teamMembers);
            }
        }

        if (!empty($hvcSearch) && $hvcSearch == '1') {
            $query->whereIn('tti.cus_com_name', function ($sub) {
                $sub->select('company_name')
                    ->from('tbl_high_value_customer')
                    ->where('hvc_status', 'active');
            });
        }

        $rows = $query->orderByDesc('aging')->get();
        

        // ðŸ§® Format data
        $disputedReceivables = $rows->map(function ($row) {
            // Calculate total invoice with tax
            $totalInvoiceAmount = ($row->sub_total_amount_without_gst + $row->total_gst_amount + $row->freight_amount) * $row->exchange_rate;

            // Fetch partial payment received
            $partPaymentReceived = DB::table('tbl_payment_received')
                ->where('invoice_id', $row->invoice_id)
                ->sum('payment_received_value');

            // Status based on aging
            $status = $row->aging > 0 ? 'OVERDUE: ' . abs($row->aging) . ' DAYS' : 'DUE: ' . abs($row->aging) . ' DAYS';

            return [
                'company_name'         => $row->cus_com_name,
                'invoice_id'           => $row->invoice_id,
                'invoice_date'         => date('d M Y', strtotime($row->invoice_generated_date)),
                'amount'               => html_entity_decode($row->currency_html_code) . ' ' . number_format($totalInvoiceAmount, 2, '.', ''),
                'due_date'             => date('d M Y', strtotime($row->invoice_date_with_payment_terms)),
                'status'               => $status,
                'adp'                  => '60 days', // Average payment period
                'account_manager'      => $this->getAdminName($row->prepared_by),
                'dispute_reason'       => $row->dispute_reason ?? 'Quality Issue',
                'dispute_date'         => $row->disputed_date ? date('d M Y', strtotime($row->disputed_date)) : date('d M Y'),
                'disputed_amount'      => $row->disputed_amount,
                'dispute_status'       => $row->dispute_status,
                'aging'                => abs($row->aging),
                'view_details'         => 'View details'
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $disputedReceivables,
            'total_count' => $disputedReceivables->count()
        ]);
    }

    /**
     * Helper: Get admin name from admin_id
     */
    private function getAdminName($adminId)
    {
        $row = DB::table('tbl_admin')
            ->select('admin_fname', 'admin_lname')
            ->where('admin_id', $adminId)
            ->where('deleteflag', 'active')
            ->first();

        if ($row) {
            return ucfirst(trim($row->admin_fname . ' ' . $row->admin_lname));
        }

        return 'Unknown';
    }

    /**
     * Get accounts receivables aging dashboard data organized into buckets
     */
    public function accountsReceivablesAgingDashboard(Request $request)
    {
        // Extract and validate parameters following existing patterns
        $acc_manager = $request->query('acc_manager', 0);
        $company_name = $request->query('company_name', '');
        $date_from = $request->query('date_from', null);
        $date_to = $request->query('date_to', null);

        // Define aging buckets with ranges
        $aging_buckets = [
            'due_in' => ['min' => null, 'max' => -1, 'label' => 'Due in'],
            'next_7_days' => ['min' => 0, 'max' => 7, 'label' => 'Next 7 days'], 
            'next_15_days' => ['min' => 8, 'max' => 15, 'label' => 'Next 15 days'],
            'next_1_month' => ['min' => 16, 'max' => 30, 'label' => 'Next 1 month'],
            'over_1_month' => ['min' => 31, 'max' => 90, 'label' => '> 1 month'],
            'overdue' => ['min' => 91, 'max' => null, 'label' => 'Overdue']
        ];

        $bucket_results = [];
        $total_receivables = 0;
        $total_invoices = 0;

        // Process each aging bucket
        foreach ($aging_buckets as $bucket_key => $bucket_config) {
            // Build base where clause following existing pattern
            $where = "
                tti.invoice_id > 230000
                AND tti.invoice_status = 'approved'
                AND tti.invoice_closed_status = 'No'
                AND tti.payment_terms = s.supply_order_payment_terms_id
                AND ttcni.invoice_id IS NULL
            ";

            // Add aging condition for current bucket
            if ($bucket_config['min'] !== null && $bucket_config['max'] !== null) {
                $where .= " AND DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) 
                            BETWEEN {$bucket_config['min']} AND {$bucket_config['max']} ";
            } elseif ($bucket_config['min'] !== null) {
                $where .= " AND DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) 
                            >= {$bucket_config['min']} ";
            } elseif ($bucket_config['max'] !== null) {
                $where .= " AND DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) 
                            <= {$bucket_config['max']} ";
            }

            // Account manager filter
            if ($acc_manager !== '' && $acc_manager != '0' && $acc_manager != 'All') {
                $where .= " AND tti.prepared_by = '$acc_manager' ";
            }

            // Company name filter
            if ($company_name !== '') {
                $where .= " AND tti.cus_com_name LIKE '%$company_name%' ";
            }

            // Date range filter
            if ($date_from && $date_to) {
                $where .= " AND tti.invoice_generated_date BETWEEN '$date_from' AND '$date_to' ";
            }

            // SQL query following existing patterns
            $sql = "SELECT 
                        COUNT(DISTINCT tti.invoice_id) AS invoice_count,
                        SUM((tti.freight_amount + tti.sub_total_amount_without_gst + tti.total_gst_amount) * tti.exchange_rate) AS total_amount,
                        tti.exchange_rate
                    FROM tbl_tax_invoice tti
                    INNER JOIN tbl_supply_order_payment_terms_master s ON tti.payment_terms = s.supply_order_payment_terms_id
                    LEFT JOIN tbl_tax_credit_note_invoice ttcni ON ttcni.invoice_id = tti.invoice_id
                    WHERE $where
                    GROUP BY tti.exchange_rate
                    ORDER BY total_amount DESC";

            $result = DB::select($sql);

            // Calculate bucket totals
            $bucket_total = 0;
            $bucket_count = 0;
            $currency = 'INR'; // Default currency

            if (!empty($result)) {
                foreach ($result as $row) {
                    $bucket_total += $row->total_amount ?? 0;
                    $bucket_count += $row->invoice_count ?? 0;
                }
                // Use first result's exchange rate for currency detection
                $currency = $result[0]->exchange_rate ? 'USD' : 'INR';
            }

            // Build aging range label
            $aging_range = '';
            if ($bucket_config['min'] !== null && $bucket_config['max'] !== null) {
                $aging_range = $bucket_config['min'] . ' - ' . $bucket_config['max'] . ' days';
            } elseif ($bucket_config['min'] !== null) {
                $aging_range = '> ' . $bucket_config['min'] . ' days';
            } elseif ($bucket_config['max'] !== null) {
                $aging_range = '< ' . abs($bucket_config['max']) . ' days';
            }

            $bucket_results[] = [
                'bucket' => $bucket_key,
                'label' => $bucket_config['label'],
                'aging_range' => $aging_range,
                'total_amount' => (float) $bucket_total,
                'invoice_count' => (int) $bucket_count,
                'currency' => $currency
            ];

            $total_receivables += $bucket_total;
            $total_invoices += $bucket_count;
        }

        // Build summary data
        $summary = [
            'total_receivables' => (float) $total_receivables,
            'total_invoices' => (int) $total_invoices,
            'currency' => 'INR'
        ];

        // Build filters applied data
        $filters_applied = [
            'acc_manager' => $acc_manager != 0 ? $acc_manager : null,
            'company_name' => $company_name ?: null,
            'date_range' => ($date_from && $date_to) ? "$date_from to $date_to" : null
        ];

        // Return response following existing pattern
        return response()->json([
            'status' => 'success',
            'message' => 'Accounts receivables aging dashboard data retrieved successfully',
            'data' => [
                'aging_buckets' => $bucket_results,
                'summary' => $summary,
                'filters_applied' => array_filter($filters_applied) // Remove null values
            ]
        ], 200);
    }

    public function Overview(Request $request)
    {
        $rupee = html_entity_decode("&#8377;") . "\u{00A0}"; // Rupee symbol with non-breaking space
        $not_yet_due = $rupee . moneyFormatIndia($this->pending_account_receivables_by_aging_not_yet_due());

        $pending_account_receivables =  $this->pending_account_receivables($acc_manager=0,$qtr_start_date_show=null,$qtr_end_date_show=null,$enq_source_search=0,$company_name='',$aging_min=0,$aging_max=0);
        $payment_received_by_aging = $this->payment_received_by_aging($acc_manager=0,$aging_min=0,$aging_max=0,$company_name='',);

        $pending_account_receivables_one =  $this->pending_account_receivables($acc_manager=0,$qtr_start_date_show=null,$qtr_end_date_show=null,$enq_source_search=0,$company_name='',$aging_min=1,$aging_max=30);
        $payment_received_by_aging_one = $this->payment_received_by_aging($acc_manager=0,$aging_min=1,$aging_max=30,$company_name='',);
        $total_receivables = $rupee . moneyFormatIndia($pending_account_receivables - $payment_received_by_aging);
        $pending_account_receivables_two =  $this->pending_account_receivables($acc_manager=0,$qtr_start_date_show=null,$qtr_end_date_show=null,$enq_source_search=0,$company_name='',$aging_min=31,$aging_max=60);
        $payment_received_by_aging_two = $this->payment_received_by_aging($acc_manager=0,$aging_min=31,$aging_max=60,$company_name='',);

        $pending_account_receivables_three =  $this->pending_account_receivables($acc_manager=0,$qtr_start_date_show=null,$qtr_end_date_show=null,$enq_source_search=0,$company_name='',$aging_min=61,$aging_max=90);
        $payment_received_by_aging_three = $this->payment_received_by_aging($acc_manager=0,$aging_min=61,$aging_max=90,$company_name='',);

        $pending_account_receivables_three_plus =  $this->pending_account_receivables($acc_manager=0,$qtr_start_date_show=null,$qtr_end_date_show=null,$enq_source_search=0,$company_name='',$aging_min=91,$aging_max=365);
        $payment_received_by_aging_three_plus = $this->payment_received_by_aging($acc_manager=0,$aging_min=91,$aging_max=365,$company_name='',);

        $overdue = $pending_account_receivables_one - $payment_received_by_aging_one
                        + $pending_account_receivables_two - $payment_received_by_aging_two
                        + $pending_account_receivables_three - $payment_received_by_aging_three
                        + $pending_account_receivables_three_plus - $payment_received_by_aging_three_plus;

        // Calculate actual disputed receivables from tbl_invoice_disputes
        $disputedAmount = $this->getDisputedReceivablesTotal();
        $disputed = $rupee . moneyFormatIndia($disputedAmount);

        $one_month = $rupee . moneyFormatIndia($pending_account_receivables_one - $payment_received_by_aging_one);
        $two_months = $rupee . moneyFormatIndia($pending_account_receivables_two - $payment_received_by_aging_two);
        $three_months = $rupee . moneyFormatIndia($pending_account_receivables_three - $payment_received_by_aging_three);
        $three_plus_months = $rupee . moneyFormatIndia($pending_account_receivables_three_plus - $payment_received_by_aging_three_plus);

        return response()->json([
            'status' => 'success',
            'total_receivables' => $total_receivables,
            'not_yet_due' => $not_yet_due,
            'overdue' => $rupee . moneyFormatIndia($overdue),
            'disputed' => $disputed,
            'one_month' => $one_month,
            'two_months' => $two_months,
            'three_months' => $three_months,
            'three_plus_months' => $three_plus_months,

            // 'overdue' => array_sum([
            //     $pending_account_receivables_one - $payment_received_by_aging_one,
            //     $pending_account_receivables_two - $payment_received_by_aging_two,
            //     $pending_account_receivables_three - $payment_received_by_aging_three,
            //     $pending_account_receivables_three_plus - $payment_received_by_aging_three_plus,
            // ]),
        ], 200);

        
    }

    public function Collections()
    {
        // Get current month and year
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        $ytd = PaymentReceived::active()->whereYear('payment_received_date', $currentYear)
            ->sum('payment_received_value');

        $mtd = PaymentReceived::active()->whereMonth('payment_received_date', $currentMonth)
            ->sum('payment_received_value');
        return response()->json([
            'status' => 'success',
            'ytd' => html_entity_decode("&#8377;") . $ytd,
            'mtd' => html_entity_decode("&#8377;") . $mtd,
            'dso' => html_entity_decode("&#8377;") . 000,
        ]);
    }

    
    public function receivableDetail(Request $request)
    {
         
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $invoice_id = $request->input('invoice_id');

        try {
            // 1. Fetch invoice with comprehensive joins following project patterns

            $invoice = DB::table('tbl_tax_invoice as tti')
                ->leftJoin('tbl_admin as ta', 'tti.prepared_by', '=', 'ta.admin_id')
                ->leftJoin('tbl_supply_order_payment_terms_master as s', 'tti.payment_terms', '=', 's.supply_order_payment_terms_id')
                ->leftJoin('tbl_currencies as tc', 'tti.invoice_currency', '=', 'tc.currency_id')
                ->leftJoin('tbl_gst_sale_type_master as tg', 'tti.gst_sale_type', '=', 'tg.gst_sale_type_id')
                ->where('tti.invoice_id', $invoice_id)
                ->where('tti.deleteflag', 'active')
                ->select(
                    'tti.*',
                    'ta.admin_fname', 'ta.admin_lname',
                    's.supply_order_payment_terms_abbrv',
                    'tc.currency_html_code', 'tc.currency_code',
                    'tti.con_name as contact_person_name',
                    'tti.con_email as contact_person_email',
                    'tti.con_mobile as contact_person_phone',
                    'tg.gst_sale_type_name as order_type'
                )
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice not found.'
                ], 404);
            }

            // 2. Enhanced Payment details with proper structure
            $payments_query = DB::table('tbl_payment_received')
                ->where('invoice_id', $invoice_id)
                ->where('deleteflag', 'active')
                ->select(
                    'payment_received_id as id',
                    'payment_received_value',
                    'payment_received_date',
                    'payment_received_via as payment_method',
                    'transaction_id as payment_reference_no',
                    'payment_received_in_bank as bank_name',
                    'payment_received_value_tds',
                    'credit_note_value',
                    'lda_other_value'
                )
                ->orderBy('payment_received_date', 'asc')
                ->get();

            // Format existing payments for UI
            $existing_payments = $payments_query->map(function ($payment, $index) use ($invoice) {
                $amount_received_formatted = html_entity_decode($invoice->currency_html_code) . ' ' . number_format($payment->payment_received_value, 2, '.', ',');
                
                // Calculate amount to be received for foreign currency
                $amount_to_be_received_formatted = '-';
                $exchange_rate_formatted = '-';
                
                if ($invoice->currency_code !== 'INR' && !empty($invoice->exchange_rate)) {
                    $amount_to_be_received = $payment->payment_received_value / $invoice->exchange_rate;
                    $amount_to_be_received_formatted = '$' . number_format($amount_to_be_received, 2, '.', ',');
                    $exchange_rate_formatted = number_format($invoice->exchange_rate, 2);
                }

                return [
                    's_no' => $index + 1,
                    'payment_received_id' => $payment->id,
                    'amount_to_be_received' => $amount_to_be_received_formatted,
                    'exchange_rate' => $exchange_rate_formatted,
                    'amount_received' => $amount_received_formatted,
                    'payment_date' => $payment->payment_received_date ? date('d M Y', strtotime($payment->payment_received_date)) : null,
                    'payment_method' => $this->getPaymentMethodName($payment->payment_method),
                    'payment_reference_no' => $payment->payment_reference_no ?: '',
                    'bank_name' => $this->getBankName($payment->bank_name),
                    'can_edit' => true,
                    'can_delete' => true,
                ];
            });

            // Calculate financial totals
            $invoiceAmount = ($invoice->sub_total_amount_without_gst + $invoice->total_gst_amount + $invoice->freight_amount) * $invoice->exchange_rate;
            $totalPaymentsReceived = $payments_query->sum('payment_received_value');
            $remainingAmount = $invoiceAmount - $totalPaymentsReceived;

            // New payment entry row for adding payments
            $new_payment_entry = [
                's_no' => $existing_payments->count() + 1,
                'payment_received_id' => '',
                'amount_to_be_received' => '',
                'exchange_rate' => $invoice->currency_code !== 'INR' ? number_format($invoice->exchange_rate, 2) : '-',
                'amount_received' => '',
                'payment_date' => '',
                'payment_method' => '',
                'payment_reference_no' => '',
                'bank_name' => '',
                'can_edit' => false,
                'can_delete' => false,
                'is_new_entry' => true,
                'default_amount' => number_format($remainingAmount, 2)
            ];

            // Get dropdown data for new payments
            $payment_methods = DB::table('tbl_payment_type_master')
                ->where('deleteflag', 'active')
                ->select('payment_type_id as id', 'payment_type_name as name')
                ->get();

            $banks = DB::table('tbl_company_bank_address')
                ->where('deleteflag', 'active')
                ->select('bank_id as id', 'bank_name as name')
                ->get();

            // Enhanced payment_details structure
            $payment_details = [
                'existing_payments' => $existing_payments,
                'new_payment_entry' => $new_payment_entry,
                'payment_methods' => $payment_methods,
                'banks' => $banks,
                'totals' => [
                    'invoice_amount' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($invoiceAmount, 2, '.', ','),
                    'total_received' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($totalPaymentsReceived, 2, '.', ','),
                    'remaining_amount' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($remainingAmount, 2, '.', ','),
                ]
            ];

            // 3. Enhanced TCS/TDS Details with actual data from payments
            $tcs_tds_details = [
                'tds_value' => $payments_query->sum('payment_received_value_tds') ?: '0',
                'credit_note' => $payments_query->sum('credit_note_value') ?: '0',
                'lda_ro_other_charges' => $payments_query->sum('lda_other_value') ?: '0',
                'balance_value' => number_format($remainingAmount, 2),
                'remark' => '',
                'can_edit' => true
            ];

            // 4. Enhanced Remarks with proper admin join
            $remarks_query = [];
            if (Schema::hasTable('tbl_invoice_remarks')) {
                $remarks_query = DB::table('tbl_invoice_remarks as tir')
                    ->leftJoin('tbl_admin as ta', 'tir.updated_by', '=', 'ta.admin_id')
                    ->where('tir.invoice_id', $invoice_id)
                    ->where('tir.deleteflag', 'active')
                    ->select(
                        'tir.remark_text as remarks',
                        'tir.follow_up_date',
                        'tir.payment_promised_date',
                        'tir.remark_date',
                        DB::raw("CONCAT(IFNULL(ta.admin_fname, ''), ' ', IFNULL(ta.admin_lname, '')) as updated_by_name")
                    )
                    ->orderByDesc('tir.remark_date')
                    ->get();
            }

            $remarks = collect($remarks_query)->map(function ($remark) {
                return [
                    'remarks' => $remark->remarks ?: '',
                    'follow_up_date' => $remark->follow_up_date ? date('d M Y', strtotime($remark->follow_up_date)) : '-',
                    'payment_promised_on' => $remark->payment_promised_date ? date('d M Y', strtotime($remark->payment_promised_date)) : '-',
                    'updated_by' => trim($remark->updated_by_name) ?: 'Unknown',
                    'remark_date' => $remark->remark_date ? date('Y-m-d H:i:s', strtotime($remark->remark_date)) : null,
                ];
            });

            // 4b. Payment Remarks (tbl_payment_remarks)
            $remarks_payment_remarks = [];
            if (Schema::hasTable('tbl_payment_remarks')) {
                $remarks_payment_remarks_query = DB::table('tbl_payment_remarks as tpr')
                    ->leftJoin('tbl_admin as ta', 'tpr.updated_by', '=', 'ta.admin_id')
                    ->where('tpr.invoice_id', $invoice_id)
                    ->select(
                        'tpr.payment_remarks_only as remarks',
                        'tpr.payment_remarks_follow_up_date as payment_expected_by',
                        DB::raw("CONCAT(IFNULL(ta.admin_fname, ''), ' ', IFNULL(ta.admin_lname, '')) as updated_by_name"),
                        'tpr.inserted_date as remark_date'
                    )
                    ->orderByDesc('tpr.inserted_date')
                    ->get();
                $remarks_payment_remarks = collect($remarks_payment_remarks_query)->map(function ($row) {
                    return [
                        'remarks' => $row->remarks ?: '',
                        'payment_expected_by' => $row->payment_expected_by ? date('d M Y', strtotime($row->payment_expected_by)) : '-',
                        'updated_by' => trim($row->updated_by_name) ?: 'Unknown',
                        'remark_date' => $row->remark_date ? date('Y-m-d H:i:s', strtotime($row->remark_date)) : null,
                    ];
                });
            }

            // 5. Calculate aging and status following project patterns
            $due_date = null;
            $status = 'DUE';
            $days = 0;
            
            if (isset($invoice->invoice_generated_date) && isset($invoice->supply_order_payment_terms_abbrv)) {
                $due_date = date('Y-m-d', strtotime($invoice->invoice_generated_date . ' + ' . $invoice->supply_order_payment_terms_abbrv . ' days'));
                $now = now();
                $due = \Carbon\Carbon::parse($due_date);
                $diff = $now->diffInDays($due, false);
                
                if ($diff < 0) {
                    $status = 'OVERDUE';
                    $days = abs($diff);
                } else {
                    $status = 'DUE';
                    $days = $diff;
                }
            }

            // 6. Build comprehensive response following project standards
            return response()->json([
                'status' => 'success',
                'data' => [
                    'invoice_id' => $invoice->invoice_id,
                    'company_name' => $invoice->cus_com_name,
                    'contact_person_name' => $invoice->contact_person_name ?: '',
                    'contact_person_email' => $invoice->contact_person_email ?: '',
                    'contact_person_phone' => $invoice->contact_person_phone ?: '',
                    'order_type_currency' => $invoice->order_type . ' ' . $invoice->currency_code,
                    'invoice_date' => $invoice->invoice_generated_date ? date('m-d-Y', strtotime($invoice->invoice_generated_date)) : null,
                    'due_date' => $due_date ? date('m-d-Y', strtotime($due_date)) : null,
                    'due_status' => $status . ($days !== null ? ': ' . number_format($days, 2) . ' DAYS' : ''),
                    'actual_days_to_pay' => 60, // Default ADP - can be calculated from payment history
                    'payment_behaviour' => 'PROMPT PAYER', // Can be determined from payment history
                    'invoice_amount' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($invoiceAmount, 2, '.', ','),
                    'amount_received' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($totalPaymentsReceived, 2, '.', ','),
                    'amount_due' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($remainingAmount, 2, '.', ','),
                    'completed_on' => null, // Can be calculated when invoice is fully paid
                    'account_manager' => isset($invoice->admin_fname) ? ucfirst(trim($invoice->admin_fname . ' ' . $invoice->admin_lname)) : 'Unknown',
                    'payment_details' => $payment_details,
                    'tcs_tds_details' => $tcs_tds_details,
                    'remarks' => $remarks,
                    'remarks_payment_remarks' => $remarks_payment_remarks,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'An error occurred while fetching receivable details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }  

    /**
     * Receivable Detail V2: Enhanced for UI (exchange rate disable logic, all fields as per screenshot)
     */
    public function receivableDetailV2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $invoice_id = $request->input('invoice_id');

        try {
            // 1. Fetch invoice with comprehensive joins
            $invoice = DB::table('tbl_tax_invoice as tti')
                ->leftJoin('tbl_admin as ta', 'tti.prepared_by', '=', 'ta.admin_id')
                ->leftJoin('tbl_supply_order_payment_terms_master as s', 'tti.payment_terms', '=', 's.supply_order_payment_terms_id')
                ->leftJoin('tbl_currencies as tc', 'tti.invoice_currency', '=', 'tc.currency_id')
                ->leftJoin('tbl_web_enq_edit as twee', 'tti.o_id', '=', 'twee.order_id')
                ->where('tti.invoice_id', $invoice_id)
                ->where('tti.deleteflag', 'active')
                ->select(
                    'tti.*',
                    'ta.admin_fname', 'ta.admin_lname',
                    's.supply_order_payment_terms_abbrv',
                    'tc.currency_html_code', 'tc.currency_code',
                    'twee.Cus_name as contact_person_name',
                    'twee.Cus_email as contact_person_email',
                    'twee.Cus_mob as contact_person_phone'
                )
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice not found.'
                ], 404);
            }

            // 2. Payment details
            $payments_query = DB::table('tbl_payment_received')
                ->where('invoice_id', $invoice_id)
                ->where('deleteflag', 'active')
                ->select(
                    'payment_received_id as id',
                    'payment_received_value',
                    'payment_received_date',
                    'payment_received_via as payment_method',
                    'transaction_id as payment_reference_no',
                    'payment_received_in_bank as bank_name',
                    'payment_received_value_tds',
                    'credit_note_value',
                    'lda_other_value'
                )
                ->orderBy('payment_received_date', 'asc')
                ->get();

            $existing_payments = $payments_query->map(function ($payment, $index) use ($invoice) {
                $amount_received_formatted = html_entity_decode($invoice->currency_html_code) . ' ' . number_format($payment->payment_received_value, 2, '.', ',');
                $amount_to_be_received_formatted = '-';
                $exchange_rate_formatted = '-';
                if ($invoice->currency_code !== 'INR' && !empty($invoice->exchange_rate)) {
                    $amount_to_be_received = $payment->payment_received_value / $invoice->exchange_rate;
                    $amount_to_be_received_formatted = '$' . number_format($amount_to_be_received, 2, '.', ',');
                    $exchange_rate_formatted = number_format($invoice->exchange_rate, 2);
                }
                return [
                    's_no' => $index + 1,
                    'amount_to_be_received' => $amount_to_be_received_formatted,
                    'exchange_rate' => $exchange_rate_formatted,
                    'amount_received' => $amount_received_formatted,
                    'payment_date' => $payment->payment_received_date ? date('d M Y', strtotime($payment->payment_received_date)) : null,
                    'payment_method' => $this->getPaymentMethodName($payment->payment_method),
                    'payment_reference_no' => $payment->payment_reference_no ?: '',
                    'bank_name' => $this->getBankName($payment->bank_name),
                    'can_edit' => true,
                    'can_delete' => true,
                ];
            });

            $invoiceAmount = ($invoice->sub_total_amount_without_gst + $invoice->total_gst_amount + $invoice->freight_amount) * $invoice->exchange_rate;
            $totalPaymentsReceived = $payments_query->sum('payment_received_value');
            $remainingAmount = $invoiceAmount - $totalPaymentsReceived;

            $new_payment_entry = [
                's_no' => $existing_payments->count() + 1,
                'amount_to_be_received' => '',
                'exchange_rate' => $invoice->currency_code !== 'INR' ? number_format($invoice->exchange_rate, 2) : '-',
                'amount_received' => '',
                'payment_date' => '',
                'payment_method' => '',
                'payment_reference_no' => '',
                'bank_name' => '',
                'can_edit' => false,
                'can_delete' => false,
                'is_new_entry' => true,
                'default_amount' => number_format($remainingAmount, 2)
            ];

            $payment_methods = DB::table('tbl_payment_type_master')
                ->where('deleteflag', 'active')
                ->select('payment_type_id as id', 'payment_type_name as name')
                ->get();

            $banks = DB::table('tbl_company_bank_address')
                ->where('deleteflag', 'active')
                ->select('bank_id as id', 'bank_name as name')
                ->get();

            $payment_details = [
                'existing_payments' => $existing_payments,
                'new_payment_entry' => $new_payment_entry,
                'payment_methods' => $payment_methods,
                'banks' => $banks,
                'totals' => [
                    'invoice_amount' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($invoiceAmount, 2, '.', ','),
                    'total_received' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($totalPaymentsReceived, 2, '.', ','),
                    'remaining_amount' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($remainingAmount, 2, '.', ','),
                ]
            ];

            // 3. TCS/TDS Details
            $tcs_tds_details = [
                'tds_value' => $payments_query->sum('payment_received_value_tds') ?: '0',
                'credit_note' => $payments_query->sum('credit_note_value') ?: '0',
                'lda_ro_other_charges' => $payments_query->sum('lda_other_value') ?: '0',
                'balance_value' => number_format($remainingAmount, 2),
                'remark' => '',
                'can_edit' => true
            ];

            // 4. Remarks
            $remarks_query = [];
            if (Schema::hasTable('tbl_invoice_remarks')) {
                $remarks_query = DB::table('tbl_invoice_remarks as tir')
                    ->leftJoin('tbl_admin as ta', 'tir.updated_by', '=', 'ta.admin_id')
                    ->where('tir.invoice_id', $invoice_id)
                    ->where('tir.deleteflag', 'active')
                    ->select(
                        'tir.remark_text as remarks',
                        'tir.follow_up_date',
                        'tir.payment_promised_date',
                        'tir.remark_date',
                        DB::raw("CONCAT(IFNULL(ta.admin_fname, ''), ' ', IFNULL(ta.admin_lname, '')) as updated_by_name")
                    )
                    ->orderByDesc('tir.remark_date')
                    ->get();
            }

            $remarks = collect($remarks_query)->map(function ($remark) {
                return [
                    'remarks' => $remark->remarks ?: '',
                    'follow_up_date' => $remark->follow_up_date ? date('d M Y', strtotime($remark->follow_up_date)) : '-',
                    'payment_promised_on' => $remark->payment_promised_date ? date('d M Y', strtotime($remark->payment_promised_date)) : '-',
                    'updated_by' => trim($remark->updated_by_name) ?: 'Unknown',
                    'remark_date' => $remark->remark_date ? date('Y-m-d H:i:s', strtotime($remark->remark_date)) : null,
                ];
            });

            // Payment Remarks (tbl_payment_remarks)
            $remarks_payment_remarks = [];
            if (Schema::hasTable('tbl_payment_remarks')) {
                $remarks_payment_remarks_query = DB::table('tbl_payment_remarks as tpr')
                    ->leftJoin('tbl_admin as ta', 'tpr.updated_by', '=', 'ta.admin_id')
                    ->where('tpr.invoice_id', $invoice_id)
                    ->select(
                        'tpr.payment_remarks_only as remarks',
                        'tpr.payment_remarks_follow_up_date as payment_expected_by',
                        DB::raw("CONCAT(IFNULL(ta.admin_fname, ''), ' ', IFNULL(ta.admin_lname, '')) as updated_by_name"),
                        'tpr.inserted_date as remark_date'
                    )
                    ->orderByDesc('tpr.inserted_date')
                    ->get();
                $remarks_payment_remarks = collect($remarks_payment_remarks_query)->map(function ($row) {
                    return [
                        'remarks' => $row->remarks ?: '',
                        'payment_expected_by' => $row->payment_expected_by ? date('d M Y', strtotime($row->payment_expected_by)) : '-',
                        'updated_by' => trim($row->updated_by_name) ?: 'Unknown',
                        'remark_date' => $row->remark_date ? date('Y-m-d H:i:s', strtotime($row->remark_date)) : null,
                    ];
                });
            }

            // 5. Aging and status
            $due_date = null;
            $status = 'DUE';
            $days = 0;
            if (isset($invoice->invoice_generated_date) && isset($invoice->supply_order_payment_terms_abbrv)) {
                $due_date = date('Y-m-d', strtotime($invoice->invoice_generated_date . ' + ' . $invoice->supply_order_payment_terms_abbrv . ' days'));
                $now = now();
                $due = \Carbon\Carbon::parse($due_date);
                $diff = $now->diffInDays($due, false);
                if ($diff < 0) {
                    $status = 'OVERDUE';
                    $days = abs($diff);
                } else {
                    $status = 'DUE';
                    $days = $diff;
                }
            }

            // 6. Exchange rate disable logic
            $exchange_rate_disabled = ($invoice->currency_code === 'INR');

            // 7. Build response
            return response()->json([
                'status' => 'success',
                'data' => [
                    'invoice_id' => $invoice->invoice_id,
                    'company_name' => $invoice->cus_com_name,
                    'contact_person_name' => $invoice->contact_person_name ?: '',
                    'contact_person_email' => $invoice->contact_person_email ?: '',
                    'contact_person_phone' => $invoice->contact_person_phone ?: '',
                    'order_type_currency' => $invoice->currency_code,
                    'invoice_date' => $invoice->invoice_generated_date ? date('m-d-Y', strtotime($invoice->invoice_generated_date)) : null,
                    'due_date' => $due_date ? date('m-d-Y', strtotime($due_date)) : null,
                    'due_status' => $status . ($days !== null ? ': ' . number_format($days, 2) . ' DAYS' : ''),
                    'actual_days_to_pay' => 60, // Default ADP
                    'payment_behaviour' => 'PROMPT PAYER',
                    'invoice_amount' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($invoiceAmount, 2, '.', ','),
                    'amount_received' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($totalPaymentsReceived, 2, '.', ','),
                    'amount_due' => html_entity_decode($invoice->currency_html_code) . ' ' . number_format($remainingAmount, 2, '.', ','),
                    'completed_on' => null,
                    'account_manager' => isset($invoice->admin_fname) ? ucfirst(trim($invoice->admin_fname . ' ' . $invoice->admin_lname)) : 'Unknown',
                    'payment_details' => $payment_details,
                    'tcs_tds_details' => $tcs_tds_details,
                    'remarks' => $remarks,
                    'remarks_payment_remarks' => $remarks_payment_remarks,
                    'exchange_rate_disabled' => $exchange_rate_disabled
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'An error occurred while fetching receivable details (V2).',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getPaymentMethodName($paymentTypeId)
    {
        if (!$paymentTypeId) return '';
        
        $result = DB::table('tbl_payment_type_master')
            ->where('payment_type_id', $paymentTypeId)
            ->where('deleteflag', 'active')
            ->value('payment_type_name');
            
        return $result ?: '';
    }
    private function getBankName($bankId)
    {
        if (!$bankId) return '';
        
        $result = DB::table('tbl_company_bank_address')
            ->where('bank_id', $bankId)
            ->where('deleteflag', 'active')
            ->value('bank_name');
            
        return $result ?: '';
    }

    /**
     * Update a payment detail for an invoice (edit payment row)
     * Request: payment_received_id, invoice_id, payment_received_value, exchange_rate, payment_received_date, payment_method, payment_reference_no, bank_name
     */
    public function updatePaymentDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_received_id' => 'required|integer|min:1',
            'invoice_id' => 'required|integer|min:1',
            'payment_received_value' => 'required|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'payment_received_date' => 'required|date',
            'payment_method' => 'required',
            'payment_reference_no' => 'nullable|string',
            'bank_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $id = $request->input('payment_received_id');
        $invoice_id = $request->input('invoice_id');
        $data = [
            'payment_received_value' => $request->input('payment_received_value'),
            'exchange_rate' => $request->input('exchange_rate'),
            'payment_received_date' => $request->input('payment_received_date'),
            'payment_received_via' => $request->input('payment_method'),
            'transaction_id' => $request->input('payment_reference_no'),
            'payment_received_in_bank' => $request->input('bank_name'),
            'updated_at' => now(),
        ];

        try {
            $updated = DB::table('tbl_payment_received')
                ->where('payment_received_id', $id)
                ->where('invoice_id', $invoice_id)
                ->where('deleteflag', 'active')
                ->update($data);

            if (!$updated) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment record not found or not updated.'
                ], 404);
            }

            // Optionally, return the updated payment row or all payments for the invoice
            $payments = DB::table('tbl_payment_received')
                ->where('invoice_id', $invoice_id)
                ->where('deleteflag', 'active')
                ->orderBy('payment_received_date', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment detail updated successfully.',
                'data' => $payments
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'An error occurred while updating payment detail.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
     * Delete a payment detail for an invoice (soft delete)
     * Request: payment_received_id, invoice_id
     */
    public function deletePaymentDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_received_id' => 'required|integer|min:1',
            'invoice_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $id = $request->input('payment_received_id');
        $invoice_id = $request->input('invoice_id');

        try {
            // Soft delete: set deleteflag to 'inactive'
            $deleted = DB::table('tbl_payment_received')
                ->where('payment_received_id', $id)
                ->where('invoice_id', $invoice_id)
                ->where('deleteflag', 'active')
                ->update(['deleteflag' => 'inactive', 'updated_at' => now()]);

            if (!$deleted) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment record not found or not deleted.'
                ], 404);
            }

            // Optionally, return the updated payment list for the invoice
            $payments = DB::table('tbl_payment_received')
                ->where('invoice_id', $invoice_id)
                ->where('deleteflag', 'active')
                ->orderBy('payment_received_date', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment detail deleted successfully.',
                'data' => $payments
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'An error occurred while deleting payment detail.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
        /**
     * Save payment remarks for an invoice
     * Request: invoice_id, payment_remarks
     */
    public function savePaymentRemark(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|integer|min:1',
            'payment_remarks' => 'required|string',
            'payment_follow_up_date' => 'required|date',    
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $invoice_id = $request->input('invoice_id');
        $remarks = $request->input('payment_remarks');
        $remarks_follow_up_date = $request->input('payment_follow_up_date');
        $payment_promised_on = $request->input('payment_promised_on');
        $admin_id = $request->input('user_id'); 

        try {
            $insert = [
                'invoice_id' => $invoice_id,
                'payment_remarks_only' => $remarks,
                'payment_remarks_follow_up_date' => $remarks_follow_up_date,
                'payment_promised_on' => $payment_promised_on,
                'updated_by' => $admin_id,
                'inserted_date' => now(),
            ];
            DB::table('tbl_payment_remarks')->insert($insert);

            // Return updated remarks list
            $remarks_list = DB::table('tbl_payment_remarks as tpr')
                ->leftJoin('tbl_admin as ta', 'tpr.updated_by', '=', 'ta.admin_id')
                ->where('tpr.invoice_id', $invoice_id)
                ->select(
                    'tpr.payment_remarks_only as remarks',
                    'tpr.payment_remarks_follow_up_date as payment_expected_by',
                    DB::raw("CONCAT(IFNULL(ta.admin_fname, ''), ' ', IFNULL(ta.admin_lname, '')) as updated_by_name"),
                    'tpr.inserted_date as remark_date'
                )
                ->orderByDesc('tpr.inserted_date')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment remark saved successfully.',
                'data' => $remarks_list
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'An error occurred while saving payment remark.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function createPaymentDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|integer|min:1',
            'payment_received_value' => 'required|numeric|min:0',
            'payment_received_date' => 'required|date',
            'payment_received_via' => 'required|string',
            'transaction_id' => 'nullable|string',
            'payment_received_in_bank' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        $data = [
            'invoice_id' => $request->input('invoice_id'),
            'payment_received_value' => $request->input('payment_received_value'),
            'payment_received_date' => $request->input('payment_received_date'),
            'payment_received_via' => $request->input('payment_received_via'),
            'transaction_id' => $request->input('transaction_id'),
            'exchange_rate' => $request->input('exchange_rate'),
            'payment_received_in_bank' => $request->input('payment_received_in_bank'),
            'deleteflag' => 'active',
            'inserted_date' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('tbl_payment_received')->insertGetId($data);

        $payment = DB::table('tbl_payment_received')->where('payment_received_id', $id)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment detail created successfully.',
            'data' => $payment
        ], 201);
    }
    
    public function promisedPaymentRemarkNotifications(Request $request)
    {
        $filter = $request->input('filter', 'today'); // today, week, month
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);
        $countOnly = $request->boolean('count', false);

        $query = DB::table('tbl_payment_remarks as tpr')
            ->join('tbl_tax_invoice as tti', 'tpr.invoice_id', '=', 'tti.invoice_id')
            ->leftJoin('tbl_admin as ta', 'tpr.updated_by', '=', 'ta.admin_id')
            ->select(
                'tpr.invoice_id',
                'tti.cus_com_name as company_name',
                'tti.invoice_generated_date',
                'tti.invoice_id as invoice_number',
                'tti.freight_amount',
                'tti.sub_total_amount_without_gst',
                'tti.total_gst_amount',
                'tti.exchange_rate',
                'tti.invoice_currency',
                'tti.prepared_by',
                'tpr.payment_remarks_only as remarks',
                'tpr.payment_remarks_follow_up_date as follow_up_date',
                'tpr.payment_promised_on as promised_date_of_payment',
                'tpr.inserted_date as remark_date',
                DB::raw("CONCAT(IFNULL(ta.admin_fname, ''), ' ', IFNULL(ta.admin_lname, '')) as account_manager")
            )
            ->where('tpr.deleteflag', 'active');

        // Filter by date
        $now = now();
        if ($filter === 'today') {
            $query->whereDate('tpr.inserted_date', $now->toDateString());
        } elseif ($filter === 'week') {
            $query->whereBetween('tpr.inserted_date', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
        } elseif ($filter === 'month') {
            $query->whereBetween('tpr.inserted_date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
        }

        // Only latest remark per invoice_id
        $sub = DB::table('tbl_payment_remarks')
            ->select(DB::raw('MAX(inserted_date) as max_date'), 'invoice_id')
            ->where('deleteflag', 'active')
            ->groupBy('invoice_id');

        $query->joinSub($sub, 'latest', function ($join) {
            $join->on('tpr.invoice_id', '=', 'latest.invoice_id')
                ->on('tpr.inserted_date', '=', 'latest.max_date');
        });

        // Count only
        if ($countOnly) {
            $total = $query->count();
            return response()->json([
                'status' => 'success',
                'count' => $total,
            ]);
        }

        // Pagination
        $total = $query->count();
        $rows = $query->orderByDesc('tpr.inserted_date')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        // Format for UI
        $data = $rows->map(function ($row) {
            $totalInvoiceAmount = ($row->sub_total_amount_without_gst + $row->total_gst_amount + $row->freight_amount) * $row->exchange_rate;
            $amountReceived = DB::table('tbl_payment_received')
                ->where('invoice_id', $row->invoice_id)
                ->where('deleteflag', 'active')
                ->sum('payment_received_value');
            $amountDue = $totalInvoiceAmount - $amountReceived;
            $overdueDays = now()->diffInDays(\Carbon\Carbon::parse($row->invoice_generated_date), false);

            return [
                'company_name' => $row->company_name,
                'invoice_id' => $row->invoice_id,
                'invoice_date' => date('d M Y', strtotime($row->invoice_generated_date)),
                'invoice_amount' => number_format($totalInvoiceAmount, 2),
                'amount_received' => number_format($amountReceived, 2),
                'amount_due' => number_format($amountDue, 2),
                'overdue_for' => $overdueDays > 0 ? "{$overdueDays} DAYS" : "-",
                'last_follow_up_date' => $row->follow_up_date ? date('d M Y', strtotime($row->follow_up_date)) : '-',
                'promised_date_of_payment' => $row->promised_date_of_payment ? date('d M Y', strtotime($row->promised_date_of_payment)) : '-',
                'account_manager' => $row->account_manager ?: 'Unknown',
                'remarks' => $row->remarks,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_records' => $total,
                'total_pages' => ceil($total / $perPage),
            ]
        ]);
    }

    /**
     * Get total disputed receivables amount from tbl_invoice_disputes
     */
    private function getDisputedReceivablesTotal()
    {
        return DB::table('tbl_invoice_disputes as tid')
            ->join('tbl_tax_invoice as tti', 'tid.invoice_id', '=', 'tti.invoice_id')
            ->where('tti.invoice_id', '>', 230000)
            ->where('tti.invoice_status', 'approved')
            ->where('tti.invoice_closed_status', 'No')
            ->where('tid.deleteflag', 'active')
            ->where('tid.dispute_status', 'active')
            ->sum('tid.disputed_amount'); // Use actual disputed amount, not full invoice amount

    }

}