<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductTypeClassController;
use App\Http\Controllers\Admin\WarrantyController;
use App\Http\Controllers\Admin\OrderDeliveryTermController;
use App\Http\Controllers\Admin\OrderPaymentTermController;
use App\Http\Controllers\Admin\VendorPaymentTermController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ServicePeriodController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\QuoteValueClassificationController;
use App\Http\Controllers\Admin\SegmentCustomerController;
use App\Http\Controllers\Admin\SourceController;
use App\Http\Controllers\Admin\DesignationCustomerController;

use App\Http\Controllers\Admin\BankAccountCompanyController;
use App\Http\Controllers\Admin\TaxRegisteredBranchesCompanyController;
use App\Http\Controllers\Admin\StockLocationTransferController;

use App\Http\Controllers\Admin\LogisticsProviderController;
use App\Http\Controllers\Admin\FreightModeController;
use App\Http\Controllers\Admin\SystemConfigurationController;
use App\Http\Controllers\Admin\CreditApprovalSettingController;

use App\Http\Controllers\Admin\BusinessFunctionMasterController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\EmployeeManagerController;
use App\Http\Controllers\Admin\TeamManagerController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\PriceBasisVendorController;

use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\SubmenuController;


Route::prefix('product-type-classes')->group(function () {
Route::get('listing', [ProductTypeClassController::class, 'index']);
Route::get('edit', [ProductTypeClassController::class, 'edit']);
Route::post('store-or-update', [ProductTypeClassController::class, 'storeOrUpdate']);
Route::delete('delete', [ProductTypeClassController::class, 'destroy']);
Route::post('update-status', [ProductTypeClassController::class, 'updateStatus']);
Route::post('update-show-on-pqv', [ProductTypeClassController::class, 'updateStatusShowOnPQV']);
});



Route::prefix('warranties')->group(function () {
    Route::get('listing', [WarrantyController::class, 'index']);
    Route::get('edit', [WarrantyController::class, 'edit']);
    Route::post('store-or-update', [WarrantyController::class, 'storeOrUpdate']);
    Route::delete('delete', [WarrantyController::class, 'destroy']);
    Route::post('update-status', [WarrantyController::class, 'updateStatus']);
});


Route::prefix('order-delivery-terms')->group(function () {
    Route::get('listing', [OrderDeliveryTermController::class, 'index']);
    Route::get('edit', [OrderDeliveryTermController::class, 'edit']);
    Route::post('store-or-update', [OrderDeliveryTermController::class, 'storeOrUpdate']);
    Route::delete('delete', [OrderDeliveryTermController::class, 'destroy']);
    Route::post('update-status', [OrderDeliveryTermController::class, 'updateStatus']);
});


Route::prefix('order-payment-terms')->group(function () {
    Route::get('listing', [OrderPaymentTermController::class, 'index']);
    Route::get('edit', [OrderPaymentTermController::class, 'edit']);
    Route::post('store-or-update', [OrderPaymentTermController::class, 'storeOrUpdate']);
    Route::delete('delete', [OrderPaymentTermController::class, 'destroy']);
    Route::post('update-status', [OrderPaymentTermController::class, 'updateStatus']);
});

Route::prefix('vendor-payment-terms')->group(function () {
    Route::get('listing', [VendorPaymentTermController::class, 'index']);
    Route::get('edit', [VendorPaymentTermController::class, 'edit']);
    Route::post('store-or-update', [VendorPaymentTermController::class, 'storeOrUpdate']);
    Route::delete('delete', [VendorPaymentTermController::class, 'destroy']);
    Route::post('update-status', [VendorPaymentTermController::class, 'updateStatus']);
});


Route::prefix('product-category')->group(function () {
    Route::get('listing', [ProductCategoryController::class, 'index']);
    Route::get('edit', [ProductCategoryController::class, 'edit']);
    Route::post('store-or-update', [ProductCategoryController::class, 'storeOrUpdate']);
    Route::delete('delete', [ProductCategoryController::class, 'destroy']);
    Route::post('update-status', [ProductCategoryController::class, 'updateStatus']);
    Route::post('update-category', [ProductCategoryController::class, 'updateCategory']);
});

Route::prefix('products')->group(function () {
    Route::get('listing', [ProductController::class, 'index']);
    Route::get('edit', [ProductController::class, 'edit']);
    Route::post('store-or-update', [ProductController::class, 'storeOrUpdate']);
    Route::post('delete', [ProductController::class, 'destroy']);
    Route::post('update-status', [ProductController::class, 'updateStatus']);
    Route::get('insert-category-ID-product-service', [ProductController::class, 'insertCategoryIDProductService']);
   Route::post('update-hot', [ProductController::class, 'updateHot']);
});
Route::prefix('services-period')->group(function () {
    Route::get('listing', [ServicePeriodController::class, 'index']);
    Route::get('edit', [ServicePeriodController::class, 'edit']);
    Route::post('store-or-update', [ServicePeriodController::class, 'storeOrUpdate']);
    Route::delete('delete', [ServicePeriodController::class, 'destroy']);
    Route::post('update-status', [ServicePeriodController::class, 'updateStatus']);
});
 Route::prefix('service-category')->group(function () {
    Route::get('listing', [ServiceCategoryController::class, 'index']);
    Route::get('edit', [ServiceCategoryController::class, 'edit']);
    Route::post('store-or-update', [ServiceCategoryController::class, 'storeOrUpdate']);
    Route::delete('delete', [ServiceCategoryController::class, 'destroy']);
    Route::post('update-status', [ServiceCategoryController::class, 'updateStatus']);
});
Route::prefix('services')->group(function () {
    Route::get('listing', [ServiceController::class, 'index']);
    Route::get('edit', [ServiceController::class, 'edit']);
    Route::post('store-or-update', [ServiceController::class, 'storeOrUpdate']);
    Route::delete('delete', [ServiceController::class, 'destroy']);
    Route::post('update-status', [ServiceController::class, 'updateStatus']);
});



Route::prefix('quote-value-classification')->group(function () {
    Route::get('listing', [QuoteValueClassificationController::class, 'index']);
    Route::get('edit', [QuoteValueClassificationController::class, 'edit']);
    Route::post('store-or-update', [QuoteValueClassificationController::class, 'storeOrUpdate']);
    Route::delete('delete', [QuoteValueClassificationController::class, 'destroy']);
    Route::post('update-status', [QuoteValueClassificationController::class, 'updateStatus']);
});

Route::prefix('segment-customer')->group(function () {
    Route::get('listing', [SegmentCustomerController::class, 'index']);
    Route::get('edit', [SegmentCustomerController::class, 'edit']);
    Route::post('store-or-update', [SegmentCustomerController::class, 'storeOrUpdate']);
    Route::delete('delete', [SegmentCustomerController::class, 'destroy']);
    Route::post('update-status', [SegmentCustomerController::class, 'updateStatus']);
});

Route::prefix('source')->group(function () {
    Route::get('listing', [SourceController::class, 'index']);
    Route::get('edit', [SourceController::class, 'edit']);
    Route::post('store-or-update', [SourceController::class, 'storeOrUpdate']);
    Route::delete('delete', [SourceController::class, 'destroy']);
    Route::post('update-status', [SourceController::class, 'updateStatus']);
});

Route::prefix('designation-customer')->group(function () {
    Route::get('listing', [DesignationCustomerController::class, 'index']);
    Route::get('edit', [DesignationCustomerController::class, 'edit']);
    Route::post('store-or-update', [DesignationCustomerController::class, 'storeOrUpdate']);
    Route::delete('delete', [DesignationCustomerController::class, 'destroy']);
    Route::post('update-status', [DesignationCustomerController::class, 'updateStatus']);
});

Route::prefix('system-configurations')->group(function () {
    Route::get('listing', [SystemConfigurationController::class, 'index']);

});

Route::prefix('bank-account-company')->group(function () {
    Route::get('listing', [BankAccountCompanyController::class, 'index']);
    Route::get('edit', [BankAccountCompanyController::class, 'edit']);
    Route::post('store-or-update', [BankAccountCompanyController::class, 'storeOrUpdate']);
    Route::delete('delete', [BankAccountCompanyController::class, 'destroy']);
    Route::post('status', [BankAccountCompanyController::class, 'updateStatus']);
});

Route::prefix('tax-registered-branches-company')->group(function () {
    Route::get('listing', [TaxRegisteredBranchesCompanyController::class, 'index']);
    Route::get('edit', [TaxRegisteredBranchesCompanyController::class, 'edit']);
    Route::post('store-or-update', [TaxRegisteredBranchesCompanyController::class, 'storeOrUpdate']);
    Route::delete('delete', [TaxRegisteredBranchesCompanyController::class, 'destroy']);
    Route::post('status', [TaxRegisteredBranchesCompanyController::class, 'updateStatus']);
});

Route::prefix('stock-location-transfer')->group(function () {
    Route::get('listing', [StockLocationTransferController::class, 'index']);
    Route::get('edit', [StockLocationTransferController::class, 'edit']);
    Route::post('store-or-update', [StockLocationTransferController::class, 'storeOrUpdate']);
    Route::delete('delete', [StockLocationTransferController::class, 'destroy']);
    Route::post('status', [StockLocationTransferController::class, 'updateStatus']);
});
Route::prefix('logistics-provider')->group(function () {
    Route::get('listing', [LogisticsProviderController::class, 'index']);
    Route::get('edit', [LogisticsProviderController::class, 'edit']);
    Route::post('store-or-update', [LogisticsProviderController::class, 'storeOrUpdate']);
    Route::delete('delete', [LogisticsProviderController::class, 'destroy']);
    Route::post('update-status', [LogisticsProviderController::class, 'updateStatus']);
});


Route::prefix('freight-mode')->group(function () {
    Route::get('listing', [FreightModeController::class, 'index']);
    Route::get('edit', [FreightModeController::class, 'edit']);
    Route::post('store-or-update', [FreightModeController::class, 'storeOrUpdate']);
    Route::delete('delete', [FreightModeController::class, 'destroy']);
    Route::post('update-status', [FreightModeController::class, 'updateStatus']);
});

Route::prefix('country')->group(function () {
    Route::get('listing', [CountryController::class, 'index']);
    Route::get('edit', [CountryController::class, 'edit']);
    Route::post('store-or-update', [CountryController::class, 'storeOrUpdate']);
    Route::delete('delete', [CountryController::class, 'destroy']);
    Route::post('update-status', [CountryController::class, 'updateStatus']);
});
Route::prefix('state')->group(function () {
    Route::get('listing', [StateController::class, 'index']);
    Route::get('edit', [StateController::class, 'edit']);
    Route::post('store-or-update', [StateController::class, 'storeOrUpdate']);
    Route::delete('delete', [StateController::class, 'destroy']);
    Route::post('status', [StateController::class, 'updateStatus']);
});
Route::prefix('city')->group(function () {
    Route::get('listing', [CityController::class, 'index']);
    Route::get('edit', [CityController::class, 'edit']);
    Route::post('store-or-update', [CityController::class, 'storeOrUpdate']);
    Route::delete('delete', [CityController::class, 'destroy']);
    Route::post('status', [CityController::class, 'updateStatus']);
});


Route::prefix('credit-approval-setting')->group(function () {
    Route::get('listing', [CreditApprovalSettingController::class, 'index']);
    Route::get('edit', [CreditApprovalSettingController::class, 'edit']);
    Route::post('store-or-update', [CreditApprovalSettingController::class, 'storeOrUpdate']);
    Route::delete('delete', [CreditApprovalSettingController::class, 'destroy']);
    Route::post('update-approval', [CreditApprovalSettingController::class, 'updateApproval']);
});


Route::prefix('business-function-master')->group(function () {
    Route::get('listing', [BusinessFunctionMasterController::class, 'index']);
    Route::get('edit', [BusinessFunctionMasterController::class, 'edit']);
    Route::post('store-or-update', [BusinessFunctionMasterController::class, 'storeOrUpdate']);
    Route::delete('delete', [BusinessFunctionMasterController::class, 'destroy']);
    Route::post('update-status', [BusinessFunctionMasterController::class, 'updateStatus']);
    Route::post('restore', [BusinessFunctionMasterController::class, 'restore']);
});

Route::prefix('role-management')->group(function () {
    Route::get('permissions', [RoleManagementController::class, 'getPermissions']);
    Route::get('listing', [RoleManagementController::class, 'index']);
    Route::get('edit', [RoleManagementController::class, 'edit']);
    Route::post('store-or-update', [RoleManagementController::class, 'storeOrUpdate']);
    Route::delete('delete', [RoleManagementController::class, 'destroy']);
    Route::post('update-status', [RoleManagementController::class, 'updateStatus']);
 
});

Route::prefix('employee-manager')->group(function () {
    Route::get('listing', [EmployeeManagerController::class, 'index']);
    Route::get('edit', [EmployeeManagerController::class, 'edit']);
    Route::post('store-or-update', [EmployeeManagerController::class, 'storeOrUpdate']);
    Route::delete('delete', [EmployeeManagerController::class, 'destroy']);
    Route::post('update-status', [EmployeeManagerController::class, 'updateStatus']);
    Route::get('view', [EmployeeManagerController::class, 'view']);
});

Route::prefix('team-manager')->group(function () {
    Route::get('listing', [TeamManagerController::class, 'index']);
    Route::get('edit', [TeamManagerController::class, 'edit']);
    Route::post('store-or-update', [TeamManagerController::class, 'storeOrUpdate']);
    Route::delete('delete', [TeamManagerController::class, 'destroy']);
    Route::post('update-status', [TeamManagerController::class, 'updateStatus']);
});

Route::prefix('tax-rate')->group(function () {
    Route::get('listing', [TaxRateController::class, 'index']);
    Route::get('edit', [TaxRateController::class, 'edit']);
    Route::post('store-or-update', [TaxRateController::class, 'storeOrUpdate']);
    Route::delete('delete', [TaxRateController::class, 'destroy']);
    Route::post('update-status', [TaxRateController::class, 'updateStatus']);
});

Route::prefix('price-basis-vendor')->group(function () {
    Route::get('listing', [PriceBasisVendorController::class, 'index']);
    Route::get('edit', [PriceBasisVendorController::class, 'edit']);
    Route::post('store-or-update', [PriceBasisVendorController::class, 'storeOrUpdate']);
    Route::delete('delete', [PriceBasisVendorController::class, 'destroy']);
    Route::post('update-status', [PriceBasisVendorController::class, 'updateStatus']);
  Route::post('update-purchase-type', [PriceBasisVendorController::class, 'updatePurchaseType']);
});

Route::prefix('menu')->group(function () {
    Route::get('listing', [MenuController::class, 'index']);
    Route::get('edit', [MenuController::class, 'edit']);
    Route::post('store-or-update', [MenuController::class, 'storeOrUpdate']);
    Route::delete('delete', [MenuController::class, 'destroy']);
    Route::post('status', [MenuController::class, 'updateStatus']);
}); 
Route::prefix('submenu')->group(function () {
    Route::get('listing', [SubmenuController::class, 'index']);
    Route::get('edit', [SubmenuController::class, 'edit']);
    Route::post('store-or-update', [SubmenuController::class, 'storeOrUpdate']);
    Route::delete('delete', [SubmenuController::class, 'destroy']);
    Route::post('status', [SubmenuController::class, 'updateStatus']);
});

