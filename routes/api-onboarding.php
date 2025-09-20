<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Onboarding\AuthApiController; 
use App\Http\Controllers\Onboarding\ProductController; 
use App\Http\Controllers\Onboarding\GetAllController;
use App\Http\Controllers\Onboarding\OrganisationProfileController;
use App\Http\Controllers\Onboarding\SalesPurchasController;
use App\Http\Controllers\Onboarding\CurrenciesOperateController;
use App\Http\Controllers\Onboarding\CategoryController;
use App\Http\Controllers\Onboarding\ProductsServicesController;
use App\Http\Controllers\Onboarding\CustomerDirectoryController;
use App\Http\Controllers\Onboarding\HistoricalEnquiriesController;
use App\Http\Controllers\Onboarding\InviteTeamController;
use App\Http\Controllers\Onboarding\ProductExportController;
 use App\Http\Controllers\Onboarding\SegmentController;
use App\Http\Controllers\Onboarding\ProductClassificationController;
use App\Http\Controllers\Onboarding\WarrantyController;
 
// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/set-password-email-link', [AuthApiController::class, 'setPasswordEmailLink']);

    Route::post('/reset-password', [AuthApiController::class, 'resetPassword']);  
    Route::post('/reset-otp', [AuthApiController::class, 'resetOtp']);  
    Route::post('/verify-otp', [AuthApiController::class, 'verifyOtp']);

    Route::post('/login', [AuthApiController::class, 'login']);
    Route::post('/forgot-password', [AuthApiController::class, 'forgotPassword']);

    Route::post('/logout', [AuthApiController::class, 'logout'])->middleware('auth:sanctum');


});




Route::get('/countries', [GetAllController::class, 'countries']);
Route::get('/currencies', [GetAllController::class, 'currencies']);
Route::get('/fiscal-months', [GetAllController::class, 'fiscal_months']);
Route::get('/financial-years', [GetAllController::class, 'financial_years']);
Route::get('/states', [GetAllController::class, 'states']);
Route::get('/cities', [GetAllController::class, 'cities']);
Route::get('/departments', [GetAllController::class, 'departments']);

Route::get('/customer-get-step-wise', [GetAllController::class, 'getCustomer']);

Route::prefix('organisation-profile')->group(function () {
    Route::post('/save', [OrganisationProfileController::class, 'save']);
});

Route::prefix('sales-purchas')->group(function () {
    Route::post('/save', [SalesPurchasController::class, 'save']);
    Route::post('/save-logo', [SalesPurchasController::class, 'saveLogo']);
});

Route::prefix('currencies-operate')->group(function () {
    Route::post('/save', [CurrenciesOperateController::class, 'save']);
});

Route::prefix('segments')->group(function () {
    Route::get('/export', [SegmentController::class, 'export']);
    Route::post('/import-excel', [SegmentController::class, 'import']);
});
Route::prefix('product-classification')->group(function () {
    Route::get('/export', [ProductClassificationController::class, 'export']);
    Route::post('/import-excel', [ProductClassificationController::class, 'import']);
});
Route::prefix('warranty')->group(function () {
    Route::get('/export', [WarrantyController::class, 'export']);
    Route::post('/import-excel', [WarrantyController::class, 'import']);
});
Route::prefix('category')->group(function () {
    Route::get('/product-export', [CategoryController::class, 'product_category_export']);
    Route::get('/service-export', [CategoryController::class, 'service_category_export']);
    Route::post('/import-excel', [CategoryController::class, 'importExcelCategory']);

});

Route::prefix('items')->group(function () {
    Route::get('/product-export', [ProductsServicesController::class, 'product_export']);
    Route::get('/service-export', [ProductsServicesController::class, 'service_export']);
    Route::post('/product-service-import-excel', [ProductsServicesController::class, 'importExcelProductService']);
    Route::post('/service-import-excel', [ProductsServicesController::class, 'importExcelService']);
    Route::post('/1nn-product-service-import-excel', [ProductsServicesController::class, 'importExcelProductNew']);
});

Route::prefix('customer-directory')->group(function () {
    Route::get('/export', [CustomerDirectoryController::class, 'customer_directory_export']);
    Route::post('/import-excel', [CustomerDirectoryController::class, 'importExcelCustomerDirectory']);

});

Route::prefix('historical-enquiries')->group(function () {
    Route::get('/export', [HistoricalEnquiriesController::class, 'historical_enquiries_export']);
    Route::post('/import-excel', [HistoricalEnquiriesController::class, 'importExcelHistoricalEnquiries']);

});


Route::prefix('invite-team')->group(function () {
    Route::post('/save', [InviteTeamController::class, 'saveInviteTeam']);

});


Route::get('/export-products-test', [ProductExportController::class, 'exportExcel']);
Route::get('/export-products', [ProductExportController::class, 'product_list_export']);

Route::get('/export-category-list', [ProductExportController::class, 'category_export']);