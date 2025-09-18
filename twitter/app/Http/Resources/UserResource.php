<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when(
                $viewer && ($viewer->id === $this->id || $viewer->isAdmin()),
                $this->email
            ),
            'role' => $this->role,
            'bio'  => $this->bio,

            'counts' => [
                'posts' => $this->whenCounted('posts'),
                'followers' => $this->whenCounted('followers'),
                'following' => $this->whenCounted('following'),
            ],

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
