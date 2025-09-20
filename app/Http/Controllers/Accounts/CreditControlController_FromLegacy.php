<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditControlController extends Controller
{
    /**
     * Get Proforma Invoices listing - based on view_pi_stat.php logic
     */
    public function getProformaInvoicesList(Request $request)
    {
        try {
            // Get request parameters
            $page = $request->get('page', 1);
            $pageSize = min($request->get('pageSize', 200), 500);
            $sortkey = $request->get('sortkey', 'pi_generated_date');
            $sortdir = $request->get('sortdir', 'asc');
            
            // Filters based on original logic
            $acc_manager = $request->get('acc_manager', '');
            $order_id = $request->get('order_id', '');

            // Build search conditions exactly like original
            $search_acc_manager = '';
            if (!empty($acc_manager)) {
                $search_acc_manager = " AND Prepared_by = '$acc_manager'";
            }

            $search_order_id = '';
            if (!empty($order_id)) {
                $search_order_id = " AND O_Id = '$order_id'";
            }

            // Base query exactly like original file
            $search = " WHERE 1=1 AND save_send='yes' " . $search_acc_manager . $search_order_id;
            $star = "pi_id, O_Id, PO_NO, pi_generated_date, Prepared_by, pi_status, Cus_Com_Name, PO_path";
            
            $sql_perf = "SELECT $star FROM tbl_performa_invoice $search ORDER BY $sortkey $sortdir LIMIT " . (($page - 1) * $pageSize) . ", $pageSize";

            $results = DB::select(DB::raw($sql_perf));

            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM tbl_performa_invoice $search";
            $total_result = DB::select(DB::raw($count_sql));
            $totalRecords = $total_result[0]->total;

            // Format results using the same helper functions as original
            $data = collect($results)->map(function ($row) {
                // Get additional data like original
                $total_invoice_amount_with_tax = $this->proforma_invoice_total_view($row->O_Id);
                $invoice_id = $this->get_invoice_id_by_order_id($row->O_Id);
                $partpayment = $this->get_total_part_payment_received_by_order_id($row->O_Id);

                return [
                    'pi_id' => $row->pi_id,
                    'offer_id' => $row->O_Id,
                    'po_no' => $row->PO_NO,
                    'pi_generated_date' => $row->pi_generated_date,
                    'date_time' => date("d/M/Y H:i:s", strtotime($row->pi_generated_date)),
                    'prepared_by' => $row->Prepared_by,
                    'pi_status' => $row->pi_status,
                    'customer_name' => ucfirst($row->Cus_Com_Name),
                    'po_path' => $row->PO_path,
                    'account_manager_name' => function_exists('admin_name') ? admin_name($row->Prepared_by) : 'Unknown',
                    'invoice_id' => $invoice_id ?: 'N/A',
                    'total_amount' => round($total_invoice_amount_with_tax, 2),
                    'advance_received' => $partpayment,
                    'status_badge' => ucfirst($row->pi_status),
                    'status_color' => $this->getStatusColor($row->pi_status),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'pagination' => [
                    'current_page' => (int) $page,
                    'page_size' => (int) $pageSize,
                    'total_records' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $pageSize)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching proforma invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Invoices listing - based on view_invoices.php logic
     */
    public function getInvoicesList(Request $request)
    {
        try {
            // Get request parameters
            $page = $request->get('page', 1);
            $pageSize = min($request->get('pageSize', 200), 500);
            $sortkey = $request->get('sortkey', 'tti.invoice_generated_date');
            $sortdir = $request->get('sortdir', 'desc');
            
            // Filters based on original logic
            $acc_manager = $request->get('acc_manager', '');
            $datevalid_from = $request->get('datevalid_from', '');
            $datevalid_to = $request->get('datevalid_to', '');
            $invoice_status = $request->get('invoice_status', '');

            // Build search conditions exactly like original
            $searchrecord = '';
            
            if (!empty($acc_manager)) {
                $searchrecord .= " AND tti.prepared_by = '$acc_manager'";
            }
            
            if (!empty($datevalid_from) && !empty($datevalid_to)) {
                $searchrecord .= " AND (date(tti.invoice_generated_date) BETWEEN '$datevalid_from' AND '$datevalid_to')";
            }
            
            if (!empty($invoice_status)) {
                $searchrecord .= " AND tti.invoice_status = '$invoice_status'";
            }

            // Base query exactly like original file view_invoices.php
            $sql = "
            SELECT tti.invoice_id, tti.o_id, tti.po_no, tti.po_due_date, tti.po_date, tti.invoice_generated_date, tti.po_from, tti.cus_com_name, tti.con_name, tti.prepared_by, tti.invoice_status, tti.payment_terms, tti.invoice_type, tti.deleteflag, tti.con_cust_co_name, tti.eway_bill_no, tti.terms_of_delivery, tti.freight_amount, tti.freight_gst_amount, tti.total_gst_amount, tti.sub_total_amount_without_gst, tti.gst_sale_type, tti.invoice_currency, tti.invoice_approval_status, tip.pro_id, tip.pro_name, tirn.ackno, tirn.ackdt, tirn.irn, tirn.signedinvoice, tirn.signedqrcode, tirn.ewbno, tirn.ewbdt, tirn.ewbvalidtill, tirn.qrcodeurl, tirn.response_msg_status, tirn.alert, tirn.requestid, tirn.irn_status 
            FROM 
            tbl_tax_invoice tti 
            INNER JOIN tbl_order_product tip ON tti.o_id=tip.order_id 
            LEFT JOIN tbl_tax_invoice_gst_irn_response tirn ON tti.invoice_id=tirn.invoice_id 
            WHERE 1=1 
            $searchrecord
            ORDER BY $sortkey $sortdir 
            LIMIT " . (($page - 1) * $pageSize) . ", $pageSize";

            $results = DB::select(DB::raw($sql));

            // Get total count
            $count_sql = "
            SELECT COUNT(*) as total 
            FROM 
            tbl_tax_invoice tti 
            INNER JOIN tbl_order_product tip ON tti.o_id=tip.order_id 
            LEFT JOIN tbl_tax_invoice_gst_irn_response tirn ON tti.invoice_id=tirn.invoice_id 
            WHERE 1=1 
            $searchrecord";
            
            $total_result = DB::select(DB::raw($count_sql));
            $totalRecords = $total_result[0]->total;

            // Format results using the same helper functions as original
            $data = collect($results)->map(function ($row) {
                return [
                    'invoice_id' => $row->invoice_id,
                    'offer_id' => $row->o_id,
                    'po_no' => $row->po_no,
                    'invoice_generated_date' => $row->invoice_generated_date,
                    'date_time' => date("d/M/Y H:i:s", strtotime($row->invoice_generated_date)),
                    'prepared_by' => $row->prepared_by,
                    'invoice_status' => $row->invoice_status,
                    'customer_name' => ucfirst($row->cus_com_name),
                    'account_manager_name' => function_exists('admin_name') ? admin_name($row->prepared_by) : 'Unknown',
                    'total_amount' => round($row->sub_total_amount_without_gst + $row->total_gst_amount + $row->freight_amount, 2),
                    'status_badge' => ucfirst($row->invoice_status),
                    'status_color' => $this->getStatusColor($row->invoice_status),
                    'irn_status' => $row->irn_status,
                    'irn' => $row->irn,
                    'eway_bill_no' => $row->eway_bill_no,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'pagination' => [
                    'current_page' => (int) $page,
                    'page_size' => (int) $pageSize,
                    'total_records' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $pageSize)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Credit Note Invoices listing - based on view_credit_note_invoices.php logic
     */
    public function getCreditNoteInvoicesList(Request $request)
    {
        try {
            // Get request parameters
            $page = $request->get('page', 1);
            $pageSize = min($request->get('pageSize', 200), 500);
            $sortkey = $request->get('sortkey', 'ttcni.credit_invoice_generated_date');
            $sortdir = $request->get('sortdir', 'desc');
            
            // Filters based on original logic
            $acc_manager = $request->get('acc_manager', '');
            $datevalid_from = $request->get('datevalid_from', '');
            $datevalid_to = $request->get('datevalid_to', '');

            // Build search conditions exactly like original
            $searchrecord = '';
            
            if (!empty($acc_manager)) {
                $searchrecord .= " AND ttcni.prepared_by = '$acc_manager'";
            }
            
            if (!empty($datevalid_from) && !empty($datevalid_to)) {
                $searchrecord .= " AND (date(ttcni.credit_invoice_generated_date) BETWEEN '$datevalid_from' AND '$datevalid_to')";
            }

            // Base query exactly like original file view_credit_note_invoices.php
            $sql = "
            SELECT ttcni.credit_note_invoice_id, ttcni.credit_invoice_generated_date, ttcni.invoice_id, ttcni.o_id, ttcni.po_no, ttcni.po_due_date, ttcni.po_date, ttcni.invoice_generated_date, ttcni.po_from, ttcni.cus_com_name, ttcni.con_name, ttcni.con_address, ttcni.con_country, ttcni.con_state, ttcni.con_city, ttcni.con_mobile, ttcni.con_email, ttcni.con_gst, ttcni.buyer_name, ttcni.buyer_address, ttcni.buyer_country, ttcni.buyer_state, ttcni.buyer_city, ttcni.buyer_mobile, ttcni.buyer_email, ttcni.prepared_by, ttcni.invoice_status, ttcni.branch_sel, ttcni.bank_sel, ttcni.payment_terms, ttcni.invoice_type, ttcni.deleteflag, ttcni.con_cust_co_name, ttcni.con_pincode, ttcni.buyer_gst, ttcni.buyer_pin_code, ttcni.eway_bill_no, ttcni.delivery_note, ttcni.ref_no_and_date, ttcni.offer_ref, ttcni.dispatch_doc_no, ttcni.delivery_note_date, ttcni.Delivery, ttcni.destination, ttcni.terms_of_delivery, ttcni.freight_amount, ttcni.freight_gst_amount, ttcni.total_gst_amount, ttcni.sub_total_amount_without_gst, ttcni.gst_sale_type, ttcni.invoice_currency, ttcni.invoice_approval_status, ttcni.rental_start_date, ttcni.rental_end_date, tcnip.order_id, tcnip.model_no, tcnip.pro_id, tcnip.pro_description, tcnip.quantity, tcnip.price, tcnip.service_period, tcnirn.ackno, tcnirn.ackdt, tcnirn.irn, tcnirn.signedinvoice, tcnirn.signedqrcode, tcnirn.ewbno, tcnirn.ewbdt, tcnirn.ewbvalidtill, tcnirn.qrcodeurl, tcnirn.response_msg_status, tcnirn.alert, tcnirn.requestid, tcnirn.irn_status 
            FROM 
            tbl_tax_credit_note_invoice ttcni 
            RIGHT JOIN tbl_credit_note_invoice_products tcnip ON ttcni.o_id=tcnip.order_id 
            LEFT JOIN tbl_tax_credit_note_invoice_gst_irn_response tcnirn ON ttcni.o_id=tcnirn.o_id 
            WHERE 1=1 
            $searchrecord
            ORDER BY $sortkey $sortdir 
            LIMIT " . (($page - 1) * $pageSize) . ", $pageSize";

            $results = DB::select(DB::raw($sql));

            // Get total count
            $count_sql = "
            SELECT COUNT(*) as total 
            FROM 
            tbl_tax_credit_note_invoice ttcni 
            RIGHT JOIN tbl_credit_note_invoice_products tcnip ON ttcni.o_id=tcnip.order_id 
            LEFT JOIN tbl_tax_credit_note_invoice_gst_irn_response tcnirn ON ttcni.o_id=tcnirn.o_id 
            WHERE 1=1 
            $searchrecord";
            
            $total_result = DB::select(DB::raw($count_sql));
            $totalRecords = $total_result[0]->total;

            // Format results using the same helper functions as original
            $data = collect($results)->map(function ($row) {
                return [
                    'credit_note_invoice_id' => $row->credit_note_invoice_id,
                    'invoice_id' => $row->invoice_id,
                    'offer_id' => $row->o_id,
                    'po_no' => $row->po_no,
                    'credit_invoice_generated_date' => $row->credit_invoice_generated_date,
                    'date_time' => date("d/M/Y H:i:s", strtotime($row->credit_invoice_generated_date)),
                    'prepared_by' => $row->prepared_by,
                    'invoice_status' => $row->invoice_status,
                    'customer_name' => ucfirst($row->cus_com_name),
                    'account_manager_name' => function_exists('admin_name') ? admin_name($row->prepared_by) : 'Unknown',
                    'total_amount' => round($row->sub_total_amount_without_gst + $row->total_gst_amount + $row->freight_amount, 2),
                    'status_badge' => ucfirst($row->invoice_status),
                    'status_color' => $this->getStatusColor($row->invoice_status),
                    'irn_status' => $row->irn_status,
                    'irn' => $row->irn,
                    'eway_bill_no' => $row->eway_bill_no,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'pagination' => [
                    'current_page' => (int) $page,
                    'page_size' => (int) $pageSize,
                    'total_records' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $pageSize)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching credit note invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Pre-approval Credit Requests listing
     */
    public function getCreditControlApprovals(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $pageSize = min($request->get('pageSize', 200), 500);
            
            $query = DB::table('tbl_pre_approval_credit_requests as pr')
                ->select([
                    'pr.id',
                    'pr.comp_id',
                    'pr.approval_amount',
                    'pr.approval_status',
                    'pr.requested_by',
                    'pr.approved_by',
                    'pr.request_date'
                ])
                ->where('pr.deleteflag', 'active');

            $totalRecords = $query->count();
            $results = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get();

            $data = $results->map(function ($item) {
                return [
                    'id' => $item->id,
                    'approval_amount' => $item->approval_amount,
                    'approval_status' => $item->approval_status,
                    'status_badge' => ucfirst($item->approval_status),
                    'request_date' => $item->request_date,
                    'customer_name' => function_exists('company_names') ? company_names($item->comp_id) : 'Unknown',
                    'requested_by_name' => function_exists('admin_name') ? admin_name($item->requested_by) : 'Unknown',
                    'approved_by_name' => function_exists('admin_name') ? admin_name($item->approved_by) : 'Unknown',
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'pagination' => [
                    'current_page' => (int) $page,
                    'page_size' => (int) $pageSize,
                    'total_records' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $pageSize)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching credit control approvals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter options - based on original dropdown logic
     */
    public function getCreditControlFilterOptions()
    {
        try {
            // Get admin users for dropdown like original
            $query = "deleteflag = 'active' ORDER BY admin_fname ASC";
            $rs_role = DB::select(DB::raw("SELECT admin_id, admin_fname, admin_lname FROM tbl_admin WHERE $query"));
            
            $admins = collect($rs_role)->map(function($row) {
                return [
                    'value' => $row->admin_id,
                    'label' => $row->admin_fname . ' ' . $row->admin_lname
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'admins' => $admins,
                    'statuses' => [
                        ['value' => 'pending', 'label' => 'Pending'],
                        ['value' => 'approved', 'label' => 'Approved'],
                        ['value' => 'reject', 'label' => 'Rejected'],
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching filter options: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getCreditControlDashboard(Request $request)
    {
        try {
            // Basic counts for dashboard
            $piStats = [
                'total' => DB::table('tbl_performa_invoice')->where('save_send', 'yes')->count(),
                'pending' => DB::table('tbl_performa_invoice')->where('save_send', 'yes')->where('pi_status', 'pending')->count(),
                'approved' => DB::table('tbl_performa_invoice')->where('save_send', 'yes')->where('pi_status', 'approved')->count()
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'proforma_invoices' => $piStats,
                    'message' => 'Dashboard data retrieved successfully'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper functions to replicate original logic
    private function proforma_invoice_total_view($order_id)
    {
        try {
            // This would be the equivalent of $s->proforma_invoice_total_view($row->O_Id)
            // You'll need to implement this based on your business logic
            return 0; // Placeholder
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function get_invoice_id_by_order_id($order_id)
    {
        try {
            // This would be the equivalent of $s->get_invoice_id_by_order_id($row->O_Id)
            // You'll need to implement this based on your business logic
            return ''; // Placeholder
        } catch (\Exception $e) {
            return '';
        }
    }

    private function get_total_part_payment_received_by_order_id($order_id)
    {
        try {
            // This would be the equivalent of $s->get_total_part_payment_received_by_order_id($row->O_Id)
            $result = DB::table('tbl_payment_received')
                ->where('o_id', $order_id)
                ->sum('payment_received_value');
            return $result ?: 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getStatusColor($status)
    {
        switch ($status) {
            case 'approved':
                return '#dcf7c1';
            case 'reject':
                return '#e29793';
            default:
                return '#f5d5a7';
        }
    }
}
