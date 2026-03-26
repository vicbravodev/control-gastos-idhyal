<?php

use App\Http\Controllers\ApprovalPolicies\ApprovalPolicyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('approval-policies', ApprovalPolicyController::class)
        ->except(['show']);
});
