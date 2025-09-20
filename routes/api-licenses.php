<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LicenseGrid\MasterDataController;
use App\Http\Controllers\LicenseGrid\CustomerContactController;
use App\Http\Controllers\LicenseGrid\LicenseController;
use App\Http\Controllers\LicenseGrid\ReminderController;
use App\Http\Controllers\LicenseGrid\FilterController;
use App\Http\Controllers\LicenseGrid\OnboardingRequestController;
use App\Http\Controllers\LicenseGrid\CustomerNoteController;
use App\Http\Controllers\LicenseGrid\AccountManagementController;
use App\Http\Controllers\LicenseGrid\ProformaInvoiceController;
  use App\Http\Controllers\LicenseGrid\AccountManagementCustomerController;

Route::middleware(['auth:api'])->group(function () {

});

Route::prefix('master-data')->group(function () {
    Route::get('countries', [MasterDataController::class, 'countries']);
    Route::get('currencies', [MasterDataController::class, 'currencies']);
    Route::get('fiscal-months', [MasterDataController::class, 'fiscalMonths']);
    Route::get('financial-years', [MasterDataController::class, 'financialYears']);
    Route::get('states', [MasterDataController::class, 'states']);     
    Route::get('cities', [MasterDataController::class, 'cities']);  
    Route::get('salutations', [MasterDataController::class, 'salutation']); 
    Route::get('onboarding-steps', [MasterDataController::class, 'getOnboardingSteps']);
    Route::get('CurrencyExchange', [MasterDataController::class, 'CurrencyExchange']);
});

Route::prefix('filter-data')->group(function () {
    Route::get('account-managers', [FilterController::class, 'accountManagers']);
    Route::get('account-created-by', [FilterController::class, 'accountCreatedBy']);
    Route::get('account-type', [FilterController::class, 'accountType']);
    Route::get('expiring-in', [FilterController::class, 'expiringIn']);
    Route::get('company-status', [FilterController::class, 'companyStatus']);
    Route::get('team-directory-search', [FilterController::class, 'teamDirectorySearch']);
    Route::get('team-directory-sort', [FilterController::class, 'teamDirectorySort']);
    Route::get('segments', [FilterController::class, 'segments']);
    Route::get('categories', [FilterController::class, 'categories']);
    Route::get('account-status', [FilterController::class, 'accountStatus']);
    
 
});


Route::prefix('customers')->group(function () {
    Route::get('listing', [CustomerController::class, 'index']);
    Route::get('listing-parent', [CustomerController::class, 'indexParent']);
    Route::get('edit', [CustomerController::class, 'edit']);
    Route::post('store-update', [CustomerController::class, 'storeOrUpdate']);
    Route::post('destroy', [CustomerController::class, 'destroy']);
    Route::post('status', [CustomerController::class, 'updateStatus']);
    Route::post('update-status-favorite', [CustomerController::class, 'updateStatusFavorite']);
});


Route::prefix('secondary-contacts')->group(function () {
    Route::get('listing', [CustomerContactController::class, 'index']);
    Route::get('edit', [CustomerContactController::class, 'edit']);
    Route::post('store-update', [CustomerContactController::class, 'storeOrUpdate']);
    Route::post('destroy', [CustomerContactController::class, 'destroy']);
    Route::post('status', [CustomerContactController::class, 'updateStatus']);
});

Route::prefix('licenses')->group(function () {
    Route::get('listing', [LicenseController::class, 'index']);
    Route::get('edit', [LicenseController::class, 'edit']);
    Route::post('store-update', [LicenseController::class, 'storeOrUpdate']);
    Route::post('destroy', [LicenseController::class, 'destroy']);
    Route::post('status', [LicenseController::class, 'updateStatus']);
    Route::get('listing-by-customer', [LicenseController::class, 'indexByCustomer']);
    Route::post('account-status-update', [LicenseController::class, 'accountStatusUpdate']);
    Route::get('index-view', [LicenseController::class, 'indexView']);
});


Route::prefix('reminders')->group(function () {
    Route::get('listing', [ReminderController::class, 'index']);
    Route::get('edit', [ReminderController::class, 'edit']);
    Route::post('store-update', [ReminderController::class, 'storeOrUpdate']);
    Route::post('destroy', [ReminderController::class, 'destroy']);
    Route::post('status', [ReminderController::class, 'updateStatus']);
});



Route::prefix('onboarding-request')->group(function () {
    Route::get('listing', [OnboardingRequestController::class, 'index']);
    Route::get('details', [OnboardingRequestController::class, 'details']);
    Route::post('update-account-manager', [OnboardingRequestController::class, 'updateAccountManager']);
    Route::post('update-gst-vat-number', [OnboardingRequestController::class, 'updateGstVatNumber']);
    Route::post('update-request-status', [OnboardingRequestController::class, 'updateRequestStatus']);
    Route::post('offline-license-customer-store', [OnboardingRequestController::class, 'offlineLicenseCustomer']);
});
 
Route::prefix('customer-note')->group(function () {
    Route::get('listing', [CustomerNoteController::class, 'index']);
    Route::get('edit', [CustomerNoteController::class, 'edit']);
    Route::post('store-update', [CustomerNoteController::class, 'storeOrUpdate']);
    Route::post('destroy', [CustomerNoteController::class, 'destroy']);
    Route::post('status', [CustomerNoteController::class, 'updateStatus']);
});
    

Route::prefix('account-management')->group(function () {
    Route::get('listing', [AccountManagementController::class, 'index']);
    Route::get('details', [AccountManagementController::class, 'details']);
    Route::get('last-license-details', [AccountManagementController::class, 'lastLicenseDetails']);
    Route::post('customer-user/update-department', [AccountManagementController::class, 'updateDepartmentCustomerUsers']);
    Route::post('customer-user/update-designation', [AccountManagementController::class, 'updateDesignationCustomerUsers']);
    Route::post('customer-user/update-status', [AccountManagementController::class, 'updateStatusCustomerUsers']);
    Route::post('save-new-customer', [AccountManagementController::class, 'storeNewCustomer']);
    
});

Route::prefix('proforma-invoice')->group(function () {
    Route::get('details', [ProformaInvoiceController::class, 'details']);
    Route::post('update', [ProformaInvoiceController::class, 'update']); 
    Route::post('license-payments/store-or-update', [ProformaInvoiceController::class, 'storeOrUpdatePaymentRecived']); 
});

Route::prefix('reminders')->group(function () {
    Route::get('listing', [ReminderController::class, 'index']);
    Route::get('edit', [ReminderController::class, 'edit']);
    Route::post('store-update', [ReminderController::class, 'storeOrUpdate']);
    Route::post('destroy', [ReminderController::class, 'destroy']);
    Route::post('status', [ReminderController::class, 'updateStatus']);
});

Route::prefix('account-management-customer')->group(function () {
    Route::get('account-information', [AccountManagementCustomerController::class, 'accountInformation']);
    Route::post('update-customer-contacts', [AccountManagementCustomerController::class, 'updateCustomerContacts']);
    Route::delete('destroy-customer-contacts', [AccountManagementCustomerController::class, 'destroyCustomerContacts']);
    Route::get('access-level-management', [AccountManagementCustomerController::class, 'accessLevelManagement']);

    Route::get('license-management', [AccountManagementCustomerController::class, 'licenseManagement']);
    Route::post('update-function-license-user', [AccountManagementCustomerController::class, 'updateFunctionLicenseUser']);
    Route::post('remove-restore-license-user', [AccountManagementCustomerController::class, 'removeRestoreLicenseUser']);
    Route::get('edit-license-user', [AccountManagementCustomerController::class, 'editLicenseUser']);
    Route::post('update-or-store-license-user', [AccountManagementCustomerController::class, 'updateOrStoreLicenseUser']);
    Route::get('roles', [AccountManagementCustomerController::class, 'roles']);
    Route::get('last-license-details', [AccountManagementCustomerController::class, 'lastLicenseDetails']);
    Route::get('function-list', [AccountManagementCustomerController::class, 'functionList']);

    Route::get('payment', [AccountManagementCustomerController::class, 'payment']);
    Route::get('previous-invoice', [AccountManagementCustomerController::class, 'previousInvoice']);
    Route::get('activity-log', [AccountManagementCustomerController::class, 'activityLog']);

});