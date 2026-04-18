<?php

use Illuminate\Support\Facades\Route;

if (file_exists(app_path('Http/Controllers/InstallerController.php'))) {
    Route::group(['prefix' => 'install', 'as' => 'installer.'], function () {
        $installerController = 'App\\Http\\Controllers\\InstallerController';

        Route::get('/', [$installerController, 'index'])->name('index');
        Route::get('/requirements', [$installerController, 'requirements'])->name('requirements');
        Route::get('/permissions', [$installerController, 'permissions'])->name('permissions');
        Route::get('/purchaseValidation', [$installerController, 'purchaseValidation'])->name('purchaseValidation');
        Route::post('/purchaseValidation', [$installerController, 'purchaseValidationStore'])->name('purchaseValidation.store');
        Route::get('/purchaseValidation-error', [$installerController, 'purchaseValidationError'])->name('purchaseValidation-error');
        Route::get('/database', [$installerController, 'databaseForm'])->name('database')->middleware('purchase.validated');
        Route::post('/database', [$installerController, 'databaseStore'])->name('database.store');
        Route::get('/database-error', [$installerController, 'databaseError'])->name('database-error');
        Route::get('/migrate', [$installerController, 'migrate'])->name('migrate');
        Route::get('/DBmigrate', [$installerController, 'databaseMigration'])->name('database.migrate');
        Route::get('/forcemigrate', [$installerController, 'databaseForceMigration'])->name('database.forcemigrate');
        Route::get('/seed', [$installerController, 'seed'])->name('seed');
        Route::get('/admin', [$installerController, 'adminForm'])->name('admin');
        Route::post('/admin', [$installerController, 'adminStore'])->name('admin.store');
        Route::get('/finish', [$installerController, 'finish'])->name('finish');
    });
}
