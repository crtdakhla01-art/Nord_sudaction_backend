<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:gallery_categories,name'],
        ]);

        $category = GalleryCategory::create($data);

        return response()->json($category, 201);
    }

    public function update(Request $request, GalleryCategory $gallery_category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:gallery_categories,name,' . $gallery_category->id],
        ]);

        $gallery_category->update($data);

        return response()->json($gallery_category->fresh());
    }

    public function destroy(GalleryCategory $gallery_category): JsonResponse
    {
        $gallery_category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
