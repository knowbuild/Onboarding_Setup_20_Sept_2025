<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SalesManager\TargetSummaryController;
use App\Http\Controllers\SalesManager\TargetSummaryListController;
use App\Http\Controllers\SalesManager\TESManagerController;
use App\Http\Controllers\SalesManager\SaleOfferTemplateController;
use App\Http\Controllers\InsideSales\InsideSalesController;

Route::prefix('target-summary')->group(function () {
    Route::get('/product-category-achieved', [TargetSummaryController::class, 'productCategoryAchieved']);
    Route::get('/customer-segment', [TargetSummaryController::class, 'customerSegment']);

    Route::get('/assigned-enquiry-conversion-funnel', [TargetSummaryController::class, 'assignedEnquiryConversionFunnel']);
    Route::get('/enquiry-source-trend', [TargetSummaryController::class, 'enquirySourceTrend']);
    Route::get('/activity', [TargetSummaryController::class, 'activity']);
    Route::get('/distribution', [TargetSummaryController::class, 'distribution']);
    Route::get('/top-selling-product', [TargetSummaryController::class, 'topSellingProduct']);
    Route::get('/products-requiring-attention-target', [TargetSummaryController::class, 'productsRequiringAttentionTarget']);
 
    Route::get('/top-customer', [TargetSummaryController::class, 'topCustomer']);
    Route::get('/target-customer-requiring-attention', [TargetSummaryController::class, 'targetCustomerRequiringAttention']);
    Route::get('/month-on-month-sales-trend', [TargetSummaryController::class, 'monthOnMonthSalesTrend']);

    Route::get('/conversion-by', [TargetSummaryListController::class, 'conversionBy']);
    Route::get('/product-type', [TargetSummaryListController::class, 'productType']);
    Route::get('/product-category', [TargetSummaryListController::class, 'productCategory']);
    Route::get('/products-services', [TargetSummaryListController::class, 'productService']);
    Route::get('/sales-conversion-rate', [TargetSummaryListController::class, 'salesConversionRate']);
    Route::get('/tes-progress', [TargetSummaryListController::class, 'tesProgress']);
});
 
 
Route::prefix('tes-manager')->group(function () {
    
    Route::get('/search-customers', [TESManagerController::class, 'searchCustomers']);
    Route::get('/search-customer-products', [TESManagerController::class, 'searchCustomerProduct']);
    Route::post('/save-target-amount', [TESManagerController::class, 'saveTesManagerData']);
    Route::post('/save-target-customers-product', [TESManagerController::class, 'saveTesData']);
    Route::post('/status-update-target-manager', [TESManagerController::class, 'statusTesManagerData']);
    Route::get('/edit-target-manager-data', [TESManagerController::class, 'getTesDataById']);
    Route::post('/discount-update-target-manager', [TESManagerController::class, 'discountTesManagerData']);

    Route::delete('/delete-target-amount', [TESManagerController::class, 'deleteTesManagerData']);
    Route::delete('/delete-target-customers-product', [TESManagerController::class, 'deleteTesData']);
});


Route::prefix('inside-sales')->group(function () {
    Route::get('/listing', [InsideSalesController::class, 'insideSalesListing']);
});

// Sale Offer Template
Route::prefix('sale-offer-template')->group(function () {
    Route::get('/show-details', [SaleOfferTemplateController::class, 'showDetails']);  
    Route::post('/store-or-update', [SaleOfferTemplateController::class, 'storeOrUpdate']); 
     Route::post('/term-disable-update', [SaleOfferTemplateController::class, 'termDisableUpdate']);   
 
});
