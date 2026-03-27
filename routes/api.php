<?php

use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminOpportunityController;
use App\Http\Controllers\Api\Admin\AdminPostController;
use App\Http\Controllers\Api\Admin\AdminAdvertisementController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\PublicAdvertisementController;
use App\Http\Controllers\Api\PublicEventController;
use App\Http\Controllers\Api\PublicOpportunityController;
use App\Http\Controllers\Api\PublicPostController;
use App\Http\Controllers\Api\PublicTypeOpportunityController;
use Illuminate\Support\Facades\Route;

Route::get('/events', [PublicEventController::class, 'index']);
Route::get('/events/{event}', [PublicEventController::class, 'show']);
Route::get('/advertisements', [PublicAdvertisementController::class, 'index']);
Route::get('/opportunities', [PublicOpportunityController::class, 'index']);
Route::get('/opportunities/{opportunity}', [PublicOpportunityController::class, 'show']);
Route::get('/posts', [PublicPostController::class, 'index']);
Route::get('/posts/{slug}', [PublicPostController::class, 'show']);
Route::get('/types-opportunities', [PublicTypeOpportunityController::class, 'index']);
Route::post('/opportunities', [PublicOpportunityController::class, 'store']);
Route::post('/contact', [ContactMessageController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

Route::prefix('admin')->group(function () {
    // Backward-compatible aliases.
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::middleware('role:admin')->group(function () {
            Route::apiResource('/events', AdminEventController::class);
            Route::apiResource('/advertisements', AdminAdvertisementController::class);
            Route::apiResource('/posts', AdminPostController::class);
        });

        Route::middleware('role:admin,manager')->group(function () {
            Route::get('/opportunities', [AdminOpportunityController::class, 'index']);
            Route::get('/opportunities/{opportunity}', [AdminOpportunityController::class, 'show']);
            Route::put('/opportunities/{opportunity}/accept', [AdminOpportunityController::class, 'accept']);
            Route::put('/opportunities/{opportunity}/reject', [AdminOpportunityController::class, 'reject']);
        });
    });
});
