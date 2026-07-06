<?php

use App\Http\Controllers\SearchEventsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('events')->group(function (): void {
        Route::get('search', [SearchEventsController::class, 'index']);
    });
});
