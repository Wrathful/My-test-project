<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;

Route::post('/api/v1/notifications', [NotificationController::class, 'store']);
Route::get('/api/v1/notifications/subscriber/{subscriberId}', [NotificationController::class, 'index']);
