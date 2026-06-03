<?php

use App\Http\Controllers\Budgets\BudgetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('budgets', BudgetController::class)->except(['show', 'destroy']);
    Route::post('budgets/{budget}/cancel', [BudgetController::class, 'cancel'])
        ->name('budgets.cancel');
});
