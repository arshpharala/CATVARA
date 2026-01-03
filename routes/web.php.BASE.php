<?php

use Illuminate\Http\Request;
use App\Models\Company\Company;

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;

use App\Http\Controllers\Admin\CMS\TinyMCEController;

use App\Http\Controllers\Admin\Settings\RoleController;
use App\Http\Controllers\Admin\Settings\UserController;
use App\Http\Controllers\Admin\CompanyContextController;
use App\Http\Controllers\Admin\Settings\CompanyController;
use App\Http\Controllers\Admin\Settings\ModuleController;
use App\Http\Controllers\Admin\Settings\PermissionController;
use App\Http\Controllers\Admin\Settings\RolePermissionController;

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
             * Company-scoped Settings (Roles are company-wise)
             */
            Route::prefix('settings')->as('settings.')->group(function () {

                Route::resource('roles', RoleController::class)->except(['show', 'destroy']);


                // Route::get('roles/{role}/permissions', [RolePermissionController::class, 'edit'])
                //     ->name('roles.permissions.edit');

                // Route::put('roles/{role}/permissions', [RolePermissionController::class, 'update'])
                //     ->name('roles.permissions.update');
            });

            /**
             * Future company modules (add later):
             * customers, inventory, pos, orders, reports...
             *
             * Example:
             * Route::resource('customers', CustomerController::class)->names('customers');
             */
        });
});

require __DIR__ . '/auth.php';
