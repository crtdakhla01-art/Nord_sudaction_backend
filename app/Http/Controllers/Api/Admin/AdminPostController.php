<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\PostPublished;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\HtmlSanitizationService;
use Illuminate\Database\QueryException;
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

        // Sanitize rich HTML content to prevent XSS.
        $sanitizer = new HtmlSanitizationService();
        $data['content'] = $sanitizer->sanitize($data['content'] ?? '');

        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $post = $this->createPostWithSlugRetry($data);

        if ($post->status === 'published') {
            event(new PostPublished($post->id));
        }

        return response()->json($post, 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json($post);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $wasPublished = $post->status === 'published';
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

        // Sanitize rich HTML content to prevent XSS.
        if (array_key_exists('content', $data)) {
            $sanitizer = new HtmlSanitizationService();
            $data['content'] = $sanitizer->sanitize($data['content'] ?? '');
        }

        if (($data['status'] ?? null) === 'published' && empty($data['published_at']) && empty($post->published_at)) {
            $data['published_at'] = now();
        }

        $this->updatePostWithSlugRetry($post, $data);

        if (! $wasPublished && $post->fresh()->status === 'published') {
            event(new PostPublished($post->id));
        }

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

    private function createPostWithSlugRetry(array $data): Post
    {
        $maxAttempts = 3;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                return Post::query()->create($data);
            } catch (QueryException $exception) {
                if (! $this->isSlugDuplicateException($exception) || $attempt === $maxAttempts - 1) {
                    throw $exception;
                }

                $data['slug'] = Post::generateRetrySlug((string) ($data['title'] ?? 'post'));
            }
        }

        throw new \RuntimeException('Unable to create post with a unique slug.');
    }

    private function updatePostWithSlugRetry(Post $post, array $data): void
    {
        $maxAttempts = 3;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $post->update($data);
                return;
            } catch (QueryException $exception) {
                if (! $this->isSlugDuplicateException($exception) || $attempt === $maxAttempts - 1) {
                    throw $exception;
                }

                $data['slug'] = Post::generateRetrySlug((string) ($data['title'] ?? $post->title));
            }
        }

        throw new \RuntimeException('Unable to update post with a unique slug.');
    }

    private function isSlugDuplicateException(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'duplicate')
            && str_contains($message, 'slug')
            && str_contains($message, 'posts');
    }
}
