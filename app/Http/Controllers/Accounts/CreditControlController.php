<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\{
    TaxInvoice,
    ProformaInvoice,
    CreditNoteInvoice,
    PreApprovalCreditRequest
};

class CreditControlController extends Controller
{
    /**
     * Simple test method to verify the controller is working
     */
    public function testController()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'CreditControlController is working!',
            'timestamp' => now(),
            'controller' => self::class
        ], 200);
    }

    /**
     * Get current financial year in format 2025-2026
     */
    private function getCurrentFinancialYear()
    {
        $currentMonth = date('n'); // 1-12
        $currentYear = date('Y');
        
        // Financial year starts from April (month 4)
        if ($currentMonth >= 4) {
            // April to March next year
            $startYear = $currentYear;
            $endYear = $currentYear + 1;
        } else {
            // January to March of current year
            $startYear = $currentYear - 1;
            $endYear = $currentYear;
        }
        
        return $startYear . '-' . $endYear;
    }

    /**
     * Get financial year date range for SQL queries
     */
    private function getFinancialYearDateRange($financialYear)
    {
        if (empty($financialYear)) {
            $financialYear = $this->getCurrentFinancialYear();
        }
        
        // Parse financial year (e.g., "2025-2026")
        $years = explode('-', $financialYear);
        if (count($years) !== 2) {
            // Fallback to current FY if invalid format
            $financialYear = $this->getCurrentFinancialYear();
            $years = explode('-', $financialYear);
        }
        
        $startYear = $years[0];
        $endYear = $years[1];
        
        return [
            'start_date' => $startYear . '-04-01', // April 1st
            'end_date' => $endYear . '-03-31',     // March 31st
            'financial_year' => $financialYear
        ];
    }

    /**
     * Get Proforma Invoices listing with filters
     */
    public function getProformaInvoicesList(Request $request)
    {
        try {
            // Get parameters with defaults using pageno and records
            $pageno = $request->get('pageno', 1);
            $records = min($request->get('records', 20), 100);
            $sortkey = $request->get('sortkey', 'created_at');
            $sortvalue = $request->get('sortvalue', 'desc');
            
            // Filters
            $searchBy = $request->get('searchBy', ''); // DO or Company Name
            $companyName = $request->get('companyName', '');
            $accountManager = $request->get('accountManager', '');
            $productName = $request->get('productName', '');
            $invoiceType = $request->get('invoiceType', '');
            $irnStatus = $request->get('irnStatus', '');
            $status = $request->get('status', '');
            $dateFrom = $request->get('dateFrom', '');
            $dateTo = $request->get('dateTo', '');
            $invoiceNumber = $request->get('invoiceNumber', '');
            
            // New filters
            $financialYear = $request->get('financial_year', '');
            $compId = $request->get('comp_id', '');
            $accManager = $request->get('acc_manager', '');

            // Get financial year date range
            $fyDateRange = $this->getFinancialYearDateRange($financialYear);

            // Build base query using exact legacy logic from view_pi_stat.php
            $searchrecord = '';
            
            // Financial year filter (always applied)
            $searchrecord .= " AND pi.pi_generated_date >= '" . $fyDateRange['start_date'] . "' AND pi.pi_generated_date <= '" . $fyDateRange['end_date'] . "'";
            
            // Company ID filter
            if (!empty($compId)) {
                $searchrecord .= " AND pi.comp_id = " . intval($compId);
            }
            
            // Account Manager filter by ID
            if (!empty($accManager)) {
                $searchrecord .= " AND pi.Prepared_by = " . intval($accManager);
            }
            
            if (!empty($searchBy)) {
                if (is_numeric($searchBy)) {
                    $searchrecord .= " AND pi.pi_id = " . intval($searchBy);
                } else {
                    $searchrecord .= " AND pi.PO_NO LIKE '%" . addslashes($searchBy) . "%'";
                }
            }

            // Account manager filter from legacy: $search_acc_manager = "and Prepared_by = '$acc_manager'";
            if (!empty($accountManager) && $accountManager != '---All User---') {
                $searchrecord .= " AND pi.Prepared_by = '" . addslashes($accountManager) . "'";
            }

            if (!empty($status) && $status != 'All') {
                $searchrecord .= " AND pi.pi_status = '" . addslashes($status) . "'";
            }

            if (!empty($dateFrom)) {
                $searchrecord .= " AND pi.pi_generated_date >= '" . addslashes($dateFrom) . "'";
            }

            if (!empty($dateTo)) {
                $searchrecord .= " AND pi.pi_generated_date <= '" . addslashes($dateTo) . "'";
            }

            if (!empty($invoiceNumber)) {
                $searchrecord .= " AND pi.PO_NO LIKE '%" . addslashes($invoiceNumber) . "%'";
            }

            // Order ID filter from legacy: $search_order_id = "and O_Id = '$order_id'";
            $orderId = $request->get('orderId', '');
            if (!empty($orderId)) {
                $searchrecord .= " AND pi.O_Id = '" . addslashes($orderId) . "'";
            }

            // Count query
            $count_sql = "SELECT COUNT(*) as total FROM tbl_performa_invoice pi WHERE pi.deleteflag = 'active' AND pi.save_send = 'yes' $searchrecord";
            $total_result = DB::select($count_sql);
            $totalItems = $total_result[0]->total ?? 0;
            $totalPages = ceil($totalItems / $records);

            // Main query using exact legacy columns from view_pi_stat.php
            $offset = ($pageno - 1) * $records;
            $sortDirection = $sortvalue === 'asc' ? 'ASC' : 'DESC';
            
            // Legacy: $star = "pi_id, O_Id, PO_NO, pi_generated_date,Prepared_by,pi_status,Cus_Com_Name,PO_path";
            $sql = "SELECT pi.pi_id, pi.O_Id, pi.PO_NO, pi.pi_generated_date, pi.Prepared_by, pi.pi_status, pi.Cus_Com_Name, pi.PO_path
                    FROM tbl_performa_invoice pi 
                    WHERE pi.deleteflag = 'active' AND pi.save_send = 'yes' $searchrecord
                    ORDER BY pi.pi_generated_date $sortDirection
                    LIMIT $offset, $records";

            $results = DB::select($sql);

            // Format results exactly like legacy view_pi_stat.php system
            $formattedResults = collect($results)->map(function ($item) {
                // Get payment status like legacy system does
                $paymentStatus = $this->getPaymentStatusFromLegacy($item->O_Id);
                
                return [
                    'pi_id' => $item->pi_id,
                    'offer_id' => $item->O_Id,
                    'po_no' => $item->PO_NO,
                    'date_time' => date('d/m/Y H:i:s', strtotime($item->pi_generated_date)),
                    'customer_name' => $item->Cus_Com_Name,
                    'value' => 'N/A', // Legacy doesn't show amount directly in listing
                    'account_manager' => function_exists('admin_name') ? admin_name($item->Prepared_by) : $item->Prepared_by,
                    'account_manager_id' => $item->Prepared_by,
                    'status' => match($item->pi_status) {
                        'approved' => 'Approved',
                        'pending' => 'Pending',
                        default => ucfirst($item->pi_status ?? 'Unknown')
                    },
                    'payment_status' => $paymentStatus['status'],
                    'advance_received' => $paymentStatus['amount'],
                    'payment_details' => $paymentStatus['details'],
                    'po_path' => $item->PO_path
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'proforma_invoices' => $formattedResults,
                    'pagination' => [
                        'pageno' => $pageno,
                        'records' => $records,
                        'totalItems' => $totalItems,
                        'totalPages' => $totalPages
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching proforma invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment status for an order ID like legacy system does
     * Based on legacy view_pi_stat.php payment checking logic
     */
    private function getPaymentStatusFromLegacy($orderId)
    {
        try {
            // Check for full payment like legacy system
            $fullPaymentQuery = "SELECT * FROM tbl_payment_received WHERE o_id = ?";
            $fullPayments = DB::select($fullPaymentQuery, [$orderId]);
            
            if (count($fullPayments) > 0) {
                $totalReceived = array_sum(array_column($fullPayments, 'payment_received_value'));
                $latestPayment = collect($fullPayments)->sortByDesc('payment_received_date')->first();
                
                return [
                    'status' => 'Full Payment received',
                    'amount' => number_format($totalReceived, 2),
                    'details' => [
                        'date' => $latestPayment->payment_received_date ?? '',
                        'via' => $latestPayment->payment_received_via ?? '',
                        'bank' => $latestPayment->payment_received_in_bank ?? ''
                    ]
                ];
            }
            
            return [
                'status' => 'Payment Pending',
                'amount' => '0.00',
                'details' => [
                    'date' => '',
                    'via' => '',
                    'bank' => ''
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'Payment Status Unknown',
                'amount' => '0.00',
                'details' => [
                    'date' => '',
                    'via' => '',
                    'bank' => ''
                ]
            ];
        }
    }

    /**
     * Get Invoices listing - using exact legacy logic from view_invoices.php
     */
    public function getInvoicesList(Request $request)
    {
        try {
            // Get request parameters using pageno and records
            $pageno = $request->get('pageno', 1);
            $records = min($request->get('records', 20), 100);
            $sortkey = $request->get('sortkey', 'tti.invoice_generated_date');
            $sortvalue = $request->get('sortvalue', 'desc');
            
            // Filters based on original legacy logic
            $acc_manager = $request->get('accountManager', '');
            $datevalid_from = $request->get('dateFrom', '');
            $datevalid_to = $request->get('dateTo', '');
            $invoice_status = $request->get('status', '');
            $searchBy = $request->get('searchBy', '');
            $invoiceNumber = $request->get('invoiceNumber', '');
            
            // New filters
            $financialYear = $request->get('financial_year', '');
            $compId = $request->get('comp_id', '');
            $accManager = $request->get('acc_manager', '');

            // Get financial year date range
            $fyDateRange = $this->getFinancialYearDateRange($financialYear);

            // Build search conditions exactly like original
            $searchrecord = '';
            
            // Financial year filter (always applied)
            $searchrecord .= " AND tti.invoice_generated_date >= '" . $fyDateRange['start_date'] . "' AND tti.invoice_generated_date <= '" . $fyDateRange['end_date'] . "'";
            
            // Company ID filter - use customers_id from tbl_order join
            if (!empty($compId)) {
                $searchrecord .= " AND to1.customers_id = " . intval($compId);
            }
            
            // Account Manager filter by ID (new acc_manager parameter)
            if (!empty($accManager)) {
                $searchrecord .= " AND tti.prepared_by = " . intval($accManager);
            }
            
            if (!empty($acc_manager) && $acc_manager != '---All User---') {
                $searchrecord .= " AND tti.prepared_by = " . intval($acc_manager);
            }
            
            if (!empty($datevalid_from) && !empty($datevalid_to)) {
                $searchrecord .= " AND (date(tti.invoice_generated_date) BETWEEN '" . addslashes($datevalid_from) . "' AND '" . addslashes($datevalid_to) . "')";
            }
            
            if (!empty($invoice_status) && $invoice_status != 'All') {
                $searchrecord .= " AND tti.invoice_status = '" . addslashes($invoice_status) . "'";
            }

            if (!empty($searchBy)) {
                if (is_numeric($searchBy)) {
                    $searchrecord .= " AND tti.invoice_id = " . intval($searchBy);
                } else {
                    $searchrecord .= " AND tti.cus_com_name LIKE '%" . addslashes($searchBy) . "%'";
                }
            }

            if (!empty($invoiceNumber)) {
                $searchrecord .= " AND tti.po_no LIKE '%" . addslashes($invoiceNumber) . "%'";
            }

            // Base query exactly like original file view_invoices.php with tbl_order join
            $sql = "SELECT tti.invoice_id, tti.o_id, tti.po_no, tti.po_due_date, tti.po_date, tti.invoice_generated_date, tti.po_from, tti.cus_com_name, tti.con_name, tti.prepared_by, tti.invoice_status, tti.payment_terms, tti.invoice_type, tti.deleteflag, tti.con_cust_co_name, tti.eway_bill_no, tti.terms_of_delivery, tti.freight_amount, tti.freight_gst_amount, tti.total_gst_amount, tti.sub_total_amount_without_gst, tti.gst_sale_type, tti.invoice_currency, tti.invoice_approval_status, tti.exchange_rate, tip.pro_id, tip.pro_name, tirn.ackno, tirn.ackdt, tirn.irn, tirn.signedinvoice, tirn.signedqrcode, tirn.ewbno, tirn.ewbdt, tirn.ewbvalidtill, tirn.qrcodeurl, tirn.response_msg_status, tirn.alert, tirn.requestid, tirn.irn_status 
            FROM tbl_tax_invoice tti 
            INNER JOIN tbl_order_product tip ON tti.o_id=tip.order_id 
            LEFT JOIN tbl_tax_invoice_gst_irn_response tirn ON tti.invoice_id=tirn.invoice_id 
            LEFT JOIN tbl_order to1 ON tti.o_id=to1.orders_id
            WHERE tti.deleteflag = 'active' AND tti.invoice_id > 230000
            $searchrecord
            ORDER BY $sortkey $sortvalue 
            LIMIT " . (($pageno - 1) * $records) . ", $records";

            $results = DB::select($sql);

            // Get total count
            $count_sql = "SELECT COUNT(*) as total 
            FROM tbl_tax_invoice tti 
            INNER JOIN tbl_order_product tip ON tti.o_id=tip.order_id 
            LEFT JOIN tbl_tax_invoice_gst_irn_response tirn ON tti.invoice_id=tirn.invoice_id 
            LEFT JOIN tbl_order to1 ON tti.o_id=to1.orders_id 
            WHERE tti.deleteflag = 'active' AND tti.invoice_id > 230000
            $searchrecord";
            
            $total_result = DB::select($count_sql);
            $totalItems = $total_result[0]->total ?? 0;
            $totalPages = ceil($totalItems / $records);

            // Format results using the same helper functions as original
            $formattedResults = collect($results)->map(function ($row) {
                return [
                    'invoice_id' => $row->invoice_id,
                    'invoice_no' => $row->po_no,
                    'date' => $row->invoice_generated_date,
                    'customer_name' => ucfirst($row->cus_com_name),
                    'products' => $row->pro_name ?? 'N/A',
                    'invoice_amount' => number_format(($row->sub_total_amount_without_gst ?? 0) + ($row->total_gst_amount ?? 0) + ($row->freight_amount ?? 0), 2),
                    'account_manager' => function_exists('admin_name') ? admin_name($row->prepared_by) : 'Unknown',
                    'account_manager_id' => $row->prepared_by,
                    'irn_status' => $row->irn_status ?: 'N/A',
                    'status' => ucfirst($row->invoice_status ?? 'Unknown'),
                    'exchange_rate' => $row->exchange_rate ?? 1
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'invoices' => $formattedResults,
                    'pagination' => [
                        'pageno' => $pageno,
                        'records' => $records,
                        'totalItems' => $totalItems,
                        'totalPages' => $totalPages
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching invoices: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Get Credit Note Invoices listing with filters
     * Using exact legacy SQL from view_credit_note_invoices.php
     */
    public function getCreditNoteInvoicesList(Request $request)
    {
        try {
            // Get parameters with defaults using pageno and records
            $pageno = $request->get('pageno', 1);
            $records = min($request->get('records', 20), 100);
            $sortkey = $request->get('sortkey', 'ttcni.credit_invoice_generated_date');
            $sortvalue = $request->get('sortvalue', 'desc');
            
            // Filters from legacy system
            $searchBy = $request->get('searchBy', '');
            $companyName = $request->get('companyName', '');
            $accountManager = $request->get('accountManager', '');
            $productName = $request->get('productName', '');
            $invoiceType = $request->get('invoiceType', '');
            $irnStatus = $request->get('irnStatus', '');
            $dateFrom = $request->get('dateFrom', '');
            $dateTo = $request->get('dateTo', '');
            
            // New filters
            $financialYear = $request->get('financial_year', '');
            $compId = $request->get('comp_id', '');
            $accManager = $request->get('acc_manager', '');

            // Get financial year date range
            $fyDateRange = $this->getFinancialYearDateRange($financialYear);
            $invoiceId = $request->get('invoiceId', '');
            $gstSaleType = $request->get('gstSaleType', '');
            $doSearch = $request->get('doSearch', '');

            // Build WHERE clause exactly like legacy system
            $whereClause = " WHERE ttcni.deleteflag = 'active' ";
            $params = [];
            
            // Financial year filter (always applied)
            $whereClause .= " AND ttcni.credit_invoice_generated_date >= ? AND ttcni.credit_invoice_generated_date <= ? ";
            $params[] = $fyDateRange['start_date'];
            $params[] = $fyDateRange['end_date'];
            
            // Company ID filter - use customers_id from tbl_order join
            if (!empty($compId)) {
                $whereClause .= " AND to1.customers_id = ? ";
                $params[] = intval($compId);
            }
            
            // Account Manager filter by ID (new acc_manager parameter)
            if (!empty($accManager)) {
                $whereClause .= " AND ttcni.prepared_by = ? ";
                $params[] = intval($accManager);
            }

            // Company name search (legacy: $cus_com_name_search)
            if (!empty($companyName)) {
                $whereClause .= " AND ttcni.cus_com_name LIKE ? ";
                $params[] = "%{$companyName}%";
            }

            // Account manager filter (legacy: $acc_manager)
            if (!empty($accountManager) && $accountManager != '0' && $accountManager != '---All User---') {
                $whereClause .= " AND ttcni.prepared_by = ? ";
                $params[] = $accountManager;
            }

            // Product name search (legacy: $pro_name_search)
            if (!empty($productName)) {
                $whereClause .= " AND tcnip.pro_description LIKE ? ";
                $params[] = "%{$productName}%";
            }

            // IRN Status filter (legacy: $irn_status_search)
            if (!empty($irnStatus) && $irnStatus != '0' && $irnStatus != 'All') {
                if ($irnStatus == 'ACT') {
                    $whereClause .= " AND tcnirn.irn_status = 'ACT' ";
                } elseif ($irnStatus == 'N/A') {
                    $whereClause .= " AND (tcnirn.irn_status IS NULL OR tcnirn.irn_status = '') ";
                } else {
                    $whereClause .= " AND tcnirn.irn_status = ? ";
                    $params[] = $irnStatus;
                }
            }

            // GST Sale Type filter (legacy: $gst_sale_type_search)
            if (!empty($gstSaleType) && $gstSaleType != '0') {
                $whereClause .= " AND ttcni.gst_sale_type = ? ";
                $params[] = $gstSaleType;
            }

            // Date range filter (legacy: $date_range_search)
            if (!empty($dateFrom) && !empty($dateTo)) {
                $whereClause .= " AND (DATE(ttcni.credit_invoice_generated_date) BETWEEN ? AND ?) ";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            } elseif (!empty($dateFrom)) {
                $whereClause .= " AND DATE(ttcni.credit_invoice_generated_date) >= ? ";
                $params[] = $dateFrom;
            } elseif (!empty($dateTo)) {
                $whereClause .= " AND DATE(ttcni.credit_invoice_generated_date) <= ? ";
                $params[] = $dateTo;
            }

            // Invoice ID search
            if (!empty($invoiceId)) {
                $whereClause .= " AND ttcni.credit_note_invoice_id LIKE ? ";
                $params[] = "%{$invoiceId}%";
            }

            // General search (legacy: $searchrecord logic)
            if (!empty($searchBy)) {
                if (is_numeric($searchBy)) {
                    $whereClause .= " AND (ttcni.credit_note_invoice_id = ? OR ttcni.o_id = ? OR ttcni.po_no LIKE ?) ";
                    $params[] = $searchBy;
                    $params[] = $searchBy;
                    $params[] = "%{$searchBy}%";
                } else {
                    $whereClause .= " AND (ttcni.cus_com_name LIKE ? OR ttcni.po_no LIKE ?) ";
                    $params[] = "%{$searchBy}%";
                    $params[] = "%{$searchBy}%";
                }
            }

            // Count query using exact legacy structure with tbl_order join
            $countSql = "SELECT COUNT(ttcni.credit_note_invoice_id) as total
                FROM tbl_tax_credit_note_invoice ttcni 
                RIGHT JOIN tbl_credit_note_invoice_products tcnip ON ttcni.o_id=tcnip.order_id 
                LEFT JOIN tbl_tax_credit_note_invoice_gst_irn_response tcnirn ON ttcni.o_id=tcnirn.o_id 
                LEFT JOIN tbl_order to1 ON ttcni.o_id=to1.orders_id
                $whereClause";

            $totalItems = DB::select($countSql, $params)[0]->total ?? 0;
            $totalPages = ceil($totalItems / $records);

            // Main query using exact legacy SQL from view_credit_note_invoices.php
            $orderBy = match($sortkey) {
                'date' => 'ttcni.credit_invoice_generated_date',
                'credit_note_id' => 'ttcni.credit_note_invoice_id',
                'uid' => 'ttcni.credit_note_invoice_id',
                'invoice_amount' => '(ttcni.sub_total_amount_without_gst + ttcni.freight_amount + ttcni.total_gst_amount + ttcni.freight_gst_amount)',
                default => 'ttcni.credit_invoice_generated_date'
            };
            
            $sortDirection = strtoupper($sortvalue === 'asc' ? 'ASC' : 'DESC');
            $offset = ($pageno - 1) * $records;

            // Exact SQL from legacy view_credit_note_invoices.php with tbl_order join
            $sql = "SELECT ttcni.credit_note_invoice_id, ttcni.credit_invoice_generated_date, ttcni.invoice_id, ttcni.o_id, ttcni.po_no, ttcni.po_due_date, ttcni.po_date, ttcni.invoice_generated_date, ttcni.po_from, ttcni.cus_com_name, ttcni.con_name, ttcni.con_address, ttcni.con_country, ttcni.con_state, ttcni.con_city, ttcni.con_mobile, ttcni.con_email, ttcni.con_gst, ttcni.buyer_name, ttcni.buyer_address, ttcni.buyer_country, ttcni.buyer_state, ttcni.buyer_city, ttcni.buyer_mobile, ttcni.buyer_email, ttcni.prepared_by, ttcni.invoice_status, ttcni.branch_sel, ttcni.bank_sel, ttcni.payment_terms, ttcni.invoice_type, ttcni.deleteflag, ttcni.con_cust_co_name, ttcni.con_pincode, ttcni.buyer_gst, ttcni.buyer_pin_code, ttcni.eway_bill_no, ttcni.delivery_note, ttcni.ref_no_and_date, ttcni.offer_ref, ttcni.dispatch_doc_no, ttcni.delivery_note_date, ttcni.Delivery, ttcni.destination, ttcni.terms_of_delivery, ttcni.freight_amount, ttcni.freight_gst_amount, ttcni.total_gst_amount, ttcni.sub_total_amount_without_gst, ttcni.gst_sale_type, ttcni.invoice_currency, ttcni.invoice_approval_status, ttcni.rental_start_date, ttcni.rental_end_date, tcnip.order_id, tcnip.model_no, tcnip.pro_id, tcnip.pro_description, tcnip.quantity, tcnip.price, tcnip.service_period, tcnirn.ackno, tcnirn.ackdt, tcnirn.irn, tcnirn.signedinvoice, tcnirn.signedqrcode, tcnirn.ewbno, tcnirn.ewbdt, tcnirn.ewbvalidtill, tcnirn.qrcodeurl, tcnirn.response_msg_status, tcnirn.alert, tcnirn.requestid, tcnirn.irn_status 
                FROM tbl_tax_credit_note_invoice ttcni 
                RIGHT JOIN tbl_credit_note_invoice_products tcnip ON ttcni.o_id=tcnip.order_id 
                LEFT JOIN tbl_tax_credit_note_invoice_gst_irn_response tcnirn ON ttcni.o_id=tcnirn.o_id 
                LEFT JOIN tbl_order to1 ON ttcni.o_id=to1.orders_id
                $whereClause
                ORDER BY $orderBy $sortDirection
                LIMIT $offset, $records";

            $results = DB::select($sql, $params);

            // Format results exactly like legacy system
            $formattedResults = [];
            foreach ($results as $row) {
                // Calculate total amount like legacy: sub_total + freight + gst + freight_gst
                $totalAmount = ($row->sub_total_amount_without_gst ?? 0) + 
                              ($row->freight_amount ?? 0) + 
                              ($row->total_gst_amount ?? 0) + 
                              ($row->freight_gst_amount ?? 0);
                
                $formattedResults[] = [
                    'invoice_id' => $row->invoice_id,
                    'credit_note_id' => $row->credit_note_invoice_id,
                    'credit_note_no' => $row->credit_note_invoice_id,
                    'date' => date('d/m/Y', strtotime($row->credit_invoice_generated_date)),
                    'uid' => $row->o_id,
                    'offer_code' => $row->o_id, // Legacy shows offer code format
                    'customer_name' => $row->cus_com_name,
                    'products' => $row->pro_description ?? 'N/A',
                    'invoice_amount' => number_format($totalAmount, 2),
                    'sub_total_amount_without_gst' => number_format($row->sub_total_amount_without_gst ?? 0, 2),
                    'freight_amount' => number_format($row->freight_amount ?? 0, 2),
                    'total_gst_amount' => number_format($row->total_gst_amount ?? 0, 2),
                    'freight_gst_amount' => number_format($row->freight_gst_amount ?? 0, 2),
                    'account_manager' => function_exists('admin_name') ? admin_name($row->prepared_by) : $row->prepared_by,
                    'account_manager_id' => $row->prepared_by,
                    'irn_status' => $row->irn_status ?? 'N/A',
                    'irn' => $row->irn ?? '',
                    'status' => match($row->invoice_status) {
                        'approved' => 'Approved',
                        'pending' => 'Pending',
                        default => ucfirst($row->invoice_status ?? 'Unknown')
                    },
                    'po_no' => $row->po_no,
                    'invoice_type' => $row->invoice_type,
                    'gst_sale_type' => $row->gst_sale_type
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'credit_notes' => $formattedResults,
                    'pagination' => [
                        'pageno' => $pageno,
                        'records' => $records,
                        'totalItems' => $totalItems,
                        'totalPages' => $totalPages
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching credit note invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Credit Control Approvals listing - Updated to match UI structure exactly
     */
    public function getCreditControlApprovals(Request $request)
    {
        try {
            // Get parameters using pageno and records
            $pageno = $request->get('pageno', 1);
            $records = min($request->get('records', 20), 100);
            $status = $request->get('status', 'all'); // all, pending, approved, rejected, expired
            $approvalFor = $request->get('approvalFor', '');
            
            // New filters
            $financialYear = $request->get('financial_year', '');
            $compId = $request->get('comp_id', '');
            $accManager = $request->get('acc_manager', '');

            // Get financial year date range
            $fyDateRange = $this->getFinancialYearDateRange($financialYear);
            
            // Mock data that matches your UI structure exactly (filtered by financial year)
            $allApprovals = [
                [
                    'id' => '361145',
                    'requested_on' => '18 Jul 2024',
                    'financial_year' => $fyDateRange['financial_year'],
                    'approval_for' => 'Credit (Pre approval)',
                    'customer_name' => 'Kolkata Municipal Corporation',
                    'account_manager' => 'Rahul Sharma',
                    'account_manager_id' => 99,
                    'approval_amount' => '₹18,00,000',
                    'document_ref' => '-',
                    'status' => 'PENDING',
                    'status_color' => 'warning',
                    'request_remarks' => 'This is just dummy text lorem ipsum is thethe dummy text.',
                    'details' => [
                        'current_status' => 'Approved',
                        'requested_payment_term' => '100%',
                        'credit_value' => '₹18,00,000',
                        'add_remarks' => ''
                    ],
                    'actions' => [
                        'can_approve' => true,
                        'can_reject' => true,
                        'can_view_company' => true,
                        'can_view_offer' => true
                    ]
                ],
                [
                    'id' => '361146',
                    'requested_on' => '18 Jul 2024',
                    'approval_for' => 'Credit note',
                    'customer_name' => 'Kolkata Municipal Corporation',
                    'account_manager' => 'Rahul Sharma',
                    'account_manager_id' => 99,
                    'approval_amount' => '₹18,00,000',
                    'document_ref' => '-',
                    'status' => 'PENDING',
                    'status_color' => 'warning',
                    'request_remarks' => 'Credit note approval required for refund processing.',
                    'details' => [
                        'current_status' => 'Pending',
                        'requested_payment_term' => '100%',
                        'credit_value' => '₹18,00,000',
                        'add_remarks' => ''
                    ],
                    'actions' => [
                        'can_approve' => true,
                        'can_reject' => true,
                        'can_view_company' => true,
                        'can_view_credit_note' => true
                    ]
                ],
                [
                    'id' => '361147',
                    'requested_on' => '18 Jul 2024',
                    'approval_for' => 'Credit',
                    'customer_name' => 'Southern Railway',
                    'account_manager' => 'Rahul Sharma',
                    'account_manager_id' => 99,
                    'approval_amount' => '₹18,00,000',
                    'document_ref' => '-',
                    'status' => 'DOCS_REQUIRED',
                    'status_color' => 'info',
                    'request_remarks' => 'Additional documentation required for credit approval.',
                    'details' => [
                        'current_status' => 'Documents Required',
                        'requested_payment_term' => '100%',
                        'credit_value' => '₹18,00,000',
                        'add_remarks' => ''
                    ],
                    'actions' => [
                        'can_approve' => false,
                        'can_reject' => true,
                        'can_request_docs' => true,
                        'can_view_company' => true
                    ]
                ],
                [
                    'id' => '361148',
                    'requested_on' => '18 Jul 2024',
                    'approval_for' => 'Proforma invoice',
                    'customer_name' => 'L&T Technology Services Ltd',
                    'account_manager' => 'Rahul Sharma',
                    'account_manager_id' => 99,
                    'approval_amount' => '₹18,00,000',
                    'document_ref' => '-',
                    'status' => 'APPROVED',
                    'status_color' => 'success',
                    'approved_on' => '18 Jul 2024',
                    'request_remarks' => 'Proforma invoice approved for immediate processing.',
                    'details' => [
                        'current_status' => 'Approved',
                        'requested_payment_term' => '100%',
                        'credit_value' => '₹18,00,000',
                        'add_remarks' => 'Approved based on company credit history'
                    ],
                    'actions' => [
                        'can_approve' => false,
                        'can_reject' => false,
                        'can_view_company' => true,
                        'can_view_invoice' => true
                    ]
                ],
                [
                    'id' => '361149',
                    'requested_on' => '18 Jul 2024',
                    'approval_for' => 'Invoice',
                    'customer_name' => 'Municipal Corporation S.A.S. Nagar Company',
                    'account_manager' => 'Rahul Sharma',
                    'account_manager_id' => 99,
                    'approval_amount' => '₹18,00,000',
                    'document_ref' => '-',
                    'status' => 'REJECTED',
                    'status_color' => 'danger',
                    'rejected_on' => '18 Jul 2024',
                    'request_remarks' => 'Invoice rejected due to insufficient documentation.',
                    'details' => [
                        'current_status' => 'Rejected',
                        'requested_payment_term' => '100%',
                        'credit_value' => '₹18,00,000',
                        'add_remarks' => 'Insufficient credit documentation provided'
                    ],
                    'actions' => [
                        'can_approve' => false,
                        'can_reject' => false,
                        'can_view_company' => true,
                        'can_resubmit' => true
                    ]
                ]
            ];

            // Filter by status
            $filteredApprovals = $allApprovals;
            if ($status !== 'all' && $status !== '') {
                $filteredApprovals = array_filter($filteredApprovals, function($approval) use ($status) {
                    return strtolower($approval['status']) === strtolower($status);
                });
            }

            // Filter by approval type
            if (!empty($approvalFor)) {
                $filteredApprovals = array_filter($filteredApprovals, function($approval) use ($approvalFor) {
                    return stripos($approval['approval_for'], $approvalFor) !== false;
                });
            }
            
            // Filter by company ID (mock implementation)
            if (!empty($compId)) {
                // In real implementation, this would filter by actual company ID
                // For now, we'll keep all records as they're mock data
            }
            
            // Filter by account manager - use exact ID matching
            if (!empty($accManager)) {
                $filteredApprovals = array_filter($filteredApprovals, function($approval) use ($accManager) {
                    return $approval['account_manager_id'] == intval($accManager);
                });
            }

            // Paginate results
            $totalItems = count($filteredApprovals);
            $totalPages = ceil($totalItems / $records);
            $offset = ($pageno - 1) * $records;
            $paginatedApprovals = array_slice(array_values($filteredApprovals), $offset, $records);

            // Calculate statistics that match your UI exactly
            $stats = [
                'pending' => count(array_filter($allApprovals, fn($a) => $a['status'] === 'PENDING')),
                'approved' => count(array_filter($allApprovals, fn($a) => $a['status'] === 'APPROVED')),
                'rejected' => count(array_filter($allApprovals, fn($a) => $a['status'] === 'REJECTED')),
                'expired_revoked' => count(array_filter($allApprovals, fn($a) => in_array($a['status'], ['EXPIRED', 'REVOKED'])))
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'approvals' => $paginatedApprovals,
                    'statistics' => $stats,
                    'pagination' => [
                        'pageno' => $pageno,
                        'records' => $records,
                        'totalItems' => $totalItems,
                        'totalPages' => $totalPages
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching credit control approvals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter options for all modules
     */
    public function getCreditControlFilterOptions(Request $request)
    {
        try {
            // Get account managers (admins) - using raw SQL to match legacy structure
            $accountManagers = DB::select("SELECT admin_id, admin_fname, admin_lname FROM tbl_admin WHERE deleteflag = 'active' ORDER BY admin_fname ASC");
            $accountManagers = collect($accountManagers)->map(function ($item) {
                return [
                    'id' => $item->admin_id,
                    'name' => trim($item->admin_fname . ' ' . $item->admin_lname)
                ];
            });

            // Get companies - try different possible column names
            try {
                $companies = DB::select("SELECT comp_id, comp_name FROM tbl_comp WHERE deleteflag = 'active' ORDER BY comp_name ASC");
            } catch (\Exception $e1) {
                try {
                    // Try with id instead of comp_id
                    $companies = DB::select("SELECT id, comp_name FROM tbl_comp WHERE deleteflag = 'active' ORDER BY comp_name ASC");
                } catch (\Exception $e2) {
                    try {
                        // Try with company_id instead of comp_id
                        $companies = DB::select("SELECT company_id, comp_name FROM tbl_comp WHERE deleteflag = 'active' ORDER BY comp_name ASC");
                    } catch (\Exception $e3) {
                        try {
                            // Try with different name column
                            $companies = DB::select("SELECT comp_id, company_name FROM tbl_comp WHERE deleteflag = 'active' ORDER BY company_name ASC");
                        } catch (\Exception $e4) {
                            // Last attempt - get all columns to see what's available
                            $companies = DB::select("SELECT * FROM tbl_comp WHERE deleteflag = 'active' ORDER BY comp_name ASC LIMIT 1");
                            if (empty($companies)) {
                                $companies = [];
                            } else {
                                // Use first available columns that look like ID and name
                                $sample = $companies[0];
                                $columns = get_object_vars($sample);
                                $idColumn = null;
                                $nameColumn = null;
                                
                                foreach ($columns as $col => $val) {
                                    if (stripos($col, 'id') !== false && !$idColumn) {
                                        $idColumn = $col;
                                    }
                                    if (stripos($col, 'name') !== false && !$nameColumn) {
                                        $nameColumn = $col;
                                    }
                                }
                                
                                if ($idColumn && $nameColumn) {
                                    $companies = DB::select("SELECT $idColumn, $nameColumn FROM tbl_comp WHERE deleteflag = 'active' ORDER BY $nameColumn ASC");
                                } else {
                                    $companies = [];
                                }
                            }
                        }
                    }
                }
            }
            
            $companies = collect($companies)->map(function ($item) {
                $itemArray = get_object_vars($item);
                $id = null;
                $name = null;
                
                // Find ID and name fields dynamically
                foreach ($itemArray as $key => $value) {
                    if (stripos($key, 'id') !== false && !$id) {
                        $id = $value;
                    }
                    if (stripos($key, 'name') !== false && !$name) {
                        $name = $value;
                    }
                }
                
                return [
                    'id' => $id ?: array_values($itemArray)[0],
                    'name' => $name ?: array_values($itemArray)[1] ?? 'Unknown'
                ];
            });

            // Status options for different modules
            $statusOptions = [
                'proforma_invoice' => ['pending', 'approved', 'rejected'],
                'invoice' => ['pending', 'approved', 'rejected'],
                'credit_note' => ['pending', 'approved', 'rejected'],
                'credit_approval' => ['pending', 'approved', 'rejected', 'expired', 'revoked']
            ];

            // IRN Status options
            $irnStatusOptions = ['ACT', 'N/A'];

            // Invoice types
            $invoiceTypes = ['Credit', 'Invoice', 'Proforma Invoice', 'Credit note'];

            // Financial years (last 5 years)
            $currentYear = date('Y');
            $financialYears = [];
            for ($i = 0; $i < 5; $i++) {
                $startYear = $currentYear - $i;
                $endYear = $startYear + 1;
                $financialYears[] = "{$startYear}-{$endYear}";
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'account_managers' => $accountManagers,
                    'companies' => $companies,
                    'status_options' => $statusOptions,
                    'irn_status_options' => $irnStatusOptions,
                    'invoice_types' => $invoiceTypes,
                    'financial_years' => $financialYears
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching filter options: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get credit control dashboard summary
     */
    public function getCreditControlDashboard(Request $request)
    {
        try {
            $financialYear = $request->get('financialYear', '2024-2025');
            
            // Parse financial year
            $fyParts = explode('-', $financialYear);
            $startDate = "{$fyParts[0]}-04-01";
            $endDate = "{$fyParts[1]}-03-31";

            // Proforma Invoice stats
            $piStats = [
                'total' => DB::table('tbl_performa_invoice')->where('deleteflag', 'active')->whereBetween('pi_generated_date', [$startDate, $endDate])->count(),
                'pending' => DB::table('tbl_performa_invoice')->where('deleteflag', 'active')->where('pi_status', 'pending')->whereBetween('pi_generated_date', [$startDate, $endDate])->count(),
                'approved' => DB::table('tbl_performa_invoice')->where('deleteflag', 'active')->where('pi_status', 'approved')->whereBetween('pi_generated_date', [$startDate, $endDate])->count()
            ];

            // Invoice stats
            $invoiceStats = [
                'total' => DB::table('tbl_tax_invoice')->where('deleteflag', 'active')->whereBetween('invoice_generated_date', [$startDate, $endDate])->count(),
                'pending' => DB::table('tbl_tax_invoice')->where('deleteflag', 'active')->where('invoice_status', 'pending')->whereBetween('invoice_generated_date', [$startDate, $endDate])->count(),
                'approved' => DB::table('tbl_tax_invoice')->where('deleteflag', 'active')->where('invoice_status', 'approved')->whereBetween('invoice_generated_date', [$startDate, $endDate])->count()
            ];

            // Credit Note stats - using correct column name from legacy file
            $creditNoteStats = [
                'total' => DB::table('tbl_tax_credit_note_invoice')->where('deleteflag', 'active')->whereBetween('credit_invoice_generated_date', [$startDate, $endDate])->count(),
                'pending' => DB::table('tbl_tax_credit_note_invoice')->where('deleteflag', 'active')->where('invoice_status', 'pending')->whereBetween('credit_invoice_generated_date', [$startDate, $endDate])->count(),
                'approved' => DB::table('tbl_tax_credit_note_invoice')->where('deleteflag', 'active')->where('invoice_status', 'approved')->whereBetween('credit_invoice_generated_date', [$startDate, $endDate])->count()
            ];

            // Credit Approval stats - using correct table name
            $creditApprovalStats = [
                'total' => DB::table('pre_approval_credit_requests')->where('deleteflag', 'active')->count(),
                'pending' => DB::table('pre_approval_credit_requests')->where('deleteflag', 'active')->where('status', 'pending')->count(),
                'approved' => DB::table('pre_approval_credit_requests')->where('deleteflag', 'active')->where('status', 'approved')->count(),
                'rejected' => DB::table('pre_approval_credit_requests')->where('deleteflag', 'active')->where('status', 'rejected')->count(),
                'expired' => DB::table('pre_approval_credit_requests')->where('deleteflag', 'active')->where('status', 'expired')->count()
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'proforma_invoices' => $piStats,
                    'invoices' => $invoiceStats,
                    'credit_notes' => $creditNoteStats,
                    'credit_approvals' => $creditApprovalStats,
                    'financial_year' => $financialYear
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update approval status with detailed validation
     */
    public function updateApprovalStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'approval_id' => 'required|string',
                'action' => 'required|in:approve,reject,request_docs',
                'remarks' => 'nullable|string|max:500',
                'admin_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation Error!',
                    'data' => $validator->errors()
                ], 400);
            }

            $approvalId = $request->approval_id;
            $action = $request->action;
            $remarks = $request->remarks ?? '';
            $adminId = $request->admin_id;

            // Mock implementation - in real scenario this would update database
            $statusMap = [
                'approve' => 'APPROVED',
                'reject' => 'REJECTED',
                'request_docs' => 'DOCS_REQUIRED'
            ];

            $newStatus = $statusMap[$action];
            $timestamp = now()->format('d M Y');

            // Log the action
            $logData = [
                'approval_id' => $approvalId,
                'action' => $action,
                'status' => $newStatus,
                'admin_id' => $adminId,
                'admin_name' => admin_name($adminId),
                'remarks' => $remarks,
                'updated_at' => $timestamp
            ];

            // Return success response with updated data
            return response()->json([
                'status' => 'success',
                'message' => 'Approval status updated successfully',
                'data' => [
                    'approval_id' => $approvalId,
                    'new_status' => $newStatus,
                    'updated_on' => $timestamp,
                    'updated_by' => admin_name($adminId),
                    'remarks' => $remarks,
                    'log_entry' => $logData
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error updating approval status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pending requests from all sources (consolidated view)
     */
    public function getAllPendingRequests(Request $request)
    {
        try {
            // Get parameters
            $pageno = $request->get('pageno', 1);
            $records = min($request->get('records', 20), 100);
            
            // Filters
            $financialYear = $request->get('financial_year', '');
            $accManager = $request->get('acc_manager', '');
            
            // Get financial year date range
            $fyDateRange = $this->getFinancialYearDateRange($financialYear);
            
            $allPendingRequests = [];
            
            // 1. Get Pending Proforma Invoices
            $whereClause = "WHERE pi.deleteflag = 'active' AND pi.pi_status = 'pending'";
            $whereClause .= " AND pi.pi_generated_date >= '" . $fyDateRange['start_date'] . "' AND pi.pi_generated_date <= '" . $fyDateRange['end_date'] . "'";
            
            if (!empty($accManager)) {
                $whereClause .= " AND pi.Prepared_by = " . intval($accManager);
            }
            
            $pendingPI = DB::select("
                SELECT pi.pi_id, pi.O_Id, pi.PO_NO, pi.pi_generated_date, pi.Cus_Com_Name, pi.Prepared_by, 'Proforma invoice' as approval_for
                FROM tbl_performa_invoice pi 
                $whereClause 
                ORDER BY pi.pi_generated_date DESC
            ");
            
            foreach ($pendingPI as $item) {
                $allPendingRequests[] = [
                    'requested_on' => date('d M Y', strtotime($item->pi_generated_date)),
                    'approval_for' => $item->approval_for,
                    'customer_name' => $item->Cus_Com_Name,
                    'account_manager' => function_exists('admin_name') ? admin_name($item->Prepared_by) : $item->Prepared_by,
                    'account_manager_id' => $item->Prepared_by,
                    'document_ref' => $item->PO_NO,
                    'status' => 'PENDING',
                    'status_color' => 'warning',
                    'source_id' => $item->pi_id,
                    'source_type' => 'proforma_invoice'
                ];
            }
            
            // 2. Get Pending Invoices
            $whereClause = "WHERE tti.deleteflag = 'active' AND tti.invoice_status = 'pending'";
            $whereClause .= " AND tti.invoice_generated_date >= '" . $fyDateRange['start_date'] . "' AND tti.invoice_generated_date <= '" . $fyDateRange['end_date'] . "'";
            
            if (!empty($accManager)) {
                $whereClause .= " AND tti.prepared_by = " . intval($accManager);
            }
            
            $pendingInvoices = DB::select("
                SELECT tti.invoice_id, tti.o_id, tti.po_no, tti.invoice_generated_date, tti.cus_com_name, tti.prepared_by, 'Invoice' as approval_for
                FROM tbl_tax_invoice tti 
                $whereClause 
                ORDER BY tti.invoice_generated_date DESC
            ");
            
            foreach ($pendingInvoices as $item) {
                $allPendingRequests[] = [
                    'requested_on' => date('d M Y', strtotime($item->invoice_generated_date)),
                    'approval_for' => $item->approval_for,
                    'customer_name' => $item->cus_com_name,
                    'account_manager' => function_exists('admin_name') ? admin_name($item->prepared_by) : $item->prepared_by,
                    'account_manager_id' => $item->prepared_by,
                    'document_ref' => $item->po_no,
                    'status' => 'PENDING',
                    'status_color' => 'warning',
                    'source_id' => $item->invoice_id,
                    'source_type' => 'invoice'
                ];
            }
            
            // 3. Get Pending Credit Notes
            $whereClause = "WHERE ttcni.deleteflag = 'active' AND ttcni.invoice_status = 'pending'";
            $whereClause .= " AND ttcni.credit_invoice_generated_date >= '" . $fyDateRange['start_date'] . "' AND ttcni.credit_invoice_generated_date <= '" . $fyDateRange['end_date'] . "'";
            
            if (!empty($accManager)) {
                $whereClause .= " AND ttcni.prepared_by = " . intval($accManager);
            }
            
            $pendingCN = DB::select("
                SELECT ttcni.credit_note_invoice_id, ttcni.o_id, ttcni.po_no, ttcni.credit_invoice_generated_date, ttcni.cus_com_name, ttcni.prepared_by, 'Credit note' as approval_for
                FROM tbl_tax_credit_note_invoice ttcni 
                $whereClause 
                ORDER BY ttcni.credit_invoice_generated_date DESC
            ");
            
            foreach ($pendingCN as $item) {
                $allPendingRequests[] = [
                    'requested_on' => date('d M Y', strtotime($item->credit_invoice_generated_date)),
                    'approval_for' => $item->approval_for,
                    'customer_name' => $item->cus_com_name,
                    'account_manager' => function_exists('admin_name') ? admin_name($item->prepared_by) : $item->prepared_by,
                    'account_manager_id' => $item->prepared_by,
                    'document_ref' => $item->po_no,
                    'status' => 'PENDING',
                    'status_color' => 'warning',
                    'source_id' => $item->credit_note_invoice_id,
                    'source_type' => 'credit_note'
                ];
            }
            
            // 4. Get Credit Pre-approval requests (from mock data - filter only PENDING)
            $creditApprovals = [
                [
                    'requested_on' => '18 Jul 2024',
                    'approval_for' => 'Credit (Pre approval)',
                    'customer_name' => 'Kolkata Municipal Corporation',
                    'account_manager' => 'Rahul Sharma',
                    'account_manager_id' => 99,
                    'document_ref' => '-',
                    'status' => 'PENDING',
                    'status_color' => 'warning',
                    'source_id' => '361145',
                    'source_type' => 'credit_approval'
                ]
            ];
            
            // Filter credit approvals by account manager if needed
            if (!empty($accManager)) {
                $creditApprovals = array_filter($creditApprovals, function($approval) use ($accManager) {
                    return $approval['account_manager_id'] == intval($accManager);
                });
            }
            
            // Add credit approvals to the list
            $allPendingRequests = array_merge($allPendingRequests, $creditApprovals);
            
            // Sort by requested_on date (newest first)
            usort($allPendingRequests, function($a, $b) {
                return strtotime($b['requested_on']) - strtotime($a['requested_on']);
            });
            
            // Apply pagination
            $totalItems = count($allPendingRequests);
            $totalPages = ceil($totalItems / $records);
            $offset = ($pageno - 1) * $records;
            $paginatedRequests = array_slice($allPendingRequests, $offset, $records);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'pending_requests' => $paginatedRequests,
                    'pagination' => [
                        'pageno' => strval($pageno),
                        'records' => strval($records),
                        'totalItems' => $totalItems,
                        'totalPages' => $totalPages
                    ],
                    'summary' => [
                        'total_pending' => $totalItems,
                        'proforma_invoices' => count(array_filter($allPendingRequests, fn($item) => $item['source_type'] === 'proforma_invoice')),
                        'invoices' => count(array_filter($allPendingRequests, fn($item) => $item['source_type'] === 'invoice')),
                        'credit_notes' => count(array_filter($allPendingRequests, fn($item) => $item['source_type'] === 'credit_note')),
                        'credit_approvals' => count(array_filter($allPendingRequests, fn($item) => $item['source_type'] === 'credit_approval'))
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching pending requests: ' . $e->getMessage()
            ], 500);
        }
    }
}
