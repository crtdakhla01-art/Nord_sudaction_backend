<?php

use App\Http\Controllers\Api\Admin\AdminActivityController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminInscriptionController;
use App\Http\Controllers\Api\Admin\AdminOpportunityController;
use App\Http\Controllers\Api\Admin\AdminPostController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\GalleryCategoryController;
use App\Http\Controllers\Api\InscriptionController;
use App\Http\Controllers\Api\NewsletterSubscriberController;
use App\Http\Controllers\Api\PublicEventController;
use App\Http\Controllers\Api\PublicOpportunityController;
use App\Http\Controllers\Api\PublicPostController;
use App\Http\Controllers\Api\PublicTypeOpportunityController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/events', [PublicEventController::class, 'index']);
Route::get('/events/{event}', [PublicEventController::class, 'show']);
Route::get('/opportunities', [PublicOpportunityController::class, 'index']);
Route::get('/opportunities/{opportunity}', [PublicOpportunityController::class, 'show']);
Route::get('/posts', [PublicPostController::class, 'index']);
Route::get('/posts/{post:slug}', [PublicPostController::class, 'show']);
Route::get('/activities', [AdminActivityController::class, 'index']);
Route::get('/gallery', [GalleryController::class, 'index']);
Route::get('/gallery-categories', [GalleryCategoryController::class, 'index']);
Route::get('/types-opportunities', [PublicTypeOpportunityController::class, 'index']);
Route::post('/opportunities', [PublicOpportunityController::class, 'store']);
Route::post('/contact', [ContactMessageController::class, 'store'])->middleware('throttle:5,1');
Route::post('/inscriptions', [InscriptionController::class, 'store'])->middleware('throttle:5,1');
Route::post('/newsletter/subscribe', [NewsletterSubscriberController::class, 'store'])->middleware('throttle:5,1');

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

Route::prefix('admin')->group(function () {
    // Public read — activities list used by the frontend without auth.
    Route::get('/activities', [AdminActivityController::class, 'index']);

    // Backward-compatible aliases.
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::middleware('role:'.Role::ADMIN.','.Role::MANAGER)->group(function () {
            Route::get('/dashboard-status', [AdminDashboardController::class, 'dashboard_status']);
            Route::get('/opportunities', [AdminOpportunityController::class, 'index']);
            Route::get('/opportunities/{opportunity}', [AdminOpportunityController::class, 'show']);
            Route::put('/opportunities/{opportunity}/accept', [AdminOpportunityController::class, 'accept']);
            Route::put('/opportunities/{opportunity}/reject', [AdminOpportunityController::class, 'reject']);
            Route::get('/inscriptions', [AdminInscriptionController::class, 'index']);
            Route::put('/inscriptions/{inscription}/payment-status', [AdminInscriptionController::class, 'updatePaymentStatus']);
        });

        Route::middleware('role:'.Role::ADMIN)->group(function () {
            Route::apiResource('/events', AdminEventController::class);
            Route::apiResource('/posts', AdminPostController::class);
            Route::post('/activities', [AdminActivityController::class, 'store']);
            Route::put('/activities/{activity}', [AdminActivityController::class, 'update']);
            Route::delete('/activities/{activity}', [AdminActivityController::class, 'destroy']);
            Route::post('/gallery', [GalleryController::class, 'store']);
            Route::put('/gallery/{gallery_image}', [GalleryController::class, 'update']);
            Route::delete('/gallery/{gallery_image}', [GalleryController::class, 'destroy']);
            Route::post('/gallery-categories', [GalleryCategoryController::class, 'store']);
            Route::put('/gallery-categories/{gallery_category}', [GalleryCategoryController::class, 'update']);
            Route::delete('/gallery-categories/{gallery_category}', [GalleryCategoryController::class, 'destroy']);
        });
    });
});
