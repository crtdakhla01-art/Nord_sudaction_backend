<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GalleryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);

        $galleryImages = GalleryImage::query()
            ->with('category')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json($galleryImages);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'gallery_categorie_id' => ['required', 'integer', 'exists:gallery_categories,id'],
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|max:51200', // 50 MB max per file
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $processor = new ImageProcessingService();
        $created   = [];

        foreach ($request->file('images') as $file){
            $result = $processor->processUpload($file);

            if (! $result) {
                continue;
            }

            $created[] = GalleryImage::create([
                'filename' => $result['filename'],
                'disk_path' => $result['disk_path'],
                'gallery_categorie_id' => (int) $request->input('gallery_categorie_id'),
            ]);
        }

        $created = GalleryImage::query()
            ->with('category')
            ->whereIn('id', collect($created)->pluck('id'))
            ->get();

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $created,
        ], 201);
    }

    public function update(Request $request, GalleryImage $gallery_image): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'gallery_categorie_id' => ['required', 'integer', 'exists:gallery_categories,id'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data = $validator->validated();

        $gallery_image->update([
            'gallery_categorie_id' => $data['gallery_categorie_id'],
        ]);

        return response()->json($gallery_image->fresh()->load('category'));
    }

    public function destroy(GalleryImage $gallery_image): JsonResponse
    {
        Storage::disk('public')->delete($gallery_image->disk_path);

        $gallery_image->delete();

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
            if (str_contains($msg, 'gallery_categorie_id')) return 'api.error_field_required';
            if (str_contains($msg, 'images')) return 'api.error_file_invalid';
            return 'api.error_field_required';
        }

        if (str_contains($msg, 'exists')) {
            return 'api.error_not_found';
        }

        if (str_contains($msg, 'integer') || str_contains($msg, 'numeric')) {
            return 'api.error_must_be_numeric';
        }

        if (str_contains($msg, 'image')) {
            return 'api.error_image_invalid';
        }

        if (str_contains($msg, 'max')) {
            return 'api.error_file_too_large';
        }

        return 'api.error_validation_failed';
    }
}
