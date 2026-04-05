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
        $search = $request->query('search');
        $perPage = min(max((int) $request->query('per_page', 9), 1), 50);

        $posts = Post::query()
            ->published()
            ->when(is_string($search) && $search !== '', function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($posts);
    }
}
