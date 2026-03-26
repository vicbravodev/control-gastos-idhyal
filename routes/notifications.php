<?php

use App\Http\Controllers\NotificationInboxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('notifications/preview', [NotificationInboxController::class, 'preview'])
        ->name('notifications.preview');

    Route::get('notifications', [NotificationInboxController::class, 'index'])
        ->name('notifications.index');

    Route::post('notifications/read-all', [NotificationInboxController::class, 'markAllRead'])
        ->name('notifications.read-all');

    Route::post('notifications/{id}/read', [NotificationInboxController::class, 'markRead'])
        ->whereUuid('id')
        ->name('notifications.read');
});
