<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentPaid;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Exception;

class PaymentDisputeController extends Controller
{
    /** 
     * Mark a payment as disputed
     * POST /api/accounts/payment-disputes/mark-disputed
     */
    public function markAsDisputed(Request $request)
    {
        try {
            // Step 1: Clean and parse JSON
            $requestBody = $request->getContent();
            
            // Remove comments from JSON if present
            $cleanedJson = preg_replace('/\/\*.*?\*\//', '', $requestBody);
            $cleanedJson = preg_replace('/\/\/.*?(\r\n|\r|\n)/', '', $cleanedJson);
            
            $data = json_decode($cleanedJson, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid JSON format: ' . json_last_error_msg(),
                    'debug' => ['raw_body' => $requestBody]
                ], 400);
            }
            
            // Step 2: Validate input
            $validator = Validator::make($data, [
                'payment_paid_id' => 'required|integer|min:1',
                'dispute_reason' => 'required|string|min:10|max:1000',
                'dispute_type' => 'sometimes|string|in:full_payment,partial_payment,overpayment,wrong_payment,duplicate_payment,other',
                'disputed_amount' => 'sometimes|numeric|min:0',
                'priority' => 'sometimes|string|in:low,medium,high,critical',
                'user_id' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation Error!',
                    'data' => $validator->errors()
                ], 400);
            }

            $paymentPaidId = $data['payment_paid_id'];
            $disputeReason = $data['dispute_reason'];
            $disputeType = $data['dispute_type'] ?? 'full_payment';
            $disputedAmount = $data['disputed_amount'] ?? null;
            $priority = $data['priority'] ?? 'medium';
            $userId = $data['user_id'];

            // Step 3: Check if payment exists
            $payment = DB::table('tbl_payment_paid')
                ->where('payment_paid_id', $paymentPaidId)
                ->where('deleteflag', 'active')
                ->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment record not found'
                ], 404);
            }

            // Step 4: Check current dispute status
            if ($payment->markas_disputed === 'yes') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment already disputed'
                ], 400);
            }
            
            // Step 5: Update tbl_payment_paid table
            $updateResult = DB::update(
                "UPDATE tbl_payment_paid SET markas_disputed = 'yes', disputed_remarks = ?, disputed_by = ?, disputed_date = NOW() WHERE payment_paid_id = ?",
                [$disputeReason, $userId, $paymentPaidId]
            );
            
            // Step 6: Create audit record in disputes table
            $auditId = DB::table('tbl_payment_disputes')->insertGetId([
                'payment_paid_id' => $paymentPaidId,
                'invoice_id' => $payment->invoice_id,
                'po_id' => $payment->po_id,
                'dispute_type' => $disputeType,
                'dispute_status' => 'active',
                'dispute_reason' => $disputeReason,
                'disputed_amount' => $disputedAmount,
                'updated_by' => $userId,
                'priority' => $priority,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment marked as disputed successfully',
                'data' => [
                    'payment_paid_id' => $paymentPaidId,
                    'dispute_id' => $auditId,
                    'invoice_id' => $payment->invoice_id,
                    'po_id' => $payment->po_id,
                    'dispute_reason' => $disputeReason,
                    'dispute_type' => $disputeType,
                    'disputed_amount' => $disputedAmount,
                    'priority' => $priority,
                    'updated_by' => $userId,
                    'disputed_date' => now()->toDateTimeString()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error marking payment as disputed: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    /**
     * Remove dispute status from payment
     * POST /api/accounts/payment-disputes/remove-disputed
     */
    public function removeFromDisputed(Request $request)
    {
        try {
            // Step 1: Clean and parse JSON
            $requestBody = $request->getContent();
            
            // Remove comments from JSON if present
            $cleanedJson = preg_replace('/\/\*.*?\*\//', '', $requestBody);
            $cleanedJson = preg_replace('/\/\/.*?(\r\n|\r|\n)/', '', $cleanedJson);
            
            $data = json_decode($cleanedJson, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid JSON format: ' . json_last_error_msg(),
                    'debug' => ['raw_body' => $requestBody]
                ], 400);
            }
            
            // Step 2: Validate input
            $validator = Validator::make($data, [
                'payment_paid_id' => 'required|integer|min:1',
                'resolution_notes' => 'sometimes|string|max:1000',
                'user_id' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation Error!',
                    'data' => $validator->errors()
                ], 400);
            }

            $paymentPaidId = $data['payment_paid_id'];
            $resolutionNotes = $data['resolution_notes'] ?? 'Payment dispute resolved';
            $userId = $data['user_id'];

            // Step 3: Check if payment exists and is disputed
            $payment = DB::table('tbl_payment_paid')
                ->where('payment_paid_id', $paymentPaidId)
                ->where('deleteflag', 'active')
                ->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment record not found'
                ], 404);
            }

            if ($payment->markas_disputed !== 'yes') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment is not currently disputed'
                ], 400);
            }

            // Step 4: Update tbl_payment_paid table to remove dispute
            $updateResult = DB::update(
                "UPDATE tbl_payment_paid SET markas_disputed = 'no', disputed_remarks = NULL, disputed_by = NULL, disputed_date = NULL WHERE payment_paid_id = ?",
                [$paymentPaidId]
            );

            // Step 5: Update audit record to resolved status
            $disputeUpdateResult = DB::table('tbl_payment_disputes')
                ->where('payment_paid_id', $paymentPaidId)
                ->where('dispute_status', 'active')
                ->update([
                    'dispute_status' => 'resolved',
                    'resolution_notes' => $resolutionNotes,
                    'updated_by' => $userId,
                    'updated_at' => now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment dispute resolved successfully',
                'data' => [
                    'payment_paid_id' => $paymentPaidId,
                    'invoice_id' => $payment->invoice_id,
                    'po_id' => $payment->po_id,
                    'resolution_notes' => $resolutionNotes,
                    'updated_by' => $userId,
                    'resolved_date' => now()->toDateTimeString(),
                    'disputes_updated' => $disputeUpdateResult
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error resolving payment dispute: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    /**
     * Get all disputed payments
     * GET /api/accounts/payment-disputes/list
     */
    public function getDisputedPayments(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $sortKey = $request->get('sortkey', 'disputed_date');
            $sortValue = $request->get('sortvalue', 'desc');

            // Build query for disputed payments with latest dispute info
            $query = DB::table('tbl_payment_paid as pp')
                ->leftJoin('vendor_po_invoice_new as vpi', 'pp.invoice_id', '=', 'vpi.id')
                ->leftJoin('vendor_po_final as vpf', 'pp.po_id', '=', 'vpf.PO_ID')
                ->leftJoin(DB::raw('(
                    SELECT 
                        payment_paid_id,
                        MAX(dispute_id) as latest_dispute_id,
                        dispute_type,
                        disputed_amount,
                        priority,
                        updated_by
                    FROM tbl_payment_disputes 
                    WHERE dispute_status = "active"
                    GROUP BY payment_paid_id
                ) as latest_dispute'), 'pp.payment_paid_id', '=', 'latest_dispute.payment_paid_id')
                ->select(
                    'pp.payment_paid_id',
                    'pp.invoice_id',
                    'pp.po_id',
                    'pp.payment_paid_value as amount_paid',
                    'pp.payment_paid_date',
                    'pp.payment_paid_via as payment_method',
                    'pp.transaction_id',
                    'pp.markas_disputed',
                    'pp.disputed_remarks',
                    'pp.disputed_by',
                    'pp.disputed_date',
                    'pp.currency_id',
                    'vpi.invoice_no',
                    'vpi.vendor_id',
                    'vpf.Flag as po_currency',
                    'latest_dispute.latest_dispute_id as dispute_id',
                    'latest_dispute.dispute_type',
                    'latest_dispute.disputed_amount',
                    'latest_dispute.priority',
                    'latest_dispute.updated_by as last_updated_by'
                )
                ->where('pp.markas_disputed', 'yes')
                ->where('pp.deleteflag', 'active');

            // Apply sorting
            if (in_array($sortKey, ['disputed_date', 'payment_paid_date', 'payment_paid_value', 'transaction_id'])) {
                $query->orderBy("pp.$sortKey", $sortValue === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('pp.disputed_date', 'desc');
            }

            // Get total count
            $totalItems = $query->count();
            $totalPages = ceil($totalItems / $perPage);

            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $results = $query->offset($offset)->limit($perPage)->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Disputed payments retrieved successfully',
                'data' => $results,
                'pagination' => [
                    'page' => (int)$page,
                    'pageSize' => (int)$perPage,
                    'totalItems' => $totalItems,
                    'totalPages' => $totalPages
                ],
                'sorting' => [
                    'sortkey' => $sortKey,
                    'sortvalue' => $sortValue
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error retrieving disputed payments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dispute history for a specific payment
     * GET /api/accounts/payment-disputes/history/{payment_paid_id}
     */
    public function getDisputeHistory($paymentPaidId)
    {
        try {
            $disputes = DB::table('tbl_payment_disputes')
                ->where('payment_paid_id', $paymentPaidId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment dispute history retrieved successfully',
                'data' => $disputes
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error retrieving payment dispute history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint for payment dispute API
     * POST /api/accounts/payment-disputes/test
     */
    public function test(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Payment Dispute API is working!',
            'data' => [
                'timestamp' => now(),
                'endpoint' => 'payment-disputes/test',
                'method' => $request->method(),
                'request_data' => $request->all()
            ]
        ], 200);
    }
}
