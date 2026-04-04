<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
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
        } catch (\Throwable $e) {
            Log::error('[PublicPostController@index] ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'previous'  => $e->getPrevious()?->getMessage(),
            ]);

            return response()->json([
                'error'     => true,
                'message'   => $e->getMessage(),
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'caused_by' => $e->getPrevious()?->getMessage(),
            ], 500);
        }
    }

    public function show(string $slug): JsonResponse
    {
        try {
            $post = Post::query()
                ->published()
                ->where('slug', $slug)
                ->firstOrFail();

            $related = Post::query()
                ->published()
                ->where('id', '!=', $post->id)
                ->orderByDesc('published_at')
                ->limit(3)
                ->get();

            return response()->json([
                'post'    => $post,
                'related' => $related,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => true, 'message' => 'Post not found.'], 404);
        } catch (\Throwable $e) {
            Log::error('[PublicPostController@show] slug=' . $slug . ' — ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'previous'  => $e->getPrevious()?->getMessage(),
            ]);

            return response()->json([
                'error'     => true,
                'message'   => $e->getMessage(),
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'caused_by' => $e->getPrevious()?->getMessage(),
            ], 500);
        }
    }
}
