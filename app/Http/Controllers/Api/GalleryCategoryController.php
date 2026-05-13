<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GalleryCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = GalleryCategory::query()
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:gallery_categories,name'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data = $validator->validated();

        $category = GalleryCategory::create($data);

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, GalleryCategory $gallery_category): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:gallery_categories,name,' . $gallery_category->id],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data = $validator->validated();

        $gallery_category->update($data);

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $gallery_category->fresh(),
        ]);
    }

    public function destroy(GalleryCategory $gallery_category): JsonResponse
    {
        $gallery_category->delete();

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
        ]);
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
            if (str_contains($msg, 'name')) return 'api.error_name_required';
            return 'api.error_field_required';
        }

        if (str_contains($msg, 'string')) {
            return 'api.error_field_required';
        }

        if (str_contains($msg, 'unique')) {
            return 'api.error_already_exists';
        }

        if (str_contains($msg, 'max')) {
            return 'api.error_too_long';
        }

        return 'api.error_validation_failed';
    }
}
