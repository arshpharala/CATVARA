<?php

use App\Http\Controllers\Admin\CMS\TinyMCEController;
use App\Http\Controllers\Admin\CompanyContextController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Settings\CompanyController;
use App\Http\Controllers\Admin\Settings\ModuleController;
use App\Http\Controllers\Admin\Settings\PermissionController;
use App\Http\Controllers\Admin\Settings\RoleController;
use App\Http\Controllers\Admin\Settings\UserController;
use App\Models\Company\Company;
use Illuminate\Support\Facades\Route;

/**
 * Bind {company} by UUID (Laravel 12 compatible)
 */
Route::bind('company', function ($value) {
    return Company::where('uuid', $value)->firstOrFail();
});

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {

    /**
     * Company selection (no company middleware here)
     */
    Route::get('select-company', [CompanyContextController::class, 'select'])
        ->name('company.select');

    Route::post('select-company', [CompanyContextController::class, 'store'])
        ->name('company.select.store');

    Route::post('switch-company', [CompanyContextController::class, 'switch'])
        ->name('company.switch');

    Route::post('switch-company/reset', [CompanyContextController::class, 'reset'])
        ->name('company.switch.reset');

    /**
     * Dashboard entry point (normalized)
     * Controller decides:
     * - if user has current company in session => redirect to /{company}/dashboard
     * - if user has one company => auto-select and redirect
     * - else => redirect to select-company
     */
    Route::get('dashboard', [CompanyContextController::class, 'dashboardEntry'])
        ->name('dashboard');

    /**
     * Global settings (no {company} in URL)
     * Keep route names: companies.*, users.* (matches your blades)
     */
    Route::prefix('settings')->group(function () {

        Route::resource('companies', CompanyController::class)->except(['destroy']);
        Route::get('companies/load/stats', [CompanyController::class, 'stats'])->name('companies.stats');

        Route::resource('currencies', \App\Http\Controllers\Admin\Settings\CurrencyController::class)->except(['destroy']);
        Route::resource('payment-terms', \App\Http\Controllers\Admin\Settings\PaymentTermController::class)->except(['destroy']);

        Route::resource('users', UserController::class)->except(['destroy'])->names('users');

        Route::resource('modules', ModuleController::class)->except(['destroy']);
        Route::resource('permissions', PermissionController::class)->except(['destroy']);

        // User company-role assignment endpoints (used in show.blade.php)
        Route::get('users/roles/by-company', [UserController::class, 'rolesByCompany'])
            ->name('users.roles.byCompany');

        Route::post('users/{user}/assign-company', [UserController::class, 'assignCompany'])
            ->name('users.assignCompany');

        Route::post('users/{user}/remove-company', [UserController::class, 'removeCompany'])
            ->name('users.removeCompany');
    });

    /**
     * CMS routes (global, but you can wrap in company.selected if needed)
     */
    Route::prefix('cms')->as('cms.')->group(function () {
        Route::post('upload/tinymce', [TinyMCEController::class, 'upload'])->name('upload.tinymce');
    });

    /**
     * Company-scoped application (everything operational)
     */
    Route::prefix('{company}')
        ->as('company.')
        ->middleware(['company.access', 'company.context'])
        ->group(function () {

            Route::get('dashboard', [DashboardController::class, 'dashboard'])
                ->name('dashboard');

            /**
             * Catalog Management
             */
            Route::prefix('catalog')->as('catalog.')->group(function () {
                Route::resource('categories', \App\Http\Controllers\Admin\Catalog\CategoryController::class);
                Route::get('categories/{category}/attributes', [\App\Http\Controllers\Admin\Catalog\CategoryController::class, 'getAttributes'])
                    ->name('categories.attributes');

                // Attributes
                Route::resource('attributes', \App\Http\Controllers\Admin\Catalog\AttributeController::class)->except(['show', 'destroy']);
                Route::resource('products', \App\Http\Controllers\Admin\Catalog\ProductController::class);
            });

            /**
             * Inventory Management
             */
            Route::prefix('inventory')->as('inventory.')->group(function () {
                Route::resource('inventory', \App\Http\Controllers\Admin\Inventory\InventoryController::class);
                Route::get('balances/data', [\App\Http\Controllers\Admin\Inventory\InventoryController::class, 'balancesData'])->name('balances.data');
                Route::get('inventory/adjust', [\App\Http\Controllers\Admin\Inventory\InventoryController::class, 'create'])->name('inventory.adjust');
                Route::post('adjust', [\App\Http\Controllers\Admin\Inventory\InventoryController::class, 'store'])->name('store');
                Route::post('transfer', [\App\Http\Controllers\Admin\Inventory\InventoryController::class, 'transfer'])->name('transfer');
                
                // Transfers
                Route::resource('transfers', \App\Http\Controllers\Admin\Inventory\TransferController::class);
                Route::post('transfers/{transfer}/approve', [\App\Http\Controllers\Admin\Inventory\TransferController::class, 'approve'])->name('transfers.approve');
                Route::post('transfers/{transfer}/ship', [\App\Http\Controllers\Admin\Inventory\TransferController::class, 'ship'])->name('transfers.ship');
                Route::post('transfers/{transfer}/receive', [\App\Http\Controllers\Admin\Inventory\TransferController::class, 'receive'])->name('transfers.receive');
                
                // Movement History
                Route::get('movements', [\App\Http\Controllers\Admin\Inventory\InventoryController::class, 'movements'])->name('movements');
                
                // Inventory Management CRUDs
                Route::resource('warehouses', \App\Http\Controllers\Admin\Inventory\WarehouseController::class);
                Route::resource('stores', \App\Http\Controllers\Admin\Inventory\StoreController::class);
                Route::resource('reasons', \App\Http\Controllers\Admin\Inventory\InventoryReasonController::class);

                // Variant Inventory Details
                Route::get('variant/{product_variant}/details', [\App\Http\Controllers\Admin\Inventory\InventoryController::class, 'variantDetails'])->name('variant.details');
            });
            /*
             * Company-scoped Settings (Roles are company-wise)
             */
            Route::prefix('settings')->as('settings.')->group(function () {

                // General Settings
                Route::get('general', [\App\Http\Controllers\Admin\Settings\CompanySettingsController::class, 'edit'])->name('general');
                Route::put('general', [\App\Http\Controllers\Admin\Settings\CompanySettingsController::class, 'update'])->name('general.update');

                Route::resource('roles', RoleController::class)->except(['show', 'destroy']);

                /**
                 * Future company modules (add later):
                 * customers, inventory, pos, orders, reports...
                 *
                 * Example:
                 * Route::resource('customers', CustomerController::class)->names('customers');
                 */
            });
        });
});

require __DIR__.'/auth.php';
