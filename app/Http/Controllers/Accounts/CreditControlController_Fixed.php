<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditControlController extends Controller
{
    /**
     * Get Proforma Invoices listing with filters and pagination
     */
    public function getProformaInvoicesList(Request $request)
    {
        try {
            // Get request parameters
            $page = $request->get('page', 1);
            $pageSize = min($request->get('pageSize', 20), 100);
            $sortkey = $request->get('sortkey', 'pi_id');
            $sortdir = $request->get('sortdir', 'desc');
            
            // Filters
            $companyId = $request->get('companyId', '');
            $adminId = $request->get('adminId', '');
            $status = $request->get('status', '');
            $dateFrom = $request->get('dateFrom', '');
            $dateTo = $request->get('dateTo', '');
            $invoiceNumber = $request->get('invoiceNumber', '');

            // Build base query - simplified
            $query = DB::table('tbl_performa_invoice as pi')
                ->select([
                    'pi.pi_id',
                    'pi.pi_no',
                    'pi.created_at as pi_date',
                    'pi.pi_amount as value',
                    'pi.pi_status as status',
                    'pi.advance_received',
                    'pi.advance_received_date',
                    'pi.advance_received_via',
                    'pi.advance_received_bank',
                    'pi.company_id',
                    'pi.account_manager'
                ])
                ->where('pi.deleteflag', 'active');

            // Apply filters
            if (!empty($companyId)) {
                $query->where('pi.company_id', $companyId);
            }

            if (!empty($adminId)) {
                $query->where('pi.account_manager', $adminId);
            }

            if (!empty($status)) {
                $query->where('pi.pi_status', $status);
            }

            if (!empty($dateFrom)) {
                $query->whereDate('pi.created_at', '>=', $dateFrom);
            }

            if (!empty($dateTo)) {
                $query->whereDate('pi.created_at', '<=', $dateTo);
            }

            if (!empty($invoiceNumber)) {
                $query->where('pi.pi_no', 'LIKE', "%{$invoiceNumber}%");
            }

            // Get total count
            $totalRecords = $query->count();

            // Apply sorting and pagination
            $results = $query->orderBy($sortkey, $sortdir)
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            // Format results
            $data = $results->map(function ($item) {
                return [
                    'id' => $item->pi_id,
                    'pi_no' => $item->pi_no,
                    'value' => $item->value,
                    'status' => $item->status,
                    'status_badge' => ucfirst($item->status),
                    'payment_status' => $item->advance_received > 0 ? 'Full Payment received' : 'Payment Pending',
                    'date_time' => $item->pi_date,
                    'customer_name' => function_exists('company_names') ? company_names($item->company_id) : 'Unknown',
                    'account_manager_name' => function_exists('admin_name') ? admin_name($item->account_manager) : 'Unknown',
                    'advance_received' => $item->advance_received,
                    'advance_received_date' => $item->advance_received_date,
                    'advance_received_via' => $item->advance_received_via,
                    'advance_received_bank' => $item->advance_received_bank,
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
     * Get Invoices listing with filters and pagination
     */
    public function getInvoicesList(Request $request)
    {
        try {
            // Get request parameters
            $page = $request->get('page', 1);
            $pageSize = min($request->get('pageSize', 20), 100);
            $sortkey = $request->get('sortkey', 'invoice_id');
            $sortdir = $request->get('sortdir', 'desc');
            
            // Filters
            $companyId = $request->get('companyId', '');
            $adminId = $request->get('adminId', '');
            $status = $request->get('status', '');
            $dateFrom = $request->get('dateFrom', '');
            $dateTo = $request->get('dateTo', '');
            $invoiceNumber = $request->get('invoiceNumber', '');

            // Build base query
            $query = DB::table('tbl_tax_invoice as ti')
                ->select([
                    'ti.invoice_id',
                    'ti.invoice_no',
                    'ti.invoice_generated_date',
                    'ti.invoice_amount as value',
                    'ti.invoice_status as status',
                    'ti.company_id',
                    'ti.account_manager'
                ])
                ->where('ti.deleteflag', 'active');

            // Apply filters
            if (!empty($companyId)) {
                $query->where('ti.company_id', $companyId);
            }

            if (!empty($adminId)) {
                $query->where('ti.account_manager', $adminId);
            }

            if (!empty($status)) {
                $query->where('ti.invoice_status', $status);
            }

            if (!empty($dateFrom)) {
                $query->whereDate('ti.invoice_generated_date', '>=', $dateFrom);
            }

            if (!empty($dateTo)) {
                $query->whereDate('ti.invoice_generated_date', '<=', $dateTo);
            }

            if (!empty($invoiceNumber)) {
                $query->where('ti.invoice_no', 'LIKE', "%{$invoiceNumber}%");
            }

            // Get total count
            $totalRecords = $query->count();

            // Apply sorting and pagination
            $results = $query->orderBy($sortkey, $sortdir)
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            // Format results
            $data = $results->map(function ($item) {
                return [
                    'id' => $item->invoice_id,
                    'invoice_no' => $item->invoice_no,
                    'value' => $item->value,
                    'status' => $item->status,
                    'status_badge' => ucfirst($item->status),
                    'date_time' => $item->invoice_generated_date,
                    'customer_name' => function_exists('company_names') ? company_names($item->company_id) : 'Unknown',
                    'account_manager_name' => function_exists('admin_name') ? admin_name($item->account_manager) : 'Unknown',
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
     * Get Credit Note Invoices listing with filters and pagination
     */
    public function getCreditNoteInvoicesList(Request $request)
    {
        try {
            // Get request parameters
            $page = $request->get('page', 1);
            $pageSize = min($request->get('pageSize', 20), 100);
            $sortkey = $request->get('sortkey', 'credit_note_id');
            $sortdir = $request->get('sortdir', 'desc');
            
            // Filters
            $companyId = $request->get('companyId', '');
            $adminId = $request->get('adminId', '');
            $status = $request->get('status', '');
            $dateFrom = $request->get('dateFrom', '');
            $dateTo = $request->get('dateTo', '');
            $invoiceNumber = $request->get('invoiceNumber', '');

            // Build base query
            $query = DB::table('tbl_tax_credit_note_invoice as cn')
                ->select([
                    'cn.credit_note_id',
                    'cn.credit_note_no',
                    'cn.credit_note_date',
                    'cn.credit_note_amount as value',
                    'cn.credit_note_status as status',
                    'cn.company_id',
                    'cn.account_manager'
                ])
                ->where('cn.deleteflag', 'active');

            // Apply filters
            if (!empty($companyId)) {
                $query->where('cn.company_id', $companyId);
            }

            if (!empty($adminId)) {
                $query->where('cn.account_manager', $adminId);
            }

            if (!empty($status)) {
                $query->where('cn.credit_note_status', $status);
            }

            if (!empty($dateFrom)) {
                $query->whereDate('cn.credit_note_date', '>=', $dateFrom);
            }

            if (!empty($dateTo)) {
                $query->whereDate('cn.credit_note_date', '<=', $dateTo);
            }

            if (!empty($invoiceNumber)) {
                $query->where('cn.credit_note_no', 'LIKE', "%{$invoiceNumber}%");
            }

            // Get total count
            $totalRecords = $query->count();

            // Apply sorting and pagination
            $results = $query->orderBy($sortkey, $sortdir)
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            // Format results
            $data = $results->map(function ($item) {
                return [
                    'id' => $item->credit_note_id,
                    'credit_note_no' => $item->credit_note_no,
                    'value' => $item->value,
                    'status' => $item->status,
                    'status_badge' => ucfirst($item->status),
                    'date_time' => $item->credit_note_date,
                    'customer_name' => function_exists('company_names') ? company_names($item->company_id) : 'Unknown',
                    'account_manager_name' => function_exists('admin_name') ? admin_name($item->account_manager) : 'Unknown',
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
     * Get Pre-approval Credit Requests listing with filters and pagination
     */
    public function getCreditControlApprovals(Request $request)
    {
        try {
            // Get request parameters
            $page = $request->get('page', 1);
            $pageSize = min($request->get('pageSize', 20), 100);
            $sortkey = $request->get('sortkey', 'id');
            $sortdir = $request->get('sortdir', 'desc');
            
            // Filters
            $companyId = $request->get('companyId', '');
            $adminId = $request->get('adminId', '');
            $status = $request->get('status', '');
            $dateFrom = $request->get('dateFrom', '');
            $dateTo = $request->get('dateTo', '');

            // Build base query
            $query = DB::table('tbl_pre_approval_credit_requests as pr')
                ->select([
                    'pr.id',
                    'pr.comp_id',
                    'pr.request_date',
                    'pr.payment_terms',
                    'pr.currency',
                    'pr.approval_amount',
                    'pr.requested_by',
                    'pr.approved_by',
                    'pr.notes',
                    'pr.approval_status',
                    'pr.created_at',
                    'pr.updated_at'
                ])
                ->where('pr.deleteflag', 'active');

            // Apply filters
            if (!empty($companyId)) {
                $query->where('pr.comp_id', $companyId);
            }

            if (!empty($adminId)) {
                $query->where('pr.requested_by', $adminId);
            }

            if (!empty($status)) {
                $query->where('pr.approval_status', $status);
            }

            if (!empty($dateFrom)) {
                $query->whereDate('pr.request_date', '>=', $dateFrom);
            }

            if (!empty($dateTo)) {
                $query->whereDate('pr.request_date', '<=', $dateTo);
            }

            // Get total count
            $totalRecords = $query->count();

            // Apply sorting and pagination
            $results = $query->orderBy($sortkey, $sortdir)
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            // Format results
            $data = $results->map(function ($item) {
                return [
                    'id' => $item->id,
                    'comp_id' => $item->comp_id,
                    'request_date' => $item->request_date,
                    'payment_terms' => $item->payment_terms,
                    'currency' => $item->currency,
                    'approval_amount' => $item->approval_amount,
                    'notes' => $item->notes,
                    'approval_status' => $item->approval_status,
                    'status_badge' => ucfirst($item->approval_status),
                    'customer_name' => function_exists('company_names') ? company_names($item->comp_id) : 'Unknown',
                    'requested_by_name' => function_exists('admin_name') ? admin_name($item->requested_by) : 'Unknown',
                    'approved_by_name' => function_exists('admin_name') ? admin_name($item->approved_by) : 'Unknown',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
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
     * Get filter options for dropdowns
     */
    public function getCreditControlFilterOptions()
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'companies' => [],
                    'admins' => [],
                    'statuses' => [
                        ['value' => 'pending', 'label' => 'Pending'],
                        ['value' => 'approved', 'label' => 'Approved'],
                        ['value' => 'rejected', 'label' => 'Rejected'],
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
            $financialYear = $request->get('financial_year', '2024-25');
            
            // Calculate date range for financial year
            $fyParts = explode('-', $financialYear);
            $startDate = "20{$fyParts[0]}-04-01";
            $endDate = "20{$fyParts[1]}-03-31";

            // Simplified stats without problematic columns
            $piStats = [
                'total' => DB::table('tbl_performa_invoice')->where('deleteflag', 'active')->count(),
                'pending' => DB::table('tbl_performa_invoice')->where('deleteflag', 'active')->where('pi_status', 'pending')->count(),
                'approved' => DB::table('tbl_performa_invoice')->where('deleteflag', 'active')->where('pi_status', 'approved')->count()
            ];

            $invoiceStats = [
                'total' => DB::table('tbl_tax_invoice')->where('deleteflag', 'active')->count(),
                'pending' => DB::table('tbl_tax_invoice')->where('deleteflag', 'active')->where('invoice_status', 'pending')->count(),
                'approved' => DB::table('tbl_tax_invoice')->where('deleteflag', 'active')->where('invoice_status', 'approved')->count()
            ];

            $creditNoteStats = [
                'total' => DB::table('tbl_tax_credit_note_invoice')->where('deleteflag', 'active')->count(),
                'pending' => DB::table('tbl_tax_credit_note_invoice')->where('deleteflag', 'active')->where('credit_note_status', 'pending')->count(),
                'approved' => DB::table('tbl_tax_credit_note_invoice')->where('deleteflag', 'active')->where('credit_note_status', 'approved')->count()
            ];

            $approvalStats = [
                'total' => DB::table('tbl_pre_approval_credit_requests')->where('deleteflag', 'active')->count(),
                'pending' => DB::table('tbl_pre_approval_credit_requests')->where('deleteflag', 'active')->where('approval_status', 'PENDING')->count(),
                'approved' => DB::table('tbl_pre_approval_credit_requests')->where('deleteflag', 'active')->where('approval_status', 'APPROVED')->count()
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'financial_year' => $financialYear,
                    'proforma_invoices' => $piStats,
                    'invoices' => $invoiceStats,
                    'credit_notes' => $creditNoteStats,
                    'approvals' => $approvalStats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}
