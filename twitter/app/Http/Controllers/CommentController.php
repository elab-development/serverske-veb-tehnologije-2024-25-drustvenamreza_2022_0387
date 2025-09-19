<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
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

        $postId = (int) $request->query('post_id', 0);
        if ($postId <= 0) {
            return response()->json(['message' => 'post_id is required'], 422);
        }

        if (!Post::whereKey($postId)->exists()) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $search = trim((string) $request->query('q', ''));

        $q = Comment::query()
            ->where('post_id', $postId)
            ->with('user');

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('content', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $comments = $q->orderByDesc('id')
            ->get()
            ->map(fn($c) => (new CommentResource($c))->toArray($request))
            ->all();

        return response()->json([
            'comments' => $comments,
        ]);
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
            return response()->json(['message' => 'Admins cannot create comments'], 403);
        }

        $data = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'content' => ['required', 'string', 'max:280'],
        ]);

        $comment = Comment::create([
            'user_id' => $auth->id,
            'post_id' => $data['post_id'],
            'content' => $data['content'],
        ]);

        $comment->load('user');

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => new CommentResource($comment),
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Comment $comment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comment $comment)
    {
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if ($auth->isAdmin()) {
            return response()->json(['message' => 'Admins cannot update comments'], 403);
        }
        if ((int) $comment->user_id !== (int) $auth->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:280'],
        ]);

        $comment->update([
            'content' => $data['content'],
        ]);

        $comment->load('user');

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => new CommentResource($comment),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Comment $comment)
    {
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if ($auth->isAdmin()) {
            return response()->json(['message' => 'Admins cannot delete comments'], 403);
        }
        if ((int) $comment->user_id !== (int) $auth->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted',
        ], 200);
    }
}
