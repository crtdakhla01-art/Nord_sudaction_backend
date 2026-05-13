<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Inscription;
use App\Models\Opportunity;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminDashboardController extends Controller
{
    public function dashboard_status(): JsonResponse
    {
        try {
            $today = Carbon::today();

            $safeCount = function (string $metric, callable $query): int {
                try {
                    return (int) $query();
                } catch (Throwable $exception) {
                    try {
                        Log::error('Admin dashboard metric query failed', [
                            'metric' => $metric,
                            'error' => $exception->getMessage(),
                        ]);
                    } catch (Throwable $loggingException) {
                        // Keep dashboard response stable even if logging transport is broken.
                    }

                    return 0;
                }
            };

            $data = [
                'generated_at' => now()->toIso8601String(),
                'users' => [
                    'total' => $safeCount('users.total', fn () => User::query()->count()),
                    'admins' => $safeCount('users.admins', fn () => User::query()->whereHas('role', fn ($query) => $query->where('name', Role::ADMIN))->count()),
                    'managers' => $safeCount('users.managers', fn () => User::query()->whereHas('role', fn ($query) => $query->where('name', Role::MANAGER))->count()),
                ],
                'opportunities' => [
                    'total' => $safeCount('opportunities.total', fn () => Opportunity::query()->count()),
                    'pending' => $safeCount('opportunities.pending', fn () => Opportunity::query()->where('status', 'pending')->count()),
                    'accepted' => $safeCount('opportunities.accepted', fn () => Opportunity::query()->where('status', 'accepted')->count()),
                    'rejected' => $safeCount('opportunities.rejected', fn () => Opportunity::query()->where('status', 'rejected')->count()),
                ],
                'events' => [
                    'total' => $safeCount('events.total', fn () => Event::query()->count()),
                    'upcoming' => $safeCount('events.upcoming', fn () => Event::query()
                        ->whereDate('date', '>=', $today)
                        ->where('is_it_passed', false)
                        ->count()),
                    'passed' => $safeCount('events.passed', fn () => Event::query()
                        ->where(function ($query) use ($today) {
                            $query->where('is_it_passed', true)
                                ->orWhereDate('date', '<', $today);
                        })
                        ->count()),
                ],
                'posts' => [
                    'total' => $safeCount('posts.total', fn () => Post::query()->count()),
                    'published' => $safeCount('posts.published', fn () => Post::query()->where('status', 'published')->count()),
                    'draft' => $safeCount('posts.draft', fn () => Post::query()->where('status', 'draft')->count()),
                    'featured' => $safeCount('posts.featured', fn () => Post::query()->where('is_featured', true)->count()),
                    // Kept for backward compatibility after removing posts.view_count.
                    'total_views' => 0,
                ],
                'contacts' => [
                    'total' => $safeCount('contacts.total', fn () => ContactMessage::query()->count()),
                    'last_7_days' => $safeCount('contacts.last_7_days', fn () => ContactMessage::query()
                        ->where('created_at', '>=', now()->subDays(7))
                        ->count()),
                ],
                'inscriptions' => [
                    'total' => $safeCount('inscriptions.total', fn () => Inscription::query()->count()),
                    'paid' => $safeCount('inscriptions.paid', fn () => Inscription::query()->where('is_paid', true)->count()),
                    'unpaid' => $safeCount('inscriptions.unpaid', fn () => Inscription::query()->where('is_paid', false)->count()),
                ],
            ];

            return response()->json([
                'success' => true,
                'message_key' => 'api.success_operation',
                'data' => $data,
            ]);
        } catch (Throwable $exception) {
            try {
                Log::error('Admin dashboard status failed', [
                    'error' => $exception->getMessage(),
                ]);
            } catch (Throwable $loggingException) {
                // Avoid surfacing logger failures to clients.
            }

            return response()->json([
                'success' => true,
                'message_key' => 'api.success_operation',
                'data' => [
                    'generated_at' => now()->toIso8601String(),
                    'users' => ['total' => 0, 'admins' => 0, 'managers' => 0],
                    'opportunities' => ['total' => 0, 'pending' => 0, 'accepted' => 0, 'rejected' => 0],
                    'events' => ['total' => 0, 'upcoming' => 0, 'passed' => 0],
                    'posts' => ['total' => 0, 'published' => 0, 'draft' => 0, 'featured' => 0, 'total_views' => 0],
                    'contacts' => ['total' => 0, 'last_7_days' => 0],
                    'inscriptions' => ['total' => 0, 'paid' => 0, 'unpaid' => 0],
                ],
            ]);
        }
    }
}
