<?php

use App\Http\Controllers\ExpenseConcepts\ExpenseConceptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('expense-concepts', ExpenseConceptController::class)
        ->except(['show']);
});
