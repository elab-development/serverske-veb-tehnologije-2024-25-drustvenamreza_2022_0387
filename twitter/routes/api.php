<?php

use App\Http\Controllers\AdminExternalStatsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);

       Route::get('/follows', [FollowController::class, 'index']);
    Route::get('/users/{user}/followers', [FollowController::class, 'followers']);
    Route::get('/users/{user}/following', [FollowController::class, 'following']);
    Route::post('/users/{user}/follow', [FollowController::class, 'follow']);
    Route::delete('/users/{user}/follow', [FollowController::class, 'unfollow']);
    Route::get('/users/{user}/follow-status', [FollowController::class, 'status']);

    Route::resource('posts', PostController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::resource('comments', CommentController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('/admin/stats/hn-tags', [AdminExternalStatsController::class, 'hnPopularKeywords']);
    Route::get('/admin/stats/guardian-tags', [AdminExternalStatsController::class, 'guardianPopularTags']);
});