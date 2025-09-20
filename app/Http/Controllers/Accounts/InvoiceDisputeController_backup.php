<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\InvoiceDispute;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class InvoiceDisputeController extends Controller
{
    /**
     * Mark invoice as disputed - Main UI workflow from invoice management
     * This is triggered from "Mark as disputed" button with confirmation popup
     * POST /api/accounts/disputes/mark-as-disputed
     */
    public function markAsDisputed(Request $request)
    {
        try {
            // Extract data using multiple methods (same as working test endpoint)
            $invoiceId = $request->invoice_id ?? 
                        $request->input('invoice_id') ?? 
                        ($request->json() ? $request->json('invoice_id') : null);

            $confirmation = $request->confirmation ?? 
                           $request->input('confirmation') ?? 
                           ($request->json() ? $request->json('confirmation') : null);

            $disputeReason = $request->dispute_reason ?? 
                            $request->input('dispute_reason') ?? 
                            ($request->json() ? $request->json('dispute_reason') : null);

            // If still no data, try to parse JSON manually
            if (!$invoiceId) {
                $rawContent = $request->getContent();
                if ($rawContent) {
                    $jsonData = json_decode($rawContent, true);
                    if ($jsonData) {
                        $invoiceId = $jsonData['invoice_id'] ?? null;
                        $confirmation = $jsonData['confirmation'] ?? null;
                        $disputeReason = $jsonData['dispute_reason'] ?? null;
                    }
                }
            }

            // Validation
            if (!$invoiceId) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice ID is required'
                ], 400);
            }

            if (!$confirmation) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Confirmation is required to proceed with dispute marking!'
                ], 400);
            }

        try {
            // Get invoice details with company info
            $invoice = DB::table('tbl_tax_invoice as inv')
                ->leftJoin('tbl_comp as comp', 'inv.comp_id', '=', 'comp.comp_id')
                ->select([
                    'inv.*',
                    'comp.comp_name',
                    'comp.comp_city'
                ])
                ->where('inv.invoice_id', $invoiceId)
                ->where('inv.deleteflag', 'active')
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice not found!'
                ], 404);
            }

            // Check if already disputed
            $existingDispute = InvoiceDispute::where('invoice_id', $invoiceId)
                ->where('dispute_status', 'active')
                ->where('deleteflag', 'active')
                ->first();

            if ($existingDispute) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice #' . $invoice->invoice_no . ' is already marked as disputed!',
                    'data' => [
                        'disputed_on' => $existingDispute->disputed_date,
                        'dispute_id' => $existingDispute->dispute_id
                    ]
                ], 400);
            }

            // Calculate amounts from tax invoice
            $totalAmount = ($invoice->freight_amount ?? 0) + 
                          ($invoice->sub_total_amount_without_gst ?? 0) + 
                          ($invoice->total_gst_amount ?? 0);
            
            // If above fields are null, try total_amount field
            if ($totalAmount == 0) {
                $totalAmount = $invoice->total_amount ?? 0;
            }

            $totalPaid = DB::table('tbl_payment_received')
                ->where('invoice_id', $invoiceId)
                ->where('deleteflag', 'active')
                ->sum('payment_received_value') ?? 0;

            $remainingBalance = $totalAmount - $totalPaid;

            // Create dispute record
            $dispute = InvoiceDispute::create([
                'invoice_id' => $invoiceId,
                'dispute_type' => 'full_invoice',
                'disputed_amount' => $remainingBalance > 0 ? $remainingBalance : $totalAmount,
                'dispute_reason' => $disputeReason ?? 'Invoice marked as disputed from invoice management',
                'dispute_status' => 'active',
                'disputed_by' => auth()->id() ?? 1,
                'disputed_date' => now(),
                'deleteflag' => 'active'
            ]);

            // Get admin name
            $adminName = auth()->id() ? admin_name(auth()->id()) : 'System Admin';

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice has been marked as disputed successfully!',
                'data' => [
                    'dispute_id' => $dispute->dispute_id,
                    'invoice_no' => $invoice->invoice_no,
                    'company_name' => $invoice->comp_name,
                    'total_amount' => $totalAmount,
                    'amount_paid' => $totalPaid,
                    'disputed_amount' => $dispute->disputed_amount,
                    'disputed_by' => $adminName,
                    'disputed_on' => now()->format('d M Y, h:i A'),
                    'ui_message' => 'This invoice will now appear only in the Disputed section of the Accounts Receivable page.'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to mark invoice as disputed!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simple test endpoint to check if API is working
     * POST /api/accounts/disputes/test
     */
    public function test(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'API is working!',
            'received_data' => $request->all(),
            'request_details' => [
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
                'raw_content' => $request->getContent(),
                'input_all' => $request->input(),
                'has_invoice_id' => $request->has('invoice_id'),
                'has_confirmation' => $request->has('confirmation'),
                'get_invoice_id' => $request->get('invoice_id'),
                'get_confirmation' => $request->get('confirmation'),
                'json_data' => $request->json()->all() ?? 'No JSON data'
            ],
            'timestamp' => now()
        ], 200);
    }

    /**
     * Mark as disputed without auth requirements (for testing)
     * POST /api/accounts/disputes-test/mark-as-disputed
     */
    public function markAsDisputedNoAuth(Request $request)
    {
        try {
            // Extract data using multiple methods
            $invoiceId = $request->invoice_id ?? 
                        $request->input('invoice_id') ?? 
                        ($request->json() ? $request->json('invoice_id') : null);

            $confirmation = $request->confirmation ?? 
                           $request->input('confirmation') ?? 
                           ($request->json() ? $request->json('confirmation') : null);

            $disputeReason = $request->dispute_reason ?? 
                            $request->input('dispute_reason') ?? 
                            ($request->json() ? $request->json('dispute_reason') : null);

            // If still no data, try to parse JSON manually
            if (!$invoiceId) {
                $rawContent = $request->getContent();
                if ($rawContent) {
                    $jsonData = json_decode($rawContent, true);
                    if ($jsonData) {
                        $invoiceId = $jsonData['invoice_id'] ?? null;
                        $confirmation = $jsonData['confirmation'] ?? null;
                        $disputeReason = $jsonData['dispute_reason'] ?? null;
                    }
                }
            }

            // Validation
            if (!$invoiceId) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice ID is required'
                ], 400);
            }

            // Get invoice details
            $invoice = DB::table('tbl_tax_invoice')
                ->where('invoice_id', $invoiceId)
                ->where('deleteflag', 'active')
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice not found!'
                ], 404);
            }

            // Check existing disputes
            $existingDispute = DB::table('tbl_invoice_disputes')
                ->where('invoice_id', $invoiceId)
                ->where('dispute_status', 'active')
                ->where('deleteflag', 'active')
                ->first();

            if ($existingDispute) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice #' . $invoice->invoice_no . ' is already disputed!'
                ], 400);
            }

            // Calculate amounts
            $totalAmount = ($invoice->freight_amount ?? 0) + 
                          ($invoice->sub_total_amount_without_gst ?? 0) + 
                          ($invoice->total_gst_amount ?? 0);
            
            // If above fields are null, try total_amount field
            if ($totalAmount == 0) {
                $totalAmount = $invoice->total_amount ?? 0;
            }

            $totalPaid = DB::table('tbl_payment_received')
                ->where('invoice_id', $invoiceId)
                ->where('deleteflag', 'active')
                ->sum('payment_received_value') ?? 0;

            $remainingBalance = $totalAmount - $totalPaid;

            // Create dispute record using DB query
            $disputeId = DB::table('tbl_invoice_disputes')->insertGetId([
                'invoice_id' => $invoiceId,
                'dispute_type' => 'full_invoice',
                'disputed_amount' => $remainingBalance > 0 ? $remainingBalance : $totalAmount,
                'dispute_reason' => $disputeReason ?? 'Invoice marked as disputed from testing',
                'dispute_status' => 'active',
                'disputed_by' => 1, // Default admin ID for testing
                'disputed_date' => now(),
                'deleteflag' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice has been marked as disputed successfully!',
                'data' => [
                    'dispute_id' => $disputeId,
                    'invoice_id' => $invoiceId,
                    'invoice_no' => $invoice->invoice_no ?? 'N/A',
                    'total_amount' => $totalAmount,
                    'amount_paid' => $totalPaid,
                    'disputed_amount' => $remainingBalance > 0 ? $remainingBalance : $totalAmount,
                    'disputed_on' => now()->format('d M Y, h:i A'),
                    'ui_message' => 'This invoice will now appear only in the Disputed section of the Accounts Receivable page.'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to mark invoice as disputed!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if an invoice can be disputed
     * GET /api/accounts/disputes/can-dispute/{invoice_id}
     */
    public function canDispute($invoiceId)
    {
        try {
            // Get invoice details
            $invoice = DB::table('tbl_tax_invoice as inv')
                ->leftJoin('tbl_comp as comp', 'inv.comp_id', '=', 'comp.comp_id')
                ->select([
                    'inv.*',
                    'comp.comp_name'
                ])
                ->where('inv.invoice_id', $invoiceId)
                ->where('inv.deleteflag', 'active')
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice not found!'
                ], 404);
            }

            // Check if already disputed
            $existingDispute = InvoiceDispute::where('invoice_id', $invoiceId)
                ->where('dispute_status', 'active')
                ->where('deleteflag', 'active')
                ->first();

            $canDispute = !$existingDispute;
            $reason = '';

            if ($existingDispute) {
                $reason = 'Invoice is already disputed';
            }

            // Calculate amounts for info
            $totalAmount = ($invoice->freight_amount ?? 0) + 
                          ($invoice->sub_total_amount_without_gst ?? 0) + 
                          ($invoice->total_gst_amount ?? 0);

            $totalPaid = DB::table('tbl_payment_received')
                ->where('invoice_id', $invoiceId)
                ->where('deleteflag', 'active')
                ->sum('payment_received_value') ?? 0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'can_dispute' => $canDispute,
                    'reason' => $reason,
                    'invoice_details' => [
                        'invoice_no' => $invoice->invoice_no,
                        'company_name' => $invoice->comp_name,
                        'total_amount' => $totalAmount,
                        'amount_paid' => $totalPaid,
                        'remaining_balance' => $totalAmount - $totalPaid
                    ],
                    'existing_dispute' => $existingDispute ? [
                        'dispute_id' => $existingDispute->dispute_id,
                        'disputed_on' => $existingDispute->disputed_date
                    ] : null
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error checking dispute status!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove dispute status from invoice (Undo dispute)
     * POST /api/accounts/disputes/remove-dispute
     */
    public function removeDispute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|integer',
            'confirmation' => 'required|boolean|accepted',
            'removal_reason' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validator->errors()
            ], 400);
        }

        try {
            // Find the active dispute
            $dispute = InvoiceDispute::where('invoice_id', $request->invoice_id)
                ->where('dispute_status', 'active')
                ->where('deleteflag', 'active')
                ->first();

            if (!$dispute) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'No active dispute found for this invoice!'
                ], 404);
            }

            // Get invoice details
            $invoice = DB::table('tbl_tax_invoice as inv')
                ->leftJoin('tbl_comp as comp', 'inv.comp_id', '=', 'comp.comp_id')
                ->select([
                    'inv.*',
                    'comp.comp_name'
                ])
                ->where('inv.invoice_id', $request->invoice_id)
                ->where('inv.deleteflag', 'active')
                ->first();

            // Update dispute record to resolved with removal reason
            $dispute->update([
                'dispute_status' => 'resolved',
                'resolved_by' => auth()->id(),
                'resolved_date' => now(),
                'resolution_remarks' => $request->removal_reason ?? 'Dispute status removed - returned to normal accounts receivable',
                'updated_by' => auth()->id(),
                'updated_at' => now()
            ]);

            $adminName = admin_name(auth()->id());

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice dispute status has been removed successfully!',
                'data' => [
                    'invoice_no' => $invoice->invoice_no,
                    'company_name' => $invoice->comp_name,
                    'dispute_removed_by' => $adminName,
                    'removed_on' => now()->format('d M Y, h:i A'),
                    'ui_message' => 'This invoice will now appear back in the normal Accounts Receivable listing.'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to remove dispute status!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark an entire invoice as disputed
     */
    public function markInvoiceAsDisputed(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:tbl_tax_invoice,invoice_id',
            'dispute_reason' => 'required|string|max:1000',
            'disputed_by' => 'required|integer' // Admin ID
            // Note: disputed_amount is now calculated automatically
        ]);

        // Check if invoice is already disputed
        $existingDispute = InvoiceDispute::where('invoice_id', $validated['invoice_id'])
            ->where('dispute_type', 'full_invoice')
            ->where('dispute_status', 'active')
            ->where('deleteflag', 'active')
            ->first();

        if ($existingDispute) {
            return response()->json([
                'status' => 'error',
                'message' => 'This invoice is already marked as disputed',
                'existing_dispute_id' => $existingDispute->dispute_id
            ], 409);
        }

        // Get invoice details for validation
        $invoice = DB::table('tbl_tax_invoice')
            ->where('invoice_id', $validated['invoice_id'])
            ->where('deleteflag', 'active')
            ->first();

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found or inactive'
            ], 404);
        }

        // Calculate total invoice amount
        $totalInvoiceAmount = ($invoice->freight_amount ?? 0) + 
                            ($invoice->sub_total_amount_without_gst ?? 0) + 
                            ($invoice->total_gst_amount ?? 0);

        // Calculate total payments received for this invoice
        $totalPaymentsReceived = DB::table('tbl_payment_received')
            ->where('invoice_id', $validated['invoice_id'])
            ->where('deleteflag', 'active')
            ->sum('payment_received_value') ?? 0;

        // Calculate remaining balance (this is what should be disputed)
        $remainingBalance = $totalInvoiceAmount - $totalPaymentsReceived;

        // Check if there's any balance left to dispute
        if ($remainingBalance <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No remaining balance to dispute. Invoice is fully paid.',
                'total_invoice_amount' => $totalInvoiceAmount,
                'total_payments_received' => $totalPaymentsReceived,
                'remaining_balance' => $remainingBalance
            ], 422);
        }

        // Create dispute record with remaining balance as disputed amount
        $dispute = InvoiceDispute::create([
            'invoice_id' => $validated['invoice_id'],
            'dispute_type' => 'full_invoice',
            'disputed_amount' => $remainingBalance, // Auto-calculated
            'dispute_reason' => $validated['dispute_reason'],
            'dispute_status' => 'active',
            'disputed_by' => $validated['disputed_by'],
            'disputed_date' => Carbon::now(),
            'deleteflag' => 'active'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice marked as disputed successfully',
            'data' => [
                'dispute_id' => $dispute->dispute_id,
                'invoice_id' => $dispute->invoice_id,
                'dispute_type' => $dispute->dispute_type,
                'disputed_amount' => $dispute->disputed_amount,
                'dispute_status' => $dispute->dispute_status,
                'disputed_date' => $dispute->disputed_date->format('Y-m-d H:i:s'),
                'calculation_details' => [
                    'total_invoice_amount' => $totalInvoiceAmount,
                    'total_payments_received' => $totalPaymentsReceived,
                    'remaining_balance_disputed' => $remainingBalance,
                    'logic' => 'Only remaining unpaid balance is disputed, not full invoice amount'
                ]
            ]
        ], 201);
    }

    /**
     * Mark a specific payment as disputed
     */
    public function markPaymentAsDisputed(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:tbl_tax_invoice,invoice_id',
            'payment_received_id' => 'required|integer|exists:tbl_payment_received,payment_received_id',
            'dispute_reason' => 'required|string|max:1000',
            'disputed_amount' => 'required|numeric|min:0',
            'disputed_by' => 'required|integer' // Admin ID
        ]);

        // Check if this payment is already disputed
        $existingDispute = InvoiceDispute::where('payment_received_id', $validated['payment_received_id'])
            ->where('dispute_status', 'active')
            ->where('deleteflag', 'active')
            ->first();

        if ($existingDispute) {
            return response()->json([
                'status' => 'error',
                'message' => 'This payment is already marked as disputed',
                'existing_dispute_id' => $existingDispute->dispute_id
            ], 409);
        }

        // Get payment details for validation
        $payment = DB::table('tbl_payment_received')
            ->where('payment_received_id', $validated['payment_received_id'])
            ->where('invoice_id', $validated['invoice_id'])
            ->where('deleteflag', 'active')
            ->first();

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment record not found or does not belong to specified invoice'
            ], 404);
        }

        // Validate disputed amount doesn't exceed payment amount
        if ($validated['disputed_amount'] > $payment->payment_received_value) {
            return response()->json([
                'status' => 'error',
                'message' => 'Disputed amount cannot exceed payment amount',
                'payment_amount' => $payment->payment_received_value,
                'disputed_amount_requested' => $validated['disputed_amount']
            ], 422);
        }

        // Create dispute record
        $dispute = InvoiceDispute::create([
            'invoice_id' => $validated['invoice_id'],
            'payment_received_id' => $validated['payment_received_id'],
            'dispute_type' => 'partial_payment',
            'disputed_amount' => $validated['disputed_amount'],
            'dispute_reason' => $validated['dispute_reason'],
            'dispute_status' => 'active',
            'disputed_by' => $validated['disputed_by'],
            'disputed_date' => Carbon::now(),
            'deleteflag' => 'active'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment marked as disputed successfully',
            'data' => [
                'dispute_id' => $dispute->dispute_id,
                'invoice_id' => $dispute->invoice_id,
                'payment_received_id' => $dispute->payment_received_id,
                'dispute_type' => $dispute->dispute_type,
                'disputed_amount' => $dispute->disputed_amount,
                'dispute_status' => $dispute->dispute_status,
                'disputed_date' => $dispute->disputed_date->format('Y-m-d H:i:s')
            ]
        ], 201);
    }

    /**
     * Get list of all disputed invoices/payments
     */
    public function getDisputedInvoices(Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(10, (int) $request->input('per_page', 20)));
        $disputeStatus = $request->input('dispute_status', 'active'); // active, resolved, cancelled, all
        $disputeType = $request->input('dispute_type'); // full_invoice, partial_payment
        $accManager = $request->input('acc_manager');

        $query = DB::table('tbl_invoice_disputes as tid')
            ->join('tbl_tax_invoice as tti', 'tid.invoice_id', '=', 'tti.invoice_id')
            ->leftJoin('tbl_payment_received as tpr', 'tid.payment_received_id', '=', 'tpr.payment_received_id')
            ->leftJoin('tbl_delivery_order as tdo', 'tti.o_id', '=', 'tdo.o_id')
            ->leftJoin('tbl_order as o', 'tti.o_id', '=', 'o.o_id')
            ->select([
                'tid.dispute_id',
                'tid.dispute_type',
                'tid.disputed_amount',
                'tid.dispute_reason',
                'tid.dispute_status',
                'tid.disputed_date',
                'tid.resolved_date',
                'tti.invoice_id',
                'tti.invoice_number',
                'tti.invoice_generated_date',
                'tti.prepared_by as account_manager_id',
                'tpr.payment_received_id',
                'tpr.payment_received_value',
                'tpr.payment_received_date',
                'o.orders_companies_name as company_name',
                DB::raw('(COALESCE(tti.freight_amount, 0) + COALESCE(tti.sub_total_amount_without_gst, 0) + COALESCE(tti.total_gst_amount, 0)) as total_invoice_amount')
            ])
            ->where('tid.deleteflag', 'active');

        // Apply filters
        if ($disputeStatus !== 'all') {
            $query->where('tid.dispute_status', $disputeStatus);
        }

        if (!empty($disputeType)) {
            $query->where('tid.dispute_type', $disputeType);
        }

        if (!empty($accManager)) {
            $query->where('tti.prepared_by', $accManager);
        }

        // Order by dispute date DESC
        $query->orderBy('tid.disputed_date', 'DESC');

        // Get paginated results
        $total = $query->count();
        $disputes = $query->skip(($page - 1) * $perPage)
                         ->take($perPage)
                         ->get();

        // Get default currency for formatting
        $defaultCurrency = DB::table('tbl_currencies')
            ->where('currency_super_default', 'yes')
            ->where('deleteflag', 'active')
            ->first();
        $currencySymbol = $defaultCurrency ? html_entity_decode($defaultCurrency->currency_html_code) : '₹';

        // Format response
        $formattedDisputes = $disputes->map(function ($dispute) use ($currencySymbol) {
            return [
                'dispute_id' => $dispute->dispute_id,
                'dispute_type' => $dispute->dispute_type,
                'dispute_type_label' => $dispute->dispute_type === 'full_invoice' ? 'Full Invoice' : 'Partial Payment',
                'disputed_amount' => $dispute->disputed_amount,
                'disputed_amount_formatted' => $currencySymbol . moneyFormatIndia($dispute->disputed_amount),
                'dispute_reason' => $dispute->dispute_reason,
                'dispute_status' => $dispute->dispute_status,
                'dispute_status_label' => ucfirst($dispute->dispute_status),
                'disputed_date' => $dispute->disputed_date,
                'disputed_date_formatted' => date_format_india($dispute->disputed_date),
                'resolved_date' => $dispute->resolved_date,
                'invoice_details' => [
                    'invoice_id' => $dispute->invoice_id,
                    'invoice_number' => $dispute->invoice_number,
                    'invoice_date' => $dispute->invoice_generated_date,
                    'total_amount' => $dispute->total_invoice_amount,
                    'total_amount_formatted' => $currencySymbol . moneyFormatIndia($dispute->total_invoice_amount),
                    'company_name' => $dispute->company_name,
                    'account_manager_id' => $dispute->account_manager_id
                ],
                'payment_details' => $dispute->payment_received_id ? [
                    'payment_received_id' => $dispute->payment_received_id,
                    'payment_amount' => $dispute->payment_received_value,
                    'payment_amount_formatted' => $currencySymbol . moneyFormatIndia($dispute->payment_received_value),
                    'payment_date' => $dispute->payment_received_date
                ] : null
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Disputed invoices retrieved successfully',
            'data' => [
                'disputes' => $formattedDisputes,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => ($page - 1) * $perPage + 1,
                    'to' => min($page * $perPage, $total)
                ],
                'filters_applied' => [
                    'dispute_status' => $disputeStatus,
                    'dispute_type' => $disputeType,
                    'acc_manager' => $accManager
                ]
            ]
        ]);
    }

    /**
     * Resolve a dispute
     */
    public function resolveDispute(Request $request, $disputeId)
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string|max:1000',
            'resolved_by' => 'required|integer' // Admin ID
        ]);

        $dispute = InvoiceDispute::where('dispute_id', $disputeId)
            ->where('deleteflag', 'active')
            ->first();

        if (!$dispute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dispute not found'
            ], 404);
        }

        if ($dispute->dispute_status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only active disputes can be resolved',
                'current_status' => $dispute->dispute_status
            ], 422);
        }

        // Update dispute status
        $dispute->update([
            'dispute_status' => 'resolved',
            'resolved_by' => $validated['resolved_by'],
            'resolved_date' => Carbon::now(),
            'resolution_notes' => $validated['resolution_notes']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dispute resolved successfully',
            'data' => [
                'dispute_id' => $dispute->dispute_id,
                'dispute_status' => $dispute->dispute_status,
                'resolved_date' => $dispute->resolved_date->format('Y-m-d H:i:s'),
                'resolution_notes' => $dispute->resolution_notes
            ]
        ]);
    }

    /**
     * Cancel a dispute
     */
    public function cancelDispute(Request $request, $disputeId)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
            'cancelled_by' => 'required|integer' // Admin ID
        ]);

        $dispute = InvoiceDispute::where('dispute_id', $disputeId)
            ->where('deleteflag', 'active')
            ->first();

        if (!$dispute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dispute not found'
            ], 404);
        }

        if ($dispute->dispute_status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only active disputes can be cancelled',
                'current_status' => $dispute->dispute_status
            ], 422);
        }

        // Update dispute status
        $dispute->update([
            'dispute_status' => 'cancelled',
            'resolved_by' => $validated['cancelled_by'],
            'resolved_date' => Carbon::now(),
            'resolution_notes' => 'Cancelled: ' . $validated['cancellation_reason']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dispute cancelled successfully',
            'data' => [
                'dispute_id' => $dispute->dispute_id,
                'dispute_status' => $dispute->dispute_status,
                'resolved_date' => $dispute->resolved_date->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Update dispute details (full edit)
     */
    public function updateDispute(Request $request, $disputeId)
    {
        $validated = $request->validate([
            'dispute_reason' => 'required|string|max:1000',
            'disputed_amount' => 'required|numeric|min:0',
            'update_remarks' => 'required|string|max:1000',
            'updated_by' => 'required|integer' // Admin ID
        ]);

        $dispute = InvoiceDispute::where('dispute_id', $disputeId)
            ->where('deleteflag', 'active')
            ->first();

        if (!$dispute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dispute not found'
            ], 404);
        }

        if ($dispute->dispute_status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only active disputes can be edited',
                'current_status' => $dispute->dispute_status
            ], 422);
        }

        // Validate disputed amount based on dispute type
        if ($dispute->dispute_type === 'full_invoice') {
            // For full invoice disputes, validate against remaining balance
            $invoice = DB::table('tbl_tax_invoice')
                ->where('invoice_id', $dispute->invoice_id)
                ->where('deleteflag', 'active')
                ->first();

            $totalInvoiceAmount = ($invoice->freight_amount ?? 0) + 
                                ($invoice->sub_total_amount_without_gst ?? 0) + 
                                ($invoice->total_gst_amount ?? 0);

            $totalPaymentsReceived = DB::table('tbl_payment_received')
                ->where('invoice_id', $dispute->invoice_id)
                ->where('deleteflag', 'active')
                ->sum('payment_received_value') ?? 0;

            $remainingBalance = $totalInvoiceAmount - $totalPaymentsReceived;

            if ($validated['disputed_amount'] > $remainingBalance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Disputed amount cannot exceed remaining balance',
                    'remaining_balance' => $remainingBalance,
                    'disputed_amount_requested' => $validated['disputed_amount']
                ], 422);
            }
        } else {
            // For partial payment disputes, validate against payment amount
            $payment = DB::table('tbl_payment_received')
                ->where('payment_received_id', $dispute->payment_received_id)
                ->where('deleteflag', 'active')
                ->first();

            if ($validated['disputed_amount'] > $payment->payment_received_value) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Disputed amount cannot exceed payment amount',
                    'payment_amount' => $payment->payment_received_value,
                    'disputed_amount_requested' => $validated['disputed_amount']
                ], 422);
            }
        }

        // Update dispute record
        $dispute->update([
            'dispute_reason' => $validated['dispute_reason'],
            'disputed_amount' => $validated['disputed_amount'],
            'last_updated_by' => $validated['updated_by'],
            'last_updated_date' => Carbon::now(),
            'update_remarks' => $validated['update_remarks']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dispute updated successfully',
            'data' => [
                'dispute_id' => $dispute->dispute_id,
                'dispute_reason' => $dispute->dispute_reason,
                'disputed_amount' => $dispute->disputed_amount,
                'last_updated_by' => $dispute->last_updated_by,
                'last_updated_date' => $dispute->last_updated_date->format('Y-m-d H:i:s'),
                'update_remarks' => $dispute->update_remarks,
                'updated_by_name' => admin_name($dispute->last_updated_by)
            ]
        ]);
    }

    /**
     * Soft delete a dispute
     */
    public function deleteDispute(Request $request, $disputeId)
    {
        $validated = $request->validate([
            'deletion_reason' => 'required|string|max:1000',
            'deleted_by' => 'required|integer' // Admin ID
        ]);

        $dispute = InvoiceDispute::where('dispute_id', $disputeId)
            ->where('deleteflag', 'active')
            ->first();

        if (!$dispute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dispute not found'
            ], 404);
        }

        if ($dispute->dispute_status === 'resolved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete resolved disputes. Please contact administrator.',
                'current_status' => $dispute->dispute_status
            ], 422);
        }

        // Soft delete the dispute
        $dispute->update([
            'deleteflag' => 'deleted',
            'dispute_status' => 'cancelled',
            'resolved_by' => $validated['deleted_by'],
            'resolved_date' => Carbon::now(),
            'resolution_notes' => 'Deleted: ' . $validated['deletion_reason'],
            'last_updated_by' => $validated['deleted_by'],
            'last_updated_date' => Carbon::now(),
            'update_remarks' => 'Dispute deleted by admin'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dispute deleted successfully',
            'data' => [
                'dispute_id' => $dispute->dispute_id,
                'deleted_by' => $dispute->resolved_by,
                'deleted_date' => $dispute->resolved_date->format('Y-m-d H:i:s'),
                'deletion_reason' => $validated['deletion_reason'],
                'deleted_by_name' => admin_name($dispute->resolved_by)
            ]
        ]);
    }

    /**
     * Restore a soft-deleted dispute
     */
    public function restoreDispute(Request $request, $disputeId)
    {
        $validated = $request->validate([
            'restore_reason' => 'required|string|max:1000',
            'restored_by' => 'required|integer' // Admin ID
        ]);

        $dispute = InvoiceDispute::where('dispute_id', $disputeId)
            ->where('deleteflag', 'deleted')
            ->first();

        if (!$dispute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Deleted dispute not found'
            ], 404);
        }

        // Restore the dispute
        $dispute->update([
            'deleteflag' => 'active',
            'dispute_status' => 'active',
            'resolved_by' => null,
            'resolved_date' => null,
            'resolution_notes' => null,
            'last_updated_by' => $validated['restored_by'],
            'last_updated_date' => Carbon::now(),
            'update_remarks' => 'Dispute restored: ' . $validated['restore_reason']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dispute restored successfully',
            'data' => [
                'dispute_id' => $dispute->dispute_id,
                'restored_by' => $dispute->last_updated_by,
                'restored_date' => $dispute->last_updated_date->format('Y-m-d H:i:s'),
                'restore_reason' => $validated['restore_reason'],
                'restored_by_name' => admin_name($dispute->last_updated_by)
            ]
        ]);
    }

    /**
     * Get deleted disputes for admin review
     */
    public function getDeletedDisputes(Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(10, (int) $request->input('per_page', 20)));

        $query = DB::table('tbl_invoice_disputes as tid')
            ->join('tbl_tax_invoice as tti', 'tid.invoice_id', '=', 'tti.invoice_id')
            ->leftJoin('tbl_admin as ta1', 'tid.disputed_by', '=', 'ta1.admin_id')
            ->leftJoin('tbl_admin as ta2', 'tid.resolved_by', '=', 'ta2.admin_id')
            ->select([
                'tid.dispute_id',
                'tid.invoice_id',
                'tid.dispute_type',
                'tid.disputed_amount',
                'tid.dispute_reason',
                'tid.disputed_date',
                'tid.resolved_date as deleted_date',
                'tid.resolution_notes as deletion_reason',
                'tti.invoice_number',
                'ta1.admin_name as disputed_by_name',
                'ta2.admin_name as deleted_by_name'
            ])
            ->where('tid.deleteflag', 'deleted')
            ->orderBy('tid.resolved_date', 'DESC');

        $total = $query->count();
        $deletedDisputes = $query->skip(($page - 1) * $perPage)
                                ->take($perPage)
                                ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Deleted disputes retrieved successfully',
            'data' => [
                'deleted_disputes' => $deletedDisputes,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]
        ]);
    }

    /**
     * Update dispute remarks and tracking
     */
    public function updateDisputeRemarks(Request $request, $disputeId)
    {
        $validated = $request->validate([
            'update_remarks' => 'required|string|max:1000',
            'updated_by' => 'required|integer' // Admin ID
        ]);

        $dispute = InvoiceDispute::where('dispute_id', $disputeId)
            ->where('deleteflag', 'active')
            ->first();

        if (!$dispute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dispute not found'
            ], 404);
        }

        if ($dispute->dispute_status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only active disputes can be updated',
                'current_status' => $dispute->dispute_status
            ], 422);
        }

        // Update dispute with tracking information
        $dispute->update([
            'last_updated_by' => $validated['updated_by'],
            'last_updated_date' => Carbon::now(),
            'update_remarks' => $validated['update_remarks']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dispute remarks updated successfully',
            'data' => [
                'dispute_id' => $dispute->dispute_id,
                'last_updated_by' => $dispute->last_updated_by,
                'last_updated_date' => $dispute->last_updated_date->format('Y-m-d H:i:s'),
                'update_remarks' => $dispute->update_remarks,
                'updated_by_name' => admin_name($dispute->last_updated_by)
            ]
        ]);
    }

    /**
     * Get dispute details by ID
     */
    public function getDisputeDetails($disputeId)
    {
        $dispute = DB::table('tbl_invoice_disputes as tid')
            ->join('tbl_tax_invoice as tti', 'tid.invoice_id', '=', 'tti.invoice_id')
            ->leftJoin('tbl_payment_received as tpr', 'tid.payment_received_id', '=', 'tpr.payment_received_id')
            ->leftJoin('tbl_delivery_order as tdo', 'tti.o_id', '=', 'tdo.o_id')
            ->leftJoin('tbl_order as o', 'tti.o_id', '=', 'o.o_id')
            ->leftJoin('tbl_admin as ta1', 'tid.disputed_by', '=', 'ta1.admin_id')
            ->leftJoin('tbl_admin as ta2', 'tid.resolved_by', '=', 'ta2.admin_id')
            ->leftJoin('tbl_admin as ta3', 'tid.last_updated_by', '=', 'ta3.admin_id')
            ->select([
                'tid.*',
                'tti.invoice_number',
                'tti.invoice_generated_date',
                'tti.prepared_by as account_manager_id',
                'tpr.payment_received_value',
                'tpr.payment_received_date',
                'tpr.payment_mode',
                'o.orders_companies_name as company_name',
                'ta1.admin_name as disputed_by_name',
                'ta2.admin_name as resolved_by_name',
                'ta3.admin_name as last_updated_by_name',
                DB::raw('(COALESCE(tti.freight_amount, 0) + COALESCE(tti.sub_total_amount_without_gst, 0) + COALESCE(tti.total_gst_amount, 0)) as total_invoice_amount')
            ])
            ->where('tid.dispute_id', $disputeId)
            ->where('tid.deleteflag', 'active')
            ->first();

        if (!$dispute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dispute not found'
            ], 404);
        }

        // Get default currency for formatting
        $defaultCurrency = DB::table('tbl_currencies')
            ->where('currency_super_default', 'yes')
            ->where('deleteflag', 'active')
            ->first();
        $currencySymbol = $defaultCurrency ? html_entity_decode($defaultCurrency->currency_html_code) : '₹';

        return response()->json([
            'status' => 'success',
            'message' => 'Dispute details retrieved successfully',
            'data' => [
                'dispute_id' => $dispute->dispute_id,
                'dispute_type' => $dispute->dispute_type,
                'dispute_type_label' => $dispute->dispute_type === 'full_invoice' ? 'Full Invoice' : 'Partial Payment',
                'disputed_amount' => $dispute->disputed_amount,
                'disputed_amount_formatted' => $currencySymbol . moneyFormatIndia($dispute->disputed_amount),
                'dispute_reason' => $dispute->dispute_reason,
                'dispute_status' => $dispute->dispute_status,
                'dispute_status_label' => ucfirst($dispute->dispute_status),
                'disputed_date' => $dispute->disputed_date,
                'disputed_date_formatted' => date_format_india($dispute->disputed_date),
                'disputed_by_name' => $dispute->disputed_by_name,
                'resolved_date' => $dispute->resolved_date,
                'resolved_by_name' => $dispute->resolved_by_name,
                'resolution_notes' => $dispute->resolution_notes,
                // Update tracking information - NEW
                'update_tracking' => [
                    'last_updated_by' => $dispute->last_updated_by,
                    'last_updated_by_name' => $dispute->last_updated_by_name,
                    'last_updated_date' => $dispute->last_updated_date,
                    'last_updated_date_formatted' => $dispute->last_updated_date ? date_format_india($dispute->last_updated_date) : null,
                    'update_remarks' => $dispute->update_remarks
                ],
                'invoice_details' => [
                    'invoice_id' => $dispute->invoice_id,
                    'invoice_number' => $dispute->invoice_number,
                    'invoice_date' => $dispute->invoice_generated_date,
                    'total_amount' => $dispute->total_invoice_amount,
                    'total_amount_formatted' => $currencySymbol . moneyFormatIndia($dispute->total_invoice_amount),
                    'company_name' => $dispute->company_name,
                    'account_manager_id' => $dispute->account_manager_id
                ],
                'payment_details' => $dispute->payment_received_id ? [
                    'payment_received_id' => $dispute->payment_received_id,
                    'payment_amount' => $dispute->payment_received_value,
                    'payment_amount_formatted' => $currencySymbol . moneyFormatIndia($dispute->payment_received_value),
                    'payment_date' => $dispute->payment_received_date,
                    'payment_mode' => $dispute->payment_mode
                ] : null
            ]
        ]);
    }

    /**
     * Working main dispute endpoint - Clean implementation
     * POST /api/accounts/disputes/mark-disputed-working
     */
    public function markAsDisputedWorking(Request $request)
    {
        try {
            // Extract data using multiple methods (same as test endpoint)
            $invoiceId = $request->invoice_id ?? 
                        $request->input('invoice_id') ?? 
                        ($request->json() ? $request->json('invoice_id') : null);

            $confirmation = $request->confirmation ?? 
                           $request->input('confirmation') ?? 
                           ($request->json() ? $request->json('confirmation') : null);

            $disputeReason = $request->dispute_reason ?? 
                            $request->input('dispute_reason') ?? 
                            ($request->json() ? $request->json('dispute_reason') : null);

            // Manual JSON parsing fallback
            if (!$invoiceId) {
                $rawContent = $request->getContent();
                if ($rawContent) {
                    $jsonData = json_decode($rawContent, true);
                    if ($jsonData) {
                        $invoiceId = $jsonData['invoice_id'] ?? null;
                        $confirmation = $jsonData['confirmation'] ?? null;
                        $disputeReason = $jsonData['dispute_reason'] ?? null;
                    }
                }
            }

            // Validation
            if (!$invoiceId) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice ID is required'
                ], 400);
            }

            if (!$confirmation) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Confirmation is required to proceed!'
                ], 400);
            }

            // Get invoice details
            $invoice = DB::table('tbl_tax_invoice as inv')
                ->leftJoin('tbl_comp as comp', 'inv.comp_id', '=', 'comp.comp_id')
                ->select(['inv.*', 'comp.comp_name'])
                ->where('inv.invoice_id', $invoiceId)
                ->where('inv.deleteflag', 'active')
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice not found!'
                ], 404);
            }

            // Check existing disputes
            $existingDispute = DB::table('tbl_invoice_disputes')
                ->where('invoice_id', $invoiceId)
                ->where('dispute_status', 'active')
                ->where('deleteflag', 'active')
                ->first();

            if ($existingDispute) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice #' . $invoice->invoice_no . ' is already disputed!'
                ], 400);
            }

            // Calculate amounts
            $totalAmount = ($invoice->freight_amount ?? 0) + 
                          ($invoice->sub_total_amount_without_gst ?? 0) + 
                          ($invoice->total_gst_amount ?? 0);
            
            if ($totalAmount == 0) {
                $totalAmount = $invoice->total_amount ?? 0;
            }

            $totalPaid = DB::table('tbl_payment_received')
                ->where('invoice_id', $invoiceId)
                ->where('deleteflag', 'active')
                ->sum('payment_received_value') ?? 0;

            $remainingBalance = $totalAmount - $totalPaid;

            // Create dispute record
            $disputeId = DB::table('tbl_invoice_disputes')->insertGetId([
                'invoice_id' => $invoiceId,
                'dispute_type' => 'full_invoice',
                'disputed_amount' => $remainingBalance > 0 ? $remainingBalance : $totalAmount,
                'dispute_reason' => $disputeReason ?? 'Invoice marked as disputed from invoice management',
                'dispute_status' => 'active',
                'disputed_by' => auth()->id() ?? 1,
                'disputed_date' => now(),
                'deleteflag' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice has been marked as disputed successfully!',
                'data' => [
                    'dispute_id' => $disputeId,
                    'invoice_id' => $invoiceId,
                    'invoice_no' => $invoice->invoice_no ?? 'N/A',
                    'company_name' => $invoice->comp_name ?? 'N/A',
                    'total_amount' => $totalAmount,
                    'amount_paid' => $totalPaid,
                    'disputed_amount' => $remainingBalance > 0 ? $remainingBalance : $totalAmount,
                    'disputed_on' => now()->format('d M Y, h:i A'),
                    'ui_message' => 'This invoice will now appear only in the Disputed section of the Accounts Receivable page.'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to mark invoice as disputed!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
