<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreditControlController_Simple extends Controller
{
    /**
     * Test controller method for debugging
     */
    public function testController()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Controller test successful!',
            'controller' => 'CreditControlController_Simple',
            'timestamp' => now(),
            'available_methods' => [
                'getProformaInvoicesList',
                'getInvoicesList', 
                'getCreditNoteInvoicesList',
                'getCreditControlApprovals',
                'getCreditControlFilterOptions',
                'getCreditControlDashboard',
                'getUnifiedApprovalsList',
                'updateApprovalStatus',
                'getPendingApprovalsSummary'
            ]
        ]);
    }

    /**
     * Get Proforma Invoices listing with filters and pagination
     */
    public function getProformaInvoicesList(Request $request)
    {
        try {
            // Get request parameters
            $page = $request->get('pageno', 1);
            $pageSize = min($request->get('records', 20), 100);
            $sortkey = $request->get('sortkey', 'pi_id');
            $sortdir = $request->get('sortdir', 'desc');
            
            // Build very basic query to avoid column issues
            $query = DB::table('tbl_performa_invoice as pi')
                ->select([
                    'pi.pi_id',
                    'pi.PO_NO as pi_no',
                    'pi.advance_received as value',
                    'pi.pi_status as status',
                    'pi.PO_From as company_id',
                    'pi.Prepared_by as account_manager'
                ])
                ->where('pi.deleteflag', 'active');

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
                    'payment_status' => 'Unknown',
                    'date_time' => date('Y-m-d H:i:s'),
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
            $page = $request->get('pageno', 1);
            $pageSize = min($request->get('records', 20), 100);
            
            $query = DB::table('tbl_tax_invoice as ti')
                ->select([
                    'ti.invoice_id',
                    'ti.po_no as invoice_no',
                    'ti.total_gst_amount as value',
                    'ti.invoice_status as status',
                    'ti.po_from as company_id',
                    'ti.prepared_by as account_manager'
                ])
                ->where('ti.deleteflag', 'active');

            $totalRecords = $query->count();
            $results = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get();

            $data = $results->map(function ($item) {
                return [
                    'id' => $item->invoice_id,
                    'invoice_no' => $item->invoice_no,
                    'value' => $item->value,
                    'status' => $item->status,
                    'status_badge' => ucfirst($item->status),
                    'date_time' => date('Y-m-d H:i:s'),
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
            $page = $request->get('pageno', 1);
            $pageSize = min($request->get('records', 20), 100);
            
            $query = DB::table('tbl_tax_credit_note_invoice as cn')
                ->select([
                    'cn.credit_note_invoice_id as credit_note_id',
                    'cn.po_no as credit_note_no',
                    'cn.total_gst_amount as value',
                    'cn.invoice_status as status',
                    'cn.po_from as company_id',
                    'cn.prepared_by as account_manager'
                ])
                ->where('cn.deleteflag', 'active');

            $totalRecords = $query->count();
            $results = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get();

            $data = $results->map(function ($item) {
                return [
                    'id' => $item->credit_note_id,
                    'credit_note_no' => $item->credit_note_no,
                    'value' => $item->value,
                    'status' => $item->status,
                    'status_badge' => ucfirst($item->status),
                    'date_time' => date('Y-m-d H:i:s'),
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
            $page = $request->get('pageno', 1);
            $pageSize = min($request->get('records', 20), 100);
            
            $query = DB::table('pre_approval_credit_requests as pr')
                ->select([
                    'pr.id',
                    'pr.comp_id',
                    'pr.approval_amount',
                    'pr.approval_status',
                    'pr.requested_by',
                    'pr.approved_by'
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
     * Get filter options for dropdowns
     */
    public function getCreditControlFilterOptions()
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'message' => 'Filter options available',
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
     * Get dashboard statistics - simplified to avoid column issues
     */
    public function getCreditControlDashboard(Request $request)
    {
        try {
            // Basic counts without date filtering to avoid column issues
            $piStats = [
                'total' => DB::table('tbl_performa_invoice')->where('deleteflag', 'active')->count(),
                'pending' => 0,
                'approved' => 0
            ];

            $invoiceStats = [
                'total' => DB::table('tbl_tax_invoice')->where('deleteflag', 'active')->count(),
                'pending' => 0,
                'approved' => 0
            ];

            $creditNoteStats = [
                'total' => DB::table('tbl_tax_credit_note_invoice')->where('deleteflag', 'active')->count(),
                'pending' => 0,
                'approved' => 0
            ];

            $approvalStats = [
                'total' => DB::table('pre_approval_credit_requests')->where('deleteflag', 'active')->count(),
                'pending' => 0,
                'approved' => 0
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'financial_year' => '2024-25',
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

    /**
     * Get unified approvals list (matches the UI shown in screenshot)
     * Combines all 4 approval types in one table with filtering and pagination
     */
    public function getUnifiedApprovalsList(Request $request)
    {
        try {
            $page = $request->get('pageno', 1);
            $pageSize = min($request->get('records', 20), 100);
            $statusFilter = $request->get('status', ''); // All, PENDING, DOCS_REQUIRED, etc.
            $approvalTypeFilter = $request->get('approval_for', ''); // All, Credit, Proforma Invoice, etc.
            $accManagerFilter = $request->get('acc_manager', ''); // Account manager filter
            
            // Calculate current financial year (April to March)
            $currentDate = now();
            $currentYear = $currentDate->year;
            $currentMonth = $currentDate->month;
            
            if ($currentMonth >= 4) {
                // Current FY: April 2025 to March 2026 -> "2025-2026"
                $currentFY = $currentYear . '-' . ($currentYear + 1);
            } else {
                // Current FY: April 2024 to March 2025 -> "2024-2025"
                $currentFY = ($currentYear - 1) . '-' . $currentYear;
            }
            
            // Handle financial year parameter - treat empty string as no value provided
            $financialYearParam = $request->get('financial_year');
            $financialYearFilter = (!empty($financialYearParam)) ? $financialYearParam : $currentFY; // Default to current FY if empty or null
            $sortkey = $request->get('sortkey', 'requested_on');
            $sortdir = $request->get('sortdir', 'desc');

            // Parse financial year to get start and end dates
            $fyParts = explode('-', $financialYearFilter);
            $fyStartYear = $fyParts[0] ?? $currentYear;
            $fyEndYear = $fyParts[1] ?? ($currentYear + 1);
            $fyStartDate = $fyStartYear . '-04-01'; // FY starts April 1st
            $fyEndDate = $fyEndYear . '-03-31'; // FY ends March 31st

            // Build union query for all approval types
            // IMPORTANT BUSINESS RULE: For Proforma Invoice, Tax Invoice, and Credit Note tables,
            // only include records where save_send = 'yes' as these are the ones sent for approval.
            // Records with save_send = 'no' are drafts/saved items not ready for approval workflow.
            $allApprovals = collect();

            // 1. Pre-approval Credit Requests (get comp_id directly, join with company table for name)
            $creditRequestsQuery = DB::table('pre_approval_credit_requests as pr')
                ->leftJoin('tbl_comp as c', 'pr.comp_id', '=', 'c.id')
                ->select([
                    'pr.id',
                    'pr.request_date as requested_on',
                    DB::raw("'Credit_Pre_approval' as approval_for"),
                    'pr.comp_id as customer_id', // Direct from pre_approval table
                    'pr.requested_by as account_manager_id',
                    'pr.approval_amount',
                    'pr.approval_status as status',
                    'pr.notes as remarks',
                    'c.comp_name as company_name_direct', // Get company name directly from join
                    DB::raw("'pre_approval' as record_type")
                ])
                ->where('pr.deleteflag', 'active')
                ->where('c.deleteflag', 'active')
                ->whereBetween('pr.request_date', [$fyStartDate, $fyEndDate]);

            if ($statusFilter && $statusFilter !== 'All') {
                $creditRequestsQuery->where('pr.approval_status', $statusFilter);
            }
            
            if ($accManagerFilter && $accManagerFilter !== 'All') {
                $creditRequestsQuery->where('pr.requested_by', $accManagerFilter);
            }

            // 2. Proforma Invoices (join with tbl_order to get proper customer_id)
            $proformaQuery = DB::table('tbl_performa_invoice as pi')
                ->leftJoin('tbl_order as o', 'pi.O_Id', '=', 'o.orders_id')
                ->select([
                    'pi.pi_id as id',
                    'pi.pi_generated_date as requested_on',
                    DB::raw("'Proforma_invoice' as approval_for"),
                    'o.customers_id as customer_id', // Get from tbl_order table
                    'pi.Prepared_by as account_manager_id',
                    'pi.O_Id', // Need O_Id for proforma_invoice_total_view function
                    'pi.pi_status as status',
                    'pi.approval_remarks as remarks',
                    DB::raw("NULL as company_name_direct"), // No direct company name field
                    DB::raw("'proforma_invoice' as record_type")
                ])
                ->where('pi.deleteflag', 'active')
                ->where('pi.save_send', 'yes') // Only include records marked for approval
                ->whereBetween('pi.pi_generated_date', [$fyStartDate, $fyEndDate]);

            if ($statusFilter && $statusFilter !== 'All') {
                // Map UI status to database status
                $dbStatus = $this->mapUiStatusToDb($statusFilter, 'proforma');
                $proformaQuery->where('pi.pi_status', $dbStatus);
            }
            
            if ($accManagerFilter && $accManagerFilter !== 'All') {
                $proformaQuery->where('pi.Prepared_by', $accManagerFilter);
            }

            // 3. Tax Invoices (join with tbl_order to get proper customer_id, also use cus_com_name)
            $taxInvoicesQuery = DB::table('tbl_tax_invoice as ti')
                ->leftJoin('tbl_order as o', 'ti.O_Id', '=', 'o.orders_id')
                ->select([
                    'ti.invoice_id as id',
                    'ti.invoice_generated_date as requested_on',
                    DB::raw("'Invoice' as approval_for"),
                    'o.customers_id as customer_id', // Get from tbl_order table
                    'ti.prepared_by as account_manager_id',
                    'ti.sub_total_amount_without_gst as approval_amount', // Use correct amount field
                    'ti.invoice_status as status',
                    'ti.approval_remarks as remarks',
                    'ti.cus_com_name as company_name_direct', // Direct company name from tax invoice
                    DB::raw("'tax_invoice' as record_type")
                ])
                ->where('ti.deleteflag', 'active')
                ->where('ti.save_send', 'yes') // Only include records marked for approval
                ->whereBetween('ti.invoice_generated_date', [$fyStartDate, $fyEndDate]);

            if ($statusFilter && $statusFilter !== 'All') {
                $dbStatus = $this->mapUiStatusToDb($statusFilter, 'invoice');
                $taxInvoicesQuery->where('ti.invoice_status', $dbStatus);
            }
            
            if ($accManagerFilter && $accManagerFilter !== 'All') {
                $taxInvoicesQuery->where('ti.prepared_by', $accManagerFilter);
            }

            // 4. Credit Note Invoices (join with tbl_order to get proper customer_id, also use cus_com_name)
            $creditNotesQuery = DB::table('tbl_tax_credit_note_invoice as cn')
                ->leftJoin('tbl_order as o', 'cn.O_Id', '=', 'o.orders_id')
                ->select([
                    'cn.credit_note_invoice_id as id',
                    'cn.credit_invoice_generated_date as requested_on',
                    DB::raw("'Credit_note' as approval_for"),
                    'o.customers_id as customer_id', // Get from tbl_order table
                    'cn.prepared_by as account_manager_id',
                    'cn.sub_total_amount_without_gst as approval_amount', // Use correct amount field
                    'cn.invoice_status as status',
                    'cn.approval_remarks as remarks',
                    'cn.cus_com_name as company_name_direct', // Direct company name from credit note
                    DB::raw("'credit_note' as record_type")
                ])
                ->where('cn.deleteflag', 'active')
                ->where('cn.save_send', 'yes') // Only include records marked for approval
                ->whereBetween('cn.credit_invoice_generated_date', [$fyStartDate, $fyEndDate]);

            if ($statusFilter && $statusFilter !== 'All') {
                $dbStatus = $this->mapUiStatusToDb($statusFilter, 'credit_note');
                $creditNotesQuery->where('cn.invoice_status', $dbStatus);
            }
            
            if ($accManagerFilter && $accManagerFilter !== 'All') {
                $creditNotesQuery->where('cn.prepared_by', $accManagerFilter);
            }

            // Get all records and combine them
            $creditRequests = $creditRequestsQuery->get();
            $proformaInvoices = $proformaQuery->get();
            $taxInvoices = $taxInvoicesQuery->get();
            $creditNotes = $creditNotesQuery->get();

            // Combine all records
            $allRecords = collect()
                ->merge($creditRequests)
                ->merge($proformaInvoices)
                ->merge($taxInvoices)
                ->merge($creditNotes);

            // Apply approval type filter
            if ($approvalTypeFilter && $approvalTypeFilter !== 'All') {
                $allRecords = $allRecords->filter(function ($item) use ($approvalTypeFilter) {
                    return $item->approval_for === $approvalTypeFilter;
                });
            }

            // Sort the collection
            $allRecords = $allRecords->sortBy(function ($item) use ($sortkey) {
                return $item->$sortkey;
            });

            if ($sortdir === 'desc') {
                $allRecords = $allRecords->reverse();
            }

            // Get total count before pagination
            $totalRecords = $allRecords->count();

            // Apply pagination
            $records = $allRecords->skip(($page - 1) * $pageSize)->take($pageSize);

            // Format results to match UI
            $data = $records->map(function ($item) {
                // Normalize status for display
                $displayStatus = $this->normalizeStatusForDisplay($item->status);
                
                // Get company name - prioritize direct company name field for all record types that have it
                $companyName = '';
                
                // For records with direct company name fields, use them first
                if (!empty($item->company_name_direct)) {
                    $companyName = $item->company_name_direct;
                } else {
                    // For other cases or if direct name is empty, use customer_id with helper
                    if ($item->customer_id && $item->customer_id > 0) {
                        $companyName = function_exists('company_names') ? company_names($item->customer_id) : '';
                    }
                    
                    // If company name is still empty, try to get it directly from database
                    if (empty($companyName) && $item->customer_id) {
                        $company = DB::table('tbl_comp')
                            ->select('comp_name')
                            ->where('id', $item->customer_id)
                            ->where('deleteflag', 'active')
                            ->first();
                        $companyName = $company ? $company->comp_name : '';
                    }
                }
                
                // Format amount to match UI (just numeric value without currency symbol)
                $amount = 0;
                
                // For proforma invoices, use the proforma_invoice_total_view function
                if ($item->record_type === 'proforma_invoice' && isset($item->O_Id)) {
                    // Check if the function exists and call it
                    if (function_exists('proforma_invoice_total_view')) {
                        $amount = proforma_invoice_total_view($item->O_Id);
                    } else {
                        // Fallback: calculate manually or use a default
                        $amount = 0;
                    }
                } else {
                    // For other record types, use the approval_amount field
                    $amount = $item->approval_amount ?? 0;
                }
                
                $formattedAmount = is_numeric($amount) ? number_format(round($amount, 2), 0) : '0';
                
                return [
                    'id' => $item->id,
                    'record_type' => $item->record_type,
                    'requested_on' => date('d M Y', strtotime($item->requested_on)),
                    'approval_for' => $item->approval_for,
                    'customer_name' => $companyName ?: 'Unknown Company',
                    'account_manager' => function_exists('admin_name') ? admin_name($item->account_manager_id) : 'Unknown',
                    'approval_amount' => $formattedAmount,
                    'document_ref' => '-', // Would need additional logic to get document references
                    'status' => $displayStatus,
                    'status_badge' => $this->getStatusBadgeInfo($displayStatus),
                    'remarks' => $item->remarks ?? '',
                    'available_actions' => $this->getAvailableActions($displayStatus)
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'pagination' => [
                    'current_page' => (int) $page,
                    'page_size' => (int) $pageSize,
                    'total_records' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $pageSize)
                ],
                'summary' => $this->getApprovalSummary(),
                'filter_options' => $this->getApprovalFilterOptions()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching unified approvals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update approval status (approve/reject/pending/docs_required/revoke)
     */
    public function updateApprovalStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'record_id' => 'required|integer',
                'record_type' => 'required|in:pre_approval,proforma_invoice,tax_invoice,credit_note',
                'action' => 'required|in:approve,reject,pending,docs_required,revoke',
                'remarks' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation Error!',
                    'data' => $validator->errors()
                ], 400);
            }

            $recordId = $request->record_id;
            $recordType = $request->record_type;
            $action = $request->action;
            $remarks = $request->remarks;
            $adminId = 1; // TODO: Get from JWT token - auth()->user()->id

            $result = null;

            switch ($recordType) {
                case 'pre_approval':
                    $result = $this->updatePreApprovalStatus($recordId, $action, $remarks, $adminId);
                    break;
                case 'proforma_invoice':
                    $result = $this->updateProformaInvoiceApprovalStatus($recordId, $action, $remarks, $adminId);
                    break;
                case 'tax_invoice':
                    $result = $this->updateTaxInvoiceApprovalStatus($recordId, $action, $remarks, $adminId);
                    break;
                case 'credit_note':
                    $result = $this->updateCreditNoteApprovalStatus($recordId, $action, $remarks, $adminId);
                    break;
                default:
                    throw new \Exception('Invalid record type');
            }

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Approval status updated successfully',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Record not found or update failed'
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error updating approval status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending approvals summary for dashboard cards
     */
    public function getPendingApprovalsSummary(Request $request)
    {
        try {
            $summary = $this->getApprovalSummary();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Pending approvals summary retrieved successfully',
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error retrieving pending approvals summary: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper methods for approval functionality

    private function mapUiStatusToDb($uiStatus, $type)
    {
        // Fixed mapping based on actual database status values
        $mapping = [
            'PENDING' => 'pending',
            'APPROVED' => 'approved', 
            'REJECTED' => 'reject',
            'DOCS_REQUIRED' => 'docs_required' // Use actual docs_required value, don't map to pending
        ];

        return $mapping[$uiStatus] ?? strtolower($uiStatus);
    }

    private function normalizeStatusForDisplay($status)
    {
        $statusMap = [
            'pending' => 'PENDING',
            'approved' => 'APPROVED',
            'reject' => 'REJECTED',
            'PENDING' => 'PENDING',
            'APPROVED' => 'APPROVED',
            'REJECTED' => 'REJECTED',
            'DOCS_REQUIRED' => 'DOCS_REQUIRED',
            'EXPIRED' => 'EXPIRED'
        ];

        return $statusMap[$status] ?? strtoupper($status);
    }

    private function getStatusBadgeInfo($status)
    {
        $badges = [
            'PENDING' => ['class' => 'warning', 'color' => '#FFA500', 'text' => 'PENDING'],
            'DOCS_REQUIRED' => ['class' => 'info', 'color' => '#17a2b8', 'text' => 'DOCS_REQUIRED'],
            'APPROVED' => ['class' => 'success', 'color' => '#28a745', 'text' => 'APPROVED'],
            'REJECTED' => ['class' => 'danger', 'color' => '#dc3545', 'text' => 'REJECTED'],
            'EXPIRED' => ['class' => 'secondary', 'color' => '#6c757d', 'text' => 'EXPIRED']
        ];

        return $badges[$status] ?? ['class' => 'secondary', 'color' => '#6c757d', 'text' => $status];
    }

    private function getAvailableActions($status)
    {
        $actions = [
            'PENDING' => [
                ['value' => 'approve', 'label' => 'Approve'],
                ['value' => 'reject', 'label' => 'Reject'],
                ['value' => 'docs_required', 'label' => 'Documents required']
            ],
            'DOCS_REQUIRED' => [
                ['value' => 'approve', 'label' => 'Approve'],
                ['value' => 'reject', 'label' => 'Reject'],
                ['value' => 'pending', 'label' => 'Mark as Pending']
            ],
            'APPROVED' => [
                ['value' => 'revoke', 'label' => 'Revoke']
            ],
            'REJECTED' => [
                ['value' => 'pending', 'label' => 'Reset to Pending']
            ],
            'EXPIRED' => [
                ['value' => 'pending', 'label' => 'Reset to Pending']
            ]
        ];

        return $actions[$status] ?? [];
    }

    private function updatePreApprovalStatus($id, $action, $remarks, $adminId)
    {
        $statusMap = [
            'approve' => 'APPROVED',
            'reject' => 'REJECTED',
            'pending' => 'PENDING',
            'docs_required' => 'DOCS_REQUIRED',
            'revoke' => 'REJECTED'
        ];

        $updateData = [
            'approval_status' => $statusMap[$action],
            'notes' => $remarks
        ];

        if ($action === 'approve') {
            $updateData['approved_by'] = $adminId;
        }

        $updated = DB::table('pre_approval_credit_requests')
            ->where('id', $id)
            ->where('deleteflag', 'active')
            ->update($updateData);

        return $updated ? [
            'record_type' => 'pre_approval',
            'record_id' => $id,
            'new_status' => $statusMap[$action],
            'action_by' => function_exists('admin_name') ? admin_name($adminId) : 'Admin',
            'remarks' => $remarks
        ] : null;
    }

    private function updateProformaInvoiceApprovalStatus($id, $action, $remarks, $adminId)
    {
        $statusMap = [
            'approve' => 'approved',
            'reject' => 'reject',
            'pending' => 'pending',
            'docs_required' => 'pending', // PI doesn't have docs_required status
            'revoke' => 'reject'
        ];

        $updateData = [
            'pi_status' => $statusMap[$action],
            'approval_remarks' => $remarks
        ];

        if ($action === 'approve') {
            $updateData['approved_by'] = $adminId;
            $updateData['approved_on'] = now();
        }

        $updated = DB::table('tbl_performa_invoice')
            ->where('pi_id', $id)
            ->where('deleteflag', 'active')
            ->update($updateData);

        return $updated ? [
            'record_type' => 'proforma_invoice',
            'record_id' => $id,
            'new_status' => $statusMap[$action],
            'action_by' => function_exists('admin_name') ? admin_name($adminId) : 'Admin',
            'remarks' => $remarks
        ] : null;
    }

    private function updateTaxInvoiceApprovalStatus($id, $action, $remarks, $adminId)
    {
        $statusMap = [
            'approve' => 'approved',
            'reject' => 'reject',
            'pending' => 'pending',
            'docs_required' => 'pending', // Tax invoice doesn't have docs_required
            'revoke' => 'reject'
        ];

        $updateData = [
            'invoice_status' => $statusMap[$action],
            'invoice_approval_status' => $statusMap[$action],
            'approval_remarks' => $remarks,
            'updated_at' => now()
        ];

        if ($action === 'approve') {
            $updateData['approved_by'] = $adminId;
            $updateData['approved_on'] = now();
        }

        $updated = DB::table('tbl_tax_invoice')
            ->where('invoice_id', $id)
            ->where('deleteflag', 'active')
            ->update($updateData);

        return $updated ? [
            'record_type' => 'tax_invoice',
            'record_id' => $id,
            'new_status' => $statusMap[$action],
            'action_by' => function_exists('admin_name') ? admin_name($adminId) : 'Admin',
            'remarks' => $remarks
        ] : null;
    }

    private function updateCreditNoteApprovalStatus($id, $action, $remarks, $adminId)
    {
        $statusMap = [
            'approve' => 'approved',
            'reject' => 'reject',
            'pending' => 'pending',
            'docs_required' => 'pending',
            'revoke' => 'reject'
        ];

        $updateData = [
            'invoice_status' => $statusMap[$action],
            'invoice_approval_status' => $statusMap[$action],
            'approval_remarks' => $remarks,
            'updated_at' => now()
        ];

        if ($action === 'approve') {
            $updateData['approved_by'] = $adminId;
            $updateData['approved_on'] = now();
        }

        $updated = DB::table('tbl_tax_credit_note_invoice')
            ->where('credit_note_invoice_id', $id)
            ->where('deleteflag', 'active')
            ->update($updateData);

        return $updated ? [
            'record_type' => 'credit_note',
            'record_id' => $id,
            'new_status' => $statusMap[$action],
            'action_by' => function_exists('admin_name') ? admin_name($adminId) : 'Admin',
            'remarks' => $remarks
        ] : null;
    }

    private function getApprovalSummary()
    {
        try {
            $pending = 0;
            $approved = 0;
            $rejected = 0;
            $docsRequired = 0;
            $expired = 0;

            // Pre-approval requests
            $preApprovalCounts = DB::table('pre_approval_credit_requests')
                ->where('deleteflag', 'active')
                ->selectRaw('approval_status, COUNT(*) as count')
                ->groupBy('approval_status')
                ->pluck('count', 'approval_status')
                ->toArray();

            // Proforma invoices
            $proformaCounts = DB::table('tbl_performa_invoice')
                ->where('deleteflag', 'active')
                ->selectRaw('pi_status, COUNT(*) as count')
                ->groupBy('pi_status')
                ->pluck('count', 'pi_status')
                ->toArray();

            // Tax invoices
            $invoiceCounts = DB::table('tbl_tax_invoice')
                ->where('deleteflag', 'active')
                ->selectRaw('invoice_status, COUNT(*) as count')
                ->groupBy('invoice_status')
                ->pluck('count', 'invoice_status')
                ->toArray();

            // Credit notes
            $creditNoteCounts = DB::table('tbl_tax_credit_note_invoice')
                ->where('deleteflag', 'active')
                ->selectRaw('invoice_status, COUNT(*) as count')
                ->groupBy('invoice_status')
                ->pluck('count', 'invoice_status')
                ->toArray();

            // Aggregate counts
            $pending = ($preApprovalCounts['PENDING'] ?? 0) + 
                      ($proformaCounts['pending'] ?? 0) + 
                      ($invoiceCounts['pending'] ?? 0) + 
                      ($creditNoteCounts['pending'] ?? 0);

            $approved = ($preApprovalCounts['APPROVED'] ?? 0) + 
                       ($proformaCounts['approved'] ?? 0) + 
                       ($invoiceCounts['approved'] ?? 0) + 
                       ($creditNoteCounts['approved'] ?? 0);

            $rejected = ($preApprovalCounts['REJECTED'] ?? 0) + 
                       ($proformaCounts['reject'] ?? 0) + 
                       ($invoiceCounts['reject'] ?? 0) + 
                       ($creditNoteCounts['reject'] ?? 0);

            $docsRequired = $preApprovalCounts['DOCS_REQUIRED'] ?? 0;
            $expired = $preApprovalCounts['EXPIRED'] ?? 0;

            return [
                'pending_requests' => $pending,
                'approved_requests' => $approved,
                'rejected_requests' => $rejected,
                'docs_required' => $docsRequired,
                'expired_revoked_requests' => $expired
            ];

        } catch (\Exception $e) {
            return [
                'pending_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'docs_required' => 0,
                'expired_revoked_requests' => 0
            ];
        }
    }

    private function getApprovalFilterOptions()
    {
        try {
            // Calculate current financial year
            $currentDate = now();
            $currentYear = $currentDate->year;
            $currentMonth = $currentDate->month;
            
            if ($currentMonth >= 4) {
                $currentFY = $currentYear . '-' . ($currentYear + 1);
            } else {
                $currentFY = ($currentYear - 1) . '-' . $currentYear;
            }

            // Get list of account managers
            $accountManagers = DB::table('tbl_admin')
                ->select('admin_id as id', 'admin_name as name')
                ->where('deleteflag', 'active')
                ->get()
                ->map(function($admin) {
                    return ['value' => $admin->id, 'label' => $admin->name];
                })
                ->prepend(['value' => 'All', 'label' => 'All'])
                ->toArray();

            return [
                'statuses' => [
                    ['value' => 'All', 'label' => 'All'],
                    ['value' => 'PENDING', 'label' => 'Pending'],
                    ['value' => 'DOCS_REQUIRED', 'label' => 'Documents required'],
                    ['value' => 'APPROVED', 'label' => 'Approve'],
                    ['value' => 'REJECTED', 'label' => 'Reject'],
                    ['value' => 'REVOKED', 'label' => 'Revoke']
                ],
                'approval_types' => [
                    ['value' => 'All', 'label' => 'All'],
                    ['value' => 'Credit_Pre_approval', 'label' => 'Credit'],
                    ['value' => 'Proforma_invoice', 'label' => 'Proforma Invoice'],
                    ['value' => 'Invoice', 'label' => 'Invoice'],
                    ['value' => 'Credit_note', 'label' => 'Credit note']
                ],
                'account_managers' => $accountManagers,
                'financial_years' => [
                    ['value' => ($currentYear - 2) . '-' . ($currentYear - 1), 'label' => ($currentYear - 2) . '-' . ($currentYear - 1)],
                    ['value' => ($currentYear - 1) . '-' . $currentYear, 'label' => ($currentYear - 1) . '-' . $currentYear],
                    ['value' => $currentFY, 'label' => $currentFY, 'default' => true], // Mark current FY as default
                    ['value' => ($currentYear + 1) . '-' . ($currentYear + 2), 'label' => ($currentYear + 1) . '-' . ($currentYear + 2)]
                ],
                'current_financial_year' => $currentFY // Provide current FY for frontend reference
            ];
        } catch (\Exception $e) {
            return [
                'statuses' => [],
                'approval_types' => [],
                'account_managers' => [['value' => 'All', 'label' => 'All']],
                'financial_years' => [['value' => '2025-2026', 'label' => '2025-2026']],
                'current_financial_year' => '2025-2026'
            ];
        }
    }
}
