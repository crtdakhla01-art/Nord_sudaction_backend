<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $search = $request->query('search');
        $perPage = min(max((int) $request->query('per_page', 9), 1), 50);

        $posts = Post::query()
            ->published()
            ->when(in_array($type, ['article', 'communique', 'media'], true), function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when(is_string($search) && $search !== '', function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($posts);
    }

    public function show(string $slug): JsonResponse
    {
        $post = Post::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $post->increment('view_count');

        $related = Post::query()
            ->published()
            ->where('type', $post->type)
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return response()->json([
            'post' => $post->fresh(),
            'related' => $related,
        ]);
    }
}
