<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'link' => ['required', 'url', 'max:2048'],
                'image' => ['nullable', 'image', 'max:2048'],
            ]);

            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('activities', 'public');
            }

            $activity = Activity::query()->create($validated);

            return response()->json([
                'message' => 'Activity created successfully.',
                'data' => $activity,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to create activity.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'title' => $request->input('title'),
                'link' => $request->input('link'),
                'has_image' => $request->hasFile('image'),
                'image_name' => $request->file('image')?->getClientOriginalName(),
            ]);

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error',
            ], 500);
        }
    }

    public function update(Request $request, Activity $activity): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'link' => ['sometimes', 'required', 'url', 'max:2048'],
                'image' => ['nullable', 'image', 'max:2048'],
            ]);

            if ($request->hasFile('image')) {
                if (!empty($activity->image)) {
                    Storage::disk('public')->delete($activity->image);
                }
                $validated['image'] = $request->file('image')->store('activities', 'public');
            }

            $activity->update($validated);

            return response()->json([
                'message' => 'Activity updated successfully.',
                'data' => $activity->fresh(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to update activity.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'activity_id' => $activity->id,
                'title' => $request->input('title'),
                'link' => $request->input('link'),
                'has_image' => $request->hasFile('image'),
                'image_name' => $request->file('image')?->getClientOriginalName(),
                'current_image' => $activity->image,
            ]);

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error',
            ], 500);
        }
    }

    public function destroy(Activity $activity): JsonResponse
    {
        try {
            if (!empty($activity->image)) {
                Storage::disk('public')->delete($activity->image);
            }

            $activity->delete();

            return response()->json([
                'message' => 'Activity deleted successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to delete activity.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'activity_id' => $activity->id,
                'current_image' => $activity->image,
            ]);

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error',
            ], 500);
        }
    }
}
