<?php

use App\Http\Controllers\VacationRules\VacationRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('vacation-rules', VacationRuleController::class)
        ->except(['show']);
});
