<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $page    = max(1, (int) $request->query('page', 1));
        $sortBy  = $request->query('sort_by', 'created_at');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $userId  = $request->query('user_id');

        $allowedSort = ['created_at', 'content'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }

        $ttl = max(5, min(300, (int) $request->query('ttl', 30)));

        $version = Cache::get('posts.index.version', 1);

        $cacheKey = sprintf(
            'posts.index:v%s:u%s:r%s:p%s:pg%s:sb%s:sd%s:f%s',
            $version,
            $auth->id,
            $auth->role,
            $perPage,
            $page,
            $sortBy,
            $sortDir,
            (int) ($userId ?? 0)
        );

        $payload = Cache::remember($cacheKey, now()->addSeconds($ttl), function () use (
            $auth,
            $perPage,
            $page,
            $sortBy,
            $sortDir,
            $userId,
            $request
        ) {
            $q = Post::query()
                ->with(['user'])
                ->withCount('comments');

            if ($auth->isAdmin()) {
                if ($userId) {
                    $q->where('user_id', (int) $userId);
                }
            } else {
                $followingIds = $auth->following()->pluck('users.id');
                $q->whereIn('user_id', $followingIds);
                if ($userId) {
                    $q->where('user_id', (int) $userId);
                }
            }

            $q->orderBy($sortBy, $sortDir);

            $paginator = $q->paginate($perPage, ['*'], 'page', $page);

            $items = $paginator->items();
            $posts = array_map(
                fn($post) => (new \App\Http\Resources\PostResource($post))->toArray($request),
                $items
            );

            return [
                'posts'    => $posts,
                'per_page' => (int) $paginator->perPage(),
                'page'     => (int) $paginator->currentPage(),
                'total'    => (int) $paginator->total(),
            ];
        });

        return response()->json($payload);
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if ($auth->isAdmin()) {
            return response()->json(['message' => 'Admins cannot create posts'], 403);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:280'],
        ]);

        $post = Post::create([
            'user_id' => $auth->id,
            'content' => $data['content'],
        ]);

        $post->load('user')->loadCount('comments');

        return response()->json([
            'message' => 'Post created successfully',
            'post' => new PostResource($post),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $post->load(['user'])->loadCount('comments');
        return new PostResource($post);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if ($auth->isAdmin()) {
            return response()->json(['message' => 'Admins cannot update posts'], 403);
        }
        if ((int) $post->user_id !== (int) $auth->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:280'],
        ]);

        $post->update([
            'content' => $data['content'],
        ]);

        $post->load('user')->loadCount('comments');

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => new PostResource($post),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Post $post)
    {
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if ($auth->isAdmin()) {
            return response()->json(['message' => 'Admins cannot delete posts'], 403);
        }
        if ((int) $post->user_id !== (int) $auth->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted',
        ], 200);
    }
}
