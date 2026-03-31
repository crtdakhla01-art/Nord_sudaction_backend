<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Opportunity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public function dashboard_status(): JsonResponse
    {
        $today = Carbon::today();

        $data = [
            'generated_at' => now()->toIso8601String(),
            'users' => [
                'total' => User::query()->count(),
                'admins' => User::query()->whereHas('role', fn ($query) => $query->where('name', 'admin'))->count(),
                'managers' => User::query()->whereHas('role', fn ($query) => $query->where('name', 'manager'))->count(),
            ],
            'opportunities' => [
                'total' => Opportunity::query()->count(),
                'pending' => Opportunity::query()->where('status', 'pending')->count(),
                'accepted' => Opportunity::query()->where('status', 'accepted')->count(),
                'rejected' => Opportunity::query()->where('status', 'rejected')->count(),
            ],
            'events' => [
                'total' => Event::query()->count(),
                'upcoming' => Event::query()
                    ->whereDate('date', '>=', $today)
                    ->where('is_it_passed', false)
                    ->count(),
                'passed' => Event::query()
                    ->where(function ($query) use ($today) {
                        $query->where('is_it_passed', true)
                            ->orWhereDate('date', '<', $today);
                    })
                    ->count(),
            ],
            'posts' => [
                'total' => Post::query()->count(),
                'published' => Post::query()->where('status', 'published')->count(),
                'draft' => Post::query()->where('status', 'draft')->count(),
                'featured' => Post::query()->where('is_featured', true)->count(),
                'total_views' => (int) Post::query()->sum('view_count'),
            ],
            'contacts' => [
                'total' => ContactMessage::query()->count(),
                'last_7_days' => ContactMessage::query()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
            ],
        ];

        return response()->json([
            'message' => 'Dashboard status fetched successfully.',
            'data' => $data,
        ]);
    }
}
