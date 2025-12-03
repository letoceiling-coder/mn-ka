<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_id' => $this->image_id,
            'image' => $this->whenLoaded('image', function() {
                return new MediaResource($this->image);
            }),
            'icon_id' => $this->icon_id,
            'icon' => $this->whenLoaded('icon', function() {
                return new MediaResource($this->icon);
            }),
            'chapter_id' => $this->chapter_id,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'category' => 'products', // Для фронтенда
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
