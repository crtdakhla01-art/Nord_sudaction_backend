<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $posts = Post::query()
            ->when(in_array($status, ['draft', 'published'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(is_string($search) && $search !== '', function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('media')) {
            $data['media'] = $request->file('media')->store('posts', 'public');
        }

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $post = Post::query()->create($data);

        return response()->json($post, 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json($post);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('media')) {
            if (!empty($post->media)) {
                Storage::disk('public')->delete($post->media);
            }

            $data['media'] = $request->file('media')->store('posts', 'public');
        }

        if (array_key_exists('is_featured', $data)) {
            $data['is_featured'] = (bool) $data['is_featured'];
        }

        if (($data['status'] ?? null) === 'published' && empty($data['published_at']) && empty($post->published_at)) {
            $data['published_at'] = now();
        }

        $post->update($data);

        return response()->json($post->fresh());
    }

    public function destroy(Post $post): JsonResponse
    {
        if (!empty($post->media)) {
            Storage::disk('public')->delete($post->media);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }
}
