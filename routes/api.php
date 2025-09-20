<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EnquiryController;
use App\Http\Controllers\Api\LeadsController;
use App\Http\Controllers\Api\OffersController;
use App\Http\Controllers\Api\PurchaseController;

use App\Http\Controllers\Api\InsidesalesController;
use App\Http\Controllers\Api\CountDashbordController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Accounts\VendorPaymentDisputeController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// Public routes of authtication
// Route::controller(LoginRegisterController::class)->group(function() {
//     //Route::post('/register', 'register');
//     Route::post('/login', 'login');
 // date_default_timezone_set("Asia/Calcutta");  // for india time zone time add by rumit on dated 13-jan-2025
    ####################Dashboard#####################################################################

    Route::get('/dashboard/users', [DashboardController::class, 'users']);
	Route::get('/dashboard/users_as_per_team', [DashboardController::class, 'users_as_per_team']);
    Route::get('/dashboard/financial_year', [DashboardController::class, 'financial_year']);
    Route::get('/dashboard/account_receivables', [DashboardController::class, 'account_receivables']);
    Route::get('/dashboard/orders_in_hand', [DashboardController::class, 'orders_in_hand']);
    Route::get('/dashboard/opportunities', [DashboardController::class, 'opportunities']);
    Route::get('/dashboard/menu_data', [DashboardController::class, 'menu_data']);
    Route::get('/dashboard/sub_menu_data', [DashboardController::class, 'sub_menu_data']);	
	Route::get('/dashboard/tasks_data', [DashboardController::class, 'tasks_data']);
    Route::get('/dashboard/escalations_data', [DashboardController::class, 'escalations_data']);
    Route::get('/dashboard/escalations_data_snooze', [DashboardController::class, 'escalations_data_snooze']);	
	Route::get('/dashboard/total_executed', [DashboardController::class, 'total_executed']);		
	Route::get('/dashboard/kill_enq_data', [DashboardController::class, 'kill_enq_data']);	
    Route::get('/dashboard/dashboard_all_data_count', [DashboardController::class, 'dashboard_all_data_count']);	
    Route::get('/dashboard/dashboard_opportunities_data_count', [DashboardController::class, 'dashboard_opportunities_data_count']);
    Route::get('/dashboard/dashboard_payments_data_count', [DashboardController::class, 'dashboard_payments_data_count']);	
    Route::get('/dashboard/dashboard_open_tenders_data_count', [DashboardController::class, 'dashboard_open_tenders_data_count']);
    Route::get('/dashboard/dashboard_latest_price_update_data_count', [DashboardController::class, 'dashboard_latest_price_update_data_count']);
    Route::get('/dashboard/dashboard_trends_data_count', [DashboardController::class, 'dashboard_trends_data_count']);
    Route::get('/dashboard/dashboard_enquiries', [DashboardController::class, 'dashboard_enquiries']);
    Route::get('/dashboard/dashboard_hot_enquiries', [DashboardController::class, 'dashboard_hot_enquiries']);
    Route::get('/dashboard/dashboard_requiring_followup_enquiries', [DashboardController::class, 'dashboard_requiring_followup_enquiries']);
    Route::get('/dashboard/dashboard_sales_to_ensure_enquiries', [DashboardController::class, 'dashboard_sales_to_ensure_enquiries']);
    Route::get('/dashboard/dashboard_offers_requiring_review_enquiries', [DashboardController::class, 'dashboard_offers_requiring_review_enquiries']);
    Route::get('/dashboard/dashboard_high_value_case_enquiries', [DashboardController::class, 'dashboard_high_value_case_enquiries']);
    Route::get('/dashboard/dashboard_account_receivables', [DashboardController::class, 'dashboard_account_receivables']);
    Route::get('/dashboard/dashboard_payment_received', [DashboardController::class, 'dashboard_payment_received']);
    Route::get('/dashboard/dashboard_open_tenders', [DashboardController::class, 'dashboard_open_tenders']);
    Route::get('/dashboard/dashboard_latest_price_updates', [DashboardController::class, 'dashboard_latest_price_updates']);
    Route::get('/dashboard/get_company_name_extn', [DashboardController::class, 'get_company_name_extn']);
    Route::get('/dashboard/dashboard_potential_sale_charts', [DashboardController::class, 'dashboard_potential_sale_charts']);
    Route::get('/dashboard/dashboard_potential_trend_charts', [DashboardController::class, 'dashboard_potential_trend_charts']);
    Route::get('/dashboard/dashboard_accounts_receivables_charts', [DashboardController::class, 'dashboard_accounts_receivables_charts']);
    Route::get('/dashboard/dashboard_revenue_by_company_charts', [DashboardController::class, 'dashboard_revenue_by_company_charts']);
    Route::get('/dashboard/kill_enquiry', [DashboardController::class, 'kill_enquiry']);
    Route::get('/dashboard/snooze_enquiry', [DashboardController::class, 'snooze_enquiry']);
    Route::get('/dashboard/dead_enquiry', [DashboardController::class, 'dead_enquiry']);
    Route::get('/dashboard/get_add_note_enq_data', [DashboardController::class, 'get_add_note_enq_data']);	
	Route::get('/dashboard/add_note_to_enquiry', [DashboardController::class, 'add_note_to_enquiry']);	
	Route::get('/dashboard/get_enq_old_remarks', [DashboardController::class, 'get_enq_old_remarks']);		
    Route::get('/dashboard/tasks_update', [DashboardController::class, 'tasks_update']);
    Route::get('/dashboard/hot_unhot_enquiry', [DashboardController::class, 'hot_unhot_enquiry']);
    Route::get('/dashboard/month_sales_to_ensure_update', [DashboardController::class, 'month_sales_to_ensure_update']);
    Route::get('/dashboard/offer_stage_update', [DashboardController::class, 'offer_stage_update']);
    Route::get('/dashboard/dashboard_overview', [DashboardController::class, 'dashboard_overview']);
    Route::get('/dashboard/assign_enquiry_basis_segment', [DashboardController::class, 'assign_enquiry_basis_segment']);
	Route::get('/dashboard/mark_as_active_enquiry', [DashboardController::class, 'mark_as_active_enquiry']);
	Route::post('/dashboard/set_a_reminder', [DashboardController::class, 'set_a_reminder']);	
	Route::get('/dashboard/orders_executed_bar_graph', [DashboardController::class, 'orders_executed_bar_graph']);		
    
    ################Dashboard END##################################################################################

    ################Enquiry Assign################################################################################
	Route::get('/enquiry/listing', [EnquiryController::class, 'listing']);
	Route::get('/enquiry/listing_eloquent', [EnquiryController::class, 'listing_eloquent']);
	Route::get('/enquiry/listing_old', [EnquiryController::class, 'listing_old']);	
	Route::get('/enquiry/listing_add_view_enq', [EnquiryController::class, 'listing_add_view_enq']);
	Route::get('/enquiry/enq_listing_export_to_excel', [EnquiryController::class, 'enq_listing_export_to_excel']);	
    Route::get('/enquiry/generate_eid', [EnquiryController::class, 'generate_eid']);
    Route::get('/enquiry/add_new_enquiry', [EnquiryController::class, 'add_new_enquiry']);		
    Route::get('/enquiry/enq_stage', [EnquiryController::class, 'enq_stage']);		
    Route::get('/enquiry/product_categories', [EnquiryController::class, 'product_categories']);			
    Route::get('/enquiry/service_categories', [EnquiryController::class, 'service_categories']);				
    Route::get('/enquiry/cust_segment', [EnquiryController::class, 'cust_segment']);				
    Route::get('/enquiry/enquiry_source', [EnquiryController::class, 'enquiry_source']);	       
    Route::get('/enquiry/enquiry_status_master', [EnquiryController::class, 'enquiry_status_master']);	               
    Route::get('/enquiry/get_edit_enquiry_data', [EnquiryController::class, 'get_edit_enquiry_data']);	               	
    Route::get('/enquiry/get_inside_sales_enquiry_data', [EnquiryController::class, 'get_inside_sales_enquiry_data']);	

	               		
	
    Route::get('/enquiry/country', [EnquiryController::class, 'country']);	               		
    Route::get('/enquiry/state', [EnquiryController::class, 'state']);	               			
    Route::get('/enquiry/city', [EnquiryController::class, 'city']);	               				
    Route::get('/enquiry/edit_enquiry_details', [EnquiryController::class, 'edit_enquiry_details']);	               					
    Route::get('/enquiry/getcompanyexists_table', [EnquiryController::class, 'getcompanyexists_table']);	               						
    Route::get('/enquiry/getcompanyexists_table_person_list', [EnquiryController::class, 'getcompanyexists_table_person_list']);	               							
	Route::get('/enquiry/transfer_account_manager', [EnquiryController::class, 'transfer_account_manager']);	               							
	Route::get('/enquiry/product_list_by_category_id', [EnquiryController::class, 'product_list_by_category_id']);	               								
	Route::get('/enquiry/service_list_by_category_id', [EnquiryController::class, 'service_list_by_category_id']);	               									
	Route::get('/enquiry/service_period_master', [EnquiryController::class, 'service_period_master']);	               										
	Route::get('/enquiry/stage_colors', [EnquiryController::class, 'stage_colors']);	               											
	Route::get('/enquiry/send_cord_order', [EnquiryController::class, 'send_cord_order']);				//gg.
	Route::get('/enquiry/price_type_list', [EnquiryController::class, 'price_type_list']);	//price list			
	Route::get('/enquiry/enquiry_history_by_enq_id', [EnquiryController::class, 'enquiry_history_by_enq_id']);								
		
################Enquiry Assign END############################################################################


################Leads Starts##################################################################################
	Route::get('/leads/leadslisting', [LeadsController::class, 'leadslisting']);
	Route::get('/leads/lead_listing_export_to_excel', [LeadsController::class, 'lead_listing_export_to_excel']);		
	Route::get('/leads/latest_po', [LeadsController::class, 'latest_po']);	               									
	Route::get('/leads/highest_value_po', [LeadsController::class, 'highest_value_po']);	               										
	Route::get('/leads/highest_value_po_by_item', [LeadsController::class, 'highest_value_po_by_item']);	               											
	Route::get('/leads/customer_po', [LeadsController::class, 'customer_po']);	               												
	Route::get('/leads/po_by_customer_segment', [LeadsController::class, 'po_by_customer_segment']);	               													
	Route::post('/leads/add_lead', [LeadsController::class, 'add_lead']);	               														
	Route::post('/leads/edit_lead', [LeadsController::class, 'edit_lead']);	               															
	Route::post('/leads/add_new_company', [LeadsController::class, 'add_new_company']);	               															
	Route::post('/leads/edit_company', [LeadsController::class, 'edit_company']);	               																
	Route::post('/leads/add_new_person_in_company', [LeadsController::class, 'add_new_person_in_company']);	               																
	Route::get('/leads/get_person_details', [LeadsController::class, 'get_person_details']);	   
	Route::post('/leads/edit_person_in_company', [LeadsController::class, 'edit_person_in_company']);	               																	
	Route::get('/leads/get_comp_list', [LeadsController::class, 'get_comp_list']);	               																
	Route::get('/leads/get_comp_details', [LeadsController::class, 'get_comp_details']);	               																	
	Route::get('/leads/comp_extn', [LeadsController::class, 'comp_extn']);	               																	
	Route::get('/leads/comp_department', [LeadsController::class, 'comp_department']);	               																		
	Route::get('/leads/comp_designation', [LeadsController::class, 'comp_designation']);	               																			
	Route::get('/leads/salutation_master', [LeadsController::class, 'salutation_master']);	               																				
	Route::get('/leads/lead_products_listing', [LeadsController::class, 'lead_products_listing']);	               																					
	Route::post('/leads/edit_lead_requirements', [LeadsController::class, 'edit_lead_requirements']);	               																						
	Route::post('/leads/delete_lead_requirements', [LeadsController::class, 'delete_lead_requirements']);	               																							

################Leads END#####################################################################################

###############Offers Starts##################################################################################    
//	Route::get('/offers/offerslisting', [OffersController::class, 'offerslisting']);	for json test
//	Route::get('/offers/offers_listing', [OffersController::class, 'offerslisting']);	for json test react developer
	Route::get('/offers/offerslisting', [OffersController::class, 'offers_listing']);	
	Route::get('/offers/offers_listing', [OffersController::class, 'offers_listing']);	
	
	
	Route::get('/offers/offers_listing_export_to_excel', [OffersController::class, 'offers_listing_export_to_excel']);		
	Route::get('/offers/offer_status_master', [OffersController::class, 'offer_status_master']);		
	Route::post('/offers/create_offer', [OffersController::class, 'create_offer']);		
	Route::post('/offers/edit_offer', [OffersController::class, 'edit_offer']);			
	Route::get('/offers/qbd_table', [OffersController::class, 'qbd_table']);			
	Route::get('/offers/supply_order_delivery_terms', [OffersController::class, 'supply_order_delivery_terms']);				
	Route::get('/offers/supply_order_payment_terms', [OffersController::class, 'supply_order_payment_terms']);					
	Route::get('/offers/warranty_master', [OffersController::class, 'warranty_master']);						
	Route::get('/offers/offer_validity_master', [OffersController::class, 'offer_validity_master']);							
	Route::get('/offers/company_branch_address', [OffersController::class, 'company_branch_address']);							
	Route::get('/offers/company_bank_address', [OffersController::class, 'company_bank_address']);								
	Route::get('/offers/enquiry_history', [OffersController::class, 'enquiry_history']);								
	Route::get('/offers/enquiry_history_with_offer_history', [OffersController::class, 'enquiry_history_with_offer_history']);									
	
	Route::post('/offers/edit_offer_customer_details', [OffersController::class, 'edit_offer_customer_details']);									
	Route::get('/offers/task_list', [OffersController::class, 'task_list']);										
	Route::post('/offers/add_task_by_offer', [OffersController::class, 'add_task_by_offer']);									
	Route::post('/offers/delete_offer_requirements', [OffersController::class, 'delete_offer_requirements']);										
	Route::get('/offers/offer_tasks_list', [OffersController::class, 'offer_tasks_list']);											
	Route::get('/offers/view_offer', [OffersController::class, 'view_offer']);												
	Route::post('/offers/kill_offer', [OffersController::class, 'kill_offer']);
	Route::post('/offers/move_to_opportunity', [OffersController::class, 'move_to_opportunity']);											
	Route::post('/offers/gstno_check_api', [OffersController::class, 'gstno_check_api']);	
	Route::post('/offers/complete_task', [OffersController::class, 'complete_task']);		
	Route::post('/offers/create_proforma_invoice', [OffersController::class, 'create_proforma_invoice']);			
	Route::post('/offers/create_delivery_order', [OffersController::class, 'create_delivery_order']);				
	Route::get('/offers/proforma_invoice_data_for_pdf', [OffersController::class, 'proforma_invoice_data_for_pdf']);				
	Route::post('/offers/edit_delivery_order', [OffersController::class, 'edit_delivery_order']);					
	Route::post('/offers/edit_proforma_invoice/', [OffersController::class, 'edit_proforma_invoice']);					
	Route::get('/offers/proforma_invoice_listing', [OffersController::class, 'proforma_invoice_listing']);
	Route::get('/offers/hot_unhot_offer', [OffersController::class, 'hot_unhot_offer']);	
	Route::get('/offers/mode_master', [OffersController::class, 'mode_master']);		
	Route::get('/offers/get_delivery_order_data', [OffersController::class, 'get_delivery_order_data']);			
	Route::get('/offers/offerslist_for_sales_cycle', [OffersController::class, 'offerslist_for_sales_cycle']);		
	Route::get('/offers/sales_cycle_total', [OffersController::class, 'sales_cycle_total']);
	Route::get('/offers/offer_product_details', [OffersController::class, 'offer_product_details']);	
	Route::get('/offers/monthly_sales_target', [OffersController::class, 'monthly_sales_target']);		//sales to ensure
	Route::get('/offers/company_directory_listing', [OffersController::class, 'company_directory_listing']);		//COmpany directory listing	
	Route::get('/offers/fav_comp', [OffersController::class, 'fav_comp']);	//Company directory mark as favorite company
	Route::get('/offers/tes_listing', [OffersController::class, 'tes_listing']);//Company directory mark as favorite company	
	Route::get('/offers/tes_listing_details', [OffersController::class, 'tes_listing_details']);//Company directory mark as favorite company		
	Route::get('/offers/customer_po_manager', [OffersController::class, 'customer_po_manager']);//customers PO manager	
	Route::get('/offers/pqv_listing', [OffersController::class, 'pqv_listing']);//customers PO manager		
	Route::get('/offers/sqv_listing', [OffersController::class, 'sqv_listing']);//customers PO manager			
	Route::get('/offers/product_type_class_master', [OffersController::class, 'product_type_class_master']);//customers PO manager			
    Route::get('/offers/currency_master', [OffersController::class, 'currency_master']);	               		
    Route::get('/offers/delete_delivery_order', [OffersController::class, 'delete_delivery_order']);	               			
	Route::post('/offers/offers_tasks_update', [OffersController::class, 'offers_tasks_update']);	               				
	Route::get('/offers/offer-history/{orderId}', [OffersController::class, 'getOfferHistory']);
	//Route::get('/offer-history/{orderId}', [OffersController::class, 'getOfferHistory']);
	Route::get('/offer-history/view/{id}', [OffersController::class, 'viewOfferSnapshot']);
	
	Route::post('/offers/create_pre_approval_credit_request', [OffersController::class, 'createPreApprovalCreditRequest']);	
	Route::get('/offers/credit_control_list', [OffersController::class, 'listPreApprovalCreditRequests']);	
	Route::post('/offers/credit_control_approval', [OffersController::class, 'updatePreApprovalCreditRequestStatus']);		
  Route::get('/offers/invoice-delivery-method', [OffersController::class, 'invoice_delivery_method']);

################Offers END################################################################################    



################Purchase Starts############################################################################    

#	Route::get('/purchase/vendorslisting', [PurchaseController::class, 'vendorslisting']);	
	Route::post('/purchase/addNewVendor', [PurchaseController::class, 'addNewVendor']);
	Route::get('/purchase/vendor_payment_terms_master', [PurchaseController::class, 'vendor_payment_terms_master']);
	Route::get('/purchase/price_basis_master', [PurchaseController::class, 'price_basis_master']);
#	Route::get('purchase/vendorslisting', [PurchaseController::class, 'vendorDetailsById']);
	Route::post('/purchase/editVendor/{vendor_id}', [PurchaseController::class, 'editVendor']);
Route::get('/purchase-order/details', [PurchaseController::class, 'getPurchaseOrderDetails']);
	
	// Keep this for listing all vendors
Route::get('/purchase/vendorslisting', [PurchaseController::class, 'vendorslisting']);

// Change this to avoid conflict
Route::get('/purchase/vendorDetailsById', [PurchaseController::class, 'vendorDetailsById']);

Route::post('/purchase/vendor/favorite', [PurchaseController::class, 'markVendorAsFavorite']);
Route::get('/purchase/vendor/products/{vendor_id}', [PurchaseController::class, 'vendorProducts'])->name('purchase.vendor.products');
Route::get('/purchase/vendor-products', [PurchaseController::class, 'getVendorProducts']);
Route::post('/purchase/vendor-products', [PurchaseController::class, 'storeVendorProduct']);
Route::post('/purchase/vendor-products/update', [PurchaseController::class, 'updateVendorProduct']);
Route::post('/purchase/vendor-products/delete', [PurchaseController::class, 'deleteVendorProduct']);
Route::get('/purchase/vendor-order-history', [PurchaseController::class, 'vendorOrderHistory']);
Route::post('/vendors/add-bank/{vendor_id}', [PurchaseController::class, 'addNewVendorBank']);
Route::post('/vendors/edit-bank/{vendor_bank_details_id}', [PurchaseController::class, 'editVendorBank']);
Route::post('/vendors/contacts/{vendor_id}', [PurchaseController::class, 'addVendorContact']);
Route::post('/vendor-contact/edit/{vendor_contacts_id}', [PurchaseController::class, 'editVendorContact']);
Route::post('/vendor-status/edit/{vendor_id}', [PurchaseController::class, 'editVendorStatus']);
Route::get('/purchase/vendor-products/po', [PurchaseController::class, 'getVendorProductsforPo']);
Route::get('/purchase/vendor-autosuggest', [PurchaseController::class, 'vendorAutoSuggest']);
Route::post('/purchase-order/create', [PurchaseController::class, 'createPurchaseOrder']);
Route::post('/purchase-order/edit', [PurchaseController::class, 'editPurchaseOrder']);

Route::post('/purchase-order/update-status', [PurchaseController::class, 'updatePOApprovalStatus']);


//Route::get('purchase/trends', [PurchaseController::class, 'purchaseTrends']);


Route::get('purchase/trends', [PurchaseController::class, 'purchaseTrends']);
Route::get('purchase/trends-yoy', [PurchaseController::class, 'purchaseTrendsYOY']);
Route::get('purchase/trends-mom', [PurchaseController::class, 'purchaseTrendsMOM']);
Route::get('purchase/trends-qoq', [PurchaseController::class, 'purchaseTrendsQOQ']);
Route::get('purchase/trends-top-products', [PurchaseController::class, 'purchaseTrendsTopProducts']);


Route::prefix('purchase-trends')->controller(PurchaseController::class)->group(function () {
    Route::get('summary', 'purchaseSummary');
    Route::get('month-wise', 'purchaseMonthWise');
    Route::get('product-comparison', 'purchaseProductComparison');

    // Optional - stubbed for now
    Route::get('top-products', 'purchaseTopProducts');
    Route::get('top-vendors', 'purchaseTopVendors');
    Route::get('by-category', 'purchaseByCategory');
    Route::get('by-application', 'purchaseByApplication');
});



Route::get('/purchase/purchase-orders', [PurchaseController::class, 'getPurchaseOrders']);
Route::post('/purchase-order/create-final', [PurchaseController::class, 'createpofinal']);
Route::get('/purchase/purchase-orders-details', [PurchaseController::class, 'getFinalPurchaseOrderDetails']);

Route::get('/purchase/get-product-details-by-id', [PurchaseController::class, 'getProductDetailsByProId']);


Route::get('/gst-sale-types', [PurchaseController::class, 'getAllGstSaleTypes']);//get all gst rates
Route::delete('/delete-purchase-order', [PurchaseController::class, 'deletePurchaseOrder']);//delete po

Route::get('purchase/vendors/', [PurchaseController::class, 'getAllActiveVendors']);


//purchase alerts:
Route::get('purchase/purchasealerts', [PurchaseController::class, 'purchasealerts']);
Route::get('purchase/purchaseforecasting', [PurchaseController::class, 'purchaseforecasting']); 
Route::get('purchase/stockbreach', [PurchaseController::class, 'stockbreach']); 


/*
Default current FY
GET https://laravelapi.knowbuild.com/laravelapi/api/purchase/purchaseordersummary

Filter by vendor
GET https://laravelapi.knowbuild.com/laravelapi/api/purchase/purchaseordersummary?vendor_id=75

Filter by purchase type
GET https://laravelapi.knowbuild.com/laravelapi/api/purchase/purchaseordersummary?purchase_type=import

With date range
GET https://laravelapi.knowbuild.com/laravelapi/api/purchase/purchaseordersummary?from_date=2024-04-01&to_date=2025-03-31
*/
Route::get('purchase/purchaseordersummary', [PurchaseController::class, 'getPurchaseOrderSummary']); 
Route::get('purchase/top-vendors-by-fy', [PurchaseController::class, 'topVendorsByFY']); 
Route::get('purchase/vendor-list-by-product', [PurchaseController::class, 'getVendorsByProductId']); 




//Draft PO:
	
Route::post('/purchase-order/create-draft-po', [PurchaseController::class, 'createDraftPurchaseOrder']);
//Route::post('/purchase-order/vendor_payment_terms_master', [PurchaseController::class, 'vendor_payment_terms_master']);
Route::post('/purchase-order/create-or-update-draft-po', [PurchaseController::class, 'createOrUpdateDraftPO']);

Route::put('/purchase-order/update-po-invoice/{id}', [PurchaseController::class, 'updatePoInvoice']);
Route::delete('/purchase-order/delete-po-invoice/{id}', [PurchaseController::class, 'deletePoInvoice']);
/*Route::post('/debug-request', function (Request $request) {
    return response()->json([
        'raw_input' => $request->all(),
        'vendor_id' => $request->input('vendor_id'),
        'pro_id' => $request->input('pro_id'),
        'pro_qty' => $request->input('pro_qty'),
    ]);
});*/

Route::get('/purchase-orders/{po_id}/invoices',[PurchaseController::class, 'getPoInvoices']);
//Route::post('/purchase-order/create-or-update-invoice',[PurchaseController::class, 'storeOrUpdate']);
//Route::post('/purchase-order/upload-po-invoice',[PurchaseController::class, 'store']);
Route::post('purchase-order/upload-po-invoice/{poId}', [PurchaseController::class, 'store']);
Route::post('/purchase-order/update-po-invoice',[PurchaseController::class, 'storeOrUpdate']);
Route::get('/purchase-order/heat-map-import-export',[PurchaseController::class, 'getPurchaseOrderTypeCounts']);
Route::get('/purchase-order/country-wise-map',[PurchaseController::class, 'getPurchaseOrderTypeCountryWise']);

################Purchase END##########################################################################################################################    

#######################################################INSIDE sales starts############################################################################    
Route::get('/inside_sales/inside_sales_listing', [InsidesalesController::class, 'inside_sales_listing']);	
Route::get('/inside_sales/assigned_today_listing', [InsidesalesController::class, 'assigned_today_listing']);	
Route::get('/inside_sales/inside_sales_listing_overdue', [InsidesalesController::class, 'inside_sales_listing_overdue']);	

Route::delete('/inside_sales/delete_enquiry', [InsidesalesController::class, 'delete_enquiry']);// for delete
Route::delete('/inside_sales/delete_multiple_enquiry', [InsidesalesController::class, 'delete_multiple_enquiry']);// for delete
Route::get('/inside_sales/restore_enquiry', [InsidesalesController::class, 'restore_enquiry']);// 
Route::get('/inside_sales/weekly_enquiry_metrics', [InsidesalesController::class, 'weekly_enquiry_metrics']);//trends and report weekly report enq count
Route::get('/inside_sales/monthy_inbound_metrics', [InsidesalesController::class, 'monthy_inbound_metrics']);//trends and report monthlyreport enq count
Route::get('/inside_sales/enquiry_source_trend', [InsidesalesController::class, 'enquiry_source_trend']);//trends and report monthlyreport enq count
Route::get('/inside_sales/qualified_vs_unqualified_enquiries_trend', [InsidesalesController::class, 'qualified_vs_unqualified_enquiries_trend']);//trends and report monthlyreport enq count
Route::get('/inside_sales/assign_enq_to_selected_acc_manager', [InsidesalesController::class, 'assign_enq_to_selected_acc_manager']);//Assign account manager on the basis of state/city/segment/product category
Route::get('/inside_sales/team_directory', [InsidesalesController::class, 'team_directory']);//Listing of account manager on the basis of state/city/segment/product category showing in team dirctory
Route::get('/inside_sales/assigned_enquiry_conversion_funnel', [InsidesalesController::class, 'assigned_enquiry_conversion_funnel']);//assigned_enquiry_conversion_funnel bar graph 

Route::post('/inside_sales/inside_set_a_reminder', [InsidesalesController::class, 'inside_set_a_reminder']);	




#######################################################INSIDE sales END############################################################################    

// });


// Protected routes of product and logout
Route::middleware('auth:sanctum')->group( function () {
    // Route::post('/logout', [LoginRegisterController::class, 'logout']);

    ##############Tested with JWT Token###############
    Route::get('/dashboard/test_auth', [DashboardController::class, 'test_auth']);
});



Route::get('/count-enquiries', [CountDashbordController::class, 'totalEnquiries']);
Route::get('/order-enquiry-diff-days', [CountDashbordController::class, 'getOrderEnquiryDiffDays']);
Route::post('/save-enquiry', [CountDashbordController::class, 'saveEnquiry']);
Route::get('/edit-enquiry', [CountDashbordController::class, 'editEnquiry']);
Route::get('/location-wise-enquiries', [CountDashbordController::class, 'locationWiseEnquiries']);
#######################################################Currency #################################################################################    
Route::get('/currency/update-currency-rates', [CurrencyController::class, 'updateRates']);


Route::get('download-pdf', [DownloadController::class, 'downloadPdf']);
Route::get('download-word', [DownloadController::class, 'downloadWord']);