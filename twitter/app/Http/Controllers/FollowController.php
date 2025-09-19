<?php

namespace App\Http\Controllers;

use App\Http\Resources\FollowResource;
use App\Http\Resources\UserMiniResource;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $auth = $request->user();
        if (!$auth || !$auth->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $follows = Follow::with(['follower', 'following'])
            ->when($request->filled('follower_id'), fn($q) =>
            $q->where('follower_id', (int) $request->input('follower_id')))
            ->when($request->filled('following_id'), fn($q) =>
            $q->where('following_id', (int) $request->input('following_id')))
            ->orderByDesc('id')
            ->get();

        return  FollowResource::collection($follows);
    }

    public function followers(User $user)
    {
        $followers = $user->followers()->get();
        return UserMiniResource::collection($followers);
    }

    public function following(User $user)
    {
        $following = $user->following()->get();
        return UserMiniResource::collection($following);
    }

    public function follow(Request $request, User $user)
    {
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($auth->isAdmin()) {
            return response()->json(['message' => 'Admins cannot follow users'], 403);
        }

        if ((int) $auth->id === (int) $user->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 422);
        }

        if ($user->isAdmin()) {
            return response()->json(['message' => 'You cannot follow admin accounts'], 403);
        }

        $follow = Follow::firstOrCreate([
            'follower_id'  => $auth->id,
            'following_id' => $user->id,
        ]);

        $follow->load(['follower', 'following']);

        return response()->json([
            'message' => $follow->wasRecentlyCreated ? 'Followed' : 'Already following',
            'follow'  => new FollowResource($follow),
        ], $follow->wasRecentlyCreated ? 201 : 200);
    }

    public function unfollow(Request $request, User $user)
    {
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($auth->isAdmin()) {
            return response()->json(['message' => 'Admins cannot unfollow users'], 403);
        }

        $deleted = Follow::where('follower_id', $auth->id)
            ->where('following_id', $user->id)
            ->delete();

        return response()->json([
            'message' => $deleted ? 'Unfollowed' : 'Not following',
        ], 200);
    }

    public function status(Request $request, User $user)
    {
        $auth = $request->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($auth->isAdmin()) {
            return response()->json(['message' => 'You cannot follow anyone'], 403);
        }

        return response()->json([
            'user_id'      => $user->id,
            'is_following' => $auth->isFollowing($user),
        ]);
    }
}
