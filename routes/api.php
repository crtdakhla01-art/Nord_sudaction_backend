<?php

use App\Http\Controllers\Api\Admin\AdminActivityController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminOpportunityController;
use App\Http\Controllers\Api\Admin\AdminPostController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\PublicEventController;
use App\Http\Controllers\Api\PublicOpportunityController;
use App\Http\Controllers\Api\PublicPostController;
use App\Http\Controllers\Api\PublicTypeOpportunityController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// ─── TEMPORARY DIAGNOSTIC ROUTE — remove once the 500 is fixed ────────────────
Route::get('/debug-posts', function () {
    $result = ['steps' => []];

    // 1. DB connection
    try {
        DB::connection()->getPdo();
        $result['steps']['db_connect'] = 'OK';
    } catch (\Throwable $e) {
        $result['steps']['db_connect'] = $e->getMessage();
        return response()->json($result, 500);
    }

    // 2. Posts table exists?
    try {
        $result['steps']['table_exists'] = Schema::hasTable('posts') ? 'YES' : 'NO';
    } catch (\Throwable $e) {
        $result['steps']['table_exists'] = $e->getMessage();
    }

    // 3. Columns present
    try {
        $result['steps']['columns'] = Schema::getColumnListing('posts');
    } catch (\Throwable $e) {
        $result['steps']['columns'] = $e->getMessage();
    }

    // 4. Raw SELECT first published post
    try {
        $row = DB::table('posts')->where('status', 'published')->first();
        $result['steps']['select_published'] = $row ? (array) $row : 'NO PUBLISHED POSTS';
    } catch (\Throwable $e) {
        $result['steps']['select_published'] = [
            'error'      => $e->getMessage(),
            'exception'  => get_class($e),
            'caused_by'  => $e->getPrevious()?->getMessage(),
        ];
    }

    // 5. Eloquent query with scope (exactly what PublicPostController does)
    try {
        $post = \App\Models\Post::query()->published()->first();
        $result['steps']['eloquent_published'] = $post ? $post->toArray() : 'NULL';
    } catch (\Throwable $e) {
        $result['steps']['eloquent_published'] = [
            'error'     => $e->getMessage(),
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'caused_by' => $e->getPrevious()?->getMessage(),
        ];
    }

    // 6. Pending migrations
    try {
        $pending = \Illuminate\Support\Facades\Artisan::call('migrate:status', ['--no-interaction' => true]);
        $result['steps']['migrate_status_exit'] = $pending;
    } catch (\Throwable $e) {
        $result['steps']['migrate_status'] = $e->getMessage();
    }

    return response()->json($result);
});
// ─────────────────────────────────────────────────────────────────────────────

Route::get('/events', [PublicEventController::class, 'index']);
Route::get('/events/{event}', [PublicEventController::class, 'show']);
Route::get('/opportunities', [PublicOpportunityController::class, 'index']);
Route::get('/opportunities/{opportunity}', [PublicOpportunityController::class, 'show']);
Route::get('/posts', [PublicPostController::class, 'index']);
Route::get('/posts/{slug}', [PublicPostController::class, 'show']);
Route::get('/activities', [AdminActivityController::class, 'index']);
Route::get('/gallery', [GalleryController::class, 'index']);
Route::get('/types-opportunities', [PublicTypeOpportunityController::class, 'index']);
Route::post('/opportunities', [PublicOpportunityController::class, 'store']);
Route::post('/contact', [ContactMessageController::class, 'store'])->middleware('throttle:5,1');

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

        Route::middleware('role:admin,manager')->group(function () {
            Route::get('/dashboard-status', [AdminDashboardController::class, 'dashboard_status']);
            Route::get('/opportunities', [AdminOpportunityController::class, 'index']);
            Route::get('/opportunities/{opportunity}', [AdminOpportunityController::class, 'show']);
            Route::put('/opportunities/{opportunity}/accept', [AdminOpportunityController::class, 'accept']);
            Route::put('/opportunities/{opportunity}/reject', [AdminOpportunityController::class, 'reject']);
        });

        Route::middleware('role:admin')->group(function () {
            Route::apiResource('/events', AdminEventController::class);
            Route::apiResource('/posts', AdminPostController::class);
            Route::post('/activities', [AdminActivityController::class, 'store']);
            Route::put('/activities/{activity}', [AdminActivityController::class, 'update']);
            Route::delete('/activities/{activity}', [AdminActivityController::class, 'destroy']);
            Route::post('/gallery', [GalleryController::class, 'store']);
            Route::delete('/gallery/{gallery_image}', [GalleryController::class, 'destroy']);
        });
    });
});
