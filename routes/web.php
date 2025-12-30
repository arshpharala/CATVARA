<?php

use App\Http\Controllers\Admin\CMS\TinyMCEController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Settings\CompanyController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');


    Route::prefix('settings')->group(function () {
        Route::resource('companies', CompanyController::class)->except('destroy');
        Route::get('companies/load/stats', [CompanyController::class, 'stats'])->name('companies.stats');
    });

    Route::prefix('cms')->as('cms.')->group(function () {
        Route::post('upload/tinymce',                            [TinyMCEController::class, 'upload'])->name('upload.tinymce');
    });
});


// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__ . '/auth.php';
