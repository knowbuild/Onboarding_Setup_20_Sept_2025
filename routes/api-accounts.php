<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Accounts\{
    FinanceController,
    VendorPaymentsFinanceController,
    AccountsDashboardController,
    InvoiceDisputeController,
    VendorPaymentDisputeController,
    InvoicingController,
    CreditControlController_Simple
};

Route::prefix('finance')->group(function () {
    Route::post('payment-received', [FinanceController::class, 'createPaymentDetail']);
    // Route::get('payment-received-by-aging', [FinanceController::class testing, 'payment_received_by_aging']);
    // Route::get('pending-account-receivables', [FinanceController::class, 'pending_account_receivables']);
    Route::get('pending-account-receivables-by-aging', [FinanceController::class, 'pending_account_receivables_by_aging']);
    // Route::get('pending-account-receivables-by-aging-not-yet-due', [FinanceController::class, 'pending_account_receivables_by_aging_not_yet_due']);
    Route::get('receivable-pie-series', [FinanceController::class, 'receivablePieSeries']);
    Route::get('topReceivables-Account-manager', [FinanceController::class, 'topReceivables']);
    Route::get('accounts-receivables', [FinanceController::class, 'accountsReceivables']);
    Route::get('accounts-receivables-aging-dashboard', [FinanceController::class, 'accountsReceivablesAgingDashboard']);
    Route::get('overview', [FinanceController::class, 'Overview']);
    Route::get('collections', [FinanceController::class, 'Collections']);
    Route::get('short-term-receivables', [FinanceController::class, 'ShortTermReceivables']);
    Route::get('overdue-receivables', [FinanceController::class, 'overdueReceivables']);
    Route::get('disputed-receivables', [FinanceController::class, 'DisputedReceivables']);
    Route::get('receivable-detail', [FinanceController::class, 'receivableDetail']);
    Route::get('receivable-detail-v2', [FinanceController::class, 'receivableDetailV2']);
    Route::post('update-payment-detail', [FinanceController::class, 'updatePaymentDetail']);
    Route::post('delete-payment-detail', [FinanceController::class, 'deletePaymentDetail']);

    Route::post('save-payment-remark', [FinanceController::class, 'savePaymentRemark']);
    Route::get('promised-payment-remark-notifications', [FinanceController::class, 'promisedPaymentRemarkNotifications']);
    //Vendor payments ven_payments.php page

    // For monthly paid payables
    Route::get('total-paid', [VendorPaymentsFinanceController::class, 'paymentsPaidThisYear']);

    // For pending payables
    Route::get('pending-payable', [VendorPaymentsFinanceController::class, 'pendingAccountPayables']);
    Route::get('pending-account-payables-by-aging', [VendorPaymentsFinanceController::class, 'pendingAccountPayablesByAging']);
    Route::get('disputed-amounts-by-aging', [VendorPaymentsFinanceController::class, 'disputedAmountsByAging']);
    Route::get('accounts-payables-aging-dashboard', [VendorPaymentsFinanceController::class, 'accountsPayableAgingDashboard']);
    Route::get('pending-payables-heatmap', [VendorPaymentsFinanceController::class, 'pendingAccountPayablesHeatmap']);
    Route::get('accounts-payables-snapshot', [VendorPaymentsFinanceController::class, 'accountsPayableSnapshot']);



	
	
	
//Vendor payments ven_payments.php page
	
// For monthly paid payables
Route::get('total-paid', [VendorPaymentsFinanceController::class, 'paymentsPaidThisYear']);


    // Accounts payable listing - matches the UI screenshot
    Route::get('accounts-payables-listing', [VendorPaymentsFinanceController::class, 'accountsPayableListing']);

    // Due in tabs summary for dashboard
    Route::get('accounts-payables-due-in-summary', [VendorPaymentsFinanceController::class, 'accountsPayableDueInSummary']);

    // Purchase order payment details - detailed view with payment history
    Route::get('purchase-order-payment-details', [VendorPaymentsFinanceController::class, 'purchaseOrderPaymentDetails']);

    // Specialized function for not yet due payments
    Route::get('not-yet-due-payments', [VendorPaymentsFinanceController::class, 'notYetDuePayments']);

    // Not yet due summary - matches UI filter in screenshot
    Route::get('not-yet-due-summary', [VendorPaymentsFinanceController::class, 'notYetDueSummary']);


    // Invoices payable after 90+ days (long-term planning)
    Route::get('invoices-payable-after-90-days', [VendorPaymentsFinanceController::class, 'invoicesPayableAfter90Days']);

// Purchase order payment details - detailed view with payment history
//Route::get('purchase-order-payment-details', [VendorPaymentsFinanceController::class, 'purchaseOrderPaymentDetails']);

// Specialized function for not yet due payments
//Route::get('not-yet-due-payments', [VendorPaymentsFinanceController::class, 'notYetDuePayments']);

// Not yet due summary - matches UI filter in screenshot
//Route::get('not-yet-due-summary', [VendorPaymentsFinanceController::class, 'notYetDueSummary']);

// Invoices payable after 90+ days (long-term planning)
//Route::get('invoices-payable-after-90-days', [VendorPaymentsFinanceController::class, 'invoicesPayableAfter90Days']);

// Payment types master data - for dropdowns and selections
Route::get('payment-types', [VendorPaymentsFinanceController::class, 'getPaymentTypes']);


	

});
// accunts dashboard routes
Route::prefix('dashboard')->group(function () {
    
    // Simple test endpoint
    Route::get('test', function() {
        return response()->json(['status' => 'success', 'message' => 'Dashboard API is working', 'timestamp' => now()]);
    });
    
    // Invoicing Dashboard - Main dashboard API
    // GET /api/accounts/dashboard/invoicing
    Route::get('invoicing', [AccountsDashboardController::class, 'getInvoicingDashboard']);
    
    // Invoicing Breakdown - Detailed breakdown by period (daily/weekly/monthly)  
    // GET /api/accounts/dashboard/invoicing-breakdown
    Route::get('invoicing-breakdown', [AccountsDashboardController::class, 'getInvoicingBreakdown']);
    
    // Receivable Follow-ups - Based on receivable-follow-up.php
    // GET /api/accounts/dashboard/receivable-follow-ups
    Route::get('receivable-follow-ups', [AccountsDashboardController::class, 'getReceivableFollowUps']);
    
    // Follow-up Details - Get detailed payment and remarks for specific invoice
    // GET /api/accounts/dashboard/follow-up-details
    Route::get('follow-up-details', [AccountsDashboardController::class, 'getFollowUpDetails']);
    
    // Promised Payments - payments where a promised date exists in tbl_payment_remarks.payment_promised_on
    // GET /api/accounts/dashboard/promised-payments
    Route::get('promised-payments', [AccountsDashboardController::class, 'getPromisedPayments']);
    
});

// Credit Control Management Routes
Route::prefix('credit-control')->group(function () {
    // Test route for controller
    Route::get('test-controller', [CreditControlController_Simple::class, 'testController']);
    
    // Test route
    Route::get('test', function() {
        return response()->json([
            'success' => true,
            'message' => 'Credit Control API is working!',
            'timestamp' => now(),
            'routes' => [
                'proforma-invoices' => '/api/accounts/credit-control/proforma-invoices',
                'invoices' => '/api/accounts/credit-control/invoices',
                'credit-notes' => '/api/accounts/credit-control/credit-notes',
                'approvals' => '/api/accounts/credit-control/approvals',
                'filter-options' => '/api/accounts/credit-control/filter-options',
                'dashboard' => '/api/accounts/credit-control/dashboard'
            ]
        ]);
    });
    
    // Get Proforma Invoices listing with filters
    // GET /api/accounts/credit-control/proforma-invoices
    Route::get('proforma-invoices', [CreditControlController_Simple::class, 'getProformaInvoicesList']);
    
    // Get Invoices listing with filters  
    // GET /api/accounts/credit-control/invoices
    Route::get('invoices', [CreditControlController_Simple::class, 'getInvoicesList']);
    
    // Get Credit Note Invoices listing with filters
    // GET /api/accounts/credit-control/credit-notes
    Route::get('credit-notes', [CreditControlController_Simple::class, 'getCreditNoteInvoicesList']);
    
    // Get Credit Control Approvals (Pre-approval credit requests)
    // GET /api/accounts/credit-control/approvals
    Route::get('approvals', [CreditControlController_Simple::class, 'getCreditControlApprovals']);
    
    // Get filter options for all modules
    // GET /api/accounts/credit-control/filter-options
    Route::get('filter-options', [CreditControlController_Simple::class, 'getCreditControlFilterOptions']);
    
    // Get credit control dashboard summary
    // GET /api/accounts/credit-control/dashboard
    Route::get('dashboard', [CreditControlController_Simple::class, 'getCreditControlDashboard']);
    
    // Get all pending requests from all sources (consolidated view) - NEW UNIFIED APPROACH
    // GET /api/accounts/credit-control/pending-requests
    Route::get('pending-requests', [CreditControlController_Simple::class, 'getUnifiedApprovalsList']);
    
    // Update approval status (approve/reject/request docs) - ENHANCED
    // PUT /api/accounts/credit-control/approval-status
    Route::put('approval-status', [CreditControlController_Simple::class, 'updateApprovalStatus']);
    
    // Get pending approvals summary for dashboard cards - NEW
    // GET /api/accounts/credit-control/pending-summary
    Route::get('pending-summary', [CreditControlController_Simple::class, 'getPendingApprovalsSummary']);

});

// Invoice Dispute Management Routes - Test without middleware
Route::prefix('disputes-test')->group(function () {
    
    // Test endpoint without middleware
    // POST /api/accounts/disputes-test/mark-as-disputed
    Route::post('mark-as-disputed', [InvoiceDisputeController::class, 'markAsDisputedNoAuth']);
    
    // Remove from disputed endpoint without middleware
    // POST /api/accounts/disputes-test/remove-from-disputed
    Route::post('remove-from-disputed', [InvoiceDisputeController::class, 'removeFromDisputedNoAuth']);
});

// Invoice Dispute Management Routes
Route::prefix('disputes')->group(function () {
    
    // Test endpoint
    // POST /api/accounts/disputes/test
    Route::post('test', [InvoiceDisputeController::class, 'test']);
    
    // Hybrid approach test endpoint
    // POST /api/accounts/disputes/test-hybrid
    Route::post('test-hybrid', [InvoiceDisputeController::class, 'testHybrid']);
    
    // Debug endpoint for troubleshooting
    // POST /api/accounts/disputes/mark-disputed-debug
    Route::post('mark-disputed-debug', [InvoiceDisputeController::class, 'markAsDisputedDebug']);
    
    // Debug authentication
    // POST /api/accounts/disputes/debug-auth  
    Route::post('debug-auth', [InvoiceDisputeController::class, 'debugAuth']);
    
    // Simple test endpoint for request parsing
    // POST /api/accounts/disputes/test-simple
    Route::post('test-simple', [InvoiceDisputeController::class, 'testSimple']);
    
    // Step-by-step debug version
    // POST /api/accounts/disputes/mark-disputed-step
    Route::post('mark-disputed-step', [InvoiceDisputeController::class, 'markAsDisputedStep']);
    
    // Working main endpoint
    // POST /api/accounts/disputes/mark-disputed-working
    Route::post('mark-disputed-working', [InvoiceDisputeController::class, 'markAsDisputedWorking']);
    
    // Remove from disputed main endpoint
    // POST /api/accounts/disputes/remove-from-disputed
    Route::post('remove-from-disputed', [InvoiceDisputeController::class, 'removeFromDisputed']);
    
    // Main UI workflow - Mark invoice as disputed with confirmation popup
    // POST /api/accounts/disputes/mark-as-disputed
    Route::post('mark-as-disputed', [InvoiceDisputeController::class, 'markAsDisputed']);
    
    // Remove dispute status (undo dispute)
    // POST /api/accounts/disputes/remove-dispute
    Route::post('remove-dispute', [InvoiceDisputeController::class, 'removeDispute']);
    
    // Check if invoice can be disputed (for UI validation)
    // GET /api/accounts/disputes/can-dispute/{invoice_id}
    Route::get('can-dispute/{invoice_id}', [InvoiceDisputeController::class, 'canDispute']);
    
    // Mark entire invoice as disputed (advanced workflow)
    // POST /api/accounts/disputes/mark-invoice-disputed
    Route::post('mark-invoice-disputed', [InvoiceDisputeController::class, 'markInvoiceAsDisputed']);
    
    // Mark specific payment as disputed
    // POST /api/accounts/disputes/mark-payment-disputed  
    Route::post('mark-payment-disputed', [InvoiceDisputeController::class, 'markPaymentAsDisputed']);
    
    // Get list of all disputed invoices/payments
    // GET /api/accounts/disputes/list
    Route::get('list', [InvoiceDisputeController::class, 'getDisputedInvoices']);
    
    // Get disputed receivables list (UI format)
    // GET /api/accounts/disputes/receivables-list
    Route::get('receivables-list', [InvoiceDisputeController::class, 'getDisputedReceivablesList']);
    
    // Get dispute details by ID
    // GET /api/accounts/disputes/{disputeId}/details
    Route::get('{disputeId}/details', [InvoiceDisputeController::class, 'getDisputeDetails']);
    
    // Update dispute details (full edit)
    // PUT /api/accounts/disputes/{disputeId}/update
    Route::put('{disputeId}/update', [InvoiceDisputeController::class, 'updateDispute']);
    
    // Update dispute remarks and tracking
    // PUT /api/accounts/disputes/{disputeId}/update-remarks
    Route::put('{disputeId}/update-remarks', [InvoiceDisputeController::class, 'updateDisputeRemarks']);
    
    // Soft delete a dispute
    // DELETE /api/accounts/disputes/{disputeId}/delete
    Route::delete('{disputeId}/delete', [InvoiceDisputeController::class, 'deleteDispute']);
    
    // Restore a soft-deleted dispute
    // PUT /api/accounts/disputes/{disputeId}/restore
    Route::put('{disputeId}/restore', [InvoiceDisputeController::class, 'restoreDispute']);
    
    // Get deleted disputes for admin review
    // GET /api/accounts/disputes/deleted
    Route::get('deleted', [InvoiceDisputeController::class, 'getDeletedDisputes']);
    
    // Resolve a dispute
    // PUT /api/accounts/disputes/{disputeId}/resolve
    Route::put('{disputeId}/resolve', [InvoiceDisputeController::class, 'resolveDispute']);
    
    // Cancel a dispute
    // PUT /api/accounts/disputes/{disputeId}/cancel
    Route::put('{disputeId}/cancel', [InvoiceDisputeController::class, 'cancelDispute']);
    
});

// Vendor Payment Dispute Management Routes 
Route::prefix('vendor-disputes')->group(function () {
    
    // Test endpoint for debugging request data
    // POST /api/accounts/vendor-disputes/test-data
    Route::post('test-data', [VendorPaymentDisputeController::class, 'testData']);
    
    // Mark vendor payment as disputed
    // POST /api/accounts/vendor-disputes/mark-disputed
    Route::post('mark-disputed', [VendorPaymentDisputeController::class, 'markDisputed']);
    
    // Remove dispute status from vendor payment
    // POST /api/accounts/vendor-disputes/remove-disputed
    Route::post('remove-disputed', [VendorPaymentDisputeController::class, 'removeDisputed']);
    
    // Get list of all disputed vendor payments with filtering and pagination
    // GET /api/accounts/vendor-disputes/list
    // Query parameters: vendor_id, currency, disputed_by, financial_year, due_in, search, pageno, records, sort_key, sort_value
    Route::get('list', [VendorPaymentDisputeController::class, 'getDisputedPayments']);
    
    // Get filter options for the disputed payments UI
    // GET /api/accounts/vendor-disputes/filter-options
    Route::get('filter-options', [VendorPaymentDisputeController::class, 'getFilterOptions']);
    
    // Export disputed payments to CSV
    // GET /api/accounts/vendor-disputes/export
    // Query parameters: vendor_id, currency, disputed_by, financial_year, due_in, search
    Route::get('export', [VendorPaymentDisputeController::class, 'exportDisputedPayments']);
    
});

// Invoicing Management Routes
Route::prefix('invoicing')->group(function () {
    
    // Test route  
    Route::get('test', [InvoicingController::class, 'test']);
    

    
    // Get filter options for dropdowns
    // GET /api/accounts/invoicing/filters
    Route::get('filters', [InvoicingController::class, 'getFilterOptions']);
    
    // Get invoices list with advanced filtering and pagination
    // GET /api/accounts/invoicing/list
    // Query parameters: page, records, sort_key, sort_value, order_id, company_name, account_manager, 
    // product_name, invoice_type, irn_status, invoice_number, status, date_from, date_to, serial_number
    Route::get('list', [InvoicingController::class, 'getInvoicesList']);
    
    // Get invoicing dashboard summary
    // GET /api/accounts/invoicing/dashboard
    // Query parameters: financial_year
    Route::get('dashboard', [InvoicingController::class, 'getDashboardSummary']);
    
    // Get invoice details by ID
    // GET /api/accounts/invoicing/{invoice_id}
    Route::get('{invoice_id}', [InvoicingController::class, 'getInvoiceDetails']);
    
    // Generate IRN for invoice
    // POST /api/accounts/invoicing/{invoice_id}/generate-irn
    // Body: { "generate_eway_bill": boolean }
    Route::post('{invoice_id}/generate-irn', [InvoicingController::class, 'generateIRN']);
    
    // Export invoices to Excel
    // POST /api/accounts/invoicing/export
    // Body: same filter parameters as list endpoint
    Route::post('export', [InvoicingController::class, 'exportInvoices']);
    
    // Create a new invoice with products and details
    // POST /api/accounts/invoicing/create
    // Body: JSON payload with invoice details
    
    
    // Download exported file
    // GET /api/accounts/invoicing/download-export/{filename}
    Route::get('download-export/{filename}', function($filename) {
        $filepath = storage_path('app/exports/' . $filename);
        
        if (!file_exists($filepath)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Export file not found'
            ], 404);
        }
        
        return response()->download($filepath);
    });

});

Route::prefix('invoicing-details')->group(function () {
    // Get invoices list with filtering and pagination
    // GET /api/accounts/invoicing-details/list
    // Query params: page, records, sort_key, sort_value, company_name, invoice_number, 
    // account_manager, product_name, product_category, invoice_status, date_from, date_to, financial_year
    Route::get('list', [InvoicingController::class, 'getInvoicesDetailList']);
    Route::get('list-with-summary', [InvoicingController::class, 'listWithSummary']);

    Route::post('create', [InvoicingController::class, 'createInvoice']);

    // Get filter options for dropdowns
    // GET /api/accounts/invoicing-details/filters
    Route::get('filters', [InvoicingController::class, 'getFilterOptions']);
    
    // Test endpoint to check filters
    Route::get('test-filters', function(Request $request) {
        return response()->json([
            'status' => 'success',
            'message' => 'Filters received',
            'filters' => $request->all()
        ]);
    });
    
    Route::get('get-invoicing-list', [InvoicingController::class, 'getInvoicesDetailList']);
    Route::get('invoicing-filters', [InvoicingController::class, 'getInvoicingFilters']);
});

