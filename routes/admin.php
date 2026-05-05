<?php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\RegionStatesController;
use App\Http\Controllers\Admin\StaffUserController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\UserVacationAdjustmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('regions/{region}/states', RegionStatesController::class)->name('regions.states');
    Route::resource('regions', RegionController::class)->except(['show']);
    Route::resource('states', StateController::class)->except(['show']);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('users', StaffUserController::class)->except(['show', 'destroy']);

    Route::get('users/{user}/vacation-adjustments', [UserVacationAdjustmentController::class, 'index'])
        ->name('users.vacation-adjustments.index');
    Route::post('users/{user}/vacation-adjustments', [UserVacationAdjustmentController::class, 'store'])
        ->name('users.vacation-adjustments.store');
});
