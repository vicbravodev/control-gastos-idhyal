<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/notifications.php';
require __DIR__.'/expense-requests.php';
require __DIR__.'/vacation-requests.php';
require __DIR__.'/budgets.php';
require __DIR__.'/expense-concepts.php';
require __DIR__.'/approval-policies.php';
require __DIR__.'/vacation-rules.php';
require __DIR__.'/reports.php';
