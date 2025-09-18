<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'follower'  => new UserMiniResource($this->whenLoaded('follower')),
            'following' => new UserMiniResource($this->whenLoaded('following')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
