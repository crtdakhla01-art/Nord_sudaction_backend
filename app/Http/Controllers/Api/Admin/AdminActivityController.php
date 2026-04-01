<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminActivityController extends Controller
{
    public function index(): JsonResponse
    {
        $activities = Activity::query()
            ->latest()
            ->get();

        return response()->json($activities);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'link' => ['required', 'url', 'max:2048'],
        ]);

        $activity = Activity::query()->create($validated);

        return response()->json([
            'message' => 'Activity created successfully.',
            'data' => $activity,
        ], 201);
    }

    public function update(Request $request, Activity $activity): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'link' => ['sometimes', 'required', 'url', 'max:2048'],
        ]);

        $activity->update($validated);

        return response()->json([
            'message' => 'Activity updated successfully.',
            'data' => $activity->fresh(),
        ]);
    }

    public function destroy(Activity $activity): JsonResponse
    {
        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully.',
        ]);
    }
}
