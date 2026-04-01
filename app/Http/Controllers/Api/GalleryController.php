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
    public function index(): JsonResponse
    {
        $galleryImages = GalleryImage::query()
            ->orderBy('id')
            ->paginate(30);

        return response()->json($galleryImages);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'images'   => 'required|array|min:1',
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
                'filename'  => $result['filename'],
                'disk_path' => $result['disk_path'],
            ]);
        }

        return response()->json([
            'message' => count($created) . ' image(s) uploaded.',
            'data'    => $created,
        ], 201);
    }

    public function destroy(GalleryImage $gallery_image): JsonResponse
    {
        Storage::disk('public')->delete($gallery_image->disk_path);

        $gallery_image->delete();

        return response()->json(['message' => 'Image deleted.']);
    }
}
