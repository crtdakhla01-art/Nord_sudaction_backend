<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $request->validate([
            'gallery_categorie_id' => ['required', 'integer', 'exists:gallery_categories,id'],
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|max:51200', // 50 MB max per file
        ]);

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
            'message' => count($created) . ' image(s) uploaded.',
            'data' => $created,
        ], 201);
    }

    public function update(Request $request, GalleryImage $gallery_image): JsonResponse
    {
        $data = $request->validate([
            'gallery_categorie_id' => ['required', 'integer', 'exists:gallery_categories,id'],
        ]);

        $gallery_image->update([
            'gallery_categorie_id' => $data['gallery_categorie_id'],
        ]);

        return response()->json($gallery_image->fresh()->load('category'));
    }

    public function destroy(GalleryImage $gallery_image): JsonResponse
    {
        Storage::disk('public')->delete($gallery_image->disk_path);

        $gallery_image->delete();

        return response()->json(['message' => 'Image deleted.']);
    }
}
