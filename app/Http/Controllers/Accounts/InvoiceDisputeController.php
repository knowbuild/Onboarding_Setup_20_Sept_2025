<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\InvoiceDispute;
use App\Models\TaxInvoice;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Exception;

class InvoiceDisputeController extends Controller
{
    /**
     * Simple test endpoint to check if API is working
     * POST /api/accounts/disputes/test
     */
    public function test(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Invoice Dispute API is working!',
            'data' => [
                'timestamp' => now(),
                'endpoint' => 'test',
                'method' => $request->method(),
                'request_data' => $request->all()
            ]
        ], 200);
    }

    /**
     * Simple test endpoint to verify the hybrid approach is working
     * POST /api/accounts/disputes/test-hybrid
     */
    public function testHybrid(Request $request)
    {
        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Hybrid approach test endpoint working',
                'data' => [
                    'timestamp' => now(),
                    'request_data' => $request->all(),
                    'database_test' => [
                        'columns_exist' => true,
                        'can_query' => true
                    ]
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Test endpoint error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Debug JWT authentication to see what user data we get
     * POST /api/accounts/disputes/debug-auth
     */
    public function debugAuth(Request $request)
    {
        try {
            $authData = [];
            
            // Try different auth methods
            $authData['bearer_token'] = $request->bearerToken();
            $authData['authorization_header'] = $request->header('Authorization');
            
            // Try default auth
            try {
                $defaultUser = auth()->user();
                $authData['default_auth'] = [
                    'success' => $defaultUser ? true : false,
                    'user_id' => $defaultUser ? $defaultUser->id ?? null : null,
                    'admin_id' => $defaultUser ? $defaultUser->admin_id ?? null : null,
                    'user_data' => $defaultUser ? $defaultUser->toArray() : null
                ];
            } catch (Exception $e) {
                $authData['default_auth'] = ['error' => $e->getMessage()];
            }
            
            // Try api guard
            try {
                $apiUser = auth('api')->user();
                $authData['api_auth'] = [
                    'success' => $apiUser ? true : false,
                    'user_id' => $apiUser ? $apiUser->id ?? null : null,
                    'admin_id' => $apiUser ? $apiUser->admin_id ?? null : null,
                    'user_data' => $apiUser ? $apiUser->toArray() : null
                ];
            } catch (Exception $e) {
                $authData['api_auth'] = ['error' => $e->getMessage()];
            }
            
            // Check JWT config
            $authData['jwt_config'] = [
                'default_guard' => config('auth.defaults.guard'),
                'api_driver' => config('auth.guards.api.driver'),
                'api_provider' => config('auth.guards.api.provider')
            ];
            
            return response()->json([
                'status' => 'success',
                'message' => 'Authentication debug information',
                'data' => $authData
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Debug auth error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Simple test endpoint to debug request parsing
     * POST /api/accounts/disputes/test-simple
     */
    public function testSimple(Request $request)
    {
        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Test endpoint reached',
                'debug_data' => [
                    'method' => $request->method(),
                    'content_type' => $request->header('Content-Type'),
                    'raw_content' => $request->getContent(),
                    'all_data' => $request->all(),
                    'json_data' => $request->json() ? $request->json()->all() : null,
                    'specific_fields' => [
                        'invoice_id_direct' => $request->invoice_id,
                        'invoice_id_input' => $request->input('invoice_id'),
                        'invoice_id_json' => $request->json() ? $request->json('invoice_id') : null
                    ]
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Test endpoint error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Step-by-step debug version of mark as disputed
     */
    public function markAsDisputedStep(Request $request)
    {
        try {
            $step = 1;
            $debug = ['step' => $step, 'message' => 'Starting markAsDisputedStep'];
            
            // Step 1: Validate input
            $invoiceId = $request->invoice_id;
            $confirmation = $request->confirmation;
            $disputeReason = $request->dispute_reason;
            
            $step = 2;
            $debug = ['step' => $step, 'message' => 'Input extracted', 'data' => [
                'invoice_id' => $invoiceId,
                'confirmation' => $confirmation,
                'dispute_reason' => $disputeReason
            ]];
            
            if (!$invoiceId || !$confirmation) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Missing required fields',
                    'debug' => $debug
                ], 400);
            }
            
            // Step 3: Find invoice using raw DB query (safer)
            $step = 3;
            $invoice = DB::select("SELECT * FROM tbl_tax_invoice WHERE invoice_id = ? AND deleteflag = 'active'", [$invoiceId]);
            
            if (empty($invoice)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice not found',
                    'debug' => ['step' => $step, 'invoice_id' => $invoiceId]
                ], 404);
            }
            
            $invoice = $invoice[0];
            $step = 4;
            
            // Step 4: Check current dispute status
            if ($invoice->markas_disputed === 'yes') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice already disputed',
                    'debug' => ['step' => $step, 'current_status' => $invoice->markas_disputed]
                ], 400);
            }
            
            // Step 5: Update using raw query
            $step = 5;
            $updateResult = DB::update(
                "UPDATE tbl_tax_invoice SET markas_disputed = 'yes', disputed_remarks = ?, disputed_by = 1, disputed_date = NOW() WHERE invoice_id = ?",
                [$disputeReason, $invoiceId]
            );
            
            // Step 6: Create audit record using correct column names
            $step = 6;
            $auditId = DB::table('tbl_invoice_disputes')->insertGetId([
                'invoice_id' => $invoiceId,
                'dispute_type' => 'full_invoice',
                'disputed_amount' => 0, // We'll calculate this later if needed
                'dispute_reason' => $disputeReason,
                'dispute_status' => 'active',
                'disputed_by' => 1,
                'disputed_date' => now(),
                'deleteflag' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Invoice marked as disputed successfully (step-by-step)',
                'data' => [
                    'invoice_id' => $invoiceId,
                    'po_no' => $invoice->po_no,
                    'company_name' => $invoice->cus_com_name,
                    'audit_id' => $auditId,
                    'update_result' => $updateResult,
                    'final_step' => $step
                ]
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Step-by-step error at step ' . ($step ?? 'unknown'),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'debug' => $debug ?? null
            ], 500);
        }
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
                    'message' => 'Invoice ID is required',
                    'debug_info' => [
                        'request_method' => $request->method(),
                        'content_type' => $request->header('Content-Type'),
                        'raw_content' => $request->getContent(),
                        'all_data' => $request->all()
                    ]
                ], 400);
            }

            if (!$confirmation) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Confirmation is required to proceed!'
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
                    'message' => 'Invoice #' . ($invoice->po_no ?? $invoiceId) . ' is already disputed!'
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
                    'invoice_no' => $invoice->po_no ?? 'N/A',
                    'company_name' => $invoice->cus_com_name ?? 'N/A',
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
     * Working main dispute endpoint - Hybrid approach with tbl_tax_invoice columns
     * POST /api/accounts/disputes/mark-disputed-working
     */
    public function markAsDisputedWorking(Request $request)
    {
        try {
            // Log the complete request for debugging
            \Log::info('=== INVOICE DISPUTE REQUEST DEBUG ===', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'raw_content' => $request->getContent(),
                'all_inputs' => $request->all(),
                'has_json' => $request->isJson(),
            ]);

            // Get request data - try multiple methods
            $data = $request->all();
            
            // Log initial data attempt
            \Log::info('Initial data extraction', ['data' => $data, 'empty' => empty($data)]);
            
            // If request->all() is empty, parse JSON manually
            if (empty($data)) {
                $jsonContent = $request->getContent();
                \Log::info('Attempting manual JSON parsing', ['content' => $jsonContent]);
                
                if ($jsonContent) {
                    // First, try to clean the JSON by removing comments
                    $cleanedJson = preg_replace('#//.*#', '', $jsonContent); // Remove single-line comments
                    $cleanedJson = preg_replace('#/\*.*?\*/#s', '', $cleanedJson); // Remove multi-line comments
                    
                    \Log::info('Cleaned JSON content', ['original' => $jsonContent, 'cleaned' => $cleanedJson]);
                    
                    $data = json_decode($cleanedJson, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        \Log::error('JSON parsing failed even after cleaning', [
                            'error' => json_last_error_msg(),
                            'original_content' => $jsonContent,
                            'cleaned_content' => $cleanedJson
                        ]);
                        
                        return response()->json([
                            'status' => 'failed',
                            'message' => 'Invalid JSON in request body. Please remove comments from JSON.',
                            'json_error' => json_last_error_msg(),
                            'help' => 'JSON does not support comments. Remove // or /* */ from your request body.',
                            'raw_content' => $jsonContent,
                            'cleaned_content' => $cleanedJson
                        ], 400);
                    }
                    \Log::info('Manual JSON parsing successful', ['data' => $data]);
                }
            }
            
            // Try Laravel's json() method if still empty
            if (empty($data) && $request->isJson()) {
                try {
                    $data = $request->json()->all();
                    \Log::info('Laravel json() method successful', ['data' => $data]);
                } catch (\Exception $e) {
                    \Log::warning('Laravel json() method failed', ['error' => $e->getMessage()]);
                }
            }
            
            // Final fallback - if data is still empty
            if (empty($data)) {
                $data = [];
                \Log::warning('All parsing methods failed, using empty array');
            }
            
            $invoiceId = $data['invoice_id'] ?? null;
            $confirmation = $data['confirmation'] ?? null;
            $disputeReason = $data['dispute_reason'] ?? null;
            $disputedBy = $data['disputed_by'] ?? null;

            \Log::info('Final extracted values', [
                'invoice_id' => $invoiceId,
                'invoice_id_type' => gettype($invoiceId),
                'confirmation' => $confirmation,
                'dispute_reason' => $disputeReason,
                'disputed_by' => $disputedBy
            ]);

            // Enhanced validation - handle both string and numeric values including 0
            if (empty($invoiceId) && $invoiceId !== 0 && $invoiceId !== '0') {
                \Log::error('Invoice ID validation failed', [
                    'invoice_id' => $invoiceId,
                    'all_data' => $data,
                    'request_details' => [
                        'method' => $request->method(),
                        'content_type' => $request->header('Content-Type'),
                        'is_json' => $request->isJson(),
                        'content_length' => strlen($request->getContent())
                    ]
                ]);
                
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice ID is required',
                    'debug' => [
                        'invoice_id_value' => $invoiceId,
                        'invoice_id_type' => gettype($invoiceId),
                        'invoice_id_empty' => empty($invoiceId),
                        'invoice_id_is_null' => is_null($invoiceId),
                        'invoice_id_boolean' => (bool)$invoiceId,
                        'received_data' => $data,
                        'raw_content' => $request->getContent(),
                        'request_method' => $request->method(),
                        'content_type' => $request->header('Content-Type'),
                        'is_json' => $request->isJson()
                    ]
                ], 400);
            }

            // Convert to integer if it's a string number
            $invoiceId = (int)$invoiceId;
            \Log::info('Converted invoice ID to integer', ['invoice_id' => $invoiceId]);

            if (!$confirmation) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Confirmation is required to proceed!'
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
                    'message' => 'Invoice not found!',
                    'debug' => [
                        'searched_invoice_id' => $invoiceId,
                        'table' => 'tbl_tax_invoice',
                        'column_used' => 'invoice_id',
                        'deleteflag_filter' => 'active'
                    ]
                ], 404);
            }

            // Check if already disputed (using new column)
            if ($invoice->markas_disputed === 'yes') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice #' . ($invoice->po_no ?? $invoiceId) . ' is already disputed!'
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

            // Get disputed_by from request payload (sent by React app)
            // Use fallback value if not provided
            $adminId = $disputedBy ?? 1;

            DB::beginTransaction();

            try {
                // Update main table with dispute status
                DB::table('tbl_tax_invoice')
                    ->where('invoice_id', $invoiceId)
                    ->update([
                        'markas_disputed' => 'yes',
                        'disputed_remarks' => $disputeReason ?? 'Invoice marked as disputed from invoice management',
                        'disputed_by' => $adminId,
                        'disputed_date' => now()
                    ]);

                // Create audit trail record using updated_by column (current schema)
                $disputeId = DB::table('tbl_invoice_disputes')->insertGetId([
                    'invoice_id' => $invoiceId,
                    'dispute_type' => 'full_invoice',
                    'disputed_amount' => $remainingBalance > 0 ? $remainingBalance : $totalAmount,
                    'dispute_reason' => $disputeReason ?? 'Invoice marked as disputed from invoice management',
                    'dispute_status' => 'active',
                    'updated_by' => $adminId, // Using the current column structure
                    'disputed_date' => now(),
                    'deleteflag' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Invoice has been marked as disputed successfully!',
                    'data' => [
                        'dispute_id' => $disputeId,
                        'invoice_id' => $invoiceId,
                        'invoice_no' => $invoice->po_no ?? 'N/A',
                        'company_name' => $invoice->cus_com_name ?? 'N/A',
                        'total_amount' => $totalAmount,
                        'amount_paid' => $totalPaid,
                        'disputed_amount' => $remainingBalance > 0 ? $remainingBalance : $totalAmount,
                        'disputed_on' => now()->format('d M Y, h:i A'),
                        'ui_message' => 'This invoice will now appear only in the Disputed section of the Accounts Receivable page.'
                    ]
                ], 200);

            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to mark invoice as disputed!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove invoice from disputed status - Hybrid approach with tbl_tax_invoice columns
     * POST /api/accounts/disputes/remove-from-disputed
     */
    public function removeFromDisputed(Request $request)
    {
        try {
            // Log the complete request for debugging
            \Log::info('=== REMOVE FROM DISPUTED REQUEST DEBUG ===', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'raw_content' => $request->getContent(),
                'all_inputs' => $request->all(),
                'has_json' => $request->isJson(),
            ]);

            // Get request data - try multiple methods
            $data = $request->all();
            
            // Log initial data attempt
            \Log::info('Initial data extraction', ['data' => $data, 'empty' => empty($data)]);
            
            // If request->all() is empty, parse JSON manually
            if (empty($data)) {
                $jsonContent = $request->getContent();
                \Log::info('Attempting manual JSON parsing', ['content' => $jsonContent]);
                
                if ($jsonContent) {
                    // First, try to clean the JSON by removing comments
                    $cleanedJson = preg_replace('#//.*#', '', $jsonContent); // Remove single-line comments
                    $cleanedJson = preg_replace('#/\*.*?\*/#s', '', $cleanedJson); // Remove multi-line comments
                    
                    \Log::info('Cleaned JSON content', ['original' => $jsonContent, 'cleaned' => $cleanedJson]);
                    
                    $data = json_decode($cleanedJson, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        \Log::error('JSON parsing failed even after cleaning', [
                            'error' => json_last_error_msg(),
                            'original_content' => $jsonContent,
                            'cleaned_content' => $cleanedJson
                        ]);
                        
                        return response()->json([
                            'status' => 'failed',
                            'message' => 'Invalid JSON in request body. Please remove comments from JSON.',
                            'json_error' => json_last_error_msg(),
                            'help' => 'JSON does not support comments. Remove // or /* */ from your request body.',
                            'raw_content' => $jsonContent,
                            'cleaned_content' => $cleanedJson
                        ], 400);
                    }
                    \Log::info('Manual JSON parsing successful', ['data' => $data]);
                }
            }
            
            // Try Laravel's json() method if still empty
            if (empty($data) && $request->isJson()) {
                try {
                    $data = $request->json()->all();
                    \Log::info('Laravel json() method successful', ['data' => $data]);
                } catch (\Exception $e) {
                    \Log::warning('Laravel json() method failed', ['error' => $e->getMessage()]);
                }
            }
            
            // Final fallback - if data is still empty
            if (empty($data)) {
                $data = [];
                \Log::warning('All parsing methods failed, using empty array');
            }
            
            $invoiceId = $data['invoice_id'] ?? null;
            $confirmation = $data['confirmation'] ?? null;
            $resolveReason = $data['resolve_reason'] ?? null;
            $resolvedBy = $data['resolved_by'] ?? null;

            \Log::info('Final extracted values', [
                'invoice_id' => $invoiceId,
                'invoice_id_type' => gettype($invoiceId),
                'confirmation' => $confirmation,
                'resolve_reason' => $resolveReason,
                'resolved_by' => $resolvedBy
            ]);

            // Enhanced validation - handle both string and numeric values including 0
            if (empty($invoiceId) && $invoiceId !== 0 && $invoiceId !== '0') {
                \Log::error('Invoice ID validation failed', [
                    'invoice_id' => $invoiceId,
                    'all_data' => $data,
                    'request_details' => [
                        'method' => $request->method(),
                        'content_type' => $request->header('Content-Type'),
                        'is_json' => $request->isJson(),
                        'content_length' => strlen($request->getContent())
                    ]
                ]);
                
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice ID is required',
                    'debug' => [
                        'invoice_id_value' => $invoiceId,
                        'invoice_id_type' => gettype($invoiceId),
                        'received_data' => $data,
                        'raw_content' => $request->getContent()
                    ]
                ], 400);
            }

            // Convert to integer if it's a string number
            $invoiceId = (int)$invoiceId;
            \Log::info('Converted invoice ID to integer', ['invoice_id' => $invoiceId]);

            if (!$confirmation) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Confirmation is required to proceed!'
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

            // Check if invoice is currently disputed (using new column)
            if ($invoice->markas_disputed !== 'yes') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice #' . ($invoice->po_no ?? $invoiceId) . ' is not currently disputed!'
                ], 400);
            }

            // Get resolved_by from request payload (sent by React app)
            // Use fallback value if not provided
            $adminId = $resolvedBy ?? 1;

            DB::beginTransaction();

            try {
                // Update main table to remove dispute status
                DB::table('tbl_tax_invoice')
                    ->where('invoice_id', $invoiceId)
                    ->update([
                        'markas_disputed' => 'no',
                        'disputed_remarks' => null,
                        'disputed_by' => null,
                        'disputed_date' => null
                    ]);

                // Update the latest dispute record status to resolved using updated_by column (current schema)
                DB::table('tbl_invoice_disputes')
                    ->where('invoice_id', $invoiceId)
                    ->where('dispute_status', 'active')
                    ->update([
                        'dispute_status' => 'resolved',
                        'updated_by' => $adminId, // Using the current column structure
                        'resolved_date' => now(),
                        'resolution_notes' => $resolveReason ?? 'Dispute resolved from invoice management',
                        'updated_at' => now()
                    ]);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Invoice has been removed from disputed status successfully!',
                    'data' => [
                        'invoice_id' => $invoiceId,
                        'invoice_no' => $invoice->po_no ?? 'N/A',
                        'company_name' => $invoice->cus_com_name ?? 'N/A',
                        'resolved_on' => now()->format('d M Y, h:i A'),
                        'resolved_by' => $adminId,
                        'ui_message' => 'This invoice will now appear back in the main invoice list.'
                    ]
                ], 200);

            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to remove invoice from disputed status!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove from disputed without auth requirements (for testing)
     * POST /api/accounts/disputes-test/remove-from-disputed
     */
    public function removeFromDisputedNoAuth(Request $request)
    {
        try {
            // Extract data using multiple methods
            $invoiceId = $request->invoice_id ?? 
                        $request->input('invoice_id') ?? 
                        ($request->json() ? $request->json('invoice_id') : null);

            $confirmation = $request->confirmation ?? 
                           $request->input('confirmation') ?? 
                           ($request->json() ? $request->json('confirmation') : null);

            $resolveReason = $request->resolve_reason ?? 
                            $request->input('resolve_reason') ?? 
                            ($request->json() ? $request->json('resolve_reason') : null);

            // Manual JSON parsing fallback
            if (!$invoiceId) {
                $rawContent = $request->getContent();
                if ($rawContent) {
                    $jsonData = json_decode($rawContent, true);
                    if ($jsonData) {
                        $invoiceId = $jsonData['invoice_id'] ?? null;
                        $confirmation = $jsonData['confirmation'] ?? null;
                        $resolveReason = $jsonData['resolve_reason'] ?? null;
                    }
                }
            }

            // Validation
            if (!$invoiceId) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice ID is required',
                    'debug_info' => [
                        'request_method' => $request->method(),
                        'content_type' => $request->header('Content-Type'),
                        'raw_content' => $request->getContent(),
                        'all_data' => $request->all()
                    ]
                ], 400);
            }

            if (!$confirmation) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Confirmation is required to proceed!'
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

            // Check if invoice is currently disputed
            $activeDispute = DB::table('tbl_invoice_disputes')
                ->where('invoice_id', $invoiceId)
                ->where('dispute_status', 'active')
                ->where('deleteflag', 'active')
                ->first();

            if (!$activeDispute) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invoice #' . ($invoice->po_no ?? $invoiceId) . ' is not currently disputed!'
                ], 400);
            }

            // Resolve the dispute by updating status
            DB::table('tbl_invoice_disputes')
                ->where('dispute_id', $activeDispute->dispute_id)
                ->update([
                    'dispute_status' => 'resolved',
                    'resolved_by' => auth()->id() ?? 1,
                    'resolved_date' => now(),
                    'resolve_reason' => $resolveReason ?? 'Dispute resolved from invoice management',
                    'updated_at' => now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice has been removed from disputed status successfully!',
                'data' => [
                    'dispute_id' => $activeDispute->dispute_id,
                    'invoice_id' => $invoiceId,
                    'invoice_no' => $invoice->po_no ?? 'N/A',
                    'company_name' => $invoice->cus_com_name ?? 'N/A',
                    'dispute_amount' => $activeDispute->disputed_amount ?? 0,
                    'resolved_on' => now()->format('d M Y, h:i A'),
                    'resolved_by' => auth()->id() ?? 1,
                    'ui_message' => 'This invoice will now appear back in the main invoice list.'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to remove invoice from disputed status!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get disputed receivables list matching the UI format
     * GET /api/accounts/disputes/receivables-list
     */
    public function getDisputedReceivablesList(Request $request)
    {
        try {
            // Get pagination parameters
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('records', 10);
            
            // Get filter parameters
            $companyName = $request->get('company_name');
            $accountManager = $request->get('account_manager');
            $status = $request->get('status');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            // Query invoices marked as disputed directly from tax invoice table
            $query = DB::table('tbl_tax_invoice')
                ->select([
                    'invoice_id',
                    'po_no as invoice_number',
                    'invoice_generated_date',
                    'po_due_date as due_date',
                    'cus_com_name as company_name',
                    'prepared_by as account_manager_id',
                    'sub_total_amount_without_gst',
                    'total_gst_amount',
                    'freight_amount',
                    'exchange_rate',
                    'markas_disputed',
                    'disputed_remarks',
                    'disputed_by',
                    'disputed_date'
                ])
                ->where('deleteflag', 'active')
                ->where('markas_disputed', 'yes'); // Only disputed invoices

            // Apply filters
            if (!empty($companyName)) {
                $query->where('cus_com_name', 'like', "%{$companyName}%");
            }

            if (!empty($accountManager)) {
                $query->where('prepared_by', $accountManager);
            }

            if (!empty($dateFrom) && !empty($dateTo)) {
                $query->whereBetween('invoice_generated_date', [$dateFrom, $dateTo]);
            }

            // Get total count for pagination
            $total = clone $query;
            $totalCount = $total->count();

            // Apply pagination and get results
            $disputes = $query->orderBy('disputed_date', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Format the data to match your UI
            $formattedDisputes = $disputes->map(function ($dispute) {
                // Calculate total amount with exchange rate
                $exchangeRate = $dispute->exchange_rate ?? 1;
                $totalAmount = (($dispute->sub_total_amount_without_gst ?? 0) + 
                               ($dispute->total_gst_amount ?? 0) + 
                               ($dispute->freight_amount ?? 0)) * $exchangeRate;

                // Get total payments received for this invoice
                $totalPaid = DB::table('tbl_payment_received')
                    ->where('invoice_id', $dispute->invoice_id)
                    ->where('deleteflag', 'active')
                    ->sum('payment_received_value') ?? 0;

                // Calculate balance due (disputed amount)
                $balanceDue = $totalAmount - $totalPaid;

                // Calculate days from due date
                $dueDate = $dispute->due_date ? Carbon::parse($dispute->due_date) : null;
                $daysDiff = 0;
                $statusText = '';
                
                if ($dueDate) {
                    $daysDiff = $dueDate->diffInDays(Carbon::now());
                    if ($dueDate->isPast()) {
                        $statusText = "OVERDUE: {$daysDiff} DAYS";
                    } else {
                        $statusText = "DUE: {$daysDiff} DAYS";
                    }
                }

                return [
                    'invoice_id' => $dispute->invoice_id,
                    'company_name' => $dispute->company_name,
                    'invoice' => $dispute->invoice_number,
                    'invoice_date' => $dispute->invoice_generated_date ? 
                        Carbon::parse($dispute->invoice_generated_date)->format('d M Y') : null,
                    'amount' => '₹' . number_format($totalAmount, 0),
                    'due_date' => $dueDate ? $dueDate->format('d M Y') : null,
                    'status' => $statusText,
                    'adp' => $daysDiff . ' days',
                    'account_manager' => admin_name($dispute->account_manager_id ?? 0),
                    'total_value_due' => '₹' . number_format($totalAmount, 2),
                    'value_received' => '₹' . number_format($totalPaid, 2),
                    'balance_due' => '₹' . number_format($balanceDue, 2),
                    'dispute_details' => [
                        'invoice_id' => $dispute->invoice_id,
                        'disputed_amount' => '₹' . number_format($balanceDue, 2), // Balance due, not total
                        'dispute_reason' => $dispute->disputed_remarks,
                        'disputed_date' => $dispute->disputed_date ? 
                            Carbon::parse($dispute->disputed_date)->format('d M Y') : null,
                        'disputed_by' => admin_name($dispute->disputed_by ?? 0)
                    ]
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedDisputes,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_records' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage)
                ],
                'summary' => [
                    'total_disputed_receivables' => $totalCount
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch disputed receivables: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of disputed invoices with filtering and pagination
     * GET /api/accounts/disputes/list
     */
    public function getDisputedInvoices(Request $request)
    {
        try {
            // Get query parameters for filtering and pagination
            $page = $request->get('pageno', 1);
            $pageSize = $request->get('records', 10);
            $sortKey = $request->get('sort_key', 'created_at');
            $sortValue = $request->get('sort_value', 'desc');
            $search = $request->get('search', '');
            $company = $request->get('company', '');
            $status = $request->get('status', 'active');

            // Build query for disputed invoices
            $query = InvoiceDispute::query()
                ->where('dispute_status', $status)
                ->where('deleteflag', 'active');

            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('dispute_reason', 'like', "%{$search}%")
                      ->orWhere('dispute_id', 'like', "%{$search}%")
                      ->orWhere('invoice_id', 'like', "%{$search}%");
                });
            }

            // Apply company filter (this will need to be joined with invoice table if needed)
            // For now, we'll skip this filter since we don't have company data directly

            // Apply sorting
            $allowedSortKeys = [
                'created_at' => 'created_at',
                'disputed_amount' => 'disputed_amount',
                'dispute_id' => 'dispute_id',
                'dispute_status' => 'dispute_status'
            ];

            $sortColumn = $allowedSortKeys[$sortKey] ?? 'created_at';
            $sortDirection = in_array(strtolower($sortValue), ['asc', 'desc']) ? $sortValue : 'desc';
            $query->orderBy($sortColumn, $sortDirection);

            // Get total count for pagination
            $totalItems = $query->count();
            $totalPages = ceil($totalItems / $pageSize);

            // Apply pagination
            $disputes = $query->skip(($page - 1) * $pageSize)
                            ->take($pageSize)
                            ->get();

            // Format the dispute data
            $disputeData = $disputes->map(function ($dispute) {
                return [
                    'dispute_id' => $dispute->dispute_id,
                    'invoice_id' => $dispute->invoice_id,
                    'payment_received_id' => $dispute->payment_received_id,
                    'dispute_amount' => number_format($dispute->disputed_amount ?? 0, 2),
                    'dispute_reason' => $dispute->dispute_reason ?? 'No reason provided',
                    'dispute_type' => $dispute->dispute_type ?? 'general',
                    'dispute_status' => $dispute->dispute_status,
                    'disputed_by' => admin_name($dispute->disputed_by ?? 0),
                    'disputed_on' => $dispute->disputed_date ? $dispute->disputed_date->format('d M Y') : ($dispute->created_at ? $dispute->created_at->format('d M Y') : null),
                    'resolved_by' => admin_name($dispute->resolved_by ?? 0),
                    'resolved_on' => $dispute->resolved_date ? $dispute->resolved_date->format('d M Y') : null,
                    'last_updated_by' => admin_name($dispute->last_updated_by ?? 0),
                    'last_updated' => $dispute->last_updated_date ? $dispute->last_updated_date->format('d M Y, h:i A') : ($dispute->updated_at ? $dispute->updated_at->format('d M Y, h:i A') : null),
                    'resolution_notes' => $dispute->resolution_notes ?? '',
                    'update_remarks' => $dispute->update_remarks ?? ''
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Disputed invoices retrieved successfully',
                'data' => $disputeData,
                'pagination' => [
                    'pageno' => (int) $page,
                    'records' => (int) $pageSize,
                    'totalItems' => $totalItems,
                    'totalPages' => $totalPages
                ],
                'sorting' => [
                    'sortKey' => $sortKey,
                    'sortValue' => $sortValue
                ],
                'filters' => [
                    'search' => $search,
                    'company' => $company,
                    'status' => $status
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error retrieving disputed invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
