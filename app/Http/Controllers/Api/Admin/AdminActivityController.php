<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'string', 'max:255'],
                'link' => ['required', 'url', 'max:2048'],
                'image' => ['nullable', 'image', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $validator->validated();

            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('activities', 'public');
            }

            $activity = Activity::query()->create($validated);

            return response()->json([
                'success' => true,
                'message_key' => 'api.success_operation',
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
                'success' => false,
                'error_key' => 'api.error_server_error',
            ], 500);
        }
    }

    public function update(Request $request, Activity $activity): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'link' => ['sometimes', 'required', 'url', 'max:2048'],
                'image' => ['nullable', 'image', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $validator->validated();

            if ($request->hasFile('image')) {
                if (!empty($activity->image)) {
                    Storage::disk('public')->delete($activity->image);
                }
                $validated['image'] = $request->file('image')->store('activities', 'public');
            }

            $activity->update($validated);

            return response()->json([
                'success' => true,
                'message_key' => 'api.success_operation',
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
                'success' => false,
                'error_key' => 'api.error_server_error',
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
                'success' => true,
                'message_key' => 'api.success_operation',
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
                'success' => false,
                'error_key' => 'api.error_server_error',
            ], 500);
        }
    }

    private function validationErrorResponse(\Illuminate\Contracts\Validation\Validator $validator): JsonResponse
    {
        $firstError = strtolower((string) $validator->errors()->first());

        return response()->json([
            'success' => false,
            'error_key' => $this->convertToErrorKey($firstError),
            'errors' => $validator->errors(),
        ], 422);
    }

    private function convertToErrorKey(string $message): string
    {
        $msg = strtolower($message);

        if (str_contains($msg, 'required')) {
            if (str_contains($msg, 'title')) return 'api.error_title_required';
            if (str_contains($msg, 'link')) return 'api.error_field_required';
            return 'api.error_field_required';
        }

        if (str_contains($msg, 'url')) {
            return 'api.error_field_required';
        }

        if (str_contains($msg, 'image')) {
            return 'api.error_image_invalid';
        }

        if (str_contains($msg, 'max')) {
            return 'api.error_too_long';
        }

        return 'api.error_validation_failed';
    }
}
