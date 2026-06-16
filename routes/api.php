<?php

use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\CarFilterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API
|--------------------------------------------------------------------------
|
| All endpoints are unauthenticated and read-only. Visitors browse cars
| and contact the seller via WhatsApp on the frontend; no public writes
| or customer accounts exist. Admin management happens in Filament.
|
| Rate limiting:
|   - /cars and /cars/{slug}        => 'api' limiter (60 req/min/IP)
|     applied automatically by the 'api' middleware group.
|   - /car-filters                  => 'public-filters' limiter (120 req/min/IP)
|     because the frontend hits it on every page load (response is also cached).
|
*/

Route::get('/cars', [CarController::class, 'index'])->name('api.cars.index');

Route::get('/cars/{slug}', [CarController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('api.cars.show');

Route::get('/car-filters', CarFilterController::class)
    ->middleware('throttle:public-filters')
    ->name('api.car-filters');
