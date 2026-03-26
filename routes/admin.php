<?php

use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\RegionStatesController;
use App\Http\Controllers\Admin\StaffUserController;
use App\Http\Controllers\Admin\StateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('regions/{region}/states', RegionStatesController::class)->name('regions.states');
    Route::resource('regions', RegionController::class)->except(['show']);
    Route::resource('states', StateController::class)->except(['show']);
    Route::resource('users', StaffUserController::class)->except(['show', 'destroy']);
});
