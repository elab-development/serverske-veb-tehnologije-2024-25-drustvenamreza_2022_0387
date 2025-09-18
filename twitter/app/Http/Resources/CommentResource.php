<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'post_id' => $this->post_id,
            'content' => $this->content,
            'user' => new UserMiniResource($this->whenLoaded('user')),

            'is_owner' => $this->when(
                $viewer !== null,
                fn() => (int) $this->user_id === (int) $viewer->id
            ),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
